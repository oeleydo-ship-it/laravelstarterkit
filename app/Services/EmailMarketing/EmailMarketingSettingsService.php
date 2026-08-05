<?php

namespace App\Services\EmailMarketing;

use App\Models\Setting;
use App\Models\Tenant;

class EmailMarketingSettingsService
{
    public const SETTING_KEY = 'email_marketing_settings';

    public function defaults(): array
    {
        return [
            'from_name' => config('app.name', 'SaaS Kit'),
            'from_email' => (string) config('mail.from.address'),
            'reply_to' => (string) config('mail.from.address'),
            'footer_text' => 'You received this email because you subscribed to our list.',
            'company_name' => '',
            'company_address' => '',
            'company_website' => '',
            'track_opens' => true,
            'track_clicks' => true,
            'double_opt_in' => false,
            'batch_size' => 100,
            'batch_delay_seconds' => 5,
            'append_compliance_footer' => true,
        ];
    }

    public function for(?Tenant $tenant = null): array
    {
        $tenant = $tenant ?? (app()->bound('tenant') ? app('tenant') : null);
        $defaults = $this->defaults();

        if (! $tenant) {
            return $defaults;
        }

        $stored = json_decode((string) Setting::get(self::SETTING_KEY, $tenant->id), true);

        if (! is_array($stored)) {
            return $defaults;
        }

        $merged = array_merge($defaults, array_intersect_key($stored, $defaults));
        $merged['track_opens'] = filter_var($merged['track_opens'], FILTER_VALIDATE_BOOLEAN);
        $merged['track_clicks'] = filter_var($merged['track_clicks'], FILTER_VALIDATE_BOOLEAN);
        $merged['double_opt_in'] = filter_var($merged['double_opt_in'], FILTER_VALIDATE_BOOLEAN);
        $merged['append_compliance_footer'] = filter_var($merged['append_compliance_footer'], FILTER_VALIDATE_BOOLEAN);
        $merged['batch_size'] = max(1, min(500, (int) $merged['batch_size']));
        $merged['batch_delay_seconds'] = max(1, min(60, (int) $merged['batch_delay_seconds']));

        return $merged;
    }

    public function save(Tenant $tenant, array $values): array
    {
        $defaults = $this->defaults();
        $clean = array_merge($defaults, array_intersect_key($values, $defaults));

        $clean['track_opens'] = filter_var($clean['track_opens'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $clean['track_clicks'] = filter_var($clean['track_clicks'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $clean['double_opt_in'] = filter_var($clean['double_opt_in'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $clean['append_compliance_footer'] = filter_var($clean['append_compliance_footer'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $clean['batch_size'] = max(1, min(500, (int) ($clean['batch_size'] ?? 100)));
        $clean['batch_delay_seconds'] = max(1, min(60, (int) ($clean['batch_delay_seconds'] ?? 5)));
        $clean['company_website'] = filled($clean['company_website'] ?? null) ? $clean['company_website'] : '';

        Setting::set(self::SETTING_KEY, $clean, $tenant->id);

        return $this->for($tenant);
    }

    public function tabs(): array
    {
        return [
            'sender' => 'Sender',
            'compliance' => 'Compliance',
            'tracking' => 'Tracking',
            'delivery' => 'Delivery',
            'test' => 'Test email',
        ];
    }
}
