<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Services\EmailMarketing\EmailMarketingSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCampaignBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $campaignId,
        public ?int $batchSize = null,
    ) {
    }

    public function handle(EmailMarketingSettingsService $settingsService): void
    {
        $campaign = EmailCampaign::withoutGlobalScopes()->with('tenant')->find($this->campaignId);

        if (! $campaign || $campaign->status === EmailCampaign::STATUS_CANCELLED) {
            return;
        }

        if ($campaign->status !== EmailCampaign::STATUS_SENDING) {
            $campaign->update(['status' => EmailCampaign::STATUS_SENDING]);
        }

        $settings = $settingsService->for($campaign->tenant);
        $batchSize = $this->batchSize ?: max(1, min(500, (int) ($settings['batch_size'] ?? 100)));

        $pendingIds = EmailCampaignRecipient::withoutGlobalScopes()
            ->where('email_campaign_id', $this->campaignId)
            ->where('status', EmailCampaignRecipient::STATUS_PENDING)
            ->orderBy('id')
            ->limit($batchSize)
            ->pluck('id');

        if ($pendingIds->isEmpty()) {
            $campaign->update([
                'status' => EmailCampaign::STATUS_SENT,
                'sent_at' => $campaign->sent_at ?? now(),
            ]);

            return;
        }

        foreach ($pendingIds as $id) {
            SendCampaignEmail::dispatch($id);
        }

        $stillPending = EmailCampaignRecipient::withoutGlobalScopes()
            ->where('email_campaign_id', $this->campaignId)
            ->where('status', EmailCampaignRecipient::STATUS_PENDING)
            ->whereNotIn('id', $pendingIds)
            ->exists();

        if ($stillPending) {
            $delay = max(1, (int) ($settings['batch_delay_seconds'] ?? 5));
            self::dispatch($this->campaignId, $batchSize)->delay(now()->addSeconds($delay));
        }
    }
}
