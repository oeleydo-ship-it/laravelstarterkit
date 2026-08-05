<?php

namespace App\Http\Controllers\Engage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Engage\CampaignRequest;
use App\Models\EngageCampaign;
use App\Services\Engage\SiteService;
use App\Support\EngageTemplates;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->authorizeResource(EngageCampaign::class, 'campaign');
    }

    public function index(Request $request)
    {
        $query = EngageCampaign::query()->orderByDesc('priority')->orderByDesc('id');

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return view('modules.engage.campaigns.index', [
            'campaigns' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create(Request $request)
    {
        $templateKey = $request->query('template');

        if (! $templateKey) {
            return view('modules.engage.campaigns.templates', [
                'groups' => EngageTemplates::grouped(),
            ]);
        }

        $template = EngageTemplates::get($templateKey);
        $campaign = $template
            ? EngageTemplates::applyToCampaign($template['defaults'])
            : new EngageCampaign([
                'type' => EngageCampaign::TYPE_BAR,
                'status' => EngageCampaign::STATUS_DRAFT,
            ]);

        return view('modules.engage.campaigns.form', [
            'campaign' => $campaign,
            'templateKey' => $templateKey,
            'templateLabel' => $template['label'] ?? null,
            'openable' => EngageCampaign::query()
                ->whereIn('type', [
                    EngageCampaign::TYPE_POPUP,
                    EngageCampaign::TYPE_SLIDE_IN,
                    EngageCampaign::TYPE_FORM,
                    EngageCampaign::TYPE_VIDEO,
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(CampaignRequest $request)
    {
        $site = $this->sites->defaultFor(currentTenant());

        $campaign = EngageCampaign::create([
            ...$request->campaignPayload(),
            'tenant_id' => currentTenant()->id,
            'engage_site_id' => $site->id,
        ]);

        return redirect()
            ->route('engage.campaigns.edit', $campaign)
            ->with('success', 'Campaign created.');
    }

    public function edit(EngageCampaign $campaign)
    {
        return view('modules.engage.campaigns.form', [
            'campaign' => $campaign,
            'templateKey' => null,
            'templateLabel' => null,
            'openable' => EngageCampaign::query()
                ->where('id', '!=', $campaign->id)
                ->whereIn('type', [
                    EngageCampaign::TYPE_POPUP,
                    EngageCampaign::TYPE_SLIDE_IN,
                    EngageCampaign::TYPE_FORM,
                    EngageCampaign::TYPE_VIDEO,
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(CampaignRequest $request, EngageCampaign $campaign)
    {
        $campaign->update($request->campaignPayload());

        return redirect()
            ->route('engage.campaigns.edit', $campaign)
            ->with('success', 'Campaign saved.');
    }

    public function destroy(EngageCampaign $campaign)
    {
        $campaign->delete();

        return redirect()
            ->route('engage.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }
}
