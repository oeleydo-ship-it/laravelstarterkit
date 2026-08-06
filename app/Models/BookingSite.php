<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BookingSite extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'public_key',
        'name',
        'timezone',
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

    public function services(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }

    public function availability(): HasMany
    {
        return $this->hasMany(BookingAvailability::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(BookingException::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(BookingAppointment::class);
    }

    public function brandColor(): string
    {
        $color = $this->settings['brand_color'] ?? '#0f766e';

        return preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $color) ? $color : '#0f766e';
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
