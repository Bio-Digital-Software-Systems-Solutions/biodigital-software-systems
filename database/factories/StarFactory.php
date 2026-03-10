<?php

namespace Database\Factories;

use App\Enums\Star\StarCategory;
use App\Enums\Star\StarStatus;
use App\Enums\Star\StarType;
use App\Models\Department;
use App\Models\Star;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Star>
 */
class StarFactory extends Factory
{
    protected $model = Star::class;

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
                'Star de l\'accueil',
                'Champion du service',
                'Leader inspirant',
                'Mentor dévoué',
            ]),
            'description' => $this->faker->paragraph(),
            'status' => StarStatus::ACTIVE,
            'type' => StarType::VOLUNTEER,
            'category' => $this->faker->randomElement(StarCategory::cases()),
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
     * Indicate that the star is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => StarStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate that the star is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => StarStatus::INACTIVE,
        ]);
    }

    /**
     * Indicate that the star is on break.
     */
    public function onBreak(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => StarStatus::ON_BREAK,
        ]);
    }

    /**
     * Indicate that the star has graduated.
     */
    public function graduated(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => StarStatus::GRADUATED,
            'expiry_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the star is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => StarStatus::SUSPENDED,
        ]);
    }

    /**
     * Indicate that the star is a volunteer.
     */
    public function volunteer(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => StarType::VOLUNTEER,
            'level' => $this->faker->numberBetween(1, 2),
        ]);
    }

    /**
     * Indicate that the star is a leader.
     */
    public function leader(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => StarType::LEADER,
            'level' => $this->faker->numberBetween(3, 4),
            'points' => $this->faker->numberBetween(500, 1000),
        ]);
    }

    /**
     * Indicate that the star is a mentor.
     */
    public function mentor(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => StarType::MENTOR,
            'level' => $this->faker->numberBetween(4, 5),
            'points' => $this->faker->numberBetween(1000, 2000),
        ]);
    }

    /**
     * Indicate that the star is an ambassador.
     */
    public function ambassador(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => StarType::AMBASSADOR,
            'level' => 5,
            'points' => $this->faker->numberBetween(2000, 5000),
        ]);
    }

    /**
     * Indicate that the star is a coordinator.
     */
    public function coordinator(): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => StarType::COORDINATOR,
            'level' => $this->faker->numberBetween(2, 3),
            'points' => $this->faker->numberBetween(250, 500),
        ]);
    }

    /**
     * Indicate that the star is featured.
     */
    public function featured(): static
    {
        return $this->state(fn(array $attributes): array => [
            'is_featured' => true,
            'is_public_profile' => true,
        ]);
    }

    /**
     * Indicate that the star has a public profile.
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
     * Expired star.
     */
    public function expired(): static
    {
        return $this->state(fn(array $attributes): array => [
            'expiry_date' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * High level star.
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
    public function inCategory(StarCategory $category): static
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
     * Configure the star with a specific user.
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
