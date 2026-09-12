<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync attendance from the ZKTeco terminal every 5 minutes
Schedule::command('attendance:pull')
    ->everyFiveMinutes()
    ->withoutOverlapping();
