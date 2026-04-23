<?php

namespace Database\Factories\Agile;

use App\Enums\Agile\AcceptanceCriterionStatus;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\UserStory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcceptanceCriterion>
 */
class AcceptanceCriterionFactory extends Factory
{
    protected $model = AcceptanceCriterion::class;

    public function definition(): array
    {
        return [
            'user_story_id' => UserStory::factory(),
            'position' => 1,
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->paragraph(),
            'status' => AcceptanceCriterionStatus::PENDING,
            'validated_by' => null,
            'validated_at' => null,
            'validation_notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attrs): array => [
            'status' => AcceptanceCriterionStatus::PENDING,
            'validated_by' => null,
            'validated_at' => null,
        ]);
    }

    public function inReview(): static
    {
        return $this->state(fn (array $attrs): array => [
            'status' => AcceptanceCriterionStatus::IN_REVIEW,
        ]);
    }

    public function validated(?User $by = null): static
    {
        return $this->state(fn (array $attrs): array => [
            'status' => AcceptanceCriterionStatus::VALIDATED,
            'validated_by' => $by?->id ?? User::factory(),
            'validated_at' => now(),
        ]);
    }

    public function rejected(?User $by = null): static
    {
        return $this->state(fn (array $attrs): array => [
            'status' => AcceptanceCriterionStatus::REJECTED,
            'validated_by' => $by?->id ?? User::factory(),
            'validated_at' => now(),
            'validation_notes' => fake()->sentence(),
        ]);
    }

    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attrs): array => ['position' => $position]);
    }
}
