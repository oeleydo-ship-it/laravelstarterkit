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
    ];

    protected function casts(): array
    {
        return [
            'enabled_by_default' => 'boolean',
        ];
    }
}
