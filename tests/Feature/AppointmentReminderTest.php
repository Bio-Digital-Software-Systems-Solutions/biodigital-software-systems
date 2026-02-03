<?php

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Services\AppointmentSmsNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create an organizer user
    $this->organizer = User::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'email' => 'jean.dupont@icc-munich.de',
        'phone_number' => '+491701234567',
    ]);

    // Create a participant user
    $this->participant = User::factory()->create([
        'first_name' => 'Marie',
        'last_name' => 'Martin',
        'email' => 'marie.martin@example.com',
        'phone_number' => '+491709876543',
    ]);
});

/*
|--------------------------------------------------------------------------
| Command Tests - Finding Appointments
|--------------------------------------------------------------------------
*/

it('finds appointments within the 24-hour reminder window', function () {
    Notification::fake();

    // Create an appointment exactly 24 hours from now
    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    // Add participant
    $appointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $this->artisan('appointments:send-reminders')
        ->assertSuccessful();

    // Check email notifications were sent
    Notification::assertSentTo(
        $this->participant,
        AppointmentReminder::class
    );

    Notification::assertSentTo(
        $this->organizer,
        AppointmentReminder::class,
        function ($notification) {
            return $notification->isOrganizer === true;
        }
    );

    $appointment->refresh();
    expect($appointment->reminder_sent_at)->not->toBeNull();
});

it('does not send reminders for appointments already reminded', function () {
    Notification::fake();

    // Create an appointment that already has a reminder sent
    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
            'reminder_sent_at' => Carbon::now()->subHour(),
        ]);

    $appointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $this->artisan('appointments:send-reminders')
        ->assertSuccessful();

    Notification::assertNotSentTo($this->participant, AppointmentReminder::class);
    Notification::assertNotSentTo($this->organizer, AppointmentReminder::class);
});

it('does not send reminders for cancelled appointments', function () {
    Notification::fake();

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->cancelled()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $appointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $this->artisan('appointments:send-reminders')
        ->assertSuccessful();

    Notification::assertNotSentTo($this->participant, AppointmentReminder::class);
    Notification::assertNotSentTo($this->organizer, AppointmentReminder::class);
});

it('does not send reminders for completed appointments', function () {
    Notification::fake();

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->completed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $appointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $this->artisan('appointments:send-reminders')
        ->assertSuccessful();

    Notification::assertNotSentTo($this->participant, AppointmentReminder::class);
    Notification::assertNotSentTo($this->organizer, AppointmentReminder::class);
});

it('sends reminders for pending appointments', function () {
    Notification::fake();

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->pending()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $appointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $this->artisan('appointments:send-reminders')
        ->assertSuccessful();

    Notification::assertSentTo($this->participant, AppointmentReminder::class);
});

it('only sends reminders to accepted participants', function () {
    Notification::fake();

    $declinedParticipant = User::factory()->create();
    $pendingParticipant = User::factory()->create();

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $appointment->participants()->attach([
        $this->participant->id => ['status' => 'accepted', 'invited_at' => now()],
        $declinedParticipant->id => ['status' => 'declined', 'invited_at' => now()],
        $pendingParticipant->id => ['status' => 'pending', 'invited_at' => now()],
    ]);

    $this->artisan('appointments:send-reminders')
        ->assertSuccessful();

    Notification::assertSentTo($this->participant, AppointmentReminder::class);
    Notification::assertNotSentTo($declinedParticipant, AppointmentReminder::class);
    Notification::assertNotSentTo($pendingParticipant, AppointmentReminder::class);
});

it('does not send reminders for appointments outside the window', function () {
    Notification::fake();

    // Appointment too far in the future
    $farAppointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(48),
            'end_datetime' => Carbon::now()->addHours(49),
        ]);

    // Appointment too soon
    $soonAppointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(2),
            'end_datetime' => Carbon::now()->addHours(3),
        ]);

    $farAppointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $soonAppointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $this->artisan('appointments:send-reminders')
        ->assertSuccessful();

    Notification::assertNotSentTo($this->participant, AppointmentReminder::class);
});

it('runs in dry-run mode without sending notifications', function () {
    Notification::fake();

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $appointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $this->artisan('appointments:send-reminders --dry-run')
        ->assertSuccessful();

    Notification::assertNotSentTo($this->participant, AppointmentReminder::class);

    $appointment->refresh();
    expect($appointment->reminder_sent_at)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| SMS Notification Service Tests
|--------------------------------------------------------------------------
*/

it('returns false when SMS is disabled', function () {
    config(['services.sms.enabled' => false]);

    $service = app(AppointmentSmsNotificationService::class);

    expect($service->isSmsEnabled())->toBeFalse();
});

it('returns true when SMS is properly configured', function () {
    config([
        'services.sms.enabled' => true,
        'services.twilio.sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    $service = app(AppointmentSmsNotificationService::class);

    expect($service->isSmsEnabled())->toBeTrue();
});

it('returns false when WhatsApp is disabled', function () {
    config(['services.whatsapp.enabled' => false]);

    $service = app(AppointmentSmsNotificationService::class);

    expect($service->isWhatsAppEnabled())->toBeFalse();
});

it('returns true when WhatsApp is properly configured', function () {
    config([
        'services.whatsapp.enabled' => true,
        'services.twilio.sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'services.twilio.token' => 'test_token',
        'services.twilio.whatsapp_from' => '+15551234567',
    ]);

    $service = app(AppointmentSmsNotificationService::class);

    expect($service->isWhatsAppEnabled())->toBeTrue();
});

it('sends SMS reminder when enabled', function () {
    config([
        'services.sms.enabled' => true,
        'services.twilio.sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    Http::fake([
        'api.twilio.com/*' => Http::response([
            'sid' => 'SM12345678901234567890123456789012',
            'status' => 'queued',
        ], 201),
    ]);

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $service = app(AppointmentSmsNotificationService::class);
    $result = $service->sendSmsReminder($appointment, $this->participant);

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.twilio.com')
            && $request['To'] === '+491709876543'
            && str_contains($request['Body'], 'Rappel');
    });
});

it('does not send SMS when participant has no phone number', function () {
    config([
        'services.sms.enabled' => true,
        'services.twilio.sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    $participantWithoutPhone = User::factory()->create([
        'phone_number' => null,
    ]);

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create();

    $service = app(AppointmentSmsNotificationService::class);
    $result = $service->sendSmsReminder($appointment, $participantWithoutPhone);

    expect($result)->toBeFalse();
});

it('sends WhatsApp reminder when enabled', function () {
    config([
        'services.whatsapp.enabled' => true,
        'services.twilio.sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'services.twilio.token' => 'test_token',
        'services.twilio.whatsapp_from' => '+15551234567',
    ]);

    Http::fake([
        'api.twilio.com/*' => Http::response([
            'sid' => 'SM12345678901234567890123456789012',
            'status' => 'queued',
        ], 201),
    ]);

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $service = app(AppointmentSmsNotificationService::class);
    $result = $service->sendWhatsAppReminder($appointment, $this->participant);

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.twilio.com')
            && $request['To'] === 'whatsapp:+491709876543'
            && str_contains($request['Body'], 'Rappel de rendez-vous');
    });
});

it('normalizes German phone numbers correctly', function () {
    config([
        'services.sms.enabled' => true,
        'services.twilio.sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
    ]);

    $participantWithLocalNumber = User::factory()->create([
        'phone_number' => '0170 123 4567', // German local format
    ]);

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create();

    $service = app(AppointmentSmsNotificationService::class);
    $result = $service->sendSmsReminder($appointment, $participantWithLocalNumber);

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['To'] === '+491701234567';
    });
});

it('handles Twilio API errors gracefully', function () {
    config([
        'services.sms.enabled' => true,
        'services.twilio.sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    Http::fake([
        'api.twilio.com/*' => Http::response([
            'code' => 21211,
            'message' => 'Invalid phone number',
        ], 400),
    ]);

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create();

    $service = app(AppointmentSmsNotificationService::class);
    $result = $service->sendSmsReminder($appointment, $this->participant);

    expect($result)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Appointment Model Tests
|--------------------------------------------------------------------------
*/

it('can mark SMS reminder as sent', function () {
    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create();

    expect($appointment->sms_reminder_sent_at)->toBeNull();
    expect($appointment->reminder_sent_at)->toBeNull();

    $appointment->markSmsReminderSent();

    $appointment->refresh();
    expect($appointment->sms_reminder_sent_at)->not->toBeNull();
    expect($appointment->reminder_sent_at)->not->toBeNull();
});

it('can mark WhatsApp reminder as sent', function () {
    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create();

    expect($appointment->whatsapp_reminder_sent_at)->toBeNull();

    $appointment->markWhatsAppReminderSent();

    $appointment->refresh();
    expect($appointment->whatsapp_reminder_sent_at)->not->toBeNull();
});

it('can check if SMS notification is enabled for appointment', function () {
    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->create([
            'notification_channels' => ['email', 'sms'],
        ]);

    expect($appointment->hasSmsNotificationEnabled())->toBeTrue();
    expect($appointment->hasWhatsAppNotificationEnabled())->toBeFalse();
    expect($appointment->hasEmailNotificationEnabled())->toBeTrue();
});

it('defaults to email when notification_channels is null', function () {
    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->create([
            'notification_channels' => null,
        ]);

    expect($appointment->hasEmailNotificationEnabled())->toBeTrue();
    expect($appointment->hasSmsNotificationEnabled())->toBeFalse();
    expect($appointment->hasWhatsAppNotificationEnabled())->toBeFalse();
});

it('can get participants with phone numbers', function () {
    $participantWithPhone = User::factory()->create([
        'phone_number' => '+491701234567',
    ]);

    $participantWithoutPhone = User::factory()->create([
        'phone_number' => null,
    ]);

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create();

    $appointment->participants()->attach([
        $participantWithPhone->id => ['status' => 'accepted', 'invited_at' => now()],
        $participantWithoutPhone->id => ['status' => 'accepted', 'invited_at' => now()],
    ]);

    $participantsWithPhones = $appointment->getParticipantsWithPhones();

    expect($participantsWithPhones)->toHaveCount(1);
    expect($participantsWithPhones->first()->id)->toBe($participantWithPhone->id);
});

it('scopes appointments needing reminders correctly', function () {
    // Appointment within window
    $appointmentInWindow = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    // Appointment already reminded
    $appointmentReminded = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
            'reminder_sent_at' => now(),
        ]);

    // Appointment cancelled
    $appointmentCancelled = Appointment::factory()
        ->ownedBy($this->organizer)
        ->cancelled()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    // Appointment outside window
    $appointmentFar = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(48),
            'end_datetime' => Carbon::now()->addHours(49),
        ]);

    $needingReminders = Appointment::needingReminders(24)->get();

    expect($needingReminders)->toHaveCount(1);
    expect($needingReminders->first()->id)->toBe($appointmentInWindow->id);
});

/*
|--------------------------------------------------------------------------
| Notification Content Tests
|--------------------------------------------------------------------------
*/

it('creates correct database notification for participant', function () {
    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'title' => 'Team Meeting',
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $notification = new AppointmentReminder($appointment, isOrganizer: false);
    $data = $notification->toDatabase($this->participant);

    expect($data['type'])->toBe('appointment_reminder');
    expect($data['title'])->toBe('Rappel : Team Meeting');
    expect($data['is_organizer'])->toBeFalse();
    expect($data['appointment_uuid'])->toBe($appointment->uuid);
});

it('creates correct database notification for organizer', function () {
    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'title' => 'Team Meeting',
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $notification = new AppointmentReminder($appointment, isOrganizer: true);
    $data = $notification->toDatabase($this->organizer);

    expect($data['type'])->toBe('appointment_reminder');
    expect($data['is_organizer'])->toBeTrue();
    expect($data['message'])->toContain('Votre rendez-vous');
});

/*
|--------------------------------------------------------------------------
| Integration Tests - Command with SMS
|--------------------------------------------------------------------------
*/

it('sends SMS reminders when enabled in command', function () {
    Notification::fake();

    config([
        'services.sms.enabled' => true,
        'services.twilio.sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
    ]);

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $appointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $this->artisan('appointments:send-reminders')
        ->assertSuccessful();

    // Verify email notification was sent
    Notification::assertSentTo($this->participant, AppointmentReminder::class);

    // Verify SMS API was called
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.twilio.com');
    });
});

it('updates sms_reminder_sent_at when SMS is sent', function () {
    Notification::fake();

    config([
        'services.sms.enabled' => true,
        'services.twilio.sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'services.twilio.token' => 'test_token',
        'services.twilio.from' => '+15551234567',
    ]);

    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
    ]);

    $appointment = Appointment::factory()
        ->ownedBy($this->organizer)
        ->confirmed()
        ->create([
            'start_datetime' => Carbon::now()->addHours(24),
            'end_datetime' => Carbon::now()->addHours(25),
        ]);

    $appointment->participants()->attach($this->participant->id, [
        'status' => 'accepted',
        'invited_at' => now(),
    ]);

    $this->artisan('appointments:send-reminders')
        ->assertSuccessful();

    $appointment->refresh();
    expect($appointment->sms_reminder_sent_at)->not->toBeNull();
});
