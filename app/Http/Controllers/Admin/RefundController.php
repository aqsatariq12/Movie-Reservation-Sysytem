<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundIndexRequest;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Builder;
use App\Actions\Refunds\RetryRefundAction;
use Illuminate\Http\RedirectResponse;

class RefundController extends Controller
{
    private const PER_PAGE = 6;

    public function index(RefundIndexRequest $request)
    {
        $refunds = Refund::query()
            ->with([
                'payment.booking.user:id,name,email',
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $term = trim($request->validated('search'));

                $query->where(function (Builder $search) use ($term) {
                    $search
                        ->where('refunds.id', is_numeric($term) ? (int) $term : 0)
                        ->orWhere('refunds.provider_refund_id', 'like', "%{$term}%")
                        ->orWhere('refunds.reason', 'like', "%{$term}%")
                        ->orWhereHas('payment.booking', function (Builder $booking) use ($term) {
                            $booking->where('booking_reference', 'like', "%{$term}%");
                        })
                        ->orWhereHas('payment.booking.user', function (Builder $user) use ($term) {
                            $user->search($term);
                        });
                });
            })
            ->when(
                $request->filled('status'),
                fn(Builder $query) =>
                    $query->where('refunds.status', $request->validated('status'))
            )
            ->when(
                $request->filled('from'),
                fn(Builder $query) =>
                    $query->where('refunds.requested_at', '>=', now()->parse($request->validated('from'))->startOfDay())
            )
            ->when(
                $request->filled('to'),
                fn(Builder $query) =>
                    $query->where('refunds.requested_at', '<=', now()->parse($request->validated('to'))->endOfDay())
            )
            ->orderByDesc('refunds.requested_at')
            ->orderByDesc('refunds.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.refunds.index', [
            'refunds' => $refunds,
        ]);
    }

    public function retry(
        Refund $refund,
        RetryRefundAction $action
    ): RedirectResponse {
        $newRefund = $action->execute($refund);

        return redirect()
            ->route('admin.refunds.index')
            ->with(
                'success',
                "Refund #{$newRefund->id} is now {$newRefund->status->value}."
            );
    }
}
