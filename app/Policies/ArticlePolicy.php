<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Article $article): bool
    {
        if ($article->is_accepted) {
            return true;
        }
        
        if ($user) {
            return $user->id === $article->user_id 
                || $user->is_revisor 
                || $user->is_admin;
        }
        
        return false;
    }

    public function create(User $user): bool
    {
        return $user->is_writer || $user->is_admin;
    }

    public function update(User $user, Article $article): bool
    {
        return $user->id === $article->user_id || $user->is_admin;
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->id === $article->user_id || $user->is_admin;
    }
}