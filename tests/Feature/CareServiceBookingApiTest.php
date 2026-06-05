<?php

namespace Tests\Feature;

use App\Models\CareService;
use App\Models\CareServiceAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CareServiceBookingApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\PastorRoleSeeder::class);
    }

    /** @test */
    public function it_can_get_list_of_pastors(): void
    {
        // Create some pastors
        $pastor1 = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@pastor.com',
        ]);
        $pastor1->assignRole('pastor');

        $pastor2 = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@pastor.com',
        ]);
        $pastor2->assignRole('pastor');

        // Create a non-pastor user
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->getJson('/api/care-service/pastors');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $pastors = $response->json('data');
        $this->assertCount(2, $pastors);

        // Check pastor details
        $johnPastor = collect($pastors)->firstWhere('email', 'john@pastor.com');
        $this->assertEquals('John Doe', $johnPastor['name']);

        $janePastor = collect($pastors)->firstWhere('email', 'jane@pastor.com');
        $this->assertEquals('Jane Smith', $janePastor['name']);
    }

    /** @test */
    public function it_can_get_available_slots_for_pastor_with_weekly_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create weekly availability for Monday (day 1)
        CareServiceAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
        ]);

        // Get next Monday
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);

        $response = $this->getJson('/api/care-service/available-slots?'.http_build_query([
            'pastor_id' => $pastor->id,
            'date' => $nextMonday->format('Y-m-d'),
            'duration' => 60,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'date' => $nextMonday->format('Y-m-d'),
                    'pastor_id' => $pastor->id,
                ],
            ]);

        $slots = $response->json('data.slots');
        $this->assertNotEmpty($slots);

        // Should have slots from 09:00 to 16:00 (8 slots total)
        $expectedSlots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];
        foreach ($expectedSlots as $expectedSlot) {
            $this->assertContains($expectedSlot, $slots);
        }
    }

    /** @test */
    public function it_can_get_available_slots_for_pastor_with_specific_date_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $specificDate = Carbon::now()->addDays(7);

        // Create specific date availability
        CareServiceAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => $specificDate->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '14:00',
            'slot_duration' => 30,
            'is_active' => true,
            'consultation_mode' => 'online',
        ]);

        $response = $this->getJson('/api/care-service/available-slots?'.http_build_query([
            'pastor_id' => $pastor->id,
            'date' => $specificDate->format('Y-m-d'),
            'duration' => 30,
        ]));

        $response->assertStatus(200);
        $slots = $response->json('data.slots');

        // Should have slots every 30 minutes from 10:00 to 13:30
        $expectedSlots = ['10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30'];
        foreach ($expectedSlots as $expectedSlot) {
            $this->assertContains($expectedSlot, $slots);
        }
    }

    /** @test */
    public function it_returns_empty_slots_when_pastor_has_no_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $nextMonday = Carbon::now()->next(Carbon::MONDAY);

        $response = $this->getJson('/api/care-service/available-slots?'.http_build_query([
            'pastor_id' => $pastor->id,
            'date' => $nextMonday->format('Y-m-d'),
            'duration' => 60,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'slots' => [],
                ],
            ]);
    }

    /** @test */
    public function it_excludes_booked_slots_from_available_slots(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $appointmentDate = Carbon::now()->next(Carbon::MONDAY);

        // Create availability for Monday
        CareServiceAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
        ]);

        // Book the 10:00 slot
        CareService::create([
            'pastor_id' => $pastor->id,
            'appointment_date' => $appointmentDate->format('Y-m-d'),
            'appointment_time' => $appointmentDate->copy()->setTime(10, 0),
            'duration_minutes' => 60,
            'client_name' => 'Test Client',
            'client_email' => 'test@example.com',
            'status' => 'confirmed',
        ]);

        $response = $this->getJson('/api/care-service/available-slots?'.http_build_query([
            'pastor_id' => $pastor->id,
            'date' => $appointmentDate->format('Y-m-d'),
            'duration' => 60,
        ]));

        $response->assertStatus(200);
        $slots = $response->json('data.slots');

        // 10:00 should be excluded
        $this->assertNotContains('10:00', $slots);

        // Other slots should be available
        $this->assertContains('09:00', $slots);
        $this->assertContains('11:00', $slots);
    }

    /** @test */
    public function it_can_book_an_available_slot(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $appointmentDate = Carbon::now()->next(Carbon::MONDAY);

        // Create availability
        CareServiceAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
        ]);

        $bookingData = [
            'pastor_id' => $pastor->id,
            'appointment_date' => $appointmentDate->format('Y-m-d'),
            'appointment_time' => '10:00',
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'client_phone' => '+1234567890',
            'notes' => 'Test booking',
        ];

        $response = $this->postJson('/api/care-service/appointments', $bookingData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Rendez-vous créé avec succès. Un email de confirmation va vous être envoyé.',
            ]);

        // Verify appointment was created
        $this->assertDatabaseHas('care_services', [
            'pastor_id' => $pastor->id,
            'appointment_date' => $appointmentDate->format('Y-m-d'),
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'status' => 'pending',
        ]);

        $uuid = $response->json('data.uuid');
        $this->assertNotEmpty($uuid);
    }

    /** @test */
    public function it_cannot_book_an_unavailable_slot(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $appointmentDate = Carbon::now()->next(Carbon::MONDAY);

        // Create availability only for 09:00-10:00
        CareServiceAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
        ]);

        $bookingData = [
            'pastor_id' => $pastor->id,
            'appointment_date' => $appointmentDate->format('Y-m-d'),
            'appointment_time' => '15:00', // Not available
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
        ];

        $response = $this->postJson('/api/care-service/appointments', $bookingData);

        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function it_validates_booking_request_data(): void
    {
        $response = $this->postJson('/api/care-service/appointments', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'pastor_id',
                'appointment_date',
                'appointment_time',
                'client_name',
                'client_email',
            ]);
    }

    /** @test */
    public function it_handles_day_of_week_conversion_correctly(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create availability for Sunday (day 7 in our system)
        CareServiceAvailability::create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 7, // Sunday
            'start_time' => '10:00',
            'end_time' => '12:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
        ]);

        // Get next Sunday (Carbon: dayOfWeek = 0)
        $nextSunday = Carbon::now()->next(Carbon::SUNDAY);
        $this->assertEquals(0, $nextSunday->dayOfWeek); // Verify it's Sunday in Carbon

        $response = $this->getJson('/api/care-service/available-slots?'.http_build_query([
            'pastor_id' => $pastor->id,
            'date' => $nextSunday->format('Y-m-d'),
            'duration' => 60,
        ]));

        $response->assertStatus(200);
        $slots = $response->json('data.slots');

        // Should have slots for Sunday
        $this->assertContains('10:00', $slots);
        $this->assertContains('11:00', $slots);
    }
}
