<?php

declare(strict_types=1);

use App\Console\Commands\PurgeExpiredPermissions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Referme les dérogations de permissions temporaires arrivées à échéance.
Schedule::command(PurgeExpiredPermissions::class)->hourly();
