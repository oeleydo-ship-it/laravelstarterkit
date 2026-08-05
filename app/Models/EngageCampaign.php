<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EngageCampaign extends Model
{
    use BelongsToTenant, HasFactory;

    public const TYPE_BAR = 'bar';
    public const TYPE_POPUP = 'popup';
    public const TYPE_SLIDE_IN = 'slide_in';
    public const TYPE_FORM = 'form';
    public const TYPE_TOAST = 'toast';
    public const TYPE_LAUNCHER = 'launcher';
    public const TYPE_VIDEO = 'video';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_LIVE = 'live';
    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'tenant_id',
        'engage_site_id',
        'name',
        'type',
        'status',
        'priority',
        'content',
        'targeting',
        'style',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'priority' => 0,
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'targeting' => 'array',
            'style' => 'array',
            'priority' => 'integer',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_BAR => 'Announcement bar',
            self::TYPE_POPUP => 'Popup',
            self::TYPE_SLIDE_IN => 'Slide-in',
            self::TYPE_FORM => 'Lead form',
            self::TYPE_TOAST => 'Notification toast',
            self::TYPE_LAUNCHER => 'Launcher',
            self::TYPE_VIDEO => 'Video popup',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_LIVE => 'Live',
            self::STATUS_PAUSED => 'Paused',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? ucfirst((string) $this->type);
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_LIVE => 'bg-success',
            self::STATUS_PAUSED => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(EngageSite::class, 'engage_site_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EngageEvent::class, 'campaign_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(EngageLead::class, 'campaign_id');
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    /**
     * Compact payload for the public runtime (no tenant/slug/vendor fields).
     */
    public function toPublicPayload(): array
    {
        return [
            'id' => $this->id,
            't' => $this->type,
            'p' => (int) $this->priority,
            'c' => $this->content ?? [],
            'g' => $this->targeting ?? [],
            's' => $this->style ?? [],
        ];
    }
}
