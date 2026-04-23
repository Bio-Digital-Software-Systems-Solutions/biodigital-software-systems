<?php

namespace Database\Factories\Agile;

use App\Enums\Agile\UserStoryStatus;
use App\Models\Agile\Epic;
use App\Models\Agile\UserStory;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserStory>
 */
class UserStoryFactory extends Factory
{
    protected $model = UserStory::class;

    public function definition(): array
    {
        return [
            'epic_id' => null,
            'sprint_id' => null,
            'assignee_id' => null,
            'reporter_id' => User::factory(),
            'title' => ucfirst(fake()->words(5, true)),
            'as_a' => fake()->randomElement(['utilisateur', 'administrateur', 'visiteur', 'membre']),
            'i_want' => fake()->sentence(),
            'so_that' => fake()->sentence(),
            'story_points' => fake()->randomElement([null, 1, 2, 3, 5, 8, 13]),
            'priority' => fake()->numberBetween(1, 5),
            'status' => UserStoryStatus::BACKLOG,
            'completed_at' => null,
        ];
    }

    public function forEpic(Epic $epic): static
    {
        return $this->state(fn (array $attrs): array => ['epic_id' => $epic->id]);
    }

    public function inBacklog(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => UserStoryStatus::BACKLOG]);
    }

    public function inSprint(Sprint $sprint): static
    {
        return $this->state(fn (array $attrs): array => ['sprint_id' => $sprint->id]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => UserStoryStatus::READY]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => UserStoryStatus::IN_PROGRESS]);
    }

    public function inReview(): static
    {
        return $this->state(fn (array $attrs): array => ['status' => UserStoryStatus::REVIEW]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attrs): array => [
            'status' => UserStoryStatus::DONE,
            'completed_at' => now(),
        ]);
    }
}
