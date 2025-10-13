<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();
        $content = '<p>'.implode('</p><p>', fake()->paragraphs(5)).'</p>';

        return [
            'title' => $title,
            'content' => $content,
            'excerpt' => fake()->optional(0.7)->sentence(),
            'status' => fake()->randomElement(['draft', 'published', 'pending']),
            'cover_image' => null,
            'video_file' => null,
            'published_at' => fake()->optional(0.8)->dateTimeBetween('-1 month', 'now'),
            'is_featured' => fake()->boolean(20),
            'views' => fake()->numberBetween(0, 1000),
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Article $article) {
            // Always generate slug from title if not explicitly provided
            if (!isset($article->getAttributes()['slug'])) {
                $article->slug = Str::slug($article->title);
            }

            // Ensure draft/pending articles don't have published_at set
            if (in_array($article->status, ['draft', 'pending']) && !isset($article->getAttributes()['published_at_was_explicitly_set'])) {
                $article->published_at = null;
            }

            // Ensure published articles have published_at set
            if ($article->status === 'published' && !$article->published_at) {
                $article->published_at = now();
            }
        });
    }

    /**
     * Indicate that the article is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the article is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the article is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the article is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the article is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the article is scheduled for publication.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'published_at' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }
}
