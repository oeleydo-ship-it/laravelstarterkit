<?php

namespace Database\Seeders;

use App\Models\ChatArticle;
use App\Models\ChatCannedResponse;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatVisitor;
use App\Models\Client;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create superadmin (no tenant)
        User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'superadmin@demo.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_superadmin' => true,
                'role' => null,
                'tenant_id' => null,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $plan = Plan::where('key', 'pro')->first();

        // Create demo tenant
        $tenant = Tenant::create([
            'name' => 'Demo Company',
            'slug' => 'demo-company',
            'plan_id' => $plan?->id,
        ]);

        // Bind tenant for scoped operations
        app()->instance('tenant', $tenant);

        // Create owner
        $owner = User::withoutGlobalScopes()->create([
            'name' => 'Demo Owner',
            'email' => 'owner@demo.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create admin
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create member
        $member = User::withoutGlobalScopes()->create([
            'name' => 'Demo Member',
            'email' => 'member@demo.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'member',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Enable modules for tenant
        $modules = Module::all();
        foreach ($modules as $module) {
            TenantModule::create([
                'tenant_id' => $tenant->id,
                'module_key' => $module->key,
                'enabled' => $module->enabled_by_default,
            ]);
        }

        // Create sample clients
        $clients = [
            ['name' => 'Acme Corp', 'email' => 'contact@acme.com', 'phone' => '+1-555-0100', 'notes' => 'Enterprise client'],
            ['name' => 'Globex Inc', 'email' => 'info@globex.com', 'phone' => '+1-555-0200', 'notes' => 'Startup partner'],
            ['name' => 'Wayne Enterprises', 'email' => 'bruce@wayne.com', 'phone' => '+1-555-0300', 'notes' => 'VIP client'],
        ];

        foreach ($clients as $clientData) {
            Client::withoutGlobalScopes()->create(array_merge($clientData, ['tenant_id' => $tenant->id]));
        }

        // Create sample tickets
        $tickets = [
            ['title' => 'Fix login page bug', 'priority' => 'high', 'status' => 'open', 'assigned_to' => $admin->id, 'description' => 'Users cannot login via social auth.'],
            ['title' => 'Update documentation', 'priority' => 'low', 'status' => 'in_progress', 'assigned_to' => $member->id, 'description' => 'API docs need updating for v2.'],
            ['title' => 'Performance optimization', 'priority' => 'medium', 'status' => 'open', 'assigned_to' => null, 'description' => 'Dashboard page loads slowly.'],
        ];

        foreach ($tickets as $ticketData) {
            Ticket::withoutGlobalScopes()->create(array_merge($ticketData, ['tenant_id' => $tenant->id]));
        }

        $this->seedChat($tenant, $admin, $member);
        $this->seedEmailMarketing($tenant);
    }

    /**
     * Sample lists, subscribers, and a template so Email Marketing is usable
     * immediately. The module ships disabled by default; enable it here for demo.
     */
    protected function seedEmailMarketing(Tenant $tenant): void
    {
        TenantModule::updateOrCreate(
            ['tenant_id' => $tenant->id, 'module_key' => 'email'],
            ['enabled' => true],
        );

        app(\App\Services\EmailMarketing\EmailMarketingSettingsService::class)->save($tenant, [
            'from_name' => $tenant->name,
            'from_email' => (string) config('mail.from.address'),
            'reply_to' => (string) config('mail.from.address'),
            'company_name' => $tenant->name,
            'company_address' => '123 Demo Street, Demo City',
            'footer_text' => 'You received this email because you subscribed to '.$tenant->name.'.',
            'track_opens' => true,
            'track_clicks' => true,
            'batch_size' => 100,
            'batch_delay_seconds' => 5,
            'append_compliance_footer' => true,
        ]);

        $list = \App\Models\EmailList::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Newsletter',
            'description' => 'General product updates and announcements.',
        ]);

        $subscribers = [
            ['email' => 'alex@example.com', 'first_name' => 'Alex', 'last_name' => 'Nguyen'],
            ['email' => 'sam@example.com', 'first_name' => 'Sam', 'last_name' => 'Patel'],
            ['email' => 'jordan@example.com', 'first_name' => 'Jordan', 'last_name' => 'Lee'],
        ];

        foreach ($subscribers as $data) {
            $subscriber = \App\Models\EmailSubscriber::withoutGlobalScopes()->create(array_merge($data, [
                'tenant_id' => $tenant->id,
                'status' => \App\Models\EmailSubscriber::STATUS_SUBSCRIBED,
                'subscribed_at' => now(),
            ]));

            $list->subscribers()->attach($subscriber->id, [
                'status' => \App\Models\EmailSubscriber::STATUS_SUBSCRIBED,
                'subscribed_at' => now(),
            ]);
        }

        // Import CRM contacts that already have emails.
        foreach (\App\Models\Client::withoutGlobalScopes()->where('tenant_id', $tenant->id)->whereNotNull('email')->get() as $client) {
            $parts = preg_split('/\s+/', trim((string) $client->name), 2);
            $subscriber = \App\Models\EmailSubscriber::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => strtolower($client->email)],
                [
                    'first_name' => $parts[0] ?? null,
                    'last_name' => $parts[1] ?? null,
                    'status' => \App\Models\EmailSubscriber::STATUS_SUBSCRIBED,
                    'subscribed_at' => now(),
                ]
            );

            $list->subscribers()->syncWithoutDetaching([
                $subscriber->id => [
                    'status' => \App\Models\EmailSubscriber::STATUS_SUBSCRIBED,
                    'subscribed_at' => now(),
                ],
            ]);
        }

        \App\Models\EmailTemplate::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Welcome',
            'subject' => 'Welcome aboard, {{first_name}}!',
            'html_body' => '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;padding:24px;"><h1>Welcome, {{first_name}}!</h1><p>Thanks for joining our list. We will share product news and tips.</p><p><a href="{{unsubscribe_url}}">Unsubscribe</a></p></body></html>',
            'text_body' => "Welcome, {{first_name}}!\n\nThanks for joining our list.\n\nUnsubscribe: {{unsubscribe_url}}",
        ]);
    }

    /**
     * Enough live chat data for the inbox, knowledge base and reports screens to
     * be worth looking at on a fresh install. The chat module ships disabled by
     * default, so it is switched on here explicitly.
     */
    protected function seedChat(Tenant $tenant, User $admin, User $member): void
    {
        TenantModule::updateOrCreate(
            ['tenant_id' => $tenant->id, 'module_key' => 'chat'],
            ['enabled' => true],
        );

        $admin->forceFill(['chat_availability' => 'online'])->save();

        $cannedResponses = [
            ['title' => 'Greeting', 'shortcut' => '/hi', 'body' => "Hi there! Thanks for getting in touch — how can I help today?"],
            ['title' => 'Refund policy', 'shortcut' => '/refund', 'body' => 'Refunds are processed within 5 working days of approval, back to the original payment method.'],
            ['title' => 'Closing', 'shortcut' => '/bye', 'body' => "Glad I could help. I'll close this chat now — reopen it any time by replying."],
        ];

        foreach ($cannedResponses as $canned) {
            ChatCannedResponse::withoutGlobalScopes()->create(
                array_merge($canned, ['tenant_id' => $tenant->id])
            );
        }

        $articles = [
            [
                'title' => 'Refunds and returns',
                'keywords' => 'refund, return, money back, cancel',
                'body' => "You can request a refund within 30 days of purchase.\n\nOnce approved, refunds are processed within 5 working days and returned to the original payment method. Shipping costs are refunded only when the item arrived damaged.",
            ],
            [
                'title' => 'Delivery times',
                'keywords' => 'shipping, delivery, courier, tracking',
                'body' => "Standard delivery is 2–4 working days. Express delivery ordered before 2pm ships the same day.\n\nTracking links are emailed as soon as the courier scans the parcel.",
            ],
            [
                'title' => 'Changing your plan',
                'keywords' => 'billing, upgrade, downgrade, plan, subscription',
                'body' => "Plans can be changed at any time from Billing → Plans.\n\nUpgrades take effect immediately and are charged pro rata. Downgrades take effect at the end of the current billing period.",
            ],
        ];

        foreach ($articles as $article) {
            ChatArticle::withoutGlobalScopes()->create(
                array_merge($article, ['tenant_id' => $tenant->id])
            );
        }

        // Three conversations covering the states the inbox distinguishes:
        // answered and assigned, waiting and unassigned, and resolved.
        $answered = $this->conversation($tenant, 'Priya Raman', 'priya@example.com', $admin->id);
        $this->message($answered, 'visitor', null, 'Hi — how long do refunds usually take?', now()->subMinutes(28));
        $this->message($answered, 'agent', $admin->id, 'Hi Priya! Refunds land within 5 working days of approval.', now()->subMinutes(26));
        $this->message($answered, 'visitor', null, 'Perfect, thank you!', now()->subMinutes(25));
        $answered->update([
            'last_message_at' => now()->subMinutes(25),
            'last_message_preview' => 'Perfect, thank you!',
        ]);

        $waiting = $this->conversation($tenant, null, null, null);
        $this->message($waiting, 'visitor', null, 'My tracking link has not updated in two days. Can someone check?', now()->subMinutes(6));
        $waiting->update([
            'last_message_at' => now()->subMinutes(6),
            'last_message_preview' => 'My tracking link has not updated in two days. Can someone check?',
        ]);

        $resolved = $this->conversation($tenant, 'Tom Beckett', 'tom@example.com', $member->id);
        $this->message($resolved, 'visitor', null, 'Can I move from Pro down to Starter?', now()->subHours(5));
        $this->message($resolved, 'agent', $member->id, 'You can — it takes effect at the end of your current billing period.', now()->subHours(5)->addMinutes(3));
        $this->message($resolved, 'agent', $member->id, 'Customer was worried about losing data on downgrade — reassured.', now()->subHours(5)->addMinutes(4), internal: true);
        $resolved->update([
            'status' => 'closed',
            'closed_at' => now()->subHours(4),
            'last_message_at' => now()->subHours(5)->addMinutes(3),
            'last_message_preview' => 'You can — it takes effect at the end of your current billing period.',
        ]);
    }

    protected function conversation(Tenant $tenant, ?string $name, ?string $email, ?int $assignedTo): ChatConversation
    {
        $visitor = ChatVisitor::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'last_seen_at' => now(),
        ]);

        return ChatConversation::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'assigned_to' => $assignedTo,
            'status' => 'open',
        ]);
    }

    protected function message(
        ChatConversation $conversation,
        string $senderType,
        ?int $senderId,
        string $body,
        $at,
        bool $internal = false,
    ): ChatMessage {
        // Written directly rather than through MessageService: the seeder needs
        // backdated timestamps, and must not broadcast or fire webhooks.
        return ChatMessage::withoutGlobalScopes()->create([
            'tenant_id' => $conversation->tenant_id,
            'chat_conversation_id' => $conversation->id,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'body' => $body,
            'is_internal' => $internal,
            'read_at' => $senderType === 'agent' ? $at : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
