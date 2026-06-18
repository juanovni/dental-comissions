<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('social:sync-comments')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('social:classify-comments')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('social:lead-alerts')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('social:roi-leakage-report')
    ->mondays()
    ->at('07:00')
    ->withoutOverlapping();
