<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Task $task;
    protected Status $status;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->status = Status::first() ?? Status::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status_id' => $this->status->id,
            'progress' => 0,
        ]);

        // Give the user necessary permissions
        $this->user->givePermissionTo(['view tasks', 'create tasks', 'edit tasks', 'delete tasks']);
    }

    // ==========================================
    // Progress Tests
    // ==========================================

    public function test_task_is_created_with_default_progress_of_zero(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status_id' => $this->status->id,
        ]);

        $this->assertEquals(0, $task->progress);
    }

    public function test_user_can_update_task_progress(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('tasks.update-progress', $this->task), [
                'progress' => 50,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Task progress updated successfully.');

        $this->task->refresh();
        $this->assertEquals(50, $this->task->progress);
    }

    public function test_progress_validation_min_zero(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('tasks.update-progress', $this->task), [
                'progress' => -10,
            ]);

        $response->assertSessionHasErrors(['progress']);
    }

    public function test_progress_validation_max_hundred(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('tasks.update-progress', $this->task), [
                'progress' => 150,
            ]);

        $response->assertSessionHasErrors(['progress']);
    }

    public function test_progress_can_be_set_to_100(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('tasks.update-progress', $this->task), [
                'progress' => 100,
            ]);

        $response->assertRedirect();

        $this->task->refresh();
        $this->assertEquals(100, $this->task->progress);
    }

    public function test_progress_is_included_in_task_creation(): void
    {
        $taskData = [
            'title' => 'Test Task with Progress',
            'description' => 'A task description',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'priority' => 'medium',
            'progress' => 25,
            'status_id' => $this->status->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertRedirect();

        $task = Task::where('title', 'Test Task with Progress')->first();
        $this->assertNotNull($task);
        $this->assertEquals(25, $task->progress);
    }

    public function test_progress_is_included_in_task_update(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('tasks.update', $this->task), [
                'title' => $this->task->title,
                'priority' => 'medium',
                'progress' => 75,
                'status_id' => $this->status->id,
            ]);

        $response->assertRedirect();

        $this->task->refresh();
        $this->assertEquals(75, $this->task->progress);
    }

    // ==========================================
    // Participant Tests
    // ==========================================

    public function test_user_can_add_participant_to_task(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tasks.participants.add', $this->task), [
                'user_id' => $this->otherUser->id,
                'role' => 'reviewer',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Participant added successfully.');

        $this->assertDatabaseHas('task_participants', [
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'role' => 'reviewer',
        ]);
    }

    public function test_cannot_add_same_participant_twice(): void
    {
        // Add participant first time
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'role' => 'reviewer',
        ]);

        // Try to add again
        $response = $this->actingAs($this->user)
            ->post(route('tasks.participants.add', $this->task), [
                'user_id' => $this->otherUser->id,
                'role' => 'developer',
            ]);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_user_can_update_participant_role(): void
    {
        $participant = TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'role' => 'reviewer',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('tasks.participants.update', [$this->task, $participant]), [
                'role' => 'lead',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Participant role updated successfully.');

        $participant->refresh();
        $this->assertEquals('lead', $participant->role);
    }

    public function test_user_can_remove_participant(): void
    {
        $participant = TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'role' => 'reviewer',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('tasks.participants.remove', [$this->task, $participant]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Participant removed successfully.');

        $this->assertDatabaseMissing('task_participants', [
            'id' => $participant->id,
        ]);
    }

    public function test_participant_requires_valid_user(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tasks.participants.add', $this->task), [
                'user_id' => 99999, // Non-existent user
                'role' => 'reviewer',
            ]);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_participant_requires_role(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tasks.participants.add', $this->task), [
                'user_id' => $this->otherUser->id,
            ]);

        $response->assertSessionHasErrors(['role']);
    }

    public function test_task_can_have_multiple_participants(): void
    {
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'role' => 'reviewer',
        ]);

        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $user2->id,
            'role' => 'developer',
        ]);

        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $user3->id,
            'role' => 'tester',
        ]);

        $this->task->load('participants');
        $this->assertCount(3, $this->task->participants);
    }

    // ==========================================
    // Comment Tests
    // ==========================================

    public function test_user_can_add_comment_to_task(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tasks.comments.add', $this->task), [
                'content' => 'This is a test comment.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment added successfully.');

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test comment.',
        ]);
    }

    public function test_comment_requires_content(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tasks.comments.add', $this->task), [
                'content' => '',
            ]);

        $response->assertSessionHasErrors(['content']);
    }

    public function test_comment_content_max_length(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tasks.comments.add', $this->task), [
                'content' => str_repeat('a', 10001), // Exceeds max length
            ]);

        $response->assertSessionHasErrors(['content']);
    }

    public function test_user_can_update_own_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Original content.',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('tasks.comments.update', [$this->task, $comment]), [
                'content' => 'Updated content.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment updated successfully.');

        $comment->refresh();
        $this->assertEquals('Updated content.', $comment->content);
    }

    public function test_user_cannot_update_others_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id, // Comment by another user
            'content' => 'Original content.',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('tasks.comments.update', [$this->task, $comment]), [
                'content' => 'Trying to update.',
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_own_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Comment to delete.',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('tasks.comments.delete', [$this->task, $comment]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment deleted successfully.');

        $this->assertDatabaseMissing('task_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_user_cannot_delete_others_comment_without_permission(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Another user comment.',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('tasks.comments.delete', [$this->task, $comment]));

        $response->assertForbidden();
    }

    public function test_task_can_have_multiple_comments(): void
    {
        TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'First comment.',
        ]);

        TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Second comment.',
        ]);

        TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'content' => 'Third comment.',
        ]);

        $this->task->load('comments');
        $this->assertCount(3, $this->task->comments);
    }

    // ==========================================
    // Attachment Tests
    // ==========================================

    public function test_user_can_upload_attachment_to_task(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.attachments.add', $this->task), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Attachment uploaded successfully.');

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'document.pdf',
            'file_type' => 'pdf',
        ]);
    }

    public function test_attachment_requires_file(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tasks.attachments.add', $this->task), []);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_attachment_max_file_size(): void
    {
        Storage::fake('public');

        // 60MB file, exceeds 50MB limit
        $file = UploadedFile::fake()->create('large-file.pdf', 60000);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.attachments.add', $this->task), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_user_can_delete_own_attachment(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = $file->store('task-attachments/' . $this->task->id, 'public');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'document.pdf',
            'file_path' => $path,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100000,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('tasks.attachments.delete', [$this->task, $attachment]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Attachment deleted successfully.');

        $this->assertDatabaseMissing('task_attachments', [
            'id' => $attachment->id,
        ]);
    }

    public function test_user_cannot_delete_others_attachment_without_permission(): void
    {
        Storage::fake('public');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'file_name' => 'document.pdf',
            'file_path' => 'task-attachments/1/document.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100000,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('tasks.attachments.delete', [$this->task, $attachment]));

        $response->assertForbidden();
    }

    public function test_user_can_download_attachment(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = $file->store('task-attachments/' . $this->task->id, 'public');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'document.pdf',
            'file_path' => $path,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.attachments.download', [$this->task, $attachment]));

        $response->assertOk();
        $response->assertDownload('document.pdf');
    }

    public function test_task_can_have_multiple_attachments(): void
    {
        TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'doc1.pdf',
            'file_path' => 'task-attachments/1/doc1.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100000,
        ]);

        TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'image.png',
            'file_path' => 'task-attachments/1/image.png',
            'file_type' => 'png',
            'mime_type' => 'image/png',
            'file_size' => 50000,
        ]);

        $this->task->load('taskAttachments');
        $this->assertCount(2, $this->task->taskAttachments);
    }

    // ==========================================
    // Model Relationship Tests
    // ==========================================

    public function test_task_has_participants_relationship(): void
    {
        $participant = TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'role' => 'developer',
        ]);

        $this->task->load('participants');

        $this->assertCount(1, $this->task->participants);
        $this->assertEquals($participant->id, $this->task->participants->first()->id);
    }

    public function test_task_has_comments_relationship(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment.',
        ]);

        $this->task->load('comments');

        $this->assertCount(1, $this->task->comments);
        $this->assertEquals($comment->id, $this->task->comments->first()->id);
    }

    public function test_task_has_attachments_relationship(): void
    {
        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'doc.pdf',
            'file_path' => 'task-attachments/1/doc.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100000,
        ]);

        $this->task->load('taskAttachments');

        $this->assertCount(1, $this->task->taskAttachments);
        $this->assertEquals($attachment->id, $this->task->taskAttachments->first()->id);
    }

    public function test_task_participant_belongs_to_user(): void
    {
        $participant = TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'role' => 'developer',
        ]);

        $this->assertInstanceOf(User::class, $participant->user);
        $this->assertEquals($this->otherUser->id, $participant->user->id);
    }

    public function test_task_comment_belongs_to_user(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment.',
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($this->user->id, $comment->user->id);
    }

    public function test_task_attachment_belongs_to_user(): void
    {
        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'doc.pdf',
            'file_path' => 'task-attachments/1/doc.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100000,
        ]);

        $this->assertInstanceOf(User::class, $attachment->user);
        $this->assertEquals($this->user->id, $attachment->user->id);
    }

    public function test_participant_users_relationship(): void
    {
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
            'role' => 'developer',
        ]);

        $this->task->load('participantUsers');

        $this->assertCount(1, $this->task->participantUsers);
        $this->assertEquals($this->otherUser->id, $this->task->participantUsers->first()->id);
        $this->assertEquals('developer', $this->task->participantUsers->first()->pivot->role);
    }
}
