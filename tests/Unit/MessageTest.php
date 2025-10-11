<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_message_belongs_to_sender()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertEquals($sender->id, $message->sender->id);
        $this->assertEquals($sender->email, $message->sender->email);
    }

    public function test_message_belongs_to_receiver()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->assertInstanceOf(User::class, $message->receiver);
        $this->assertEquals($receiver->id, $message->receiver->id);
        $this->assertEquals($receiver->email, $message->receiver->email);
    }

    public function test_message_can_check_if_read()
    {
        $message = Message::factory()->create(['read_at' => null]);
        $this->assertFalse($message->isRead());

        $message->update(['read_at' => now()]);
        $this->assertTrue($message->isRead());
    }

    public function test_message_can_be_marked_as_read()
    {
        $message = Message::factory()->create(['read_at' => null]);
        $this->assertFalse($message->isRead());

        $message->markAsRead();
        $message->refresh();

        $this->assertTrue($message->isRead());
        $this->assertNotNull($message->read_at);
    }

    public function test_marking_already_read_message_as_read_does_not_change_timestamp()
    {
        $readAt = now()->subHour();
        $message = Message::factory()->create(['read_at' => $readAt]);

        $message->markAsRead();
        $message->refresh();

        $this->assertEquals($readAt->format('Y-m-d H:i:s'), $message->read_at->format('Y-m-d H:i:s'));
    }

    public function test_message_excerpt_attribute()
    {
        $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 20);
        $message = Message::factory()->create(['content' => $longContent]);

        $excerpt = $message->excerpt;
        $this->assertStringEndsWith('...', $excerpt);
        $this->assertLessThanOrEqual(103, strlen($excerpt)); // 100 chars + '...'
    }

    public function test_message_excerpt_attribute_with_html_tags()
    {
        $htmlContent = '<p>This is a <strong>test</strong> message with <em>HTML</em> tags.</p>';
        $message = Message::factory()->create(['content' => $htmlContent]);

        $excerpt = $message->excerpt;
        $this->assertStringNotContainsString('<p>', $excerpt);
        $this->assertStringNotContainsString('<strong>', $excerpt);
        $this->assertStringNotContainsString('<em>', $excerpt);
        $this->assertStringEndsWith('...', $excerpt);
    }

    public function test_message_type_label_attribute()
    {
        $directMessage = Message::factory()->create(['type' => 'direct']);
        $this->assertEquals('Direct Message', $directMessage->type_label);

        $broadcastMessage = Message::factory()->create(['type' => 'broadcast']);
        $this->assertEquals('Broadcast', $broadcastMessage->type_label);

        $systemMessage = Message::factory()->create(['type' => 'system']);
        $this->assertEquals('System Message', $systemMessage->type_label);
    }

    public function test_message_type_label_attribute_with_direct_type()
    {
        $message = Message::factory()->create(['type' => 'direct']);
        $this->assertEquals('Direct Message', $message->type_label);
    }

    public function test_message_unread_scope()
    {
        $readMessage = Message::factory()->create(['read_at' => now()]);
        $unreadMessage = Message::factory()->create(['read_at' => null]);

        $unreadMessages = Message::unread()->get();

        $this->assertCount(1, $unreadMessages);
        $this->assertEquals($unreadMessage->id, $unreadMessages->first()->id);
        $this->assertFalse($unreadMessages->contains($readMessage));
    }

    public function test_message_read_scope()
    {
        $readMessage = Message::factory()->create(['read_at' => now()]);
        $unreadMessage = Message::factory()->create(['read_at' => null]);

        $readMessages = Message::read()->get();

        $this->assertCount(1, $readMessages);
        $this->assertEquals($readMessage->id, $readMessages->first()->id);
        $this->assertFalse($readMessages->contains($unreadMessage));
    }

    public function test_message_type_scope()
    {
        $directMessage = Message::factory()->create(['type' => 'direct']);
        $broadcastMessage = Message::factory()->create(['type' => 'broadcast']);
        $systemMessage = Message::factory()->create(['type' => 'system']);

        $directMessages = Message::type('direct')->get();
        $this->assertCount(1, $directMessages);
        $this->assertEquals($directMessage->id, $directMessages->first()->id);

        $broadcastMessages = Message::type('broadcast')->get();
        $this->assertCount(1, $broadcastMessages);
        $this->assertEquals($broadcastMessage->id, $broadcastMessages->first()->id);

        $systemMessages = Message::type('system')->get();
        $this->assertCount(1, $systemMessages);
        $this->assertEquals($systemMessage->id, $systemMessages->first()->id);
    }

    public function test_message_between_users_scope()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Message from user1 to user2
        $message1 = Message::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
        ]);

        // Message from user2 to user1
        $message2 = Message::factory()->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
        ]);

        // Message from user3 to user1 (should not be included)
        $message3 = Message::factory()->create([
            'sender_id' => $user3->id,
            'receiver_id' => $user1->id,
        ]);

        $messagesBetween = Message::betweenUsers($user1->id, $user2->id)->get();

        $this->assertCount(2, $messagesBetween);
        $this->assertTrue($messagesBetween->contains($message1));
        $this->assertTrue($messagesBetween->contains($message2));
        $this->assertFalse($messagesBetween->contains($message3));
    }

    public function test_message_fillable_attributes()
    {
        $messageData = [
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'sender_id' => 1,
            'receiver_id' => 2,
            'read_at' => now(),
            'type' => 'direct',
        ];

        $message = new Message;
        $message->fill($messageData);

        $this->assertEquals('Test Subject', $message->subject);
        $this->assertEquals('Test content', $message->content);
        $this->assertEquals(1, $message->sender_id);
        $this->assertEquals(2, $message->receiver_id);
        $this->assertEquals('direct', $message->type);
        $this->assertNotNull($message->read_at);
    }

    public function test_message_casts_read_at_to_datetime()
    {
        $message = Message::factory()->create([
            'read_at' => '2024-01-01 12:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $message->read_at);
        $this->assertEquals('2024-01-01 12:00:00', $message->read_at->format('Y-m-d H:i:s'));
    }

    public function test_message_creation_with_minimum_required_fields()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::create([
            'content' => 'Test message',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'type' => 'direct',
        ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'Test message',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'type' => 'direct',
        ]);

        $this->assertNull($message->subject);
        $this->assertNull($message->read_at);
    }

    public function test_message_creation_with_all_fields()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::create([
            'subject' => 'Test Subject',
            'content' => 'Test message content',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'type' => 'broadcast',
            'read_at' => now(),
        ]);

        $this->assertDatabaseHas('messages', [
            'subject' => 'Test Subject',
            'content' => 'Test message content',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'type' => 'broadcast',
        ]);

        $this->assertNotNull($message->read_at);
    }

    public function test_message_can_have_empty_subject()
    {
        $message = Message::factory()->create(['subject' => null]);
        $this->assertNull($message->subject);

        $message = Message::factory()->create(['subject' => '']);
        $this->assertEquals('', $message->subject);
    }

    public function test_message_content_is_required()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'type' => 'direct',
            // content is missing
        ]);
    }

    public function test_message_type_defaults_to_direct_in_database()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::create([
            'content' => 'Test message',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            // type not specified, should default to 'direct'
        ]);

        $this->assertEquals('direct', $message->fresh()->type);
    }

    public function test_message_belongs_to_department()
    {
        $sender = User::factory()->create();
        $department = \App\Models\Department::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => null,
            'recipient_type' => 'department',
            'department_id' => $department->id,
        ]);

        $this->assertInstanceOf(\App\Models\Department::class, $message->department);
        $this->assertEquals($department->id, $message->department->id);
        $this->assertEquals($department->name, $message->department->name);
    }

    public function test_message_can_have_user_recipient_type()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'recipient_type' => 'user',
            'department_id' => null,
        ]);

        $this->assertEquals('user', $message->recipient_type);
        $this->assertNotNull($message->receiver_id);
        $this->assertNull($message->department_id);
    }

    public function test_message_can_have_department_recipient_type()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $department = \App\Models\Department::factory()->create();

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'recipient_type' => 'department',
            'department_id' => $department->id,
        ]);

        $this->assertEquals('department', $message->recipient_type);
        $this->assertNotNull($message->department_id);
        $this->assertEquals($department->id, $message->department_id);
    }

    public function test_message_fillable_includes_new_fields()
    {
        $messageData = [
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'sender_id' => 1,
            'receiver_id' => 2,
            'recipient_type' => 'user',
            'department_id' => null,
            'read_at' => now(),
            'type' => 'direct',
        ];

        $message = new Message;
        $message->fill($messageData);

        $this->assertEquals('user', $message->recipient_type);
        $this->assertNull($message->department_id);
    }
}
