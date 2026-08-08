<?php

namespace App\Providers;

use App\Models\ChatArticle;
use App\Models\ChatCannedResponse;
use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\EmailList;
use App\Models\EmailSubscriber;
use App\Models\EmailTemplate;
use App\Models\EngageCampaign;
use App\Models\Form;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\Review;
use App\Models\ReviewWidget;
use App\Models\SocialProofEvent;
use App\Models\Ticket;
use App\Policies\ChatArticlePolicy;
use App\Policies\ChatCannedResponsePolicy;
use App\Policies\ChatConversationPolicy;
use App\Policies\ClientPolicy;
use App\Policies\EmailCampaignPolicy;
use App\Policies\EmailListPolicy;
use App\Policies\EmailSubscriberPolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\EngageCampaignPolicy;
use App\Policies\FormPolicy;
use App\Policies\BookingAppointmentPolicy;
use App\Policies\BookingServicePolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ReviewWidgetPolicy;
use App\Policies\SocialProofEventPolicy;
use App\Policies\TicketPolicy;
use App\Services\Chat\Ai\AiProvider;
use App\Services\Chat\AiSettingsService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Resolved per request from workspace settings (OpenAI / Kimi / Anthropic),
        // with .env as a fallback when nothing is saved in Chat Settings.
        $this->app->bind(AiProvider::class, function ($app) {
            $tenant = app()->bound('tenant') ? app('tenant') : null;

            return $app->make(AiSettingsService::class)->makeProvider($tenant);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Composer package discovery runs before deployment platforms attach the
        // shared database to a fresh release. System settings are optional at
        // this stage, so an unavailable database must not abort the deployment.
        try {
            if (Schema::hasTable('system_settings')) {
                config([
                    'app.name' => SystemSetting::get('app_name', config('app.name')),
                    'app.timezone' => SystemSetting::get('timezone', config('app.timezone')),
                    'cashier.key' => SystemSetting::get('stripe_key', config('cashier.key')),
                    'cashier.secret' => SystemSetting::get('stripe_secret', config('cashier.secret')),
                    'cashier.webhook.secret' => SystemSetting::get('stripe_webhook_secret', config('cashier.webhook.secret')),
                ]);
            }
        } catch (\Throwable) {
            // Keep environment configuration until the database is available.
        }
        // Register policies
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(ChatConversation::class, ChatConversationPolicy::class);
        Gate::policy(ChatCannedResponse::class, ChatCannedResponsePolicy::class);
        Gate::policy(ChatArticle::class, ChatArticlePolicy::class);
        Gate::policy(EmailList::class, EmailListPolicy::class);
        Gate::policy(EmailSubscriber::class, EmailSubscriberPolicy::class);
        Gate::policy(EmailTemplate::class, EmailTemplatePolicy::class);
        Gate::policy(EmailCampaign::class, EmailCampaignPolicy::class);
        Gate::policy(EngageCampaign::class, EngageCampaignPolicy::class);
        Gate::policy(Form::class, FormPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(ReviewWidget::class, ReviewWidgetPolicy::class);
        Gate::policy(BookingService::class, BookingServicePolicy::class);
        Gate::policy(BookingAppointment::class, BookingAppointmentPolicy::class);
        Gate::policy(SocialProofEvent::class, SocialProofEventPolicy::class);

        // Broadcasting auth: authenticated agents resolve normally; unauthenticated
        // widget visitors identify themselves via a `chat_visitor_token` param so
        // private chat channels can authorize them too.
        //
        // NOTE: the widget does not use this route — it has no session, and this
        // one is CSRF-protected. It authorizes against its own endpoint instead,
        // WidgetController@authorizeChannel. This branch remains only for any
        // non-browser client that can satisfy CSRF.
        Broadcast::resolveAuthenticatedUserUsing(function ($request) {
            if ($request->user()) {
                return $request->user();
            }

            if ($token = $request->input('chat_visitor_token')) {
                return ChatVisitor::withoutGlobalScopes()->where('token', $token)->first();
            }

            return null;
        });

        // Superadmin bypass — superadmins pass all gates
        Gate::before(function ($user, $ability) {
            if ($user->is_superadmin) {
                return true;
            }
        });

        // Define gates for privileges / roles
        Gate::define('manage-team', function ($user) {
            return $user->isOwnerOrAdmin() || $user->hasPrivilege(\App\Support\Privileges::TEAM_MANAGE);
        });

        Gate::define('manage-modules', function ($user) {
            return $user->isOwnerOrAdmin() || $user->hasPrivilege(\App\Support\Privileges::MODULES_MANAGE);
        });

        Gate::define('manage-billing', function ($user) {
            return $user->isOwner() || $user->hasPrivilege(\App\Support\Privileges::BILLING_MANAGE);
        });

        Gate::define('manage-settings', function ($user) {
            return $user->isOwnerOrAdmin() || $user->hasPrivilege(\App\Support\Privileges::SETTINGS_MANAGE);
        });

        Gate::define('chat-agent', function ($user) {
            return $user->canActAsChatAgent();
        });

        Gate::define('superadmin', function ($user) {
            return $user->is_superadmin;
        });
    }
}
