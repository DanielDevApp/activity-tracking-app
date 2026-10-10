<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $f = $this->filters($request);

        $base = $this->updatesQuery($f);

        return view('reports', [
            'f' => $f,
            'updates' => (clone $base)->latest('created_at')->paginate(25)->withQueryString(),
            'stats' => [
                'total' => (clone $base)->count(),
                'done' => (clone $base)->where('status', 'done')->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'people' => (clone $base)->distinct()->count('user_id'),
            ],
            'days' => $this->dailyCompletion($f),
            'activities' => Activity::withTrashed()->orderBy('title')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $f = $this->filters($request);
        $query = $this->updatesQuery($f)->latest('created_at');
        $name = 'activity-report-' . $f['from'] . '_to_' . $f['to'] . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents correctly
            fputcsv($out, ['Activity day', 'Activity', 'Status', 'Remark', 'Updated by', 'Employee ID', 'Job title', 'Phone', 'Updated at']);

            $query->lazy(500)->each(function ($u) use ($out) {
                fputcsv($out, array_map([$this, 'safeCell'], [
                    $u->log->log_date->toDateString(),
                    $u->log->activity->title,
                    $u->status,
                    $u->remark,
                    $u->who('name'),
                    $u->who('employee_id'),
                    $u->who('job_title'),
                    $u->who('phone'),
                    $u->created_at->format('Y-m-d H:i:s'),
                ]));
            });

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Defuse spreadsheet formula injection (=, +, -, @ at the start of a cell). */
    private function safeCell($value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    private function filters(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'activity_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:pending,done'],
        ]);

        return [
            'from' => $request->input('from') ?: today()->subDays(6)->toDateString(),
            'to' => $request->input('to') ?: today()->toDateString(),
            'activity_id' => $request->input('activity_id'),
            'user_id' => $request->input('user_id'),
            'status' => $request->input('status'),
        ];
    }

    private function updatesQuery(array $f): Builder
    {
        return ActivityUpdate::query()
            ->with(['log.activity', 'user'])
            ->whereBetween('created_at', [Carbon::parse($f['from'])->startOfDay(), Carbon::parse($f['to'])->endOfDay()])
            ->when($f['user_id'], fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($f['activity_id'], fn ($q, $v) => $q->whereHas('log', fn ($l) => $l->where('activity_id', $v)));
    }

    /** Per activity-day completion for the chart (current state of each day's checklist). */
    private function dailyCompletion(array $f)
    {
        return ActivityLog::query()
            ->whereBetween('log_date', [$f['from'], $f['to']])
            ->when($f['activity_id'], fn ($q, $v) => $q->where('activity_id', $v))
            ->selectRaw("log_date, count(*) as total, sum(case when status = 'done' then 1 else 0 end) as done_count")
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get();
    }
}
