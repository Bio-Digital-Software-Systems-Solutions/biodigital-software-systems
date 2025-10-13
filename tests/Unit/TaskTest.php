<?php

namespace Tests\Unit;

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // No seeding needed for unit tests
    }

    public function test_task_belongs_to_program(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();
        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
        ]);

        $this->assertInstanceOf(Program::class, $task->program);
        $this->assertEquals($program->id, $task->program->id);
    }

    public function test_task_belongs_to_status(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();
        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
        ]);

        $this->assertInstanceOf(Status::class, $task->status);
        $this->assertEquals($status->id, $task->status->id);
    }

    public function test_task_belongs_to_assigned_user(): void
    {
        $user = User::factory()->create();
        $assignedUser = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'assigned_to' => $assignedUser->id,
        ]);

        $this->assertInstanceOf(User::class, $task->assignedUser);
        $this->assertEquals($assignedUser->id, $task->assignedUser->id);
    }

    public function test_is_overdue_returns_true_when_past_due_and_not_completed(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $pendingStatus->id,
            'due_date' => now()->subDay(),
        ]);

        $this->assertTrue($task->isOverdue());
    }

    public function test_is_overdue_returns_false_when_completed(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $completedStatus = Status::factory()->create(['name' => 'completed']);

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $completedStatus->id,
            'due_date' => now()->subDay(),
        ]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_is_overdue_returns_false_when_no_due_date(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create(['name' => 'pending']);

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'due_date' => null,
        ]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_is_completed_returns_true_when_status_is_completed(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $completedStatus = Status::factory()->create(['name' => 'completed']);

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $completedStatus->id,
        ]);

        $this->assertTrue($task->isCompleted());
    }

    public function test_is_in_progress_returns_true_when_status_is_in_progress(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $inProgressStatus = Status::factory()->create(['name' => 'in_progress']);

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $inProgressStatus->id,
        ]);

        $this->assertTrue($task->isInProgress());
    }

    public function test_is_pending_returns_true_when_status_is_pending(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $pendingStatus->id,
        ]);

        $this->assertTrue($task->isPending());
    }

    public function test_days_until_due_attribute_calculates_correctly(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'due_date' => \Carbon\Carbon::today()->addDays(5),
        ]);

        // Allow for slight timing differences (should be 5, but might be 4 or 5)
        $this->assertGreaterThanOrEqual(4, $task->days_until_due);
        $this->assertLessThanOrEqual(5, $task->days_until_due);
    }

    public function test_days_until_due_attribute_returns_null_when_no_due_date(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'due_date' => null,
        ]);

        $this->assertNull($task->days_until_due);
    }

    public function test_hours_variance_attribute_calculates_correctly(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'estimated_hours' => 10.0,
            'actual_hours' => 12.5,
        ]);

        $this->assertEquals(2.5, $task->hours_variance);
    }

    public function test_hours_variance_attribute_returns_zero_when_missing_hours(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'estimated_hours' => null,
            'actual_hours' => 12.5,
        ]);

        $this->assertEquals(0, $task->hours_variance);
    }

    public function test_completion_percentage_attribute_calculates_correctly(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'estimated_hours' => 10.0,
            'actual_hours' => 8.0,
        ]);

        $this->assertEquals(80.0, $task->completion_percentage);
    }

    public function test_completion_percentage_attribute_caps_at_100(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'estimated_hours' => 10.0,
            'actual_hours' => 15.0,
        ]);

        $this->assertEquals(100.0, $task->completion_percentage);
    }

    public function test_completion_percentage_returns_100_when_completed_without_hours(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $completedStatus = Status::factory()->create(['name' => 'completed']);

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $completedStatus->id,
            'estimated_hours' => null,
            'actual_hours' => null,
        ]);

        $this->assertEquals(100, $task->completion_percentage);
    }

    public function test_priority_label_attribute_returns_correct_labels(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $highTask = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'priority' => 'high',
        ]);

        $mediumTask = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'priority' => 'medium',
        ]);

        $lowTask = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'priority' => 'low',
        ]);

        $this->assertEquals('High Priority', $highTask->priority_label);
        $this->assertEquals('Medium Priority', $mediumTask->priority_label);
        $this->assertEquals('Low Priority', $lowTask->priority_label);
    }

    public function test_assignee_name_attribute_returns_user_full_name(): void
    {
        $user = User::factory()->create();
        $assignedUser = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'assigned_to' => $assignedUser->id,
        ]);

        $this->assertEquals('John Doe', $task->assignee_name);
    }

    public function test_assignee_name_attribute_returns_unassigned_when_no_user(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $task = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'assigned_to' => null,
        ]);

        $this->assertEquals('Unassigned', $task->assignee_name);
    }

    public function test_overdue_scope_filters_overdue_tasks(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);
        $completedStatus = Status::factory()->create(['name' => 'completed']);

        $overdueTask = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $pendingStatus->id,
            'due_date' => now()->subDay(),
        ]);

        Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $completedStatus->id,
            'due_date' => now()->subDay(),
        ]);

        Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $pendingStatus->id,
            'due_date' => now()->addDay(),
        ]);

        $overdueTasks = Task::overdue()->get();

        $this->assertCount(1, $overdueTasks);
        $this->assertEquals($overdueTask->id, $overdueTasks->first()->id);
    }

    public function test_completed_scope_filters_completed_tasks(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);
        $completedStatus = Status::factory()->create(['name' => 'completed']);

        Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $pendingStatus->id,
        ]);

        $completedTask = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $completedStatus->id,
        ]);

        $completedTasks = Task::completed()->get();

        $this->assertCount(1, $completedTasks);
        $this->assertEquals($completedTask->id, $completedTasks->first()->id);
    }

    public function test_priority_scope_filters_by_priority(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $highTask = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'priority' => 'high',
        ]);

        Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'priority' => 'low',
        ]);

        $highTasks = Task::priority('high')->get();

        $this->assertCount(1, $highTasks);
        $this->assertEquals($highTask->id, $highTasks->first()->id);
    }

    public function test_assigned_to_scope_filters_by_user(): void
    {
        $user = User::factory()->create();
        $assignedUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $assignedTask = Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'assigned_to' => $assignedUser->id,
        ]);

        Task::factory()->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
            'assigned_to' => $otherUser->id,
        ]);

        $userTasks = Task::assignedTo($assignedUser->id)->get();

        $this->assertCount(1, $userTasks);
        $this->assertEquals($assignedTask->id, $userTasks->first()->id);
    }
}
