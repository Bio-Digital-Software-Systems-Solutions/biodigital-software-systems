<?php

namespace Tests\Feature;

use App\Enums\Scheduling\AvailabilityStatus;
use App\Enums\Scheduling\DayOfWeek;
use App\Enums\Scheduling\RecurrenceType;
use App\Models\Department;
use App\Models\Scheduling\EmployeeAvailability;
use App\Models\User;
use App\Services\Scheduling\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SchedulingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view departments']);
        Permission::create(['name' => 'manage departments']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['view departments', 'manage departments']);

        // Create employee user
        $this->employee = User::factory()->create();
        $this->employee->givePermissionTo('view departments');

        // Create department
        $this->department = Department::factory()->create();
        $this->department->users()->attach([$this->admin->id, $this->employee->id]);
    }

    // ============================================
    // Availability Service Tests
    // ============================================

    public function test_can_set_availability_for_date(): void
    {
        $service = app(AvailabilityService::class);
        $date = Carbon::parse('2026-01-19'); // Monday

        $availability = $service->setAvailability(
            $this->employee,
            $this->department->id,
            $date,
            AvailabilityStatus::AVAILABLE,
            '09:00',
            '17:00',
            'Test note'
        );

        $this->assertInstanceOf(EmployeeAvailability::class, $availability);
        $this->assertEquals($this->employee->id, $availability->user_id);
        $this->assertEquals($this->department->id, $availability->department_id);
        $this->assertEquals(DayOfWeek::MONDAY->value, $availability->day_of_week);
        $this->assertEquals(AvailabilityStatus::AVAILABLE, $availability->status);
        $this->assertEquals('09:00', $availability->start_time);
        $this->assertEquals('17:00', $availability->end_time);
        $this->assertEquals('Test note', $availability->notes);
    }

    public function test_set_availability_updates_existing_for_same_day_of_week(): void
    {
        $service = app(AvailabilityService::class);
        $date = Carbon::parse('2026-01-19'); // Monday

        // Create initial availability
        $first = $service->setAvailability(
            $this->employee,
            $this->department->id,
            $date,
            AvailabilityStatus::AVAILABLE,
            '09:00',
            '17:00'
        );

        // Update with same day but different week
        $nextMonday = Carbon::parse('2026-01-26'); // Also a Monday
        $second = $service->setAvailability(
            $this->employee,
            $this->department->id,
            $nextMonday,
            AvailabilityStatus::UNAVAILABLE,
            '10:00',
            '18:00',
            'Updated'
        );

        // Should update the same record
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(AvailabilityStatus::UNAVAILABLE, $second->status);
        $this->assertEquals('Updated', $second->notes);
    }

    public function test_day_of_week_conversion_is_correct(): void
    {
        $service = app(AvailabilityService::class);

        // Test all days of the week
        $testCases = [
            ['2026-01-18', DayOfWeek::SUNDAY],   // Sunday
            ['2026-01-19', DayOfWeek::MONDAY],   // Monday
            ['2026-01-20', DayOfWeek::TUESDAY],  // Tuesday
            ['2026-01-21', DayOfWeek::WEDNESDAY], // Wednesday
            ['2026-01-22', DayOfWeek::THURSDAY], // Thursday
            ['2026-01-23', DayOfWeek::FRIDAY],   // Friday
            ['2026-01-24', DayOfWeek::SATURDAY], // Saturday
        ];

        foreach ($testCases as [$dateString, $expectedDay]) {
            $date = Carbon::parse($dateString);
            $availability = $service->setAvailability(
                $this->employee,
                $this->department->id,
                $date,
                AvailabilityStatus::AVAILABLE
            );

            $this->assertEquals(
                $expectedDay->value,
                $availability->day_of_week,
                "Failed for date {$dateString}: expected {$expectedDay->name} but got day_of_week={$availability->day_of_week}"
            );
        }
    }

    public function test_can_get_weekly_pattern(): void
    {
        $service = app(AvailabilityService::class);

        // Set availability for multiple days
        $service->setAvailability(
            $this->employee,
            $this->department->id,
            Carbon::parse('2026-01-19'), // Monday
            AvailabilityStatus::AVAILABLE,
            '09:00',
            '17:00'
        );

        $service->setAvailability(
            $this->employee,
            $this->department->id,
            Carbon::parse('2026-01-20'), // Tuesday
            AvailabilityStatus::UNAVAILABLE
        );

        $pattern = $service->getWeeklyPattern($this->employee, $this->department->id);

        $this->assertIsArray($pattern);
        $this->assertArrayHasKey(DayOfWeek::MONDAY->value, $pattern);
        $this->assertArrayHasKey(DayOfWeek::TUESDAY->value, $pattern);

        $this->assertEquals(AvailabilityStatus::AVAILABLE, $pattern[DayOfWeek::MONDAY->value]['status']);
        $this->assertEquals(AvailabilityStatus::UNAVAILABLE, $pattern[DayOfWeek::TUESDAY->value]['status']);
    }

    public function test_availability_is_stored_correctly(): void
    {
        $service = app(AvailabilityService::class);

        // Set Monday as available
        $service->setAvailability(
            $this->employee,
            $this->department->id,
            Carbon::parse('2026-01-19'), // Monday
            AvailabilityStatus::AVAILABLE
        );

        // Verify it's stored in the database
        $this->assertDatabaseHas('employee_availabilities', [
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'day_of_week' => DayOfWeek::MONDAY->value,
            'status' => AvailabilityStatus::AVAILABLE->value,
        ]);
    }

    // ============================================
    // Controller Tests - My Availability Page
    // ============================================

    public function test_can_view_my_availability_page(): void
    {
        $this->actingAs($this->employee)
            ->get(route('departments.availability.my', ['department' => $this->department]))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Departments/Schedule/Availability/MyAvailability')
                ->has('department')
                ->has('currentAvailability')
                ->has('weeklyPattern')
                ->has('availabilityStatuses')
            );
    }

    public function test_can_store_my_availability(): void
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $this->actingAs($this->employee)
            ->post(route('departments.availability.my.store', ['department' => $this->department]), [
                'availability' => [
                    'monday' => [
                        'available' => true,
                        'slots' => [['start' => '09:00', 'end' => '17:00']],
                        'notes' => 'Available all day',
                    ],
                    'tuesday' => [
                        'available' => false,
                        'slots' => [],
                        'notes' => 'Not available',
                    ],
                    'wednesday' => [
                        'available' => true,
                        'slots' => [['start' => '10:00', 'end' => '14:00']],
                        'notes' => null,
                    ],
                    'thursday' => [
                        'available' => true,
                        'slots' => [],
                        'notes' => null,
                    ],
                    'friday' => [
                        'available' => true,
                        'slots' => [],
                        'notes' => null,
                    ],
                    'saturday' => [
                        'available' => false,
                        'slots' => [],
                        'notes' => null,
                    ],
                    'sunday' => [
                        'available' => false,
                        'slots' => [],
                        'notes' => null,
                    ],
                ],
                'week_start' => $weekStart,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Verify Monday was saved as available
        $this->assertDatabaseHas('employee_availabilities', [
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'day_of_week' => DayOfWeek::MONDAY->value,
            'status' => AvailabilityStatus::AVAILABLE->value,
        ]);

        // Verify Tuesday was saved as unavailable
        $this->assertDatabaseHas('employee_availabilities', [
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'day_of_week' => DayOfWeek::TUESDAY->value,
            'status' => AvailabilityStatus::UNAVAILABLE->value,
        ]);
    }

    public function test_store_my_availability_validates_required_fields(): void
    {
        $this->actingAs($this->employee)
            ->post(route('departments.availability.my.store', ['department' => $this->department]), [
                // Missing availability and week_start
            ])
            ->assertSessionHasErrors(['availability', 'week_start']);
    }

    public function test_store_my_availability_validates_time_format(): void
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $this->actingAs($this->employee)
            ->post(route('departments.availability.my.store', ['department' => $this->department]), [
                'availability' => [
                    'monday' => [
                        'available' => true,
                        'slots' => [['start' => 'invalid', 'end' => 'invalid']],
                    ],
                ],
                'week_start' => $weekStart,
            ])
            ->assertSessionHasErrors(['availability.monday.slots.0.start', 'availability.monday.slots.0.end']);
    }

    // ============================================
    // Controller Tests - Single Date Availability
    // ============================================

    public function test_can_store_single_date_availability(): void
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        $this->actingAs($this->employee)
            ->post(route('departments.availability.store', ['department' => $this->department]), [
                'date' => $date,
                'status' => AvailabilityStatus::AVAILABLE->value,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'notes' => 'Test availability',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $savedDate = Carbon::parse($date);
        $this->assertDatabaseHas('employee_availabilities', [
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'day_of_week' => $savedDate->dayOfWeek,
            'status' => AvailabilityStatus::AVAILABLE->value,
        ]);
    }

    public function test_single_date_availability_validates_required_fields(): void
    {
        $this->actingAs($this->employee)
            ->post(route('departments.availability.store', ['department' => $this->department]), [
                // Missing date and status
            ])
            ->assertSessionHasErrors(['date', 'status']);
    }

    // ============================================
    // Controller Tests - Weekly Availability
    // ============================================

    public function test_can_store_weekly_recurring_availability(): void
    {
        $this->actingAs($this->employee)
            ->post(route('departments.availability.store-weekly', ['department' => $this->department]), [
                'day_of_week' => DayOfWeek::MONDAY->value,
                'status' => AvailabilityStatus::AVAILABLE->value,
                'start_time' => '08:00',
                'end_time' => '16:00',
                'notes' => 'Weekly Monday availability',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employee_availabilities', [
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'day_of_week' => DayOfWeek::MONDAY->value,
            'status' => AvailabilityStatus::AVAILABLE->value,
            'recurrence_type' => RecurrenceType::WEEKLY->value,
        ]);
    }

    public function test_weekly_availability_validates_day_of_week_range(): void
    {
        $this->actingAs($this->employee)
            ->post(route('departments.availability.store-weekly', ['department' => $this->department]), [
                'day_of_week' => 7, // Invalid - should be 0-6
                'status' => AvailabilityStatus::AVAILABLE->value,
            ])
            ->assertSessionHasErrors(['day_of_week']);
    }

    // ============================================
    // Authorization Tests
    // ============================================

    public function test_guest_cannot_access_availability_page(): void
    {
        $this->get(route('departments.availability.my', ['department' => $this->department]))
            ->assertRedirect(route('login'));
    }

    public function test_department_member_can_access_own_department_availability(): void
    {
        // Test that department member can access their own department
        $this->actingAs($this->employee)
            ->get(route('departments.availability.my', ['department' => $this->department]))
            ->assertStatus(200);
    }

    public function test_availability_is_scoped_to_department(): void
    {
        // Create availability in department 1
        $service = app(AvailabilityService::class);
        $service->setAvailability(
            $this->employee,
            $this->department->id,
            Carbon::parse('2026-01-19'),
            AvailabilityStatus::AVAILABLE
        );

        // Create another department
        $otherDepartment = Department::factory()->create();

        // Verify availability is scoped to department
        $this->assertDatabaseHas('employee_availabilities', [
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        $this->assertDatabaseMissing('employee_availabilities', [
            'user_id' => $this->employee->id,
            'department_id' => $otherDepartment->id,
        ]);
    }

    public function test_user_can_only_set_own_availability(): void
    {
        $otherEmployee = User::factory()->create();
        $otherEmployee->givePermissionTo('view departments');
        $this->department->users()->attach($otherEmployee);

        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        // Employee sets their own availability
        $this->actingAs($this->employee)
            ->post(route('departments.availability.my.store', ['department' => $this->department]), [
                'availability' => [
                    'monday' => ['available' => true, 'slots' => []],
                    'tuesday' => ['available' => true, 'slots' => []],
                    'wednesday' => ['available' => true, 'slots' => []],
                    'thursday' => ['available' => true, 'slots' => []],
                    'friday' => ['available' => true, 'slots' => []],
                    'saturday' => ['available' => false, 'slots' => []],
                    'sunday' => ['available' => false, 'slots' => []],
                ],
                'week_start' => $weekStart,
            ])
            ->assertRedirect();

        // Verify it's saved under the correct user
        $this->assertDatabaseHas('employee_availabilities', [
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
        ]);

        // Verify other employee doesn't have this availability
        $this->assertDatabaseMissing('employee_availabilities', [
            'user_id' => $otherEmployee->id,
            'department_id' => $this->department->id,
        ]);
    }

    // ============================================
    // Edge Cases
    // ============================================

    public function test_can_handle_empty_slots_array(): void
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $this->actingAs($this->employee)
            ->post(route('departments.availability.my.store', ['department' => $this->department]), [
                'availability' => [
                    'monday' => ['available' => true, 'slots' => []],
                    'tuesday' => ['available' => true, 'slots' => []],
                    'wednesday' => ['available' => true, 'slots' => []],
                    'thursday' => ['available' => true, 'slots' => []],
                    'friday' => ['available' => true, 'slots' => []],
                    'saturday' => ['available' => false, 'slots' => []],
                    'sunday' => ['available' => false, 'slots' => []],
                ],
                'week_start' => $weekStart,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_can_handle_null_notes(): void
    {
        $service = app(AvailabilityService::class);
        $date = Carbon::parse('2026-01-19');

        $availability = $service->setAvailability(
            $this->employee,
            $this->department->id,
            $date,
            AvailabilityStatus::AVAILABLE,
            '09:00',
            '17:00'
        );

        $this->assertNull($availability->notes);
    }

    // ============================================
    // Controller Tests - Member Week Availability API
    // ============================================

    public function test_can_get_member_week_availability(): void
    {
        $service = app(AvailabilityService::class);
        $service->setAvailability(
            $this->employee,
            $this->department->id,
            Carbon::parse('2026-01-19'), // Monday
            AvailabilityStatus::AVAILABLE,
            '09:00',
            '17:00'
        );

        $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
                'week' => '2026-01-19',
            ]))
            ->assertSuccessful()
            ->assertJsonStructure([
                'employee' => ['id', 'full_name'],
                'week_start',
                'week_end',
                'prev_week',
                'next_week',
                'dates',
            ]);
    }

    public function test_member_week_availability_returns_7_days(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
                'week' => '2026-01-19',
            ]))
            ->assertSuccessful()
            ->assertJsonCount(7, 'dates');
    }

    public function test_member_week_availability_returns_correct_week_bounds(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
                'week' => '2026-01-21', // Wednesday → should resolve to Mon 19 - Sun 25
            ]))
            ->assertSuccessful()
            ->assertJsonPath('week_start', '2026-01-19')
            ->assertJsonPath('week_end', '2026-01-25');
    }

    public function test_member_week_availability_returns_correct_nav_weeks(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
                'week' => '2026-01-19',
            ]))
            ->assertSuccessful();

        $response->assertJsonPath('prev_week', '2026-01-12');
        $response->assertJsonPath('next_week', '2026-01-26');
    }

    public function test_member_week_availability_shows_available_status(): void
    {
        $service = app(AvailabilityService::class);
        $service->setAvailability(
            $this->employee,
            $this->department->id,
            Carbon::parse('2026-01-19'), // Monday
            AvailabilityStatus::AVAILABLE,
            '09:00',
            '17:00'
        );

        $response = $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
                'week' => '2026-01-19',
            ]))
            ->assertSuccessful();

        $response->assertJsonPath('dates.2026-01-19.status', 'available');
        $response->assertJsonPath('dates.2026-01-19.is_available', true);
        $response->assertJsonPath('dates.2026-01-19.is_absent', false);
    }

    public function test_member_week_availability_shows_unavailable_status(): void
    {
        $service = app(AvailabilityService::class);
        $service->setAvailability(
            $this->employee,
            $this->department->id,
            Carbon::parse('2026-01-20'), // Tuesday
            AvailabilityStatus::UNAVAILABLE
        );

        $response = $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
                'week' => '2026-01-19',
            ]))
            ->assertSuccessful();

        $response->assertJsonPath('dates.2026-01-20.status', 'unavailable');
        $response->assertJsonPath('dates.2026-01-20.is_available', false);
    }

    public function test_member_week_availability_includes_time_slots(): void
    {
        $service = app(AvailabilityService::class);
        $service->setAvailability(
            $this->employee,
            $this->department->id,
            Carbon::parse('2026-01-19'), // Monday
            AvailabilityStatus::AVAILABLE,
            '08:00',
            '16:00'
        );

        $response = $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
                'week' => '2026-01-19',
            ]))
            ->assertSuccessful();

        $response->assertJsonPath('dates.2026-01-19.time_slots.0.start', '08:00');
        $response->assertJsonPath('dates.2026-01-19.time_slots.0.end', '16:00');
    }

    public function test_member_week_availability_uses_current_week_by_default(): void
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
            ]))
            ->assertSuccessful()
            ->assertJsonPath('week_start', $weekStart);
    }

    public function test_member_week_availability_returns_employee_info(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
                'week' => '2026-01-19',
            ]))
            ->assertSuccessful();

        $response->assertJsonPath('employee.id', $this->employee->id);
        $this->assertNotEmpty($response->json('employee.full_name'));
    }

    public function test_member_week_availability_requires_authentication(): void
    {
        $this->getJson(route('departments.availability.member-week', [
            'department' => $this->department,
            'user' => $this->employee,
        ]))
            ->assertUnauthorized();
    }

    public function test_member_week_availability_day_structure_is_correct(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('departments.availability.member-week', [
                'department' => $this->department,
                'user' => $this->employee,
                'week' => '2026-01-19',
            ]))
            ->assertSuccessful();

        // Each day must have the expected fields
        foreach (['2026-01-19', '2026-01-20', '2026-01-21', '2026-01-22', '2026-01-23', '2026-01-24', '2026-01-25'] as $date) {
            $this->assertArrayHasKey($date, $response->json('dates'));
            $dayData = $response->json("dates.{$date}");
            $this->assertArrayHasKey('status', $dayData);
            $this->assertArrayHasKey('is_available', $dayData);
            $this->assertArrayHasKey('is_absent', $dayData);
            $this->assertArrayHasKey('absence_type', $dayData);
            $this->assertArrayHasKey('time_slots', $dayData);
        }
    }

    public function test_availability_status_enum_values(): void
    {
        $this->assertEquals('available', AvailabilityStatus::AVAILABLE->value);
        $this->assertEquals('unavailable', AvailabilityStatus::UNAVAILABLE->value);
        $this->assertEquals('partially_available', AvailabilityStatus::PARTIALLY_AVAILABLE->value);
        $this->assertEquals('preferred', AvailabilityStatus::PREFERRED->value);
    }

    public function test_day_of_week_enum_values(): void
    {
        $this->assertEquals(0, DayOfWeek::SUNDAY->value);
        $this->assertEquals(1, DayOfWeek::MONDAY->value);
        $this->assertEquals(2, DayOfWeek::TUESDAY->value);
        $this->assertEquals(3, DayOfWeek::WEDNESDAY->value);
        $this->assertEquals(4, DayOfWeek::THURSDAY->value);
        $this->assertEquals(5, DayOfWeek::FRIDAY->value);
        $this->assertEquals(6, DayOfWeek::SATURDAY->value);
    }
}
