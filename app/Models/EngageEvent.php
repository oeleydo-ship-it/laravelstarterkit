<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngageEvent extends Model
{
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    public const TYPE_IMPRESSION = 'impression';
    public const TYPE_CLICK = 'click';
    public const TYPE_DISMISS = 'dismiss';
    public const TYPE_SUBMIT = 'submit';

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'type',
        'path',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EngageCampaign::class, 'campaign_id');
    }
}
