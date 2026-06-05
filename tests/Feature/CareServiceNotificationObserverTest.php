<?php

use App\Mail\CareServiceAppointmentUpdated;
use App\Models\CareService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);
uses(Tests\CreatesPermissions::class);

beforeEach(function (): void {
    $this->setupPermissions();

    // Create care service permissions
    Permission::firstOrCreate(['name' => 'view care service', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create care service', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage care service', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'view care service dashboard', 'guard_name' => 'web']);

    // Create pastor role
    $pastorRole = Role::firstOrCreate(['name' => 'pastor', 'guard_name' => 'web']);
    $pastorRole->givePermissionTo(['view care service', 'create care service', 'manage care service', 'view care service dashboard']);
});

describe('CareService Update Notifications', function (): void {

    it('sends email to client when appointment date changes', function (): void {
        Mail::fake();

        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $client = User::factory()->create();

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => $client->id,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'appointment_date' => now()->addDays(5),
        ]);

        // Update the appointment date
        $careService->update([
            'appointment_date' => now()->addDays(10),
        ]);

        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail): bool => $mail->hasTo('client@example.com')
            && $mail->recipientType === 'client');
    });

    it('sends email to pastor when appointment time changes', function (): void {
        Mail::fake();

        $pastor = User::factory()->create(['email' => 'pastor@example.com']);
        $pastor->assignRole('pastor');

        $client = User::factory()->create();

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => $client->id,
            'client_email' => 'client@example.com',
            'appointment_time' => now()->setTime(10, 0),
        ]);

        // Simulate a different user making the change (not the pastor)
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        // Update the appointment time
        $careService->update([
            'appointment_time' => now()->setTime(14, 0),
        ]);

        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail): bool => $mail->hasTo('pastor@example.com')
            && $mail->recipientType === 'pastor');
    });

    it('sends email to both client and pastor when pastor changes', function (): void {
        Mail::fake();

        $oldPastor = User::factory()->create(['email' => 'old.pastor@example.com']);
        $oldPastor->assignRole('pastor');

        $newPastor = User::factory()->create(['email' => 'new.pastor@example.com']);
        $newPastor->assignRole('pastor');

        $client = User::factory()->create();

        $careService = CareService::factory()->create([
            'pastor_id' => $oldPastor->id,
            'user_id' => $client->id,
            'client_email' => 'client@example.com',
        ]);

        // Update to new pastor
        $careService->update([
            'pastor_id' => $newPastor->id,
        ]);

        // Client should receive notification
        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail) => $mail->hasTo('client@example.com'));

        // New pastor should receive notification
        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail) => $mail->hasTo('new.pastor@example.com'));
    });

    it('sends email when location type changes', function (): void {
        Mail::fake();

        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $client = User::factory()->create();

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => $client->id,
            'client_email' => 'client@example.com',
            'location_type' => 'in_person',
        ]);

        // Update to zoom
        $careService->update([
            'location_type' => 'zoom',
            'zoom_link' => 'https://zoom.us/j/123456789',
        ]);

        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail): bool => isset($mail->changes['location_type']));
    });

    it('does not send notification for status-only changes', function (): void {
        Mail::fake();

        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $client = User::factory()->create();

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => $client->id,
            'client_email' => 'client@example.com',
            'status' => 'pending',
        ]);

        // Update only the status (handled by other notification flows)
        $careService->update([
            'status' => 'confirmed',
        ]);

        Mail::assertNotQueued(CareServiceAppointmentUpdated::class);
    });

    it('does not notify pastor making the change', function (): void {
        Mail::fake();

        $pastor = User::factory()->create(['email' => 'pastor@example.com']);
        $pastor->assignRole('pastor');

        $client = User::factory()->create();

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => $client->id,
            'client_email' => 'client@example.com',
        ]);

        // Simulate the pastor making the change
        $this->actingAs($pastor);

        $careService->update([
            'appointment_date' => now()->addDays(10),
        ]);

        // Pastor should NOT receive notification since they made the change
        Mail::assertNotQueued(CareServiceAppointmentUpdated::class, fn ($mail) => $mail->hasTo('pastor@example.com'));

        // But client should still receive notification
        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail) => $mail->hasTo('client@example.com'));
    });

    it('includes all changed fields in the notification', function (): void {
        Mail::fake();

        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $client = User::factory()->create();

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => $client->id,
            'client_email' => 'client@example.com',
            'appointment_date' => now()->addDays(5),
            'duration_minutes' => 60,
        ]);

        // Update multiple fields
        $careService->update([
            'appointment_date' => now()->addDays(10),
            'duration_minutes' => 90,
        ]);

        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail): bool => isset($mail->changes['appointment_date'])
            && isset($mail->changes['duration_minutes']));
    });

    it('does not send notification when non-notifiable fields change', function (): void {
        Mail::fake();

        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $client = User::factory()->create();

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => $client->id,
            'client_email' => 'client@example.com',
        ]);

        // Update non-notifiable fields
        $careService->update([
            'notes' => 'Updated notes',
            'reminder_sent_at' => now(),
        ]);

        Mail::assertNotQueued(CareServiceAppointmentUpdated::class);
    });

});

describe('CareService Mail Content', function (): void {

    it('mail has correct subject', function (): void {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
        ]);

        $changes = [
            'appointment_date' => ['old' => '01/01/2025', 'new' => '15/01/2025'],
        ];

        $mail = new CareServiceAppointmentUpdated($careService, $changes, 'client');

        expect($mail->envelope()->subject)->toContain('modifie');
    });

    it('mail includes appointment details', function (): void {
        $pastor = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $pastor->assignRole('pastor');

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'client_email' => 'client@example.com',
            'client_name' => 'Jane Smith',
        ]);

        $changes = [
            'appointment_date' => ['old' => '01/01/2025', 'new' => '15/01/2025'],
        ];

        $mail = new CareServiceAppointmentUpdated($careService, $changes, 'client');
        $content = $mail->content();

        expect($content->with['appointment']->client_name)->toBe('Jane Smith');
        expect($content->with['changes'])->toBe($changes);
    });

    it('mail differentiates between client and pastor recipients', function (): void {
        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'client_email' => 'client@example.com',
        ]);

        $changes = ['appointment_date' => ['old' => '01/01/2025', 'new' => '15/01/2025']];

        $clientMail = new CareServiceAppointmentUpdated($careService, $changes, 'client');
        $pastorMail = new CareServiceAppointmentUpdated($careService, $changes, 'pastor');

        expect($clientMail->recipientType)->toBe('client');
        expect($pastorMail->recipientType)->toBe('pastor');
    });

});

describe('Edge Cases', function (): void {

    it('handles appointment without client email gracefully', function (): void {
        Mail::fake();

        $pastor = User::factory()->create(['email' => 'pastor@example.com']);
        $pastor->assignRole('pastor');

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => null,
            'client_email' => null, // No client email
            'client_name' => 'Walk-in Client',
        ]);

        // Should not throw exception
        $careService->update([
            'appointment_date' => now()->addDays(10),
        ]);

        // Only pastor should be notified
        Mail::assertQueued(CareServiceAppointmentUpdated::class, 1);
    });

    it('handles multiple simultaneous changes', function (): void {
        Mail::fake();

        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $newPastor = User::factory()->create();
        $newPastor->assignRole('pastor');

        $client = User::factory()->create();

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => $client->id,
            'client_email' => 'client@example.com',
            'appointment_date' => now()->addDays(5),
            'location_type' => 'in_person',
        ]);

        // Multiple changes at once
        $careService->update([
            'pastor_id' => $newPastor->id,
            'appointment_date' => now()->addDays(10),
            'location_type' => 'zoom',
            'zoom_link' => 'https://zoom.us/j/123',
        ]);

        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail): bool => isset($mail->changes['pastor_id'])
            && isset($mail->changes['appointment_date'])
            && isset($mail->changes['location_type'])
            && isset($mail->changes['zoom_link']));
    });

    it('sends notification to user account if different from client email', function (): void {
        Mail::fake();

        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $registeredUser = User::factory()->create(['email' => 'user.account@example.com']);

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'user_id' => $registeredUser->id,
            'client_email' => 'different.email@example.com',
        ]);

        $careService->update([
            'appointment_date' => now()->addDays(10),
        ]);

        // Both emails should receive notifications
        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail) => $mail->hasTo('different.email@example.com'));

        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail) => $mail->hasTo('user.account@example.com'));
    });

    it('handles sequential updates correctly', function (): void {
        Mail::fake();

        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'client_email' => 'client@example.com',
        ]);

        // First update
        $careService->update(['appointment_date' => now()->addDays(5)]);

        // Second update
        $careService->update(['duration_minutes' => 90]);

        // Should queue 4 notifications total (2 updates × 2 recipients: client + pastor)
        Mail::assertQueued(CareServiceAppointmentUpdated::class, 4);
    });

});

describe('Field Label Formatting', function (): void {

    it('formats location type labels correctly', function (): void {
        Mail::fake();

        $pastor = User::factory()->create();
        $pastor->assignRole('pastor');

        $careService = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'client_email' => 'client@example.com',
            'location_type' => 'in_person',
        ]);

        $careService->update([
            'location_type' => 'zoom',
        ]);

        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail): bool => $mail->changes['location_type']['old'] === 'En personne'
            && $mail->changes['location_type']['new'] === 'Zoom');
    });

    it('formats pastor name when pastor changes', function (): void {
        Mail::fake();

        $oldPastor = User::factory()->create(['first_name' => 'John', 'last_name' => 'Smith']);
        $oldPastor->assignRole('pastor');

        $newPastor = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
        $newPastor->assignRole('pastor');

        $careService = CareService::factory()->create([
            'pastor_id' => $oldPastor->id,
            'client_email' => 'client@example.com',
        ]);

        $careService->update([
            'pastor_id' => $newPastor->id,
        ]);

        Mail::assertQueued(CareServiceAppointmentUpdated::class, fn ($mail): bool => $mail->changes['pastor_id']['old'] === 'John Smith'
            && $mail->changes['pastor_id']['new'] === 'Jane Doe');
    });

});
