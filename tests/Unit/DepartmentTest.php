<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_has_uuid()
    {
        $department = Department::factory()->create();

        $this->assertNotNull($department->uuid);
        $this->assertIsString($department->uuid);
    }

    public function test_department_can_have_appointments()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($department->appointments->contains($appointment));
        $this->assertEquals(1, $department->appointments()->count());
    }

    public function test_department_appointments_relation_is_morph_many()
    {
        $department = Department::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\MorphMany::class,
            $department->appointments()
        );
    }

    public function test_department_can_have_multiple_appointments()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        Appointment::factory()->count(5)->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(5, $department->appointments()->count());
    }

    public function test_department_upcoming_appointments_filters_past_appointments()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        // Create past appointments
        Appointment::factory()->past()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $user->id,
        ]);

        // Create future appointments
        Appointment::factory()->future()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(2, $department->appointments()->count());
        $this->assertEquals(1, $department->upcomingAppointments()->count());
    }

    public function test_department_upcoming_appointments_are_ordered_by_start_datetime()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $laterAppointment = Appointment::factory()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $user->id,
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(10)->addHour(),
            'title' => 'Later',
        ]);

        $earlierAppointment = Appointment::factory()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $user->id,
            'start_datetime' => now()->addDays(1),
            'end_datetime' => now()->addDays(1)->addHour(),
            'title' => 'Earlier',
        ]);

        $upcoming = $department->upcomingAppointments()->get();

        $this->assertEquals('Earlier', $upcoming->first()->title);
        $this->assertEquals('Later', $upcoming->last()->title);
    }

    public function test_department_can_have_head_of_department()
    {
        $headUser = User::factory()->create();
        $department = Department::factory()->create([
            'head_of_department' => $headUser->id,
        ]);

        $this->assertNotNull($department->headOfDepartment);
        $this->assertEquals($headUser->id, $department->headOfDepartment->id);
    }

    public function test_department_can_have_users()
    {
        $department = Department::factory()->create();
        $users = User::factory()->count(3)->create();

        $department->users()->attach($users->pluck('id'));

        $this->assertEquals(3, $department->users()->count());
    }

    public function test_is_head_of_department_returns_true_for_head()
    {
        $headUser = User::factory()->create();
        $department = Department::factory()->create([
            'head_of_department' => $headUser->id,
        ]);

        $this->assertTrue($department->isHeadOfDepartment($headUser));
    }

    public function test_is_head_of_department_returns_false_for_regular_user()
    {
        $headUser = User::factory()->create();
        $regularUser = User::factory()->create();
        $department = Department::factory()->create([
            'head_of_department' => $headUser->id,
        ]);

        $this->assertFalse($department->isHeadOfDepartment($regularUser));
    }

    public function test_department_scope_active_filters_inactive()
    {
        Department::factory()->count(2)->create(['is_active' => true]);
        Department::factory()->create(['is_active' => false]);

        $activeDepartments = Department::active()->get();

        $this->assertEquals(2, $activeDepartments->count());
    }

    public function test_department_uses_uuid_for_route_key()
    {
        $department = Department::factory()->create();

        $this->assertEquals('uuid', $department->getRouteKeyName());
    }

    public function test_department_budget_is_cast_to_decimal()
    {
        $department = Department::factory()->create([
            'budget' => 50000.50,
        ]);

        $this->assertEquals('50000.50', $department->budget);
    }

    public function test_department_is_active_is_cast_to_boolean()
    {
        $department = Department::factory()->create([
            'is_active' => 1,
        ]);

        $this->assertTrue($department->is_active);
        $this->assertIsBool($department->is_active);
    }
}
