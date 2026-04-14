<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Note;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Throwable;

class AttachmentController extends Controller
{
    public function indexForNote(Note $note)
    {
        $this->authorize('view', $note);

        $attachments = $note->attachments()->orderByDesc('created_at')->get();

        return response()->json(['attachments' => $attachments], Response::HTTP_OK);
    }

    public function indexForTask(Note $note, Task $task)
    {
        $this->authorize('view', $note);

        if ($task->note_id !== $note->id) {
            return response()->json(['message' => 'Úloha nepatrí tejto poznámke.'], Response::HTTP_NOT_FOUND);
        }

        $attachments = $task->attachments()->orderByDesc('created_at')->get();

        return response()->json(['attachments' => $attachments], Response::HTTP_OK);
    }

    public function storeForNote(Request $request, Note $note)
    {
        $this->authorize('createAttachment', $note);

        $validated = $request->validate([
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt', 'zip'])
                    ->max('10mb'),
            ],
        ]);

        $attachment = $this->processStore($validated['file'], $note, 'notes/' . $note->id);

        if (!$attachment) {
            return response()->json(['message' => 'Chyba pri ukladaní.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message'    => 'Príloha nahraná.',
            'attachment' => $attachment,
            'url'        => Storage::disk($attachment->disk)->url($attachment->path),
        ], Response::HTTP_CREATED);
    }

    public function storeForTask(Request $request, Note $note, Task $task)
    {
        $this->authorize('createAttachment', $note);

        if ($task->note_id !== $note->id) {
            return response()->json(['message' => 'Úloha nepatrí tejto poznámke.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt', 'zip'])
                    ->max('10mb'),
            ],
        ]);

        $attachment = $this->processStore($validated['file'], $task, 'tasks/' . $task->id);

        if (!$attachment) {
            return response()->json(['message' => 'Chyba pri ukladaní.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['attachment' => $attachment], Response::HTTP_CREATED);
    }

    public function destroy(Attachment $attachment)
    {
        $this->authorize('delete', $attachment);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->json(['message' => 'Príloha odstránená.'], Response::HTTP_OK);
    }

    public function link(Attachment $attachment)
    {
        $this->authorize('view', $attachment);

        $expiresAt = now()->addMinutes(5);
        $url = Storage::disk($attachment->disk)->url($attachment->path);

        return response()->json([
            'url'        => $url,
            'expires_at' => $expiresAt->toIso8601String(),
        ], Response::HTTP_OK);
    }

    private function processStore($file, $model, string $directory): ?Attachment
    {
        $disk = 'public';
        $path = null;

        try {
            DB::beginTransaction();

            $path = $file->store($directory, $disk);

            $attachment = $model->attachments()->create([
                'public_id'     => (string) Str::ulid(),
                'collection'    => 'attachment',
                'visibility'    => 'public',
                'disk'          => $disk,
                'path'          => $path,
                'stored_name'   => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);

            DB::commit();

            return $attachment;
        } catch (Throwable $e) {
            DB::rollBack();

            if ($path) {
                Storage::disk($disk)->delete($path);
            }

            return null;
        }
    }
}
