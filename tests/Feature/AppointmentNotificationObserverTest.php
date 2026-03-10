<?php

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentCreated;
use App\Notifications\AppointmentUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);
uses(Tests\CreatesPermissions::class);

beforeEach(function (): void {
    $this->setupPermissions();

    // Create appointment-specific permissions
    Permission::firstOrCreate(['name' => 'view appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'edit appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'delete appointments', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage appointment participants', 'guard_name' => 'web']);
});

describe('Appointment Creation Notifications', function (): void {

    it('sends email notification to organizer when appointment is created', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Test Meeting',
        ]);

        Notification::assertSentTo(
            $organizer,
            AppointmentCreated::class,
            fn($notification): bool => $notification->appointment->id === $appointment->id
        );
    });

    it('includes correct appointment details in the notification', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Team Standup',
            'description' => 'Daily standup meeting',
            'location' => 'Conference Room A',
            'type' => 'meeting',
        ]);

        Notification::assertSentTo(
            $organizer,
            AppointmentCreated::class,
            fn($notification): bool => $notification->appointment->title === 'Team Standup'
                && $notification->appointment->location === 'Conference Room A'
        );
    });

    it('notification uses mail and database channels', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();

        Appointment::factory()->create([
            'user_id' => $organizer->id,
        ]);

        Notification::assertSentTo(
            $organizer,
            AppointmentCreated::class,
            fn($notification, $channels): bool => in_array('mail', $channels) && in_array('database', $channels)
        );
    });

});

describe('Appointment Update Notifications', function (): void {

    it('sends email notification to participants when appointment is updated', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();
        $participant = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Original Title',
        ]);

        $appointment->participants()->attach($participant->id, [
            'status' => 'accepted',
        ]);

        // Clear previous notifications
        Notification::fake();

        // Update the appointment
        $appointment->update([
            'title' => 'Updated Title',
        ]);

        Notification::assertSentTo(
            $participant,
            AppointmentUpdated::class,
            fn($notification): bool => $notification->appointment->id === $appointment->id
                && isset($notification->changes['title'])
        );
    });

    it('sends notification to organizer when another user updates appointment', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();
        $editor = User::factory()->create();
        $editor->givePermissionTo('edit appointments');

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Original Title',
        ]);

        // Simulate another user making the change
        $this->actingAs($editor);

        // Clear previous notifications
        Notification::fake();

        $appointment->update([
            'title' => 'Updated Title',
        ]);

        Notification::assertSentTo(
            $organizer,
            AppointmentUpdated::class
        );
    });

    it('does not send notification to user making the change', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Original Title',
        ]);

        // Simulate organizer making the change
        $this->actingAs($organizer);

        // Clear previous notifications
        Notification::fake();

        $appointment->update([
            'title' => 'Updated Title',
        ]);

        // Organizer should NOT receive notification since they made the change
        Notification::assertNotSentTo($organizer, AppointmentUpdated::class);
    });

    it('includes changed fields in the notification', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();
        $participant = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Original Title',
            'location' => 'Room A',
        ]);

        $appointment->participants()->attach($participant->id, [
            'status' => 'accepted',
        ]);

        // Clear previous notifications
        Notification::fake();

        $appointment->update([
            'title' => 'New Title',
            'location' => 'Room B',
        ]);

        Notification::assertSentTo(
            $participant,
            AppointmentUpdated::class,
            fn($notification): bool => isset($notification->changes['title'])
                && isset($notification->changes['location'])
                && $notification->changes['title']['old'] === 'Original Title'
                && $notification->changes['title']['new'] === 'New Title'
        );
    });

    it('does not send notification when non-notifiable fields change', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();
        $participant = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
        ]);

        $appointment->participants()->attach($participant->id, [
            'status' => 'accepted',
        ]);

        // Clear previous notifications
        Notification::fake();

        // Update a non-notifiable field
        $appointment->update([
            'reminder_sent_at' => now(),
        ]);

        Notification::assertNotSentTo($participant, AppointmentUpdated::class);
    });

    it('notifies all participants when appointment time changes', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();
        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
        ]);

        $appointment->participants()->attach([
            $participant1->id => ['status' => 'accepted'],
            $participant2->id => ['status' => 'accepted'],
        ]);

        // Clear previous notifications
        Notification::fake();

        $appointment->update([
            'start_datetime' => now()->addDays(5),
        ]);

        Notification::assertSentTo($participant1, AppointmentUpdated::class);
        Notification::assertSentTo($participant2, AppointmentUpdated::class);
    });

    it('notifies participants when status changes', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();
        $participant = User::factory()->create();

        $appointment = Appointment::factory()->pending()->create([
            'user_id' => $organizer->id,
        ]);

        $appointment->participants()->attach($participant->id, [
            'status' => 'accepted',
        ]);

        // Clear previous notifications
        Notification::fake();

        $appointment->update([
            'status' => 'confirmed',
        ]);

        Notification::assertSentTo(
            $participant,
            AppointmentUpdated::class,
            fn($notification): bool => isset($notification->changes['status'])
        );
    });

});

describe('Notification Content', function (): void {

    it('AppointmentCreated notification has correct mail subject', function (): void {
        $organizer = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Important Meeting',
        ]);

        $notification = new AppointmentCreated($appointment);
        $mailMessage = $notification->toMail($organizer);

        expect($mailMessage->subject)->toContain('Important Meeting');
    });

    it('AppointmentUpdated notification includes changes in mail', function (): void {
        $organizer = User::factory()->create();
        $participant = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Updated Meeting',
        ]);

        $changes = [
            'title' => ['old' => 'Old Title', 'new' => 'Updated Meeting'],
        ];

        $notification = new AppointmentUpdated($appointment, $changes);
        $mailMessage = $notification->toMail($participant);

        expect($mailMessage->subject)->toContain('Updated Meeting');
    });

    it('AppointmentCreated notification stores correct database data', function (): void {
        $organizer = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Database Test Meeting',
        ]);

        $notification = new AppointmentCreated($appointment);
        $databaseData = $notification->toDatabase($organizer);

        expect($databaseData['type'])->toBe('appointment_created');
        expect($databaseData['appointment_id'])->toBe($appointment->id);
        expect($databaseData['appointment_title'])->toBe('Database Test Meeting');
    });

    it('AppointmentUpdated notification stores changes in database', function (): void {
        $organizer = User::factory()->create();
        $participant = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
        ]);

        $changes = [
            'location' => ['old' => 'Room A', 'new' => 'Room B'],
        ];

        $notification = new AppointmentUpdated($appointment, $changes);
        $databaseData = $notification->toDatabase($participant);

        expect($databaseData['type'])->toBe('appointment_updated');
        expect($databaseData['changes'])->toBe($changes);
    });

});

describe('Edge Cases', function (): void {

    it('handles appointment without participants gracefully', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
        ]);

        // Clear creation notification
        Notification::fake();

        // Should not throw exception
        $appointment->update([
            'title' => 'Updated Title',
        ]);

        // No participants to notify, but should not fail
        expect(true)->toBeTrue();
    });

    it('handles multiple updates in sequence', function (): void {
        Notification::fake();

        $organizer = User::factory()->create();
        $participant = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'title' => 'Original',
        ]);

        $appointment->participants()->attach($participant->id, [
            'status' => 'accepted',
        ]);

        // Clear previous notifications
        Notification::fake();

        // First update
        $appointment->update(['title' => 'First Update']);

        // Second update
        $appointment->update(['location' => 'New Location']);

        // Should receive 2 notifications
        Notification::assertSentToTimes($participant, AppointmentUpdated::class, 2);
    });

});
