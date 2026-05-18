<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Codziennie o 9:00 wysyłaj przypomnienia o zaległych płatnościach.
Schedule::command('saas:send-payment-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();

// Codziennie wieczorem aktualizuj ceny paliw (scraper z e-petrol — best effort).
Schedule::command('saas:fetch-fuel-prices')
    ->dailyAt('22:00')
    ->withoutOverlapping()
    ->onOneServer();
