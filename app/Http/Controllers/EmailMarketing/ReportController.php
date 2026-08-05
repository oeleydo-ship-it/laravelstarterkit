<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignClick;
use App\Models\EmailSubscriber;
use App\Support\Privileges;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        abort_unless(
            auth()->user()->hasPrivilege(Privileges::EMAIL_MANAGE)
                || auth()->user()->hasPrivilege(Privileges::EMAIL_VIEW)
                || auth()->user()->isOwnerOrAdmin()
                || auth()->user()->canAccessModule('email'),
            403
        );

        $campaigns = EmailCampaign::query()
            ->whereIn('status', [EmailCampaign::STATUS_SENT, EmailCampaign::STATUS_SENDING])
            ->latest('sent_at')
            ->limit(20)
            ->get();

        $topLinks = EmailCampaignClick::query()
            ->selectRaw('url, COUNT(*) as clicks')
            ->whereIn('email_campaign_recipient_id', function ($q) {
                $q->select('id')
                    ->from('email_campaign_recipients')
                    ->where('tenant_id', currentTenant()->id);
            })
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get();

        return view('modules.email.reports', [
            'totals' => [
                'subscribers' => EmailSubscriber::where('status', EmailSubscriber::STATUS_SUBSCRIBED)->count(),
                'unsubscribed' => EmailSubscriber::where('status', EmailSubscriber::STATUS_UNSUBSCRIBED)->count(),
                'emails_sent' => (int) EmailCampaign::sum('sent_count'),
                'opens' => (int) EmailCampaign::sum('open_count'),
                'clicks' => (int) EmailCampaign::sum('click_count'),
                'failed' => (int) EmailCampaign::sum('failed_count'),
            ],
            'campaigns' => $campaigns,
            'topLinks' => $topLinks,
        ]);
    }
}
