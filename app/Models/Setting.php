<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    // ─── Scopes ───

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id');
    }

    // ─── Helpers ───

    public static function set(string $key, $value, ?int $tenantId = null): self
    {
        return self::updateOrCreate(
            ['key' => $key, 'tenant_id' => $tenantId],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );
    }

    public static function get(string $key, ?int $tenantId = null, $default = null)
    {
        $setting = self::where('key', $key)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when(!$tenantId, fn($q) => $q->whereNull('tenant_id'))
            ->first();

        return $setting ? $setting->value : $default;
    }
}
