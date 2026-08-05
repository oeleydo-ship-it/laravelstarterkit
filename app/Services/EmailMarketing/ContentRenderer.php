<?php

namespace App\Services\EmailMarketing;

use App\Models\EmailCampaignRecipient;
use App\Models\EmailSubscriber;

class ContentRenderer
{
    /**
     * Replace merge tags and optionally wrap links / inject open pixel.
     *
     * Supported tags: {{first_name}}, {{last_name}}, {{email}}, {{full_name}},
     * {{unsubscribe_url}}, {{preview_text}}
     */
    public function render(
        string $html,
        EmailCampaignRecipient $recipient,
        array $extras = [],
        bool $trackOpens = true,
        bool $trackClicks = true,
    ): string {
        $html = strtr($html, $this->replacements($recipient, $recipient->subscriber, $extras, escapeHtml: true));

        if ($trackClicks) {
            $html = $this->rewriteLinks($html, $recipient);
        }

        if ($trackOpens) {
            $pixel = '<img src="'.e(route('email.track.open', $recipient->tracking_token)).'" width="1" height="1" alt="" style="display:none;" />';
            if (stripos($html, '</body>') !== false) {
                $html = str_ireplace('</body>', $pixel.'</body>', $html);
            } else {
                $html .= $pixel;
            }
        }

        return $html;
    }

    public function renderText(string $text, EmailCampaignRecipient $recipient, array $extras = []): string
    {
        return strtr($text, $this->replacements($recipient, $recipient->subscriber, $extras, escapeHtml: false));
    }

    public function renderSubject(string $subject, EmailCampaignRecipient $recipient, array $extras = []): string
    {
        return $this->renderText($subject, $recipient, $extras);
    }

    /**
     * Preview merge tags for a sample subscriber (no tracking).
     */
    public function previewHtml(string $html, ?EmailSubscriber $subscriber = null, array $extras = []): string
    {
        $fake = new EmailCampaignRecipient([
            'email' => $subscriber?->email ?? 'subscriber@example.com',
            'tracking_token' => 'preview',
        ]);
        $fake->setRelation('subscriber', $subscriber);

        return strtr($html, $this->replacements($fake, $subscriber, $extras, escapeHtml: true));
    }

    protected function replacements(
        EmailCampaignRecipient $recipient,
        ?EmailSubscriber $subscriber,
        array $extras,
        bool $escapeHtml,
    ): array {
        $first = $subscriber?->first_name ?? '';
        $last = $subscriber?->last_name ?? '';
        $email = $recipient->email;
        $full = trim("{$first} {$last}") ?: $email;

        $unsubscribeUrl = $subscriber?->unsubscribe_token
            ? route('email.unsubscribe.show', $subscriber->unsubscribe_token)
            : '#';

        $map = [
            '{{first_name}}' => $first,
            '{{last_name}}' => $last,
            '{{email}}' => $email,
            '{{full_name}}' => $full,
            '{{unsubscribe_url}}' => $unsubscribeUrl,
            '{{preview_text}}' => (string) ($extras['preview_text'] ?? ''),
            '{{First Name}}' => $first,
            '{{Last Name}}' => $last,
            '{{Email}}' => $email,
            '{{Unsubscribe URL}}' => $unsubscribeUrl,
        ];

        if ($escapeHtml) {
            foreach ($map as $key => $value) {
                $map[$key] = e($value);
            }
        }

        // Extras intentionally applied after escaping so callers can inject trusted HTML if needed.
        return array_merge($map, $extras);
    }

    protected function rewriteLinks(string $html, EmailCampaignRecipient $recipient): string
    {
        return preg_replace_callback(
            '/<a\s([^>]*?)href=(["\'])([^"\']+)\2([^>]*)>/i',
            function (array $matches) use ($recipient) {
                $url = html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5);

                if ($this->shouldSkipTracking($url)) {
                    return $matches[0];
                }

                $tracked = route('email.track.click', [
                    'token' => $recipient->tracking_token,
                    'url' => $url,
                ]);

                return '<a '.$matches[1].'href='.$matches[2].e($tracked).$matches[2].$matches[4].'>';
            },
            $html
        ) ?? $html;
    }

    protected function shouldSkipTracking(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#') || str_starts_with(strtolower($url), 'mailto:')) {
            return true;
        }

        if (str_contains($url, '/email/unsubscribe/') || str_contains($url, '/email/track/')) {
            return true;
        }

        return false;
    }
}
