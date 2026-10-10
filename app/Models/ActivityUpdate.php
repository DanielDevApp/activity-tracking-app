<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Append-only audit record: who changed what, when, with their bio at that moment.
 */
class ActivityUpdate extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'activity_log_id', 'user_id', 'status', 'remark', 'user_snapshot', 'created_at',
    ];

    protected $casts = [
        'user_snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Audit records are immutable and cannot be edited.');
        });
    }

    public function log()
    {
        return $this->belongsTo(ActivityLog::class, 'activity_log_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function who(string $key, string $default = ''): string
    {
        return (string) ($this->user_snapshot[$key] ?? $default);
    }
}
