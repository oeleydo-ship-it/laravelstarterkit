<?php

namespace App\Models;

use App\Support\LikeSearch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChatDocument extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size',
        'extracted_text',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ChatDocument $document) {
            if ($document->path) {
                Storage::disk($document->disk)->delete($document->path);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        $needle = LikeSearch::pattern($term);

        return $query->where(function ($q) use ($needle) {
            $q->whereRaw(LikeSearch::clause('title'), [$needle])
                ->orWhereRaw(LikeSearch::clause('original_name'), [$needle])
                ->orWhereRaw(LikeSearch::clause('extracted_text'), [$needle]);
        });
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }

    public function excerpt(int $limit = 600): string
    {
        return (string) str($this->extracted_text ?? '')->limit($limit);
    }
}
