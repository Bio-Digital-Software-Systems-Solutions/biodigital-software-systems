<?php

namespace Tests\Unit;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_room_has_fillable_attributes(): void
    {
        $room = new ChatRoom;
        $fillable = $room->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('created_by', $fillable);
    }

    public function test_chat_message_has_fillable_attributes(): void
    {
        $message = new ChatMessage;
        $fillable = $message->getFillable();

        $this->assertContains('room_id', $fillable);
        $this->assertContains('sender_id', $fillable);
        $this->assertContains('content', $fillable);
        $this->assertContains('is_read', $fillable);
    }

    public function test_chat_room_creator_relationship(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $room->creator);
        $this->assertEquals($user->id, $room->creator->id);
    }

    public function test_chat_room_participants_relationship(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);

        $room->participants()->attach([$user1->id, $user2->id]);

        $this->assertEquals(2, $room->participants->count());
        $this->assertTrue($room->participants->contains($user1));
        $this->assertTrue($room->participants->contains($user2));
    }

    public function test_chat_room_messages_relationship(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        $message1 = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'First message',
        ]);

        $message2 = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'Second message',
        ]);

        $this->assertEquals(2, $room->messages->count());
        $this->assertTrue($room->messages->contains($message1));
        $this->assertTrue($room->messages->contains($message2));
    }

    public function test_chat_room_last_message_relationship(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'First message',
        ]);

        $lastMessage = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'Last message',
        ]);

        $this->assertInstanceOf(ChatMessage::class, $room->lastMessage);
        $this->assertEquals($lastMessage->id, $room->lastMessage->id);
        $this->assertEquals('Last message', $room->lastMessage->content);
    }

    public function test_chat_message_room_relationship(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
        ]);

        $this->assertInstanceOf(ChatRoom::class, $message->room);
        $this->assertEquals($room->id, $message->room->id);
    }

    public function test_chat_message_sender_relationship(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
        ]);

        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertEquals($user->id, $message->sender->id);
    }

    public function test_user_chat_rooms_relationship(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $room1 = ChatRoom::create([
            'name' => 'Room 1',
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);

        $room2 = ChatRoom::create([
            'name' => 'Room 2',
            'type' => 'group',
            'created_by' => $user1->id,
        ]);

        $room1->participants()->attach([$user1->id, $user2->id]);
        $room2->participants()->attach([$user1->id]);

        $user1ChatRooms = $user1->chatRooms;

        $this->assertEquals(2, $user1ChatRooms->count());
        $this->assertTrue($user1ChatRooms->contains($room1));
        $this->assertTrue($user1ChatRooms->contains($room2));

        $user2ChatRooms = $user2->chatRooms;

        $this->assertEquals(1, $user2ChatRooms->count());
        $this->assertTrue($user2ChatRooms->contains($room1));
        $this->assertFalse($user2ChatRooms->contains($room2));
    }

    public function test_user_chat_messages_relationship(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        $message1 = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'First message',
        ]);

        $message2 = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'Second message',
        ]);

        $userMessages = $user->chatMessages;

        $this->assertEquals(2, $userMessages->count());
        $this->assertTrue($userMessages->contains($message1));
        $this->assertTrue($userMessages->contains($message2));
    }

    public function test_chat_message_is_read_cast_to_boolean(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
            'is_read' => 1,
        ]);

        $this->assertIsBool($message->is_read);
        $this->assertTrue($message->is_read);
    }

    public function test_chat_message_timestamps_are_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::create([
            'name' => 'Test Room',
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $message->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $message->updated_at);
    }
}
