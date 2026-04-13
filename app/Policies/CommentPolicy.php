<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }
        return null;
    }

    // Ktokoľvek prihlásený môže čítať komentáre
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Komentár môže pridať ktokoľvek prihlásený
    public function create(User $user): bool
    {
        return true;
    }

    // Upraviť môže iba autor komentára
    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    // Zmazať môže iba autor komentára
    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }
}
