<?php

namespace App\Http\Controllers\SocialProof;

use App\Http\Controllers\Controller;
use App\Models\SocialProofEvent;
use App\Services\SocialProof\SiteService;
use App\Support\Privileges;

class DashboardController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            abort_unless(
                $request->user()->canAccessModule('socialproof')
                    || $request->user()->hasPrivilege(Privileges::SOCIALPROOF_VIEW)
                    || $request->user()->hasPrivilege(Privileges::SOCIALPROOF_MANAGE)
                    || $request->user()->isOwnerOrAdmin(),
                403
            );

            return $next($request);
        });
    }

    public function index()
    {
        $site = $this->sites->defaultFor(currentTenant());
        $events = SocialProofEvent::latest('occurred_at')->latest('id')->limit(8)->get();

        $stats = [
            'fake' => SocialProofEvent::where('source', SocialProofEvent::SOURCE_FAKE)->where('is_active', true)->count(),
            'api' => SocialProofEvent::where('source', SocialProofEvent::SOURCE_API)->where('is_active', true)->count(),
            'active' => SocialProofEvent::where('is_active', true)->count(),
        ];

        return view('modules.socialproof.dashboard', compact('site', 'events', 'stats'));
    }
}
