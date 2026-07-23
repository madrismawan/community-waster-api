<?php

use App\Jobs\CancelUncollectedOrganicWaste;
use App\Models\WasteOrganic;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CancelUncollectedOrganicWaste)
    ->dailyAt(WasteOrganic::AUTO_CANCEL_TIME)
    ->timezone(WasteOrganic::AUTO_CANCEL_TIMEZONE)
    ->withoutOverlapping();
