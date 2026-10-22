<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Hold;
use App\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('app:expire-holds')]
#[Description('Expire overdue holds and release their seats safely')]
class ExpireHolds extends Command
{
    public function handle(): int
    {
        $expiredCount = 0;
        $skippedCount = 0;

        $holdIds = Hold::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->pluck('id');

        foreach ($holdIds as $holdId) {
            try {
                $result = DB::transaction(function () use ($holdId) {
                    $hold = Hold::query()
                        ->whereKey($holdId)
                        ->lockForUpdate()
                        ->first();

                    if (
                        !$hold ||
                        $hold->status !== 'active' ||
                        !$hold->expires_at ||
                        $hold->expires_at->isFuture()
                    ) {
                        return 'skipped';
                    }

                    $bookings = Booking::query()
                        ->where('hold_id', $hold->id)
                        ->lockForUpdate()
                        ->get();

                    foreach ($bookings as $booking) {
                        $payments = Payment::query()
                            ->where('booking_id', $booking->id)
                            ->lockForUpdate()
                            ->get();

                        // Never release seats for paid or refunded payments.
                        if ($payments->contains(
                            fn ($payment) => in_array(
                                $payment->status,
                                ['paid', 'refunded'],
                                true
                            )
                        )) {
                            return 'skipped';
                        }

                        // Do not cancel a booking that is already confirmed.
                        if ($booking->status === 'confirmed') {
                            return 'skipped';
                        }

                        // An open Stripe session must be reconciled with Stripe
                        // before its seats can safely be released.
                        if ($payments->contains(
                            fn ($payment) =>
                                $payment->provider === 'stripe' &&
                                $payment->status === 'pending' &&
                                !empty($payment->provider_session_id)
                        )) {
                            return 'skipped';
                        }

                        foreach ($payments as $payment) {
                            if ($payment->status === 'pending') {
                                $payment->update([
                                    'status' => 'expired',
                                ]);
                            }
                        }

                        if ($booking->status === 'pending_payment') {
                            $booking->update([
                                'status' => 'cancelled',
                                'cancelled_at' => now(),
                            ]);
                        }
                    }

                    DB::table('showtime_seats')
                        ->where('hold_id', $hold->id)
                        ->where('status', 'held')
                        ->update([
                            'status' => 'available',
                            'hold_id' => null,
                            'booking_id' => null,
                            'held_until' => null,
                            'updated_at' => now(),
                        ]);

                    $hold->update([
                        'status' => 'expired',
                    ]);

                    return 'expired';
                });

                if ($result === 'expired') {
                    $expiredCount++;
                    $this->info("Hold {$holdId}: expired; held seats released.");
                } else {
                    $skippedCount++;
                    $this->warn("Hold {$holdId}: skipped for safety.");
                }
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Hold {$holdId}: {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Expired holds: {$expiredCount}");
        $this->info("Skipped holds: {$skippedCount}");

        return self::SUCCESS;
    }
}
