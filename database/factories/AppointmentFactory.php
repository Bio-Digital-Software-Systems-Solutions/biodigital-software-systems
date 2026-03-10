<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDateTime = $this->faker->dateTimeBetween('now', '+2 months');
        $endDateTime = (clone $startDateTime)->modify('+'.$this->faker->numberBetween(30, 180).' minutes');

        return [
            'uuid' => (string) Str::uuid(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
            'location' => $this->faker->optional(0.6)->address(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
            'type' => $this->faker->randomElement(['individual', 'group', 'consultation', 'meeting']),
            'visibility' => $this->faker->randomElement(['public', 'private']),
            'user_id' => User::factory(),
            'appointmentable_type' => null,
            'appointmentable_id' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the appointment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the appointment is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the appointment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the appointment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the appointment is individual.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'individual',
        ]);
    }

    /**
     * Indicate that the appointment is a group meeting.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'group',
        ]);
    }

    /**
     * Indicate that the appointment is a consultation.
     */
    public function consultation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'consultation',
        ]);
    }

    /**
     * Indicate that the appointment is a meeting.
     */
    public function meeting(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'meeting',
        ]);
    }

    /**
     * Create an appointment in the past.
     */
    public function past(): static
    {
        $startDateTime = $this->faker->dateTimeBetween('-2 months', '-1 day');
        $endDateTime = (clone $startDateTime)->modify('+'.$this->faker->numberBetween(30, 180).' minutes');

        return $this->state(fn (array $attributes): array => [
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
            'status' => $this->faker->randomElement(['completed', 'cancelled']),
        ]);
    }

    /**
     * Create an appointment today.
     */
    public function today(): static
    {
        $hour = $this->faker->numberBetween(9, 17);
        $minute = $this->faker->randomElement([0, 15, 30, 45]);
        $startDateTime = Carbon::today()->setTime($hour, $minute);
        $endDateTime = (clone $startDateTime)->addMinutes($this->faker->numberBetween(30, 180));

        return $this->state(fn (array $attributes): array => [
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
        ]);
    }

    /**
     * Create an appointment in the future.
     */
    public function future(): static
    {
        $startDateTime = $this->faker->dateTimeBetween('+1 day', '+2 months');
        $endDateTime = (clone $startDateTime)->modify('+'.$this->faker->numberBetween(30, 180).' minutes');

        return $this->state(fn (array $attributes): array => [
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
            'status' => $this->faker->randomElement(['pending', 'confirmed']),
        ]);
    }

    /**
     * Create an appointment with specific duration in minutes.
     */
    public function duration(int $minutes): static
    {
        return $this->state(function (array $attributes) use ($minutes): array {
            $startDateTime = isset($attributes['start_datetime'])
                ? Carbon::parse($attributes['start_datetime'])
                : $this->faker->dateTimeBetween('now', '+2 months');

            return [
                'start_datetime' => $startDateTime,
                'end_datetime' => (clone $startDateTime)->addMinutes($minutes),
            ];
        });
    }

    /**
     * Create an appointment with metadata.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes): array => [
            'metadata' => json_encode($metadata),
        ]);
    }

    /**
     * Create an appointment owned by a specific user.
     */
    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create an appointment at a specific time.
     */
    public function at(string $date, string $time): static
    {
        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time);
        $endDateTime = (clone $startDateTime)->addHour();

        return $this->state(fn (array $attributes): array => [
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
        ]);
    }
}
