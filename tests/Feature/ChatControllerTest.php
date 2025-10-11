<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatControllerTest extends TestCase
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

    public function test_authenticated_user_with_permission_can_view_chat_index()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/chat');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Chat/Index'));
    }

    public function test_user_without_permission_cannot_access_chat()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/chat');

        $response->assertStatus(403);
    }

    public function test_user_can_create_direct_chat_room()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $response = $this->actingAs($user1)->postJson('/chat/rooms', [
            'type' => 'direct',
            'participant_ids' => [$user2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'room' => [
                'id',
                'name',
                'type',
                'created_by',
                'participants',
            ],
        ]);

        $this->assertDatabaseHas('chat_rooms', [
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);

        $room = ChatRoom::first();
        $this->assertTrue($room->participants->contains($user1));
        $this->assertTrue($room->participants->contains($user2));
    }

    public function test_user_can_create_group_chat_room()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user1->assignRole('member');

        $response = $this->actingAs($user1)->postJson('/chat/rooms', [
            'name' => 'Test Group',
            'type' => 'group',
            'participant_ids' => [$user2->id, $user3->id],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('chat_rooms', [
            'name' => 'Test Group',
            'type' => 'group',
            'created_by' => $user1->id,
        ]);

        $room = ChatRoom::first();
        $this->assertEquals(3, $room->participants->count());
    }

    public function test_creating_duplicate_direct_room_returns_existing_room()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $existingRoom = ChatRoom::create([
            'name' => 'Direct Chat',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $existingRoom->participants()->attach([$user1->id, $user2->id]);

        $response = $this->actingAs($user1)->postJson('/chat/rooms', [
            'type' => 'direct',
            'participant_ids' => [$user2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'room' => ['id' => $existingRoom->id],
        ]);

        $this->assertEquals(1, ChatRoom::count());
    }

    public function test_user_can_send_message_to_room()
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
            'content' => 'Hello, this is a test message!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message' => [
                'id',
                'content',
                'sender',
                'created_at',
            ],
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'room_id' => $room->id,
            'sender_id' => $user1->id,
            'content' => 'Hello, this is a test message!',
        ]);
    }

    public function test_user_cannot_send_message_to_room_they_are_not_part_of()
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

        $response = $this->actingAs($user1)->postJson("/chat/rooms/{$room->id}/messages", [
            'content' => 'I should not be able to send this!',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_get_messages_from_room()
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

        ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user1->id,
            'content' => 'First message',
        ]);

        ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user2->id,
            'content' => 'Second message',
        ]);

        $response = $this->actingAs($user1)->getJson("/chat/rooms/{$room->id}/messages");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'messages' => [
                '*' => [
                    'id',
                    'content',
                    'sender',
                    'created_at',
                ],
            ],
        ]);

        $messages = $response->json('messages');
        $this->assertEquals(2, count($messages));
        $this->assertEquals('First message', $messages[0]['content']);
        $this->assertEquals('Second message', $messages[1]['content']);
    }

    public function test_user_can_get_unread_message_count()
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

        ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user2->id,
            'content' => 'Unread message 1',
            'is_read' => false,
        ]);

        ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user2->id,
            'content' => 'Unread message 2',
            'is_read' => false,
        ]);

        $response = $this->actingAs($user1)->getJson('/chat/unread-count');

        $response->assertStatus(200);
        $response->assertJson(['unread_count' => 2]);
    }

    public function test_user_can_leave_chat_room()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'group',
            'created_by' => $user1->id,
        ]);
        $room->participants()->attach([$user1->id, $user2->id]);

        $response = $this->actingAs($user1)->deleteJson("/chat/rooms/{$room->id}/leave");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $room->refresh();
        $this->assertFalse($room->participants->contains($user1));
        $this->assertTrue($room->participants->contains($user2));
    }

    public function test_room_is_deleted_when_last_participant_leaves()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('member');

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $room->participants()->attach([$user1->id]);

        ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user1->id,
            'content' => 'This message should be deleted',
        ]);

        $response = $this->actingAs($user1)->deleteJson("/chat/rooms/{$room->id}/leave");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('chat_rooms', ['id' => $room->id]);
        $this->assertDatabaseMissing('chat_messages', ['room_id' => $room->id]);
    }

    public function test_message_validation_requires_content()
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
            'content' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_room_creation_validation_requires_participant_ids()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('member');

        $response = $this->actingAs($user1)->postJson('/chat/rooms', [
            'type' => 'direct',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['participant_ids']);
    }
}
