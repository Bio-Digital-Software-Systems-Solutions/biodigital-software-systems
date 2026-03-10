<?php

namespace Database\Factories;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Employee\EmploymentType;
use App\Enums\Employee\PaymentMethod;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hireDate = $this->faker->dateTimeBetween('-5 years', '-1 month');
        $probationEndDate = (clone $hireDate)->modify('+6 months');

        return [
            'user_id' => User::factory(),
            'department_id' => null,
            'manager_id' => null,
            'position' => $this->faker->randomElement([
                'Développeur',
                'Chef de projet',
                'Designer',
                'Comptable',
                'Assistant',
                'Coordinateur',
                'Manager',
                'Directeur',
            ]),
            'job_title' => $this->faker->jobTitle(),
            'birth_date' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'nationality' => $this->faker->randomElement(['German', 'French', 'Swiss', 'Austrian', 'Italian']),
            'social_security_number' => $this->faker->numerify('###-##-####'),
            'tax_id' => $this->faker->numerify('##/###/####'),
            'personal_email' => $this->faker->safeEmail(),
            'work_phone' => $this->faker->phoneNumber(),
            'personal_phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'Germany',
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_relationship' => $this->faker->randomElement(['Spouse', 'Parent', 'Sibling', 'Friend']),
            'status' => EmployeeStatus::ACTIVE,
            'employment_type' => EmploymentType::FULL_TIME,
            'hire_date' => $hireDate,
            'probation_end_date' => $probationEndDate,
            'contract_end_date' => null,
            'termination_date' => null,
            'termination_reason' => null,
            'hourly_rate' => $this->faker->randomFloat(2, 15, 50),
            'monthly_salary' => $this->faker->randomFloat(2, 2500, 8000),
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'bank_name' => $this->faker->company() . ' Bank',
            'bank_iban' => $this->faker->iban('DE'),
            'bank_bic' => $this->faker->swiftBicNumber(),
            'weekly_hours' => 40.00,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'default_start_time' => '09:00',
            'default_end_time' => '17:00',
            'annual_leave_days' => 30,
            'remaining_leave_days' => $this->faker->numberBetween(15, 30),
            'sick_days_taken' => $this->faker->numberBetween(0, 5),
            'skills' => $this->faker->randomElements([
                'PHP', 'JavaScript', 'TypeScript', 'React', 'Vue', 'Laravel',
                'Project Management', 'Communication', 'Leadership', 'Excel',
            ], random_int(2, 5)),
            'certifications' => null,
            'languages' => ['German', 'English'],
            'avatar' => null,
            'contract_document' => null,
            'id_document' => null,
            'notes' => null,
            'internal_notes' => null,
        ];
    }

    /**
     * Indicate that the employee is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => EmployeeStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => EmployeeStatus::INACTIVE,
        ]);
    }

    /**
     * Indicate that the employee is on leave.
     */
    public function onLeave(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => EmployeeStatus::ON_LEAVE,
        ]);
    }

    /**
     * Indicate that the employee is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => EmployeeStatus::TERMINATED,
            'termination_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'termination_reason' => $this->faker->randomElement([
                'Resignation',
                'Contract End',
                'Relocation',
                'Career Change',
            ]),
        ]);
    }

    /**
     * Indicate that the employee is full-time.
     */
    public function fullTime(): static
    {
        return $this->state(fn(array $attributes): array => [
            'employment_type' => EmploymentType::FULL_TIME,
            'weekly_hours' => 40.00,
        ]);
    }

    /**
     * Indicate that the employee is part-time.
     */
    public function partTime(): static
    {
        return $this->state(fn(array $attributes): array => [
            'employment_type' => EmploymentType::PART_TIME,
            'weekly_hours' => 20.00,
        ]);
    }

    /**
     * Indicate that the employee is a contractor.
     */
    public function contractor(): static
    {
        return $this->state(fn(array $attributes): array => [
            'employment_type' => EmploymentType::CONTRACT,
            'contract_end_date' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }

    /**
     * Indicate that the employee is an intern.
     */
    public function intern(): static
    {
        return $this->state(fn(array $attributes): array => [
            'employment_type' => EmploymentType::INTERN,
            'weekly_hours' => 35.00,
            'contract_end_date' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
            'monthly_salary' => $this->faker->randomFloat(2, 500, 1500),
        ]);
    }

    /**
     * Indicate that the employee is a volunteer.
     */
    public function volunteer(): static
    {
        return $this->state(fn(array $attributes): array => [
            'employment_type' => EmploymentType::VOLUNTEER,
            'weekly_hours' => 10.00,
            'hourly_rate' => null,
            'monthly_salary' => null,
        ]);
    }

    /**
     * Indicate that the employee is on probation.
     */
    public function onProbation(): static
    {
        return $this->state(fn(array $attributes): array => [
            'hire_date' => now()->subMonths(2),
            'probation_end_date' => now()->addMonths(4),
        ]);
    }

    /**
     * Indicate that the employee has completed probation.
     */
    public function probationCompleted(): static
    {
        return $this->state(fn(array $attributes): array => [
            'hire_date' => now()->subYear(),
            'probation_end_date' => now()->subMonths(6),
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
     * Assign a manager.
     */
    public function withManager(Employee $manager): static
    {
        return $this->state(fn(array $attributes): array => [
            'manager_id' => $manager->id,
            'department_id' => $manager->department_id,
        ]);
    }

    /**
     * With contract ending soon.
     */
    public function contractEndingSoon(int $days = 30): static
    {
        return $this->state(fn(array $attributes): array => [
            'employment_type' => EmploymentType::CONTRACT,
            'contract_end_date' => now()->addDays($days),
        ]);
    }

    /**
     * Configure the employee with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
