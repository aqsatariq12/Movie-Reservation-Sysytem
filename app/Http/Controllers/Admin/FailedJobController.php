<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FailedJobIndexRequest;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Support\FailedJobPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FailedJobController extends Controller
{
    private const RANGES = [
        '24h' => 'Last 24 hours',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'all' => 'All time',
    ];

    public function index(FailedJobIndexRequest $request)
    {
        $range = $request->validated('range') ?? '24h';
        $class = $request->validated('job_class');
        $search = $request->validated('search');

        $jobs = DB::table('failed_jobs')
            ->when($range !== 'all', fn($q) => $q->where('failed_at', '>=', match ($range) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                default => now()->subDay(),
            }))
            ->when($class, fn($q) => $q->where('payload->displayName', $class))
            ->when($search, function ($q) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $q->where(fn($w) => $w->where('payload', 'like', $like)->orWhere('exception', 'like', $like));
            })
            ->orderByDesc('failed_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn($row) => FailedJobPresenter::row($row));

        // Real booking status behind the "email failure never changes a booking" notice.
        $notice = null;
        $emailJob = $jobs->getCollection()->first(
            fn($job) => $job['class'] === 'SendBookingConfirmation' && $job['reference']
        );
        if ($emailJob && ($booking = Booking::where('booking_reference', $emailJob['reference'])->first())) {
            $notice = [
                'reference' => $emailJob['reference'],
                'status' => str_replace('_', ' ', $booking->status->value),
                'confirmed' => $booking->status === BookingStatus::Confirmed,
            ];
        }

        return view('admin.failed-jobs.index', [
            'jobs' => $jobs,
            'notice' => $notice,
            'ranges' => self::RANGES,
            'range' => $range,
            'jobClass' => $class,
            'search' => $search,
            'jobClasses' => DB::table('failed_jobs')
                ->select('payload->displayName as job_class')
                ->distinct()
                ->orderBy('job_class')
                ->pluck('job_class'),
        ]);
    }

    public function show(Request $request, string $uuid)
    {
        $row = DB::table('failed_jobs')->where('uuid', $uuid)->first();

        if (!$row) {
            return $this->gone();
        }

        $job = FailedJobPresenter::row($row);
        $action = in_array($request->query('action'), ['retry', 'delete'], true)
            ? $request->query('action')
            : null;

        return view('admin.failed-jobs.show', [
            'job' => $job,
            'action' => $action,
            'notice' => $this->notice($job),
        ]);
    }

    public function retry(Request $request, string $uuid)
    {
        $row = DB::table('failed_jobs')->where('uuid', $uuid)->first();

        if (!$row) {
            return $this->gone();
        }

        $job = FailedJobPresenter::row($row);

        try {
            DB::transaction(function () use ($request, $row, $job, $uuid) {
                // Puts the job back on its queue and removes the failed record.
                Artisan::call('queue:retry', ['id' => [$uuid]]);

                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'failed_job.retried',
                    'entity_type' => 'failed_job',
                    'entity_id' => $row->id,
                    'before_data' => [
                        'uuid' => $uuid,
                        'job_class' => $job['class'],
                        'reference' => $job['reference'],
                        'failed_at' => $job['failed_at']->toDateTimeString(),
                    ],
                    'after_data' => ['result' => 'retry queued'],
                    'ip_address' => $request->ip(),
                ]);
            });
        } catch (\Throwable $e) {
            report($e); // full error goes to storage/logs, not to the admin's screen

            return redirect()
                ->route('admin.failed-jobs.show', $uuid)
                ->withErrors(['retry' => 'This job could not be queued again. Its saved data may be unreadable. Check the application log, or delete the failed job.']);
        }

        return redirect()
            ->route('admin.failed-jobs.index')
            ->with('status', "{$job['class']} was queued for another attempt. If it fails again it will appear here as a new entry.");
    }

    public function destroy(Request $request, string $uuid)
    {
        $row = DB::table('failed_jobs')->where('uuid', $uuid)->first();

        if (!$row) {
            return $this->gone();
        }

        $job = FailedJobPresenter::row($row);

        DB::transaction(function () use ($request, $row, $job, $uuid) {
            app('queue.failer')->forget($uuid);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'failed_job.deleted',
                'entity_type' => 'failed_job',
                'entity_id' => $row->id,
                'before_data' => [
                    'uuid' => $uuid,
                    'job_class' => $job['class'],
                    'reference' => $job['reference'],
                    'failed_at' => $job['failed_at']->toDateTimeString(),
                ],
                'after_data' => ['result' => 'failed record removed'],
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()
            ->route('admin.failed-jobs.index')
            ->with('status', 'Failed job removed. The booking was not changed.');
    }

    /** Already retried or removed (for example by a double click). */
    private function gone()
    {
        return redirect()
            ->route('admin.failed-jobs.index')
            ->with('status', 'That failed job no longer exists. It may already have been retried or removed.');
    }

    /** Context banner for the detail page. */
    private function notice(array $job): array
    {
        if ($job['class'] === 'SendBookingConfirmation') {
            $booking = $job['reference'] && Str::startsWith($job['reference'], 'CB-')
                ? Booking::where('booking_reference', $job['reference'])->first()
                : null;

            if ($booking) {
                $paid = $booking->payments()
                    ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Refunded->value])
                    ->exists();
                $tickets = $booking->tickets()->where('status', TicketStatus::Valid->value)->count();

                return [
                    'tone' => 'info',
                    'title' => '✓ Booking unchanged · still ' . str_replace('_', ' ', $booking->status->value),
                    'body' => "{$job['reference']} " . ($paid ? 'has a verified payment' : 'has no verified payment')
                        . " and {$tickets} valid " . Str::plural('ticket', $tickets)
                        . '. Retrying or deleting this email job does not change the booking, seats or payment.',
                ];
            }

            return [
                'tone' => 'info',
                'title' => '✓ Booking unchanged',
                'body' => 'Retrying or deleting this email job does not change any booking, seat or payment.',
            ];
        }

        // Refund / webhook / other jobs: retrying repeats real processing.
        return [
            'tone' => 'warning',
            'title' => '! Retrying runs this job again',
            'body' => 'Retrying a refund or webhook job repeats real processing. Check the related booking, payment or refund first. Deleting only removes the failed queue record.',
        ];
    }
}