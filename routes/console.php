<?php

use App\Console\Commands\AdCampaignExpire;
use App\Console\Commands\ExpireStories;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ExpireStories::class)->hourly();
Schedule::command(AdCampaignExpire::class)->hourly();
