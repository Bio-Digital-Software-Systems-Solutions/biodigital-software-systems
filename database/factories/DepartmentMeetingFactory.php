<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\DepartmentMeeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentMeeting>
 */
class DepartmentMeetingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DepartmentMeeting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'department_id' => Department::factory(),
            'appointment_id' => Appointment::factory(),
            'created_by' => User::factory(),
            'notify_all_members' => $this->faker->boolean(80),
            'is_mandatory' => $this->faker->boolean(30),
            'notes' => $this->faker->optional(0.5)->sentence(),
            'notified_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the meeting should notify all members.
     */
    public function notifyAll(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify_all_members' => true,
        ]);
    }

    /**
     * Indicate that the meeting should not notify all members.
     */
    public function notifyNone(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify_all_members' => false,
        ]);
    }

    /**
     * Indicate that the meeting is mandatory.
     */
    public function mandatory(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_mandatory' => true,
        ]);
    }

    /**
     * Indicate that the meeting is optional.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_mandatory' => false,
        ]);
    }

    /**
     * Indicate that the meeting has been notified.
     */
    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => now(),
        ]);
    }

    /**
     * Indicate that the meeting has not been notified.
     */
    public function notNotified(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => null,
        ]);
    }

    /**
     * Create a meeting for a specific department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->id,
        ]);
    }

    /**
     * Create a meeting created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Create a meeting with a specific appointment.
     */
    public function withAppointment(Appointment $appointment): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_id' => $appointment->id,
        ]);
    }
}
