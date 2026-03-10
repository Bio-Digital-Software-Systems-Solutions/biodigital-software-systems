<?php

namespace Database\Factories;

use App\Models\WorkflowTransition;
use App\Models\DepartmentWorkflow;
use App\Models\WorkflowStep;
use App\Enums\Workflow\TransitionConditionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowTransition>
 */
class WorkflowTransitionFactory extends Factory
{
    protected $model = WorkflowTransition::class;

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
            'from_step_id' => WorkflowStep::factory(),
            'to_step_id' => WorkflowStep::factory(),
            'name' => $this->faker->words(2, true),
            'condition_type' => TransitionConditionType::ALWAYS,
            'condition_config' => [],
            'priority' => $this->faker->numberBetween(1, 10),
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the transition is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the transition has a field condition.
     */
    public function fieldCondition(string $field, string $operator, mixed $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'condition_type' => TransitionConditionType::FORM_FIELD,
            'condition_config' => [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ],
        ]);
    }

    /**
     * Indicate that the transition depends on approval result.
     */
    public function approvalResult(bool $approved): static
    {
        return $this->state(fn (array $attributes): array => [
            'condition_type' => TransitionConditionType::APPROVAL_RESULT,
            'condition_config' => [
                'approved' => $approved,
            ],
        ]);
    }
}
