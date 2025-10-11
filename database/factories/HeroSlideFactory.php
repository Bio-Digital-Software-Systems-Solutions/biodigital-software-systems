<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'media_type' => $this->faker->randomElement(['image', 'video']),
            'media_url' => $this->faker->imageUrl(1920, 1080),
            'cta_text' => $this->faker->words(2, true),
            'cta_link' => $this->faker->url(),
            'overlay_opacity' => $this->faker->randomFloat(2, 0.3, 0.8),
            'order' => $this->faker->numberBetween(0, 10),
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
