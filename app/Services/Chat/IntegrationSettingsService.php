<?php

namespace App\Services\Chat;

use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Where a workspace wants to be told about chat activity, and where it wants
 * chat events posted. One JSON setting, because these are all the same kind of
 * knob: an optional destination that is off until a URL is filled in.
 */
class IntegrationSettingsService
{
    public const SETTING_KEY = 'chat_integrations';

    public static function defaults(): array
    {
        return [
            'mail_enabled' => false,
            'slack_webhook_url' => null,
            'discord_webhook_url' => null,
            'telegram_bot_token' => null,
            'telegram_chat_id' => null,
            'webhook_url' => null,
            'webhook_secret' => null,
        ];
    }

    public function for(Tenant $tenant): array
    {
        $stored = json_decode((string) Setting::get(self::SETTING_KEY, $tenant->id), true);

        $settings = array_merge(self::defaults(), is_array($stored) ? $stored : []);
        $settings['mail_enabled'] = (bool) $settings['mail_enabled'];

        return $settings;
    }

    public function save(Tenant $tenant, array $values): array
    {
        $settings = array_merge($this->for($tenant), array_intersect_key($values, self::defaults()));

        // A workspace that turns on outbound webhooks without choosing a secret
        // still gets signed deliveries — an unsigned webhook is not verifiable
        // by the receiver, which defeats the point of sending one.
        if (filled($settings['webhook_url']) && blank($settings['webhook_secret'])) {
            $settings['webhook_secret'] = Str::random(40);
        }

        Setting::set(self::SETTING_KEY, $settings, $tenant->id);

        return $this->for($tenant);
    }
}
