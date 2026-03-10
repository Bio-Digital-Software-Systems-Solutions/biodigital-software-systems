<?php

namespace Database\Factories;

use App\Models\WorkflowStep;
use App\Models\DepartmentWorkflow;
use App\Enums\Workflow\StepType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowStep>
 */
class WorkflowStepFactory extends Factory
{
    protected $model = WorkflowStep::class;

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
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'type' => StepType::ACTION,
            'order' => $this->faker->numberBetween(1, 10),
            'position_x' => $this->faker->numberBetween(100, 800),
            'position_y' => $this->faker->numberBetween(100, 600),
            'config' => [],
            'is_start' => false,
            'is_end' => false,
        ];
    }

    /**
     * Indicate that the step is a start step.
     */
    public function start(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StepType::START,
            'name' => 'Start',
            'is_start' => true,
            'order' => 0,
        ]);
    }

    /**
     * Indicate that the step is an end step.
     */
    public function end(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StepType::END,
            'name' => 'End',
            'is_end' => true,
            'order' => 100,
        ]);
    }

    /**
     * Indicate that the step is a task step.
     */
    public function task(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StepType::ACTION,
        ]);
    }

    /**
     * Indicate that the step is an approval step.
     */
    public function approval(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StepType::APPROVAL,
        ]);
    }

    /**
     * Indicate that the step is a form step.
     */
    public function form(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StepType::FORM,
        ]);
    }

    /**
     * Indicate that the step is a condition step.
     */
    public function condition(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StepType::CONDITION,
        ]);
    }

    /**
     * Indicate that the step is a notification step.
     */
    public function notification(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StepType::NOTIFICATION,
        ]);
    }
}
