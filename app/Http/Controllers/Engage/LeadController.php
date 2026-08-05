<?php

namespace App\Http\Controllers\Engage;

use App\Http\Controllers\Controller;
use App\Models\EngageCampaign;
use App\Models\EngageLead;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->authorize('viewAny', EngageCampaign::class);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $leads = EngageLead::query()
            ->with('campaign')
            ->when($request->filled('campaign_id'), fn ($q) => $q->where('campaign_id', $request->query('campaign_id')))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('modules.engage.leads.index', [
            'leads' => $leads,
            'campaigns' => EngageCampaign::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', EngageCampaign::class);

        $filename = 'leads-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'email', 'campaign', 'page_url', 'created_at']);

            EngageLead::query()
                ->with('campaign')
                ->when($request->filled('campaign_id'), fn ($q) => $q->where('campaign_id', $request->query('campaign_id')))
                ->orderBy('id')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $lead) {
                        fputcsv($out, [
                            $lead->id,
                            $lead->name,
                            $lead->email,
                            $lead->campaign?->name,
                            $lead->page_url,
                            $lead->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
