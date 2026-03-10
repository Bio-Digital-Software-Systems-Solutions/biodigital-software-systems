<?php

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create necessary permissions
    Permission::firstOrCreate(['name' => 'view appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'edit appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'delete appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage appointment participants', 'guard_name' => 'web']);

    // Create a user with proper permissions
    $this->organizer = User::factory()->create();
    $this->organizer->givePermissionTo([
        'view appointments',
        'create appointments',
        'edit appointments',
    ]);

    $this->actingAs($this->organizer);
});

describe('Scheduler Configuration', function (): void {
    it('has appointment reminders scheduled', function (): void {
        // Use artisan to list scheduled events
        $this->artisan('schedule:list')
            ->expectsOutputToContain('appointments:send-reminders')
            ->assertSuccessful();
    });

    it('command exists and can run with dry-run', function (): void {
        $this->artisan('appointments:send-reminders', ['--dry-run' => true])
            ->assertSuccessful();
    });

    it('routes/console.php contains scheduler configuration', function (): void {
        $consoleRoutesPath = base_path('routes/console.php');
        $content = file_get_contents($consoleRoutesPath);

        // Check that the schedule commands are defined
        expect($content)->toContain("Schedule::command('appointments:send-reminders')");
        expect($content)->toContain("->dailyAt('09:00')");
        expect($content)->toContain('->withoutOverlapping()');
        expect($content)->toContain('->onOneServer()');
    });

    it('has 6:00 PM schedule with 18 hours option', function (): void {
        $consoleRoutesPath = base_path('routes/console.php');
        $content = file_get_contents($consoleRoutesPath);

        expect($content)->toContain('appointments:send-reminders --hours=18');
        expect($content)->toContain("->dailyAt('18:00')");
    });
});

describe('Notification Channels Validation', function (): void {
    it('accepts valid notification channels on create', function (): void {
        $response = $this->post(route('appointments.store'), [
            'title' => 'Test Appointment',
            'start_datetime' => now()->addDays(2)->setHour(10)->setMinute(0)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDays(2)->setHour(11)->setMinute(0)->format('Y-m-d H:i:s'),
            'type' => 'individual',
            'visibility' => 'private',
            'meeting_mode' => 'in_person',
            'notification_channels' => ['email', 'sms', 'whatsapp'],
        ]);

        $response->assertRedirect();

        $appointment = Appointment::latest()->first();
        expect($appointment->notification_channels)->toBe(['email', 'sms', 'whatsapp']);
    });

    it('accepts only email notification channel', function (): void {
        $response = $this->post(route('appointments.store'), [
            'title' => 'Test Appointment',
            'start_datetime' => now()->addDays(2)->setHour(10)->setMinute(0)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDays(2)->setHour(11)->setMinute(0)->format('Y-m-d H:i:s'),
            'type' => 'individual',
            'visibility' => 'private',
            'meeting_mode' => 'in_person',
            'notification_channels' => ['email'],
        ]);

        $response->assertRedirect();

        $appointment = Appointment::latest()->first();
        expect($appointment->notification_channels)->toBe(['email']);
    });

    it('rejects invalid notification channels', function (): void {
        $response = $this->post(route('appointments.store'), [
            'title' => 'Test Appointment',
            'start_datetime' => now()->addDays(2)->setHour(10)->setMinute(0)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDays(2)->setHour(11)->setMinute(0)->format('Y-m-d H:i:s'),
            'type' => 'individual',
            'visibility' => 'private',
            'meeting_mode' => 'in_person',
            'notification_channels' => ['email', 'invalid_channel'],
        ]);

        $response->assertSessionHasErrors('notification_channels.1');
    });

    it('allows null notification channels (defaults to email)', function (): void {
        $response = $this->post(route('appointments.store'), [
            'title' => 'Test Appointment',
            'start_datetime' => now()->addDays(2)->setHour(10)->setMinute(0)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDays(2)->setHour(11)->setMinute(0)->format('Y-m-d H:i:s'),
            'type' => 'individual',
            'visibility' => 'private',
            'meeting_mode' => 'in_person',
        ]);

        $response->assertRedirect();

        $appointment = Appointment::latest()->first();
        expect($appointment->notification_channels)->toBeNull();
    });
});

describe('Notification Channels on Update', function (): void {
    it('can update notification channels', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'notification_channels' => ['email'],
            'start_datetime' => now()->addDays(3)->setHour(10)->setMinute(0),
            'end_datetime' => now()->addDays(3)->setHour(11)->setMinute(0),
        ]);

        $response = $this->patch(route('appointments.update', $appointment->uuid), [
            'title' => $appointment->title,
            'start_datetime' => $appointment->start_datetime->format('Y-m-d H:i:s'),
            'end_datetime' => $appointment->end_datetime->format('Y-m-d H:i:s'),
            'type' => $appointment->type,
            'visibility' => $appointment->visibility,
            'meeting_mode' => $appointment->meeting_mode ?? 'in_person',
            'notification_channels' => ['email', 'sms', 'whatsapp'],
        ]);

        $response->assertRedirect();

        $appointment->refresh();
        expect($appointment->notification_channels)->toBe(['email', 'sms', 'whatsapp']);
    });

    it('can remove sms and whatsapp channels', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'notification_channels' => ['email', 'sms', 'whatsapp'],
            'start_datetime' => now()->addDays(3)->setHour(10)->setMinute(0),
            'end_datetime' => now()->addDays(3)->setHour(11)->setMinute(0),
        ]);

        $response = $this->patch(route('appointments.update', $appointment->uuid), [
            'title' => $appointment->title,
            'start_datetime' => $appointment->start_datetime->format('Y-m-d H:i:s'),
            'end_datetime' => $appointment->end_datetime->format('Y-m-d H:i:s'),
            'type' => $appointment->type,
            'visibility' => $appointment->visibility,
            'meeting_mode' => $appointment->meeting_mode ?? 'in_person',
            'notification_channels' => ['email'],
        ]);

        $response->assertRedirect();

        $appointment->refresh();
        expect($appointment->notification_channels)->toBe(['email']);
    });
});

describe('Model Notification Channel Methods', function (): void {
    it('correctly identifies sms notification enabled', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'notification_channels' => ['email', 'sms'],
        ]);

        expect($appointment->hasSmsNotificationEnabled())->toBeTrue();
    });

    it('correctly identifies sms notification disabled', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'notification_channels' => ['email'],
        ]);

        expect($appointment->hasSmsNotificationEnabled())->toBeFalse();
    });

    it('correctly identifies whatsapp notification enabled', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'notification_channels' => ['email', 'whatsapp'],
        ]);

        expect($appointment->hasWhatsAppNotificationEnabled())->toBeTrue();
    });

    it('correctly identifies whatsapp notification disabled', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'notification_channels' => ['email'],
        ]);

        expect($appointment->hasWhatsAppNotificationEnabled())->toBeFalse();
    });

    it('handles null notification channels gracefully', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'notification_channels' => null,
        ]);

        expect($appointment->hasSmsNotificationEnabled())->toBeFalse();
        expect($appointment->hasWhatsAppNotificationEnabled())->toBeFalse();
    });
});

describe('Appointment Reminders Tracking', function (): void {
    it('can mark sms reminder as sent', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'sms_reminder_sent_at' => null,
        ]);

        $appointment->markSmsReminderSent();

        expect($appointment->sms_reminder_sent_at)->not->toBeNull();
    });

    it('can mark whatsapp reminder as sent', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'whatsapp_reminder_sent_at' => null,
        ]);

        $appointment->markWhatsAppReminderSent();

        expect($appointment->whatsapp_reminder_sent_at)->not->toBeNull();
    });

    it('can mark general reminder as sent', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'reminder_sent_at' => null,
        ]);

        $appointment->markRemindersSent();

        expect($appointment->reminder_sent_at)->not->toBeNull();
    });
});

describe('Inertia Page Props', function (): void {
    it('create page renders successfully', function (): void {
        $response = $this->get(route('appointments.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Appointments/Create')
            ->has('users')
            ->has('types')
        );
    });

    it('edit page renders with notification_channels', function (): void {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->organizer->id,
            'notification_channels' => ['email', 'sms'],
        ]);

        $response = $this->get(route('appointments.edit', $appointment->uuid));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Appointments/Edit')
            ->has('appointment')
            ->where('appointment.notification_channels', ['email', 'sms'])
        );
    });
});
