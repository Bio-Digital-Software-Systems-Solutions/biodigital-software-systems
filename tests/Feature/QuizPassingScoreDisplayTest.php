<?php

use App\Models\Quiz;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->student = User::factory()->create();
    $this->student->assignRole('student');

    $this->teacher = User::factory()->create();
    $this->teacher->assignRole('teacher');

    $this->training = Training::factory()->create(['visibility' => 'public', 'is_active' => true]);
});

/*
|--------------------------------------------------------------------------
| required_points calculation on training show page
|--------------------------------------------------------------------------
*/

it('calculates required_points correctly for 60% of 46 points', function (): void {
    Quiz::factory()->create([
        'training_id' => $this->training->id,
        'max_score' => 46,
        'passing_score' => 60,
        'is_active' => true,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.passing_score', 60)
        ->where('training.quizzes.0.max_score', 46)
        ->where('training.quizzes.0.required_points', 28) // ceil(46 * 60 / 100) = ceil(27.6) = 28
    );
});

it('calculates required_points correctly for 50% of 100 points', function (): void {
    Quiz::factory()->create([
        'training_id' => $this->training->id,
        'max_score' => 100,
        'passing_score' => 50,
        'is_active' => true,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.passing_score', 50)
        ->where('training.quizzes.0.max_score', 100)
        ->where('training.quizzes.0.required_points', 50) // ceil(100 * 50 / 100) = 50
    );
});

it('calculates required_points correctly for 70% of 30 points', function (): void {
    Quiz::factory()->create([
        'training_id' => $this->training->id,
        'max_score' => 30,
        'passing_score' => 70,
        'is_active' => true,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.passing_score', 70)
        ->where('training.quizzes.0.max_score', 30)
        ->where('training.quizzes.0.required_points', 21) // ceil(30 * 70 / 100) = ceil(21) = 21
    );
});

it('rounds up required_points with ceil for 75% of 10 points', function (): void {
    Quiz::factory()->create([
        'training_id' => $this->training->id,
        'max_score' => 10,
        'passing_score' => 75,
        'is_active' => true,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.required_points', 8) // ceil(10 * 75 / 100) = ceil(7.5) = 8
    );
});

it('handles 100% passing score', function (): void {
    Quiz::factory()->create([
        'training_id' => $this->training->id,
        'max_score' => 25,
        'passing_score' => 100,
        'is_active' => true,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.required_points', 25) // ceil(25 * 100 / 100) = 25
    );
});

it('handles 0% passing score', function (): void {
    Quiz::factory()->create([
        'training_id' => $this->training->id,
        'max_score' => 40,
        'passing_score' => 0,
        'is_active' => true,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.required_points', 0) // ceil(40 * 0 / 100) = 0
    );
});

it('calculates required_points with small fractional result', function (): void {
    Quiz::factory()->create([
        'training_id' => $this->training->id,
        'max_score' => 7,
        'passing_score' => 60,
        'is_active' => true,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.required_points', 5) // ceil(7 * 60 / 100) = ceil(4.2) = 5
    );
});

/*
|--------------------------------------------------------------------------
| required_points on quiz index page (admin)
|--------------------------------------------------------------------------
*/

it('returns required_points in quiz index for teachers', function (): void {
    Quiz::factory()->create([
        'training_id' => $this->training->id,
        'max_score' => 46,
        'passing_score' => 60,
    ]);

    $response = $this->actingAs($this->teacher)
        ->get(route('trainings.quizzes.index', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('quizzes.0.passing_score', 60)
        ->where('quizzes.0.max_score', 46)
    );
});
