<?php

namespace Database\Factories;

use App\Models\NeedComment;
use App\Models\DepartmentNeed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NeedComment>
 */
class NeedCommentFactory extends Factory
{
    protected $model = NeedComment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'need_id' => DepartmentNeed::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'is_internal' => false,
            'parent_id' => null,
            'mentions' => null,
        ];
    }

    /**
     * Indicate that the comment is internal.
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_internal' => true,
        ]);
    }

    /**
     * Indicate that this is a reply to another comment.
     */
    public function reply(NeedComment $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'need_id' => $parent->need_id,
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Add mentions to the comment.
     */
    public function withMentions(array $userIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'mentions' => $userIds,
        ]);
    }
}
