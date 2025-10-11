<?php

namespace Tests\Unit;

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_program_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $program->user);
        $this->assertEquals($user->id, $program->user->id);
    }

    public function test_program_has_many_tasks(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        $tasks = Task::factory()->count(3)->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
        ]);

        $this->assertCount(3, $program->tasks);
        $this->assertInstanceOf(Task::class, $program->tasks->first());
    }

    public function test_total_tasks_attribute_counts_correctly(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $status = Status::factory()->create();

        Task::factory()->count(5)->create([
            'program_id' => $program->id,
            'status_id' => $status->id,
        ]);

        $this->assertEquals(5, $program->total_tasks);
    }

    public function test_completed_tasks_attribute_counts_correctly(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);
        $completedStatus = Status::factory()->create(['name' => 'completed']);

        Task::factory()->count(3)->create([
            'program_id' => $program->id,
            'status_id' => $completedStatus->id,
        ]);

        Task::factory()->count(2)->create([
            'program_id' => $program->id,
            'status_id' => $pendingStatus->id,
        ]);

        $this->assertEquals(3, $program->completed_tasks);
    }

    public function test_pending_tasks_attribute_counts_correctly(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);
        $completedStatus = Status::factory()->create(['name' => 'completed']);

        Task::factory()->count(4)->create([
            'program_id' => $program->id,
            'status_id' => $pendingStatus->id,
        ]);

        Task::factory()->count(2)->create([
            'program_id' => $program->id,
            'status_id' => $completedStatus->id,
        ]);

        $this->assertEquals(4, $program->pending_tasks);
    }

    public function test_in_progress_tasks_attribute_counts_correctly(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);
        $inProgressStatus = Status::factory()->create(['name' => 'in_progress']);

        Task::factory()->count(2)->create([
            'program_id' => $program->id,
            'status_id' => $inProgressStatus->id,
        ]);

        Task::factory()->count(3)->create([
            'program_id' => $program->id,
            'status_id' => $pendingStatus->id,
        ]);

        $this->assertEquals(2, $program->in_progress_tasks);
    }

    public function test_actual_progress_attribute_calculates_correctly(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);
        $pendingStatus = Status::factory()->create(['name' => 'pending']);
        $completedStatus = Status::factory()->create(['name' => 'completed']);

        Task::factory()->count(3)->create([
            'program_id' => $program->id,
            'status_id' => $completedStatus->id,
        ]);

        Task::factory()->count(2)->create([
            'program_id' => $program->id,
            'status_id' => $pendingStatus->id,
        ]);

        // 3 completed out of 5 total = 60%
        $this->assertEquals(60.0, $program->actual_progress);
    }

    public function test_actual_progress_attribute_returns_zero_when_no_tasks(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(0, $program->actual_progress);
    }

    public function test_duration_in_days_attribute_calculates_correctly(): void
    {
        $user = User::factory()->create();
        $startDate = now();
        $endDate = now()->addDays(30);

        $program = Program::factory()->create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $this->assertEquals(30, $program->duration_in_days);
    }

    public function test_is_overdue_returns_true_when_past_end_date_and_not_completed(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'end_date' => now()->subDay(),
        ]);

        $this->assertTrue($program->isOverdue());
    }

    public function test_is_overdue_returns_false_when_completed(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'end_date' => now()->subDay(),
        ]);

        $this->assertFalse($program->isOverdue());
    }

    public function test_is_overdue_returns_false_when_not_past_end_date(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'end_date' => now()->addDay(),
        ]);

        $this->assertFalse($program->isOverdue());
    }

    public function test_is_active_returns_true_for_active_statuses(): void
    {
        $user = User::factory()->create();

        $activeProgram = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $inProgressProgram = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
        ]);

        $this->assertTrue($activeProgram->isActive());
        $this->assertTrue($inProgressProgram->isActive());
    }

    public function test_is_active_returns_false_for_inactive_statuses(): void
    {
        $user = User::factory()->create();

        $completedProgram = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $cancelledProgram = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $this->assertFalse($completedProgram->isActive());
        $this->assertFalse($cancelledProgram->isActive());
    }

    public function test_remaining_budget_attribute_returns_budget(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create([
            'user_id' => $user->id,
            'budget' => 50000.00,
        ]);

        // Currently just returns budget as placeholder
        $this->assertEquals(50000.00, $program->remaining_budget);
    }

    public function test_priority_label_attribute_returns_correct_labels(): void
    {
        $user = User::factory()->create();

        $highProgram = Program::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
        ]);

        $mediumProgram = Program::factory()->create([
            'user_id' => $user->id,
            'priority' => 'medium',
        ]);

        $lowProgram = Program::factory()->create([
            'user_id' => $user->id,
            'priority' => 'low',
        ]);

        $this->assertEquals('High Priority', $highProgram->priority_label);
        $this->assertEquals('Medium Priority', $mediumProgram->priority_label);
        $this->assertEquals('Low Priority', $lowProgram->priority_label);
    }

    public function test_active_scope_filters_active_programs(): void
    {
        $user = User::factory()->create();

        $activeProgram = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $inProgressProgram = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
        ]);

        Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $activePrograms = Program::active()->get();

        $this->assertCount(2, $activePrograms);
        $this->assertTrue($activePrograms->contains($activeProgram));
        $this->assertTrue($activePrograms->contains($inProgressProgram));
    }

    public function test_completed_scope_filters_completed_programs(): void
    {
        $user = User::factory()->create();

        Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $completedProgram = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $completedPrograms = Program::completed()->get();

        $this->assertCount(1, $completedPrograms);
        $this->assertEquals($completedProgram->id, $completedPrograms->first()->id);
    }

    public function test_overdue_scope_filters_overdue_programs(): void
    {
        $user = User::factory()->create();

        $overdueProgram = Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'end_date' => now()->subDay(),
        ]);

        Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'end_date' => now()->subDay(),
        ]);

        Program::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'end_date' => now()->addDay(),
        ]);

        $overduePrograms = Program::overdue()->get();

        $this->assertCount(1, $overduePrograms);
        $this->assertEquals($overdueProgram->id, $overduePrograms->first()->id);
    }

    public function test_priority_scope_filters_by_priority(): void
    {
        $user = User::factory()->create();

        $highProgram = Program::factory()->create([
            'user_id' => $user->id,
            'priority' => 'high',
        ]);

        Program::factory()->create([
            'user_id' => $user->id,
            'priority' => 'low',
        ]);

        $highPrograms = Program::priority('high')->get();

        $this->assertCount(1, $highPrograms);
        $this->assertEquals($highProgram->id, $highPrograms->first()->id);
    }
}
