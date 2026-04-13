<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Note;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Throwable;
use DB;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Zoznam príloh pre poznámku
     */
    public function indexForNote(Note $note)
    {
        $this->authorize('viewAny', Attachment::class);

        $attachments = $note->attachments()
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['attachments' => $attachments], Response::HTTP_OK);
    }

    /**
     * Zoznam príloh pre úlohu
     */
    public function indexForTask(Note $note, Task $task)
    {
        $this->authorize('viewAny', Attachment::class);

        if ($task->note_id !== $note->id) {
            return response()->json(['message' => 'Úloha nepatrí tejto poznámke.'], Response::HTTP_NOT_FOUND);
        }

        $attachments = $task->attachments()
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['attachments' => $attachments], Response::HTTP_OK);
    }

    /**
     * Nahratie jednej prílohy k poznámke
     */
    public function storeForNote(Request $request, Note $note)
    {
        $this->authorize('create', Attachment::class);

        $validated = $request->validate([
            'file' => [
                'required',
                File::types(['jpg','jpeg','png','gif','webp','pdf','doc','docx','txt','zip'])
                    ->max('10mb'),
            ],
        ]);

        $attachment = $this->processStore($validated['file'], $note, 'notes/' . $note->id);

        if (!$attachment) {
            return response()->json(['message' => 'Chyba pri ukladaní.'], 500);
        }

        return response()->json([
            'message' => 'Príloha nahraná.',
            'attachment' => $attachment,
            'url' => Storage::disk($attachment->disk)->url($attachment->path)
        ], Response::HTTP_CREATED);
    }

    /**
     * Nahratie jednej prílohy k úlohe
     */
    public function storeForTask(Request $request, Note $note, Task $task)
    {
        $this->authorize('create', Attachment::class);

        if ($task->note_id !== $note->id) {
            return response()->json(['message' => 'Úloha nepatrí tejto poznámke.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'file' => ['required', File::types(['jpg','jpeg','png','pdf'])->max('10mb')],
        ]);

        $attachment = $this->processStore($validated['file'], $task, 'tasks/' . $task->id);

        if (!$attachment) {
            return response()->json(['message' => 'Chyba pri ukladaní.'], 500);
        }

        return response()->json(['attachment' => $attachment], Response::HTTP_CREATED);
    }

    /**
     * Vymazanie prílohy
     */
    public function destroy(Attachment $attachment)
    {
        $this->authorize('delete', $attachment);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->json(['message' => 'Príloha odstránená.'], Response::HTTP_OK);
    }

    /**
     * Vygenerovanie dočasného linku na stiahnutie (ak je disk private)
     */
    public function link(Attachment $attachment)
    {
        $this->authorize('viewAny', Attachment::class);

        $expiresAt = now()->addMinutes(5);
        $url = Storage::disk($attachment->disk)->temporaryUrl($attachment->path, $expiresAt);

        return response()->json([
            'url' => $url,
            'expires_at' => $expiresAt->toIso8601String(),
        ], Response::HTTP_OK);
    }

    /**
     * Pomocná funkcia pre spracovanie súboru a DB zápis
     */
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
            if ($path) Storage::disk($disk)->delete($path);
            return null;
        }
    }
}
