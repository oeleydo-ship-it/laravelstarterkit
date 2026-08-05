<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'price_monthly',
        'price_yearly',
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        'limits',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    // ─── Helpers ───

    public function isFree(): bool
    {
        return $this->price_monthly == 0;
    }

    public function getLimit(string $key, $default = null)
    {
        return $this->limits[$key] ?? $default;
    }

    public function monthlySavingsPercent(): float
    {
        if ($this->price_monthly <= 0) {
            return 0;
        }

        $yearlyMonthly = $this->price_yearly / 12;

        return round((($this->price_monthly - $yearlyMonthly) / $this->price_monthly) * 100);
    }
}
