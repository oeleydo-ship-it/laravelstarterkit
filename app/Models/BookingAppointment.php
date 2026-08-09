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

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

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
        'internal_notes',
        'cancellation_reason',
        'cancelled_at',
        'rescheduled_at',
        'reminder_sent_at',
        'status',
        'payment_status',
        'amount_cents',
        'currency',
        'stripe_checkout_session_id',
        'payment_expires_at',
        'paid_at',
        'confirmation_code',
        'assigned_to',
        'client_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'rescheduled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'payment_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'amount_cents' => 'integer',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_NO_SHOW => 'No show',
            self::STATUS_PENDING_PAYMENT => 'Awaiting payment',
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
            self::STATUS_NO_SHOW => 'bg-danger',
            self::STATUS_PENDING_PAYMENT => 'bg-warning text-dark',
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function manageUrl(): string
    {
        return url('/b/'.$this->site?->public_key.'/manage/'.$this->confirmation_code);
    }
}
