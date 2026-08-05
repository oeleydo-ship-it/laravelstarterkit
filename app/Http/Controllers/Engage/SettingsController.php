<?php

namespace App\Http\Controllers\Engage;

use App\Http\Controllers\Controller;
use App\Models\EngageCampaign;
use App\Services\Engage\SiteService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            abort_unless(
                $request->user()->hasPrivilege(\App\Support\Privileges::ENGAGE_MANAGE)
                    || $request->user()->isOwnerOrAdmin(),
                403
            );

            return $next($request);
        });
    }

    public function index()
    {
        $site = $this->sites->defaultFor(currentTenant());

        return view('modules.engage.settings', [
            'site' => $site,
            'snippet' => $this->sites->embedSnippet($site),
        ]);
    }

    public function install()
    {
        $site = $this->sites->defaultFor(currentTenant());

        return view('modules.engage.install', [
            'site' => $site,
            'snippet' => $this->sites->embedSnippet($site),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
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
            ->route('engage.install')
            ->with('success', 'Install key rotated. Update the snippet on your website.');
    }
}
