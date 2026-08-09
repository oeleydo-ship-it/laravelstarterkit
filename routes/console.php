<?php

use App\Console\Commands\DispatchScheduledEmailCampaigns;
use App\Console\Commands\DispatchScheduledAutoblogPosts;
use App\Console\Commands\SendBookingReminders;
use App\Console\Commands\ExpireBookingPaymentHolds;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(DispatchScheduledEmailCampaigns::class)->everyMinute();
Schedule::command(DispatchScheduledAutoblogPosts::class)->everyMinute()->withoutOverlapping();
Schedule::command(SendBookingReminders::class)->everyMinute()->withoutOverlapping();
Schedule::command(ExpireBookingPaymentHolds::class)->everyMinute()->withoutOverlapping();
