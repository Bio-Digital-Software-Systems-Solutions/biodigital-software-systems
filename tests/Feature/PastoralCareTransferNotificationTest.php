<?php

use App\Mail\PastoralCareTransferNotification;
use App\Models\Message;
use App\Models\PastoralCare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create necessary roles and permissions
    $pastorRole = Role::firstOrCreate(['name' => 'pastor']);
    $adminRole = Role::firstOrCreate(['name' => 'admin']);

    $permissions = [
        'view pastoral care',
        'create pastoral care',
        'edit pastoral care',
        'delete pastoral care',
        'transfer pastoral care',
        'manage pastoral care',
    ];

    foreach ($permissions as $permissionName) {
        $permission = Permission::firstOrCreate(['name' => $permissionName]);
        $pastorRole->givePermissionTo($permission);
        $adminRole->givePermissionTo($permission);
    }

    // Create pastors
    $this->oldPastor = User::factory()->create([
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'email' => 'jean.dupont@example.com',
    ]);
    $this->oldPastor->assignRole('pastor');

    $this->newPastor = User::factory()->create([
        'first_name' => 'Marie',
        'last_name' => 'Martin',
        'email' => 'marie.martin@example.com',
    ]);
    $this->newPastor->assignRole('pastor');

    // Create admin user who will perform the transfer
    $this->admin = User::factory()->create([
        'first_name' => 'Admin',
        'last_name' => 'User',
        'email' => 'admin@example.com',
    ]);
    $this->admin->assignRole('admin');

    // Create a client user
    $this->client = User::factory()->create([
        'first_name' => 'Client',
        'last_name' => 'Test',
        'email' => 'client@example.com',
    ]);

    // Create a test appointment
    $this->appointment = PastoralCare::factory()->create([
        'pastor_id' => $this->oldPastor->id,
        'user_id' => $this->client->id,
        'client_name' => 'Client Test',
        'client_email' => 'client@example.com',
        'client_phone' => '+49 123 456789',
        'status' => 'pending',
        'appointment_date' => now()->addDays(3),
        'appointment_time' => now()->setHour(14)->setMinute(0),
        'duration_minutes' => 60,
        'location_type' => 'in_person',
    ]);
});

it('sends email to new pastor when appointment is transferred', function () {
    Mail::fake();

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $this->appointment), [
            'transferred_to_id' => $this->newPastor->id,
            'transfer_reason' => 'Changement de planning',
        ])
        ->assertRedirect();

    Mail::assertQueued(PastoralCareTransferNotification::class, function ($mail) {
        return $mail->hasTo($this->newPastor->email)
            && $mail->recipientType === 'new_pastor';
    });
});

it('sends email to old pastor when appointment is transferred', function () {
    Mail::fake();

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $this->appointment), [
            'transferred_to_id' => $this->newPastor->id,
            'transfer_reason' => 'Changement de planning',
        ])
        ->assertRedirect();

    Mail::assertQueued(PastoralCareTransferNotification::class, function ($mail) {
        return $mail->hasTo($this->oldPastor->email)
            && $mail->recipientType === 'old_pastor';
    });
});

it('sends email to client when appointment is transferred', function () {
    Mail::fake();

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $this->appointment), [
            'transferred_to_id' => $this->newPastor->id,
            'transfer_reason' => 'Changement de planning',
        ])
        ->assertRedirect();

    Mail::assertQueued(PastoralCareTransferNotification::class, function ($mail) {
        return $mail->hasTo($this->appointment->client_email)
            && $mail->recipientType === 'client';
    });
});

it('sends all three emails when appointment is transferred', function () {
    Mail::fake();

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $this->appointment), [
            'transferred_to_id' => $this->newPastor->id,
            'transfer_reason' => 'Changement de planning',
        ])
        ->assertRedirect();

    // Should queue exactly 3 emails
    Mail::assertQueued(PastoralCareTransferNotification::class, 3);
});

it('creates platform message for new pastor when appointment is transferred', function () {
    Mail::fake();

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $this->appointment), [
            'transferred_to_id' => $this->newPastor->id,
            'transfer_reason' => 'Changement de planning',
        ]);

    $this->assertDatabaseHas('messages', [
        'receiver_id' => $this->newPastor->id,
        'subject' => 'Nouveau rendez-vous de soin pastoral transféré',
        'type' => 'system',
    ]);
});

it('creates platform message for old pastor when appointment is transferred', function () {
    Mail::fake();

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $this->appointment), [
            'transferred_to_id' => $this->newPastor->id,
            'transfer_reason' => 'Changement de planning',
        ]);

    $this->assertDatabaseHas('messages', [
        'receiver_id' => $this->oldPastor->id,
        'subject' => 'Rendez-vous de soin pastoral transféré',
        'type' => 'system',
    ]);
});

it('creates platform message for client when appointment is transferred and client has account', function () {
    Mail::fake();

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $this->appointment), [
            'transferred_to_id' => $this->newPastor->id,
            'transfer_reason' => 'Changement de planning',
        ]);

    $this->assertDatabaseHas('messages', [
        'receiver_id' => $this->client->id,
        'subject' => 'Changement de responsable pour votre rendez-vous',
        'type' => 'system',
    ]);
});

it('sends email to client even when they do not have an account', function () {
    Mail::fake();

    // Create appointment without user_id (external client)
    $externalAppointment = PastoralCare::factory()->create([
        'pastor_id' => $this->oldPastor->id,
        'user_id' => null,
        'client_name' => 'External Client',
        'client_email' => 'external@example.com',
        'status' => 'pending',
        'appointment_date' => now()->addDays(3),
        'appointment_time' => now()->setHour(14)->setMinute(0),
    ]);

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $externalAppointment), [
            'transferred_to_id' => $this->newPastor->id,
        ])
        ->assertRedirect();

    // Email should still be queued for client
    Mail::assertQueued(PastoralCareTransferNotification::class, function ($mail) {
        return $mail->hasTo('external@example.com')
            && $mail->recipientType === 'client';
    });
});

it('does not create platform message for client without account', function () {
    Mail::fake();

    // Create appointment without user_id (external client)
    $externalAppointment = PastoralCare::factory()->create([
        'pastor_id' => $this->oldPastor->id,
        'user_id' => null,
        'client_name' => 'External Client',
        'client_email' => 'external@example.com',
        'status' => 'pending',
    ]);

    $initialMessageCount = Message::count();

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $externalAppointment), [
            'transferred_to_id' => $this->newPastor->id,
        ]);

    // Only 2 platform messages (for pastors), none for external client
    expect(Message::count())->toBe($initialMessageCount + 2);
});

it('includes transfer reason in notifications when provided', function () {
    Mail::fake();

    $transferReason = 'Vacances du pasteur initial';

    $this->actingAs($this->admin)
        ->post(route('pastoral-care.transfer', $this->appointment), [
            'transferred_to_id' => $this->newPastor->id,
            'transfer_reason' => $transferReason,
        ]);

    // Check that the mailable contains the transfer reason
    Mail::assertQueued(PastoralCareTransferNotification::class, function ($mail) use ($transferReason) {
        return $mail->appointment->transfer_reason === $transferReason;
    });

    // Check platform message content
    $message = Message::where('receiver_id', $this->newPastor->id)->first();
    expect($message->content)->toContain($transferReason);
});

it('mailable has correct subject for new pastor', function () {
    $mailable = new PastoralCareTransferNotification(
        $this->appointment,
        $this->oldPastor,
        $this->newPastor,
        'new_pastor'
    );

    expect($mailable->envelope()->subject)->toContain('Nouveau rendez-vous pastoral transféré');
    expect($mailable->envelope()->subject)->toContain($this->appointment->client_name);
});

it('mailable has correct subject for old pastor', function () {
    $mailable = new PastoralCareTransferNotification(
        $this->appointment,
        $this->oldPastor,
        $this->newPastor,
        'old_pastor'
    );

    expect($mailable->envelope()->subject)->toContain('Rendez-vous pastoral transféré');
    expect($mailable->envelope()->subject)->toContain($this->newPastor->first_name);
});

it('mailable has correct subject for client', function () {
    $mailable = new PastoralCareTransferNotification(
        $this->appointment,
        $this->oldPastor,
        $this->newPastor,
        'client'
    );

    expect($mailable->envelope()->subject)->toContain('Changement de responsable');
});

it('mailable is queued', function () {
    $mailable = new PastoralCareTransferNotification(
        $this->appointment,
        $this->oldPastor,
        $this->newPastor,
        'client'
    );

    expect($mailable)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('requires transfer pastoral care permission to transfer', function () {
    Mail::fake();

    // Create a user without transfer permission
    $userWithoutPermission = User::factory()->create();

    // User without permission should not be able to transfer
    // The app returns a redirect with session error (not 403) because of Inertia
    $response = $this->actingAs($userWithoutPermission)
        ->post(route('pastoral-care.transfer', $this->appointment), [
            'transferred_to_id' => $this->newPastor->id,
        ]);

    // With Inertia, unauthorized requests often redirect with session error
    // The important thing is that NO emails were sent
    Mail::assertNothingQueued();

    // The appointment should not have been transferred
    $this->appointment->refresh();
    expect($this->appointment->pastor_id)->toBe($this->oldPastor->id);
    expect($this->appointment->transferred_at)->toBeNull();
});
