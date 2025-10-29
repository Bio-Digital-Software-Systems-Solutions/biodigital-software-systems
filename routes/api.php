<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\TusUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// TUS protocol file upload endpoints (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::any('/files/{fileId?}', TusUploadController::class)->where('fileId', '.*');
    Route::get('/files/{fileId}/metadata', [TusUploadController::class, 'metadata']);
});

// Public API for trainings list (used by landing page)
Route::get('/trainings', [App\Http\Controllers\TrainingController::class, 'index'])
    ->name('api.trainings.index');

Route::middleware('auth:sanctum')->group(function () {
    // Projects API
    Route::apiResource('projects', ProjectController::class)->names([
        'index' => 'api.projects.index',
        'store' => 'api.projects.store',
        'show' => 'api.projects.show',
        'update' => 'api.projects.update',
        'destroy' => 'api.projects.destroy',
    ]);
    Route::get('projects/{project}/tasks', [ProjectController::class, 'tasks'])->name('api.projects.tasks');
    Route::patch('projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('api.projects.updateStatus');

    // Project participants
    Route::post('projects/{project}/participants', [ProjectController::class, 'addParticipant'])->name('api.projects.addParticipant');
    Route::delete('projects/{project}/participants/{participant}', [ProjectController::class, 'removeParticipant'])->name('api.projects.removeParticipant');

    // Project comments
    Route::post('projects/{project}/comments', [ProjectController::class, 'storeComment'])->name('api.projects.storeComment');
    Route::delete('projects/{project}/comments/{comment}', [ProjectController::class, 'deleteComment'])->name('api.projects.deleteComment');

    // Project attachments
    Route::post('projects/{project}/attachments', [ProjectController::class, 'uploadAttachment'])->name('api.projects.uploadAttachment');
    Route::delete('projects/{project}/attachments/{attachment}', [ProjectController::class, 'deleteAttachment'])->name('api.projects.deleteAttachment');

    // Tasks API (unified Task model)
    Route::apiResource('tasks', TaskController::class)->names([
        'index' => 'api.tasks.index',
        'store' => 'api.tasks.store',
        'show' => 'api.tasks.show',
        'update' => 'api.tasks.update',
        'destroy' => 'api.tasks.destroy',
    ]);
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('api.tasks.updateStatus');

    // Task comments
    Route::post('tasks/{task}/comments', [TaskController::class, 'storeComment'])->name('api.tasks.storeComment');
    Route::delete('tasks/{task}/comments/{comment}', [TaskController::class, 'deleteComment'])->name('api.tasks.deleteComment');

    // Task participants
    Route::post('tasks/{task}/participants', [TaskController::class, 'addParticipant'])->name('api.tasks.addParticipant');
    Route::delete('tasks/{task}/participants/{user}', [TaskController::class, 'removeParticipant'])->name('api.tasks.removeParticipant');

    // Task attachments
    Route::post('tasks/{task}/attachments', [TaskController::class, 'uploadAttachment'])->name('api.tasks.uploadAttachment');
    Route::delete('tasks/{task}/attachments/{attachment}', [TaskController::class, 'deleteAttachment'])->name('api.tasks.deleteAttachment');
});
