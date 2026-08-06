<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Forms\SiteService;

class DashboardController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            $this->authorize('viewAny', Form::class);

            return $next($request);
        });
    }

    public function index()
    {
        $site = $this->sites->defaultFor(currentTenant());

        return view('modules.forms.dashboard', [
            'site' => $site,
            'stats' => [
                'live' => Form::query()->where('status', Form::STATUS_LIVE)->count(),
                'forms' => Form::query()->count(),
                'submissions' => FormSubmission::query()->count(),
            ],
            'recentForms' => Form::query()->latest()->limit(8)->get(),
            'recentSubmissions' => FormSubmission::query()->with('form')->latest()->limit(8)->get(),
        ]);
    }
}
