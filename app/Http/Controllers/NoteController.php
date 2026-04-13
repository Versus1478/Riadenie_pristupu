<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class NoteController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $this->authorize('viewAny', Note::class);

        $notes = Note::query()
            ->select(['id', 'user_id', 'title', 'body', 'status', 'is_pinned', 'created_at'])
            ->with(['user:id,first_name,last_name', 'categories:id,name,color'])
            ->whereIn('status', ['published', 'archived'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(5);

        return response()->json(['notes' => $notes], Response::HTTP_OK);
    }

    public function myNotes(Request $request)
    {
        $this->authorize('viewAny', Note::class);

        $notes = $request->user()
            ->notes()
            ->select(['id', 'user_id', 'title', 'body', 'status', 'is_pinned', 'created_at'])
            ->with(['categories:id,name,color'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(5);

        return response()->json(['notes' => $notes], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Note::class);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'min:3', 'max:255'],
            'body'         => ['nullable', 'string'],
            'status'       => ['sometimes', 'required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'is_pinned'    => ['sometimes', 'boolean'],
            'categories'   => ['sometimes', 'array', 'max:3'],
            'categories.*' => ['integer', 'distinct', 'exists:categories,id'],
        ]);

        $note = $request->user()->notes()->create([
            'title'     => $validated['title'],
            'body'      => $validated['body'] ?? null,
            'status'    => $validated['status'] ?? 'draft',
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        if (!empty($validated['categories'])) {
            $note->categories()->sync($validated['categories']);
        }

        return response()->json([
            'message' => 'Poznámka bola úspešne vytvorená.',
            'note'    => $note->load(['user:id,first_name,last_name', 'categories:id,name,color']),
        ], Response::HTTP_CREATED);
    }

    // Namiesto (string $id) použi (Note $note)
    public function show(Note $note)
    {
        // Ak si tu, Note už existuje (Laravel ho našiel podľa ID v URL)
        // Ak ho nenašiel, hodil by 404 automaticky.

        $note->load([
            'user:id,first_name,last_name',
            'categories:id,name,color',
            'comments.user:id,first_name,last_name',
            'tasks.comments.user:id,first_name,last_name',
        ]);

        $this->authorize('view', $note);

        return response()->json(['note' => $note], Response::HTTP_OK);
    }

    public function update(Request $request, string $id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $note);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'body'         => ['nullable', 'string'],
            'status'       => ['sometimes', 'required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'is_pinned'    => ['sometimes', 'boolean'],
            'categories'   => ['sometimes', 'array'],
            'categories.*' => ['integer', 'distinct', 'exists:categories,id'],
        ]);

        $note->update($validated);

        if (array_key_exists('categories', $validated)) {
            $note->categories()->sync($validated['categories']);
        }

        return response()->json([
            'message' => 'Poznámka bola aktualizovaná.',
            'note'    => $note->load(['user:id,first_name,last_name', 'categories:id,name,color']),
        ], Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('delete', $note);

        $note->delete();

        return response()->json(['message' => 'Poznámka bola úspešne odstránená.'], Response::HTTP_OK);
    }

    // --- Vlastné akcie ---

    public function pin(string $id)
    {
        $note = Note::find($id);
        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('pin', $note);

        $note->pin();
        return response()->json(['message' => 'Poznámka bola pripnutá.', 'note' => $note]);
    }

    public function unpin(string $id)
    {
        $note = Note::find($id);
        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('pin', $note); // rovnaké oprávnenie ako pin

        $note->unpin();
        return response()->json(['message' => 'Poznámka bola odopnutá.', 'note' => $note]);
    }

    public function archive(string $id)
    {
        $note = Note::find($id);
        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('archive', $note);

        $note->archive();
        return response()->json(['message' => 'Poznámka bola archivovaná.', 'note' => $note], Response::HTTP_OK);
    }

    public function publish(string $id)
    {
        $note = Note::find($id);
        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('publish', $note);

        $note->publish();
        return response()->json(['message' => 'Poznámka bola publikovaná.', 'note' => $note], Response::HTTP_OK);
    }

    // --- Štatistiky / hromadné akcie (len admin cez before()) ---

    public function statsByStatus()
    {
        $this->authorize('viewAny', Note::class);

        $stats = Note::whereNull('deleted_at')
            ->groupBy('status')
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->orderBy('status')
            ->get();

        return response()->json(['stats' => $stats], Response::HTTP_OK);
    }

    public function archiveOldDrafts()
    {
        $this->authorize('create', Note::class); // len admin prejde cez before()

        $affected = Note::query()
            ->draft()
            ->where('updated_at', '<', now()->subDays(30))
            ->update(['status' => 'archived', 'updated_at' => now()]);

        return response()->json([
            'message'       => 'Staré koncepty boli archivované.',
            'affected_rows' => $affected,
        ]);
    }

    public function userNotesWithCategories(string $userId)
    {
        $this->authorize('viewAny', Note::class);

        $notes = Note::with('categories')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($note) => [
                'id'         => $note->id,
                'title'      => $note->title,
                'categories' => $note->categories->pluck('name'),
            ]);

        return response()->json(['notes' => $notes], Response::HTTP_OK);
    }

    public function search(Request $request)
    {
        $this->authorize('viewAny', Note::class);

        $q     = trim((string) $request->query('q', ''));
        $notes = Note::searchPublished($q);

        return response()->json(['query' => $q, 'notes' => $notes], Response::HTTP_OK);
    }

    public function userNoteCount(string $userId)
    {
        $this->authorize('viewAny', Note::class);

        $count = Note::countByUser($userId);
        return response()->json(['note_count' => $count, 'user_id' => $userId], Response::HTTP_OK);
    }

    public function pinnedNotes()
    {
        $this->authorize('viewAny', Note::class);

        $notes = Note::pinned()->orderBy('updated_at', 'desc')->get();
        return response()->json(['notes' => $notes], Response::HTTP_OK);
    }

    public function recentNotes(int $days = 7)
    {
        $this->authorize('viewAny', Note::class);

        $notes = Note::recent($days)->orderBy('updated_at', 'desc')->get();
        return response()->json(['notes' => $notes], Response::HTTP_OK);
    }

    public function userDraftNotes(string $userId)
    {
        $this->authorize('viewAny', Note::class);

        $notes = Note::user($userId)->draft()->orderBy('updated_at', 'desc')->get();
        return response()->json(['notes' => $notes], Response::HTTP_OK);
    }
}
