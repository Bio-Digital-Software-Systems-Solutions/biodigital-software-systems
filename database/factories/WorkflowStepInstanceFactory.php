<?php

namespace Database\Factories;

use App\Models\WorkflowStepInstance;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\User;
use App\Enums\Workflow\StepInstanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowStepInstance>
 */
class WorkflowStepInstanceFactory extends Factory
{
    protected $model = WorkflowStepInstance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'workflow_instance_id' => WorkflowInstance::factory(),
            'workflow_step_id' => WorkflowStep::factory(),
            'status' => StepInstanceStatus::PENDING,
            'input_data' => [],
            'output_data' => null,
            'context' => [],
            'attempt_count' => 1,
            'max_attempts' => 3,
            'assigned_to' => null,
            'completed_by' => null,
        ];
    }

    /**
     * Indicate that the step instance is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => StepInstanceStatus::ACTIVE,
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the step instance is completed.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => StepInstanceStatus::COMPLETED,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the step instance is waiting.
     */
    public function waiting(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => StepInstanceStatus::WAITING,
        ]);
    }

    /**
     * Indicate that the step instance has failed.
     */
    public function failed(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => StepInstanceStatus::FAILED,
            'error_message' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the step instance is assigned to a user.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn(array $attributes): array => [
            'assigned_to' => $user->id,
        ]);
    }
}
