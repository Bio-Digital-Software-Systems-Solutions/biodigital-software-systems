<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\CreatesPermissions;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase, CreatesPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();
    }

    protected function createUserWithChatPermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('use chat');
        return $user;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_access_chat_index(): void
    {
        $user = $this->createUserWithChatPermission();

        $response = $this->actingAs($user)->get('/chat');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Chat/Index')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_cannot_access_chat(): void
    {
        $response = $this->get('/chat');

        $response->assertRedirect('/login');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_direct_chat_room(): void
    {
        $user1 = $this->createUserWithChatPermission();
        $user2 = $this->createUserWithChatPermission();

        $response = $this->actingAs($user1)->post('/chat/rooms', [
            'type' => 'direct',
            'participant_ids' => [$user2->id],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('chat_rooms', [
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_group_chat_room(): void
    {
        $user1 = $this->createUserWithChatPermission();
        $user2 = $this->createUserWithChatPermission();
        $user3 = $this->createUserWithChatPermission();

        $response = $this->actingAs($user1)->post('/chat/rooms', [
            'type' => 'group',
            'name' => 'Team Chat',
            'participant_ids' => [$user2->id, $user3->id],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('chat_rooms', [
            'type' => 'group',
            'name' => 'Team Chat',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function group_chat_requires_name(): void
    {
        $user1 = $this->createUserWithChatPermission();
        $user2 = $this->createUserWithChatPermission();

        $response = $this->actingAs($user1)
            ->postJson('/chat/rooms', [
                'type' => 'group',
                'participant_ids' => [$user2->id],
            ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_send_message_to_room(): void
    {
        $user = $this->createUserWithChatPermission();
        $room = ChatRoom::factory()->create(['created_by' => $user->id]);
        $room->participants()->attach($user->id);

        $response = $this->actingAs($user)->post("/chat/rooms/{$room->uuid}/messages", [
            'content' => 'Hello, World!',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('chat_messages', [
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'Hello, World!',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_send_message_to_room_they_are_not_in(): void
    {
        $user = $this->createUserWithChatPermission();
        $otherUser = $this->createUserWithChatPermission();
        $room = ChatRoom::factory()->create(['created_by' => $otherUser->id]);
        $room->participants()->attach($otherUser->id);

        $response = $this->actingAs($user)
            ->postJson("/chat/rooms/{$room->uuid}/messages", [
                'content' => 'Hello',
            ]);

        // Accept either 403 Forbidden or 302 Redirect as access denied
        $this->assertContains($response->status(), [403, 302]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_retrieve_messages_from_room(): void
    {
        $user = $this->createUserWithChatPermission();
        $room = ChatRoom::factory()->create(['created_by' => $user->id]);
        $room->participants()->attach($user->id);

        ChatMessage::factory()->count(10)->create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/chat/rooms/{$room->uuid}/messages");

        $response->assertSuccessful();
        $response->assertJsonCount(10, 'messages');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_retrieve_messages_from_room_they_are_not_in(): void
    {
        $user = $this->createUserWithChatPermission();
        $otherUser = $this->createUserWithChatPermission();
        $room = ChatRoom::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson("/chat/rooms/{$room->uuid}/messages");

        // Accept either 403 Forbidden or 302 Redirect as access denied
        $this->assertContains($response->status(), [403, 302]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function message_content_is_required(): void
    {
        $user = $this->createUserWithChatPermission();
        $room = ChatRoom::factory()->create(['created_by' => $user->id]);
        $room->participants()->attach($user->id);

        $response = $this->actingAs($user)
            ->postJson("/chat/rooms/{$room->uuid}/messages", [
                'content' => '',
            ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function messages_are_marked_as_read(): void
    {
        $sender = $this->createUserWithChatPermission();
        $receiver = $this->createUserWithChatPermission();

        $room = ChatRoom::factory()->create(['created_by' => $sender->id]);
        $room->participants()->attach([$sender->id, $receiver->id]);

        // Sender creates an unread message
        $message = ChatMessage::factory()->create([
            'room_id' => $room->id,
            'sender_id' => $sender->id,
            'is_read' => false,
        ]);

        // Receiver views messages - should automatically mark as read
        $response = $this->actingAs($receiver)
            ->getJson("/chat/rooms/{$room->uuid}/messages");

        $response->assertSuccessful();
        $this->assertTrue($message->fresh()->is_read);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_leave_chat_room(): void
    {
        $user = $this->createUserWithChatPermission();
        $room = ChatRoom::factory()->create(['created_by' => $user->id]);
        $room->participants()->attach($user->id);

        $response = $this->actingAs($user)->delete("/chat/rooms/{$room->uuid}/leave");

        $response->assertSuccessful();
        $this->assertFalse($room->participants->contains($user));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_sees_only_their_chat_rooms(): void
    {
        $user1 = $this->createUserWithChatPermission();
        $user2 = $this->createUserWithChatPermission();

        $room1 = ChatRoom::factory()->create(['created_by' => $user1->id]);
        $room1->participants()->attach($user1->id);

        $room2 = ChatRoom::factory()->create(['created_by' => $user2->id]);
        $room2->participants()->attach($user2->id);

        $response = $this->actingAs($user1)->get('/chat');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('chatRooms.0.id', $room1->id)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function messages_are_sanitized_for_xss(): void
    {
        $user = $this->createUserWithChatPermission();
        $room = ChatRoom::factory()->create(['created_by' => $user->id]);
        $room->participants()->attach($user->id);

        $response = $this->actingAs($user)->post("/chat/rooms/{$room->uuid}/messages", [
            'content' => '<script>alert("xss")</script>Hello',
        ]);

        $response->assertSuccessful();
        $message = ChatMessage::latest()->first();

        $this->assertStringNotContainsString('<script>', $message->content);
    }
}
