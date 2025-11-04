<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\CreatesPermissions;
use Tests\TestCase;
use Carbon\Carbon;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase, CreatesPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();

        // Create appointment-specific permissions
        Permission::firstOrCreate(['name' => 'view appointments']);
        Permission::firstOrCreate(['name' => 'create appointments']);
        Permission::firstOrCreate(['name' => 'edit appointments']);
        Permission::firstOrCreate(['name' => 'delete appointments']);
        Permission::firstOrCreate(['name' => 'manage appointment participants']);
    }

    public function test_authenticated_user_with_permission_can_view_appointments_index()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view appointments');

        $appointments = Appointment::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('appointments.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Index')
                ->has('appointments.data', 3)
                ->has('stats')
                ->has('filters')
        );
    }

    public function test_user_without_view_permission_cannot_access_appointments_index()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('appointments.index'));

        $response->assertStatus(403);
    }

    public function test_authenticated_user_with_permission_can_view_create_form()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $response = $this->actingAs($user)->get(route('appointments.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Create')
                ->has('users')
                ->has('types')
        );
    }

    public function test_user_without_create_permission_cannot_access_create_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('appointments.create'));

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_create_appointment()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $participant = User::factory()->create();

        $startDate = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');
        $endDate = Carbon::now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s');

        $appointmentData = [
            'title' => 'Test Appointment',
            'description' => 'Test Description',
            'start_datetime' => $startDate,
            'end_datetime' => $endDate,
            'location' => 'Test Location',
            'type' => 'individual',
            'participant_ids' => [$participant->id],
        ];

        $response = $this->actingAs($user)->post(route('appointments.store'), $appointmentData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointments', [
            'title' => 'Test Appointment',
            'description' => 'Test Description',
            'location' => 'Test Location',
            'type' => 'individual',
            'user_id' => $user->id,
        ]);

        $appointment = Appointment::where('title', 'Test Appointment')->first();
        $this->assertTrue($appointment->participants->contains($participant));
    }

    public function test_user_cannot_create_appointment_without_permission()
    {
        $user = User::factory()->create();

        $appointmentData = [
            'title' => 'Test Appointment',
            'start_datetime' => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_datetime' => Carbon::now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'type' => 'individual',
        ];

        $response = $this->actingAs($user)->post(route('appointments.store'), $appointmentData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('appointments', ['title' => 'Test Appointment']);
    }

    public function test_appointment_creation_validates_required_fields()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $response = $this->actingAs($user)->post(route('appointments.store'), []);

        $response->assertSessionHasErrors(['title', 'start_datetime', 'end_datetime', 'type']);
    }

    public function test_appointment_creation_validates_end_time_after_start_time()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $startDate = Carbon::now()->addDays(1);
        $endDate = Carbon::now()->addDays(1)->subHours(1); // End before start

        $appointmentData = [
            'title' => 'Test Appointment',
            'start_datetime' => $startDate->format('Y-m-d H:i:s'),
            'end_datetime' => $endDate->format('Y-m-d H:i:s'),
            'type' => 'individual',
        ];

        $response = $this->actingAs($user)->post(route('appointments.store'), $appointmentData);

        $response->assertSessionHasErrors(['end_datetime']);
    }

    public function test_authenticated_user_can_view_appointment()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view appointments');

        $appointment = Appointment::factory()->ownedBy($user)->create();

        $response = $this->actingAs($user)->get(route('appointments.show', $appointment->uuid));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Show')
                ->where('appointment.id', $appointment->id)
                ->has('canModify')
                ->has('canCancel')
        );
    }

    public function test_user_can_view_other_users_appointment_with_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view appointments');

        $otherUser = User::factory()->create();
        $appointment = Appointment::factory()->ownedBy($otherUser)->create();

        $response = $this->actingAs($user)->get(route('appointments.show', $appointment->uuid));

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_view_edit_form_for_own_appointment()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('edit appointments');

        $appointment = Appointment::factory()->ownedBy($user)->create();

        $response = $this->actingAs($user)->get(route('appointments.edit', $appointment->uuid));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Edit')
                ->where('appointment.id', $appointment->id)
                ->has('users')
                ->has('types')
        );
    }

    public function test_user_cannot_edit_other_users_appointment_without_admin_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('edit appointments');

        $otherUser = User::factory()->create();
        $appointment = Appointment::factory()->ownedBy($otherUser)->create();

        $response = $this->actingAs($user)->get(route('appointments.edit', $appointment->uuid));

        $response->assertStatus(403);
    }

    public function test_admin_can_edit_any_appointment()
    {
        $admin = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(['view appointments', 'edit appointments', 'delete appointments']);
        $admin->assignRole('admin');

        $otherUser = User::factory()->create();
        $appointment = Appointment::factory()->ownedBy($otherUser)->create();

        $response = $this->actingAs($admin)->get(route('appointments.edit', $appointment->uuid));

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_update_own_appointment()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('edit appointments');

        $appointment = Appointment::factory()->ownedBy($user)->create();

        $updateData = [
            'title' => 'Updated Appointment',
            'description' => 'Updated Description',
            'start_datetime' => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_datetime' => Carbon::now()->addDays(2)->addHours(2)->format('Y-m-d H:i:s'),
            'location' => 'Updated Location',
            'type' => 'meeting',
            'participant_ids' => [],
        ];

        $response = $this->actingAs($user)->put(route('appointments.update', $appointment->uuid), $updateData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'title' => 'Updated Appointment',
            'description' => 'Updated Description',
            'location' => 'Updated Location',
            'type' => 'meeting',
        ]);
    }

    public function test_authenticated_user_can_delete_own_appointment()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('delete appointments');

        $appointment = Appointment::factory()->ownedBy($user)->create();

        $response = $this->actingAs($user)->delete(route('appointments.destroy', $appointment->uuid));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_user_cannot_delete_other_users_appointment()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('delete appointments');

        $otherUser = User::factory()->create();
        $appointment = Appointment::factory()->ownedBy($otherUser)->create();

        $response = $this->actingAs($user)->delete(route('appointments.destroy', $appointment->uuid));

        $response->assertStatus(403);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_admin_can_delete_any_appointment()
    {
        $admin = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(['view appointments', 'edit appointments', 'delete appointments']);
        $admin->assignRole('admin');

        $otherUser = User::factory()->create();
        $appointment = Appointment::factory()->ownedBy($otherUser)->create();

        $response = $this->actingAs($admin)->delete(route('appointments.destroy', $appointment->uuid));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_user_can_confirm_own_appointment()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('edit appointments');

        $appointment = Appointment::factory()->ownedBy($user)->pending()->create();

        $response = $this->actingAs($user)->patch(route('appointments.confirm', $appointment->uuid));

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_user_can_cancel_own_appointment()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('edit appointments');

        $appointment = Appointment::factory()->ownedBy($user)->confirmed()->create();

        $response = $this->actingAs($user)->patch(route('appointments.cancel', $appointment->uuid));

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_participant_can_accept_invitation()
    {
        $organizer = User::factory()->create();
        $participant = User::factory()->create();

        $appointment = Appointment::factory()->ownedBy($organizer)->create();
        $appointment->participants()->attach($participant->id, ['status' => 'pending']);

        $response = $this->actingAs($participant)->patch(route('appointments.accept-invitation', $appointment->uuid));

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointment_user', [
            'appointment_id' => $appointment->id,
            'user_id' => $participant->id,
            'status' => 'accepted',
        ]);
    }

    public function test_participant_can_decline_invitation()
    {
        $organizer = User::factory()->create();
        $participant = User::factory()->create();

        $appointment = Appointment::factory()->ownedBy($organizer)->create();
        $appointment->participants()->attach($participant->id, ['status' => 'pending']);

        $response = $this->actingAs($participant)->patch(route('appointments.decline-invitation', $appointment->uuid));

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointment_user', [
            'appointment_id' => $appointment->id,
            'user_id' => $participant->id,
            'status' => 'declined',
        ]);
    }

    public function test_appointments_index_can_be_filtered_by_status()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view appointments');

        Appointment::factory()->confirmed()->count(2)->create();
        Appointment::factory()->pending()->count(3)->create();

        $response = $this->actingAs($user)->get(route('appointments.index', ['status' => 'confirmed']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->has('appointments.data', 2)
                ->where('filters.status', 'confirmed')
        );
    }

    public function test_appointments_index_can_be_searched()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view appointments');

        Appointment::factory()->create(['title' => 'Meeting with John']);
        Appointment::factory()->create(['title' => 'Doctor Appointment']);
        Appointment::factory()->create(['title' => 'Team Review']);

        $response = $this->actingAs($user)->get(route('appointments.index', ['search' => 'Meeting']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->has('appointments.data', 1)
                ->where('filters.search', 'Meeting')
        );
    }

    public function test_calendar_view_displays_appointments()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view appointments');

        $appointment = Appointment::factory()->today()->create();

        $response = $this->actingAs($user)->get(route('appointments.calendar'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Calendar')
                ->has('appointments')
        );
    }

    public function test_create_form_passes_prefilled_data_from_query_parameters()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $response = $this->actingAs($user)->get(route('appointments.create', [
            'date' => '2025-11-15',
            'time' => '14:30'
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Create')
                ->where('prefilledData.date', '2025-11-15')
                ->where('prefilledData.time', '14:30')
        );
    }

    public function test_create_form_passes_single_preselected_participant()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $participant = User::factory()->create();

        $response = $this->actingAs($user)->get(route('appointments.create', [
            'participant_ids' => [$participant->id]
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Create')
                ->where('prefilledData.participant_ids', [$participant->id])
                ->has('preselectedParticipants', 1)
                ->where('preselectedParticipants.0.id', $participant->id)
                ->where('preselectedParticipants.0.name', $participant->first_name . ' ' . $participant->last_name)
        );
    }

    public function test_create_form_passes_multiple_preselected_participants()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        $response = $this->actingAs($user)->get(route('appointments.create', [
            'participant_ids' => [$participant1->id, $participant2->id]
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Create')
                ->where('prefilledData.participant_ids', [$participant1->id, $participant2->id])
                ->has('preselectedParticipants', 2)
                ->where('preselectedParticipants.0.id', $participant1->id)
                ->where('preselectedParticipants.1.id', $participant2->id)
        );
    }

    public function test_create_form_handles_single_participant_id_as_string()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $participant = User::factory()->create();

        $response = $this->actingAs($user)->get(route('appointments.create', [
            'participant_ids' => $participant->id // Pass as string instead of array
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Create')
                ->where('prefilledData.participant_ids', [$participant->id])
                ->has('preselectedParticipants', 1)
                ->where('preselectedParticipants.0.id', $participant->id)
        );
    }

    public function test_create_form_with_complete_prefilled_data_from_agenda_click()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $selectedUser = User::factory()->create();

        $response = $this->actingAs($user)->get(route('appointments.create', [
            'date' => '2025-11-15',
            'time' => '14:30',
            'participant_ids' => [$selectedUser->id]
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Create')
                ->where('prefilledData.date', '2025-11-15')
                ->where('prefilledData.time', '14:30')
                ->where('prefilledData.participant_ids', [$selectedUser->id])
                ->has('preselectedParticipants', 1)
                ->where('preselectedParticipants.0.id', $selectedUser->id)
        );
    }

    public function test_users_list_excludes_current_user()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->get(route('appointments.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Create')
                ->has('users')
                ->where('users', function ($users) use ($user, $otherUser) {
                    $userIds = collect($users)->pluck('id')->toArray();
                    return !in_array($user->id, $userIds) && in_array($otherUser->id, $userIds);
                })
        );
    }

    public function test_preselected_participants_excludes_current_user()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create appointments');

        $otherUser = User::factory()->create();

        // Try to preselect the current user (should work but they shouldn't appear in the list)
        $response = $this->actingAs($user)->get(route('appointments.create', [
            'participant_ids' => [$user->id, $otherUser->id]
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Appointments/Create')
                ->where('prefilledData.participant_ids', [$user->id, $otherUser->id])
                ->has('preselectedParticipants', 2) // Both users in preselectedParticipants
                ->has('users') // Current user excluded from users list
                ->where('users', function ($users) use ($user, $otherUser) {
                    $userIds = collect($users)->pluck('id')->toArray();
                    return !in_array($user->id, $userIds) && in_array($otherUser->id, $userIds);
                })
        );
    }
}