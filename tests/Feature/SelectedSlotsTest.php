<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PastorAvailability;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class SelectedSlotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\PastorRoleSeeder::class);
    }

    /** @test */
    public function it_returns_only_selected_slots_when_available(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $specificDate = Carbon::now()->addDays(7);

        // Create availability with specific selected slots
        $availability = PastorAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => $specificDate->format('Y-m-d'),
            'start_time' => '06:00',
            'end_time' => '22:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'selected_slots' => ['10:00'] // Only 10:00 is selected
        ]);

        // Test the getTimeSlotsForDate method directly (this is the core functionality)
        $slots = $availability->getTimeSlotsForDate($specificDate->format('Y-m-d'));
        $this->assertCount(1, $slots);
        $this->assertEquals(['10:00'], $slots);
    }

    /** @test */
    public function it_returns_multiple_selected_slots_in_correct_order(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $specificDate = Carbon::now()->addDays(7);

        // Create availability with multiple selected slots
        $availability = PastorAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => $specificDate->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'selected_slots' => ['14:00', '10:00', '11:00'] // Unordered selected slots
        ]);

        // Test the getTimeSlotsForDate method directly (this is the core functionality)
        $slots = $availability->getTimeSlotsForDate($specificDate->format('Y-m-d'));

        // Should contain all selected slots, sorted chronologically
        $this->assertCount(3, $slots);
        $this->assertEquals(['10:00', '11:00', '14:00'], $slots);
    }

    /** @test */
    public function it_generates_default_slots_when_no_specific_slots_selected(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $appointmentDate = Carbon::now()->next(Carbon::MONDAY);

        // Create availability WITHOUT selected_slots (should use default generation)
        PastorAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1, // Monday
            'start_time' => '10:00',
            'end_time' => '12:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person'
            // No selected_slots - should generate default slots
        ]);

        $response = $this->getJson('/api/pastoral-care/available-slots?' . http_build_query([
            'pastor_id' => $pastor->id,
            'date' => $appointmentDate->format('Y-m-d'),
            'duration' => 60
        ]));

        $response->assertStatus(200);
        $slots = $response->json('data.slots');

        // Should generate default slots from 10:00 to 11:00 (2 hours, 60min duration = 2 slots)
        $this->assertEquals(['10:00', '11:00'], $slots);
    }

    /** @test */
    public function it_handles_empty_selected_slots_array(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $appointmentDate = Carbon::now()->next(Carbon::MONDAY);

        // Create availability with empty selected_slots array
        PastorAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '14:00',
            'end_time' => '16:00',
            'slot_duration' => 30,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'selected_slots' => [] // Empty array - should use default generation
        ]);

        $response = $this->getJson('/api/pastoral-care/available-slots?' . http_build_query([
            'pastor_id' => $pastor->id,
            'date' => $appointmentDate->format('Y-m-d'),
            'duration' => 30
        ]));

        $response->assertStatus(200);
        $slots = $response->json('data.slots');

        // Should generate default slots every 30 minutes
        $this->assertEquals(['14:00', '14:30', '15:00', '15:30'], $slots);
    }

    /** @test */
    public function selected_slots_are_sorted_chronologically_in_model(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $specificDate = Carbon::now()->addDays(7);

        $availability = PastorAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => $specificDate->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'selected_slots' => ['15:00', '09:00', '12:00', '21:00', '10:00'] // Unordered
        ]);

        // Test the model's getTimeSlotsForDate method which should sort them
        $sortedSlots = $availability->getTimeSlotsForDate($specificDate->format('Y-m-d'));

        $this->assertEquals(['09:00', '10:00', '12:00', '15:00', '21:00'], $sortedSlots);
    }
}