<?php

namespace Database\Factories;

use App\Models\ProfileSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileSkill>
 */
class ProfileSkillFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ProfileSkill::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skills = [
            'soft' => ['Communication', 'Leadership', 'Teamwork', 'Problem Solving', 'Time Management', 'Adaptability', 'Creativity', 'Critical Thinking', 'Emotional Intelligence', 'Conflict Resolution'],
            'hard' => ['Project Management', 'Data Analysis', 'Financial Planning', 'Marketing Strategy', 'Business Development', 'Quality Assurance', 'Risk Management', 'Process Improvement'],
            'technical' => ['PHP', 'Laravel', 'React', 'TypeScript', 'JavaScript', 'Python', 'SQL', 'Docker', 'Git', 'AWS', 'Node.js', 'Vue.js', 'GraphQL', 'REST API'],
        ];

        $category = fake()->randomElement(['soft', 'hard', 'technical']);

        return [
            'name' => fake()->unique()->randomElement($skills[$category]),
            'category' => $category,
        ];
    }

    /**
     * Indicate that the skill is a soft skill.
     */
    public function soft(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'soft',
            'name' => fake()->randomElement(['Communication', 'Leadership', 'Teamwork', 'Problem Solving', 'Time Management']),
        ]);
    }

    /**
     * Indicate that the skill is a hard skill.
     */
    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'hard',
            'name' => fake()->randomElement(['Project Management', 'Data Analysis', 'Financial Planning', 'Marketing Strategy']),
        ]);
    }

    /**
     * Indicate that the skill is a technical skill.
     */
    public function technical(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'technical',
            'name' => fake()->randomElement(['PHP', 'Laravel', 'React', 'TypeScript', 'JavaScript', 'Python']),
        ]);
    }
}
