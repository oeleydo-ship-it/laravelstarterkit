<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'enabled_by_default',
        'is_active',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'enabled_by_default' => 'boolean',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->where('is_visible', true);
    }
}
