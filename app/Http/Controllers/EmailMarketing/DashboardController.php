<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailList;
use App\Models\EmailSubscriber;
use App\Models\EmailTemplate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', EmailCampaign::class);

        return view('modules.email.dashboard', [
            'stats' => [
                'lists' => EmailList::count(),
                'subscribers' => EmailSubscriber::where('status', EmailSubscriber::STATUS_SUBSCRIBED)->count(),
                'templates' => EmailTemplate::count(),
                'campaigns' => EmailCampaign::count(),
                'sent' => EmailCampaign::where('status', EmailCampaign::STATUS_SENT)->count(),
                'opens' => (int) EmailCampaign::sum('open_count'),
                'clicks' => (int) EmailCampaign::sum('click_count'),
            ],
            'recentCampaigns' => EmailCampaign::with('list')->latest()->limit(8)->get(),
        ]);
    }
}
