<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskInlineUpdateTest extends TestCase
{
    use RefreshDatabase;

    public $user;

    public $program;

    public $status;

    public $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(StatusSeeder::class);

        $this->user = User::factory()->create();
        $this->program = Program::factory()->create(['user_id' => $this->user->id]);
        $this->status = Status::first() ?? Status::factory()->create();
        $this->task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'assigned_to' => $this->user->id,
            'priority' => 'medium',
            'title' => 'Original Title',
        ]);
    }

    public function test_inline_update_title(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'title',
                'value' => 'Updated Title',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('task.title', 'Updated Title');

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_inline_update_priority(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'priority',
                'value' => 'high',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('task.priority', 'high');

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'priority' => 'high',
        ]);
    }

    public function test_inline_update_status(): void
    {
        $this->user->givePermissionTo('edit tasks');
        $completedStatus = Status::where('name', 'completed')->first()
            ?? Status::factory()->create(['name' => 'completed']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'status_id',
                'value' => $completedStatus->id,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('task.status_id', $completedStatus->id);

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'status_id' => $completedStatus->id,
        ]);
    }

    public function test_inline_update_due_date(): void
    {
        $this->user->givePermissionTo('edit tasks');
        $newDate = now()->addDays(14)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'due_date',
                'value' => $newDate,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->task->refresh();
        $this->assertEquals($newDate, $this->task->due_date->format('Y-m-d'));
    }

    public function test_inline_update_due_date_can_be_null(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'due_date',
                'value' => null,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'due_date' => null,
        ]);
    }

    public function test_inline_update_assigned_to(): void
    {
        $this->user->givePermissionTo('edit tasks');
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'assigned_to',
                'value' => $otherUser->id,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('task.assigned_to', $otherUser->id);
        $response->assertJsonStructure([
            'success',
            'task' => ['id', 'uuid', 'title', 'status', 'assigned_user'],
        ]);
    }

    public function test_inline_update_assigned_to_can_be_null(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'assigned_to',
                'value' => null,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'assigned_to' => null,
        ]);
    }

    public function test_inline_update_rejects_disallowed_field(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'progress',
                'value' => 50,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Field not allowed.');
    }

    public function test_inline_update_rejects_invalid_priority(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'priority',
                'value' => 'invalid_priority',
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_rejects_invalid_status_id(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'status_id',
                'value' => 99999,
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_rejects_empty_title(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'title',
                'value' => '',
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_rejects_title_too_long(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'title',
                'value' => str_repeat('A', 256),
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_requires_edit_permission(): void
    {
        $this->user->givePermissionTo('view tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'title',
                'value' => 'Should Not Work',
            ]);

        $response->assertForbidden();
    }

    public function test_inline_update_requires_authentication(): void
    {
        $response = $this->patchJson(route('tasks.inline-update', $this->task->uuid), [
            'field' => 'title',
            'value' => 'Should Not Work',
        ]);

        $response->assertUnauthorized();
    }

    public function test_inline_update_returns_loaded_relationships(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'title',
                'value' => 'New Title',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'task' => [
                'id',
                'uuid',
                'title',
                'status' => ['id', 'name'],
                'assigned_user',
            ],
        ]);
    }

    public function test_inline_update_all_priority_values(): void
    {
        $this->user->givePermissionTo('edit tasks');

        foreach (['lowest', 'low', 'medium', 'high', 'highest'] as $priority) {
            $response = $this->actingAs($this->user)
                ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                    'field' => 'priority',
                    'value' => $priority,
                ]);

            $response->assertOk();
            $response->assertJsonPath('task.priority', $priority);
        }
    }

    public function test_inline_update_invalid_date_format(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'due_date',
                'value' => 'not-a-date',
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_nonexistent_task(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', 'nonexistent-uuid'), [
                'field' => 'title',
                'value' => 'Test',
            ]);

        $response->assertNotFound();
    }

    public function test_inline_update_description(): void
    {
        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patchJson(route('tasks.inline-update', $this->task->uuid), [
                'field' => 'description',
                'value' => 'Updated description text',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'description' => 'Updated description text',
        ]);
    }
}
