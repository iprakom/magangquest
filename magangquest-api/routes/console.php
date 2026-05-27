<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cron jobs for automated game mechanics
Schedule::command('quests:auto-approve')->everyFifteenMinutes();
Schedule::command('endgame:check')->daily();
Schedule::command('streaks:calculate')->dailyAt('00:00');
Schedule::command('perfectday:check')->dailyAt('23:59');
