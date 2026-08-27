<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// PRD 11 — pengingat terjadwal, dijalankan otomatis lewat container "scheduler".
Schedule::command('contract-reminders:send')->dailyAt('07:00');
Schedule::command('attendance-reminders:send')->dailyAt('07:30');
