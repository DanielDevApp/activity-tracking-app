<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesDate;
use App\Models\ActivityLog;
use App\Models\ActivityUpdate;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    use ResolvesDate;

    public function index(Request $request, ActivityLogService $service)
    {
        $date = $this->resolveDate($request);
        $view = $request->query('view') === 'time' ? 'time' : 'activity';

        // Every update MADE on this day, including late fixes to older days' items.
        $updates = ActivityUpdate::with('log.activity')
            ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->get();

        $byLog = $updates->groupBy('activity_log_id');

        // The board: this day's checklist plus any older item that was touched today.
        $logs = ActivityLog::with(['activity', 'lastUpdater'])
            ->where(fn ($q) => $q->where('log_date', $date->toDateString())->orWhereIn('id', $byLog->keys()))
            ->get()
            ->sortBy(fn ($l) => [$l->log_date->timestamp, $l->activity->sort_order])
            ->values();

        return view('timeline', [
            'date' => $date,
            'view' => $view,
            'updates' => $updates,
            'byLog' => $byLog,
            'logs' => $logs,
            'outstanding' => $service->outstanding($date),
            'people' => $updates->pluck('user_id')->unique()->count(),
            'doneCount' => $updates->where('status', 'done')->count(),
        ]);
    }
}
