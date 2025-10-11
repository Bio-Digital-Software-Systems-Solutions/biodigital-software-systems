<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'use chat']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['use chat']);

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['use chat']);
    }

    public function test_returns_json_response_for_ajax_requests()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/chat');

        $response->assertStatus(200);
    }

    public function test_message_length_validation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $room->participants()->attach([$user1->id, $user2->id]);

        $response = $this->actingAs($user1)->postJson("/chat/rooms/{$room->id}/messages", [
            'content' => str_repeat('a', 1001), // Over 1000 character limit
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_cannot_create_room_with_nonexistent_user()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->postJson('/chat/rooms', [
            'type' => 'direct',
            'participant_ids' => [999999], // Non-existent user ID
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['participant_ids.0']);
    }

    public function test_duplicate_participants_are_handled()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $response = $this->actingAs($user1)->postJson('/chat/rooms', [
            'type' => 'group',
            'participant_ids' => [$user2->id, $user2->id, $user1->id], // Duplicate IDs
        ]);

        $response->assertStatus(200);

        $room = ChatRoom::first();
        $this->assertEquals(2, $room->participants->count()); // Should have unique participants
    }

    public function test_room_name_generation_for_group_chat()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user1->assignRole('member');

        $response = $this->actingAs($user1)->postJson('/chat/rooms', [
            'type' => 'group',
            'participant_ids' => [$user2->id, $user3->id],
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals('Groupe de discussion', $data['room']['name']);
    }

    public function test_can_create_room_with_explicit_name()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $response = $this->actingAs($user1)->postJson('/chat/rooms', [
            'name' => 'Custom Group Name',
            'type' => 'group',
            'participant_ids' => [$user2->id],
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals('Custom Group Name', $data['room']['name']);
    }

    public function test_messages_are_marked_as_read_when_retrieved()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $room->participants()->attach([$user1->id, $user2->id]);

        // Create unread message from user2
        ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user2->id,
            'content' => 'Unread message',
            'is_read' => false,
        ]);

        // User1 retrieves messages
        $this->actingAs($user1)->getJson("/chat/rooms/{$room->id}/messages");

        // Message should now be marked as read
        $message = ChatMessage::first();
        $this->assertTrue($message->is_read);
    }

    public function test_own_messages_are_not_marked_as_read()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $room->participants()->attach([$user1->id, $user2->id]);

        // Create message from user1
        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user1->id,
            'content' => 'My message',
            'is_read' => false,
        ]);

        // User1 retrieves messages (their own)
        $this->actingAs($user1)->getJson("/chat/rooms/{$room->id}/messages");

        // Message should remain unread (sender's own message)
        $message->refresh();
        $this->assertFalse($message->is_read);
    }

    public function test_unread_count_only_includes_others_messages()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $room->participants()->attach([$user1->id, $user2->id]);

        // User1's own unread message
        ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user1->id,
            'content' => 'My message',
            'is_read' => false,
        ]);

        // User2's unread message
        ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user2->id,
            'content' => 'Other message',
            'is_read' => false,
        ]);

        $response = $this->actingAs($user1)->getJson('/chat/unread-count');

        $response->assertStatus(200);
        $response->assertJson(['unread_count' => 1]); // Only user2's message
    }

    public function test_room_updated_at_changes_when_message_sent()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $room->participants()->attach([$user1->id, $user2->id]);

        $originalTime = $room->updated_at;

        // Wait a moment to ensure time difference
        sleep(1);

        $response = $this->actingAs($user1)->postJson("/chat/rooms/{$room->id}/messages", [
            'content' => 'Test message',
        ]);

        $response->assertStatus(200);

        $room->refresh();
        $this->assertNotEquals($originalTime, $room->updated_at);
    }

    public function test_cannot_access_room_without_participation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Private Room',
            'type' => 'direct',
            'created_by' => $user2->id,
        ]);
        $room->participants()->attach([$user2->id, $user3->id]);

        // User1 tries to access room they're not part of
        $response = $this->actingAs($user1)->getJson("/chat/rooms/{$room->id}/messages");

        $response->assertStatus(403);
    }

    public function test_cannot_leave_room_not_participating_in()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Private Room',
            'type' => 'direct',
            'created_by' => $user2->id,
        ]);
        $room->participants()->attach([$user2->id, $user3->id]);

        // User1 tries to leave room they're not part of
        $response = $this->actingAs($user1)->deleteJson("/chat/rooms/{$room->id}/leave");

        $response->assertStatus(403);
    }

    public function test_messages_are_returned_in_chronological_order()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $room->participants()->attach([$user1->id, $user2->id]);

        $message1 = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user1->id,
            'content' => 'First message',
        ]);

        $message2 = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user2->id,
            'content' => 'Second message',
        ]);

        $response = $this->actingAs($user1)->getJson("/chat/rooms/{$room->id}/messages");

        $response->assertStatus(200);
        $messages = $response->json('messages');

        $this->assertEquals('First message', $messages[0]['content']);
        $this->assertEquals('Second message', $messages[1]['content']);
    }

    public function test_message_limit_is_respected()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $room->participants()->attach([$user1->id, $user2->id]);

        // Create 60 messages (more than the 50 limit)
        for ($i = 1; $i <= 60; $i++) {
            ChatMessage::create([
                'room_id' => $room->id,
                'sender_id' => $user1->id,
                'content' => "Message $i",
            ]);
        }

        $response = $this->actingAs($user1)->getJson("/chat/rooms/{$room->id}/messages");

        $response->assertStatus(200);
        $messages = $response->json('messages');

        $this->assertEquals(50, count($messages)); // Should only return 50 most recent
        $this->assertEquals('Message 11', $messages[0]['content']); // Should start from message 11
        $this->assertEquals('Message 60', $messages[49]['content']); // Should end with message 60
    }
}
