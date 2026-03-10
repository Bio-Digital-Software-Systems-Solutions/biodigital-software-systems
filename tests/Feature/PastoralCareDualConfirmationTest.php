<?php

use App\Mail\PastoralCareDualConfirmationNotification;
use App\Mail\PastoralCareFollowUpNotification;
use App\Mail\PastoralCarePartialConfirmationNotification;
use App\Mail\PastoralCarePastorFollowUpNotification;
use App\Models\PastoralCare;
use App\Models\PastorAvailability;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Activitylog\Models\Activity;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Database\Seeders\PastorRoleSeeder::class);

    // Create pastor
    $this->pastor = User::factory()->create();
    $this->pastor->assignRole('pastor');

    // Create a pending appointment for testing
    $this->appointment = PastoralCare::factory()->create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'client@example.com',
        'client_phone' => '+49 123 456789',
        'appointment_date' => now()->addDays(7),
        'appointment_time' => now()->addDays(7)->setTime(10, 0),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'pending',
    ]);

    // Create availability for the pastor
    PastorAvailability::factory()->create([
        'pastor_id' => $this->pastor->id,
        'type' => 'weekly',
        'day_of_week' => now()->addDays(14)->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'is_active' => true,
    ]);
});

// ===== Token Generation Tests =====

test('appointment generates confirmation tokens on creation', function (): void {
    $appointment = PastoralCare::factory()->create([
        'pastor_id' => $this->pastor->id,
        'client_email' => 'test@example.com',
        'appointment_date' => now()->addDays(7),
        'appointment_time' => now()->addDays(7)->setTime(10, 0),
    ]);

    expect($appointment->client_confirmation_token)->not->toBeNull()
        ->and($appointment->client_confirmation_token)->toHaveLength(64)
        ->and($appointment->pastor_confirmation_token)->not->toBeNull()
        ->and($appointment->pastor_confirmation_token)->toHaveLength(64)
        ->and($appointment->client_confirmation_token)->not->toBe($appointment->pastor_confirmation_token);
});

test('confirmation tokens are unique per appointment', function (): void {
    $appointment1 = PastoralCare::factory()->create(['pastor_id' => $this->pastor->id]);
    $appointment2 = PastoralCare::factory()->create(['pastor_id' => $this->pastor->id]);

    expect($appointment1->client_confirmation_token)->not->toBe($appointment2->client_confirmation_token)
        ->and($appointment1->pastor_confirmation_token)->not->toBe($appointment2->pastor_confirmation_token);
});

// ===== Client Confirmation Tests =====

test('client can confirm appointment with valid token', function (): void {
    Mail::fake();

    $response = $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => $this->appointment->client_confirmation_token,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'confirmation_status' => [
                    'client_confirmed' => true,
                    'pastor_confirmed' => false,
                ],
            ],
        ]);

    $this->appointment->refresh();
    expect($this->appointment->client_confirmed_at)->not->toBeNull()
        ->and($this->appointment->pastor_confirmed_at)->toBeNull()
        ->and($this->appointment->status)->toBe('pending'); // Not fully confirmed yet
});

test('client cannot confirm with invalid token', function (): void {
    // Use a valid-length token that doesn't exist in the database
    $response = $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => str_repeat('x', 64), // 64-char token that doesn't exist
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
        ]);
});

test('client confirmation validates token length', function (): void {
    $response = $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => 'short_token',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('client cannot confirm twice', function (): void {
    $this->appointment->confirmByClient($this->appointment->client_confirmation_token);

    $response = $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => $this->appointment->client_confirmation_token,
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

test('client cannot confirm past appointment', function (): void {
    $pastAppointment = PastoralCare::factory()->create([
        'pastor_id' => $this->pastor->id,
        'appointment_date' => now()->subDays(1),
        'appointment_time' => now()->subDays(1)->setTime(10, 0),
        'status' => 'pending',
    ]);

    $response = $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => $pastAppointment->client_confirmation_token,
    ]);

    $response->assertStatus(400);
});

test('client cannot confirm cancelled appointment', function (): void {
    $this->appointment->update(['status' => 'cancelled']);

    $response = $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => $this->appointment->client_confirmation_token,
    ]);

    $response->assertStatus(400);
});

// ===== Pastor Confirmation Tests =====

test('pastor can confirm appointment with valid token', function (): void {
    Mail::fake();

    $response = $this->postJson('/api/pastoral-care/confirm-by-pastor', [
        'token' => $this->appointment->pastor_confirmation_token,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'confirmation_status' => [
                    'client_confirmed' => false,
                    'pastor_confirmed' => true,
                ],
            ],
        ]);

    $this->appointment->refresh();
    expect($this->appointment->pastor_confirmed_at)->not->toBeNull()
        ->and($this->appointment->client_confirmed_at)->toBeNull()
        ->and($this->appointment->status)->toBe('pending'); // Not fully confirmed yet
});

test('pastor cannot confirm with invalid token', function (): void {
    // Use a valid-length token that doesn't exist in the database
    $response = $this->postJson('/api/pastoral-care/confirm-by-pastor', [
        'token' => str_repeat('y', 64), // 64-char token that doesn't exist
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
        ]);
});

test('pastor confirmation validates token length', function (): void {
    $response = $this->postJson('/api/pastoral-care/confirm-by-pastor', [
        'token' => 'short_token',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('pastor cannot confirm twice', function (): void {
    $this->appointment->confirmByPastor($this->appointment->pastor_confirmation_token);

    $response = $this->postJson('/api/pastoral-care/confirm-by-pastor', [
        'token' => $this->appointment->pastor_confirmation_token,
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

// ===== Dual Confirmation Tests =====

test('appointment is confirmed when both parties confirm', function (): void {
    Mail::fake();

    // Client confirms first
    $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => $this->appointment->client_confirmation_token,
    ]);

    // Pastor confirms second
    $response = $this->postJson('/api/pastoral-care/confirm-by-pastor', [
        'token' => $this->appointment->pastor_confirmation_token,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'confirmation_status' => [
                    'client_confirmed' => true,
                    'pastor_confirmed' => true,
                    'is_fully_confirmed' => true,
                ],
                'status' => 'confirmed',
            ],
        ]);

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe('confirmed')
        ->and($this->appointment->is_fully_confirmed)->toBeTrue();
});

test('order of confirmation does not matter', function (): void {
    Mail::fake();

    // Pastor confirms first
    $this->postJson('/api/pastoral-care/confirm-by-pastor', [
        'token' => $this->appointment->pastor_confirmation_token,
    ]);

    // Client confirms second
    $response = $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => $this->appointment->client_confirmation_token,
    ]);

    $response->assertStatus(200);

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe('confirmed')
        ->and($this->appointment->is_fully_confirmed)->toBeTrue();
});

// ===== Activity Logging Tests =====

test('client confirmation is logged in activity', function (): void {
    Mail::fake();

    $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => $this->appointment->client_confirmation_token,
    ]);

    $activity = Activity::where('subject_type', PastoralCare::class)
        ->where('subject_id', $this->appointment->id)
        ->where('description', 'Client a confirmé le rendez-vous')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['confirmed_by'])->toBe('client')
        ->and($activity->properties['client_name'])->toBe('Test Client');
});

test('pastor confirmation is logged in activity', function (): void {
    Mail::fake();

    $this->postJson('/api/pastoral-care/confirm-by-pastor', [
        'token' => $this->appointment->pastor_confirmation_token,
    ]);

    $activity = Activity::where('subject_type', PastoralCare::class)
        ->where('subject_id', $this->appointment->id)
        ->where('description', 'Pasteur a confirmé le rendez-vous')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['confirmed_by'])->toBe('pastor');
});

test('dual confirmation is logged in activity', function (): void {
    Mail::fake();

    // Both parties confirm
    $this->appointment->confirmByClient($this->appointment->client_confirmation_token);
    $this->appointment->confirmByPastor($this->appointment->pastor_confirmation_token);

    $activity = Activity::where('subject_type', PastoralCare::class)
        ->where('subject_id', $this->appointment->id)
        ->where('description', 'Rendez-vous confirmé par les deux parties')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties)->toHaveKey('client_confirmed_at')
        ->and($activity->properties)->toHaveKey('pastor_confirmed_at');
});

// ===== Notification Tests =====

test('partial confirmation sends notification to other party', function (): void {
    Mail::fake();

    // Client confirms - should notify pastor
    $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => $this->appointment->client_confirmation_token,
    ]);

    Mail::assertQueued(PastoralCarePartialConfirmationNotification::class, fn($mail): bool => $mail->hasTo($this->pastor->email) && $mail->recipientType === 'pastor');
});

test('dual confirmation sends notifications to both parties', function (): void {
    Mail::fake();

    // Both parties confirm
    $this->postJson('/api/pastoral-care/confirm-by-client', [
        'token' => $this->appointment->client_confirmation_token,
    ]);

    $this->postJson('/api/pastoral-care/confirm-by-pastor', [
        'token' => $this->appointment->pastor_confirmation_token,
    ]);

    Mail::assertQueued(PastoralCareDualConfirmationNotification::class, fn($mail) => $mail->hasTo($this->appointment->client_email));

    Mail::assertQueued(PastoralCareDualConfirmationNotification::class, fn($mail) => $mail->hasTo($this->pastor->email));
});

// ===== Follow-up Dual Confirmation Tests =====

test('follow-up sends notifications to both client and pastor', function (): void {
    Mail::fake();

    // Complete the parent appointment
    $this->appointment->update(['status' => 'completed']);

    $nextWeek = now()->addDays(14);

    $this->actingAs($this->pastor)
        ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
            'appointment_date' => $nextWeek->format('Y-m-d'),
            'appointment_time' => '10:00',
            'duration_minutes' => 60,
        ]);

    // Verify both mails are sent
    Mail::assertQueued(PastoralCareFollowUpNotification::class, fn($mail) => $mail->hasTo($this->appointment->client_email));

    Mail::assertQueued(PastoralCarePastorFollowUpNotification::class, fn($mail) => $mail->hasTo($this->pastor->email));
});

test('follow-up creates appointment with confirmation tokens', function (): void {
    Mail::fake();

    // Complete the parent appointment
    $this->appointment->update(['status' => 'completed']);

    $nextWeek = now()->addDays(14);

    $response = $this->actingAs($this->pastor)
        ->postJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/follow-up", [
            'appointment_date' => $nextWeek->format('Y-m-d'),
            'appointment_time' => '10:00',
        ]);

    $response->assertStatus(201);

    $followUp = PastoralCare::where('parent_id', $this->appointment->id)->first();

    expect($followUp->client_confirmation_token)->not->toBeNull()
        ->and($followUp->pastor_confirmation_token)->not->toBeNull()
        ->and($followUp->client_confirmed_at)->toBeNull()
        ->and($followUp->pastor_confirmed_at)->toBeNull();
});

// ===== Confirmation Status API Tests =====

test('can get confirmation status via API', function (): void {
    // Partially confirm
    $this->appointment->update(['client_confirmed_at' => now()]);

    $response = $this->getJson("/api/pastoral-care/appointments/{$this->appointment->uuid}/confirmation-status");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'confirmation_status' => [
                    'client_confirmed' => true,
                    'pastor_confirmed' => false,
                    'is_fully_confirmed' => false,
                ],
            ],
        ]);
});

test('confirmation status returns 404 for invalid uuid', function (): void {
    $response = $this->getJson('/api/pastoral-care/appointments/invalid-uuid/confirmation-status');

    $response->assertStatus(404);
});

// ===== Model Accessors Tests =====

test('is_fully_confirmed accessor returns correct value', function (): void {
    expect($this->appointment->is_fully_confirmed)->toBeFalse();

    $this->appointment->update(['client_confirmed_at' => now()]);
    expect($this->appointment->is_fully_confirmed)->toBeFalse();

    $this->appointment->update(['pastor_confirmed_at' => now()]);
    expect($this->appointment->is_fully_confirmed)->toBeTrue();
});

test('confirmation_status accessor returns correct structure', function (): void {
    $status = $this->appointment->confirmation_status;

    expect($status)->toBeArray()
        ->and($status)->toHaveKeys(['client_confirmed', 'pastor_confirmed', 'client_confirmed_at', 'pastor_confirmed_at', 'is_fully_confirmed'])
        ->and($status['client_confirmed'])->toBeFalse()
        ->and($status['pastor_confirmed'])->toBeFalse()
        ->and($status['is_fully_confirmed'])->toBeFalse();
});

// ===== Token Regeneration Tests =====

test('confirmation tokens can be regenerated', function (): void {
    $oldClientToken = $this->appointment->client_confirmation_token;
    $oldPastorToken = $this->appointment->pastor_confirmation_token;

    $this->appointment->regenerateConfirmationTokens();

    expect($this->appointment->client_confirmation_token)->not->toBe($oldClientToken)
        ->and($this->appointment->pastor_confirmation_token)->not->toBe($oldPastorToken);
});

// ===== Find by Token Tests =====

test('can find appointment by client token', function (): void {
    $found = PastoralCare::findByClientToken($this->appointment->client_confirmation_token);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($this->appointment->id);
});

test('can find appointment by pastor token', function (): void {
    $found = PastoralCare::findByPastorToken($this->appointment->pastor_confirmation_token);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($this->appointment->id);
});

test('returns null for non-existent token', function (): void {
    $found = PastoralCare::findByClientToken('non_existent_token_12345678901234567890123456789012345678901234');

    expect($found)->toBeNull();
});
