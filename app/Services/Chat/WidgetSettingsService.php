<?php

namespace App\Services\Chat;

use App\Models\Setting;
use App\Models\Tenant;

/**
 * Per-tenant look and wording of the public chat widget. Stored as one JSON
 * setting rather than a column per field so adding a knob later is a code
 * change only — the widget is the one part of the module a tenant's own
 * customers see, so it gets its own settings surface.
 */
class WidgetSettingsService
{
    public const SETTING_KEY = 'chat_widget_appearance';

    public static function defaults(): array
    {
        return [
            'title' => null, // null → the workspace name
            'greeting' => 'Hi there! How can we help?',
            'launcher_text' => 'Chat',
            'color' => '#0d6efd',
            'offline_message' => "We're offline right now. Leave a message and we'll get back to you.",
            // Ask the visitor who they are before the thread opens. Off by
            // default: a form in front of the composer costs conversations.
            'pre_chat_enabled' => false,
            'pre_chat_message' => 'Tell us who you are so we can get back to you.',
        ];
    }

    public function for(Tenant $tenant): array
    {
        $stored = json_decode((string) Setting::get(self::SETTING_KEY, $tenant->id), true);

        $appearance = array_merge(self::defaults(), is_array($stored) ? $stored : []);
        $appearance['pre_chat_enabled'] = (bool) $appearance['pre_chat_enabled'];

        // Resolved here rather than at save time so renaming the workspace keeps
        // flowing through for tenants who never set an explicit title.
        $appearance['title'] = filled($appearance['title']) ? $appearance['title'] : $tenant->name;

        return $appearance;
    }

    public function save(Tenant $tenant, array $values): array
    {
        $clean = array_intersect_key($values, self::defaults());

        Setting::set(self::SETTING_KEY, $clean, $tenant->id);

        return $this->for($tenant);
    }
}
