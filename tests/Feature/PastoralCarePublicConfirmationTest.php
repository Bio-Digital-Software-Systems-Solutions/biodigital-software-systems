<?php

use App\Models\PastoralCare;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create necessary roles
    Role::create(['name' => 'pastor']);
    Role::create(['name' => 'member']);

    // Create a pastor user
    $this->pastor = User::factory()->create([
        'first_name' => 'Marie',
        'last_name' => 'Bernard',
        'email' => 'marie.bernard@icc-munich.de',
    ]);
    $this->pastor->assignRole('pastor');

    // Create a pending appointment (set to 3 days in future to allow cancellation)
    // Note: Cancellation requires appointment to be > 24 hours in the future
    $appointmentDate = Carbon::now()->addDays(3);
    $this->appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Philippe Garnier',
        'client_email' => 'philippe.garnier@icc-munich.de',
        'client_phone' => '1234567890',
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => $appointmentDate->setHour(10)->setMinute(0),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'notes' => 'Gestion des finances dans le couple',
        'status' => 'pending',
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Confirmation Tests
|--------------------------------------------------------------------------
*/

it('can confirm a pending appointment via public API without authentication', function (): void {
    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirm");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Rendez-vous confirmé avec succès',
        ]);

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe('confirmed');
    expect($this->appointment->confirmation_sent_at)->not->toBeNull();
});

it('returns 404 when confirming non-existent appointment', function (): void {
    $response = $this->postJson('/api/pastoral-care/appointments/non-existent-uuid/confirm');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Rendez-vous introuvable',
        ]);
});

it('cannot confirm an already confirmed appointment', function (): void {
    $this->appointment->update(['status' => 'confirmed']);

    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirm");

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);

    expect($response->json('message'))->toContain('confirm');
});

it('cannot confirm a cancelled appointment', function (): void {
    $this->appointment->update([
        'status' => 'cancelled',
        'cancelled_at' => now(),
        'cancellation_reason' => 'Test cancellation',
    ]);

    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirm");

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

it('cannot confirm a completed appointment', function (): void {
    $this->appointment->update([
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirm");

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

/*
|--------------------------------------------------------------------------
| Public Cancellation Tests
|--------------------------------------------------------------------------
*/

it('can cancel a pending appointment via public API without authentication', function (): void {
    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/cancel", [
        'cancellation_reason' => 'Empêchement personnel',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Rendez-vous annulé avec succès',
        ]);

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe('cancelled');
    expect($this->appointment->cancellation_reason)->toBe('Empêchement personnel');
    expect($this->appointment->cancelled_at)->not->toBeNull();
});

it('can cancel a confirmed appointment via public API', function (): void {
    $this->appointment->update(['status' => 'confirmed']);

    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/cancel", [
        'cancellation_reason' => 'Urgence familiale',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Rendez-vous annulé avec succès',
        ]);

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe('cancelled');
});

it('can cancel without providing a reason', function (): void {
    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/cancel", []);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe('cancelled');
    expect($this->appointment->cancellation_reason)->toBeNull();
});

it('returns 404 when cancelling non-existent appointment', function (): void {
    $response = $this->postJson('/api/pastoral-care/appointments/non-existent-uuid/cancel', [
        'cancellation_reason' => 'Test',
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Rendez-vous introuvable',
        ]);
});

it('cannot cancel an already cancelled appointment', function (): void {
    $this->appointment->update([
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/cancel", [
        'cancellation_reason' => 'Test',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

it('cannot cancel a completed appointment', function (): void {
    $this->appointment->update([
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/cancel", [
        'cancellation_reason' => 'Test',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

it('validates cancellation reason max length', function (): void {
    $longReason = str_repeat('A', 501);

    $response = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/cancel", [
        'cancellation_reason' => $longReason,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cancellation_reason']);
});

it('cannot cancel appointment within 24 hours of appointment time', function (): void {
    // Create appointment for less than 24 hours from now
    $soonAppointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::now()->addHours(12)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(12),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/pastoral-care/appointments/{$soonAppointment->uuid}/cancel", [
        'cancellation_reason' => 'Test',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

/*
|--------------------------------------------------------------------------
| Show Appointment Details Tests
|--------------------------------------------------------------------------
*/

it('can view appointment details via public API', function (): void {
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
                'pastor' => [
                    'name',
                    'email',
                ],
                'can_be_confirmed',
                'can_be_cancelled',
            ],
        ]);

    expect($response->json('data.client_name'))->toBe('Philippe Garnier');
    expect($response->json('data.pastor.name'))->toBe('Marie Bernard');
    expect($response->json('data.can_be_confirmed'))->toBeTrue();
    expect($response->json('data.can_be_cancelled'))->toBeTrue();
});

it('returns correct confirmability status for different appointment states', function (): void {
    // Pending - can be confirmed and cancelled
    $response = $this->getJson("/api/pastoral-care/appointments/{$this->appointment->uuid}");
    expect($response->json('data.can_be_confirmed'))->toBeTrue();
    expect($response->json('data.can_be_cancelled'))->toBeTrue();

    // Confirmed - cannot be confirmed again, but can be cancelled
    $this->appointment->update(['status' => 'confirmed']);
    $response = $this->getJson("/api/pastoral-care/appointments/{$this->appointment->uuid}");
    expect($response->json('data.can_be_confirmed'))->toBeFalse();
    expect($response->json('data.can_be_cancelled'))->toBeTrue();

    // Cancelled - cannot be confirmed or cancelled
    $this->appointment->update(['status' => 'cancelled']);
    $response = $this->getJson("/api/pastoral-care/appointments/{$this->appointment->uuid}");
    expect($response->json('data.can_be_confirmed'))->toBeFalse();
    expect($response->json('data.can_be_cancelled'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Confirmation Status Tests
|--------------------------------------------------------------------------
*/

it('can get confirmation status via public API', function (): void {
    $response = $this->getJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirmation-status");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'uuid',
                'status',
                'confirmation_status',
                'appointment_date',
                'appointment_time',
            ],
        ]);

    expect($response->json('data.status'))->toBe('pending');
});

it('returns 404 for confirmation status of non-existent appointment', function (): void {
    $response = $this->getJson('/api/pastoral-care/appointments/fake-uuid/confirmation-status');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Rendez-vous introuvable',
        ]);
});

/*
|--------------------------------------------------------------------------
| Edge Cases & Security Tests
|--------------------------------------------------------------------------
*/

it('handles concurrent confirmation attempts gracefully', function (): void {
    // Simulate concurrent request by confirming twice
    $response1 = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirm");
    $response1->assertStatus(200);

    $response2 = $this->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirm");
    $response2->assertStatus(400);
});

it('handles SQL injection attempts in UUID', function (): void {
    $maliciousUuid = "'; DROP TABLE pastoral_cares; --";

    $response = $this->getJson("/api/pastoral-care/appointments/{$maliciousUuid}");

    $response->assertStatus(404);
});

it('returns proper error for empty UUID', function (): void {
    $response = $this->getJson('/api/pastoral-care/appointments/');

    // Empty path hits the index route which requires auth, so 401 is also acceptable
    expect($response->status())->toBeIn([401, 404, 405]);
});

/*
|--------------------------------------------------------------------------
| Location Type Specific Tests
|--------------------------------------------------------------------------
*/

it('can confirm zoom appointment', function (): void {
    $zoomAppointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(15)->setMinute(0),
        'duration_minutes' => 45,
        'location_type' => 'zoom',
        'zoom_link' => 'https://zoom.us/j/123456789',
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/pastoral-care/appointments/{$zoomAppointment->uuid}/confirm");

    $response->assertStatus(200);

    $zoomAppointment->refresh();
    expect($zoomAppointment->status)->toBe('confirmed');
    expect($zoomAppointment->location_type)->toBe('zoom');
});

it('can confirm hybrid appointment', function (): void {
    $hybridAppointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(16)->setMinute(0),
        'duration_minutes' => 30,
        'location_type' => 'hybrid',
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/pastoral-care/appointments/{$hybridAppointment->uuid}/confirm");

    $response->assertStatus(200);

    $hybridAppointment->refresh();
    expect($hybridAppointment->status)->toBe('confirmed');
});

/*
|--------------------------------------------------------------------------
| Different Duration Tests
|--------------------------------------------------------------------------
*/

it('can confirm appointments with different durations', function ($duration): void {
    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::tomorrow()->addDays(random_int(1, 7)),
        'appointment_time' => Carbon::tomorrow()->setHour(random_int(9, 17))->setMinute(0),
        'duration_minutes' => $duration,
        'location_type' => 'in_person',
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/pastoral-care/appointments/{$appointment->uuid}/confirm");

    $response->assertStatus(200);

    $appointment->refresh();
    expect($appointment->status)->toBe('confirmed');
    expect($appointment->duration_minutes)->toBe($duration);
})->with([30, 45, 60, 90, 120]);
