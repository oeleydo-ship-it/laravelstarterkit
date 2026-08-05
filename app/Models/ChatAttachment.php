<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChatAttachment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'chat_message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Keep the disk from filling up with orphans when a conversation (and so
        // its messages) is hard-deleted.
        static::deleted(function (ChatAttachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = max((int) $this->size, 0);
        $power = $size > 0 ? (int) floor(log($size, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($size / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    }

    /**
     * What both the agent UI and the public widget are allowed to know about a
     * file: never the disk or the storage path.
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->humanSize(),
            'is_image' => $this->isImage(),
        ];
    }
}
