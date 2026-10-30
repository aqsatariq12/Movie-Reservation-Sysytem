<?php

namespace App\Actions\Refunds;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Services\StripePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RetryRefundAction
{
    private const ALLOWED_REASONS = [
        'late_payment_after_booking_cancelled',
        'customer_booking_cancelled',
        'admin_booking_cancelled',
    ];

    public function __construct(
        private StripePaymentService $stripe
    ) {
    }

    public function execute(Refund $refund): Refund
    {
        $target = DB::transaction(function () use ($refund) {
            $original = Refund::query()
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            $isPending = $original->status === RefundStatus::Pending;

            if (!$isPending && !in_array($original->status, [
                RefundStatus::Failed,
                RefundStatus::Canceled,
            ], true)) {
                throw ValidationException::withMessages([
                    'refund' => 'Only pending, failed, or canceled refunds can be processed.',
                ]);
            }

            if (!in_array($original->reason, self::ALLOWED_REASONS, true)) {
                throw ValidationException::withMessages([
                    'refund' => 'This refund reason is not eligible for Stripe processing.',
                ]);
            }

            if ($isPending && $original->provider_refund_id !== null) {
                throw ValidationException::withMessages([
                    'refund' => 'This pending refund already has a Stripe refund reference.',
                ]);
            }

            if (!$isPending && (
                !is_string($original->provider_refund_id) ||
                !str_starts_with($original->provider_refund_id, 're_')
            )) {
                throw ValidationException::withMessages([
                    'refund' => 'This refund does not have a valid Stripe refund reference.',
                ]);
            }

            $payment = $original->payment()
                ->lockForUpdate()
                ->first();

            if (
                !$payment ||
                $payment->provider !== 'stripe' ||
                blank($payment->provider_payment_id) ||
                $payment->status !== PaymentStatus::Paid
            ) {
                throw ValidationException::withMessages([
                    'refund' => 'The original Stripe payment is not eligible for refund processing.',
                ]);
            }

            if ($original->amount_cents <= 0) {
                throw ValidationException::withMessages([
                    'refund' => 'The refund amount must be positive.',
                ]);
            }

            if ($isPending) {
                $target = $original;
            } else {
                if ($payment->refunds()
                    ->where('status', RefundStatus::Pending->value)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'refund' => 'A refund attempt is already pending for this payment.',
                    ]);
                }

                $target = $payment->refunds()->create([
                    'retry_of_refund_id' => $original->id,
                    'amount_cents' => (int) $original->amount_cents,
                    'status' => RefundStatus::Pending,
                    'reason' => $original->reason,
                    'requested_at' => now(),
                ]);
            }

            $committed = (int) $payment->refunds()
                ->whereIn('status', [
                    RefundStatus::Pending->value,
                    RefundStatus::Succeeded->value,
                ])
                ->sum('amount_cents');

            if ($committed > (int) $payment->amount_cents) {
                throw ValidationException::withMessages([
                    'refund' => 'The total pending and successful refunds exceed the original payment amount.',
                ]);
            }

            return $target;
        });

        try {
            $target->refresh();
            $payment = $target->payment;

            if (
                !$payment ||
                $payment->provider !== 'stripe' ||
                blank($payment->provider_payment_id) ||
                $payment->status !== PaymentStatus::Paid
            ) {
                throw new \RuntimeException(
                    'The Stripe payment is no longer eligible for refund processing.'
                );
            }

            if (
                $target->status !== RefundStatus::Pending ||
                $target->provider_refund_id !== null
            ) {
                throw new \RuntimeException(
                    'This refund is no longer eligible for processing.'
                );
            }

            $existing = $this->stripe->retrieveRefundsForPaymentIntent(
                $payment->provider_payment_id
            );

            foreach ($existing->data as $existingRefund) {
                if (
                    (string) ($existingRefund->metadata->local_refund_id ?? '')
                    === (string) $target->id
                ) {
                    $status = match ((string) $existingRefund->status) {
                        'succeeded' => RefundStatus::Succeeded,
                        'failed' => RefundStatus::Failed,
                        'canceled' => RefundStatus::Canceled,
                        default => RefundStatus::Pending,
                    };

                    DB::table('refunds')
                        ->where('id', $target->id)
                        ->whereNull('provider_refund_id')
                        ->update([
                            'provider_refund_id' => $existingRefund->id,
                            'status' => $status->value,
                            'completed_at' => in_array($status, [
                                RefundStatus::Succeeded,
                                RefundStatus::Failed,
                                RefundStatus::Canceled,
                            ], true) ? now() : null,
                            'updated_at' => now(),
                        ]);

                    return $target->fresh();
                }
            }

            $stripeRefund = $this->stripe->createRefund(
                $payment->provider_payment_id,
                (int) $target->amount_cents,
                'local-refund-' . $target->id,
                ['local_refund_id' => (string) $target->id]
            );

            $status = match ((string) $stripeRefund->status) {
                'succeeded' => RefundStatus::Succeeded,
                'failed' => RefundStatus::Failed,
                'canceled' => RefundStatus::Canceled,
                default => RefundStatus::Pending,
            };

            DB::table('refunds')
                ->where('id', $target->id)
                ->whereNull('provider_refund_id')
                ->update([
                    'provider_refund_id' => $stripeRefund->id,
                    'status' => $status->value,
                    'completed_at' => in_array($status, [
                        RefundStatus::Succeeded,
                        RefundStatus::Failed,
                        RefundStatus::Canceled,
                    ], true) ? now() : null,
                    'updated_at' => now(),
                ]);

            if ($status === RefundStatus::Succeeded) {
                $successful = (int) DB::table('refunds')
                    ->where('payment_id', $payment->id)
                    ->where('status', RefundStatus::Succeeded->value)
                    ->sum('amount_cents');

                if ($successful >= (int) $payment->amount_cents) {
                    $payment->update([
                        'status' => PaymentStatus::Refunded,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::error('Admin refund processing failed.', [
                'refund_id' => $target->id,
                'payment_id' => $target->payment_id,
                'message' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'refund' => 'Refund processing failed. Check the refund record and Stripe before trying again.',
            ]);
        }

        return $target->fresh();
    }
}
