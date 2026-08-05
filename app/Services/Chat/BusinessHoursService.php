<?php

namespace App\Services\Chat;

use App\Models\Setting;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * When a workspace is staffed. Drives the widget's online/offline state, so the
 * visitor is told up front whether to expect a live reply.
 */
class BusinessHoursService
{
    public const SETTING_KEY = 'chat_business_hours';

    public const DAYS = [
        'mon' => 'Monday',
        'tue' => 'Tuesday',
        'wed' => 'Wednesday',
        'thu' => 'Thursday',
        'fri' => 'Friday',
        'sat' => 'Saturday',
        'sun' => 'Sunday',
    ];

    public static function defaults(): array
    {
        $days = [];

        foreach (array_keys(self::DAYS) as $day) {
            $days[$day] = [
                'enabled' => ! in_array($day, ['sat', 'sun'], true),
                'start' => '09:00',
                'end' => '17:00',
            ];
        }

        return [
            'enabled' => false,
            'timezone' => 'UTC',
            'days' => $days,
        ];
    }

    public function for(Tenant $tenant): array
    {
        $stored = json_decode((string) Setting::get(self::SETTING_KEY, $tenant->id), true);
        $defaults = self::defaults();

        if (! is_array($stored)) {
            return $defaults;
        }

        $hours = array_merge($defaults, array_intersect_key($stored, $defaults));
        $hours['enabled'] = (bool) ($hours['enabled'] ?? false);
        $hours['timezone'] = $this->safeTimezone($hours['timezone'] ?? null);

        // Merge day by day so a partially stored schedule (or a new day key added
        // in a later release) still yields a complete, usable week.
        foreach (array_keys(self::DAYS) as $day) {
            $stored_day = is_array($stored['days'][$day] ?? null) ? $stored['days'][$day] : [];

            $hours['days'][$day] = [
                'enabled' => (bool) ($stored_day['enabled'] ?? $defaults['days'][$day]['enabled']),
                'start' => $this->safeTime($stored_day['start'] ?? null, $defaults['days'][$day]['start']),
                'end' => $this->safeTime($stored_day['end'] ?? null, $defaults['days'][$day]['end']),
            ];
        }

        return $hours;
    }

    public function save(Tenant $tenant, array $values): array
    {
        Setting::set(self::SETTING_KEY, $values, $tenant->id);

        return $this->for($tenant);
    }

    /**
     * Open when hours are switched off entirely — a workspace that never
     * configured a schedule should not silently look closed to its visitors.
     */
    public function isOpen(Tenant $tenant, ?CarbonInterface $at = null): bool
    {
        $hours = $this->for($tenant);

        if (! $hours['enabled']) {
            return true;
        }

        $now = ($at ? Carbon::instance($at) : Carbon::now())->setTimezone($hours['timezone']);

        $day = $hours['days'][strtolower($now->format('D'))] ?? null;

        if (! $day || ! $day['enabled']) {
            return false;
        }

        $minutes = ((int) $now->format('H')) * 60 + (int) $now->format('i');
        $start = $this->minutes($day['start']);
        $end = $this->minutes($day['end']);

        // An end before the start means the shift runs past midnight.
        return $start <= $end
            ? $minutes >= $start && $minutes < $end
            : $minutes >= $start || $minutes < $end;
    }

    protected function minutes(string $time): int
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hour) * 60 + (int) $minute;
    }

    protected function safeTimezone(?string $timezone): string
    {
        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'UTC';
    }

    protected function safeTime(?string $time, string $fallback): string
    {
        return is_string($time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) ? $time : $fallback;
    }
}
