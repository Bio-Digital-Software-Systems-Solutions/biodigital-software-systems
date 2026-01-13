<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\DepartmentMeeting;
use App\Models\User;
use App\Notifications\DepartmentMeetingCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DepartmentMeetingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();

        // Add the user to the department
        $this->department->users()->attach($this->user->id);
    }

    /** @test */
    public function it_can_list_department_meetings(): void
    {
        $organizer = User::factory()->create();

        // Create appointments and meetings
        $appointment1 = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $this->department->id,
        ]);
        $appointment2 = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $this->department->id,
        ]);

        DepartmentMeeting::factory()->create([
            'department_id' => $this->department->id,
            'appointment_id' => $appointment1->id,
            'created_by' => $organizer->id,
        ]);
        DepartmentMeeting::factory()->create([
            'department_id' => $this->department->id,
            'appointment_id' => $appointment2->id,
            'created_by' => $organizer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.meetings.index', $this->department));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'uuid',
                        'department_id',
                        'appointment_id',
                        'notify_all_members',
                        'is_mandatory',
                        'appointment' => [
                            'uuid',
                            'title',
                            'start_datetime',
                            'end_datetime',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_create_a_department_meeting(): void
    {
        Notification::fake();

        $startDatetime = now()->addDays(7)->setHour(10)->setMinute(0)->setSecond(0);
        $endDatetime = now()->addDays(7)->setHour(11)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.meetings.store', $this->department), [
                'title' => 'Weekly Department Meeting',
                'description' => 'Discussion of weekly progress',
                'start_datetime' => $startDatetime->toDateTimeString(),
                'end_datetime' => $endDatetime->toDateTimeString(),
                'location' => 'Conference Room A',
                'type' => 'meeting',
                'notify_all_members' => true,
                'is_mandatory' => false,
                'notes' => 'Bring your reports',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'appointment' => [
                        'uuid',
                        'title',
                        'start_datetime',
                        'end_datetime',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('department_meetings', [
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'notify_all_members' => true,
            'is_mandatory' => false,
            'notes' => 'Bring your reports',
        ]);

        $this->assertDatabaseHas('appointments', [
            'title' => 'Weekly Department Meeting',
            'location' => 'Conference Room A',
            'type' => 'meeting',
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $this->department->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_meeting(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.meetings.store', $this->department), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'start_datetime', 'end_datetime', 'type']);
    }

    /** @test */
    public function it_validates_end_datetime_is_after_start_datetime(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.meetings.store', $this->department), [
                'title' => 'Test Meeting',
                'start_datetime' => now()->addDays(7)->toDateTimeString(),
                'end_datetime' => now()->addDays(6)->toDateTimeString(),
                'type' => 'meeting',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_datetime']);
    }

    /** @test */
    public function it_sends_notification_to_all_department_members_when_no_participants_specified(): void
    {
        Notification::fake();

        // Add more members to department
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $this->department->users()->attach([$member1->id, $member2->id]);

        $startDatetime = now()->addDays(7)->setHour(10)->setMinute(0)->setSecond(0);
        $endDatetime = now()->addDays(7)->setHour(11)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.meetings.store', $this->department), [
                'title' => 'Team Meeting',
                'start_datetime' => $startDatetime->toDateTimeString(),
                'end_datetime' => $endDatetime->toDateTimeString(),
                'type' => 'meeting',
                'notify_all_members' => true,
            ]);

        $response->assertCreated();

        // Should notify all department members except the creator
        Notification::assertSentTo([$member1, $member2], DepartmentMeetingCreated::class);
        Notification::assertNotSentTo($this->user, DepartmentMeetingCreated::class);
    }

    /** @test */
    public function it_does_not_send_notification_when_notify_all_members_is_false_and_no_participants(): void
    {
        Notification::fake();

        // Add more members to department
        $member1 = User::factory()->create();
        $this->department->users()->attach($member1->id);

        $startDatetime = now()->addDays(7)->setHour(10)->setMinute(0)->setSecond(0);
        $endDatetime = now()->addDays(7)->setHour(11)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.meetings.store', $this->department), [
                'title' => 'Private Meeting',
                'start_datetime' => $startDatetime->toDateTimeString(),
                'end_datetime' => $endDatetime->toDateTimeString(),
                'type' => 'meeting',
                'notify_all_members' => false,
            ]);

        $response->assertCreated();

        // Should not notify anyone with DepartmentMeetingCreated
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_can_show_a_specific_meeting(): void
    {
        $organizer = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $this->department->id,
        ]);
        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $this->department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $organizer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.meetings.show', [
                'department' => $this->department,
                'meeting' => $meeting,
            ]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'uuid',
                    'department_id',
                    'appointment_id',
                    'notify_all_members',
                    'is_mandatory',
                    'appointment',
                    'creator',
                ],
            ])
            ->assertJsonPath('data.uuid', (string) $meeting->uuid);
    }

    /** @test */
    public function it_returns_404_for_meeting_from_another_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $organizer = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $otherDepartment->id,
        ]);
        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $otherDepartment->id,
            'appointment_id' => $appointment->id,
            'created_by' => $organizer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.meetings.show', [
                'department' => $this->department,
                'meeting' => $meeting,
            ]));

        $response->assertNotFound();
    }

    /** @test */
    public function it_can_update_a_meeting(): void
    {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $this->department->id,
        ]);
        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $this->department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $this->user->id,
            'is_mandatory' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson(route('api.departments.meetings.update', [
                'department' => $this->department,
                'meeting' => $meeting,
            ]), [
                'title' => 'Updated Title',
                'is_mandatory' => true,
                'notes' => 'Updated notes',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.appointment.title', 'Updated Title')
            ->assertJsonPath('data.is_mandatory', true)
            ->assertJsonPath('data.notes', 'Updated notes');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'title' => 'Updated Title',
        ]);

        $this->assertDatabaseHas('department_meetings', [
            'id' => $meeting->id,
            'is_mandatory' => true,
            'notes' => 'Updated notes',
        ]);
    }

    /** @test */
    public function it_can_delete_a_meeting(): void
    {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->user->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $this->department->id,
        ]);
        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $this->department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson(route('api.departments.meetings.destroy', [
                'department' => $this->department,
                'meeting' => $meeting,
            ]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Meeting should be deleted (cascade from appointment)
        $this->assertDatabaseMissing('department_meetings', ['id' => $meeting->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    /** @test */
    public function it_cannot_delete_meeting_from_another_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $organizer = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $otherDepartment->id,
        ]);
        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $otherDepartment->id,
            'appointment_id' => $appointment->id,
            'created_by' => $organizer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson(route('api.departments.meetings.destroy', [
                'department' => $this->department,
                'meeting' => $meeting,
            ]));

        $response->assertNotFound();

        // Meeting should still exist
        $this->assertDatabaseHas('department_meetings', ['id' => $meeting->id]);
    }

    /** @test */
    public function it_can_get_meetings_for_a_specific_month(): void
    {
        $organizer = User::factory()->create();

        // Create appointment in January 2026
        $januaryAppointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $this->department->id,
            'start_datetime' => '2026-01-15 10:00:00',
            'end_datetime' => '2026-01-15 11:00:00',
        ]);
        DepartmentMeeting::factory()->create([
            'department_id' => $this->department->id,
            'appointment_id' => $januaryAppointment->id,
            'created_by' => $organizer->id,
        ]);

        // Create appointment in February 2026
        $februaryAppointment = Appointment::factory()->create([
            'user_id' => $organizer->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $this->department->id,
            'start_datetime' => '2026-02-15 10:00:00',
            'end_datetime' => '2026-02-15 11:00:00',
        ]);
        DepartmentMeeting::factory()->create([
            'department_id' => $this->department->id,
            'appointment_id' => $februaryAppointment->id,
            'created_by' => $organizer->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(route('api.departments.meetings.month', $this->department) . '?year=2026&month=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->getJson(route('api.departments.meetings.index', $this->department));

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_adds_creator_as_confirmed_participant(): void
    {
        $startDatetime = now()->addDays(7)->setHour(10)->setMinute(0)->setSecond(0);
        $endDatetime = now()->addDays(7)->setHour(11)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.meetings.store', $this->department), [
                'title' => 'New Meeting',
                'start_datetime' => $startDatetime->toDateTimeString(),
                'end_datetime' => $endDatetime->toDateTimeString(),
                'type' => 'meeting',
            ]);

        $response->assertCreated();

        $appointmentUuid = $response->json('data.appointment.uuid');
        $appointment = Appointment::where('uuid', $appointmentUuid)->first();

        $this->assertDatabaseHas('appointment_user', [
            'appointment_id' => $appointment->id,
            'user_id' => $this->user->id,
            'status' => 'accepted',
        ]);
    }

    /** @test */
    public function it_marks_meeting_as_mandatory(): void
    {
        $startDatetime = now()->addDays(7)->setHour(10)->setMinute(0)->setSecond(0);
        $endDatetime = now()->addDays(7)->setHour(11)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.meetings.store', $this->department), [
                'title' => 'Mandatory Meeting',
                'start_datetime' => $startDatetime->toDateTimeString(),
                'end_datetime' => $endDatetime->toDateTimeString(),
                'type' => 'meeting',
                'is_mandatory' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_mandatory', true);

        $this->assertDatabaseHas('department_meetings', [
            'department_id' => $this->department->id,
            'is_mandatory' => true,
        ]);
    }

    /** @test */
    public function it_validates_appointment_type(): void
    {
        $startDatetime = now()->addDays(7)->setHour(10)->setMinute(0)->setSecond(0);
        $endDatetime = now()->addDays(7)->setHour(11)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.meetings.store', $this->department), [
                'title' => 'Test Meeting',
                'start_datetime' => $startDatetime->toDateTimeString(),
                'end_datetime' => $endDatetime->toDateTimeString(),
                'type' => 'invalid_type',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_can_create_meeting_with_specific_participants(): void
    {
        Notification::fake();

        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        // Add participants to department
        $this->department->users()->attach([$participant1->id, $participant2->id]);

        $startDatetime = now()->addDays(7)->setHour(10)->setMinute(0)->setSecond(0);
        $endDatetime = now()->addDays(7)->setHour(11)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(route('api.departments.meetings.store', $this->department), [
                'title' => 'Meeting with Participants',
                'start_datetime' => $startDatetime->toDateTimeString(),
                'end_datetime' => $endDatetime->toDateTimeString(),
                'type' => 'meeting',
                'participant_ids' => [$participant1->id, $participant2->id],
                'notify_all_members' => false,
            ]);

        $response->assertCreated();

        $appointmentUuid = $response->json('data.appointment.uuid');
        $appointment = Appointment::where('uuid', $appointmentUuid)->first();

        // Check participants were added
        $this->assertDatabaseHas('appointment_user', [
            'appointment_id' => $appointment->id,
            'user_id' => $participant1->id,
        ]);
        $this->assertDatabaseHas('appointment_user', [
            'appointment_id' => $appointment->id,
            'user_id' => $participant2->id,
        ]);
    }
}
