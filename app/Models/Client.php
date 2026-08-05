<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, BelongsToTenant, LogsActivity;

    public const STATUS_LEAD = 'lead';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_CHURNED = 'churned';

    protected $fillable = [
        'tenant_id',
        'name',
        'company',
        'email',
        'phone',
        'status',
        'tags',
        'website',
        'source',
        'address',
        'city',
        'country',
        'notes',
    ];

    protected $attributes = [
        'status' => self::STATUS_LEAD,
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_LEAD => 'Lead',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_CHURNED => 'Churned',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'bg-success',
            self::STATUS_LEAD => 'bg-info text-dark',
            self::STATUS_INACTIVE => 'bg-secondary',
            self::STATUS_CHURNED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function crmNotes()
    {
        return $this->hasMany(ClientNote::class)->latest();
    }

    public function tagList(): array
    {
        return array_values(array_filter(array_map('trim', $this->tags ?? [])));
    }
}
