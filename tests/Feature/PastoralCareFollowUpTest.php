<?php

namespace Tests\Feature;

use App\Mail\PastoralCareFollowUpNotification;
use App\Models\PastoralCare;
use App\Models\PastorAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PastoralCareFollowUpTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $pastor;

    protected User $admin;

    protected User $regularUser;

    protected PastoralCare $appointment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\PastorRoleSeeder::class);

        // Create users with proper roles
        $this->pastor = User::factory()->create();
        $this->pastor->assignRole('pastor');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create();

        // Create a completed appointment for the pastor
        $this->appointment = PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'client_phone' => '+49 123 456789',
            'appointment_date' => now()->subDays(1),
            'appointment_time' => now()->subDays(1)->setTime(10, 0),
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'status' => 'completed',
        ]);

        // Create availability for the pastor (future dates)
        PastorAvailability::factory()->create([
            'pastor_id' => $this->pastor->id,
            'type' => 'weekly',
            'day_of_week' => now()->addDays(7)->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function pastor_can_create_follow_up_from_existing_appointment(): void
    {
        Mail::fake();

        $nextWeek = now()->addDays(7);

        $response = $this->actingAs($this->pastor)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                'appointment_date' => $nextWeek->format('Y-m-d'),
                'appointment_time' => '10:00',
                'duration_minutes' => 60,
                'location_type' => 'in_person',
                'notes' => 'Follow-up discussion',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // Verify the follow-up appointment was created
        $this->assertDatabaseHas('pastoral_cares', [
            'parent_id' => $this->appointment->id,
            'pastor_id' => $this->pastor->id,
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function follow_up_preserves_client_information(): void
    {
        Mail::fake();

        $nextWeek = now()->addDays(7);

        $response = $this->actingAs($this->pastor)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                'appointment_date' => $nextWeek->format('Y-m-d'),
                'appointment_time' => '10:00',
            ]);

        $response->assertStatus(201);

        $followUp = PastoralCare::where('parent_id', $this->appointment->id)->first();

        $this->assertNotNull($followUp);
        $this->assertEquals($this->appointment->client_name, $followUp->client_name);
        $this->assertEquals($this->appointment->client_email, $followUp->client_email);
        $this->assertEquals($this->appointment->client_phone, $followUp->client_phone);
    }

    /** @test */
    public function follow_up_links_to_parent_appointment(): void
    {
        Mail::fake();

        $nextWeek = now()->addDays(7);

        $response = $this->actingAs($this->pastor)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                'appointment_date' => $nextWeek->format('Y-m-d'),
                'appointment_time' => '10:00',
            ]);

        $response->assertStatus(201);

        $followUp = PastoralCare::where('parent_id', $this->appointment->id)->first();

        $this->assertNotNull($followUp);
        $this->assertEquals($this->appointment->id, $followUp->parent_id);

        // Test relationship
        $this->assertEquals($this->appointment->id, $followUp->parent->id);

        // Test inverse relationship
        $this->assertTrue($this->appointment->followUps->contains($followUp));
    }

    /** @test */
    public function follow_up_sends_notifications_to_participants(): void
    {
        Mail::fake();

        $nextWeek = now()->addDays(7);

        $this->actingAs($this->pastor)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                'appointment_date' => $nextWeek->format('Y-m-d'),
                'appointment_time' => '10:00',
            ]);

        // Verify email was sent to client
        Mail::assertQueued(PastoralCareFollowUpNotification::class, function ($mail) {
            return $mail->hasTo($this->appointment->client_email);
        });
    }

    /** @test */
    public function non_pastor_cannot_create_follow_up(): void
    {
        $nextWeek = now()->addDays(7);

        $response = $this->actingAs($this->regularUser)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                'appointment_date' => $nextWeek->format('Y-m-d'),
                'appointment_time' => '10:00',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function pastor_cannot_create_follow_up_for_another_pastors_appointment(): void
    {
        $anotherPastor = User::factory()->create();
        $anotherPastor->assignRole('pastor');

        $nextWeek = now()->addDays(7);

        $response = $this->actingAs($anotherPastor)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                'appointment_date' => $nextWeek->format('Y-m-d'),
                'appointment_time' => '10:00',
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function follow_up_requires_valid_date_and_time(): void
    {
        $response = $this->actingAs($this->pastor)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                // Missing required fields
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_date', 'appointment_time']);
    }

    /** @test */
    public function follow_up_cannot_be_scheduled_in_the_past(): void
    {
        $yesterday = now()->subDay();

        $response = $this->actingAs($this->pastor)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                'appointment_date' => $yesterday->format('Y-m-d'),
                'appointment_time' => '10:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_date']);
    }

    /** @test */
    public function follow_up_inherits_duration_from_parent_if_not_provided(): void
    {
        Mail::fake();

        $nextWeek = now()->addDays(7);

        $response = $this->actingAs($this->pastor)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                'appointment_date' => $nextWeek->format('Y-m-d'),
                'appointment_time' => '10:00',
                // duration_minutes not provided, should inherit from parent
            ]);

        $response->assertStatus(201);

        $followUp = PastoralCare::where('parent_id', $this->appointment->id)->first();
        $this->assertEquals($this->appointment->duration_minutes, $followUp->duration_minutes);
    }

    /** @test */
    public function follow_up_inherits_location_type_from_parent_if_not_provided(): void
    {
        Mail::fake();

        $nextWeek = now()->addDays(7);

        $response = $this->actingAs($this->pastor)
            ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
                'appointment_date' => $nextWeek->format('Y-m-d'),
                'appointment_time' => '10:00',
                // location_type not provided, should inherit from parent
            ]);

        $response->assertStatus(201);

        $followUp = PastoralCare::where('parent_id', $this->appointment->id)->first();
        $this->assertEquals($this->appointment->location_type, $followUp->location_type);
    }

    /** @test */
    public function parent_appointment_is_accessible_via_relationship(): void
    {
        // Create a follow-up appointment
        $followUp = PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
            'parent_id' => $this->appointment->id,
            'client_name' => $this->appointment->client_name,
            'client_email' => $this->appointment->client_email,
            'appointment_date' => now()->addDays(7),
            'appointment_time' => now()->addDays(7)->setTime(10, 0),
            'status' => 'pending',
        ]);

        // Load the follow-up with the parent relationship (mimics controller behavior)
        $loadedFollowUp = PastoralCare::with('parent.pastor')->find($followUp->id);

        // Verify the parent relationship is loaded correctly
        $this->assertNotNull($loadedFollowUp->parent);
        $this->assertEquals($this->appointment->id, $loadedFollowUp->parent->id);
        $this->assertEquals($this->appointment->uuid, $loadedFollowUp->parent->uuid);
        $this->assertNotNull($loadedFollowUp->parent->pastor);
    }

    /** @test */
    public function follow_ups_are_accessible_via_relationship(): void
    {
        // Create follow-up appointments
        $followUp1 = PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
            'parent_id' => $this->appointment->id,
            'client_name' => $this->appointment->client_name,
            'client_email' => $this->appointment->client_email,
            'appointment_date' => now()->addDays(7),
            'appointment_time' => now()->addDays(7)->setTime(10, 0),
            'status' => 'pending',
        ]);

        $followUp2 = PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
            'parent_id' => $this->appointment->id,
            'client_name' => $this->appointment->client_name,
            'client_email' => $this->appointment->client_email,
            'appointment_date' => now()->addDays(14),
            'appointment_time' => now()->addDays(14)->setTime(14, 0),
            'status' => 'confirmed',
        ]);

        // Load the parent with the followUps relationship (mimics controller behavior)
        $loadedParent = PastoralCare::with('followUps.pastor')->find($this->appointment->id);

        // Verify the followUps relationship is loaded correctly
        $this->assertCount(2, $loadedParent->followUps);
        $this->assertTrue($loadedParent->followUps->contains('id', $followUp1->id));
        $this->assertTrue($loadedParent->followUps->contains('id', $followUp2->id));

        // Verify pastor is loaded for each follow-up
        foreach ($loadedParent->followUps as $followUp) {
            $this->assertNotNull($followUp->pastor);
        }
    }
}
