<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ProgramStep;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProgramStepManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $program;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view programs']);
        Permission::create(['name' => 'create programs']);
        Permission::create(['name' => 'edit programs']);
        Permission::create(['name' => 'delete programs']);

        // Create user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['view programs', 'create programs', 'edit programs', 'delete programs']);

        // Create a program
        $this->program = Program::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_create_a_program_step(): void
    {
        $stepData = [
            'name' => 'Phase de Planification',
            'description' => 'Définition des objectifs',
            'order_index' => 1,
            'start_datetime' => now()->toDateTimeString(),
            'end_datetime' => now()->addDays(14)->toDateTimeString(),
            'duration_minutes' => 168 * 60,
            'status' => 'pending',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('programs.steps.store', $this->program), $stepData);

        $response->assertRedirect();
        $this->assertDatabaseHas('program_steps', [
            'program_id' => $this->program->id,
            'name' => 'Phase de Planification',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function user_can_update_a_program_step(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
            'name' => 'Original Name',
            'status' => 'pending',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'order_index' => 1,
            'start_datetime' => $step->start_datetime->toDateTimeString(),
            'end_datetime' => $step->end_datetime->toDateTimeString(),
            'duration_minutes' => 120,
            'status' => 'in_progress',
        ];

        $response = $this->actingAs($this->user)
            ->patch(route('programs.steps.update', ['program' => $this->program, 'step' => $step]), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('program_steps', [
            'id' => $step->id,
            'name' => 'Updated Name',
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function user_can_delete_a_program_step(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('programs.steps.destroy', ['program' => $this->program, 'step' => $step]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('program_steps', [
            'id' => $step->id,
        ]);
    }

    /** @test */
    public function user_can_attach_participant_to_step(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
        ]);

        $participant = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('programs.steps.participants.attach', ['program' => $this->program, 'step' => $step]), [
                'user_id' => $participant->id,
                'role_in_step' => 'Chef de projet',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('program_step_users', [
            'program_step_id' => $step->id,
            'user_id' => $participant->id,
            'role_in_step' => 'Chef de projet',
        ]);
    }

    /** @test */
    public function user_can_detach_participant_from_step(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
        ]);

        $participant = User::factory()->create();
        $step->users()->attach($participant->id, ['role_in_step' => 'Analyste']);

        $response = $this->actingAs($this->user)
            ->delete(route('programs.steps.participants.detach', [
                'program' => $this->program,
                'step' => $step,
                'user' => $participant->id,
            ]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('program_step_users', [
            'program_step_id' => $step->id,
            'user_id' => $participant->id,
        ]);
    }

    /** @test */
    public function user_can_create_task_for_step(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
        ]);

        $assignee = User::factory()->create();

        $taskData = [
            'title' => 'Définir les objectifs',
            'description' => 'Description de la tâche',
            'due_date' => now()->addDays(7)->toDateTimeString(),
            'priority' => 'high',
            'estimated_hours' => 8,
            'assigned_to' => $assignee->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('programs.steps.tasks.store', ['program' => $this->program, 'step' => $step]), $taskData);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'program_id' => $this->program->id,
            'program_step_id' => $step->id,
            'title' => 'Définir les objectifs',
            'priority' => 'high',
            'assigned_to' => $assignee->id,
        ]);
    }

    /** @test */
    public function user_can_update_task_in_step(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
        ]);

        $status = Status::factory()->create(['name' => 'todo']);
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'program_step_id' => $step->id,
            'status_id' => $status->id,
            'title' => 'Original Task',
        ]);

        $updateData = [
            'title' => 'Updated Task Title',
            'description' => 'Updated description',
            'due_date' => now()->addDays(10)->toDateTimeString(),
            'priority' => 'medium',
            'estimated_hours' => 12,
            'actual_hours' => 8,
            'assigned_to' => $this->user->id,
            'status_id' => $status->id,
        ];

        $response = $this->actingAs($this->user)
            ->patch(route('programs.steps.tasks.update', [
                'program' => $this->program,
                'step' => $step,
                'task' => $task,
            ]), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'priority' => 'medium',
        ]);
    }

    /** @test */
    public function user_can_delete_task_from_step(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
        ]);

        $status = Status::factory()->create();
        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'program_step_id' => $step->id,
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('programs.steps.tasks.destroy', [
                'program' => $this->program,
                'step' => $step,
                'task' => $task,
            ]));

        $response->assertRedirect();
        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    /** @test */
    public function user_can_update_task_status(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
        ]);

        $statusTodo = Status::factory()->create(['name' => 'todo']);
        $statusInProgress = Status::factory()->create(['name' => 'in_progress']);

        $task = Task::factory()->create([
            'program_id' => $this->program->id,
            'program_step_id' => $step->id,
            'status_id' => $statusTodo->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('programs.steps.tasks.update-status', [
                'program' => $this->program,
                'step' => $step,
                'task' => $task,
            ]), [
                'status_id' => $statusInProgress->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status_id' => $statusInProgress->id,
        ]);
    }

    /** @test */
    public function program_progress_updates_when_step_status_changes(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->patch(route('programs.steps.update', ['program' => $this->program, 'step' => $step]), [
                'name' => $step->name,
                'description' => $step->description,
                'order_index' => $step->order_index,
                'start_datetime' => $step->start_datetime->toDateTimeString(),
                'end_datetime' => $step->end_datetime->toDateTimeString(),
                'duration_minutes' => $step->duration_minutes,
                'status' => 'completed',
            ]);

        $this->program->refresh();
        $this->assertNotNull($this->program->progress_percentage);
    }

    /** @test */
    public function validation_fails_when_creating_step_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('programs.steps.store', $this->program), [
                'name' => '', // Required field
                'status' => 'invalid_status',
            ]);

        $response->assertSessionHasErrors(['name', 'status']);
    }

    /** @test */
    public function validation_fails_when_creating_task_with_invalid_priority(): void
    {
        $step = ProgramStep::factory()->create([
            'program_id' => $this->program->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('programs.steps.tasks.store', ['program' => $this->program, 'step' => $step]), [
                'title' => 'Test Task',
                'priority' => 'invalid_priority',
            ]);

        $response->assertSessionHasErrors(['priority']);
    }
}
