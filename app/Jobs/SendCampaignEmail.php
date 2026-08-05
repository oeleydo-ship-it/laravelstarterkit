<?php

namespace App\Jobs;

use App\Mail\CampaignMailable;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Services\EmailMarketing\ContentRenderer;
use App\Services\EmailMarketing\EmailMarketingSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendCampaignEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $recipientId,
    ) {
    }

    public function handle(ContentRenderer $renderer, EmailMarketingSettingsService $settingsService): void
    {
        $recipient = EmailCampaignRecipient::withoutGlobalScopes()
            ->with(['campaign.tenant', 'subscriber'])
            ->find($this->recipientId);

        if (! $recipient || $recipient->status !== EmailCampaignRecipient::STATUS_PENDING) {
            return;
        }

        $campaign = $recipient->campaign;

        if (! $campaign || in_array($campaign->status, [EmailCampaign::STATUS_CANCELLED], true)) {
            return;
        }

        $settings = $settingsService->for($campaign->tenant);

        try {
            $html = $renderer->render(
                $campaign->html_body,
                $recipient,
                ['preview_text' => $campaign->preview_text ?? ''],
                (bool) $settings['track_opens'],
                (bool) $settings['track_clicks'],
            );

            $text = $campaign->text_body
                ? $renderer->renderText($campaign->text_body, $recipient, ['preview_text' => $campaign->preview_text ?? ''])
                : html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);

            if (($settings['append_compliance_footer'] ?? true)
                && ! str_contains(strtolower($campaign->html_body), '{{unsubscribe_url}}')
                && ! str_contains(strtolower($html), '/email/unsubscribe/')) {
                $footer = $this->footerHtml($settings, $recipient);
                if (stripos($html, '</body>') !== false) {
                    $html = str_ireplace('</body>', $footer.'</body>', $html);
                } else {
                    $html .= $footer;
                }
            } elseif (($settings['append_compliance_footer'] ?? true)
                && (filled($settings['company_address'] ?? null)
                    || filled($settings['footer_text'] ?? null)
                    || filled($settings['company_name'] ?? null)
                    || filled($settings['company_website'] ?? null))) {
                $footer = $this->footerHtml($settings, $recipient, includeUnsubscribe: false);
                if ($footer !== '') {
                    if (stripos($html, '</body>') !== false) {
                        $html = str_ireplace('</body>', $footer.'</body>', $html);
                    } else {
                        $html .= $footer;
                    }
                }
            }

            $subject = $renderer->renderSubject($campaign->subject, $recipient);

            Mail::to($recipient->email)->send(new CampaignMailable(
                subjectLine: $subject,
                htmlBody: $html,
                textBody: $text,
                fromName: $campaign->from_name ?: $settings['from_name'],
                fromEmail: $campaign->from_email ?: $settings['from_email'],
                replyTo: $campaign->reply_to ?: $settings['reply_to'],
            ));

            $recipient->update([
                'status' => EmailCampaignRecipient::STATUS_SENT,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            $campaign->increment('sent_count');
        } catch (Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $recipient->update([
                    'status' => EmailCampaignRecipient::STATUS_FAILED,
                    'error_message' => Str::limit($e->getMessage(), 500),
                ]);

                $campaign->increment('failed_count');
            }

            throw $e;
        } finally {
            $this->maybeFinalize($campaign->fresh());
        }
    }

    protected function footerHtml(array $settings, EmailCampaignRecipient $recipient, bool $includeUnsubscribe = true): string
    {
        $parts = array_filter([
            e($settings['footer_text'] ?? ''),
            e($settings['company_name'] ?? ''),
            e($settings['company_address'] ?? ''),
            e($settings['company_website'] ?? ''),
        ]);

        if ($includeUnsubscribe) {
            $unsubscribe = $recipient->subscriber
                ? route('email.unsubscribe.show', $recipient->subscriber->unsubscribe_token)
                : '#';
            $parts[] = '<a href="'.e($unsubscribe).'">Unsubscribe</a>';
        }

        if ($parts === []) {
            return '';
        }

        return '<div style="margin-top:32px;padding-top:16px;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;text-align:center;">'
            .implode('<br>', $parts)
            .'</div>';
    }

    protected function maybeFinalize(?EmailCampaign $campaign): void
    {
        if (! $campaign || $campaign->status !== EmailCampaign::STATUS_SENDING) {
            return;
        }

        $pending = EmailCampaignRecipient::withoutGlobalScopes()
            ->where('email_campaign_id', $campaign->id)
            ->where('status', EmailCampaignRecipient::STATUS_PENDING)
            ->exists();

        if ($pending) {
            return;
        }

        $campaign->update([
            'status' => EmailCampaign::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }
}
