<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PastoralCare;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PastoralAppointmentsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\PastorRoleSeeder::class);
    }

    /** @test */
    public function pastor_can_view_their_appointments()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $anotherPastor = User::factory()->create();
        $anotherPastor->assignRole('pastor');

        $client = User::factory()->create();
        $client->assignRole('member');

        // Create appointments for the pastor
        $appointment1 = PastoralCare::create([
            'pastor_id' => $pastor->id,
            'user_id' => $client->id,
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time' => Carbon::tomorrow()->setTime(10, 0),
            'duration_minutes' => 60,
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'status' => 'pending',
            'location_type' => 'in_person'
        ]);

        // Create appointment for another pastor (should not be visible)
        $appointment2 = PastoralCare::create([
            'pastor_id' => $anotherPastor->id,
            'user_id' => $client->id,
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time' => Carbon::tomorrow()->setTime(14, 0),
            'duration_minutes' => 60,
            'client_name' => 'Jane Smith',
            'client_email' => 'jane@example.com',
            'status' => 'confirmed',
            'location_type' => 'online'
        ]);

        $response = $this->actingAs($pastor)
            ->get('/pastoral-care/appointments');

        $response->assertStatus(200);

        // Check that the page contains the pastor's appointment
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith'); // Should not see other pastor's appointments
    }

    /** @test */
    public function admin_can_view_all_appointments()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pastor1 = User::factory()->create();
        $pastor1->assignRole('pastor');

        $pastor2 = User::factory()->create();
        $pastor2->assignRole('pastor');

        // Create appointments for different pastors
        $appointment1 = PastoralCare::create([
            'pastor_id' => $pastor1->id,
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time' => Carbon::tomorrow()->setTime(10, 0),
            'duration_minutes' => 60,
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'status' => 'pending',
            'location_type' => 'in_person'
        ]);

        $appointment2 = PastoralCare::create([
            'pastor_id' => $pastor2->id,
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time' => Carbon::tomorrow()->setTime(14, 0),
            'duration_minutes' => 60,
            'client_name' => 'Jane Smith',
            'client_email' => 'jane@example.com',
            'status' => 'confirmed',
            'location_type' => 'online'
        ]);

        $response = $this->actingAs($admin)
            ->get('/pastoral-care/appointments');

        $response->assertStatus(200);

        // Admin should see all appointments
        $response->assertSee('John Doe');
        $response->assertSee('Jane Smith');
    }

    /** @test */
    public function non_pastor_cannot_access_appointments_page()
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)
            ->get('/pastoral-care/appointments');

        // Should redirect or show 403
        $this->assertTrue(
            $response->status() === 403 ||
            $response->status() === 302
        );
    }

    /** @test */
    public function pastor_can_confirm_appointment()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $appointment = PastoralCare::create([
            'pastor_id' => $pastor->id,
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time' => Carbon::tomorrow()->setTime(10, 0),
            'duration_minutes' => 60,
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'status' => 'pending',
            'location_type' => 'in_person'
        ]);

        $response = $this->actingAs($pastor)
            ->post("/pastoral-care/appointments/{$appointment->uuid}/confirm");

        $response->assertStatus(302); // Redirect

        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
    }

    /** @test */
    public function pastor_can_complete_appointment()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $appointment = PastoralCare::create([
            'pastor_id' => $pastor->id,
            'appointment_date' => Carbon::yesterday(),
            'appointment_time' => Carbon::yesterday()->setTime(10, 0),
            'duration_minutes' => 60,
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'status' => 'confirmed',
            'location_type' => 'in_person'
        ]);

        $response = $this->actingAs($pastor)
            ->post("/pastoral-care/appointments/{$appointment->uuid}/complete", [
                'pastor_notes' => 'Session completed successfully'
            ]);

        $response->assertStatus(302);

        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);
        $this->assertEquals('Session completed successfully', $appointment->pastor_notes);
    }

    /** @test */
    public function pastor_can_mark_appointment_as_no_show()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $appointment = PastoralCare::create([
            'pastor_id' => $pastor->id,
            'appointment_date' => Carbon::yesterday(),
            'appointment_time' => Carbon::yesterday()->setTime(10, 0),
            'duration_minutes' => 60,
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'status' => 'confirmed',
            'location_type' => 'in_person'
        ]);

        $response = $this->actingAs($pastor)
            ->post("/pastoral-care/appointments/{$appointment->uuid}/no-show");

        $response->assertStatus(302);

        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);
    }

    /** @test */
    public function pastor_cannot_manage_other_pastor_appointments()
    {
        $pastor1 = User::factory()->create();
        $pastor1->assignRole('pastor');

        $pastor2 = User::factory()->create();
        $pastor2->assignRole('pastor');

        $appointment = PastoralCare::create([
            'pastor_id' => $pastor2->id,
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time' => Carbon::tomorrow()->setTime(10, 0),
            'duration_minutes' => 60,
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'status' => 'pending',
            'location_type' => 'in_person'
        ]);

        $response = $this->actingAs($pastor1)
            ->post("/pastoral-care/appointments/{$appointment->uuid}/confirm");

        $response->assertStatus(403); // Forbidden

        $appointment->refresh();
        $this->assertEquals('pending', $appointment->status); // Status unchanged
    }

    /** @test */
    public function appointment_api_filters_by_pastor()
    {
        $pastor1 = User::factory()->create();
        $pastor1->assignRole('pastor');

        $pastor2 = User::factory()->create();
        $pastor2->assignRole('pastor');

        // Create appointments for both pastors
        $appointment1 = PastoralCare::create([
            'pastor_id' => $pastor1->id,
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time' => Carbon::tomorrow()->setTime(10, 0),
            'duration_minutes' => 60,
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'status' => 'pending',
            'location_type' => 'in_person'
        ]);

        $appointment2 = PastoralCare::create([
            'pastor_id' => $pastor2->id,
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time' => Carbon::tomorrow()->setTime(14, 0),
            'duration_minutes' => 60,
            'client_name' => 'Jane Smith',
            'client_email' => 'jane@example.com',
            'status' => 'confirmed',
            'location_type' => 'online'
        ]);

        // Test with Sanctum authentication
        $response = $this->actingAs($pastor1, 'sanctum')
            ->getJson('/api/pastoral-care/appointments');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $appointments = $response->json('data.appointments');

        // Pastor1 should only see their own appointment
        $this->assertCount(1, $appointments);
        $this->assertEquals($appointment1->uuid, $appointments[0]['uuid']);
        $this->assertEquals('John Doe', $appointments[0]['client_name']);
    }

    /** @test */
    public function it_validates_appointment_time_is_in_future_for_booking()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $pastDate = Carbon::yesterday();

        $bookingData = [
            'pastor_id' => $pastor->id,
            'appointment_date' => $pastDate->format('Y-m-d'),
            'appointment_time' => '10:00',
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com'
        ];

        $response = $this->postJson('/api/pastoral-care/appointments', $bookingData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_date']);
    }

    /** @test */
    public function it_handles_timezone_correctly_for_appointments()
    {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $tomorrow = Carbon::tomorrow();

        $appointment = PastoralCare::create([
            'pastor_id' => $pastor->id,
            'appointment_date' => $tomorrow->format('Y-m-d'),
            'appointment_time' => $tomorrow->setTime(15, 30), // 3:30 PM
            'duration_minutes' => 60,
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'status' => 'confirmed',
            'location_type' => 'in_person'
        ]);

        $response = $this->actingAs($pastor, 'sanctum')
            ->getJson('/api/pastoral-care/appointments');

        $response->assertStatus(200);

        $appointmentData = $response->json('data.appointments.0');
        $this->assertEquals('15:30', $appointmentData['appointment_time']);
    }
}