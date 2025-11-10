<?php

namespace Tests\Unit;

use App\Models\PastoralCare;
use App\Models\PastorAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Carbon\Carbon;

class PastoralCareAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create pastor role
        Role::create(['name' => 'pastor']);
    }

    public function test_get_available_time_slots_returns_pastor_defined_slots()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create weekly availability for Monday (day 1)
        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        $mondayDate = '2025-12-15'; // This is a Monday in the future
        $slots = PastoralCare::getAvailableTimeSlots($pastor->id, $mondayDate, 60);

        $expectedSlots = ['09:00', '10:00', '11:00'];
        $this->assertEquals($expectedSlots, $slots);
    }

    public function test_get_available_time_slots_returns_empty_when_no_availability()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // No availability defined
        $mondayDate = '2025-12-15';
        $slots = PastoralCare::getAvailableTimeSlots($pastor->id, $mondayDate, 60);

        $this->assertEmpty($slots);
    }

    public function test_get_available_time_slots_respects_inactive_availability()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create inactive availability
        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration' => 60,
            'is_active' => false, // Inactive
        ]);

        $mondayDate = '2025-12-15';
        $slots = PastoralCare::getAvailableTimeSlots($pastor->id, $mondayDate, 60);

        $this->assertEmpty($slots);
    }

    public function test_get_available_time_slots_excludes_booked_slots()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create availability
        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        // Create an existing appointment at 10:00
        PastoralCare::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => '2025-12-15',
            'appointment_time' => '2025-12-15 10:00:00',
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);

        $mondayDate = '2025-12-15';
        $slots = PastoralCare::getAvailableTimeSlots($pastor->id, $mondayDate, 60);

        // Should exclude 10:00 slot
        $expectedSlots = ['09:00', '11:00'];
        $this->assertEquals($expectedSlots, $slots);
    }

    public function test_get_available_time_slots_excludes_past_slots()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create availability for today
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeek;

        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => $dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '23:59', // Late in the day
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        $todayDate = $today->format('Y-m-d');
        $slots = PastoralCare::getAvailableTimeSlots($pastor->id, $todayDate, 60);

        // Should only include future slots
        foreach ($slots as $slot) {
            $slotDateTime = Carbon::parse($todayDate . ' ' . $slot);
            $this->assertTrue($slotDateTime > Carbon::now(), "Slot {$slot} should be in the future");
        }
    }

    public function test_get_available_time_slots_works_with_specific_date_availability()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create specific date availability for Christmas
        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => '2025-12-25',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'slot_duration' => 30,
            'is_active' => true,
        ]);

        $slots = PastoralCare::getAvailableTimeSlots($pastor->id, '2025-12-25', 30);

        $expectedSlots = ['10:00', '10:30', '11:00', '11:30'];
        $this->assertEquals($expectedSlots, $slots);

        // Should not be available on other dates
        $otherDateSlots = PastoralCare::getAvailableTimeSlots($pastor->id, '2025-12-24', 30);
        $this->assertEmpty($otherDateSlots);
    }

    public function test_get_available_time_slots_handles_different_slot_durations()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create availability with 30-minute slots
        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '14:00',
            'end_time' => '16:00',
            'slot_duration' => 30,
            'is_active' => true,
        ]);

        $mondayDate = '2025-12-15';

        // Request 30-minute slots (matches availability)
        $slots30 = PastoralCare::getAvailableTimeSlots($pastor->id, $mondayDate, 30);
        $expectedSlots30 = ['14:00', '14:30', '15:00', '15:30'];
        $this->assertEquals($expectedSlots30, $slots30);

        // Request 60-minute slots (should fit within 30-minute slots)
        $slots60 = PastoralCare::getAvailableTimeSlots($pastor->id, $mondayDate, 60);
        $expectedSlots60 = ['14:00', '15:00'];
        $this->assertEquals($expectedSlots60, $slots60);
    }

    public function test_get_available_time_slots_handles_multiple_availability_periods()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create morning availability
        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        // Create afternoon availability
        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '14:00',
            'end_time' => '16:00',
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        $mondayDate = '2025-12-15';
        $slots = PastoralCare::getAvailableTimeSlots($pastor->id, $mondayDate, 60);

        $expectedSlots = ['09:00', '10:00', '14:00', '15:00'];
        sort($slots); // Ensure sorted order
        $this->assertEquals($expectedSlots, $slots);
    }

    public function test_is_time_slot_available_detects_conflicts()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create existing appointment
        $existingAppointment = PastoralCare::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => '2025-12-15',
            'appointment_time' => '2025-12-15 10:00:00',
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);

        // Test slot that conflicts
        $isAvailable = PastoralCare::isTimeSlotAvailable(
            $pastor->id,
            '2025-12-15 10:00:00',
            60
        );
        $this->assertFalse($isAvailable);

        // Test slot that doesn't conflict
        $isAvailable = PastoralCare::isTimeSlotAvailable(
            $pastor->id,
            '2025-12-15 11:30:00',
            60
        );
        $this->assertTrue($isAvailable);
    }

    public function test_is_time_slot_available_ignores_cancelled_appointments()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create cancelled appointment
        PastoralCare::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => '2025-12-15',
            'appointment_time' => '2025-12-15 10:00:00',
            'duration_minutes' => 60,
            'status' => 'cancelled',
        ]);

        // Should be available since appointment is cancelled
        $isAvailable = PastoralCare::isTimeSlotAvailable(
            $pastor->id,
            '2025-12-15 10:00:00',
            60
        );
        $this->assertTrue($isAvailable);
    }

    public function test_is_time_slot_available_can_exclude_specific_appointment()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => '2025-12-15',
            'appointment_time' => '2025-12-15 10:00:00',
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);

        // Without excluding the appointment, slot should not be available
        $isAvailable = PastoralCare::isTimeSlotAvailable(
            $pastor->id,
            '2025-12-15 10:00:00',
            60
        );
        $this->assertFalse($isAvailable);

        // With excluding the appointment, slot should be available
        $isAvailable = PastoralCare::isTimeSlotAvailable(
            $pastor->id,
            '2025-12-15 10:00:00',
            60,
            $appointment->id
        );
        $this->assertTrue($isAvailable);
    }

    public function test_get_available_time_slots_removes_duplicates_and_sorts()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create overlapping availabilities (shouldn't happen in practice but test anyway)
        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        // This would create overlapping slots if they existed
        PastorAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => '2025-12-15', // Same Monday
            'start_time' => '10:00',
            'end_time' => '13:00',
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        $mondayDate = '2025-12-15';
        $slots = PastoralCare::getAvailableTimeSlots($pastor->id, $mondayDate, 60);

        // Should have unique slots and be sorted
        $uniqueSlots = array_unique($slots);
        $this->assertEquals($uniqueSlots, $slots, 'Slots should not contain duplicates');

        $sortedSlots = $slots;
        sort($sortedSlots);
        $this->assertEquals($sortedSlots, $slots, 'Slots should be sorted');
    }
}