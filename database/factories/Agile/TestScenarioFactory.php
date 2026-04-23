<?php

namespace Database\Factories\Agile;

use App\Enums\Agile\TestScenarioExecutionStatus;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\TestScenario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestScenario>
 */
class TestScenarioFactory extends Factory
{
    protected $model = TestScenario::class;

    public function definition(): array
    {
        return [
            'acceptance_criterion_id' => AcceptanceCriterion::factory(),
            'title' => ucfirst(fake()->words(4, true)),
            'given' => 'un contexte initial',
            'when' => 'une action se produit',
            'then' => 'un résultat est observé',
            'free_form' => null,
            'automated_test_ref' => null,
            'execution_status' => TestScenarioExecutionStatus::NOT_RUN,
            'last_executed_by' => null,
            'last_executed_at' => null,
            'failure_notes' => null,
        ];
    }

    public function gherkin(): static
    {
        return $this->state(fn (array $attrs): array => [
            'given' => 'un contexte initial',
            'when' => 'une action se produit',
            'then' => 'un résultat est observé',
            'free_form' => null,
        ]);
    }

    public function freeForm(): static
    {
        return $this->state(fn (array $attrs): array => [
            'given' => null,
            'when' => null,
            'then' => null,
            'free_form' => fake()->paragraph(),
        ]);
    }

    public function passed(?User $by = null): static
    {
        return $this->state(fn (array $attrs): array => [
            'execution_status' => TestScenarioExecutionStatus::PASSED,
            'last_executed_by' => $by?->id ?? User::factory(),
            'last_executed_at' => now(),
        ]);
    }

    public function failed(?User $by = null): static
    {
        return $this->state(fn (array $attrs): array => [
            'execution_status' => TestScenarioExecutionStatus::FAILED,
            'last_executed_by' => $by?->id ?? User::factory(),
            'last_executed_at' => now(),
            'failure_notes' => fake()->sentence(),
        ]);
    }

    public function blocked(?User $by = null): static
    {
        return $this->state(fn (array $attrs): array => [
            'execution_status' => TestScenarioExecutionStatus::BLOCKED,
            'last_executed_by' => $by?->id ?? User::factory(),
            'last_executed_at' => now(),
        ]);
    }
}
