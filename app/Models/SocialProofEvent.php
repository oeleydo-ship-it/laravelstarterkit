<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialProofEvent extends Model
{
    use BelongsToTenant;

    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SUBSCRIBE = 'subscribe';

    public const SOURCE_FAKE = 'fake';
    public const SOURCE_LIVE = 'live';
    public const SOURCE_API = 'api';

    protected $fillable = [
        'tenant_id',
        'social_proof_site_id',
        'type',
        'source',
        'customer_name',
        'location',
        'item_name',
        'avatar_url',
        'product_url',
        'is_active',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SUBSCRIBE => 'Subscribe',
        ];
    }

    public static function sources(): array
    {
        return [
            self::SOURCE_FAKE => 'Fake',
            self::SOURCE_LIVE => 'Live',
            self::SOURCE_API => 'API',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SocialProofSite::class, 'social_proof_site_id');
    }

    public function toPublicPayload(array $settings = []): array
    {
        $verb = $this->type === self::TYPE_SUBSCRIBE
            ? ($settings['subscribe_verb'] ?? 'subscribed to')
            : ($settings['purchase_verb'] ?? 'purchased');

        return [
            'id' => 'e'.$this->id,
            'n' => $this->customer_name,
            'l' => $this->location,
            'i' => $this->item_name,
            'v' => $verb,
            't' => $this->type,
            'a' => $this->avatar_url,
            'u' => $this->product_url,
            'at' => optional($this->occurred_at ?? $this->created_at)?->toIso8601String(),
        ];
    }
}
