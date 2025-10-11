<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingClassSchedule>
 */
class TrainingClassScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $startTime = fake()->time('H:i', '18:00');

        return [
            'training_class_id' => \App\Models\TrainingClass::factory(),
            'day_of_week' => fake()->randomElement($days),
            'start_time' => $startTime,
            'end_time' => fake()->time('H:i', '22:00'),
            'room' => fake()->optional()->bothify('Salle ##?'),
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }
}
