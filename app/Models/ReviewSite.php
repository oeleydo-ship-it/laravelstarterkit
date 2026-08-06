<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReviewSite extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'public_key', 'name', 'allowed_origins', 'settings'];

    protected function casts(): array
    {
        return ['allowed_origins' => 'array', 'settings' => 'array'];
    }

    public static function generatePublicKey(): string
    {
        do {
            $key = Str::upper(Str::random(24));
        } while (static::withoutGlobalScopes()->where('public_key', $key)->exists());

        return $key;
    }

    public function reviews(): HasMany { return $this->hasMany(Review::class); }
    public function widgets(): HasMany { return $this->hasMany(ReviewWidget::class); }

    public function allowsOrigin(?string $origin): bool
    {
        $origins = $this->allowed_origins ?? [];
        return empty($origins) || ($origin && in_array($origin, $origins, true));
    }
}
