<?php

namespace App\Http\Controllers\Engage;

use App\Http\Controllers\Controller;
use App\Models\EngageCampaign;
use App\Models\EngageLead;
use App\Services\Engage\SiteService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            $this->authorize('viewAny', EngageCampaign::class);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $site = $this->sites->defaultFor(currentTenant());

        $stats = [
            'live' => EngageCampaign::query()->where('status', EngageCampaign::STATUS_LIVE)->count(),
            'campaigns' => EngageCampaign::query()->count(),
            'leads' => EngageLead::query()->count(),
        ];

        $recentCampaigns = EngageCampaign::query()->orderByDesc('updated_at')->limit(8)->get();
        $recentLeads = EngageLead::query()->with('campaign')->orderByDesc('id')->limit(8)->get();

        return view('modules.engage.dashboard', compact('site', 'stats', 'recentCampaigns', 'recentLeads'));
    }
}
