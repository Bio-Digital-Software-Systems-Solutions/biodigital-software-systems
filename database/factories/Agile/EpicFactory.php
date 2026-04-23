<?php

namespace Database\Factories\Agile;

use App\Enums\Agile\EpicStatus;
use App\Models\Agile\Epic;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Epic>
 */
class EpicFactory extends Factory
{
    protected $model = Epic::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'owner_id' => User::factory(),
            'title' => ucfirst(fake()->words(4, true)),
            'description' => fake()->paragraph(),
            'business_value' => fake()->sentence(),
            'status' => EpicStatus::DRAFT,
            'priority' => fake()->numberBetween(1, 5),
            'target_date' => fake()->optional()->dateTimeBetween('+1 month', '+6 months'),
            'labels' => null,
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attrs): array => ['project_id' => $project->id]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => EpicStatus::DRAFT]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => EpicStatus::READY]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => EpicStatus::IN_PROGRESS]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => EpicStatus::DONE]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => EpicStatus::ARCHIVED]);
    }
}
