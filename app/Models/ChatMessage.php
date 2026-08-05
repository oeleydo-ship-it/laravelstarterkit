<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'chat_conversation_id',
        'sender_type',
        'sender_id',
        'body',
        'is_internal',
        'read_at',
    ];

    protected $attributes = [
        'is_internal' => false,
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'is_internal' => 'boolean',
        ];
    }

    /**
     * Messages the visitor is allowed to see. Every visitor-facing query must go
     * through this — internal notes live in the same table as normal replies.
     */
    public function scopeVisibleToVisitor($query)
    {
        return $query->where('is_internal', false);
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachment()
    {
        return $this->hasOne(ChatAttachment::class, 'chat_message_id');
    }

    public function isFromAgent(): bool
    {
        return $this->sender_type === 'agent';
    }

    public function isFromBot(): bool
    {
        return $this->sender_type === 'bot';
    }

    public function isFromVisitor(): bool
    {
        return $this->sender_type === 'visitor';
    }

    public function displayName(): string
    {
        return match ($this->sender_type) {
            'bot' => 'Assistant',
            'agent' => $this->sender?->name ?? 'Agent',
            default => 'Visitor',
        };
    }
}
