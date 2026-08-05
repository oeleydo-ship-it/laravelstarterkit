<?php

namespace App\Models;

use App\Support\LikeSearch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatArticle extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'keywords',
        'body',
        'is_published',
    ];

    protected $attributes = [
        'is_published' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Deliberately a LIKE search rather than a full-text index: the starter kit
     * has to run on SQLite, MySQL and Postgres unchanged, and a workspace's
     * knowledge base is small enough that this stays cheap.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        $needle = LikeSearch::pattern($term);

        return $query->where(function ($q) use ($needle) {
            $q->whereRaw(LikeSearch::clause('title'), [$needle])
                ->orWhereRaw(LikeSearch::clause('keywords'), [$needle])
                ->orWhereRaw(LikeSearch::clause('body'), [$needle]);
        });
    }
}
