<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewWidget;
use App\Services\Reviews\SiteService;

class DashboardController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            $this->authorize('viewAny', Review::class);
            return $next($request);
        });
    }

    public function index()
    {
        $site = $this->sites->defaultFor(currentTenant());
        $stats = ['approved' => Review::where('status', Review::STATUS_APPROVED)->count(), 'pending' => Review::where('status', Review::STATUS_PENDING)->count(), 'widgets' => ReviewWidget::count()];
        return view('modules.reviews.dashboard', ['site' => $site, 'stats' => $stats, 'reviews' => Review::latest()->limit(6)->get()]);
    }
}
