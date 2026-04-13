<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index(Note $note)
    {
        $this->authorize('view', [Task::class, $note]);

        $tasks = $note->tasks()->orderBy('created_at')->get();

        return response()->json(['tasks' => $tasks], Response::HTTP_OK);
    }

    public function store(Request $request, Note $note)
    {
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

    public function show(Note $note, Task $task)
    {
        $this->authorize('view', [Task::class, $note]);

        $task->load('comments.user:id,first_name,last_name');

        return response()->json(['task' => $task], Response::HTTP_OK);
    }

    public function update(Request $request, Note $note, Task $task)
    {
        $this->authorize('update', [Task::class, $note]);

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

    public function destroy(Note $note, Task $task)
    {
        $this->authorize('delete', [Task::class, $note]);

        $task->delete();

        return response()->json(['message' => 'Úloha bola odstránená.'], Response::HTTP_OK);
    }
}
