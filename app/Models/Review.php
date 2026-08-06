<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = ['tenant_id', 'review_site_id', 'rating', 'body', 'author_name', 'author_company', 'author_avatar', 'status', 'source', 'client_id'];
    protected function casts(): array { return ['rating' => 'integer']; }

    public function site(): BelongsTo { return $this->belongsTo(ReviewSite::class, 'review_site_id'); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }

    public function toPublicPayload(): array
    {
        return ['r' => $this->rating, 'b' => $this->body, 'n' => $this->author_name, 'c' => $this->author_company, 'a' => $this->author_avatar];
    }
}
