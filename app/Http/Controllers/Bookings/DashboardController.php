<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Services\Bookings\SiteService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            $this->authorize('viewAny', BookingService::class);

            return $next($request);
        });
    }

    public function index()
    {
        $site = $this->sites->defaultFor(currentTenant());

        $stats = [
            'services' => BookingService::query()->where('active', true)->count(),
            'upcoming' => BookingAppointment::query()
                ->where('status', BookingAppointment::STATUS_SCHEDULED)
                ->where('starts_at', '>=', now())
                ->count(),
            'total' => BookingAppointment::query()->count(),
        ];

        $upcoming = BookingAppointment::query()
            ->with('service')
            ->where('status', BookingAppointment::STATUS_SCHEDULED)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        return view('modules.bookings.dashboard', [
            'site' => $site,
            'stats' => $stats,
            'upcoming' => $upcoming,
            'publicUrl' => $this->sites->publicUrl($site),
        ]);
    }
}
