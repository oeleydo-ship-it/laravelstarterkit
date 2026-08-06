<?php

namespace App\Http\Controllers\SocialProof;

use App\Http\Controllers\Controller;
use App\Models\SocialProofEvent;
use App\Services\SocialProof\SiteService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->authorizeResource(SocialProofEvent::class, 'event');
    }

    public function index(Request $request)
    {
        $query = SocialProofEvent::query()->latest('occurred_at')->latest('id');

        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return view('modules.socialproof.events.index', [
            'events' => $query->paginate(25)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('modules.socialproof.events.form', [
            'event' => new SocialProofEvent([
                'type' => SocialProofEvent::TYPE_PURCHASE,
                'source' => SocialProofEvent::SOURCE_FAKE,
                'is_active' => true,
                'occurred_at' => now(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $site = $this->sites->defaultFor(currentTenant());
        $event = SocialProofEvent::create([
            'tenant_id' => currentTenant()->id,
            'social_proof_site_id' => $site->id,
            ...$this->payload($request),
        ]);

        return redirect()->route('socialproof.events.edit', $event)->with('success', 'Notification created.');
    }

    public function edit(SocialProofEvent $event)
    {
        return view('modules.socialproof.events.form', compact('event'));
    }

    public function update(Request $request, SocialProofEvent $event)
    {
        $event->update($this->payload($request));

        return back()->with('success', 'Notification saved.');
    }

    public function destroy(SocialProofEvent $event)
    {
        $event->delete();

        return redirect()->route('socialproof.events.index')->with('success', 'Notification deleted.');
    }

    protected function payload(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:purchase,subscribe'],
            'source' => ['required', 'in:fake,api'],
            'customer_name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'item_name' => ['required', 'string', 'max:190'],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
            'product_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        return [
            'type' => $data['type'],
            'source' => $data['source'],
            'customer_name' => $data['customer_name'],
            'location' => $data['location'] ?? null,
            'item_name' => $data['item_name'],
            'avatar_url' => $data['avatar_url'] ?? null,
            'product_url' => $data['product_url'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'occurred_at' => $data['occurred_at'] ?? now(),
        ];
    }
}
