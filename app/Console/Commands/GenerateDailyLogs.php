<?php

namespace App\Console\Commands;

use App\Services\ActivityLogService;
use Illuminate\Console\Command;

class GenerateDailyLogs extends Command
{
    protected $signature = 'activities:generate';

    protected $description = "Create today's checklist (one pending log per active activity)";

    public function handle(ActivityLogService $service): int
    {
        $service->ensureLogsUpTo(today());
        $this->info("Daily logs are up to date for " . today()->toDateString() . '.');

        return self::SUCCESS;
    }
}
