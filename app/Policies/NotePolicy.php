<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Note $note): bool
    {
        if (in_array($note->status, ['published', 'archived'])) {
            return true;
        }

        return $note->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function restore(User $user, Note $note): bool
    {
        return false;
    }

    public function forceDelete(User $user, Note $note): bool
    {
        return false;
    }


    public function pin(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function archive(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function publish(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function createAttachment(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }
}
