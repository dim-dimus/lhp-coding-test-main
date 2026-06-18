<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder emails go out hourly; the command itself is idempotent, so the
// cadence only affects timeliness, never whether a reminder is sent twice.
Schedule::command('events:send-reminders')->hourly();
