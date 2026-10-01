<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Records announcements that opened / closed according to their dates (see
// RECRUITMENT_LIFECYCLE_RULES.md). Effective state never depends on this run.
Schedule::command('recruitment:sync-statuses')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('backups:dispatch-due')->everyMinute()->withoutOverlapping()->onOneServer();
