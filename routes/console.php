<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run voucher expiration check daily at midnight
Schedule::command('vouchers:expire')->daily();

// Sync Cambodia Post shipping rates every Sunday at 3am
Schedule::command('shipping:sync-cambodia-post')->weeklyOn(0, '03:00');
