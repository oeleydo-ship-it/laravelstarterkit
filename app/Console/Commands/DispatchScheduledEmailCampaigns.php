<?php

namespace App\Console\Commands;

use App\Services\EmailMarketing\CampaignSendService;
use Illuminate\Console\Command;

class DispatchScheduledEmailCampaigns extends Command
{
    protected $signature = 'email:dispatch-scheduled';

    protected $description = 'Queue email marketing campaigns that are due to send';

    public function handle(CampaignSendService $service): int
    {
        $count = $service->dispatchDueScheduled();

        $this->info("Dispatched {$count} scheduled campaign(s).");

        return self::SUCCESS;
    }
}
