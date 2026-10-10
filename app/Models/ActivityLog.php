<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One row per activity per day: the CURRENT state of that day's checklist item.
 * The history of how it got there lives in ActivityUpdate.
 */
class ActivityLog extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DONE = 'done';

    protected $fillable = [
        'activity_id', 'log_date', 'status', 'remark', 'last_updated_by', 'last_updated_at',
    ];

    protected $casts = ['last_updated_at' => 'datetime'];

    /**
     * log_date is always stored as a plain Y-m-d string (never a timestamp),
     * so the unique (activity_id, log_date) index and comparisons behave the
     * same on SQLite and MySQL.
     */
    protected function logDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->startOfDay() : null,
            set: fn ($value) => Carbon::parse($value)->toDateString(),
        );
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function lastUpdater()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function updates()
    {
        return $this->hasMany(ActivityUpdate::class)->latest('created_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }
}
