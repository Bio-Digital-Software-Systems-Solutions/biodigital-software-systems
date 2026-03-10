<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectTaskControllerTest extends TestCase
{
    public $user;
    public $project;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view programs');
        $this->user->givePermissionTo('edit programs');

        $this->project = Project::factory()->create();
    }

    public function test_can_store_comment_on_task(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/comments", [
                'content' => 'This is a test comment',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'content',
            'user_id',
            'task_id',
            'created_at',
            'user' => [
                'id',
                'first_name',
                'last_name',
            ],
        ]);

        $this->assertDatabaseHas('task_comments', [
            'content' => 'This is a test comment',
            'user_id' => $this->user->id,
            'task_id' => $task->id,
        ]);
    }

    public function test_cannot_store_empty_comment(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/comments", [
                'content' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_can_delete_own_comment(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $comment = $task->comments()->create([
            'content' => 'Test comment',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->uuid}/comments/{$comment->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('task_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_can_upload_attachment(): void
    {
        Storage::fake('public');

        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $file = UploadedFile::fake()->image('test-document.jpg', 100, 100);

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'name',
            'file_path',
            'file_type',
            'mime_type',
            'file_size',
            'uploaded_by',
            'created_at',
            'uploader' => [
                'id',
                'first_name',
                'last_name',
            ],
        ]);

        $this->assertDatabaseHas('attachments', [
            'name' => 'test-document.jpg',
            'file_type' => 'image',
            'uploaded_by' => $this->user->id,
            'attachable_type' => ProjectTask::class,
            'attachable_id' => $task->id,
        ]);

        Storage::disk('public')->assertExists(
            $response->json('file_path')
        );
    }

    public function test_can_delete_attachment(): void
    {
        Storage::fake('public');

        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $file = UploadedFile::fake()->image('test.jpg');
        $filePath = $file->store('task-attachments', 'public');

        $attachment = $task->attachments()->create([
            'name' => 'test.jpg',
            'file_path' => $filePath,
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploaded_by' => $this->user->id,
        ]);

        Storage::disk('public')->assertExists($filePath);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->uuid}/attachments/{$attachment->uuid}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('attachments', [
            'id' => $attachment->id,
        ]);

        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_cannot_upload_file_larger_than_50mb(): void
    {
        Storage::fake('public');

        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        // Create a file that is 51MB (51200KB + 1KB)
        $file = UploadedFile::fake()->create('large-file.pdf', 51201);

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_can_add_participant_to_task(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $participant = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/participants", [
                'user_id' => $participant->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'task_id',
            'user_id',
            'user' => [
                'id',
                'first_name',
                'last_name',
            ],
        ]);

        $this->assertDatabaseHas('task_participants', [
            'task_id' => $task->id,
            'user_id' => $participant->id,
        ]);
    }

    public function test_can_remove_participant_from_task(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $participant = User::factory()->create();
        $task->participants()->create([
            'user_id' => $participant->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->uuid}/participants/{$participant->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('task_participants', [
            'task_id' => $task->id,
            'user_id' => $participant->id,
        ]);
    }

    public function test_cannot_add_duplicate_participant(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $participant = User::factory()->create();
        $task->participants()->create([
            'user_id' => $participant->id,
        ]);

        // Try to add the same participant again
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/participants", [
                'user_id' => $participant->id,
            ]);

        // Should still succeed but not create duplicate
        $response->assertStatus(201);

        $this->assertEquals(1, $task->participants()->count());
    }

    public function test_attachment_has_correct_file_type(): void
    {
        Storage::fake('public');

        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        // Test image
        $imageFile = UploadedFile::fake()->image('test.jpg');
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/attachments", ['file' => $imageFile]);
        $response->assertJsonPath('file_type', 'image');

        // Test video
        $videoFile = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/attachments", ['file' => $videoFile]);
        $response->assertJsonPath('file_type', 'video');

        // Test document
        $docFile = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/attachments", ['file' => $docFile]);
        $response->assertJsonPath('file_type', 'document');
    }

    public function test_uses_uuid_for_routing(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $this->assertNotNull($task->uuid);

        // Test with UUID
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/comments", [
                'content' => 'Test comment',
            ]);

        $response->assertStatus(201);

        // Test with ID (should fail)
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/comments", [
                'content' => 'Test comment',
            ]);

        $response->assertStatus(404);
    }
}
