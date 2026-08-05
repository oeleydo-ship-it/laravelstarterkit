<?php

namespace App\Policies;

use App\Models\ChatArticle;
use App\Models\User;

class ChatArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ChatArticle $article): bool
    {
        return $user->tenant_id === $article->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwnerOrAdmin();
    }

    public function update(User $user, ChatArticle $article): bool
    {
        return $user->tenant_id === $article->tenant_id && $user->isOwnerOrAdmin();
    }

    public function delete(User $user, ChatArticle $article): bool
    {
        return $user->tenant_id === $article->tenant_id && $user->isOwnerOrAdmin();
    }
}
