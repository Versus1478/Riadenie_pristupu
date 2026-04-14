<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;

class AttachmentPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Attachment $attachment): bool
    {
        $note = $this->resolveNote($attachment);

        if (!$note) {
            return false;
        }

        if (in_array($note->status, ['published', 'archived'])) {
            return true;
        }

        return $note->user_id === $user->id;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $note = $this->resolveNote($attachment);

        return $note && $note->user_id === $user->id;
    }

    private function resolveNote(Attachment $attachment): ?Note
    {
        $attachable = $attachment->attachable;

        if (!$attachable) {
            return null;
        }

        if ($attachable instanceof Note) {
            return $attachable;
        }

        if ($attachable instanceof Task) {
            return $attachable->note;
        }

        return null;
    }
}
