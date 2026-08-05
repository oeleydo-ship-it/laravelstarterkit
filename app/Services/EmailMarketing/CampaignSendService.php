<?php

namespace App\Services\EmailMarketing;

use App\Jobs\ProcessCampaignBatch;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignSendService
{
    public function __construct(
        protected EmailMarketingSettingsService $settings,
    ) {
    }

    public function queue(EmailCampaign $campaign, bool $immediate = true): EmailCampaign
    {
        if (! $campaign->canSend()) {
            throw ValidationException::withMessages([
                'campaign' => 'This campaign cannot be sent. Ensure it has a list, subject, and content, and is still a draft or scheduled.',
            ]);
        }

        $settings = $this->settings->for($campaign->tenant);

        DB::transaction(function () use ($campaign, $settings, $immediate) {
            $campaign->refresh();

            if (! $campaign->canSend()) {
                return;
            }

            $subscribers = EmailSubscriber::query()
                ->where('status', EmailSubscriber::STATUS_SUBSCRIBED)
                ->whereHas('lists', function ($q) use ($campaign) {
                    $q->where('email_lists.id', $campaign->email_list_id)
                        ->where('email_list_subscriber.status', EmailSubscriber::STATUS_SUBSCRIBED);
                })
                ->get();

            if ($subscribers->isEmpty()) {
                throw ValidationException::withMessages([
                    'campaign' => 'The selected list has no active subscribers.',
                ]);
            }

            EmailCampaignRecipient::where('email_campaign_id', $campaign->id)->delete();

            $now = now();
            $rows = $subscribers->map(fn (EmailSubscriber $subscriber) => [
                'tenant_id' => $campaign->tenant_id,
                'email_campaign_id' => $campaign->id,
                'email_subscriber_id' => $subscriber->id,
                'email' => $subscriber->email,
                'tracking_token' => (string) str()->uuid(),
                'status' => EmailCampaignRecipient::STATUS_PENDING,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            foreach (array_chunk($rows, 500) as $chunk) {
                EmailCampaignRecipient::insert($chunk);
            }

            $campaign->update([
                'status' => EmailCampaign::STATUS_SENDING,
                'started_at' => $now,
                'scheduled_at' => $immediate ? null : $campaign->scheduled_at,
                'from_name' => $campaign->from_name ?: $settings['from_name'],
                'from_email' => $campaign->from_email ?: $settings['from_email'],
                'reply_to' => $campaign->reply_to ?: $settings['reply_to'],
                'recipients_count' => count($rows),
                'sent_count' => 0,
                'failed_count' => 0,
                'open_count' => 0,
                'click_count' => 0,
                'sent_at' => null,
            ]);
        });

        ProcessCampaignBatch::dispatch($campaign->id);

        return $campaign->fresh();
    }

    public function schedule(EmailCampaign $campaign, $scheduledAt): EmailCampaign
    {
        if (! $campaign->isEditable()) {
            throw ValidationException::withMessages([
                'campaign' => 'Only draft or scheduled campaigns can be rescheduled.',
            ]);
        }

        if (! $campaign->email_list_id || blank($campaign->subject) || blank($campaign->html_body)) {
            throw ValidationException::withMessages([
                'campaign' => 'Add a list, subject, and content before scheduling.',
            ]);
        }

        $campaign->update([
            'status' => EmailCampaign::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);

        return $campaign->fresh();
    }

    public function cancel(EmailCampaign $campaign): EmailCampaign
    {
        if (! in_array($campaign->status, [EmailCampaign::STATUS_SCHEDULED, EmailCampaign::STATUS_SENDING], true)) {
            throw ValidationException::withMessages([
                'campaign' => 'Only scheduled or sending campaigns can be cancelled.',
            ]);
        }

        DB::transaction(function () use ($campaign) {
            $campaign->update([
                'status' => EmailCampaign::STATUS_CANCELLED,
                'scheduled_at' => null,
            ]);

            EmailCampaignRecipient::withoutGlobalScopes()
                ->where('email_campaign_id', $campaign->id)
                ->where('status', EmailCampaignRecipient::STATUS_PENDING)
                ->update([
                    'status' => EmailCampaignRecipient::STATUS_CANCELLED,
                    'updated_at' => now(),
                ]);
        });

        return $campaign->fresh();
    }

    public function dispatchDueScheduled(): int
    {
        $due = EmailCampaign::withoutGlobalScopes()
            ->where('status', EmailCampaign::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($due as $campaign) {
            try {
                $this->queue($campaign, immediate: true);
                $count++;
            } catch (\Throwable) {
                $campaign->update(['status' => EmailCampaign::STATUS_FAILED]);
            }
        }

        return $count;
    }
}
