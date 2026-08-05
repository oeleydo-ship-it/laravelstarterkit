<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id',
        'email_list_id',
        'email_template_id',
        'created_by',
        'name',
        'subject',
        'preview_text',
        'from_name',
        'from_email',
        'reply_to',
        'html_body',
        'text_body',
        'status',
        'scheduled_at',
        'started_at',
        'sent_at',
        'recipients_count',
        'sent_count',
        'failed_count',
        'open_count',
        'click_count',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_SENDING => 'Sending',
            self::STATUS_SENT => 'Sent',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-secondary',
            self::STATUS_SCHEDULED => 'bg-info text-dark',
            self::STATUS_SENDING => 'bg-primary',
            self::STATUS_SENT => 'bg-success',
            self::STATUS_CANCELLED => 'bg-dark',
            self::STATUS_FAILED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(EmailList::class, 'email_list_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED], true);
    }

    public function canSend(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED], true)
            && filled($this->email_list_id)
            && filled($this->subject)
            && filled($this->html_body);
    }

    public function openRate(): float
    {
        if ($this->sent_count === 0) {
            return 0.0;
        }

        return round(($this->open_count / $this->sent_count) * 100, 1);
    }

    public function clickRate(): float
    {
        if ($this->sent_count === 0) {
            return 0.0;
        }

        return round(($this->click_count / $this->sent_count) * 100, 1);
    }
}
