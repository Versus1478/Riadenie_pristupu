<?php

namespace App\Policies;

use App\Models\Attachment;
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

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        // vlastník attachable záznamu — overíme cez polymorfnú reláciu
        $attachable = $attachment->attachable;

        if (!$attachable) {
            return false;
        }

        // Note aj Task majú user_id cez note
        if (method_exists($attachable, 'getKey')) {
            if (isset($attachable->user_id)) {
                return $attachable->user_id === $user->id;
            }
            // Task nemá priamo user_id — ideme cez note
            if (isset($attachable->note_id)) {
                return $attachable->note?->user_id === $user->id;
            }
        }

        return false;
    }
}
