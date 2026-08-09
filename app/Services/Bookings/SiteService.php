<?php

namespace App\Services\Bookings;

use App\Models\BookingSite;
use App\Models\Tenant;
use Illuminate\Support\Str;

class SiteService
{
    public function defaultFor(Tenant $tenant): BookingSite
    {
        $site = BookingSite::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->first();

        if ($site) {
            if (blank($site->calendar_token)) {
                $site->update(['calendar_token' => Str::random(48)]);
            }

            return $site;
        }

        return BookingSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => BookingSite::generatePublicKey(),
            'calendar_token' => Str::random(48),
            'name' => 'Bookings',
            'timezone' => config('app.timezone', 'UTC'),
            'allowed_origins' => [],
            'settings' => ['brand_color' => '#0f766e'],
        ]);
    }

    public function rotateKey(BookingSite $site): BookingSite
    {
        $site->update(['public_key' => BookingSite::generatePublicKey()]);

        return $site->fresh();
    }

    public function saveSettings(BookingSite $site, array $data): BookingSite
    {
        $origins = collect(preg_split('/\r\n|\r|\n/', (string) ($data['allowed_origins'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $settings = $site->settings ?? [];
        if (! empty($data['brand_color'])) {
            $settings['brand_color'] = $data['brand_color'];
        }

        $settings['widget_enabled'] = (bool) ($data['widget_enabled'] ?? false);
        $settings['widget_label'] = trim((string) ($data['widget_label'] ?? 'Book a time')) ?: 'Book a time';
        $settings['widget_position'] = in_array($data['widget_position'] ?? '', ['bottom-right', 'bottom-left', 'top-right', 'top-left'], true)
            ? $data['widget_position']
            : 'bottom-right';
        $settings['max_displays'] = max(0, min(1000, (int) ($data['max_displays'] ?? 0)));
        $settings['frequency_hours'] = max(0, min(8760, (int) ($data['frequency_hours'] ?? 24)));
        $settings['minimum_notice_hours'] = max(0, min(720, (int) ($data['minimum_notice_hours'] ?? 1)));
        $settings['maximum_advance_days'] = max(1, min(730, (int) ($data['maximum_advance_days'] ?? 90)));
        $settings['reminders_enabled'] = (bool) ($data['reminders_enabled'] ?? false);
        $settings['reminder_hours'] = max(1, min(168, (int) ($data['reminder_hours'] ?? 24)));

        $site->update([
            'name' => $data['name'] ?? $site->name,
            'timezone' => $data['timezone'] ?? $site->timezone,
            'allowed_origins' => $origins,
            'settings' => $settings,
        ]);

        return $site->fresh();
    }

    public function publicUrl(BookingSite $site): string
    {
        return url('/b/'.$site->public_key);
    }

    public function embedSnippet(BookingSite $site): string
    {
        $url = htmlspecialchars($this->publicUrl($site), ENT_QUOTES, 'UTF-8');

        return '<a href="'.$url.'">Book a time</a>';
    }

    public function widgetSnippet(BookingSite $site): string
    {
        $src = htmlspecialchars(url('/b/'.$site->public_key.'.js'), ENT_QUOTES, 'UTF-8');

        return '<script src="'.$src.'" async></script>';
    }

    public function calendarFeedUrl(BookingSite $site): string
    {
        return url('/calendar/bookings/'.$site->calendar_token.'.ics');
    }
}
