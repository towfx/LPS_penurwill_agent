<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')
            ->orderByDesc('created_at');

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('actor_id')) {
            $query->where('user_id', $request->actor_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('target_type')) {
            $query->where('target_type', 'like', '%'.$request->target_type.'%');
        }

        $logs = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/ActivityLog', [
            'logs' => $logs,
            'filters' => $request->only(['start_date', 'end_date', 'actor_id', 'action', 'target_type']),
        ]);
    }

    public function export(Request $request)
    {
        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('actor_id')) {
            $query->where('user_id', $request->actor_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->get();

        $csv = "ID,User,Action,Description,Target Type,Target ID,IP,Created At\n";
        foreach ($logs as $log) {
            $csv .= implode(',', [
                $log->id,
                '"'.($log->user?->email ?? 'system').'"',
                '"'.$log->action.'"',
                '"'.str_replace('"', '""', $log->description ?? '').'"',
                '"'.class_basename($log->target_type ?? '').'"',
                $log->target_id ?? '',
                $log->ip_address ?? '',
                $log->created_at?->toISOString() ?? '',
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="activity-log-'.now()->format('Y-m-d').'.csv"',
        ]);
    }
}
