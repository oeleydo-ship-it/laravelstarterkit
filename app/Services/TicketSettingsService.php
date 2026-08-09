<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Tenant;

class TicketSettingsService
{
    public const KEY = 'tickets.workflow';

    public function for(Tenant $tenant): array
    {
        $saved = json_decode((string) Setting::get(self::KEY, $tenant->id, '{}'), true) ?: [];

        return array_merge([
            'default_priority' => 'medium',
            'default_assignee_id' => null,
            'ai_creation_enabled' => false,
            'ai_auto_create_on_close' => false,
        ], $saved);
    }

    public function save(Tenant $tenant, array $settings): void
    {
        Setting::set(self::KEY, $settings, $tenant->id);
    }
}
