<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_belongs_to_creator()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($event->creator->is($user));
    }

    public function test_event_can_have_address()
    {
        $address = Address::factory()->create();
        $event = Event::factory()->create(['address_id' => $address->id]);

        $this->assertTrue($event->address->is($address));
    }

    public function test_event_can_have_participants()
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        $event->participants()->attach($user->id);

        $this->assertTrue($event->participants->contains($user));
    }

    public function test_event_dates_are_cast_to_datetime()
    {
        $event = Event::factory()->create([
            'start_date' => '2024-12-01 10:00:00',
            'end_date' => '2024-12-01 12:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $event->start_date);
        $this->assertInstanceOf(Carbon::class, $event->end_date);
    }

    public function test_event_can_determine_if_full()
    {
        $event = Event::factory()->create(['max_participants' => 2]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Event is not full initially
        $this->assertFalse($event->isFull());

        // Add participants
        $event->participants()->attach([$user1->id, $user2->id]);
        $event->refresh();

        // Event should now be full
        $this->assertTrue($event->isFull());

        // Try to add another participant
        $this->assertFalse($event->canAddParticipant());
    }

    public function test_event_with_no_max_participants_is_never_full()
    {
        $event = Event::factory()->create(['max_participants' => null]);
        $users = User::factory()->count(100)->create();

        $event->participants()->attach($users->pluck('id')->toArray());
        $event->refresh();

        $this->assertFalse($event->isFull());
        $this->assertTrue($event->canAddParticipant());
    }

    public function test_event_status_defaults_to_planned()
    {
        $event = Event::factory()->create();

        $this->assertEquals('planned', $event->status);
    }

    public function test_event_is_public_by_default()
    {
        $event = Event::factory()->create();

        $this->assertTrue($event->is_public);
    }

    public function test_event_can_be_private()
    {
        $event = Event::factory()->create(['is_public' => false]);

        $this->assertFalse($event->is_public);
    }
}
