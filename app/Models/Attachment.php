<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'public_id',
        'collection',
        'visibility',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'size',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function publicUrl(): ?string
    {
        if ($this->visibility !== 'public') {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
