<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChatVisitor extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'token',
        'client_id',
        'name',
        'email',
        'phone',
        'company',
        'crm_notes',
        'ip_address',
        'user_agent',
        'location',
        'country',
        'city',
        'current_page',
        'page_title',
        'page_visits',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'page_visits' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChatVisitor $visitor) {
            if (! $visitor->token) {
                $visitor->token = (string) Str::uuid();
            }
        });
    }

    public function conversations()
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function displayName(): string
    {
        return $this->name
            ?: $this->email
            ?: 'Visitor #'.$this->id;
    }

    public function displayLocation(): ?string
    {
        if (filled($this->location)) {
            return $this->location;
        }

        $parts = array_values(array_filter([$this->city, $this->country]));

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Record the page the visitor is on and keep a short recent history.
     */
    public function recordPageVisit(?string $url, ?string $title = null): void
    {
        if (! filled($url)) {
            return;
        }

        $url = Str::limit($url, 2000, '');
        $title = filled($title) ? Str::limit($title, 255, '') : null;

        $visits = collect($this->page_visits ?? []);
        $last = $visits->first();

        if (! is_array($last) || ($last['url'] ?? null) !== $url) {
            $visits->prepend([
                'url' => $url,
                'title' => $title,
                'visited_at' => now()->toIso8601String(),
            ]);
        } elseif ($title && empty($last['title'])) {
            $visits[0]['title'] = $title;
        }

        $this->forceFill([
            'current_page' => $url,
            'page_title' => $title ?? $this->page_title,
            'page_visits' => $visits->take(25)->values()->all(),
        ])->save();
    }
}
