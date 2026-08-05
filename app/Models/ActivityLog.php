<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    // ─── Helpers ───

    public function getDescriptionAttribute(): string
    {
        $model = class_basename($this->subject_type ?? 'Item');
        $name = $this->meta['name'] ?? '';
        $action = ucfirst($this->action);

        return "{$action} {$model}" . ($name ? ": {$name}" : '');
    }
}
