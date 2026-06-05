<?php

namespace Database\Factories;

use App\Models\HomepageSection;
use App\Models\HomepageSubsection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HomepageSubsection>
 */
class HomepageSubsectionFactory extends Factory
{
    protected $model = HomepageSubsection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $blockType = $this->faker->randomElement(HomepageSubsection::BLOCK_TYPES);

        return [
            'homepage_section_id' => HomepageSection::factory()->ofType('custom'),
            'block_type' => $blockType,
            'content' => match ($blockType) {
                'heading' => ['text' => $this->faker->sentence(3), 'level' => 2],
                'paragraph' => ['text' => $this->faker->paragraph()],
                'image' => ['url' => $this->faker->imageUrl(), 'alt' => $this->faker->words(3, true)],
                'button' => ['label' => $this->faker->word(), 'href' => '/', 'variant' => 'default'],
                'card' => ['title' => $this->faker->sentence(2), 'body' => $this->faker->paragraph()],
            },
            'design_settings' => [],
            'order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function ofBlockType(string $blockType): self
    {
        return $this->state(fn () => [
            'block_type' => $blockType,
            'content' => match ($blockType) {
                'heading' => ['text' => 'Test heading', 'level' => 2],
                'paragraph' => ['text' => 'Test paragraph'],
                'image' => ['url' => 'https://example.com/img.png', 'alt' => 'test'],
                'button' => ['label' => 'Click', 'href' => '/', 'variant' => 'default'],
                'card' => ['title' => 'Card', 'body' => 'Body'],
            },
        ]);
    }
}
