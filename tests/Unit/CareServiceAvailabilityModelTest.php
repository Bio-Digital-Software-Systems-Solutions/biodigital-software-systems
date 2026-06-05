<?php

namespace Tests\Unit;

use App\Models\CareServiceAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CareServiceAvailabilityModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create pastor role
        Role::create(['name' => 'pastor']);
    }

    public function test_care_service_availability_can_be_created(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        CareServiceAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'notes' => 'Standard availability',
        ]);

        $this->assertDatabaseHas('care_service_availability', [
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => 1, // Database stores as integer
        ]);
    }

    public function test_care_service_availability_belongs_to_pastor(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $availability = CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
        ]);

        $this->assertInstanceOf(User::class, $availability->pastor);
        $this->assertEquals($pastor->id, $availability->pastor->id);
    }

    public function test_pastor_has_many_availabilities(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
        ]);

        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 2,
        ]);

        $this->assertCount(2, $pastor->availability);
    }

    public function test_scopes_work_correctly(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create active and inactive availabilities
        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'is_active' => true,
        ]);

        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 2,
            'is_active' => false,
        ]);

        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => '2025-12-25',
            'is_active' => true,
        ]);

        // Test scopes
        $this->assertCount(2, CareServiceAvailability::active()->get());
        $this->assertCount(2, CareServiceAvailability::weekly()->get());
        $this->assertCount(1, CareServiceAvailability::specificDate()->get());
        $this->assertCount(3, CareServiceAvailability::forPastor($pastor->id)->get());
    }

    public function test_get_time_slots_for_date_generates_correct_slots(): void
    {
        $availability = new CareServiceAvailability([
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration' => 30,
        ]);

        $slots = $availability->getTimeSlotsForDate('2025-01-13');

        $expected = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30'];
        $this->assertEquals($expected, $slots);
    }

    public function test_get_time_slots_for_date_with_different_durations(): void
    {
        // Test 60-minute slots
        $availability = new CareServiceAvailability([
            'start_time' => '14:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
        ]);

        $slots = $availability->getTimeSlotsForDate('2025-01-13');

        $expected = ['14:00', '15:00', '16:00'];
        $this->assertEquals($expected, $slots);

        // Test 90-minute slots
        $availability = new CareServiceAvailability([
            'start_time' => '09:00',
            'end_time' => '12:30',
            'slot_duration' => 90,
        ]);

        $slots = $availability->getTimeSlotsForDate('2025-01-13');

        $expected = ['09:00', '10:30'];
        $this->assertEquals($expected, $slots);
    }

    public function test_applies_to_method_for_weekly_availability(): void
    {
        $availability = new CareServiceAvailability([
            'type' => 'weekly',
            'day_of_week' => 1, // Monday
        ]);

        // Test with Monday date
        $mondayDate = '2025-01-13'; // This is a Monday
        $this->assertTrue($availability->appliesTo($mondayDate));

        // Test with Tuesday date
        $tuesdayDate = '2025-01-14'; // This is a Tuesday
        $this->assertFalse($availability->appliesTo($tuesdayDate));
    }

    public function test_applies_to_method_for_specific_date_availability(): void
    {
        $availability = new CareServiceAvailability([
            'type' => 'specific_date',
            'specific_date' => '2025-12-25',
        ]);

        $this->assertTrue($availability->appliesTo('2025-12-25'));
        $this->assertFalse($availability->appliesTo('2025-12-24'));
    }

    public function test_get_day_name_attribute(): void
    {
        $availability = new CareServiceAvailability([
            'type' => 'weekly',
            'day_of_week' => 1,
        ]);

        $this->assertEquals('Lundi', $availability->day_name);

        // Test specific date type
        $availability->type = 'specific_date';
        $availability->day_of_week = null;
        $this->assertEquals('', $availability->day_name);

        // Test invalid day
        $availability->type = 'weekly';
        $availability->day_of_week = 99;
        $this->assertEquals('', $availability->day_name);
    }

    public function test_get_time_range_attribute(): void
    {
        $availability = new CareServiceAvailability([
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $this->assertEquals('09:00 - 17:00', $availability->time_range);
    }

    public function test_casts_work_correctly(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Test specific_date type
        $availability = CareServiceAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => '2025-12-25',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        $availability = $availability->fresh();

        $this->assertInstanceOf(\Carbon\Carbon::class, $availability->specific_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $availability->start_time);
        $this->assertInstanceOf(\Carbon\Carbon::class, $availability->end_time);
        $this->assertNull($availability->day_of_week); // Should be null for specific_date type
        $this->assertIsInt($availability->slot_duration);
        $this->assertIsBool($availability->is_active);

        // Test weekly type
        $weeklyAvailability = CareServiceAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'slot_duration' => 30,
            'is_active' => false,
        ]);

        $weeklyAvailability = $weeklyAvailability->fresh();

        $this->assertNull($weeklyAvailability->specific_date); // Should be null for weekly type
        $this->assertInstanceOf(\Carbon\Carbon::class, $weeklyAvailability->start_time);
        $this->assertInstanceOf(\Carbon\Carbon::class, $weeklyAvailability->end_time);
        $this->assertIsInt($weeklyAvailability->day_of_week);
        $this->assertIsInt($weeklyAvailability->slot_duration);
        $this->assertIsBool($weeklyAvailability->is_active);
    }

    public function test_fillable_fields(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $data = [
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 3,
            'specific_date' => null,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'slot_duration' => 45,
            'is_active' => false,
            'notes' => 'Test notes',
        ];

        $availability = CareServiceAvailability::create($data);

        foreach ($data as $key => $value) {
            if ($key === 'start_time' || $key === 'end_time') {
                // Compare time strings
                $this->assertEquals($value, $availability->{$key}->format('H:i'));
            } else {
                $this->assertEquals($value, $availability->{$key});
            }
        }
    }
}
