<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewWidget extends Model
{
    use BelongsToTenant;

    public const LAYOUT_STACKED = 'stacked';
    public const LAYOUT_CAROUSEL = 'carousel';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_LIVE = 'live';

    protected $fillable = ['tenant_id', 'review_site_id', 'name', 'layout', 'min_rating', 'max_items', 'style', 'status'];
    protected function casts(): array { return ['style' => 'array', 'min_rating' => 'integer', 'max_items' => 'integer']; }

    public function site(): BelongsTo { return $this->belongsTo(ReviewSite::class, 'review_site_id'); }
    public function isLive(): bool { return $this->status === self::STATUS_LIVE; }
}
