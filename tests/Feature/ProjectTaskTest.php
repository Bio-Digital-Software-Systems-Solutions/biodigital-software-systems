<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected ProjectTask $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions first
        \Spatie\Permission\Models\Permission::create(['name' => 'view programs']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage programs']);

        // Create roles and permissions
        Role::create(['name' => 'admin']);
        $role = Role::create(['name' => 'project-manager']);
        $role->givePermissionTo('view programs');
        $role->givePermissionTo('manage programs');

        $this->user = User::factory()->create();
        $this->user->assignRole('project-manager');

        $this->project = Project::factory()->create();
        $this->task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_view_task_details(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('project-tasks.show', $this->task));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ProjectTasks/Show')
                ->has('task')
                ->where('task.id', $this->task->id)
            );
    }

    /** @test */
    public function user_can_start_task(): void
    {
        $this->assertNull($this->task->started_at);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$this->task->id}", [
                'started_at' => now()->toISOString(),
            ]);

        $response->assertOk();
        $this->assertNotNull($this->task->fresh()->started_at);
    }

    /** @test */
    public function user_can_pause_task(): void
    {
        $this->task->update(['started_at' => now()]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$this->task->id}", [
                'paused_at' => now()->toISOString(),
            ]);

        $response->assertOk();
        $this->assertNotNull($this->task->fresh()->paused_at);
    }

    /** @test */
    public function user_can_stop_task(): void
    {
        $this->task->update(['started_at' => now()]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$this->task->id}", [
                'stopped_at' => now()->toISOString(),
            ]);

        $response->assertOk();
        $this->assertNotNull($this->task->fresh()->stopped_at);
    }

    /** @test */
    public function user_can_add_comment_to_task(): void
    {
        $commentData = [
            'content' => 'This is a test comment',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->id}/comments", $commentData);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'content',
                'user_id',
                'task_id',
                'created_at',
                'updated_at',
                'user',
            ]);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test comment',
        ]);
    }

    /** @test */
    public function user_can_delete_own_comment(): void
    {
        $comment = TaskComment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$this->task->id}/comments/{$comment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('task_comments', ['id' => $comment->id]);
    }

    /** @test */
    public function user_cannot_delete_other_users_comment_without_permission(): void
    {
        // Create a regular user without 'manage programs' permission
        $regularUser = User::factory()->create();
        $regularRole = Role::create(['name' => 'RegularUser']);
        $regularRole->givePermissionTo('view programs'); // Only view, not manage
        $regularUser->assignRole('RegularUser');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('project-manager');

        $comment = TaskComment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $otherUser->id,
            'content' => 'Other user comment',
        ]);

        $response = $this->actingAs($regularUser)
            ->deleteJson("/api/tasks/{$this->task->id}/comments/{$comment->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('task_comments', ['id' => $comment->id]);
    }

    /** @test */
    public function user_can_add_participant_to_task(): void
    {
        $participant = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->id}/participants", [
                'user_id' => $participant->id,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('task_participants', [
            'task_id' => $this->task->id,
            'user_id' => $participant->id,
        ]);
    }

    /** @test */
    public function user_can_remove_participant_from_task(): void
    {
        $participant = User::factory()->create();
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $participant->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$this->task->id}/participants/{$participant->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('task_participants', [
            'task_id' => $this->task->id,
            'user_id' => $participant->id,
        ]);
    }

    /** @test */
    public function reviewer_can_approve_task(): void
    {
        $reviewer = User::factory()->create();
        $reviewer->assignRole('project-manager');

        $this->task->update(['reviewer_id' => $reviewer->id]);

        $this->task->refresh();
        $this->assertEquals(false, (bool) $this->task->reviewed);

        $response = $this->actingAs($reviewer)
            ->patchJson("/api/tasks/{$this->task->id}", [
                'reviewed' => true,
                'reviewed_at' => now()->toISOString(),
            ]);

        $response->assertOk();
        $this->assertTrue($this->task->fresh()->reviewed);
        $this->assertNotNull($this->task->fresh()->reviewed_at);
    }

    /** @test */
    public function task_can_have_multiple_participants(): void
    {
        $participants = User::factory()->count(3)->create();

        foreach ($participants as $participant) {
            TaskParticipant::create([
                'task_id' => $this->task->id,
                'user_id' => $participant->id,
            ]);
        }

        $this->assertEquals(3, $this->task->participants()->count());
    }

    /** @test */
    public function task_can_have_multiple_comments(): void
    {
        $users = User::factory()->count(3)->create();
        $users->each(fn ($user) => $user->assignRole('project-manager'));

        foreach ($users as $user) {
            TaskComment::create([
                'task_id' => $this->task->id,
                'user_id' => $user->id,
                'content' => "Comment by {$user->first_name}",
            ]);
        }

        $this->assertEquals(3, $this->task->comments()->count());
    }

    /** @test */
    public function cannot_add_same_participant_twice(): void
    {
        $participant = User::factory()->create();

        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $participant->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $participant->id,
        ]);
    }

    /** @test */
    public function user_can_upload_attachment(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test-image.jpg');

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'task_id',
                'user_id',
                'file_name',
                'file_path',
                'file_type',
                'mime_type',
                'file_size',
                'created_at',
                'updated_at',
                'user',
            ]);

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'test-image.jpg',
            'file_type' => 'image',
        ]);

        Storage::disk('public')->assertExists('task-attachments/'.$file->hashName());
    }

    /** @test */
    public function user_can_upload_video_attachment(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('test-video.mp4', 1000, 'video/mp4');

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $this->task->id,
            'file_type' => 'video',
        ]);
    }

    /** @test */
    public function user_can_upload_document_attachment(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('test-doc.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $this->task->id,
            'file_type' => 'document',
        ]);
    }

    /** @test */
    public function user_can_delete_own_attachment(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test.jpg');
        $filePath = $file->store('task-attachments', 'public');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'test.jpg',
            'file_path' => $filePath,
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$this->task->id}/attachments/{$attachment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($filePath);
    }

    /** @test */
    public function user_cannot_delete_other_users_attachment_without_permission(): void
    {
        Storage::fake('public');

        $regularUser = User::factory()->create();
        $regularRole = Role::create(['name' => 'RegularUser']);
        $regularRole->givePermissionTo('view programs');
        $regularUser->assignRole('RegularUser');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('project-manager');

        $file = UploadedFile::fake()->image('test.jpg');
        $filePath = $file->store('task-attachments', 'public');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $otherUser->id,
            'file_name' => 'test.jpg',
            'file_path' => $filePath,
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        $response = $this->actingAs($regularUser)
            ->deleteJson("/api/tasks/{$this->task->id}/attachments/{$attachment->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('task_attachments', ['id' => $attachment->id]);
    }

    /** @test */
    public function attachment_upload_validates_file(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->id}/attachments", [
                'file' => 'not-a-file',
            ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function task_can_have_multiple_attachments(): void
    {
        Storage::fake('public');

        $file1 = UploadedFile::fake()->image('image.jpg');
        $file2 = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $file3 = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'image.jpg',
            'file_path' => $file1->store('task-attachments', 'public'),
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => $file1->getSize(),
        ]);

        TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'document.pdf',
            'file_path' => $file2->store('task-attachments', 'public'),
            'file_type' => 'document',
            'mime_type' => 'application/pdf',
            'file_size' => $file2->getSize(),
        ]);

        TaskAttachment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'file_name' => 'video.mp4',
            'file_path' => $file3->store('task-attachments', 'public'),
            'file_type' => 'video',
            'mime_type' => 'video/mp4',
            'file_size' => $file3->getSize(),
        ]);

        $this->assertEquals(3, $this->task->attachments()->count());
    }
}
