<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCountTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function it_returns_zero_when_user_has_no_unread_notifications()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertSuccessful()
            ->assertJson([
                'count' => 0,
                'chat_messages' => 0,
                'system_messages' => 0,
            ]);
    }

    /** @test */
    public function it_counts_unread_chat_messages_correctly()
    {
        // Create a chat room with both users
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->otherUser->id]);

        // Create 3 unread messages from other user
        ChatMessage::factory()->count(3)->create([
            'room_id' => $room->id,
            'sender_id' => $this->otherUser->id,
            'created_at' => Carbon::now()->subHours(2),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertSuccessful()
            ->assertJson([
                'count' => 3,
                'chat_messages' => 3,
                'system_messages' => 0,
            ]);
    }

    /** @test */
    public function it_counts_unread_system_messages_correctly()
    {
        // Create 5 unread system messages
        Message::factory()->count(5)->create([
            'receiver_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertSuccessful()
            ->assertJson([
                'count' => 5,
                'chat_messages' => 0,
                'system_messages' => 5,
            ]);
    }

    /** @test */
    public function it_counts_both_chat_and_system_messages()
    {
        // Create chat room and messages
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->otherUser->id]);

        ChatMessage::factory()->count(2)->create([
            'room_id' => $room->id,
            'sender_id' => $this->otherUser->id,
            'created_at' => Carbon::now()->subHours(1),
        ]);

        // Create system messages
        Message::factory()->count(3)->create([
            'receiver_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertSuccessful()
            ->assertJson([
                'count' => 5,
                'chat_messages' => 2,
                'system_messages' => 3,
            ]);
    }

    /** @test */
    public function it_does_not_count_own_messages_in_chat()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->otherUser->id]);

        // Create messages from the user themselves
        ChatMessage::factory()->count(3)->create([
            'room_id' => $room->id,
            'sender_id' => $this->user->id,
            'created_at' => Carbon::now()->subHours(1),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertSuccessful()
            ->assertJson([
                'count' => 0,
                'chat_messages' => 0,
                'system_messages' => 0,
            ]);
    }

    /** @test */
    public function it_does_not_count_chat_messages_older_than_7_days()
    {
        $room = ChatRoom::factory()->create();
        $room->participants()->attach([$this->user->id, $this->otherUser->id]);

        // Create old messages (8 days ago)
        ChatMessage::factory()->count(2)->create([
            'room_id' => $room->id,
            'sender_id' => $this->otherUser->id,
            'created_at' => Carbon::now()->subDays(8),
        ]);

        // Create recent messages (2 days ago)
        ChatMessage::factory()->count(3)->create([
            'room_id' => $room->id,
            'sender_id' => $this->otherUser->id,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertSuccessful()
            ->assertJson([
                'count' => 3,
                'chat_messages' => 3,
                'system_messages' => 0,
            ]);
    }

    /** @test */
    public function it_does_not_count_read_system_messages()
    {
        // Create read messages
        Message::factory()->count(2)->create([
            'receiver_id' => $this->user->id,
            'read_at' => Carbon::now()->subHour(),
        ]);

        // Create unread messages
        Message::factory()->count(3)->create([
            'receiver_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertSuccessful()
            ->assertJson([
                'count' => 3,
                'chat_messages' => 0,
                'system_messages' => 3,
            ]);
    }

    /** @test */
    public function it_only_counts_messages_for_authenticated_user()
    {
        // Create messages for other user
        Message::factory()->count(5)->create([
            'receiver_id' => $this->otherUser->id,
            'read_at' => null,
        ]);

        // Create messages for authenticated user
        Message::factory()->count(2)->create([
            'receiver_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertSuccessful()
            ->assertJson([
                'count' => 2,
                'chat_messages' => 0,
                'system_messages' => 2,
            ]);
    }

    /** @test */
    public function guest_cannot_access_notification_count()
    {
        $response = $this->getJson(route('notifications.unread-count'));

        $response->assertUnauthorized();
    }
}
