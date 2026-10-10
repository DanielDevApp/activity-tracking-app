<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityLogService
{
    /** If the scheduler was down, never back-fill more than this many days. */
    private const BACKFILL_LIMIT_DAYS = 31;

    /**
     * Make sure every active activity has a log row for $date. Idempotent and
     * race-safe: INSERT OR IGNORE against the unique (activity_id, log_date) index.
     * Future dates are never generated, and activities are not retro-added to
     * days before they existed.
     */
    public function ensureLogsForDate(Carbon $date): void
    {
        $date = $date->copy()->startOfDay();

        if ($date->greaterThan(today())) {
            return;
        }

        $day = $date->toDateString();
        $existing = ActivityLog::where('log_date', $day)->pluck('activity_id');

        $rows = Activity::active()
            ->whereDate('created_at', '<=', $day)
            ->whereNotIn('id', $existing)
            ->pluck('id')
            ->map(fn ($id) => [
                'activity_id' => $id,
                'log_date' => $day,
                'status' => ActivityLog::STATUS_PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

        if ($rows) {
            ActivityLog::insertOrIgnore($rows);
        }
    }

    /**
     * Generate logs for every day from the last generated day up to $date, so a
     * missed scheduler run never hides unfinished work from the handover view.
     */
    public function ensureLogsUpTo(Carbon $date): void
    {
        $end = $date->copy()->startOfDay();
        $last = ActivityLog::max('log_date');

        if ($last) {
            $start = Carbon::parse($last)->addDay()->startOfDay();
            $floor = $end->copy()->subDays(self::BACKFILL_LIMIT_DAYS);
            if ($start->lessThan($floor)) {
                $start = $floor;
            }
            for ($d = $start->copy(); $d->lessThan($end); $d->addDay()) {
                $this->ensureLogsForDate($d);
            }
        }

        $this->ensureLogsForDate($end);
    }

    /**
     * Change status/remark and write the immutable audit record in one
     * transaction. Returns false when nothing actually changed (no noise).
     */
    public function update(ActivityLog $log, User $user, string $status, ?string $remark): bool
    {
        $remark = filled($remark) ? trim($remark) : null;

        return DB::transaction(function () use ($log, $user, $status, $remark) {
            $locked = ActivityLog::lockForUpdate()->findOrFail($log->id);

            if ($locked->log_date->greaterThan(today())) {
                throw ValidationException::withMessages([
                    'status' => 'Activities for future dates cannot be updated yet.',
                ]);
            }

            if ($locked->status === $status && $locked->remark === $remark) {
                return false;
            }

            $locked->update([
                'status' => $status,
                'remark' => $remark,
                'last_updated_by' => $user->id,
                'last_updated_at' => now(),
            ]);

            ActivityUpdate::create([
                'activity_log_id' => $locked->id,
                'user_id' => $user->id,
                'status' => $status,
                'remark' => $remark,
                'user_snapshot' => $user->bioSnapshot(),
            ]);

            return true;
        });
    }

    /** Still pending from days BEFORE $date: the core of the handover. */
    public function carriedOver(Carbon $date): Collection
    {
        return ActivityLog::with(['activity', 'lastUpdater'])
            ->pending()
            ->where('log_date', '<', $date->toDateString())
            ->orderBy('log_date')
            ->get();
    }

    /** Everything pending on or before $date (what the next shift inherits). */
    public function outstanding(Carbon $date): Collection
    {
        return ActivityLog::with(['activity', 'lastUpdater'])
            ->pending()
            ->where('log_date', '<=', $date->toDateString())
            ->orderBy('log_date')
            ->get();
    }
}
