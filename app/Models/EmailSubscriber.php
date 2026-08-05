<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailSubscriber extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    public const STATUS_SUBSCRIBED = 'subscribed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_COMPLAINED = 'complained';

    protected $fillable = [
        'tenant_id',
        'email',
        'first_name',
        'last_name',
        'status',
        'unsubscribe_token',
        'meta',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_SUBSCRIBED,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $subscriber) {
            if (blank($subscriber->unsubscribe_token)) {
                $subscriber->unsubscribe_token = (string) Str::uuid();
            }

            if ($subscriber->status === self::STATUS_SUBSCRIBED && blank($subscriber->subscribed_at)) {
                $subscriber->subscribed_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_SUBSCRIBED => 'Subscribed',
            self::STATUS_UNSUBSCRIBED => 'Unsubscribed',
            self::STATUS_BOUNCED => 'Bounced',
            self::STATUS_COMPLAINED => 'Complained',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SUBSCRIBED => 'bg-success',
            self::STATUS_UNSUBSCRIBED => 'bg-secondary',
            self::STATUS_BOUNCED => 'bg-warning text-dark',
            self::STATUS_COMPLAINED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([$this->first_name, $this->last_name]))) ?: $this->email;
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(EmailList::class, 'email_list_subscriber')
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at'])
            ->withTimestamps();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function isSubscribed(): bool
    {
        return $this->status === self::STATUS_SUBSCRIBED;
    }

    public function unsubscribe(): void
    {
        $this->update([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);

        $this->lists()->newPivotStatement()
            ->where('email_subscriber_id', $this->id)
            ->update([
                'status' => self::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ]);
    }
}
