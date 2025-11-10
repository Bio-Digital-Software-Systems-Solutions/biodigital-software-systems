<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PastorAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class PastoralAvailabilitySelectedSlotsTest extends TestCase
{
    use RefreshDatabase;

    protected User $pastor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create pastor role
        Role::create(['name' => 'pastor']);

        // Create and authenticate a pastor user
        $this->pastor = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'pastor@example.com',
        ]);

        $this->pastor->assignRole('pastor');
    }

    /** @test */
    public function pastor_can_create_availability_with_selected_slots()
    {
        $this->actingAs($this->pastor);

        $data = [
            'type' => 'weekly',
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'location' => 'Église ICC Munich',
            'room' => 'Bureau pastoral',
            'notes' => 'Test creation with selected slots',
            'selected_slots' => ['09:00', '10:00', '11:00', '14:00', '15:00']
        ];

        $response = $this->post(route('pastoral-availability.store'), $data);

        $response->assertStatus(302);
        $response->assertRedirect(route('pastoral-availability.index'));

        // Verify the availability was created with selected slots
        $availability = PastorAvailability::where('pastor_id', $this->pastor->id)
            ->where('day_of_week', 1)
            ->first();

        $this->assertNotNull($availability);
        $this->assertEquals('weekly', $availability->type);
        $this->assertEquals(1, $availability->day_of_week);
        $this->assertEquals('09:00', $availability->start_time);
        $this->assertEquals('17:00', $availability->end_time);
        $this->assertEquals(60, $availability->slot_duration);
        $this->assertTrue($availability->is_active);
        $this->assertEquals('in_person', $availability->consultation_mode);
        $this->assertEquals('Église ICC Munich', $availability->location);
        $this->assertEquals('Bureau pastoral', $availability->room);
        $this->assertEquals('Test creation with selected slots', $availability->notes);

        // Most importantly - check selected slots
        $this->assertEquals(['09:00', '10:00', '11:00', '14:00', '15:00'], $availability->selected_slots);
    }

    /** @test */
    public function pastor_can_create_availability_with_empty_selected_slots()
    {
        $this->actingAs($this->pastor);

        $data = [
            'type' => 'specific_date',
            'specific_date' => '2025-12-26', // Different date to avoid conflict
            'start_time' => '10:00',
            'end_time' => '16:00',
            'slot_duration' => 30,
            'is_active' => true,
            'consultation_mode' => 'online',
            'meeting_link' => 'https://zoom.us/j/123456789',
            'notes' => 'Christmas consultation',
            'selected_slots' => [] // Explicitly empty array
        ];

        $response = $this->post(route('pastoral-availability.store'), $data);

        $response->assertStatus(302);

        $availability = PastorAvailability::where('pastor_id', $this->pastor->id)
            ->where('specific_date', '2025-12-26')
            ->first();

        $this->assertNotNull($availability);
        $this->assertEquals('specific_date', $availability->type);
        $this->assertEquals('2025-12-26', $availability->specific_date);
        $this->assertEquals('online', $availability->consultation_mode);
        $this->assertEquals('https://zoom.us/j/123456789', $availability->meeting_link);

        // Should be empty array when explicitly provided as empty
        $this->assertEquals([], $availability->selected_slots);
    }

    /** @test */
    public function pastor_can_update_selected_slots()
    {
        $this->actingAs($this->pastor);

        // Create initial availability
        $availability = PastorAvailability::create([
            'pastor_id' => $this->pastor->id,
            'type' => 'weekly',
            'day_of_week' => 2, // Tuesday
            'start_time' => '08:00',
            'end_time' => '18:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'hybrid',
            'location' => 'Centre pastoral',
            'room' => 'Salle 3',
            'selected_slots' => ['08:00', '09:00', '10:00']
        ]);

        // Update with different selected slots
        $updateData = [
            'selected_slots' => ['14:00', '15:00', '16:00', '17:00']
        ];

        $response = $this->put(route('pastoral-availability.update', $availability->id), $updateData);

        $response->assertStatus(302);

        $availability->refresh();
        $this->assertEquals(['14:00', '15:00', '16:00', '17:00'], $availability->selected_slots);
    }

    /** @test */
    public function pastor_can_clear_selected_slots()
    {
        $this->actingAs($this->pastor);

        // Create availability with selected slots
        $availability = PastorAvailability::create([
            'pastor_id' => $this->pastor->id,
            'type' => 'weekly',
            'day_of_week' => 3,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'selected_slots' => ['09:00', '10:00', '11:00']
        ]);

        // Update to clear selected slots
        $updateData = [
            'selected_slots' => []
        ];

        $response = $this->put(route('pastoral-availability.update', $availability->id), $updateData);

        $response->assertStatus(302);

        $availability->refresh();
        $this->assertEquals([], $availability->selected_slots);
    }

    /** @test */
    public function selected_slots_are_cast_to_array()
    {
        $this->actingAs($this->pastor);

        $availability = PastorAvailability::create([
            'pastor_id' => $this->pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'selected_slots' => ['09:00', '10:00', '14:00']
        ]);

        // Verify the selected_slots are properly cast as an array
        $this->assertIsArray($availability->selected_slots);
        $this->assertCount(3, $availability->selected_slots);
        $this->assertContains('09:00', $availability->selected_slots);
        $this->assertContains('10:00', $availability->selected_slots);
        $this->assertContains('14:00', $availability->selected_slots);
    }

    /** @test */
    public function selected_slots_validation_accepts_valid_time_strings()
    {
        $this->actingAs($this->pastor);

        $data = [
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'selected_slots' => ['09:00', '10:00', '11:00', '14:00']
        ];

        $response = $this->post(route('pastoral-availability.store'), $data);

        $response->assertStatus(302);
        $this->assertDatabaseHas('pastor_availability', [
            'pastor_id' => $this->pastor->id,
            'day_of_week' => 1,
        ]);
    }

    /** @test */
    public function selected_slots_validation_rejects_invalid_data()
    {
        $this->actingAs($this->pastor);

        $data = [
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'selected_slots' => ['invalid_time', 123, null] // Invalid formats
        ];

        $response = $this->post(route('pastoral-availability.store'), $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors();
    }
}