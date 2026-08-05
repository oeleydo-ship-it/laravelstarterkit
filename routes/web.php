<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\WorkspaceTeamController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SuperAdmin;
use App\Http\Controllers\Chat\ArticleController as ChatArticleController;
use App\Http\Controllers\Chat\AssistController as ChatAssistController;
use App\Http\Controllers\Chat\AttachmentController as ChatAttachmentController;
use App\Http\Controllers\Chat\AvailabilityController as ChatAvailabilityController;
use App\Http\Controllers\Chat\CannedResponseController as ChatCannedResponseController;
use App\Http\Controllers\Chat\ChatSettingsController;
use App\Http\Controllers\Chat\ChatDocumentController;
use App\Http\Controllers\Chat\ConversationController as ChatConversationController;
use App\Http\Controllers\Chat\MessageController as ChatMessageController;
use App\Http\Controllers\Chat\ReportController as ChatReportController;
use App\Http\Controllers\Chat\WidgetController as ChatWidgetController;
use App\Http\Controllers\EmailMarketing\CampaignController as EmailCampaignController;
use App\Http\Controllers\EmailMarketing\DashboardController as EmailDashboardController;
use App\Http\Controllers\EmailMarketing\ListController as EmailListController;
use App\Http\Controllers\EmailMarketing\ReportController as EmailReportController;
use App\Http\Controllers\EmailMarketing\SettingsController as EmailSettingsController;
use App\Http\Controllers\EmailMarketing\SubscriberController as EmailSubscriberController;
use App\Http\Controllers\EmailMarketing\TemplateController as EmailTemplateController;
use App\Http\Controllers\EmailMarketing\TrackingController as EmailTrackingController;
use App\Http\Controllers\EmailMarketing\UnsubscribeController as EmailUnsubscribeController;
use App\Http\Controllers\Engage\CampaignController as EngageCampaignController;
use App\Http\Controllers\Engage\DashboardController as EngageDashboardController;
use App\Http\Controllers\Engage\EmbedController as EngageEmbedController;
use App\Http\Controllers\Engage\LeadController as EngageLeadController;
use App\Http\Controllers\Engage\SettingsController as EngageSettingsController;
use App\Models\Plan;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('landing');

Route::get('/pricing', function () {
    $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
    return view('pricing', compact('plans'));
})->name('pricing');

/*
|--------------------------------------------------------------------------
| Email Marketing (public tracking & unsubscribe)
|--------------------------------------------------------------------------
*/

Route::prefix('email')->name('email.')->middleware('throttle:120,1')->group(function () {
    Route::get('track/open/{token}', [EmailTrackingController::class, 'open'])->name('track.open');
    Route::get('track/click/{token}', [EmailTrackingController::class, 'click'])->name('track.click');
    Route::get('unsubscribe/{token}', [EmailUnsubscribeController::class, 'show'])->name('unsubscribe.show');
    Route::post('unsubscribe/{token}', [EmailUnsubscribeController::class, 'store'])->name('unsubscribe.store');
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Laravel UI)
|--------------------------------------------------------------------------
*/

Auth::routes();

/*
|--------------------------------------------------------------------------
| Invite Accept Routes (public)
|--------------------------------------------------------------------------
*/

Route::get('/invite/{token}', [InviteController::class, 'accept'])->name('invite.accept');
Route::post('/invite/{token}', [InviteController::class, 'register'])->name('invite.register');

/*
|--------------------------------------------------------------------------
| Stripe Webhook (no CSRF)
|--------------------------------------------------------------------------
*/

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Onboarding (auth required, no tenant)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

/*
|--------------------------------------------------------------------------
| App Routes (auth + tenant middleware)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', \App\Http\Middleware\SetTenant::class])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', fn() => redirect()->route('dashboard'));

    // Settings
    Route::middleware(['privilege:settings.manage'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    // Team Management (owner/admin)
    Route::middleware([\App\Http\Middleware\CheckRole::class . ':owner,admin'])->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team/invite', [TeamController::class, 'invite'])->name('team.invite');
        Route::post('/team/groups', [WorkspaceTeamController::class, 'store'])->name('team.groups.store');
        Route::put('/team/groups/{team}', [WorkspaceTeamController::class, 'update'])->name('team.groups.update');
        Route::delete('/team/groups/{team}', [WorkspaceTeamController::class, 'destroy'])->name('team.groups.destroy');
        Route::put('/team/{user}/role', [TeamController::class, 'changeRole'])->name('team.changeRole');
        Route::put('/team/{user}/status', [TeamController::class, 'toggleStatus'])->name('team.toggleStatus');
        Route::put('/team/{user}/privileges', [WorkspaceTeamController::class, 'updatePrivileges'])->name('team.privileges');
    });

    // Billing
    Route::middleware(['privilege:billing.manage'])->group(function () {
        Route::get('/billing/plans', [BillingController::class, 'plans'])->name('billing.plans');
        Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
        Route::get('/billing/status', [BillingController::class, 'status'])->name('billing.status');
    });

    // Modules Management (owner/admin or modules.manage)
    Route::middleware(['privilege:modules.manage'])->group(function () {
        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::post('/modules/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle');
    });

    // ─── Module-Protected Routes ───

    // Clients / CRM Module
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':clients'])->group(function () {
        Route::resource('clients', ClientController::class);
        Route::post('clients/{client}/notes', [ClientController::class, 'storeNote'])->name('clients.notes.store');
    });

    // Tickets Module
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':tickets'])->group(function () {
        Route::resource('tickets', TicketController::class);
    });

    // Live Chat Module (agent side)
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':chat'])->prefix('chat')->name('chat.')->group(function () {
        Route::resource('conversations', ChatConversationController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::put('conversations/{conversation}/visitor', [ChatConversationController::class, 'updateVisitor'])->name('conversations.visitor.update');
        Route::post('conversations/{conversation}/messages', [ChatMessageController::class, 'store'])->name('conversations.messages.store');
        Route::get('conversations/{conversation}/messages', [ChatMessageController::class, 'index'])->name('conversations.messages.index');
        Route::post('conversations/{conversation}/notes', [ChatMessageController::class, 'note'])->name('conversations.notes.store');
        Route::post('conversations/{conversation}/attachments', [ChatAttachmentController::class, 'store'])->name('conversations.attachments.store');
        Route::get('attachments/{attachment}', [ChatAttachmentController::class, 'download'])->name('attachments.download');
        Route::post('conversations/{conversation}/read', [ChatMessageController::class, 'read'])->name('conversations.read');
        Route::post('conversations/{conversation}/typing', [ChatMessageController::class, 'typing'])->name('conversations.typing');
        Route::put('conversations/{conversation}/transfer', [ChatConversationController::class, 'transfer'])->name('conversations.transfer');

        Route::post('conversations/{conversation}/suggest', [ChatAssistController::class, 'suggest'])->name('conversations.suggest');

        // Knowledge base. `search` is declared before the resource so it is not
        // captured by the {article} wildcard.
        Route::get('articles/search', [ChatArticleController::class, 'search'])->name('articles.search');
        Route::resource('articles', ChatArticleController::class)->except(['show']);

        Route::resource('canned-responses', ChatCannedResponseController::class)
            ->parameters(['canned-responses' => 'canned_response'])
            ->except(['show']);

        // Agent availability (any team member can set their own)
        Route::put('availability', [ChatAvailabilityController::class, 'update'])->name('availability.update');

        // Reports and settings (owner/admin or chat.manage)
        Route::middleware([\App\Http\Middleware\EnsurePrivilege::class . ':chat.manage'])->group(function () {
            Route::get('reports', [ChatReportController::class, 'index'])->name('reports.index');
            Route::get('reports/export', [ChatReportController::class, 'export'])->name('reports.export');

            Route::get('settings', [ChatSettingsController::class, 'index'])->name('settings.index');
            Route::put('settings', [ChatSettingsController::class, 'update'])->name('settings.update');
            Route::put('settings/appearance', [ChatSettingsController::class, 'updateAppearance'])->name('settings.appearance');
            Route::put('settings/hours', [ChatSettingsController::class, 'updateHours'])->name('settings.hours');
            Route::put('settings/integrations', [ChatSettingsController::class, 'updateIntegrations'])->name('settings.integrations');
            Route::put('settings/knowledge', [ChatDocumentController::class, 'updateSettings'])->name('settings.knowledge');
            Route::post('settings/documents', [ChatDocumentController::class, 'store'])->name('settings.documents.store');
            Route::put('settings/documents/{document}/toggle', [ChatDocumentController::class, 'toggle'])->name('settings.documents.toggle');
            Route::get('settings/documents/{document}/download', [ChatDocumentController::class, 'download'])->name('settings.documents.download');
            Route::delete('settings/documents/{document}', [ChatDocumentController::class, 'destroy'])->name('settings.documents.destroy');
            Route::post('settings/api-tokens', [ChatSettingsController::class, 'storeToken'])->name('settings.tokens.store');
            Route::delete('settings/api-tokens/{token}', [ChatSettingsController::class, 'destroyToken'])->name('settings.tokens.destroy');
        });
    });

    // Email Marketing Module
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':email'])->prefix('email')->name('email.')->group(function () {
        Route::get('/', [EmailDashboardController::class, 'index'])->name('dashboard');

        Route::resource('lists', EmailListController::class);

        Route::get('subscribers/import', [EmailSubscriberController::class, 'importForm'])->name('subscribers.import');
        Route::post('subscribers/import', [EmailSubscriberController::class, 'import'])->name('subscribers.import.store');
        Route::post('subscribers/import-clients', [EmailSubscriberController::class, 'importFromClients'])->name('subscribers.import-clients');
        Route::resource('subscribers', EmailSubscriberController::class);

        Route::resource('templates', EmailTemplateController::class);

        Route::post('campaigns/{campaign}/send', [EmailCampaignController::class, 'send'])->name('campaigns.send');
        Route::post('campaigns/{campaign}/schedule', [EmailCampaignController::class, 'schedule'])->name('campaigns.schedule');
        Route::post('campaigns/{campaign}/cancel', [EmailCampaignController::class, 'cancel'])->name('campaigns.cancel');
        Route::get('campaigns/{campaign}/preview', [EmailCampaignController::class, 'preview'])->name('campaigns.preview');
        Route::post('campaigns/{campaign}/apply-template', [EmailCampaignController::class, 'applyTemplate'])->name('campaigns.apply-template');
        Route::resource('campaigns', EmailCampaignController::class);

        Route::get('reports', [EmailReportController::class, 'index'])->name('reports.index');

        Route::get('settings', [EmailSettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [EmailSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/test', [EmailSettingsController::class, 'sendTest'])->name('settings.test');
    });

    // Engage Module
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':engage'])->prefix('engage')->name('engage.')->group(function () {
        Route::get('/', [EngageDashboardController::class, 'index'])->name('dashboard');
        Route::resource('campaigns', EngageCampaignController::class)->except(['show']);
        Route::get('leads', [EngageLeadController::class, 'index'])->name('leads.index');
        Route::get('leads/export', [EngageLeadController::class, 'export'])->name('leads.export');
        Route::get('install', [EngageSettingsController::class, 'install'])->name('install');
        Route::get('settings', [EngageSettingsController::class, 'index'])->name('settings');
        Route::put('settings', [EngageSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/rotate', [EngageSettingsController::class, 'rotateKey'])->name('settings.rotate');
    });

    // ─── Profile ───
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| Live Chat Widget (public, unauthenticated visitors)
|--------------------------------------------------------------------------
*/

Route::prefix('widget/{tenantSlug}')
    ->middleware([
        'throttle:60,1',
        \App\Http\Middleware\SetTenantFromSlug::class,
        \App\Http\Middleware\AllowChatWidgetFraming::class,
    ])
    ->name('chat.widget.')
    ->group(function () {
        Route::get('/', [ChatWidgetController::class, 'show'])->name('show');
        Route::get('/embed.js', [ChatWidgetController::class, 'embedScript'])->name('embed');
        Route::get('/knowledge', [ChatWidgetController::class, 'knowledge'])->name('knowledge');
        Route::post('/start', [ChatWidgetController::class, 'start'])->name('start');
        Route::post('/broadcasting/auth', [ChatWidgetController::class, 'authorizeChannel'])->name('broadcasting.auth');
        Route::get('/conversations/{conversationId}/messages', [ChatWidgetController::class, 'messages'])->name('messages.index');
        Route::post('/conversations/{conversationId}/messages', [ChatWidgetController::class, 'sendMessage'])->name('messages.store');
        Route::post('/conversations/{conversationId}/attachments', [ChatWidgetController::class, 'sendAttachment'])->name('attachments.store');
        Route::get('/conversations/{conversationId}/attachments/{attachmentId}', [ChatWidgetController::class, 'downloadAttachment'])->name('attachments.download');
        Route::post('/conversations/{conversationId}/rating', [ChatWidgetController::class, 'rate'])->name('rating.store');
        Route::post('/conversations/{conversationId}/typing', [ChatWidgetController::class, 'typing'])->name('typing');
    });

/*
|--------------------------------------------------------------------------
| White-label on-site embed (opaque /x/{siteKey})
|--------------------------------------------------------------------------
*/

Route::prefix('x/{siteKey}')
    ->middleware([
        'throttle:120,1',
        \App\Http\Middleware\SetTenantFromEngageSiteKey::class,
        \App\Http\Middleware\AllowPublicFraming::class,
    ])
    ->where(['siteKey' => '[A-Za-z0-9]+'])
    ->group(function () {
        Route::options('{any?}', [EngageEmbedController::class, 'preflight'])->where('any', '.*');
        Route::get('c', [EngageEmbedController::class, 'config']);
        Route::post('e', [EngageEmbedController::class, 'event']);
        Route::post('l', [EngageEmbedController::class, 'lead']);
    });

Route::get('/x/{siteKey}.js', [EngageEmbedController::class, 'boot'])
    ->middleware([
        'throttle:120,1',
        \App\Http\Middleware\SetTenantFromEngageSiteKey::class,
        \App\Http\Middleware\AllowPublicFraming::class,
    ])
    ->where(['siteKey' => '[A-Za-z0-9]+']);

/*
|--------------------------------------------------------------------------
| Superadmin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('superadmin')
    ->middleware(['auth', \App\Http\Middleware\EnsureSuperAdmin::class])
    ->group(function () {
        Route::get('/', [SuperAdmin\DashboardController::class, 'index'])->name('superadmin.dashboard');
        Route::get('/settings', [SuperAdmin\SettingsController::class, 'index'])->name('superadmin.settings');
        Route::put('/settings', [SuperAdmin\SettingsController::class, 'update'])->name('superadmin.settings.update');
        Route::resource('/plans', SuperAdmin\PlanController::class)->names('superadmin.plans');
    });

