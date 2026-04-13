<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Task;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function index(Note $note)
    {
        // Route model binding — $note je automaticky nájdená
        $this->authorize('view', [Task::class, $note]);

        $tasks = $note->tasks()->orderBy('created_at')->get();

        return response()->json(['tasks' => $tasks], Response::HTTP_OK);
    }

    public function store(Request $request, string $noteId)
    {
        $note = Note::find($noteId);

        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('create', [Task::class, $note]);

        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'is_done' => ['sometimes', 'boolean'],
            'due_at'  => ['nullable', 'date'],
        ]);

        $task = $note->tasks()->create($validated);

        return response()->json([
            'message' => 'Úloha bola vytvorená.',
            'task'    => $task,
        ], Response::HTTP_CREATED);
    }

    public function show(string $noteId, string $taskId)
    {
        $note = Note::find($noteId);

        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('view', [Task::class, $note]);

        $task = $note->tasks()
            ->with('comments.user:id,first_name,last_name')
            ->where('id', $taskId)
            ->first();

        if (!$task) {
            return response()->json(['message' => 'Úloha nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['task' => $task], Response::HTTP_OK);
    }

    public function update(Request $request, int $noteId, int $taskId)
    {
        $note = Note::find($noteId);

        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', [Task::class, $note]);

        $task = $note->tasks()->where('id', $taskId)->first();

        if (!$task) {
            return response()->json(['message' => 'Úloha nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'is_done' => ['sometimes', 'boolean'],
            'due_at'  => ['nullable', 'date'],
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'Úloha bola úspešne aktualizovaná.',
            'task'    => $task->fresh(),
        ], Response::HTTP_OK);
    }

    public function destroy(string $noteId, string $taskId)
    {
        $note = Note::find($noteId);

        if (!$note) {
            return response()->json(['message' => 'Poznámka nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('delete', [Task::class, $note]);

        $task = $note->tasks()->where('id', $taskId)->first();

        if (!$task) {
            return response()->json(['message' => 'Úloha nenájdená.'], Response::HTTP_NOT_FOUND);
        }

        $task->delete();

        return response()->json(['message' => 'Úloha bola odstránená.'], Response::HTTP_OK);
    }
}
