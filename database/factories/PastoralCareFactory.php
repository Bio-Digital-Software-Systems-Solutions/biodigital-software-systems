<?php

namespace Database\Factories;

use App\Models\PastoralCare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PastoralCare>
 */
class PastoralCareFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PastoralCare::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $appointmentDate = $this->faker->dateTimeBetween('now', '+3 months');
        $appointmentDateTime = Carbon::instance($appointmentDate)
            ->setHour($this->faker->numberBetween(9, 16))
            ->setMinute($this->faker->randomElement([0, 30]));

        return [
            'pastor_id' => User::factory()->create()->assignRole('pastor'),
            'client_name' => $this->faker->name(),
            'client_email' => $this->faker->unique()->safeEmail(),
            'client_phone' => $this->faker->optional()->phoneNumber(),
            'appointment_date' => $appointmentDateTime->toDateString(),
            'appointment_time' => $appointmentDateTime,
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'location_type' => $this->faker->randomElement(['in_person', 'zoom', 'hybrid']),
            'zoom_link' => function (array $attributes): ?string {
                if (in_array($attributes['location_type'], ['zoom', 'hybrid'])) {
                    return 'https://zoom.us/j/' . $this->faker->numerify('#########');
                }
                return null;
            },
            'notes' => $this->faker->optional()->paragraph(),
            'pastor_notes' => $this->faker->optional()->paragraphs(2, true),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
            'cancellation_reason' => function (array $attributes) {
                if ($attributes['status'] === 'cancelled') {
                    return $this->faker->sentence();
                }
                return null;
            },
            'cancelled_at' => function (array $attributes) {
                if ($attributes['status'] === 'cancelled') {
                    return $this->faker->dateTimeBetween('-1 month', 'now');
                }
                return null;
            },
            'confirmation_sent_at' => function (array $attributes) {
                if (in_array($attributes['status'], ['confirmed', 'completed'])) {
                    return $this->faker->dateTimeBetween('-1 week', 'now');
                }
                return null;
            },
            'reminder_sent_at' => function (array $attributes) {
                if (in_array($attributes['status'], ['confirmed', 'completed']) && $this->faker->boolean(50)) {
                    return $this->faker->dateTimeBetween('-3 days', 'now');
                }
                return null;
            },
        ];
    }

    /**
     * Indicate that the appointment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'confirmation_sent_at' => null,
        ]);
    }

    /**
     * Indicate that the appointment is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'confirmation_sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the appointment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'confirmation_sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'appointment_date' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    /**
     * Indicate that the appointment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
            'cancelled_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'cancellation_reason' => $this->faker->sentence(),
            'confirmation_sent_at' => null,
        ]);
    }

    /**
     * Indicate that the appointment is scheduled for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes): array => [
            'appointment_date' => Carbon::today(),
            'appointment_time' => Carbon::today()
                ->setHour($this->faker->numberBetween(9, 16))
                ->setMinute($this->faker->randomElement([0, 30])),
        ]);
    }

    /**
     * Indicate that the appointment is in the past.
     */
    public function past(): static
    {
        $pastDate = $this->faker->dateTimeBetween('-1 month', '-1 day');

        return $this->state(fn (array $attributes): array => [
            'appointment_date' => $pastDate->format('Y-m-d'),
            'appointment_time' => Carbon::instance($pastDate)
                ->setHour($this->faker->numberBetween(9, 16))
                ->setMinute($this->faker->randomElement([0, 30])),
        ]);
    }

    /**
     * Indicate that the appointment is a zoom meeting.
     */
    public function zoom(): static
    {
        return $this->state(fn (array $attributes): array => [
            'location_type' => 'zoom',
            'zoom_link' => 'https://zoom.us/j/' . $this->faker->numerify('#########'),
        ]);
    }

    /**
     * Indicate that the appointment is in person.
     */
    public function inPerson(): static
    {
        return $this->state(fn (array $attributes): array => [
            'location_type' => 'in_person',
            'zoom_link' => null,
        ]);
    }

    /**
     * Indicate that the appointment is hybrid.
     */
    public function hybrid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'location_type' => 'hybrid',
            'zoom_link' => 'https://zoom.us/j/' . $this->faker->numerify('#########'),
        ]);
    }
}