<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TaskToggleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $program;

    protected $todoStatus;

    protected $completedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view tasks']);
        Permission::create(['name' => 'create tasks']);
        Permission::create(['name' => 'edit tasks']);
        Permission::create(['name' => 'delete tasks']);

        // Create user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['view tasks', 'create tasks', 'edit tasks', 'delete tasks']);

        // Create a program
        $this->program = Program::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Create statuses
        $this->todoStatus = Status::factory()->create(['name' => 'todo']);
        $this->completedStatus = Status::factory()->create(['name' => 'completed']);
    }

    /** @test */
    public function user_can_toggle_task_to_completed(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->todoStatus->id,
            'title' => 'Test Task',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status_id' => $this->completedStatus->id,
        ]);
    }

    /** @test */
    public function user_can_toggle_task_to_incomplete(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->completedStatus->id,
            'title' => 'Completed Task',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status_id' => $this->todoStatus->id,
        ]);
    }

    /** @test */
    public function user_can_bulk_mark_tasks_as_completed(): void
    {
        $task1 = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->todoStatus->id,
            'title' => 'Task 1',
        ]);

        $task2 = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->todoStatus->id,
            'title' => 'Task 2',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.bulk-toggle-complete'), [
                'task_ids' => [$task1->id, $task2->id],
                'completed' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task1->id,
            'status_id' => $this->completedStatus->id,
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $task2->id,
            'status_id' => $this->completedStatus->id,
        ]);
    }

    /** @test */
    public function user_can_bulk_mark_tasks_as_incomplete(): void
    {
        $task1 = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->completedStatus->id,
            'title' => 'Completed Task 1',
        ]);

        $task2 = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->completedStatus->id,
            'title' => 'Completed Task 2',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.bulk-toggle-complete'), [
                'task_ids' => [$task1->id, $task2->id],
                'completed' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task1->id,
            'status_id' => $this->todoStatus->id,
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $task2->id,
            'status_id' => $this->todoStatus->id,
        ]);
    }

    /** @test */
    public function bulk_toggle_requires_task_ids(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tasks.bulk-toggle-complete'), [
                'completed' => true,
            ]);

        $response->assertSessionHasErrors(['task_ids']);
    }

    /** @test */
    public function bulk_toggle_requires_completed_flag(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->todoStatus->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('tasks.bulk-toggle-complete'), [
                'task_ids' => [$task->id],
            ]);

        $response->assertSessionHasErrors(['completed']);
    }

    /** @test */
    public function unauthorized_user_cannot_toggle_task(): void
    {
        $unauthorizedUser = User::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->todoStatus->id,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->patch(route('tasks.toggle-complete', $task->id));

        // Should be forbidden (403) or redirected (302) if authorization fails
        $this->assertTrue(in_array($response->status(), [302, 403]));

        // Task status should not have changed
        $task->refresh();
        $this->assertEquals($this->todoStatus->id, $task->status_id);
    }

    /** @test */
    public function task_toggle_preserves_other_fields(): void
    {
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'status_id' => $this->todoStatus->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'priority' => 'high',
        ]);

        $this->actingAs($this->user)
            ->patch(route('tasks.toggle-complete', $task->id));

        $task->refresh();
        $this->assertEquals('Original Title', $task->title);
        $this->assertEquals('Original Description', $task->description);
        $this->assertEquals('high', $task->priority);
        $this->assertEquals($this->completedStatus->id, $task->status_id);
    }
}
