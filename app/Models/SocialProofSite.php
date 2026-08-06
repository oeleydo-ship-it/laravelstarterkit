<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SocialProofSite extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'public_key', 'name', 'allowed_origins', 'settings'];

    protected function casts(): array
    {
        return [
            'allowed_origins' => 'array',
            'settings' => 'array',
        ];
    }

    public static function generatePublicKey(): string
    {
        do {
            $key = Str::upper(Str::random(24));
        } while (static::withoutGlobalScopes()->where('public_key', $key)->exists());

        return $key;
    }

    public static function defaultSettings(): array
    {
        return [
            'enabled' => true,
            'position' => 'bottom-left',
            'initial_delay_ms' => 4000,
            'display_duration_ms' => 5000,
            'interval_ms' => 9000,
            'max_displays' => 5,
            'max_per_page' => 4,
            'include_fake' => true,
            'include_live_subscribers' => true,
            'include_live_bookings' => true,
            'include_api' => true,
            'accent_color' => '#0f766e',
            'purchase_verb' => 'purchased',
            'subscribe_verb' => 'subscribed to',
        ];
    }

    public function resolvedSettings(): array
    {
        return array_merge(self::defaultSettings(), $this->settings ?? []);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SocialProofEvent::class);
    }

    public function allowsOrigin(?string $origin): bool
    {
        $origins = $this->allowed_origins ?? [];

        return empty($origins) || ($origin && in_array($origin, $origins, true));
    }
}
