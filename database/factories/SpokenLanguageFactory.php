<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SpokenLanguage>
 */
class SpokenLanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $languages = [
            ['name' => 'French', 'code' => 'fr', 'native_name' => 'Français'],
            ['name' => 'English', 'code' => 'en', 'native_name' => 'English'],
            ['name' => 'German', 'code' => 'de', 'native_name' => 'Deutsch'],
            ['name' => 'Spanish', 'code' => 'es', 'native_name' => 'Español'],
            ['name' => 'Italian', 'code' => 'it', 'native_name' => 'Italiano'],
            ['name' => 'Portuguese', 'code' => 'pt', 'native_name' => 'Português'],
            ['name' => 'Dutch', 'code' => 'nl', 'native_name' => 'Nederlands'],
            ['name' => 'Russian', 'code' => 'ru', 'native_name' => 'Русский'],
            ['name' => 'Chinese', 'code' => 'zh', 'native_name' => '中文'],
            ['name' => 'Japanese', 'code' => 'ja', 'native_name' => '日本語'],
            ['name' => 'Korean', 'code' => 'ko', 'native_name' => '한국어'],
            ['name' => 'Arabic', 'code' => 'ar', 'native_name' => 'العربية'],
            ['name' => 'Turkish', 'code' => 'tr', 'native_name' => 'Türkçe'],
            ['name' => 'Polish', 'code' => 'pl', 'native_name' => 'Polski'],
            ['name' => 'Swedish', 'code' => 'sv', 'native_name' => 'Svenska'],
        ];

        $language = fake()->unique()->randomElement($languages);

        return [
            'name' => $language['name'],
            'code' => $language['code'],
            'native_name' => $language['native_name'],
        ];
    }

    /**
     * Create a French language.
     */
    public function french(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'French',
            'code' => 'fr',
            'native_name' => 'Français',
        ]);
    }

    /**
     * Create an English language.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'English',
            'code' => 'en',
            'native_name' => 'English',
        ]);
    }

    /**
     * Create a German language.
     */
    public function german(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'German',
            'code' => 'de',
            'native_name' => 'Deutsch',
        ]);
    }
}
