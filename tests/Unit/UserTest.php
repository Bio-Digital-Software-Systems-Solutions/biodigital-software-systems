<?php

namespace Tests\Unit;

use App\Models\BookRental;
use App\Models\ChatRoom;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_full_name_attribute()
    {
        $user = new User([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $user->full_name);
    }

    public function test_user_can_have_events()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->events->contains($event));
    }

    public function test_user_can_participate_in_events()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $user->participatedEvents()->attach($event->id);

        $this->assertTrue($user->participatedEvents->contains($event));
    }

    public function test_user_can_have_book_rentals()
    {
        $user = User::factory()->create();
        $rental = BookRental::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->bookRentals->contains($rental));
    }

    public function test_user_can_have_chat_rooms()
    {
        $user = User::factory()->create();
        $chatRoom = ChatRoom::factory()->create();

        $user->chatRooms()->attach($chatRoom->id);

        $this->assertTrue($user->chatRooms->contains($chatRoom));
    }

    public function test_user_password_is_hidden()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret'),
        ]);

        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
    }

    public function test_user_remember_token_is_hidden()
    {
        $user = User::factory()->create([
            'remember_token' => 'test-token',
        ]);

        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('remember_token', $userArray);
    }
}
