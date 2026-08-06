<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Services\Reviews\SiteService;
use App\Support\Privileges;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->hasPrivilege(Privileges::REVIEWS_MANAGE) || $request->user()->isOwnerOrAdmin(), 403);
            return $next($request);
        });
    }
    public function index() { return view('modules.reviews.settings', ['site' => $this->sites->defaultFor(currentTenant())]); }
    public function install() { $site = $this->sites->defaultFor(currentTenant()); return view('modules.reviews.install', ['site' => $site, 'snippet' => $this->sites->embedSnippet($site)]); }
    public function update(Request $request) { $this->sites->saveSettings($this->sites->defaultFor(currentTenant()), $request->validate(['name' => ['required','string','max:120'], 'allowed_origins' => ['nullable','string','max:5000']])); return back()->with('success', 'Settings saved.'); }
}
