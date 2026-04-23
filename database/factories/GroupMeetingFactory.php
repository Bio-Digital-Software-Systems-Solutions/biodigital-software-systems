<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupMeeting>
 */
class GroupMeetingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'appointment_id' => Appointment::factory(),
            'created_by' => User::factory(),
            'notify_all_members' => fake()->boolean(70),
            'is_mandatory' => fake()->boolean(30),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function mandatory(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_mandatory' => true,
        ]);
    }

    public function notified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notified_at' => now(),
        ]);
    }
}
