<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatConversation extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'chat_visitor_id',
        'assigned_to',
        'status',
        'closed_at',
        'rating',
        'rating_comment',
        'rated_at',
        'last_message_at',
        'last_message_preview',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
            'rated_at' => 'datetime',
            'rating' => 'integer',
        ];
    }

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    public function scopeRated($query)
    {
        return $query->whereNotNull('rated_at');
    }

    /**
     * A visitor rates a conversation once. Re-rating would let the last click
     * before the tab closes overwrite considered feedback, and would make the
     * reported averages move under a date range that has already been read.
     */
    public function isRated(): bool
    {
        return $this->rated_at !== null;
    }

    public function visitor()
    {
        return $this->belongsTo(ChatVisitor::class, 'chat_visitor_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
