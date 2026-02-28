<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Event\EventProgramme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event\EventProgramme>
 */
class EventProgrammeFactory extends Factory
{
    protected $model = EventProgramme::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'uploaded_by' => User::factory(),
            'file_path' => 'events/programmes/'.fake()->uuid().'.pdf',
            'file_name' => fake()->word().'-programme.pdf',
            'file_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 10000000),
            'is_active' => true,
        ];
    }

    /**
     * State with a valid share token (24h).
     */
    public function withShareToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'share_token' => bin2hex(random_bytes(32)),
            'share_token_expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * State with an expired share token.
     */
    public function withExpiredToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'share_token' => bin2hex(random_bytes(32)),
            'share_token_expires_at' => now()->subHour(),
        ]);
    }

    /**
     * State for an image programme (PNG/JPEG).
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'events/programmes/'.fake()->uuid().'.jpg',
            'file_name' => fake()->word().'-programme.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(100000, 5000000),
        ]);
    }
}
