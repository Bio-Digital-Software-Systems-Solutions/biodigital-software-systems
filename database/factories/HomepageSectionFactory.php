<?php

namespace Database\Factories;

use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HomepageSection>
 */
class HomepageSectionFactory extends Factory
{
    protected $model = HomepageSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(HomepageSection::TYPES);

        return [
            'key' => $type.'-'.Str::random(8),
            'type' => $type,
            'title' => $this->faker->sentence(3),
            'content' => [
                'badge' => $this->faker->word(),
                'heading' => $this->faker->sentence(4),
                'subtitle' => $this->faker->sentence(8),
            ],
            'design_settings' => [],
            'order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function ofType(string $type): self
    {
        return $this->state(fn () => [
            'type' => $type,
            'key' => $type.'-'.Str::random(8),
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
