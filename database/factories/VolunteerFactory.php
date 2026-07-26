<?php

namespace Database\Factories;

use App\Enums\Volunteer\VolunteerCategory;
use App\Enums\Volunteer\VolunteerStatus;
use App\Enums\Volunteer\VolunteerType;
use App\Models\Department;
use App\Models\Volunteer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Volunteer>
 */
class VolunteerFactory extends Factory
{
    protected $model = Volunteer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recognitionDate = $this->faker->dateTimeBetween('-2 years', '-1 month');

        return [
            'user_id' => User::factory(),
            'department_id' => null,
            'nominated_by' => null,
            'title' => $this->faker->randomElement([
                'Bénévole du mois',
                'Volunteer de l\'accueil',
                'Champion du service',
                'Leader inspirant',
                'Mentor dévoué',
            ]),
            'description' => $this->faker->paragraph(),
            'status' => VolunteerStatus::ACTIVE,
            'type' => VolunteerType::VOLUNTEER,
            'category' => $this->faker->randomElement(VolunteerCategory::cases()),
            'points' => $this->faker->numberBetween(0, 500),
            'level' => $this->faker->numberBetween(1, 3),
            'recognition_date' => $recognitionDate,
            'expiry_date' => null,
            'achievements' => $this->faker->randomElements([
                'Premier service',
                '100 heures de service',
                'Leader formé',
                'Mentor certifié',
                '1 an de service',
            ], random_int(0, 3)),
            'badges' => $this->faker->randomElements([
                'Ponctualité',
                'Fiabilité',
                'Excellence',
                'Créativité',
                'Engagement',
            ], random_int(0, 2)),
            'skills' => $this->faker->randomElements([
                'Communication',
                'Leadership',
                'Organisation',
                'Technique',
                'Créativité',
                'Médias',
                'Musique',
                'Enseignement',
            ], random_int(2, 5)),
            'areas_of_service' => $this->faker->randomElements([
                'Accueil',
                'Technique son',
                'Média',
                'Enfants',
                'Jeunesse',
                'Louange',
                'Administration',
            ], random_int(1, 3)),
            'available_days' => $this->faker->randomElements([
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
            ], random_int(2, 4)),
            'available_from' => '09:00',
            'available_to' => '18:00',
            'hours_per_week' => $this->faker->numberBetween(2, 10),
            'total_hours_served' => $this->faker->numberBetween(10, 500),
            'is_contactable' => true,
            'preferred_contact_method' => $this->faker->randomElement(['email', 'phone', 'sms']),
            'receive_notifications' => true,
            'bio' => $this->faker->paragraph(),
            'avatar' => null,
            'cover_image' => null,
            'is_public_profile' => $this->faker->boolean(30),
            'is_featured' => false,
            'display_order' => 0,
            'testimonial' => $this->faker->optional()->sentence(),
            'favorite_verse' => $this->faker->optional()->randomElement([
                'Philippiens 4:13',
                'Psaume 23:1',
                'Romains 8:28',
                'Jérémie 29:11',
                'Proverbes 3:5-6',
            ]),
            'notes' => null,
            'internal_notes' => null,
        ];
    }

    /**
     * Indicate that the volunteer is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => VolunteerStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate that the volunteer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => VolunteerStatus::INACTIVE,
        ]);
    }

    /**
     * Indicate that the volunteer is on break.
     */
    public function onBreak(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => VolunteerStatus::ON_BREAK,
        ]);
    }

    /**
     * Indicate that the volunteer has graduated.
     */
    public function graduated(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => VolunteerStatus::GRADUATED,
            'expiry_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the volunteer is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => VolunteerStatus::SUSPENDED,
        ]);
    }

    /**
     * Indicate that the volunteer is a volunteer.
     */
    public function volunteer(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => VolunteerType::VOLUNTEER,
            'level' => $this->faker->numberBetween(1, 2),
        ]);
    }

    /**
     * Indicate that the volunteer is a leader.
     */
    public function leader(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => VolunteerType::LEADER,
            'level' => $this->faker->numberBetween(3, 4),
            'points' => $this->faker->numberBetween(500, 1000),
        ]);
    }

    /**
     * Indicate that the volunteer is a mentor.
     */
    public function mentor(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => VolunteerType::MENTOR,
            'level' => $this->faker->numberBetween(4, 5),
            'points' => $this->faker->numberBetween(1000, 2000),
        ]);
    }

    /**
     * Indicate that the volunteer is an ambassador.
     */
    public function ambassador(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => VolunteerType::AMBASSADOR,
            'level' => 5,
            'points' => $this->faker->numberBetween(2000, 5000),
        ]);
    }

    /**
     * Indicate that the volunteer is a coordinator.
     */
    public function coordinator(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => VolunteerType::COORDINATOR,
            'level' => $this->faker->numberBetween(2, 3),
            'points' => $this->faker->numberBetween(250, 500),
        ]);
    }

    /**
     * Indicate that the volunteer is featured.
     */
    public function featured(): static
    {
        return $this->state(fn(array $attributes): array => [
            'is_featured' => true,
            'is_public_profile' => true,
        ]);
    }

    /**
     * Indicate that the volunteer has a public profile.
     */
    public function publicProfile(): static
    {
        return $this->state(fn(array $attributes): array => [
            'is_public_profile' => true,
        ]);
    }

    /**
     * With expiry date soon.
     */
    public function expiringSoon(int $days = 30): static
    {
        return $this->state(fn(array $attributes): array => [
            'expiry_date' => now()->addDays($days),
        ]);
    }

    /**
     * Expired volunteer.
     */
    public function expired(): static
    {
        return $this->state(fn(array $attributes): array => [
            'expiry_date' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * High level volunteer.
     */
    public function highLevel(): static
    {
        return $this->state(fn(array $attributes): array => [
            'level' => $this->faker->numberBetween(4, 5),
            'points' => $this->faker->numberBetween(1000, 3000),
        ]);
    }

    /**
     * Assign to a department.
     */
    public function inDepartment(Department $department): static
    {
        return $this->state(fn(array $attributes): array => [
            'department_id' => $department->id,
        ]);
    }

    /**
     * With specific category.
     */
    public function inCategory(VolunteerCategory $category): static
    {
        return $this->state(fn(array $attributes): array => [
            'category' => $category,
        ]);
    }

    /**
     * Nominated by a user.
     */
    public function nominatedBy(User $user): static
    {
        return $this->state(fn(array $attributes): array => [
            'nominated_by' => $user->id,
        ]);
    }

    /**
     * Configure the volunteer with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Recently recognized.
     */
    public function recentlyRecognized(): static
    {
        return $this->state(fn(array $attributes): array => [
            'recognition_date' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }
}
