<?php

use App\Models\Employee;
use App\Models\Star;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Enable query logging
    DB::enableQueryLog();
});

afterEach(function () {
    DB::disableQueryLog();
});

it('eager loads user relation when fetching stars', function () {
    // Create 5 stars with users
    $users = User::factory()->count(5)->create();
    foreach ($users as $user) {
        Star::factory()->create(['user_id' => $user->id]);
    }

    DB::flushQueryLog();

    // Fetch all stars and access full_name (which triggers user relation)
    $stars = Star::all();
    foreach ($stars as $star) {
        $star->full_name; // Access the appended attribute
    }

    $queries = DB::getQueryLog();

    // Should be 2 queries max: one for stars, one for users (eager loaded)
    // Without eager loading, it would be 1 + 5 = 6 queries
    expect(count($queries))->toBeLessThanOrEqual(2);
});

it('eager loads user relation when fetching employees', function () {
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

it('includes user data when serializing star to array', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    $star = Star::factory()->create(['user_id' => $user->id]);

    // Refresh to load from database
    $star = Star::find($star->id);

    expect($star->full_name)->toBe('John Doe');
    expect($star->relationLoaded('user'))->toBeTrue();
});

it('includes user data when serializing employee to array', function () {
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
