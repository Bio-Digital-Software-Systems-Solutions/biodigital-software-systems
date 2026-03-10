<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PastoralCareTheme>
 */
class PastoralCareThemeFactory extends Factory
{
    /**
     * Default colors for themes
     */
    private array $colors = [
        '#6366f1', // indigo
        '#8b5cf6', // violet
        '#ec4899', // pink
        '#14b8a6', // teal
        '#f59e0b', // amber
        '#10b981', // emerald
        '#3b82f6', // blue
        '#ef4444', // red
    ];

    /**
     * Default icons for themes
     */
    private array $icons = [
        'heart',
        'users',
        'message-circle',
        'book-open',
        'shield',
        'star',
        'sun',
        'compass',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->randomElement($this->colors),
            'icon' => $this->faker->randomElement($this->icons),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the theme is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
