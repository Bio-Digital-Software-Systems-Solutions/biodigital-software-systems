<?php

use App\Models\Status;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskParticipant;
use App\Models\User;
use App\Services\TaskActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create permissions
    Permission::firstOrCreate(['name' => 'view tasks']);
    Permission::firstOrCreate(['name' => 'create tasks']);
    Permission::firstOrCreate(['name' => 'edit tasks']);
    Permission::firstOrCreate(['name' => 'delete tasks']);
    Permission::firstOrCreate(['name' => 'delete attachments']);

    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $adminRole->syncPermissions([
        'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'delete attachments',
    ]);

    // Create statuses using factory
    Status::factory()->pending()->create();
    Status::factory()->inProgress()->create();
    Status::factory()->completed()->create();

    // Create fake storage
    Storage::fake('public');
});

// ==========================================
// Status Change Activity Tests
// ==========================================

it('logs activity when task status is changed', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $pendingStatus = Status::where('name', 'pending')->first();
    $inProgressStatus = Status::where('name', 'in_progress')->first();

    $task = Task::factory()->create([
        'status_id' => $pendingStatus->id,
    ]);

    $response = $this->actingAs($user)
        ->patchJson("/api/tasks/{$task->uuid}/status", [
            'status_id' => $inProgressStatus->id,
        ]);

    $response->assertOk();

    // Check that activity was logged
    $activity = Activity::where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('event', 'status_changed')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toContain('Statut changé');
    expect($activity->properties['old_status_name'])->toBe('pending');
    expect($activity->properties['new_status_name'])->toBe('in_progress');
    expect($activity->causer_id)->toBe($user->id);
});

it('does not log activity when status is unchanged', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $pendingStatus = Status::where('name', 'pending')->first();

    $task = Task::factory()->create([
        'status_id' => $pendingStatus->id,
    ]);

    // Clear any existing activities
    Activity::where('subject_type', Task::class)->where('subject_id', $task->id)->delete();

    $response = $this->actingAs($user)
        ->patchJson("/api/tasks/{$task->uuid}/status", [
            'status_id' => $pendingStatus->id, // Same status
        ]);

    $response->assertOk();

    // Check that no status_changed activity was logged
    $activity = Activity::where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('event', 'status_changed')
        ->first();

    expect($activity)->toBeNull();
});

// ==========================================
// Progress Change Activity Tests
// ==========================================

it('logs activity when task progress is changed', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $task = Task::factory()->create([
        'progress' => 25,
    ]);

    $response = $this->actingAs($user)
        ->patchJson("/api/tasks/{$task->uuid}/progress", [
            'progress' => 75,
        ]);

    $response->assertOk();

    // Check that activity was logged
    $activity = Activity::where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('event', 'progress_updated')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toContain('Progression mise à jour');
    expect($activity->properties['old_progress'])->toBe(25);
    expect($activity->properties['new_progress'])->toBe(75);
    expect($activity->causer_id)->toBe($user->id);
});

it('does not log activity when progress is unchanged', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $task = Task::factory()->create([
        'progress' => 50,
    ]);

    // Clear any existing activities
    Activity::where('subject_type', Task::class)->where('subject_id', $task->id)->delete();

    $response = $this->actingAs($user)
        ->patchJson("/api/tasks/{$task->uuid}/progress", [
            'progress' => 50, // Same progress
        ]);

    $response->assertOk();

    // Check that no progress_updated activity was logged
    $activity = Activity::where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('event', 'progress_updated')
        ->first();

    expect($activity)->toBeNull();
});

it('logs activity when progress changes from zero to value', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $task = Task::factory()->create([
        'progress' => 0,
    ]);

    $response = $this->actingAs($user)
        ->patchJson("/api/tasks/{$task->uuid}/progress", [
            'progress' => 30,
        ]);

    $response->assertOk();

    $activity = Activity::where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('event', 'progress_updated')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->properties['old_progress'])->toBe(0);
    expect($activity->properties['new_progress'])->toBe(30);
});

// ==========================================
// Participant Activity Tests
// ==========================================

it('logs activity when participant is added to task', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $participantUser = User::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
    ]);

    $task = Task::factory()->create();

    $response = $this->actingAs($user)
        ->postJson("/api/tasks/{$task->uuid}/participants", [
            'user_id' => $participantUser->id,
            'role' => 'developer',
        ]);

    $response->assertCreated();

    // Check that activity was logged
    $activity = Activity::where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('event', 'participant_added')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toContain('Participant ajouté');
    expect($activity->description)->toContain('Jean Dupont');
    expect($activity->properties['user_name'])->toBe('Jean Dupont');
    expect($activity->properties['role'])->toBe('developer');
    expect($activity->causer_id)->toBe($user->id);
});

it('logs activity when participant is removed from task', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $participantUser = User::factory()->create([
        'first_name' => 'Marie',
        'last_name' => 'Martin',
    ]);

    $task = Task::factory()->create();

    // Add participant first
    TaskParticipant::create([
        'task_id' => $task->id,
        'user_id' => $participantUser->id,
        'role' => 'tester',
    ]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/tasks/{$task->uuid}/participants/{$participantUser->id}");

    $response->assertNoContent();

    // Check that activity was logged
    $activity = Activity::where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('event', 'participant_removed')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toContain('Participant retiré');
    expect($activity->description)->toContain('Marie Martin');
    expect($activity->properties['user_name'])->toBe('Marie Martin');
    expect($activity->properties['role'])->toBe('tester');
    expect($activity->causer_id)->toBe($user->id);
});

// ==========================================
// Attachment Activity Tests
// ==========================================

it('logs activity when attachment is added to task', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $task = Task::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 1024);

    $response = $this->actingAs($user)
        ->postJson("/api/tasks/{$task->uuid}/attachments", [
            'file' => $file,
        ]);

    $response->assertCreated();

    // Check that activity was logged
    $activity = Activity::where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('event', 'attachment_added')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toContain('Document ajouté');
    expect($activity->description)->toContain('document.pdf');
    expect($activity->properties['file_name'])->toBe('document.pdf');
    expect($activity->causer_id)->toBe($user->id);
});

it('logs activity when attachment is removed from task', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $task = Task::factory()->create();

    // Create an attachment
    $attachment = TaskAttachment::create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'file_name' => 'report.xlsx',
        'file_path' => 'task-attachments/1/report.xlsx',
        'file_type' => 'xlsx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'file_size' => 2048,
    ]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/tasks/{$task->uuid}/attachments/{$attachment->id}");

    $response->assertNoContent();

    // Check that activity was logged
    $activity = Activity::where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->where('event', 'attachment_removed')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toContain('Document supprimé');
    expect($activity->description)->toContain('report.xlsx');
    expect($activity->properties['file_name'])->toBe('report.xlsx');
    expect($activity->causer_id)->toBe($user->id);
});

// ==========================================
// Get Activities Endpoint Tests
// ==========================================

it('returns all activities for a task', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $pendingStatus = Status::where('name', 'pending')->first();
    $inProgressStatus = Status::where('name', 'in_progress')->first();

    $task = Task::factory()->create([
        'status_id' => $pendingStatus->id,
        'progress' => 0,
    ]);

    // Perform some actions to generate activities
    $this->actingAs($user)->patchJson("/api/tasks/{$task->uuid}/status", [
        'status_id' => $inProgressStatus->id,
    ]);

    $this->actingAs($user)->patchJson("/api/tasks/{$task->uuid}/progress", [
        'progress' => 50,
    ]);

    // Get activities
    $response = $this->actingAs($user)
        ->getJson("/api/tasks/{$task->uuid}/activities");

    $response->assertOk();

    $activities = $response->json();
    expect(count($activities))->toBeGreaterThanOrEqual(2);

    // Verify structure
    expect($activities[0])->toHaveKeys(['id', 'description', 'event', 'properties', 'causer', 'created_at']);
});

it('returns activities for multiple status changes', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $pendingStatus = Status::where('name', 'pending')->first();
    $inProgressStatus = Status::where('name', 'in_progress')->first();
    $completedStatus = Status::where('name', 'completed')->first();

    $task = Task::factory()->create([
        'status_id' => $pendingStatus->id,
    ]);

    // Change to in_progress
    $this->actingAs($user)->patchJson("/api/tasks/{$task->uuid}/status", [
        'status_id' => $inProgressStatus->id,
    ]);

    // Change to completed
    $this->actingAs($user)->patchJson("/api/tasks/{$task->uuid}/status", [
        'status_id' => $completedStatus->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson("/api/tasks/{$task->uuid}/activities");

    $response->assertOk();

    $activities = $response->json();

    // Filter only status_changed events
    $statusActivities = collect($activities)
        ->filter(fn ($a): bool => $a['event'] === 'status_changed')
        ->values();

    expect($statusActivities->count())->toBe(2);

    // Verify both status changes are recorded
    $statusNames = $statusActivities->pluck('properties.new_status_name')->toArray();
    expect($statusNames)->toContain('in_progress');
    expect($statusNames)->toContain('completed');
});

// ==========================================
// TaskActivityService Unit Tests
// ==========================================

it('TaskActivityService logs status change correctly', function (): void {
    $user = User::factory()->create();
    $pendingStatus = Status::where('name', 'pending')->first();
    $completedStatus = Status::where('name', 'completed')->first();

    $task = Task::factory()->create([
        'status_id' => $pendingStatus->id,
    ]);

    $service = new TaskActivityService;
    $activity = $service->logStatusChange($task, $pendingStatus->id, $completedStatus->id, $user);

    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->event)->toBe('status_changed');
    expect($activity->properties['type'])->toBe('status_changed');
    expect($activity->properties['old_status_name'])->toBe('pending');
    expect($activity->properties['new_status_name'])->toBe('completed');
});

it('TaskActivityService logs progress change correctly', function (): void {
    $user = User::factory()->create();

    $task = Task::factory()->create([
        'progress' => 25,
    ]);

    $service = new TaskActivityService;
    $activity = $service->logProgressChange($task, 25, 75, $user);

    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->event)->toBe('progress_updated');
    expect($activity->properties['type'])->toBe('progress_updated');
    expect($activity->properties['old_progress'])->toBe(25);
    expect($activity->properties['new_progress'])->toBe(75);
});

it('TaskActivityService logs participant added correctly', function (): void {
    $user = User::factory()->create();
    $participantUser = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'User',
    ]);

    $task = Task::factory()->create();

    $participant = TaskParticipant::create([
        'task_id' => $task->id,
        'user_id' => $participantUser->id,
        'role' => 'reviewer',
    ]);

    $service = new TaskActivityService;
    $activity = $service->logParticipantAdded($task, $participant, $user);

    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->event)->toBe('participant_added');
    expect($activity->properties['type'])->toBe('participant_added');
    expect($activity->properties['user_name'])->toBe('Test User');
    expect($activity->properties['role'])->toBe('reviewer');
});

it('TaskActivityService logs participant removed correctly', function (): void {
    $user = User::factory()->create();
    $participantUser = User::factory()->create([
        'first_name' => 'Removed',
        'last_name' => 'Participant',
    ]);

    $task = Task::factory()->create();

    $service = new TaskActivityService;
    $activity = $service->logParticipantRemoved($task, $participantUser, 'contributor', $user);

    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->event)->toBe('participant_removed');
    expect($activity->properties['type'])->toBe('participant_removed');
    expect($activity->properties['user_name'])->toBe('Removed Participant');
    expect($activity->properties['role'])->toBe('contributor');
});

it('TaskActivityService logs attachment added correctly', function (): void {
    $user = User::factory()->create();

    $task = Task::factory()->create();

    $attachment = TaskAttachment::create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'file_name' => 'test-file.pdf',
        'file_path' => 'task-attachments/1/test-file.pdf',
        'file_type' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
    ]);

    $service = new TaskActivityService;
    $activity = $service->logAttachmentAdded($task, $attachment, $user);

    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->event)->toBe('attachment_added');
    expect($activity->properties['type'])->toBe('attachment_added');
    expect($activity->properties['file_name'])->toBe('test-file.pdf');
});

it('TaskActivityService logs attachment removed correctly', function (): void {
    $user = User::factory()->create();

    $task = Task::factory()->create();

    $service = new TaskActivityService;
    $activity = $service->logAttachmentRemoved($task, 'deleted-file.docx', $user);

    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->event)->toBe('attachment_removed');
    expect($activity->properties['type'])->toBe('attachment_removed');
    expect($activity->properties['file_name'])->toBe('deleted-file.docx');
});

it('TaskActivityService getTaskActivities returns all activities for task', function (): void {
    $user = User::factory()->create();
    $pendingStatus = Status::where('name', 'pending')->first();
    $inProgressStatus = Status::where('name', 'in_progress')->first();

    $task = Task::factory()->create([
        'status_id' => $pendingStatus->id,
    ]);

    $service = new TaskActivityService;

    // Log multiple activities
    $service->logStatusChange($task, $pendingStatus->id, $inProgressStatus->id, $user);
    $service->logProgressChange($task, 0, 50, $user);

    $activities = $service->getTaskActivities($task);

    expect($activities->count())->toBeGreaterThanOrEqual(2);
    expect($activities->first()->subject_id)->toBe($task->id);
});

// ==========================================
// Edge Cases
// ==========================================

it('handles activity logging when causer is null', function (): void {
    $pendingStatus = Status::where('name', 'pending')->first();
    $completedStatus = Status::where('name', 'completed')->first();

    $task = Task::factory()->create([
        'status_id' => $pendingStatus->id,
    ]);

    // Test service with null causer (simulating system action)
    $service = new TaskActivityService;

    // This should not throw an exception
    $activity = $service->logStatusChange($task, $pendingStatus->id, $completedStatus->id);

    expect($activity)->toBeInstanceOf(Activity::class);
    // causer_id should be null when no user is authenticated
});

it('logs complete workflow of task activities', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $participantUser = User::factory()->create([
        'first_name' => 'Workflow',
        'last_name' => 'Test',
    ]);

    $pendingStatus = Status::where('name', 'pending')->first();
    $inProgressStatus = Status::where('name', 'in_progress')->first();
    $completedStatus = Status::where('name', 'completed')->first();

    $task = Task::factory()->create([
        'status_id' => $pendingStatus->id,
        'progress' => 0,
    ]);

    // 1. Change status to in_progress
    $this->actingAs($user)->patchJson("/api/tasks/{$task->uuid}/status", [
        'status_id' => $inProgressStatus->id,
    ]);

    // 2. Update progress
    $this->actingAs($user)->patchJson("/api/tasks/{$task->uuid}/progress", [
        'progress' => 25,
    ]);

    // 3. Add participant
    $this->actingAs($user)->postJson("/api/tasks/{$task->uuid}/participants", [
        'user_id' => $participantUser->id,
        'role' => 'developer',
    ]);

    // 4. Add attachment
    $file = UploadedFile::fake()->create('spec.pdf', 500);
    $this->actingAs($user)->postJson("/api/tasks/{$task->uuid}/attachments", [
        'file' => $file,
    ]);

    // 5. Update progress again
    $this->actingAs($user)->patchJson("/api/tasks/{$task->uuid}/progress", [
        'progress' => 100,
    ]);

    // 6. Change status to completed
    $this->actingAs($user)->patchJson("/api/tasks/{$task->uuid}/status", [
        'status_id' => $completedStatus->id,
    ]);

    // Get all activities
    $response = $this->actingAs($user)->getJson("/api/tasks/{$task->uuid}/activities");

    $response->assertOk();
    $activities = $response->json();

    // Should have at least 6 activities
    expect(count($activities))->toBeGreaterThanOrEqual(6);

    // Verify different event types are present
    $events = collect($activities)->pluck('event')->unique()->values()->toArray();
    expect($events)->toContain('status_changed');
    expect($events)->toContain('progress_updated');
    expect($events)->toContain('participant_added');
    expect($events)->toContain('attachment_added');
});
