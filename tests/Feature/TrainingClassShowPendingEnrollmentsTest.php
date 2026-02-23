<?php

use App\Models\Teacher;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $teacher = Teacher::create([
        'user_id' => User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe'])->id,
        'specialization' => 'Computer Science',
        'bio' => 'Experienced teacher',
        'is_active' => true,
    ]);

    $this->training = Training::factory()->create();

    $this->trainingClass = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
        'teacher_id' => $teacher->user_id,
    ]);
});

it('includes pending enrollments in show page', function () {
    $student = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Martin']);

    DB::table('training_enrollments')->insert([
        'user_id' => $student->id,
        'training_id' => $this->training->id,
        'training_class_id' => $this->trainingClass->id,
        'status' => 'pending',
        'progress' => 0,
        'attendance_rate' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('training-classes.show', $this->trainingClass->uuid));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('TrainingClass/Show')
        ->has('pendingEnrollments', 1)
        ->where('pendingEnrollments.0.user_name', 'Alice Martin')
    );
});

it('does not include approved enrollments in pending list', function () {
    $student = User::factory()->create();

    DB::table('training_enrollments')->insert([
        'user_id' => $student->id,
        'training_id' => $this->training->id,
        'training_class_id' => $this->trainingClass->id,
        'status' => 'approved',
        'progress' => 0,
        'attendance_rate' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('training-classes.show', $this->trainingClass->uuid));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('pendingEnrollments', 0)
    );
});

it('returns empty pending enrollments when none exist', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('training-classes.show', $this->trainingClass->uuid));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('pendingEnrollments', 0)
    );
});
