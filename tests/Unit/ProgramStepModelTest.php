<?php

namespace Tests\Unit;

use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramStep;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramStepModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_step_belongs_to_program(): void
    {
        $program = Program::factory()->create();
        $step = ProgramStep::factory()->create([
            'program_id' => $program->id,
        ]);

        $this->assertInstanceOf(Program::class, $step->program);
        $this->assertEquals($program->id, $step->program->id);
    }

    public function test_program_step_has_many_tasks(): void
    {
        $step = ProgramStep::factory()->create();
        Task::factory()->count(3)->create([
            'program_step_id' => $step->id,
        ]);

        $this->assertCount(3, $step->tasks);
        $this->assertInstanceOf(Task::class, $step->tasks->first());
    }

    public function test_program_step_has_many_participants(): void
    {
        $step = ProgramStep::factory()->create();
        $participants = Participant::factory()->count(2)->create();

        foreach ($participants as $participant) {
            $step->participants()->attach($participant->id, [
                'role_in_step' => 'Developer',
            ]);
        }

        $this->assertCount(2, $step->participants);
        $this->assertInstanceOf(Participant::class, $step->participants->first());
    }

    public function test_is_completed_method_returns_true_for_completed_step(): void
    {
        $step = ProgramStep::factory()->create(['status' => 'completed']);

        $this->assertTrue($step->isCompleted());
    }

    public function test_is_in_progress_method_returns_true_for_in_progress_step(): void
    {
        $step = ProgramStep::factory()->create(['status' => 'in_progress']);

        $this->assertTrue($step->isInProgress());
    }

    public function test_is_pending_method_returns_true_for_pending_step(): void
    {
        $step = ProgramStep::factory()->create(['status' => 'pending']);

        $this->assertTrue($step->isPending());
    }

    public function test_ordered_scope_returns_steps_ordered_by_index(): void
    {
        $program = Program::factory()->create();

        ProgramStep::factory()->create(['program_id' => $program->id, 'order_index' => 3]);
        ProgramStep::factory()->create(['program_id' => $program->id, 'order_index' => 1]);
        ProgramStep::factory()->create(['program_id' => $program->id, 'order_index' => 2]);

        $orderedSteps = ProgramStep::ordered()->get();

        $this->assertEquals(1, $orderedSteps->first()->order_index);
        $this->assertEquals(3, $orderedSteps->last()->order_index);
    }
}
