<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignClick;
use App\Models\EmailCampaignRecipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TrackingController extends Controller
{
    public function open(string $token): Response
    {
        $recipient = EmailCampaignRecipient::withoutGlobalScopes()
            ->where('tracking_token', $token)
            ->first();

        if ($recipient) {
            $isFirst = $recipient->open_count === 0;

            $recipient->increment('open_count');
            if (! $recipient->opened_at) {
                $recipient->update(['opened_at' => now()]);
            }

            if ($isFirst && $recipient->campaign) {
                EmailCampaign::withoutGlobalScopes()
                    ->where('id', $recipient->email_campaign_id)
                    ->increment('open_count');
            }
        }

        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function click(Request $request, string $token): RedirectResponse|SymfonyResponse
    {
        $url = $request->query('url');

        if (! is_string($url) || ! $this->isSafeRedirect($url)) {
            abort(400, 'Invalid destination.');
        }

        $recipient = EmailCampaignRecipient::withoutGlobalScopes()
            ->where('tracking_token', $token)
            ->first();

        if ($recipient) {
            $isFirst = $recipient->click_count === 0;

            $recipient->increment('click_count');
            if (! $recipient->clicked_at) {
                $recipient->update(['clicked_at' => now()]);
            }

            EmailCampaignClick::create([
                'email_campaign_recipient_id' => $recipient->id,
                'url' => $url,
                'clicked_at' => now(),
            ]);

            if ($isFirst) {
                EmailCampaign::withoutGlobalScopes()
                    ->where('id', $recipient->email_campaign_id)
                    ->increment('click_count');
            }

            if ($recipient->open_count === 0) {
                $recipient->update(['opened_at' => now(), 'open_count' => 1]);
                EmailCampaign::withoutGlobalScopes()
                    ->where('id', $recipient->email_campaign_id)
                    ->increment('open_count');
            }
        }

        return redirect()->away($url);
    }

    protected function isSafeRedirect(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}
