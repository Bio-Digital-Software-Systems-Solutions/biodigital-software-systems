<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => $this->faker->optional(0.7)->sentence(4),
            'content' => $this->faker->paragraphs(rand(1, 3), true),
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'type' => $this->faker->randomElement(['direct', 'broadcast', 'system']),
            'read_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the message is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the message is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the message is a direct message.
     */
    public function direct(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'direct',
        ]);
    }

    /**
     * Indicate that the message is a broadcast message.
     */
    public function broadcast(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'broadcast',
        ]);
    }

    /**
     * Indicate that the message is a system message.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'system',
            'subject' => 'System Notification',
        ]);
    }

    /**
     * Indicate that the message has no subject.
     */
    public function noSubject(): static
    {
        return $this->state(fn (array $attributes) => [
            'subject' => null,
        ]);
    }

    /**
     * Create a message between specific users.
     */
    public function between(User $sender, User $receiver): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);
    }

    /**
     * Create a message with long content for testing excerpts.
     */
    public function longContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => $this->faker->paragraphs(10, true),
        ]);
    }

    /**
     * Create a message with HTML content.
     */
    public function htmlContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => '<p>This is a <strong>test</strong> message with <em>HTML</em> tags.</p><ul><li>Item 1</li><li>Item 2</li></ul>',
        ]);
    }
}
