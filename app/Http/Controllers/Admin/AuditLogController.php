<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditLogIndexRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditLogPresenter;
use Illuminate\Support\Carbon;

class AuditLogController extends Controller
{
    public function index(AuditLogIndexRequest $request)
    {
        $date = $request->validated('date');

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('actor'), fn ($q) => $q->where('user_id', $request->integer('actor')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->validated('action')))
            ->when($date, fn ($q) => $q->whereBetween('created_at', [
                Carbon::parse($date)->startOfDay(),
                Carbon::parse($date)->endOfDay(),
            ]))
            ->targetMatches($request->validated('target'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Entry shown in the drawer (?view=ID)
        $selected = $request->filled('view')
            ? AuditLog::with('user')->find($request->integer('view'))
            : null;

        // concat() builds a new collection, so the table rows are untouched.
        $targets = AuditLogPresenter::targets(
            $logs->getCollection()->concat($selected ? [$selected] : [])
        );

        return view('admin.audit.index', [
            'logs'     => $logs,
            'selected' => $selected,
            'targets'  => $targets,
            'changes'  => $selected ? AuditLogPresenter::changes($selected->before_data, $selected->after_data) : [],
            'title'    => $selected ? AuditLogPresenter::title($selected->action) : null,
            'date'     => $date,
            'actors'   => User::whereIn('id', AuditLog::query()->whereNotNull('user_id')->select('user_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name']),
            'actions'  => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}