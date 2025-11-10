<?php

namespace Database\Factories;

use App\Models\PastorAvailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PastorAvailability>
 */
class PastorAvailabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pastor_id' => User::factory(),
            'type' => 'weekly',
            'day_of_week' => $this->faker->numberBetween(1, 7), // Monday to Sunday
            'specific_date' => null,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'slot_duration' => 60,
            'is_active' => true,
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Create a weekly availability for a specific day.
     */
    public function weekly(int $dayOfWeek = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'weekly',
            'day_of_week' => $dayOfWeek ?? $this->faker->numberBetween(1, 7),
            'specific_date' => null,
        ]);
    }

    /**
     * Create a specific date availability.
     */
    public function specificDate(string $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'specific_date',
            'day_of_week' => null,
            'specific_date' => $date ?? $this->faker->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
        ]);
    }

    /**
     * Create an active availability.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive availability.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create availability with custom time range.
     */
    public function timeRange(string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Create availability with custom slot duration.
     */
    public function slotDuration(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'slot_duration' => $minutes,
        ]);
    }

    /**
     * Create morning availability (9 AM to 12 PM).
     */
    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'slot_duration' => 60,
        ]);
    }

    /**
     * Create afternoon availability (2 PM to 6 PM).
     */
    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',
            'slot_duration' => 60,
        ]);
    }
}