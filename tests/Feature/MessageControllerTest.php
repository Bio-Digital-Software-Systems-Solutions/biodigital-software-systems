<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles
        Permission::create(['name' => 'view messages']);
        Permission::create(['name' => 'create messages']);
        Permission::create(['name' => 'edit messages']);
        Permission::create(['name' => 'delete messages']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['view messages', 'create messages', 'edit messages', 'delete messages']);

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view messages', 'create messages']);
    }

    public function test_authenticated_user_can_view_messages_index()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/messages');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Messages/Index'));
    }

    public function test_user_can_view_only_their_messages()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $user1->assignRole('member');

        // Messages involving user1
        $message1 = Message::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
        ]);
        $message2 = Message::factory()->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
        ]);

        // Message not involving user1
        $message3 = Message::factory()->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user3->id,
        ]);

        $response = $this->actingAs($user1)->get('/messages');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Messages/Index')
            ->has('messages.data', 2)
                // Latest message first (message2 was created after message1)
            ->where('messages.data.0.id', $message1->id)
            ->where('messages.data.1.id', $message2->id)
        );
    }

    public function test_authenticated_user_with_permission_can_create_message()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/messages/create');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Messages/Create')
            ->has('users')
        );
    }

    public function test_user_without_permission_cannot_create_message()
    {
        $user = User::factory()->create();
        // Not assigning any role

        $response = $this->actingAs($user)->get('/messages/create');

        $response->assertStatus(403);
    }

    public function test_user_can_store_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $sender->assignRole('admin');

        $messageData = [
            'subject' => 'Test Message',
            'content' => 'This is a test message content.',
            'recipient_type' => 'user',
            'recipient_id' => $receiver->id,
            'type' => 'direct',
        ];

        $response = $this->actingAs($sender)->post('/messages', $messageData);

        $response->assertRedirect('/messages');
        $this->assertDatabaseHas('messages', [
            'subject' => 'Test Message',
            'content' => 'This is a test message content.',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'recipient_type' => 'user',
            'type' => 'direct',
        ]);
    }

    public function test_user_can_store_message_without_subject()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $sender->assignRole('admin');

        $messageData = [
            'content' => 'This is a test message without subject.',
            'recipient_type' => 'user',
            'recipient_id' => $receiver->id,
            'type' => 'direct',
        ];

        $response = $this->actingAs($sender)->post('/messages', $messageData);

        $response->assertRedirect('/messages');
        $this->assertDatabaseHas('messages', [
            'subject' => null,
            'content' => 'This is a test message without subject.',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'recipient_type' => 'user',
            'type' => 'direct',
        ]);
    }

    public function test_message_creation_validates_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/messages', []);

        $response->assertSessionHasErrors(['content', 'recipient_type', 'recipient_id', 'type']);
    }

    public function test_message_creation_validates_receiver_exists()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $messageData = [
            'content' => 'Test message',
            'recipient_type' => 'user',
            'recipient_id' => 999, // Non-existent user
            'type' => 'direct',
        ];

        $response = $this->actingAs($user)->post('/messages', $messageData);

        $response->assertStatus(404); // The controller throws 404 for non-existent recipients
    }

    public function test_message_creation_validates_type()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $sender->assignRole('admin');

        $messageData = [
            'content' => 'Test message',
            'receiver_id' => $receiver->id,
            'type' => 'invalid_type',
        ];

        $response = $this->actingAs($sender)->post('/messages', $messageData);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_user_can_view_single_message_they_sent()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $sender->assignRole('member');

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($sender)->get("/messages/{$message->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Messages/Show')
            ->has('message.subject')
            ->where('message.id', $message->id)
        );
    }

    public function test_user_can_view_single_message_they_received()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $receiver->assignRole('member');

        $message = Message::factory()->unread()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($receiver)->get("/messages/{$message->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Messages/Show')
            ->where('message.id', $message->id)
        );

        // Check that message was marked as read
        $message->refresh();
        $this->assertNotNull($message->read_at);
    }

    public function test_user_cannot_view_message_they_are_not_involved_in()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $user3->assignRole('member');

        $message = Message::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
        ]);

        $response = $this->actingAs($user3)->get("/messages/{$message->id}");

        $response->assertStatus(403);
    }

    public function test_sender_can_edit_their_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $sender->assignRole('admin');

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($sender)->get("/messages/{$message->id}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Messages/Edit')
            ->has('message')
            ->has('users')
            ->where('message.id', $message->id)
        );
    }

    public function test_receiver_cannot_edit_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $receiver->assignRole('admin');

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($receiver)->get("/messages/{$message->id}/edit");

        $response->assertStatus(403);
    }

    public function test_sender_can_update_their_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $newReceiver = User::factory()->create();
        $sender->assignRole('admin');

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'subject' => 'Original Subject',
            'content' => 'Original content',
            'type' => 'direct',
        ]);

        $updateData = [
            'subject' => 'Updated Subject',
            'content' => 'Updated content',
            'receiver_id' => $newReceiver->id,
            'type' => 'broadcast',
        ];

        $response = $this->actingAs($sender)->put("/messages/{$message->id}", $updateData);

        $response->assertRedirect('/messages');
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'subject' => 'Updated Subject',
            'content' => 'Updated content',
            'receiver_id' => $newReceiver->id,
            'type' => 'broadcast',
        ]);
    }

    public function test_receiver_cannot_update_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $receiver->assignRole('admin');

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $updateData = [
            'subject' => 'Updated Subject',
            'content' => 'Updated content',
            'receiver_id' => $receiver->id,
            'type' => 'direct',
        ];

        $response = $this->actingAs($receiver)->put("/messages/{$message->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_sender_can_delete_their_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $sender->assignRole('admin');

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($sender)->delete("/messages/{$message->id}");

        $response->assertRedirect('/messages');
        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }

    public function test_receiver_cannot_delete_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $receiver->assignRole('admin');

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($receiver)->delete("/messages/{$message->id}");

        $response->assertStatus(403);
    }

    public function test_receiver_can_mark_message_as_read()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $receiver->assignRole('member');

        $message = Message::factory()->unread()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($receiver)->patch("/messages/{$message->id}/mark-as-read");

        $response->assertRedirect();
        $message->refresh();
        $this->assertNotNull($message->read_at);
    }

    public function test_sender_cannot_mark_message_as_read()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $sender->assignRole('member');

        $message = Message::factory()->unread()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($sender)->patch("/messages/{$message->id}/mark-as-read");

        $response->assertStatus(403);
    }

    public function test_unread_count_endpoint()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->assignRole('member');

        // Create unread messages for user
        Message::factory()->count(3)->unread()->create([
            'receiver_id' => $user->id,
            'sender_id' => $otherUser->id,
        ]);

        // Create read messages for user
        Message::factory()->count(2)->read()->create([
            'receiver_id' => $user->id,
            'sender_id' => $otherUser->id,
        ]);

        // Create messages sent by user (should not count)
        Message::factory()->count(2)->unread()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->get('/messages-unread-count');

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    }

    public function test_messages_index_with_type_filter()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->assignRole('member');

        Message::factory()->direct()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
        ]);
        Message::factory()->broadcast()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->get('/messages?type=direct');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Messages/Index')
            ->has('messages.data', 1)
            ->where('messages.data.0.type', 'direct')
        );
    }

    public function test_messages_index_with_status_filter()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->assignRole('member');

        Message::factory()->unread()->create([
            'receiver_id' => $user->id,
            'sender_id' => $otherUser->id,
        ]);
        Message::factory()->read()->create([
            'receiver_id' => $user->id,
            'sender_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->get('/messages?status=unread');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Messages/Index')
            ->has('messages.data', 1)
            ->where('messages.data.0.read_at', null)
        );
    }

    public function test_messages_index_with_search_filter()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->assignRole('member');

        Message::factory()->create([
            'subject' => 'Important Meeting',
            'content' => 'Let\'s discuss the project.',
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
        ]);
        Message::factory()->create([
            'subject' => 'Random Topic',
            'content' => 'Some other content.',
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->get('/messages?search=meeting');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Messages/Index')
            ->has('messages.data', 1)
            ->where('messages.data.0.subject', 'Important Meeting')
        );
    }

    public function test_guest_cannot_access_messages()
    {
        $response = $this->get('/messages');
        $response->assertRedirect('/login');
    }

    public function test_user_can_search_recipients()
    {
        $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);
        $user->assignRole('member');

        // Create other users
        User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Johnson', 'email' => 'bob@example.com']);

        // Create departments
        \App\Models\Department::factory()->create(['name' => 'Engineering', 'code' => 'ENG']);
        \App\Models\Department::factory()->create(['name' => 'Marketing', 'code' => 'MKT']);

        $response = $this->actingAs($user)->get('/messages-search-recipients?search=jane');

        $response->assertStatus(200);
        $response->assertJson([
            [
                'type' => 'user',
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
            ]
        ]);
    }

    public function test_search_recipients_returns_users_and_departments()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Wonder']);
        $department = \App\Models\Department::factory()->create(['name' => 'Engineering', 'code' => 'ENG', 'is_active' => true]);

        $response = $this->actingAs($user)->get('/messages-search-recipients');

        $response->assertStatus(200);
        $json = $response->json();

        // Should have both users and departments
        $this->assertGreaterThan(0, count($json));

        $userResults = collect($json)->where('type', 'user');
        $deptResults = collect($json)->where('type', 'department');

        $this->assertGreaterThan(0, $userResults->count());
        $this->assertGreaterThan(0, $deptResults->count());
    }

    public function test_search_recipients_excludes_current_user()
    {
        $user = User::factory()->create(['first_name' => 'Current', 'last_name' => 'User']);
        $user->assignRole('member');

        User::factory()->create(['first_name' => 'Other', 'last_name' => 'User']);

        $response = $this->actingAs($user)->get('/messages-search-recipients');

        $response->assertStatus(200);
        $json = $response->json();

        // Current user should not be in results
        $userResults = collect($json)->where('type', 'user');
        $this->assertFalse($userResults->contains('id', $user->id));
    }

    public function test_user_can_send_message_to_another_user()
    {
        $sender = User::factory()->create();
        $sender->assignRole('member');

        $receiver = User::factory()->create();

        $response = $this->actingAs($sender)->post('/messages', [
            'subject' => 'Test Subject',
            'content' => 'Test message content',
            'recipient_type' => 'user',
            'recipient_id' => $receiver->id,
            'type' => 'direct',
        ]);

        $response->assertRedirect('/messages');
        $response->assertSessionHas('message', 'Message sent successfully.');

        $this->assertDatabaseHas('messages', [
            'subject' => 'Test Subject',
            'content' => 'Test message content',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'recipient_type' => 'user',
            'department_id' => null,
            'type' => 'direct',
        ]);
    }

    public function test_user_can_send_message_to_department()
    {
        $sender = User::factory()->create();
        $sender->assignRole('member');

        $department = \App\Models\Department::factory()->create(['name' => 'Engineering']);

        // Add users to department
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $department->users()->attach([$user1->id, $user2->id, $user3->id, $sender->id]);

        $response = $this->actingAs($sender)->post('/messages', [
            'subject' => 'Department Announcement',
            'content' => 'Important team message',
            'recipient_type' => 'department',
            'recipient_id' => $department->id,
            'type' => 'broadcast',
        ]);

        $response->assertRedirect('/messages');
        $response->assertSessionHas('message', 'Message sent successfully.');

        // Should create 3 messages (not to sender)
        $messages = Message::where('sender_id', $sender->id)
            ->where('department_id', $department->id)
            ->get();

        $this->assertCount(3, $messages);

        // Verify each message
        foreach ($messages as $message) {
            $this->assertEquals('broadcast', $message->type);
            $this->assertEquals('department', $message->recipient_type);
            $this->assertEquals($department->id, $message->department_id);
            $this->assertEquals('Department Announcement', $message->subject);
            $this->assertNotEquals($sender->id, $message->receiver_id);
        }
    }

    public function test_department_message_does_not_send_to_sender()
    {
        $sender = User::factory()->create();
        $sender->assignRole('member');

        $department = \App\Models\Department::factory()->create();

        $user1 = User::factory()->create();
        $sender_in_dept = $sender;

        $department->users()->attach([$user1->id, $sender_in_dept->id]);

        $this->actingAs($sender)->post('/messages', [
            'content' => 'Test message',
            'recipient_type' => 'department',
            'recipient_id' => $department->id,
            'type' => 'broadcast',
        ]);

        // Should only create 1 message (to user1, not sender)
        $messages = Message::where('sender_id', $sender->id)->get();
        $this->assertCount(1, $messages);
        $this->assertEquals($user1->id, $messages->first()->receiver_id);
        $this->assertNotEquals($sender->id, $messages->first()->receiver_id);
    }

    public function test_sending_message_requires_recipient_type()
    {
        $sender = User::factory()->create();
        $sender->assignRole('member');
        $receiver = User::factory()->create();

        $response = $this->actingAs($sender)->post('/messages', [
            'content' => 'Test message',
            'recipient_id' => $receiver->id,
            'type' => 'direct',
            // recipient_type is missing
        ]);

        $response->assertSessionHasErrors(['recipient_type']);
    }

    public function test_sending_message_validates_recipient_exists()
    {
        $sender = User::factory()->create();
        $sender->assignRole('member');

        $response = $this->actingAs($sender)->post('/messages', [
            'content' => 'Test message',
            'recipient_type' => 'user',
            'recipient_id' => 99999, // Non-existent user
            'type' => 'direct',
        ]);

        $response->assertStatus(404);
    }

    public function test_search_recipients_only_returns_active_departments()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        \App\Models\Department::factory()->create(['name' => 'Active Dept', 'is_active' => true]);
        \App\Models\Department::factory()->create(['name' => 'Inactive Dept', 'is_active' => false]);

        $response = $this->actingAs($user)->get('/messages-search-recipients');

        $response->assertStatus(200);
        $json = $response->json();

        $deptResults = collect($json)->where('type', 'department');

        $this->assertTrue($deptResults->contains('name', 'Active Dept'));
        $this->assertFalse($deptResults->contains('name', 'Inactive Dept'));
    }
}
