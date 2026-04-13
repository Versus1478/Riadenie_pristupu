<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/avatar', [AuthController::class, 'uploadAvatar']);
        Route::patch('/change-password', [AuthController::class, 'changePassword']);
        Route::patch('/profile', [AuthController::class, 'updateProfile']);
    });
});

Route::middleware('auth:sanctum')->group(function () {

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);

    Route::middleware('admin')->group(function () {
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
    });

    // Notes — špeciálne routes musia byť PRED apiResource
    Route::prefix('notes')->group(function () {
        Route::get('stats/status',                 [NoteController::class, 'statsByStatus']);
        Route::get('pinned',                       [NoteController::class, 'pinnedNotes']);
        Route::get('recent/{days?}',               [NoteController::class, 'recentNotes']);
        Route::get('search',                       [NoteController::class, 'search']);
        Route::patch('actions/archive-old-drafts', [NoteController::class, 'archiveOldDrafts']);

        Route::patch('{id}/pin',     [NoteController::class, 'pin']);
        Route::patch('{id}/unpin',   [NoteController::class, 'unpin']);
        Route::patch('{id}/archive', [NoteController::class, 'archive']);
        Route::patch('{id}/publish', [NoteController::class, 'publish']);
    });

    // Notes + Tasks CRUD
    Route::apiResource('notes', NoteController::class);
    Route::apiResource('notes.tasks', TaskController::class)->scoped();

    // Komentáre k note
    Route::get( 'notes/{note}/comments', [CommentController::class, 'indexForNote']);
    Route::post('notes/{note}/comments', [CommentController::class, 'storeForNote']);

    // Komentáre k tasku
    Route::get( 'notes/{note}/tasks/{task}/comments', [CommentController::class, 'indexForTask']);
    Route::post('notes/{note}/tasks/{task}/comments', [CommentController::class, 'storeForTask']);

    // Úprava a zmazanie komentára
    Route::patch( 'comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

    // Prílohy k note
    Route::get( 'notes/{note}/attachments', [AttachmentController::class, 'indexForNote']);
    Route::post('notes/{note}/attachments', [AttachmentController::class, 'storeForNote']);

    // Prílohy k tasku
    Route::get( 'notes/{note}/tasks/{task}/attachments', [AttachmentController::class, 'indexForTask']);
    Route::post('notes/{note}/tasks/{task}/attachments', [AttachmentController::class, 'storeForTask']);

    // Zmazanie prílohy
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);

    // Získanie linku na stiahnutie prílohy
    Route::get('attachments/{attachment}/link', [AttachmentController::class, 'link']);

    // User routes
    Route::prefix('users/{user}')->group(function () {
        Route::get('notes',         [NoteController::class, 'userNotesWithCategories']);
        Route::patch('notes/count', [NoteController::class, 'userNoteCount']);
        Route::get('draft-notes',   [NoteController::class, 'userDraftNotes']);
    });

    Route::middleware('verified')->get('/verified-check', function () {
        return 'ok';
    });
});
