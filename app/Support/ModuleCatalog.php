<?php

namespace App\Support;

class ModuleCatalog
{
    /**
     * Canonical feature modules available to every workspace.
     * Kept here so migrations, seeders, and the Modules UI stay in sync
     * without relying on a one-time db:seed on production.
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => 'clients',
                'name' => 'CRM',
                'description' => 'Store client information, companies, status, tags, and CRM notes.',
                'enabled_by_default' => true,
            ],
            [
                'key' => 'tickets',
                'name' => 'Support Tickets',
                'description' => 'Track and manage support tickets assigned to your team.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'chat',
                'name' => 'Live Chat',
                'description' => 'Realtime live chat between your team and website visitors.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'email',
                'name' => 'Email Marketing',
                'description' => 'Lists, subscribers, templates, and campaigns with open/click tracking.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'engage',
                'name' => 'Engage',
                'description' => 'White-label announcement bars, popups, lead forms, and on-site notifications.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'forms',
                'name' => 'Forms & Surveys',
                'description' => 'Embeddable lead forms, surveys, quizzes, and NPS with CRM/email sync.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'reviews',
                'name' => 'Reviews & Testimonials',
                'description' => 'Collect, moderate, and display reviews with a white-label on-site widget.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'bookings',
                'name' => 'Bookings',
                'description' => 'Services, availability, and public appointment booking for demos and support.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'socialproof',
                'name' => 'Social Proof',
                'description' => 'Recent purchase and subscribe notification toasts — live and fake — with reload display limits.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'autoblog',
                'name' => 'AI Autoblog',
                'description' => 'Generate SEO articles with OpenAI or Kimi K3 and publish to WordPress or REST webhooks.',
                'enabled_by_default' => false,
            ],
        ];
    }

    public static function sync(): int
    {
        $count = 0;

        foreach (self::definitions() as $module) {
            \App\Models\Module::updateOrCreate(
                ['key' => $module['key']],
                $module
            );
            $count++;
        }

        return $count;
    }
}
