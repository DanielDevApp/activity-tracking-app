<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Create each day's checklist even if nobody has opened the app yet.
Schedule::command('activities:generate')->dailyAt('00:01')->withoutOverlapping();
