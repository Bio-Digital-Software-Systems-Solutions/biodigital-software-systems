<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProgramTaskCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $admin;

    protected Program $program;

    protected Status $status;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'create tasks']);
        Permission::create(['name' => 'view programs']);
        Permission::create(['name' => 'view tasks']);

        // Create users
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['create tasks', 'view programs', 'view tasks']);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['create tasks', 'view programs', 'view tasks']);

        // Create a program
        $this->program = Program::factory()->create([
            'user_id' => $this->admin->id,
            'name' => 'Test Program',
            'status' => 'active',
        ]);

        // Create a status
        $this->status = Status::create(['name' => 'pending', 'type' => 'task']);
    }

    /** @test */
    public function it_can_create_a_task_for_a_program()
    {
        $taskData = [
            'title' => 'New Task',
            'description' => 'Task description',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'priority' => 'medium',
            'estimated_hours' => 5,
            'notes' => 'Some notes',
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
            'assigned_to' => $this->user->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('programs.show', $this->program->id));
        $response->assertSessionHas('success', 'Task created successfully.');

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_create_task()
    {
        $taskData = [
            'title' => 'New Task',
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'priority' => 'medium',
        ];

        $response = $this->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_requires_create_tasks_permission()
    {
        $userWithoutPermission = User::factory()->create();

        $taskData = [
            'title' => 'New Task',
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'priority' => 'medium',
        ];

        $response = $this->actingAs($userWithoutPermission)
            ->post(route('tasks.store'), $taskData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), []);

        $response->assertSessionHasErrors(['title', 'priority', 'status_id', 'program_id']);
    }

    /** @test */
    public function it_validates_title_is_required()
    {
        $taskData = [
            'description' => 'Task description',
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'priority' => 'medium',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertSessionHasErrors('title');
    }

    /** @test */
    public function it_validates_priority_is_valid()
    {
        $taskData = [
            'title' => 'New Task',
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'priority' => 'invalid-priority',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertSessionHasErrors('priority');
    }

    /** @test */
    public function it_validates_program_exists()
    {
        $taskData = [
            'title' => 'New Task',
            'program_id' => 99999,
            'status_id' => $this->status->id,
            'priority' => 'medium',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertSessionHasErrors('program_id');
    }

    /** @test */
    public function it_validates_status_exists()
    {
        $taskData = [
            'title' => 'New Task',
            'program_id' => $this->program->id,
            'status_id' => 99999,
            'priority' => 'medium',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertSessionHasErrors('status_id');
    }

    /** @test */
    public function it_validates_assigned_user_exists()
    {
        $taskData = [
            'title' => 'New Task',
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'priority' => 'medium',
            'assigned_to' => 99999,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertSessionHasErrors('assigned_to');
    }

    /** @test */
    public function it_validates_due_date_is_in_future()
    {
        $taskData = [
            'title' => 'New Task',
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'priority' => 'medium',
            'due_date' => now()->subDays(1)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertSessionHasErrors('due_date');
    }

    /** @test */
    public function it_validates_estimated_hours_is_positive()
    {
        $taskData = [
            'title' => 'New Task',
            'program_id' => $this->program->id,
            'status_id' => $this->status->id,
            'priority' => 'medium',
            'estimated_hours' => -5,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertSessionHasErrors('estimated_hours');
    }

    /** @test */
    public function it_can_create_task_with_all_optional_fields()
    {
        $taskData = [
            'title' => 'Comprehensive Task',
            'description' => 'Detailed description',
            'due_date' => now()->addDays(14)->format('Y-m-d'),
            'priority' => 'high',
            'estimated_hours' => 10.5,
            'notes' => 'Important notes',
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
            'assigned_to' => $this->user->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('programs.show', $this->program->id));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Comprehensive Task',
            'description' => 'Detailed description',
            'priority' => 'high',
            'estimated_hours' => 10.5,
            'notes' => 'Important notes',
            'program_id' => $this->program->id,
            'assigned_to' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_create_task_without_optional_fields()
    {
        $taskData = [
            'title' => 'Minimal Task',
            'priority' => 'low',
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('programs.show', $this->program->id));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Minimal Task',
            'program_id' => $this->program->id,
            'description' => null,
            'assigned_to' => null,
        ]);
    }

    /** @test */
    public function it_associates_task_with_program()
    {
        $taskData = [
            'title' => 'Program Task',
            'priority' => 'medium',
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
        ];

        $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $task = Task::where('title', 'Program Task')->first();

        $this->assertNotNull($task);
        $this->assertEquals($this->program->id, $task->program_id);
        $this->assertTrue($task->program->is($this->program));
    }

    /** @test */
    public function it_can_create_multiple_tasks_for_same_program()
    {
        $task1Data = [
            'title' => 'Task 1',
            'priority' => 'high',
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
        ];

        $task2Data = [
            'title' => 'Task 2',
            'priority' => 'medium',
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
        ];

        $this->actingAs($this->user)->post(route('tasks.store'), $task1Data);
        $this->actingAs($this->user)->post(route('tasks.store'), $task2Data);

        $this->assertDatabaseHas('tasks', ['title' => 'Task 1', 'program_id' => $this->program->id]);
        $this->assertDatabaseHas('tasks', ['title' => 'Task 2', 'program_id' => $this->program->id]);

        $this->assertEquals(2, $this->program->tasks()->count());
    }

    /** @test */
    public function it_loads_program_id_from_query_parameter_in_create_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('tasks.create', ['program' => $this->program->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Create')
            ->has('programId')
            ->where('programId', (string) $this->program->id)
        );
    }

    /** @test */
    public function it_shows_all_active_programs_in_create_form()
    {
        $activeProgram = Program::factory()->create(['status' => 'active']);
        $inactiveProgram = Program::factory()->create(['status' => 'cancelled']);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.create'));

        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Create')
            ->has('programs')
        );
    }

    /** @test */
    public function it_redirects_to_program_show_after_successful_creation()
    {
        $taskData = [
            'title' => 'Test Task',
            'priority' => 'medium',
            'status_id' => $this->status->id,
            'program_id' => $this->program->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('programs.show', $this->program->id));
        $response->assertSessionHas('success');
    }
}
