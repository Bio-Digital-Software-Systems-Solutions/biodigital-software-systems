<?php

namespace Database\Factories;

use App\Models\WorkflowActivityLog;
use App\Models\WorkflowInstance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowActivityLog>
 */
class WorkflowActivityLogFactory extends Factory
{
    protected $model = WorkflowActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['started', 'paused', 'resumed', 'completed', 'cancelled', 'step_started', 'step_completed'];

        return [
            'workflow_instance_id' => WorkflowInstance::factory(),
            'step_instance_id' => null,
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement($actions),
            'entity_type' => WorkflowInstance::class,
            'entity_id' => $this->faker->numberBetween(1, 100),
            'old_values' => null,
            'new_values' => null,
            'metadata' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Indicate that the log is for a started action.
     */
    public function started(): static
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'started',
            'new_values' => ['status' => 'active'],
        ]);
    }

    /**
     * Indicate that the log is for a completed action.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'completed',
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'completed'],
        ]);
    }

    /**
     * Indicate that the log is for a paused action.
     */
    public function paused(): static
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'paused',
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'paused'],
        ]);
    }

    /**
     * Indicate that the log is for a step action.
     */
    public function stepAction(): static
    {
        return $this->state(fn(array $attributes) => [
            'action' => $this->faker->randomElement(['step_started', 'step_completed']),
            'entity_type' => 'App\\Models\\WorkflowStepInstance',
        ]);
    }

    /**
     * Indicate that the log is for a cancelled action.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'cancelled',
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'cancelled'],
        ]);
    }

    /**
     * Include metadata.
     */
    public function withMetadata(array $metadata = []): static
    {
        return $this->state(fn(array $attributes) => [
            'metadata' => $metadata ?: ['extra_info' => $this->faker->sentence()],
        ]);
    }
}
