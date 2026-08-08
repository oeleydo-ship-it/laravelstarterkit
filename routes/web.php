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
use App\Http\Controllers\Auth\GoogleController;
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
use App\Http\Controllers\Forms\DashboardController as FormsDashboardController;
use App\Http\Controllers\Forms\FormController as FormsFormController;
use App\Http\Controllers\Forms\SubmissionController as FormsSubmissionController;
use App\Http\Controllers\Forms\SettingsController as FormsSettingsController;
use App\Http\Controllers\Forms\EmbedController as FormsEmbedController;
use App\Http\Controllers\Reviews\DashboardController as ReviewsDashboardController;
use App\Http\Controllers\Reviews\EmbedController as ReviewsEmbedController;
use App\Http\Controllers\Reviews\ReviewController as ReviewsReviewController;
use App\Http\Controllers\Reviews\SettingsController as ReviewsSettingsController;
use App\Http\Controllers\Reviews\WidgetController as ReviewsWidgetController;
use App\Http\Controllers\Bookings\DashboardController as BookingsDashboardController;
use App\Http\Controllers\Bookings\ServiceController as BookingsServiceController;
use App\Http\Controllers\Bookings\AvailabilityController as BookingsAvailabilityController;
use App\Http\Controllers\Bookings\AppointmentController as BookingsAppointmentController;
use App\Http\Controllers\Bookings\SettingsController as BookingsSettingsController;
use App\Http\Controllers\Bookings\PublicController as BookingsPublicController;
use App\Http\Controllers\SocialProof\DashboardController as SocialProofDashboardController;
use App\Http\Controllers\SocialProof\EmbedController as SocialProofEmbedController;
use App\Http\Controllers\SocialProof\EventController as SocialProofEventController;
use App\Http\Controllers\SocialProof\SettingsController as SocialProofSettingsController;
use App\Http\Controllers\Autoblog\DashboardController as AutoblogDashboardController;
use App\Http\Controllers\Autoblog\PostController as AutoblogPostController;
use App\Http\Controllers\Autoblog\DestinationController as AutoblogDestinationController;
use App\Http\Controllers\Autoblog\SettingsController as AutoblogSettingsController;
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
Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

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

    // Forms Module (Reviews and Bookings module routes follow here.)
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':forms'])->prefix('forms')->name('forms.')->group(function () {
        Route::get('/', [FormsDashboardController::class, 'index'])->name('dashboard');
        Route::resource('forms', FormsFormController::class)->except(['show']);
        Route::get('submissions', [FormsSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/export', [FormsSubmissionController::class, 'export'])->name('submissions.export');
        Route::get('install', [FormsSettingsController::class, 'install'])->name('install');
        Route::get('settings', [FormsSettingsController::class, 'index'])->name('settings');
        Route::put('settings', [FormsSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/rotate', [FormsSettingsController::class, 'rotateKey'])->name('settings.rotate');
    });

    // Reviews & Testimonials Module
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':reviews'])->prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [ReviewsDashboardController::class, 'index'])->name('dashboard');
        Route::get('reviews', [ReviewsReviewController::class, 'index'])->name('index');
        Route::put('reviews/{review}/approve', [ReviewsReviewController::class, 'approve'])->name('approve');
        Route::put('reviews/{review}/reject', [ReviewsReviewController::class, 'reject'])->name('reject');
        Route::delete('reviews/{review}', [ReviewsReviewController::class, 'destroy'])->name('destroy');
        Route::resource('widgets', ReviewsWidgetController::class)->except(['show']);
        Route::get('install', [ReviewsSettingsController::class, 'install'])->name('install');
        Route::get('settings', [ReviewsSettingsController::class, 'index'])->name('settings');
        Route::put('settings', [ReviewsSettingsController::class, 'update'])->name('settings.update');
    });

    // Bookings Module
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':bookings'])->prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingsDashboardController::class, 'index'])->name('dashboard');
        Route::resource('services', BookingsServiceController::class)->except(['show']);
        Route::get('availability', [BookingsAvailabilityController::class, 'edit'])->name('availability.edit');
        Route::put('availability', [BookingsAvailabilityController::class, 'update'])->name('availability.update');
        Route::post('availability/exceptions', [BookingsAvailabilityController::class, 'storeException'])->name('availability.exceptions.store');
        Route::delete('availability/exceptions/{exception}', [BookingsAvailabilityController::class, 'destroyException'])->name('availability.exceptions.destroy');
        Route::get('appointments', [BookingsAppointmentController::class, 'index'])->name('appointments.index');
        Route::put('appointments/{appointment}/status', [BookingsAppointmentController::class, 'updateStatus'])->name('appointments.status');
        Route::get('install', [BookingsSettingsController::class, 'install'])->name('install');
        Route::get('settings', [BookingsSettingsController::class, 'index'])->name('settings');
        Route::put('settings', [BookingsSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/rotate', [BookingsSettingsController::class, 'rotateKey'])->name('settings.rotate');
    });

    // Social Proof Module
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':socialproof'])->prefix('socialproof')->name('socialproof.')->group(function () {
        Route::get('/', [SocialProofDashboardController::class, 'index'])->name('dashboard');
        Route::resource('events', SocialProofEventController::class)->except(['show']);
        Route::get('install', [SocialProofSettingsController::class, 'install'])->name('install');
        Route::get('settings', [SocialProofSettingsController::class, 'index'])->name('settings');
        Route::put('settings', [SocialProofSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/rotate', [SocialProofSettingsController::class, 'rotateKey'])->name('settings.rotate');
    });

    // AI Autoblog Module
    Route::middleware([\App\Http\Middleware\EnsureModuleEnabled::class . ':autoblog'])->prefix('autoblog')->name('autoblog.')->group(function () {
        Route::get('/', [AutoblogDashboardController::class, 'index'])->name('dashboard');
        Route::post('posts', [AutoblogPostController::class, 'store'])->name('posts.store');
        Route::get('post-library', [AutoblogPostController::class, 'index'])->name('posts.index');
        Route::get('posts/{post}', [AutoblogPostController::class, 'show'])->name('posts.show');
        Route::put('posts/{post}', [AutoblogPostController::class, 'update'])->name('posts.update');
        Route::post('posts/{post}/publish', [AutoblogPostController::class, 'publish'])->name('posts.publish');
        Route::post('posts/{post}/retry', [AutoblogPostController::class, 'retry'])->name('posts.retry');
        Route::delete('posts/{post}', [AutoblogPostController::class, 'destroy'])->name('posts.destroy');
        Route::post('destinations', [AutoblogDestinationController::class, 'store'])->name('destinations.store');
        Route::get('destination-connections', [AutoblogDestinationController::class, 'index'])->name('destinations.index');
        Route::post('destinations/{destination}/verify', [AutoblogDestinationController::class, 'verify'])->name('destinations.verify');
        Route::put('destinations/{destination}', [AutoblogDestinationController::class, 'update'])->name('destinations.update');
        Route::delete('destinations/{destination}', [AutoblogDestinationController::class, 'destroy'])->name('destinations.destroy');
        Route::put('settings', [AutoblogSettingsController::class, 'update'])->name('settings.update');
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

// White-label public form embed.
Route::prefix('f/{siteKey}')->middleware(['throttle:120,1', \App\Http\Middleware\SetTenantFromFormSiteKey::class, \App\Http\Middleware\AllowPublicFraming::class])->where(['siteKey' => '[A-Za-z0-9]+'])->group(function () {
    Route::options('{any?}', [FormsEmbedController::class, 'preflight'])->where('any', '.*');
    Route::get('c', [FormsEmbedController::class, 'config']);
    Route::post('s', [FormsEmbedController::class, 'submit']);
});
Route::get('/f/{siteKey}.js', [FormsEmbedController::class, 'boot'])->middleware(['throttle:120,1', \App\Http\Middleware\SetTenantFromFormSiteKey::class, \App\Http\Middleware\AllowPublicFraming::class])->where(['siteKey' => '[A-Za-z0-9]+']);

// White-label reviews embed and public submission routes.
Route::prefix('r/{siteKey}')->middleware(['throttle:120,1', \App\Http\Middleware\SetTenantFromReviewSiteKey::class, \App\Http\Middleware\AllowPublicFraming::class])->where(['siteKey' => '[A-Za-z0-9]+'])->group(function () {
    Route::get('c', [ReviewsEmbedController::class, 'config']);
    Route::post('s', [ReviewsEmbedController::class, 'submit']);
    Route::get('write', [ReviewsEmbedController::class, 'write'])->name('reviews.write');
});
Route::get('/r/{siteKey}.js', [ReviewsEmbedController::class, 'boot'])
    ->middleware(['throttle:120,1', \App\Http\Middleware\SetTenantFromReviewSiteKey::class, \App\Http\Middleware\AllowPublicFraming::class])
    ->where(['siteKey' => '[A-Za-z0-9]+']);

// Public booking page + site widget loader
Route::get('/b/{siteKey}.js', [BookingsPublicController::class, 'boot'])
    ->middleware([
        'throttle:120,1',
        \App\Http\Middleware\SetTenantFromBookingSiteKey::class,
        \App\Http\Middleware\AllowPublicFraming::class,
    ])
    ->where(['siteKey' => '[A-Za-z0-9]+']);

Route::prefix('b/{siteKey}')
    ->middleware([
        'throttle:60,1',
        \App\Http\Middleware\SetTenantFromBookingSiteKey::class,
        \App\Http\Middleware\AllowPublicFraming::class,
    ])
    ->where(['siteKey' => '[A-Za-z0-9]+'])
    ->group(function () {
        Route::get('/', [BookingsPublicController::class, 'show']);
        Route::get('slots', [BookingsPublicController::class, 'slots']);
        Route::post('book', [BookingsPublicController::class, 'book']);
    });

// White-label social proof purchase / subscribe toasts
Route::prefix('sp/{siteKey}')->middleware(['throttle:120,1', \App\Http\Middleware\SetTenantFromSocialProofSiteKey::class, \App\Http\Middleware\AllowPublicFraming::class])->where(['siteKey' => '[A-Za-z0-9]+'])->group(function () {
    Route::options('{any?}', [SocialProofEmbedController::class, 'preflight'])->where('any', '.*');
    Route::get('c', [SocialProofEmbedController::class, 'config']);
    Route::post('e', [SocialProofEmbedController::class, 'ingest']);
});
Route::get('/sp/{siteKey}.js', [SocialProofEmbedController::class, 'boot'])
    ->middleware(['throttle:120,1', \App\Http\Middleware\SetTenantFromSocialProofSiteKey::class, \App\Http\Middleware\AllowPublicFraming::class])
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
        Route::resource('/users', SuperAdmin\UserController::class)->except(['show'])->names('superadmin.users');
        Route::resource('/workspaces', SuperAdmin\TenantController::class)
            ->parameters(['workspaces' => 'tenant'])
            ->only(['index', 'edit', 'update'])->names('superadmin.tenants');
        Route::get('/settings', [SuperAdmin\SettingsController::class, 'index'])->name('superadmin.settings');
        Route::put('/settings', [SuperAdmin\SettingsController::class, 'update'])->name('superadmin.settings.update');
        Route::resource('/plans', SuperAdmin\PlanController::class)->names('superadmin.plans');
    });
