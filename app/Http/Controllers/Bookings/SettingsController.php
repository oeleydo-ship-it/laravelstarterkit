<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Services\Bookings\SiteService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            abort_unless(
                $request->user()->hasPrivilege(\App\Support\Privileges::BOOKINGS_MANAGE)
                    || $request->user()->isOwnerOrAdmin(),
                403
            );

            return $next($request);
        });
    }

    public function index()
    {
        $site = $this->sites->defaultFor(currentTenant());

        return view('modules.bookings.settings', [
            'site' => $site,
            'publicUrl' => $this->sites->publicUrl($site),
            'snippet' => $this->sites->embedSnippet($site),
        ]);
    }

    public function install()
    {
        $site = $this->sites->defaultFor(currentTenant());

        return view('modules.bookings.install', [
            'site' => $site,
            'publicUrl' => $this->sites->publicUrl($site),
            'snippet' => $this->sites->embedSnippet($site),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'timezone'],
            'brand_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'allowed_origins' => ['nullable', 'string', 'max:5000'],
        ]);

        $site = $this->sites->defaultFor(currentTenant());
        $this->sites->saveSettings($site, $validated);

        return back()->with('success', 'Settings saved.');
    }

    public function rotateKey()
    {
        $site = $this->sites->defaultFor(currentTenant());
        $this->sites->rotateKey($site);

        return redirect()
            ->route('bookings.install')
            ->with('success', 'Booking link key rotated. Share the new URL.');
    }
}
