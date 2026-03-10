<?php

namespace Database\Factories;

use App\Models\WorkflowInstance;
use App\Models\DepartmentWorkflow;
use App\Models\Department;
use App\Models\User;
use App\Enums\Workflow\WorkflowInstanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowInstance>
 */
class WorkflowInstanceFactory extends Factory
{
    protected $model = WorkflowInstance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'workflow_id' => DepartmentWorkflow::factory(),
            'department_id' => Department::factory(),
            'started_by' => User::factory(),
            'name' => null,
            'status' => WorkflowInstanceStatus::PENDING,
            'context' => [],
            'input_data' => [],
            'output_data' => null,
            'cancellation_reason' => null,
            'failure_reason' => null,
            'started_at' => now(),
        ];
    }

    /**
     * Indicate that the instance is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => WorkflowInstanceStatus::ACTIVE,
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the instance is paused.
     */
    public function paused(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => WorkflowInstanceStatus::PAUSED,
        ]);
    }

    /**
     * Indicate that the instance is completed.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => WorkflowInstanceStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the instance is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => WorkflowInstanceStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Indicate that the instance has failed.
     */
    public function failed(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => WorkflowInstanceStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' => $this->faker->sentence(),
        ]);
    }
}
