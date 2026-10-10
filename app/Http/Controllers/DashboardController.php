<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesDate;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesDate;

    public function index(Request $request, ActivityLogService $service)
    {
        $date = $this->resolveDate($request);

        // Only "today" generates rows. Browsing old dates must never invent history.
        if ($date->isToday()) {
            $service->ensureLogsUpTo($date);
        }

        $logs = ActivityLog::with(['activity', 'lastUpdater'])
            ->where('log_date', $date->toDateString())
            ->get()
            ->sortBy(fn ($log) => [$log->activity->sort_order, $log->activity->id])
            ->values();

        return view('dashboard', [
            'date' => $date,
            'logs' => $logs,
            'carriedOver' => $service->carriedOver($date),
            'done' => $logs->where('status', 'done')->count(),
            'total' => $logs->count(),
        ]);
    }
}
