<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Event\EventMedia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event\EventMedia>
 */
class EventMediaFactory extends Factory
{
    protected $model = EventMedia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mediaType = fake()->randomElement([EventMedia::TYPE_IMAGE, EventMedia::TYPE_VIDEO]);
        $isImage = $mediaType === EventMedia::TYPE_IMAGE;

        return [
            'event_id' => Event::factory(),
            'uploaded_by' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'file_path' => $isImage
                ? 'events/media/'.fake()->uuid().'.jpg'
                : 'events/media/'.fake()->uuid().'.mp4',
            'file_name' => $isImage
                ? fake()->word().'.jpg'
                : fake()->word().'.mp4',
            'file_type' => $isImage ? 'image/jpeg' : 'video/mp4',
            'file_size' => $isImage
                ? fake()->numberBetween(100000, 5000000)
                : fake()->numberBetween(1000000, 100000000),
            'media_type' => $mediaType,
            'collection' => EventMedia::COLLECTION_GALLERY,
            'is_featured' => false,
            'thumbnail_path' => null,
            'width' => $isImage ? fake()->numberBetween(800, 1920) : null,
            'height' => $isImage ? fake()->numberBetween(600, 1080) : null,
            'duration' => $isImage ? null : fake()->numberBetween(30, 600),
            'sort_order' => 0,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the media is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'events/media/'.fake()->uuid().'.jpg',
            'file_name' => fake()->word().'.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'media_type' => EventMedia::TYPE_IMAGE,
            'width' => fake()->numberBetween(800, 1920),
            'height' => fake()->numberBetween(600, 1080),
            'duration' => null,
        ]);
    }

    /**
     * Indicate that the media is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'events/media/'.fake()->uuid().'.mp4',
            'file_name' => fake()->word().'.mp4',
            'file_type' => 'video/mp4',
            'file_size' => fake()->numberBetween(1000000, 100000000),
            'media_type' => EventMedia::TYPE_VIDEO,
            'width' => null,
            'height' => null,
            'duration' => fake()->numberBetween(30, 600),
        ]);
    }

    /**
     * Indicate that the media is a banner.
     */
    public function banner(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection' => EventMedia::COLLECTION_BANNER,
        ]);
    }

    /**
     * Indicate that the media is in the gallery.
     */
    public function gallery(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection' => EventMedia::COLLECTION_GALLERY,
        ]);
    }

    /**
     * Indicate that the media is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
