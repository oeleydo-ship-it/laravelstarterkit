<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'plan_id',
        'stripe_customer_id',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function tenantModules()
    {
        return $this->hasMany(TenantModule::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function invites()
    {
        return $this->hasMany(Invite::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function chatVisitors()
    {
        return $this->hasMany(ChatVisitor::class);
    }

    public function chatConversations()
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function chatArticles()
    {
        return $this->hasMany(ChatArticle::class);
    }

    public function chatDocuments()
    {
        return $this->hasMany(ChatDocument::class);
    }

    public function chatCannedResponses()
    {
        return $this->hasMany(ChatCannedResponse::class);
    }

    public function chatApiTokens()
    {
        return $this->hasMany(ChatApiToken::class);
    }

    public function autoblogPosts() { return $this->hasMany(AutoblogPost::class); }
    public function autoblogDestinations() { return $this->hasMany(AutoblogDestination::class); }

    // ─── Helpers ───

    public function isModuleEnabled(string $moduleKey): bool
    {
        return $this->tenantModules()
            ->where('module_key', $moduleKey)
            ->where('enabled', true)
            ->exists();
    }

    public function getPlanLimit(string $key, $default = null)
    {
        if (!$this->plan) {
            return $default;
        }

        $limits = $this->plan->limits;

        return $limits[$key] ?? $default;
    }

    public function activeUserCount(): int
    {
        return $this->users()->where('status', 'active')->count();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }
}
