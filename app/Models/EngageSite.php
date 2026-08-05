<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EngageSite extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'public_key',
        'name',
        'allowed_origins',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'allowed_origins' => 'array',
            'settings' => 'array',
        ];
    }

    public static function generatePublicKey(): string
    {
        return Str::lower(Str::random(32));
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EngageCampaign::class);
    }

    public function brandColor(): string
    {
        $color = $this->settings['brand_color'] ?? '#2563eb';

        return preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $color) ? $color : '#2563eb';
    }

    public function allowsOrigin(?string $origin): bool
    {
        $allowed = array_values(array_filter($this->allowed_origins ?? []));

        if ($allowed === []) {
            return true;
        }

        if (! $origin) {
            return false;
        }

        $origin = rtrim(strtolower($origin), '/');

        foreach ($allowed as $entry) {
            $entry = rtrim(strtolower((string) $entry), '/');
            if ($entry !== '' && $entry === $origin) {
                return true;
            }
        }

        return false;
    }
}
