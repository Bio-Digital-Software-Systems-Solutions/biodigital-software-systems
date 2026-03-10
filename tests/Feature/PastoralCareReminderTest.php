<?php

use App\Mail\PastoralCareAppointmentReminder;
use App\Mail\PastoralCarePastorReminder;
use App\Models\PastoralCare;
use App\Models\User;
use App\Services\PastoralCareNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create necessary roles
    Role::create(['name' => 'pastor']);
    Role::create(['name' => 'member']);

    // Create a pastor user (users table doesn't have phone column)
    $this->pastor = User::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'email' => 'jean.dupont@icc-munich.de',
    ]);
    $this->pastor->assignRole('pastor');
});

/*
|--------------------------------------------------------------------------
| Command Tests - Finding Appointments
|--------------------------------------------------------------------------
*/

it('finds appointments within the 24-hour reminder window', function (): void {
    Mail::fake();

    // Create an appointment exactly 24 hours from now
    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Marie Martin',
        'client_email' => 'marie.martin@example.com',
        'client_phone' => '+33698765432',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertQueued(PastoralCareAppointmentReminder::class, fn($mail) => $mail->hasTo($appointment->client_email));

    Mail::assertQueued(PastoralCarePastorReminder::class, fn($mail) => $mail->hasTo($this->pastor->email));

    $appointment->refresh();
    expect($appointment->reminder_sent_at)->not->toBeNull();
});

it('does not send reminders for appointments already reminded', function (): void {
    Mail::fake();

    // Create an appointment that already has a reminder sent
    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Pierre Durand',
        'client_email' => 'pierre.durand@example.com',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
        'reminder_sent_at' => Carbon::now()->subHour(), // Already sent
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertNotQueued(PastoralCareAppointmentReminder::class);
    Mail::assertNotQueued(PastoralCarePastorReminder::class);
});

it('does not send reminders for cancelled appointments', function (): void {
    Mail::fake();

    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Sophie Bernard',
        'client_email' => 'sophie.bernard@example.com',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertNotQueued(PastoralCareAppointmentReminder::class);
    Mail::assertNotQueued(PastoralCarePastorReminder::class);
});

it('does not send reminders for completed appointments', function (): void {
    Mail::fake();

    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Lucas Petit',
        'client_email' => 'lucas.petit@example.com',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'completed',
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertNotQueued(PastoralCareAppointmentReminder::class);
    Mail::assertNotQueued(PastoralCarePastorReminder::class);
});

it('sends reminders for pending appointments', function (): void {
    Mail::fake();

    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Emma Roux',
        'client_email' => 'emma.roux@example.com',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'zoom',
        'zoom_link' => 'https://zoom.us/j/123456789',
        'status' => 'pending',
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertQueued(PastoralCareAppointmentReminder::class);
    Mail::assertQueued(PastoralCarePastorReminder::class);

    $appointment->refresh();
    expect($appointment->reminder_sent_at)->not->toBeNull();
});

it('does not send reminders for appointments too far in the future', function (): void {
    Mail::fake();

    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Hugo Moreau',
        'client_email' => 'hugo.moreau@example.com',
        'appointment_date' => Carbon::now()->addDays(3)->toDateString(),
        'appointment_time' => Carbon::now()->addDays(3),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertNotQueued(PastoralCareAppointmentReminder::class);
    Mail::assertNotQueued(PastoralCarePastorReminder::class);
});

it('does not send reminders for appointments too close', function (): void {
    Mail::fake();

    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Lea Simon',
        'client_email' => 'lea.simon@example.com',
        'appointment_date' => Carbon::now()->addHours(2)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(2),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertNotQueued(PastoralCareAppointmentReminder::class);
    Mail::assertNotQueued(PastoralCarePastorReminder::class);
});

/*
|--------------------------------------------------------------------------
| Command Tests - Dry Run Mode
|--------------------------------------------------------------------------
*/

it('does not send reminders in dry run mode', function (): void {
    Mail::fake();

    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Tom Richard',
        'client_email' => 'tom.richard@example.com',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $this->artisan('pastoral-care:send-reminders --dry-run')
        ->assertSuccessful();

    Mail::assertNotQueued(PastoralCareAppointmentReminder::class);
    Mail::assertNotQueued(PastoralCarePastorReminder::class);

    $appointment->refresh();
    expect($appointment->reminder_sent_at)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Command Tests - Custom Hours Option
|--------------------------------------------------------------------------
*/

it('respects custom hours option for reminder window', function (): void {
    Mail::fake();

    // Create appointment 48 hours from now
    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Julie Leblanc',
        'client_email' => 'julie.leblanc@example.com',
        'appointment_date' => Carbon::now()->addHours(48)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(48),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    // Default 24 hours - should not send
    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertNotQueued(PastoralCareAppointmentReminder::class);

    // With 48 hours option - should send
    $this->artisan('pastoral-care:send-reminders --hours=48')
        ->assertSuccessful();

    Mail::assertQueued(PastoralCareAppointmentReminder::class);
});

/*
|--------------------------------------------------------------------------
| Command Tests - Multiple Appointments
|--------------------------------------------------------------------------
*/

it('handles multiple appointments in the reminder window', function (): void {
    Mail::fake();

    // Create 3 appointments within the 2-hour window (23-25 hours from now)
    // The command looks for appointments between now+23h and now+25h
    for ($i = 1; $i <= 3; $i++) {
        PastoralCare::create([
            'pastor_id' => $this->pastor->id,
            'client_name' => "Client {$i}",
            'client_email' => "client{$i}@example.com",
            'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
            'appointment_time' => Carbon::now()->addHours(24)->addMinutes($i * 10), // 24h10m, 24h20m, 24h30m
            'duration_minutes' => 30,
            'location_type' => 'in_person',
            'status' => 'confirmed',
        ]);
    }

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    // Should send to each client
    Mail::assertQueued(PastoralCareAppointmentReminder::class, 3);
    // And to pastor for each appointment
    Mail::assertQueued(PastoralCarePastorReminder::class, 3);
});

/*
|--------------------------------------------------------------------------
| Notification Service Tests - SMS
|--------------------------------------------------------------------------
*/

it('sends SMS reminder when SMS is enabled', function (): void {
    // Fake HTTP for Twilio
    Http::fake([
        'api.twilio.com/*' => Http::response([
            'sid' => 'SM123456',
            'status' => 'queued',
        ], 200),
    ]);

    // Set config to enable SMS
    config([
        'pastoral_care.integrations.sms.enabled' => true,
        'services.twilio.sid' => 'test_sid',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'client_phone' => '+33612345678',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(10),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $service = new PastoralCareNotificationService;
    $result = $service->sendSmsReminder($appointment);

    expect($result)->toBeTrue();

    Http::assertSent(fn($request): bool => str_contains((string) $request->url(), 'api.twilio.com')
        && $request['To'] === '+33612345678');
});

it('does not send SMS when SMS is disabled', function (): void {
    Http::fake();

    config([
        'pastoral_care.integrations.sms.enabled' => false,
    ]);

    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'client_phone' => '+33612345678',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(10),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $service = new PastoralCareNotificationService;
    $result = $service->sendSmsReminder($appointment);

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

it('does not send SMS when client has no phone number', function (): void {
    Http::fake();

    config([
        'pastoral_care.integrations.sms.enabled' => true,
        'services.twilio.sid' => 'test_sid',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'client_phone' => null, // No phone
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(10),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $service = new PastoralCareNotificationService;
    $result = $service->sendSmsReminder($appointment);

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Notification Service Tests - WhatsApp
|--------------------------------------------------------------------------
*/

it('sends WhatsApp reminder when WhatsApp is enabled', function (): void {
    Http::fake([
        'api.twilio.com/*' => Http::response([
            'sid' => 'WA123456',
            'status' => 'queued',
        ], 200),
    ]);

    config([
        'pastoral_care.integrations.whatsapp.enabled' => true,
        'services.twilio.sid' => 'test_sid',
        'services.twilio.token' => 'test_token',
        'services.twilio.whatsapp_from' => '+15551234567',
    ]);

    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'client_phone' => '+33612345678',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(14),
        'duration_minutes' => 45,
        'location_type' => 'zoom',
        'zoom_link' => 'https://zoom.us/j/123456789',
        'status' => 'confirmed',
    ]);

    $service = new PastoralCareNotificationService;
    $result = $service->sendWhatsAppReminder($appointment);

    expect($result)->toBeTrue();

    Http::assertSent(fn($request): bool => str_contains((string) $request->url(), 'api.twilio.com')
        && str_contains((string) $request['To'], 'whatsapp:'));
});

it('does not send WhatsApp when WhatsApp is disabled', function (): void {
    Http::fake();

    config([
        'pastoral_care.integrations.whatsapp.enabled' => false,
    ]);

    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'client_phone' => '+33612345678',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(10),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $service = new PastoralCareNotificationService;
    $result = $service->sendWhatsAppReminder($appointment);

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Notification Service Tests - Phone Number Normalization
|--------------------------------------------------------------------------
*/

it('normalizes phone numbers without country code', function (): void {
    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 200),
    ]);

    config([
        'pastoral_care.integrations.sms.enabled' => true,
        'services.twilio.sid' => 'test_sid',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'client_phone' => '0612345678', // German format without country code
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(10),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $service = new PastoralCareNotificationService;
    $service->sendSmsReminder($appointment);

    Http::assertSent(fn($request): bool =>
        // Should be normalized to +49612345678
        $request['To'] === '+49612345678');
});

/*
|--------------------------------------------------------------------------
| Mail Content Tests
|--------------------------------------------------------------------------
*/

it('client reminder email contains appointment details', function (): void {
    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(10)->setMinute(30),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $mail = new PastoralCareAppointmentReminder($appointment);
    $rendered = $mail->render();

    expect($rendered)->toContain('Test Client');
    expect($rendered)->toContain($this->pastor->first_name);
    expect($rendered)->toContain('60 minutes');
});

it('pastor reminder email contains client information', function (): void {
    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Marie Client',
        'client_email' => 'marie@example.com',
        'client_phone' => '+33612345678',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(14),
        'duration_minutes' => 45,
        'location_type' => 'zoom',
        'zoom_link' => 'https://zoom.us/j/123456789',
        'notes' => 'Discussion about faith journey',
        'status' => 'confirmed',
    ]);

    $mail = new PastoralCarePastorReminder($appointment);
    $rendered = $mail->render();

    expect($rendered)->toContain('Marie Client');
    expect($rendered)->toContain('marie@example.com');
    expect($rendered)->toContain('+33612345678');
    expect($rendered)->toContain('Discussion about faith journey');
});

/*
|--------------------------------------------------------------------------
| Error Handling Tests
|--------------------------------------------------------------------------
*/

it('handles Twilio API errors gracefully', function (): void {
    Http::fake([
        'api.twilio.com/*' => Http::response([
            'code' => 21211,
            'message' => 'Invalid phone number',
        ], 400),
    ]);

    config([
        'pastoral_care.integrations.sms.enabled' => true,
        'services.twilio.sid' => 'test_sid',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    $appointment = PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'client_phone' => 'invalid_phone',
        'appointment_date' => Carbon::tomorrow(),
        'appointment_time' => Carbon::tomorrow()->setHour(10),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $service = new PastoralCareNotificationService;
    $result = $service->sendSmsReminder($appointment);

    expect($result)->toBeFalse();
});

it('command continues after individual email failure', function (): void {
    Mail::fake();

    // Make the first email fail
    Mail::shouldReceive('to')
        ->once()
        ->andThrow(new \Exception('SMTP error'));

    // Create appointment
    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Failing Client',
        'client_email' => 'fail@example.com',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    // Command should still complete (with failure status)
    $this->artisan('pastoral-care:send-reminders')
        ->assertFailed();
});

/*
|--------------------------------------------------------------------------
| Configuration Tests
|--------------------------------------------------------------------------
*/

it('respects disabled reminders configuration', function (): void {
    Mail::fake();

    config(['pastoral_care.notifications.reminders.enabled' => false]);

    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful()
        ->expectsOutput('Pastoral care reminders are disabled in configuration.');

    Mail::assertNothingQueued();
});

it('respects disabled client reminder configuration', function (): void {
    Mail::fake();

    config(['pastoral_care.notifications.reminders.send_client_reminder' => false]);

    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertNotQueued(PastoralCareAppointmentReminder::class);
    Mail::assertQueued(PastoralCarePastorReminder::class);
});

it('respects disabled pastor reminder configuration', function (): void {
    Mail::fake();

    config(['pastoral_care.notifications.reminders.send_pastor_reminder' => false]);

    PastoralCare::create([
        'pastor_id' => $this->pastor->id,
        'client_name' => 'Test Client',
        'client_email' => 'test@example.com',
        'appointment_date' => Carbon::now()->addHours(24)->toDateString(),
        'appointment_time' => Carbon::now()->addHours(24),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
        'status' => 'confirmed',
    ]);

    $this->artisan('pastoral-care:send-reminders')
        ->assertSuccessful();

    Mail::assertQueued(PastoralCareAppointmentReminder::class);
    Mail::assertNotQueued(PastoralCarePastorReminder::class);
});
