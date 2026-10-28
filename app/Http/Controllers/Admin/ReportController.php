<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportIndexRequest;
use App\Models\Movie;
use App\Support\SalesReport;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Pagination\LengthAwarePaginator;
class ReportController extends Controller
{
    public function index(ReportIndexRequest $request)
    {
        $report = $this->makeReport($request);
        $tab = $request->validated('tab') ?? 'day';

        $daily = $report->perDay();
        $allRows = $tab === 'movie' ? $report->perMovie() : $daily;

        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $rows = (new LengthAwarePaginator(
            $allRows->forPage($page, $perPage)->values(),
            $allRows->count(),
            $perPage,
            $page,
            ['path' => $request->url()],
        ))->withQueryString();

        return view('admin.reports.index', [
            'tab' => $tab,
            'from' => $report->from,
            'to' => $report->to,
            'totals' => SalesReport::totals($daily), // always the full range
            'rows' => $rows,
            'movies' => Movie::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function export(ReportIndexRequest $request): StreamedResponse
    {
        $report = $this->makeReport($request);
        $tab = $request->validated('tab') ?? 'day';
        $rows = $tab === 'movie' ? $report->perMovie() : $report->perDay();

        $filename = sprintf(
            'sales-%s-%s-to-%s.csv',
            $tab === 'movie' ? 'per-movie' : 'per-day',
            $report->from->toDateString(),
            $report->to->toDateString()
        );

        return response()->streamDownload(function () use ($rows, $tab) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [$tab === 'movie' ? 'Movie' : 'Date', 'Tickets', 'Gross USD', 'Refunds USD', 'Net USD']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['label'],
                    $r['tickets'],
                    number_format($r['gross'] / 100, 2, '.', ''),
                    number_format($r['refunds'] / 100, 2, '.', ''),
                    number_format($r['net'] / 100, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Default range = last 7 days (today included). Capped at 366 days. */
    private function makeReport(ReportIndexRequest $request): SalesReport
    {
        $to = $request->filled('to') ? Carbon::parse($request->validated('to')) : now();
        $from = $request->filled('from') ? Carbon::parse($request->validated('from')) : $to->copy()->subDays(6);

        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366);
        }

        return new SalesReport($from, $to, $request->filled('movie_id') ? $request->integer('movie_id') : null);
    }
}