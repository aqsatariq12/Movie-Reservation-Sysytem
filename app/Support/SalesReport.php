<?php

namespace App\Support;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Movie;
use Illuminate\Database\Query\Builder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReport
{
    public readonly CarbonImmutable $from;
    public readonly CarbonImmutable $to;

    public function __construct(CarbonInterface $from, CarbonInterface $to, private ?int $movieId = null)
    {
        $this->from = $from->toImmutable()->startOfDay();
        $this->to = $to->toImmutable()->endOfDay();
    }
    /** Payments that count as real sales. */
    private function sales(): Builder
    {
        return DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->whereIn('payments.status', [PaymentStatus::Paid->value, PaymentStatus::Refunded->value])
            ->whereNotNull('payments.paid_at')
            ->whereBetween('payments.paid_at', [$this->from, $this->to])
            ->when($this->movieId, fn($q) => $q->where('showtimes.movie_id', $this->movieId));
    }

    private function refunds(): Builder
    {
        return DB::table('refunds')
            ->join('payments', 'payments.id', '=', 'refunds.payment_id')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->where('refunds.status', RefundStatus::Succeeded->value)
            ->whereNotNull('refunds.completed_at')
            ->whereBetween('refunds.completed_at', [$this->from, $this->to])
            ->when($this->movieId, fn($q) => $q->where('showtimes.movie_id', $this->movieId));
    }

    /** One row per seat sold, for bookings that have a verified payment. */
    private function tickets(): Builder
    {
        return DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->join('payments', 'payments.booking_id', '=', 'bookings.id')
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->whereIn('payments.status', [PaymentStatus::Paid->value, PaymentStatus::Refunded->value])
            ->whereNotNull('payments.paid_at')
            ->whereBetween('payments.paid_at', [$this->from, $this->to])
            ->when($this->movieId, fn($q) => $q->where('showtimes.movie_id', $this->movieId));
    }

    /**
     * One row per calendar day in the range (days with no sales show zeros).
     *
     * @return Collection<int, array{key:string,label:string,tickets:int,gross:int,refunds:int,net:int}>
     */
    public function perDay(): Collection
    {
        $gross = $this->sales()
            ->selectRaw('DATE(payments.paid_at) as k, SUM(payments.amount_cents) as total')
            ->groupBy('k')->pluck('total', 'k');

        $refunds = $this->refunds()
            ->selectRaw('DATE(refunds.completed_at) as k, SUM(refunds.amount_cents) as total')
            ->groupBy('k')->pluck('total', 'k');

        $tickets = $this->tickets()
            ->selectRaw('DATE(payments.paid_at) as k, COUNT(DISTINCT booking_seats.id) as total')
            ->groupBy('k')->pluck('total', 'k');

        $rows = collect();
        for ($day = $this->from; $day->lte($this->to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $rows->push($this->row($key, $day->format('M j, Y'), $tickets[$key] ?? 0, $gross[$key] ?? 0, $refunds[$key] ?? 0));
        }

        return $rows;
    }

    /** One row per movie that had a sale or a refund, biggest gross first. */
    public function perMovie(): Collection
    {
        $gross = $this->sales()
            ->selectRaw('showtimes.movie_id as k, SUM(payments.amount_cents) as total')
            ->groupBy('showtimes.movie_id')->pluck('total', 'k');

        $refunds = $this->refunds()
            ->selectRaw('showtimes.movie_id as k, SUM(refunds.amount_cents) as total')
            ->groupBy('showtimes.movie_id')->pluck('total', 'k');

        $tickets = $this->tickets()
            ->selectRaw('showtimes.movie_id as k, COUNT(DISTINCT booking_seats.id) as total')
            ->groupBy('showtimes.movie_id')->pluck('total', 'k');

        $ids = $gross->keys()->merge($refunds->keys())->merge($tickets->keys())->unique();
        $titles = Movie::whereIn('id', $ids)->pluck('title', 'id');

        return $ids
            ->map(fn($id) => $this->row(
                (string) $id,
                $titles[$id] ?? 'Unknown movie',
                $tickets[$id] ?? 0,
                $gross[$id] ?? 0,
                $refunds[$id] ?? 0,
            ))
            ->sortByDesc('gross')
            ->values();
    }

    /** @param  Collection  $rows  per-day rows */
    public static function totals(Collection $rows): array
    {
        return [
            'tickets' => (int) $rows->sum('tickets'),
            'gross' => (int) $rows->sum('gross'),
            'refunds' => (int) $rows->sum('refunds'),
            'net' => (int) $rows->sum('net'),
        ];
    }

    private function row(string $key, string $label, $tickets, $gross, $refunds): array
    {
        $gross = (int) $gross;
        $refunds = (int) $refunds;

        return [
            'key' => $key,
            'label' => $label,
            'tickets' => (int) $tickets,
            'gross' => $gross,
            'refunds' => $refunds,
            'net' => $gross - $refunds,
        ];
    }
}