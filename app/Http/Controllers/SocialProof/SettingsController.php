<?php

namespace App\Http\Controllers\SocialProof;

use App\Http\Controllers\Controller;
use App\Services\SocialProof\SiteService;
use App\Support\Privileges;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            abort_unless(
                $request->user()->hasPrivilege(Privileges::SOCIALPROOF_MANAGE) || $request->user()->isOwnerOrAdmin(),
                403
            );

            return $next($request);
        });
    }

    public function index()
    {
        $site = $this->sites->defaultFor(currentTenant());

        return view('modules.socialproof.settings', [
            'site' => $site,
            'settings' => $site->resolvedSettings(),
        ]);
    }

    public function install()
    {
        $site = $this->sites->defaultFor(currentTenant());

        return view('modules.socialproof.install', [
            'site' => $site,
            'snippet' => $this->sites->embedSnippet($site),
            'settings' => $site->resolvedSettings(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'allowed_origins' => ['nullable', 'string', 'max:5000'],
            'enabled' => ['nullable', 'boolean'],
            'position' => ['required', 'in:bottom-left,bottom-right,top-left,top-right'],
            'initial_delay_ms' => ['required', 'integer', 'min:0', 'max:120000'],
            'display_duration_ms' => ['required', 'integer', 'min:1000', 'max:60000'],
            'interval_ms' => ['required', 'integer', 'min:2000', 'max:120000'],
            'max_displays' => ['required', 'integer', 'min:0', 'max:1000'],
            'max_per_page' => ['required', 'integer', 'min:1', 'max:50'],
            'include_fake' => ['nullable', 'boolean'],
            'include_live_subscribers' => ['nullable', 'boolean'],
            'include_live_bookings' => ['nullable', 'boolean'],
            'include_api' => ['nullable', 'boolean'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'purchase_verb' => ['required', 'string', 'max:60'],
            'subscribe_verb' => ['required', 'string', 'max:60'],
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $data['include_fake'] = $request->boolean('include_fake');
        $data['include_live_subscribers'] = $request->boolean('include_live_subscribers');
        $data['include_live_bookings'] = $request->boolean('include_live_bookings');
        $data['include_api'] = $request->boolean('include_api');

        $this->sites->saveSettings($this->sites->defaultFor(currentTenant()), $data);

        return back()->with('success', 'Settings saved.');
    }

    public function rotateKey()
    {
        $this->sites->rotateKey($this->sites->defaultFor(currentTenant()));

        return back()->with('success', 'Site key rotated. Update your embed snippet.');
    }
}
