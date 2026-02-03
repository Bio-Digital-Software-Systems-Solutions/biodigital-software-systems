<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
        $this->program = Program::factory()->create(['user_id' => $this->user->id]);
        $this->status = Status::first() ?? Status::factory()->create();
    }

    public function test_index_displays_tasks(): void
    {
        $tasks = Task::factory()->count(3)->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
        ]);

        $this->user->givePermissionTo('view tasks');

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Tasks/Index')
            ->has('tasks.data', 3)
        );
    }

    public function test_index_can_filter_by_status(): void
    {
        $pendingStatus = Status::where('name', 'pending')->first()
            ?? Status::factory()->create(['name' => 'pending']);
        $completedStatus = Status::where('name', 'completed')->first()
            ?? Status::factory()->create(['name' => 'completed']);

        Task::factory()->count(2)->create([
            'program_id' => $this->program->id,
            'status_id' => $pendingStatus->id,
        ]);

        Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $completedStatus->id,
        ]);

        $this->user->givePermissionTo('view tasks');

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Tasks/Index')
            ->has('tasks.data', 2)
        );
    }

    public function test_create_displays_form(): void
    {
        $this->user->givePermissionTo('create tasks');

        $response = $this->actingAs($this->user)
            ->get(route('tasks.create'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Tasks/Create')
            ->has('programs')
            ->has('statuses')
            ->has('users')
        );
    }

    public function test_store_creates_task(): void
    {
        $this->user->givePermissionTo('create tasks');

        $taskData = [
            'title' => 'Test Task',
            'description' => 'Task description',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'priority' => 'medium',
            'estimated_hours' => '8.5',
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success', 'Task created successfully.');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'description' => 'Task description',
            'priority' => 'medium',
            'estimated_hours' => 8.5,
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->user->givePermissionTo('create tasks');

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), []);

        $response->assertSessionHasErrors(['title', 'description', 'priority', 'status_id']);
    }

    public function test_store_validates_description_minimum_length(): void
    {
        $this->user->givePermissionTo('create tasks');

        $taskData = [
            'title' => 'Test Task',
            'description' => 'Too short', // Less than 10 characters
            'priority' => 'medium',
            'status_id' => $this->status->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertSessionHasErrors(['description']);
    }

    public function test_store_accepts_valid_description(): void
    {
        $this->user->givePermissionTo('create tasks');

        $taskData = [
            'title' => 'Test Task',
            'description' => 'This is a valid description with more than 10 characters',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'priority' => 'medium',
            'status_id' => $this->status->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertSessionDoesntHaveErrors(['description']);
        $response->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'description' => 'This is a valid description with more than 10 characters',
        ]);
    }

    public function test_store_validates_priority_values(): void
    {
        $this->user->givePermissionTo('create tasks');

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), [
                'title' => 'Test Task',
                'description' => 'A valid description for the test task',
                'priority' => 'invalid',
                'status_id' => $this->status->id,
                'program_id' => $this->program->id,
            ]);

        $response->assertSessionHasErrors(['priority']);
    }

    public function test_show_displays_task(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
        ]);

        $this->user->givePermissionTo('view tasks');

        $response = $this->actingAs($this->user)
            ->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Tasks/Show')
            ->where('task.id', $task->id)
            ->where('task.title', $task->title)
        );
    }

    public function test_edit_displays_form(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
        ]);

        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Tasks/Edit')
            ->where('task.id', $task->id)
            ->has('programs')
            ->has('statuses')
            ->has('users')
        );
    }

    public function test_update_modifies_task(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'title' => 'Original Title',
        ]);

        $this->user->givePermissionTo('edit tasks');

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'priority' => 'high',
            'estimated_hours' => '10',
            'actual_hours' => '12',
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('tasks.update', $task), $updateData);

        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success', 'Task updated successfully.');

        $task->refresh();
        $this->assertEquals('Updated Title', $task->title);
        $this->assertEquals('high', $task->priority);
        $this->assertEquals(10.0, (float) $task->estimated_hours);
        $this->assertEquals(12.0, (float) $task->actual_hours);
    }

    public function test_destroy_deletes_task(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
        ]);

        $this->user->givePermissionTo('delete tasks');

        $response = $this->actingAs($this->user)
            ->delete(route('tasks.destroy', $task));

        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success', 'Task deleted successfully.');

        $this->assertSoftDeleted($task);
    }

    public function test_unauthorized_user_cannot_access_tasks(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index'));

        $response->assertForbidden();
    }

    public function test_user_can_filter_by_assigned_user(): void
    {
        $assignedUser = User::factory()->create();

        $myTask = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'assigned_to' => $assignedUser->id,
        ]);

        $otherTask = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'assigned_to' => $this->user->id,
        ]);

        $this->user->givePermissionTo('view tasks');

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['assigned_to' => $assignedUser->id]));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Tasks/Index')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.assigned_to', $assignedUser->id)
        );
    }

    public function test_task_due_date_validation(): void
    {
        $this->user->givePermissionTo('create tasks');

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), [
                'title' => 'Test Task',
                'description' => 'A valid description for the test task',
                'due_date' => now()->subDay()->format('Y-m-d'),
                'priority' => 'medium',
                'status_id' => $this->status->id,
                'program_id' => $this->program->id,
            ]);

        $response->assertSessionHasErrors(['due_date']);
    }

    public function test_toggle_complete_marks_task_as_completed(): void
    {
        $pendingStatus = Status::where('name', 'pending')->first()
            ?? Status::factory()->create(['name' => 'pending']);
        $completedStatus = Status::where('name', 'completed')->first()
            ?? Status::factory()->create(['name' => 'completed']);

        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $pendingStatus->id,
        ]);

        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->uuid));

        $response->assertRedirect();

        $task->refresh();
        $this->assertEquals($completedStatus->id, $task->status_id);
    }

    public function test_toggle_complete_marks_completed_task_as_pending(): void
    {
        $pendingStatus = Status::where('name', 'pending')->first()
            ?? Status::factory()->create(['name' => 'pending']);
        $completedStatus = Status::where('name', 'completed')->first()
            ?? Status::factory()->create(['name' => 'completed']);

        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $completedStatus->id,
        ]);

        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->uuid));

        $response->assertRedirect();

        $task->refresh();
        $this->assertEquals($pendingStatus->id, $task->status_id);
    }

    public function test_toggle_complete_works_multiple_times(): void
    {
        $pendingStatus = Status::where('name', 'pending')->first()
            ?? Status::factory()->create(['name' => 'pending']);
        $completedStatus = Status::where('name', 'completed')->first()
            ?? Status::factory()->create(['name' => 'completed']);

        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $pendingStatus->id,
        ]);

        $this->user->givePermissionTo('edit tasks');

        // First toggle: pending -> completed
        $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->uuid));

        $task->refresh();
        $this->assertEquals($completedStatus->id, $task->status_id, 'First toggle should mark as completed');

        // Second toggle: completed -> pending
        $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->uuid));

        $task->refresh();
        $this->assertEquals($pendingStatus->id, $task->status_id, 'Second toggle should mark as pending');

        // Third toggle: pending -> completed
        $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->uuid));

        $task->refresh();
        $this->assertEquals($completedStatus->id, $task->status_id, 'Third toggle should mark as completed again');
    }

    public function test_toggle_complete_uses_uuid_routing(): void
    {
        $pendingStatus = Status::where('name', 'pending')->first()
            ?? Status::factory()->create(['name' => 'pending']);

        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $pendingStatus->id,
        ]);

        $this->user->givePermissionTo('edit tasks');

        // Ensure UUID is generated
        $this->assertNotNull($task->uuid, 'Task should have a UUID');

        // Test that route works with UUID
        $response = $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->uuid));

        $response->assertRedirect();
        $response->assertStatus(302);
    }

    public function test_toggle_complete_requires_edit_permission(): void
    {
        $pendingStatus = Status::where('name', 'pending')->first()
            ?? Status::factory()->create(['name' => 'pending']);

        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $pendingStatus->id,
        ]);

        // User without permission
        $response = $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->uuid));

        $response->assertForbidden();
    }

    public function test_toggle_complete_handles_missing_statuses_gracefully(): void
    {
        $customStatus = Status::factory()->create(['name' => 'custom']);

        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $customStatus->id,
        ]);

        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->uuid));

        $response->assertRedirect();

        // Task status should not change if completed/pending statuses don't exist
        $task->refresh();
        $this->assertEquals($customStatus->id, $task->status_id);
    }

    public function test_edit_page_cancel_button_returns_to_task_details(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
        ]);

        $this->user->givePermissionTo('edit tasks');

        $response = $this->actingAs($this->user)
            ->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Tasks/Edit')
            ->where('task.uuid', $task->uuid)
        );

        // The Edit page should have task data with uuid so the Cancel button
        // can navigate to tasks.show route with the task's uuid
        $this->assertNotNull($task->uuid);
        $this->assertEquals(
            route('tasks.show', $task->uuid),
            route('tasks.show', $task)
        );
    }
}
