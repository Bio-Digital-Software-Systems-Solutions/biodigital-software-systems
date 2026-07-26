<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Enable query logging
    DB::enableQueryLog();
});

afterEach(function (): void {
    DB::disableQueryLog();
});

it('eager loads user relation when fetching volunteers', function (): void {
    // Create 5 volunteers with users
    $users = User::factory()->count(5)->create();
    foreach ($users as $user) {
        Volunteer::factory()->create(['user_id' => $user->id]);
    }

    DB::flushQueryLog();

    // Fetch all volunteers and access full_name (which triggers user relation)
    $volunteers = Volunteer::all();
    foreach ($volunteers as $volunteer) {
        $volunteer->full_name; // Access the appended attribute
    }

    $queries = DB::getQueryLog();

    // Should be 2 queries max: one for volunteers, one for users (eager loaded)
    // Without eager loading, it would be 1 + 5 = 6 queries
    expect(count($queries))->toBeLessThanOrEqual(2);
});

it('eager loads user relation when fetching employees', function (): void {
    // Create 5 employees with users
    $users = User::factory()->count(5)->create();
    foreach ($users as $user) {
        Employee::factory()->create(['user_id' => $user->id]);
    }

    DB::flushQueryLog();

    // Fetch all employees and access full_name (which triggers user relation)
    $employees = Employee::all();
    foreach ($employees as $employee) {
        $employee->full_name; // Access the appended attribute
    }

    $queries = DB::getQueryLog();

    // Should be 2 queries max: one for employees, one for users (eager loaded)
    // Without eager loading, it would be 1 + 5 = 6 queries
    expect(count($queries))->toBeLessThanOrEqual(2);
});

it('includes user data when serializing volunteer to array', function (): void {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    $volunteer = Volunteer::factory()->create(['user_id' => $user->id]);

    // Refresh to load from database
    $volunteer = Volunteer::find($volunteer->id);

    expect($volunteer->full_name)->toBe('John Doe');
    expect($volunteer->relationLoaded('user'))->toBeTrue();
});

it('includes user data when serializing employee to array', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // Refresh to load from database
    $employee = Employee::find($employee->id);

    expect($employee->full_name)->toBe('Jane Smith');
    expect($employee->relationLoaded('user'))->toBeTrue();
});
