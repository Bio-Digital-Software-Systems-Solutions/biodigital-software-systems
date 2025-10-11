<?php

namespace Database\Factories;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(['Developer', 'Designer', 'Project Manager', 'Analyst', 'Tester']),
            'phone' => fake()->phoneNumber(),
            'skills' => fake()->randomElements(['PHP', 'JavaScript', 'Python', 'React', 'Vue', 'Laravel', 'Node.js'], fake()->numberBetween(2, 5)),
        ];
    }
}
