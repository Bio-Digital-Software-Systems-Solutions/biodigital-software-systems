<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'type' => $this->faker->randomElement(['book', 'article', 'event']),
        ];
    }

    public function book(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'book',
        ]);
    }

    public function article(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'article',
        ]);
    }

    public function event(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'event',
        ]);
    }
}
