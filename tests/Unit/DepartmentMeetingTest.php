<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\DepartmentMeeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentMeetingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_a_uuid(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
        ]);

        $this->assertNotNull($meeting->uuid);
        $this->assertIsString((string) $meeting->uuid);
    }

    /** @test */
    public function it_belongs_to_a_department(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(Department::class, $meeting->department);
        $this->assertEquals($department->id, $meeting->department->id);
    }

    /** @test */
    public function it_belongs_to_an_appointment(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(Appointment::class, $meeting->appointment);
        $this->assertEquals($appointment->id, $meeting->appointment->id);
    }

    /** @test */
    public function it_belongs_to_a_creator(): void
    {
        $department = Department::factory()->create();
        $creator = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $creator->id]);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $creator->id,
        ]);

        $this->assertInstanceOf(User::class, $meeting->creator);
        $this->assertEquals($creator->id, $meeting->creator->id);
    }

    /** @test */
    public function it_can_check_if_has_been_notified(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $notNotified = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
            'notified_at' => null,
        ]);

        $appointment2 = Appointment::factory()->create(['user_id' => $user->id]);
        $notified = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment2->id,
            'created_by' => $user->id,
            'notified_at' => now(),
        ]);

        $this->assertFalse($notNotified->hasBeenNotified());
        $this->assertTrue($notified->hasBeenNotified());
    }

    /** @test */
    public function it_can_mark_as_notified(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
            'notified_at' => null,
        ]);

        $this->assertFalse($meeting->hasBeenNotified());

        $meeting->markAsNotified();
        $meeting->refresh();

        $this->assertTrue($meeting->hasBeenNotified());
        $this->assertNotNull($meeting->notified_at);
    }

    /** @test */
    public function it_casts_notify_all_members_to_boolean(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
            'notify_all_members' => 1,
        ]);

        $this->assertTrue($meeting->notify_all_members);
        $this->assertIsBool($meeting->notify_all_members);
    }

    /** @test */
    public function it_casts_is_mandatory_to_boolean(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
            'is_mandatory' => 1,
        ]);

        $this->assertTrue($meeting->is_mandatory);
        $this->assertIsBool($meeting->is_mandatory);
    }

    /** @test */
    public function it_uses_uuid_for_route_key(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
        ]);

        $this->assertEquals('uuid', $meeting->getRouteKeyName());
    }

    /** @test */
    public function it_gets_members_to_notify_returns_all_department_users_when_notify_all_and_no_participants(): void
    {
        $department = Department::factory()->create();
        $creator = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        // Add members to department
        $department->users()->attach([$member1->id, $member2->id]);

        $appointment = Appointment::factory()->create([
            'user_id' => $creator->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
        ]);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $creator->id,
            'notify_all_members' => true,
        ]);

        $membersToNotify = $meeting->getMembersToNotify();

        $this->assertCount(2, $membersToNotify);
        $this->assertTrue($membersToNotify->contains('id', $member1->id));
        $this->assertTrue($membersToNotify->contains('id', $member2->id));
    }

    /** @test */
    public function it_gets_members_to_notify_returns_participants_when_specified(): void
    {
        $department = Department::factory()->create();
        $creator = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $participant = User::factory()->create();

        // Add members to department
        $department->users()->attach([$member1->id, $member2->id]);

        $appointment = Appointment::factory()->create([
            'user_id' => $creator->id,
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
        ]);

        // Add participant to appointment
        $appointment->participants()->attach($participant->id);

        $meeting = DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment->id,
            'created_by' => $creator->id,
            'notify_all_members' => true,
        ]);

        $membersToNotify = $meeting->getMembersToNotify();

        // Should return participants, not all department members
        $this->assertCount(1, $membersToNotify);
        $this->assertTrue($membersToNotify->contains('id', $participant->id));
    }

    /** @test */
    public function department_can_have_multiple_meetings(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $appointment1 = Appointment::factory()->create(['user_id' => $user->id]);
        $appointment2 = Appointment::factory()->create(['user_id' => $user->id]);
        $appointment3 = Appointment::factory()->create(['user_id' => $user->id]);

        DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment1->id,
            'created_by' => $user->id,
        ]);
        DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment2->id,
            'created_by' => $user->id,
        ]);
        DepartmentMeeting::factory()->create([
            'department_id' => $department->id,
            'appointment_id' => $appointment3->id,
            'created_by' => $user->id,
        ]);

        $this->assertCount(3, $department->meetings);
    }

    /** @test */
    public function appointment_can_have_multiple_department_meetings(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        DepartmentMeeting::factory()->create([
            'department_id' => $department1->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
        ]);
        DepartmentMeeting::factory()->create([
            'department_id' => $department2->id,
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
        ]);

        $this->assertCount(2, $appointment->departmentMeetings);
    }
}
