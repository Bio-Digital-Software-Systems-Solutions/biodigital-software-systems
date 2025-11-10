<?php

namespace Tests\Feature;

use App\Models\PastoralCare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class PastoralCareApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $pastor;
    protected $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles
        Role::create(['name' => 'pastor']);
        Role::create(['name' => 'member']);

        // Create a pastor user
        $this->pastor = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'pastor@example.com',
        ]);
        $this->pastor->assignRole('pastor');

        // Create a test appointment
        $this->appointment = PastoralCare::create([
            'pastor_id' => $this->pastor->id,
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'client_phone' => '+1234567890',
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time' => Carbon::tomorrow()->setHour(14)->setMinute(0),
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'notes' => 'Initial consultation',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_get_list_of_pastors()
    {
        $response = $this->getJson('/api/pastoral-care/pastors');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'phone'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('John Doe', $response->json('data.0.name'));
    }

    /** @test */
    public function it_can_get_available_time_slots()
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->getJson("/api/pastoral-care/available-slots?pastor_id={$this->pastor->id}&date={$tomorrow}&duration=60");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'date',
                        'slots'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($tomorrow, $response->json('data.date'));
        $this->assertIsArray($response->json('data.slots'));
    }

    /** @test */
    public function it_can_create_new_appointment()
    {
        $appointmentData = [
            'pastor_id' => $this->pastor->id,
            'client_name' => 'New Client',
            'client_email' => 'newclient@example.com',
            'client_phone' => '+0987654321',
            'appointment_date' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
            'duration_minutes' => 60,
            'location_type' => 'zoom',
            'zoom_link' => 'https://zoom.us/j/123456789',
            'notes' => 'Follow-up consultation'
        ];

        $response = $this->postJson('/api/pastoral-care/appointments', $appointmentData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'uuid',
                        'appointment' => [
                            'id',
                            'uuid',
                            'client_name',
                            'client_email',
                            'appointment_date',
                            'appointment_time',
                            'duration_minutes',
                            'location_type',
                            'status',
                            'pastor' => [
                                'name',
                                'email'
                            ]
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('New Client', $response->json('data.appointment.client_name'));
        $this->assertEquals('pending', $response->json('data.appointment.status'));

        $this->assertDatabaseHas('pastoral_cares', [
            'client_name' => 'New Client',
            'client_email' => 'newclient@example.com',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_can_show_appointment_details()
    {
        $response = $this->getJson("/api/pastoral-care/appointments/{$this->appointment->uuid}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'uuid',
                        'client_name',
                        'client_email',
                        'client_phone',
                        'appointment_date',
                        'appointment_time',
                        'duration_minutes',
                        'location_type',
                        'status',
                        'notes',
                        'pastor_notes',
                        'pastor' => [
                            'name',
                            'email'
                        ],
                        'can_be_confirmed',
                        'can_be_cancelled'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->appointment->client_name, $response->json('data.client_name'));
        $this->assertEquals('John Doe', $response->json('data.pastor.name'));
    }

    /** @test */
    public function it_can_confirm_appointment()
    {
        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirm");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertTrue($response->json('success'));

        $this->appointment->refresh();
        $this->assertEquals('confirmed', $this->appointment->status);
        $this->assertNotNull($this->appointment->confirmation_sent_at);
    }

    /** @test */
    public function it_cannot_confirm_appointment_that_cannot_be_confirmed()
    {
        // Make appointment already confirmed
        $this->appointment->update(['status' => 'confirmed']);

        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirm");

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_can_cancel_appointment()
    {
        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/cancel", [
            'cancellation_reason' => 'Client requested cancellation'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertTrue($response->json('success'));

        $this->appointment->refresh();
        $this->assertEquals('cancelled', $this->appointment->status);
        $this->assertEquals('Client requested cancellation', $this->appointment->cancellation_reason);
        $this->assertNotNull($this->appointment->cancelled_at);
    }

    /** @test */
    public function it_can_complete_appointment()
    {
        // First confirm the appointment
        $this->appointment->update(['status' => 'confirmed']);

        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/complete");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertTrue($response->json('success'));

        $this->appointment->refresh();
        $this->assertEquals('completed', $this->appointment->status);
    }

    /** @test */
    public function it_cannot_complete_appointment_that_is_not_confirmed()
    {
        // Appointment is pending, not confirmed
        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/complete");

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_can_mark_appointment_as_no_show()
    {
        // Set appointment to confirmed and in the past
        $this->appointment->update([
            'status' => 'confirmed',
            'appointment_date' => Carbon::yesterday(),
            'appointment_time' => Carbon::yesterday()->setHour(14)->setMinute(0)
        ]);

        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/no-show");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertTrue($response->json('success'));

        $this->appointment->refresh();
        $this->assertEquals('no_show', $this->appointment->status);
    }

    /** @test */
    public function it_can_update_pastor_notes()
    {
        $this->actingAs($this->pastor);

        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}", [
            'pastor_notes' => 'Client discussed family issues. Provided guidance on communication strategies. Recommended follow-up in 2 weeks.'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);

        $this->assertTrue($response->json('success'));

        $this->appointment->refresh();
        $this->assertStringContains('Client discussed family issues', $this->appointment->pastor_notes);
    }

    /** @test */
    public function it_validates_pastor_notes_length()
    {
        $this->actingAs($this->pastor);

        // Generate a string longer than 2000 characters
        $longNotes = str_repeat('A very long note. ', 150); // Should exceed 2000 chars

        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}", [
            'pastor_notes' => $longNotes
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['pastor_notes']);
    }

    /** @test */
    public function it_requires_authentication_for_protected_endpoints()
    {
        // Test update endpoint requires authentication
        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}", [
            'pastor_notes' => 'Some notes'
        ]);

        $response->assertStatus(401);

        // Test complete endpoint requires authentication
        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/complete");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_pastor_role_for_pastor_specific_actions()
    {
        // Create a regular user (not a pastor)
        $regularUser = User::factory()->create();
        $regularUser->assignRole('member');

        $this->actingAs($regularUser);

        // Try to update pastor notes
        $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}", [
            'pastor_notes' => 'Some notes'
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthorized - Pastor access required'
                ]);
    }

    /** @test */
    public function it_validates_appointment_creation_data()
    {
        $invalidData = [
            'pastor_id' => 'invalid',
            'client_name' => '',
            'client_email' => 'invalid-email',
            'appointment_date' => 'invalid-date',
            'appointment_time' => 'invalid-time',
            'duration_minutes' => 'invalid',
            'location_type' => 'invalid-type',
            'zoom_link' => 'invalid-url'
        ];

        $response = $this->postJson('/api/pastoral-care/appointments', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'pastor_id',
                    'client_name',
                    'client_email',
                    'appointment_date',
                    'appointment_time',
                    'duration_minutes',
                    'location_type'
                ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_appointment()
    {
        $response = $this->getJson('/api/pastoral-care/appointments/non-existent-uuid');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Rendez-vous introuvable'
                ]);
    }

    /** @test */
    public function it_checks_time_slot_availability_when_creating_appointment()
    {
        // Create an appointment that conflicts with existing one
        $conflictingData = [
            'pastor_id' => $this->pastor->id,
            'client_name' => 'Conflicting Client',
            'client_email' => 'conflict@example.com',
            'appointment_date' => $this->appointment->appointment_date->format('Y-m-d'),
            'appointment_time' => $this->appointment->appointment_time->format('H:i'),
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'notes' => 'This should conflict'
        ];

        $response = $this->postJson('/api/pastoral-care/appointments', $conflictingData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['appointment_time']);
    }

    /** @test */
    public function it_can_get_appointments_list_for_authenticated_pastor()
    {
        $this->actingAs($this->pastor);

        // Create another appointment for the same pastor
        PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
            'status' => 'confirmed'
        ]);

        $response = $this->getJson('/api/pastoral-care/appointments');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'uuid',
                                'client_name',
                                'appointment_date',
                                'status'
                            ]
                        ]
                    ],
                    'stats' => [
                        'pending',
                        'confirmed',
                        'completed',
                        'cancelled'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_filters_appointments_by_status()
    {
        $this->actingAs($this->pastor);

        // Create appointments with different statuses
        PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
            'status' => 'confirmed'
        ]);

        PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
            'status' => 'completed'
        ]);

        $response = $this->getJson('/api/pastoral-care/appointments?status=pending');

        $response->assertStatus(200);

        $appointments = $response->json('data.data');
        foreach ($appointments as $appointment) {
            $this->assertEquals('pending', $appointment['status']);
        }
    }

    /** @test */
    public function it_can_delete_appointment_as_pastor()
    {
        $this->actingAs($this->pastor);

        $response = $this->deleteJson("/api/pastoral-care/appointments/{$this->appointment->uuid}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Rendez-vous supprimé avec succès'
                ]);

        $this->assertSoftDeleted('pastoral_cares', [
            'id' => $this->appointment->id
        ]);
    }
}