<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAppointment extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'tenant_id',
        'booking_site_id',
        'booking_service_id',
        'starts_at',
        'ends_at',
        'guest_name',
        'guest_email',
        'guest_phone',
        'notes',
        'status',
        'client_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SCHEDULED => 'bg-primary',
            self::STATUS_COMPLETED => 'bg-success',
            self::STATUS_CANCELLED => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(BookingSite::class, 'booking_site_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class, 'booking_service_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
