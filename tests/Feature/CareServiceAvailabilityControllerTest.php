<?php

namespace Tests\Feature;

use App\Models\CareServiceAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CareServiceAvailabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'pastor']);
        Role::create(['name' => 'member']);
        Role::create(['name' => 'admin']);
    }

    public function test_pastor_can_view_availability_index(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $response = $this->actingAs($pastor)->get('/care-service-availability');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('CareService/Availability/Index')
                ->has('availabilities')
                ->has('pastor')
        );
    }

    public function test_non_pastor_cannot_access_availability_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/care-service-availability');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_availability(): void
    {
        $response = $this->get('/care-service-availability');

        $response->assertRedirect('/login');
    }

    public function test_pastor_can_view_create_form(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $response = $this->actingAs($pastor)->get('/care-service-availability/create');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('CareService/Availability/Create')
                ->has('pastor')
        );
    }

    public function test_pastor_can_create_weekly_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $data = [
            'type' => 'weekly',
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'notes' => 'Standard Monday availability',
        ];

        $response = $this->actingAs($pastor)->post('/care-service-availability', $data);

        $response->assertRedirect('/care-service-availability');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('care_service_availability', [
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => 1,
            'notes' => 'Standard Monday availability',
        ]);
    }

    public function test_pastor_can_create_weekly_availability_for_sunday(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Sunday is day_of_week = 0 in JavaScript convention
        $data = [
            'type' => 'weekly',
            'day_of_week' => 0, // Sunday
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'notes' => 'Sunday availability',
        ];

        $response = $this->actingAs($pastor)->post('/care-service-availability', $data);

        $response->assertRedirect('/care-service-availability');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('care_service_availability', [
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => 1,
            'notes' => 'Sunday availability',
        ]);
    }

    public function test_day_of_week_validation_accepts_all_valid_days(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Test all valid days (0-6)
        for ($day = 0; $day <= 6; $day++) {
            $data = [
                'type' => 'weekly',
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'slot_duration' => 60,
                'is_active' => true,
                'consultation_mode' => 'in_person',
            ];

            $response = $this->actingAs($pastor)->post('/care-service-availability', $data);

            // Should redirect (success) or have conflict error (not validation error for day_of_week)
            $this->assertNotTrue(
                $response->getSession()->has('errors') &&
                $response->getSession()->get('errors')->has('day_of_week'),
                "Day {$day} should be valid but got validation error"
            );
        }
    }

    public function test_day_of_week_validation_rejects_invalid_days(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Test invalid day (7 - out of range)
        $data = [
            'type' => 'weekly',
            'day_of_week' => 7, // Invalid - should be 0-6
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
            'consultation_mode' => 'in_person',
        ];

        $response = $this->actingAs($pastor)->post('/care-service-availability', $data);

        $response->assertSessionHasErrors(['day_of_week']);

        // Test invalid day (-1 - negative)
        $data['day_of_week'] = -1;
        $response = $this->actingAs($pastor)->post('/care-service-availability', $data);

        $response->assertSessionHasErrors(['day_of_week']);
    }

    public function test_pastor_can_create_specific_date_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $data = [
            'type' => 'specific_date',
            'specific_date' => '2027-12-25',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'slot_duration' => 30,
            'is_active' => true,
            'consultation_mode' => 'in_person',
            'notes' => 'Christmas consultation',
        ];

        $response = $this->actingAs($pastor)->post('/care-service-availability', $data);

        $response->assertRedirect('/care-service-availability');

        $this->assertDatabaseHas('care_service_availability', [
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'slot_duration' => 30,
        ]);
    }

    public function test_cannot_create_conflicting_weekly_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create existing availability for Monday
        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'is_active' => true,
        ]);

        // Try to create another Monday availability
        $data = [
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '14:00',
            'end_time' => '18:00',
            'slot_duration' => 45,
            'consultation_mode' => 'in_person',
        ];

        $response = $this->actingAs($pastor)->post('/care-service-availability', $data);

        $response->assertSessionHasErrors('conflict');
        $this->assertEquals(1, CareServiceAvailability::where('pastor_id', $pastor->id)->count());
    }

    public function test_cannot_create_conflicting_specific_date_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create existing availability for specific date
        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => '2027-12-25',
            'is_active' => true,
        ]);

        // Try to create another availability for same date
        $data = [
            'type' => 'specific_date',
            'specific_date' => '2027-12-25',
            'start_time' => '14:00',
            'end_time' => '18:00',
            'slot_duration' => 45,
            'consultation_mode' => 'in_person',
        ];

        $response = $this->actingAs($pastor)->post('/care-service-availability', $data);

        $response->assertSessionHasErrors('conflict');
    }

    public function test_create_availability_validation_rules(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Test missing required fields
        $response = $this->actingAs($pastor)->post('/care-service-availability', []);

        $response->assertSessionHasErrors(['type', 'start_time', 'end_time', 'slot_duration', 'consultation_mode']);

        // Test weekly without day_of_week
        $response = $this->actingAs($pastor)->post('/care-service-availability', [
            'type' => 'weekly',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
        ]);

        $response->assertSessionHasErrors(['day_of_week']);

        // Test specific_date without date
        $response = $this->actingAs($pastor)->post('/care-service-availability', [
            'type' => 'specific_date',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
        ]);

        $response->assertSessionHasErrors(['specific_date']);

        // Test invalid time range (end before start)
        $response = $this->actingAs($pastor)->post('/care-service-availability', [
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '17:00',
            'end_time' => '09:00',
            'slot_duration' => 60,
        ]);

        $response->assertSessionHasErrors(['end_time']);
    }

    public function test_pastor_can_view_specific_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $availability = CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
        ]);

        $response = $this->actingAs($pastor)->get("/care-service-availability/{$availability->id}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('CareService/Availability/Show')
                ->has('availability')
                ->has('sampleSlots')
        );
    }

    public function test_pastor_cannot_view_other_care_service_availability(): void
    {
        $pastor1 = User::factory()->create();
        $pastor1->assignRole('pastor');

        $pastor2 = User::factory()->create();
        $pastor2->assignRole('pastor');

        $availability = CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor2->id,
        ]);

        $response = $this->actingAs($pastor1)->get("/care-service-availability/{$availability->id}");

        $response->assertStatus(403);
    }

    public function test_pastor_can_edit_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $availability = CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
        ]);

        $response = $this->actingAs($pastor)->get("/care-service-availability/{$availability->id}/edit");

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('CareService/Availability/Edit')
                ->has('availability')
        );
    }

    public function test_pastor_can_update_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $availability = CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'slot_duration' => 60,
            'is_active' => true,
        ]);

        $updateData = [
            'type' => 'weekly',
            'day_of_week' => 2, // Change to Tuesday
            'start_time' => '10:00',
            'end_time' => '16:00',
            'slot_duration' => 45,
            'is_active' => false,
            'notes' => 'Updated availability',
        ];

        $response = $this->actingAs($pastor)->put("/care-service-availability/{$availability->id}", $updateData);

        $response->assertRedirect('/care-service-availability');

        $this->assertDatabaseHas('care_service_availability', [
            'id' => $availability->id,
            'pastor_id' => $pastor->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'slot_duration' => 45,
            'is_active' => 0,
            'notes' => 'Updated availability',
        ]);
    }

    public function test_pastor_can_delete_availability(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $availability = CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
        ]);

        $response = $this->actingAs($pastor)->delete("/care-service-availability/{$availability->id}");

        $response->assertRedirect('/care-service-availability');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('care_service_availability', [
            'id' => $availability->id,
        ]);
    }

    public function test_pastor_can_toggle_availability_status(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $availability = CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($pastor)->post("/care-service-availability/{$availability->id}/toggle-status");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('care_service_availability', [
            'id' => $availability->id,
            'is_active' => false,
        ]);

        // Toggle back
        $this->actingAs($pastor)->post("/care-service-availability/{$availability->id}/toggle-status");

        $this->assertDatabaseHas('care_service_availability', [
            'id' => $availability->id,
            'is_active' => true,
        ]);
    }

    public function test_preview_slots_endpoint_returns_correct_data(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $data = [
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration' => 30,
        ];

        $response = $this->actingAs($pastor)->post('/care-service-availability/preview-slots', $data);

        $response->assertStatus(200);
        $response->assertJson([
            'slots' => ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30'],
            'count' => 6,
        ]);
    }

    public function test_preview_slots_validates_input(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Missing required fields
        $response = $this->actingAs($pastor)->post('/care-service-availability/preview-slots', []);

        $response->assertStatus(422);

        // Invalid time range
        $response = $this->actingAs($pastor)->post('/care-service-availability/preview-slots', [
            'start_time' => '17:00',
            'end_time' => '09:00',
            'slot_duration' => 60,
        ]);

        $response->assertStatus(422);
    }

    public function test_non_pastor_cannot_access_any_availability_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $availability = CareServiceAvailability::factory()->create();

        // Test all routes that should be forbidden
        $routes = [
            ['GET', '/care-service-availability'],
            ['GET', '/care-service-availability/create'],
            ['POST', '/care-service-availability'],
            ['GET', "/care-service-availability/{$availability->id}"],
            ['GET', "/care-service-availability/{$availability->id}/edit"],
            ['PUT', "/care-service-availability/{$availability->id}"],
            ['DELETE', "/care-service-availability/{$availability->id}"],
            ['POST', "/care-service-availability/{$availability->id}/toggle-status"],
            ['POST', '/care-service-availability/preview-slots'],
        ];

        foreach ($routes as [$method, $route]) {
            $response = $this->actingAs($user)->{strtolower($method)}($route, [
                'start_time' => '09:00',
                'end_time' => '17:00',
                'slot_duration' => 60,
            ]);

            $response->assertStatus(403);
        }
    }

    public function test_availability_index_shows_correct_data_structure(): void
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        // Create different types of availability
        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'weekly',
            'day_of_week' => 1,
        ]);

        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor->id,
            'type' => 'specific_date',
            'specific_date' => '2025-12-25',
        ]);

        $response = $this->actingAs($pastor)->get('/care-service-availability');

        $response->assertInertia(
            fn ($page) => $page
                ->component('CareService/Availability/Index')
                ->where('availabilities', fn ($availabilities): bool => count($availabilities) === 2)
                ->where('pastor.id', $pastor->id)
        );
    }
}
