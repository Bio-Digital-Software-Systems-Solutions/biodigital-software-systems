<?php

namespace Tests\Unit\Models;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Employee\EmploymentType;
use App\Enums\Employee\PaymentMethod;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeModelTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_employee_can_be_created(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_employee_uses_uuid_for_route_key(): void
    {
        $employee = Employee::factory()->create();

        $this->assertEquals('uuid', $employee->getRouteKeyName());
        $this->assertNotNull($employee->uuid);
        $this->assertEquals(36, strlen($employee->uuid));
    }

    public function test_employee_generates_unique_employee_number(): void
    {
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();

        $this->assertNotNull($employee1->employee_number);
        $this->assertNotNull($employee2->employee_number);
        $this->assertNotEquals($employee1->employee_number, $employee2->employee_number);
        $this->assertStringStartsWith('EMP', $employee1->employee_number);
    }

    public function test_employee_number_contains_year(): void
    {
        $employee = Employee::factory()->create();
        $year = date('Y');

        $this->assertStringContainsString($year, $employee->employee_number);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_employee_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $employee->user);
        $this->assertEquals($user->id, $employee->user->id);
    }

    public function test_employee_belongs_to_department(): void
    {
        $department = Department::factory()->create();
        $employee = Employee::factory()->inDepartment($department)->create();

        $this->assertInstanceOf(Department::class, $employee->department);
        $this->assertEquals($department->id, $employee->department->id);
    }

    public function test_employee_can_have_manager(): void
    {
        $manager = Employee::factory()->create();
        $employee = Employee::factory()->withManager($manager)->create();

        $this->assertInstanceOf(Employee::class, $employee->manager);
        $this->assertEquals($manager->id, $employee->manager->id);
    }

    public function test_employee_can_have_subordinates(): void
    {
        $manager = Employee::factory()->create();
        $subordinates = Employee::factory()->count(3)->withManager($manager)->create();

        $this->assertCount(3, $manager->subordinates);
        $this->assertInstanceOf(Employee::class, $manager->subordinates->first());
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    public function test_full_name_accessor(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->full_name, $employee->full_name);
    }

    public function test_is_on_probation_accessor_returns_true_when_in_probation(): void
    {
        $employee = Employee::factory()->onProbation()->create();

        $this->assertTrue($employee->is_on_probation);
    }

    public function test_is_on_probation_accessor_returns_false_when_probation_completed(): void
    {
        $employee = Employee::factory()->probationCompleted()->create();

        $this->assertFalse($employee->is_on_probation);
    }

    public function test_years_of_service_accessor(): void
    {
        $employee = Employee::factory()->create([
            'hire_date' => now()->subYears(3),
        ]);

        $this->assertEquals(3.0, $employee->years_of_service);
    }

    public function test_years_of_service_considers_termination_date(): void
    {
        $employee = Employee::factory()->create([
            'hire_date' => now()->subYears(5),
            'termination_date' => now()->subYears(2),
        ]);

        $this->assertEquals(3.0, $employee->years_of_service);
    }

    public function test_age_accessor(): void
    {
        $employee = Employee::factory()->create([
            'birth_date' => now()->subYears(30),
        ]);

        $this->assertEquals(30, $employee->age);
    }

    public function test_remaining_probation_days_accessor(): void
    {
        $employee = Employee::factory()->create([
            'probation_end_date' => now()->addDays(30),
        ]);

        // Allow for slight timing differences (29-30 days)
        $this->assertGreaterThanOrEqual(29, $employee->remaining_probation_days);
        $this->assertLessThanOrEqual(30, $employee->remaining_probation_days);
    }

    public function test_contract_remaining_days_accessor(): void
    {
        $employee = Employee::factory()->create([
            'contract_end_date' => now()->addDays(60),
        ]);

        // Allow for slight timing differences (59-60 days)
        $this->assertGreaterThanOrEqual(59, $employee->contract_remaining_days);
        $this->assertLessThanOrEqual(60, $employee->contract_remaining_days);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_active(): void
    {
        Employee::factory()->active()->count(3)->create();
        Employee::factory()->inactive()->count(2)->create();

        $activeEmployees = Employee::active()->get();

        $this->assertCount(3, $activeEmployees);
        $activeEmployees->each(fn($e) => $this->assertEquals(EmployeeStatus::ACTIVE, $e->status));
    }

    public function test_scope_inactive(): void
    {
        Employee::factory()->active()->count(2)->create();
        Employee::factory()->inactive()->count(3)->create();

        $inactiveEmployees = Employee::inactive()->get();

        $this->assertCount(3, $inactiveEmployees);
    }

    public function test_scope_on_leave(): void
    {
        Employee::factory()->active()->count(2)->create();
        Employee::factory()->onLeave()->count(2)->create();

        $onLeaveEmployees = Employee::onLeave()->get();

        $this->assertCount(2, $onLeaveEmployees);
    }

    public function test_scope_terminated(): void
    {
        Employee::factory()->active()->count(2)->create();
        Employee::factory()->terminated()->count(1)->create();

        $terminatedEmployees = Employee::terminated()->get();

        $this->assertCount(1, $terminatedEmployees);
    }

    public function test_scope_by_status(): void
    {
        Employee::factory()->active()->count(3)->create();
        Employee::factory()->onLeave()->count(2)->create();

        $activeEmployees = Employee::byStatus(EmployeeStatus::ACTIVE)->get();

        $this->assertCount(3, $activeEmployees);
    }

    public function test_scope_by_employment_type(): void
    {
        Employee::factory()->fullTime()->count(3)->create();
        Employee::factory()->partTime()->count(2)->create();

        $fullTimeEmployees = Employee::byEmploymentType(EmploymentType::FULL_TIME)->get();

        $this->assertCount(3, $fullTimeEmployees);
    }

    public function test_scope_in_department(): void
    {
        $department = Department::factory()->create();
        Employee::factory()->inDepartment($department)->count(3)->create();
        Employee::factory()->count(2)->create();

        $deptEmployees = Employee::inDepartment($department->id)->get();

        $this->assertCount(3, $deptEmployees);
    }

    public function test_scope_on_probation(): void
    {
        Employee::factory()->onProbation()->count(2)->create();
        Employee::factory()->probationCompleted()->count(3)->create();

        $probationEmployees = Employee::onProbation()->get();

        $this->assertCount(2, $probationEmployees);
    }

    public function test_scope_contract_ending_soon(): void
    {
        Employee::factory()->contractEndingSoon(15)->count(2)->create();
        Employee::factory()->contractEndingSoon(60)->count(1)->create();
        Employee::factory()->fullTime()->count(2)->create();

        $endingSoon = Employee::contractEndingSoon(30)->get();

        $this->assertCount(2, $endingSoon);
    }

    public function test_scope_search(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Developer',
        ]);
        Employee::factory()->create([
            'user_id' => $user->id,
            'position' => 'Senior Developer',
        ]);
        Employee::factory()->count(2)->create();

        $results = Employee::search('Developer')->get();

        $this->assertCount(1, $results);
    }

    // ==========================================
    // Method Tests
    // ==========================================

    public function test_can_work_returns_true_for_active_employee(): void
    {
        $employee = Employee::factory()->active()->create();

        $this->assertTrue($employee->canWork());
    }

    public function test_can_work_returns_false_for_inactive_employee(): void
    {
        $employee = Employee::factory()->inactive()->create();

        $this->assertFalse($employee->canWork());
    }

    public function test_is_available_on_checks_working_days(): void
    {
        $employee = Employee::factory()->create([
            'status' => EmployeeStatus::ACTIVE,
            'working_days' => ['monday', 'tuesday', 'wednesday'],
        ]);

        // Find a Monday
        $monday = Carbon::now()->startOfWeek();
        // Find a Saturday
        $saturday = Carbon::now()->endOfWeek()->subDay();

        $this->assertTrue($employee->isAvailableOn($monday));
        $this->assertFalse($employee->isAvailableOn($saturday));
    }

    public function test_terminate_updates_status_and_dates(): void
    {
        $employee = Employee::factory()->active()->create();
        $terminationDate = Carbon::now();

        $employee->terminate('Resignation', $terminationDate);

        $this->assertEquals(EmployeeStatus::TERMINATED, $employee->status);
        $this->assertEquals('Resignation', $employee->termination_reason);
        $this->assertEquals($terminationDate->format('Y-m-d'), $employee->termination_date->format('Y-m-d'));
    }

    public function test_activate_clears_termination_data(): void
    {
        $employee = Employee::factory()->terminated()->create();

        $employee->activate();

        $this->assertEquals(EmployeeStatus::ACTIVE, $employee->status);
        $this->assertNull($employee->termination_date);
        $this->assertNull($employee->termination_reason);
    }

    public function test_set_on_leave_updates_status(): void
    {
        $employee = Employee::factory()->active()->create();

        $employee->setOnLeave();

        $this->assertEquals(EmployeeStatus::ON_LEAVE, $employee->status);
    }

    public function test_deduct_leave_days_succeeds_when_sufficient(): void
    {
        $employee = Employee::factory()->create(['remaining_leave_days' => 10]);

        $result = $employee->deductLeaveDays(5);

        $this->assertTrue($result);
        $this->assertEquals(5, $employee->fresh()->remaining_leave_days);
    }

    public function test_deduct_leave_days_fails_when_insufficient(): void
    {
        $employee = Employee::factory()->create(['remaining_leave_days' => 3]);

        $result = $employee->deductLeaveDays(5);

        $this->assertFalse($result);
        $this->assertEquals(3, $employee->fresh()->remaining_leave_days);
    }

    public function test_add_sick_day_increments_counter(): void
    {
        $employee = Employee::factory()->create(['sick_days_taken' => 2]);

        $employee->addSickDay();

        $this->assertEquals(3, $employee->fresh()->sick_days_taken);
    }

    public function test_reset_annual_leave(): void
    {
        $employee = Employee::factory()->create([
            'annual_leave_days' => 30,
            'remaining_leave_days' => 5,
            'sick_days_taken' => 10,
        ]);

        $employee->resetAnnualLeave();

        $employee->refresh();
        $this->assertEquals(30, $employee->remaining_leave_days);
        $this->assertEquals(0, $employee->sick_days_taken);
    }

    public function test_has_skill(): void
    {
        $employee = Employee::factory()->create([
            'skills' => ['PHP', 'Laravel', 'React'],
        ]);

        $this->assertTrue($employee->hasSkill('PHP'));
        $this->assertTrue($employee->hasSkill('php')); // Case insensitive
        $this->assertFalse($employee->hasSkill('Python'));
    }

    public function test_add_skill(): void
    {
        $employee = Employee::factory()->create([
            'skills' => ['PHP', 'Laravel'],
        ]);

        $employee->addSkill('React');

        $this->assertContains('React', $employee->fresh()->skills);
    }

    public function test_add_skill_does_not_duplicate(): void
    {
        $employee = Employee::factory()->create([
            'skills' => ['PHP', 'Laravel'],
        ]);

        $employee->addSkill('PHP');

        $this->assertCount(2, $employee->fresh()->skills);
    }

    public function test_remove_skill(): void
    {
        $employee = Employee::factory()->create([
            'skills' => ['PHP', 'Laravel', 'React'],
        ]);

        $employee->removeSkill('Laravel');

        $skills = $employee->fresh()->skills;
        $this->assertNotContains('Laravel', $skills);
        $this->assertCount(2, $skills);
    }

    // ==========================================
    // Cast Tests
    // ==========================================

    public function test_status_is_cast_to_enum(): void
    {
        $employee = Employee::factory()->active()->create();

        $this->assertInstanceOf(EmployeeStatus::class, $employee->status);
        $this->assertEquals(EmployeeStatus::ACTIVE, $employee->status);
    }

    public function test_employment_type_is_cast_to_enum(): void
    {
        $employee = Employee::factory()->fullTime()->create();

        $this->assertInstanceOf(EmploymentType::class, $employee->employment_type);
        $this->assertEquals(EmploymentType::FULL_TIME, $employee->employment_type);
    }

    public function test_payment_method_is_cast_to_enum(): void
    {
        $employee = Employee::factory()->create([
            'payment_method' => PaymentMethod::BANK_TRANSFER,
        ]);

        $this->assertInstanceOf(PaymentMethod::class, $employee->payment_method);
        $this->assertEquals(PaymentMethod::BANK_TRANSFER, $employee->payment_method);
    }

    public function test_date_fields_are_cast_to_carbon(): void
    {
        $employee = Employee::factory()->create([
            'birth_date' => '1990-01-15',
            'hire_date' => '2020-03-01',
        ]);

        $this->assertInstanceOf(Carbon::class, $employee->birth_date);
        $this->assertInstanceOf(Carbon::class, $employee->hire_date);
    }

    public function test_array_fields_are_cast_correctly(): void
    {
        $employee = Employee::factory()->create([
            'working_days' => ['monday', 'tuesday'],
            'skills' => ['PHP', 'Laravel'],
            'languages' => ['English', 'French'],
        ]);

        $this->assertIsArray($employee->working_days);
        $this->assertIsArray($employee->skills);
        $this->assertIsArray($employee->languages);
    }

    // ==========================================
    // Soft Delete Tests
    // ==========================================

    public function test_employee_can_be_soft_deleted(): void
    {
        $employee = Employee::factory()->create();

        $employee->delete();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertNull(Employee::find($employee->id));
        $this->assertNotNull(Employee::withTrashed()->find($employee->id));
    }

    public function test_soft_deleted_employee_can_be_restored(): void
    {
        $employee = Employee::factory()->create();
        $employee->delete();

        $employee->restore();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'deleted_at' => null]);
    }
}
