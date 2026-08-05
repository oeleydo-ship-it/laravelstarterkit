<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChatAppearanceRequest;
use App\Http\Requests\UpdateChatBusinessHoursRequest;
use App\Http\Requests\UpdateChatIntegrationsRequest;
use App\Models\ChatApiToken;
use App\Models\ChatDocument;
use App\Models\Setting;
use App\Services\Chat\AiSettingsService;
use App\Services\Chat\ApiTokenService;
use App\Services\Chat\BusinessHoursService;
use App\Services\Chat\IntegrationSettingsService;
use App\Services\Chat\KnowledgeBaseService;
use App\Services\Chat\RoutingService;
use App\Services\Chat\WidgetSettingsService;
use Illuminate\Http\Request;

class ChatSettingsController extends Controller
{
    public function __construct(
        protected RoutingService $routing,
        protected WidgetSettingsService $appearance,
        protected BusinessHoursService $hours,
        protected IntegrationSettingsService $integrations,
        protected ApiTokenService $tokens,
        protected KnowledgeBaseService $knowledgeBase,
        protected AiSettingsService $aiSettings,
    ) {
    }

    /**
     * The settings screen is one page of vertical tabs. Which pane opens is
     * driven by `?tab=`, so a save can land the user back where they were and a
     * bookmark or a validation redirect keeps its place.
     */
    public const TABS = [
        'routing' => 'Routing',
        'appearance' => 'Widget appearance',
        'hours' => 'Business hours',
        'knowledge' => 'Knowledge base',
        'notifications' => 'Notifications & webhooks',
        'tokens' => 'API tokens',
        'install' => 'Install',
    ];

    public function index()
    {
        $tenant = currentTenant();

        return view('modules.chat.settings', [
            'tabs' => self::TABS,
            'activeTab' => array_key_exists(request('tab'), self::TABS) ? request('tab') : null,
            'embedSnippet' => $this->embedSnippet($tenant),
            'strategies' => RoutingService::strategies(),
            'current' => $this->routing->strategyFor($tenant),
            'appearance' => $this->appearance->for($tenant),
            'hours' => $this->hours->for($tenant),
            'dayLabels' => BusinessHoursService::DAYS,
            'timezones' => timezone_identifiers_list(),
            'isOpenNow' => $this->hours->isOpen($tenant),
            'widgetUrl' => route('chat.widget.show', $tenant->slug),
            'integrations' => $this->integrations->for($tenant),
            'apiTokens' => ChatApiToken::orderBy('name')->get(),
            'documents' => ChatDocument::orderByDesc('created_at')->get(),
            'autoReplyEnabled' => $this->knowledgeBase->autoReplyEnabled($tenant),
            'aiAvailable' => $this->aiSettings->makeProvider($tenant)->isConfigured(),
            'aiSettings' => $this->aiSettings->forForm($tenant),
            'aiProviders' => AiSettingsService::providers(),
            'openaiModels' => AiSettingsService::openaiModels(),
            'kimiModels' => AiSettingsService::kimiModels(),
            'kbExtensions' => config('chat.knowledge_base.extensions'),
            'kbMaxKb' => config('chat.knowledge_base.max_kb'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'routing_strategy' => 'required|in:'.implode(',', array_keys(RoutingService::strategies())),
        ]);

        Setting::set(RoutingService::SETTING_KEY, $validated['routing_strategy'], currentTenant()->id);

        return $this->backToTab('routing', 'Chat routing updated.');
    }

    public function updateAppearance(UpdateChatAppearanceRequest $request)
    {
        $this->appearance->save(currentTenant(), $request->validated());

        return $this->backToTab('appearance', 'Widget appearance updated.');
    }

    public function updateHours(UpdateChatBusinessHoursRequest $request)
    {
        $this->hours->save(currentTenant(), $request->validated());

        return $this->backToTab('hours', 'Business hours updated.');
    }

    public function updateIntegrations(UpdateChatIntegrationsRequest $request)
    {
        $this->integrations->save(currentTenant(), $request->validated());

        return $this->backToTab('notifications', 'Notifications and webhooks updated.');
    }

    public function storeToken(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:60']);

        [, $plain] = $this->tokens->issue(currentTenant(), $validated['name']);

        // Flashed once and never stored in plaintext — if it is lost the only
        // remedy is to revoke it and issue another.
        return $this->backToTab('tokens', 'API token created. Copy it now — it will not be shown again.')
            ->with('new_api_token', $plain);
    }

    public function destroyToken(ChatApiToken $token)
    {
        abort_if($token->tenant_id !== currentTenant()->id, 404);

        $token->delete();

        return $this->backToTab('tokens', 'API token revoked.');
    }

    /**
     * Saves redirect to their own pane rather than `back()`, so the page does
     * not snap to the first tab every time something is saved.
     */
    protected function backToTab(string $tab, string $message)
    {
        return redirect()
            ->route('chat.settings.index', ['tab' => $tab])
            ->with('success', $message);
    }

    /**
     * The one-line install snippet. The loader is served per tenant, so the
     * customer never has to configure a workspace id by hand.
     */
    protected function embedSnippet($tenant): string
    {
        return '<script src="'.route('chat.widget.embed', $tenant->slug).'" async></script>';
    }
}
