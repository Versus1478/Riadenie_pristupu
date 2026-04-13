<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }
        return null;
    }

    /**
     * TaskPolicy prijíma Note ako druhý parameter, pretože tasky
     * nemajú zmysel bez kontextu poznámky (oprávnenie = oprávnenie na note).
     */
    public function view(User $user, Note $note): bool
    {
        if ($note->status === 'published' || $note->status === 'archived') {
            return true;
        }
        return $note->user_id === $user->id;
    }

    public function create(User $user, Note $note): bool
    {
        // task môže pridať iba vlastník poznámky
        return $note->user_id === $user->id;
    }

    public function update(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
