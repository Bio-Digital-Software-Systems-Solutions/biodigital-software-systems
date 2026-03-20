<?php

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Training;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->teacher = User::factory()->create();
    $this->teacher->assignRole('teacher');

    $this->student = User::factory()->create();
    $this->student->assignRole('student');

    $this->training = Training::factory()->create();
});

/*
|--------------------------------------------------------------------------
| Quiz creation with datetime availability
|--------------------------------------------------------------------------
*/

it('can create a quiz with datetime availability', function (): void {
    $quizData = [
        'title' => 'Quiz avec horaires',
        'description' => 'Un quiz avec des heures précises',
        'duration_minutes' => 30,
        'passing_score' => 60,
        'available_from' => '2026-03-20 00:00:00',
        'available_until' => '2026-03-21 23:59:00',
        'is_active' => true,
        'questions' => [
            [
                'question' => 'Question 1?',
                'type' => 'multiple_choice',
                'options' => ['A', 'B', 'C'],
                'correct_answers' => ['A'],
                'points' => 5,
            ],
        ],
    ];

    $response = $this->actingAs($this->teacher)
        ->post(route('trainings.quizzes.store', $this->training->uuid), $quizData);

    $response->assertRedirect(route('trainings.quizzes.index', $this->training->uuid));
    $response->assertSessionHas('success');

    $quiz = Quiz::where('training_id', $this->training->id)->first();
    expect($quiz->available_from)->toBeInstanceOf(Carbon::class);
    expect($quiz->available_until)->toBeInstanceOf(Carbon::class);
    expect($quiz->available_from->format('Y-m-d H:i:s'))->toBe('2026-03-20 00:00:00');
    expect($quiz->available_until->format('Y-m-d H:i:s'))->toBe('2026-03-21 23:59:00');
});

it('can create a quiz with datetime-local format', function (): void {
    $quizData = [
        'title' => 'Quiz datetime-local',
        'description' => null,
        'duration_minutes' => 20,
        'passing_score' => 50,
        'available_from' => '2026-03-20T08:00',
        'available_until' => '2026-03-21T18:30',
        'is_active' => true,
        'questions' => [
            [
                'question' => 'Question?',
                'type' => 'true_false',
                'options' => null,
                'correct_answers' => [true],
                'points' => 3,
            ],
        ],
    ];

    $response = $this->actingAs($this->teacher)
        ->post(route('trainings.quizzes.store', $this->training->uuid), $quizData);

    $response->assertRedirect();

    $quiz = Quiz::where('training_id', $this->training->id)->first();
    expect($quiz->available_from->format('Y-m-d H:i'))->toBe('2026-03-20 08:00');
    expect($quiz->available_until->format('Y-m-d H:i'))->toBe('2026-03-21 18:30');
});

it('can create a quiz with null availability dates', function (): void {
    $quizData = [
        'title' => 'Quiz sans dates',
        'description' => null,
        'duration_minutes' => 15,
        'passing_score' => 60,
        'available_from' => null,
        'available_until' => null,
        'is_active' => true,
        'questions' => [
            [
                'question' => 'Question?',
                'type' => 'true_false',
                'options' => null,
                'correct_answers' => [true],
                'points' => 5,
            ],
        ],
    ];

    $response = $this->actingAs($this->teacher)
        ->post(route('trainings.quizzes.store', $this->training->uuid), $quizData);

    $response->assertRedirect();

    $quiz = Quiz::where('training_id', $this->training->id)->first();
    expect($quiz->available_from)->toBeNull();
    expect($quiz->available_until)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Quiz update with datetime availability
|--------------------------------------------------------------------------
*/

it('can update quiz datetime availability', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'available_from' => '2026-03-20 00:00:00',
        'available_until' => '2026-03-21 23:59:00',
    ]);
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $updateData = [
        'title' => $quiz->title,
        'description' => $quiz->description,
        'duration_minutes' => $quiz->duration_minutes,
        'passing_score' => $quiz->passing_score,
        'available_from' => '2026-04-01T09:00',
        'available_until' => '2026-04-15T17:30',
        'is_active' => true,
        'questions' => [
            [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'options' => $question->options,
                'correct_answers' => $question->correct_answers,
                'points' => $question->points,
            ],
        ],
    ];

    $response = $this->actingAs($this->teacher)
        ->put(route('trainings.quizzes.update', [$this->training->uuid, $quiz->uuid]), $updateData);

    $response->assertRedirect();

    $quiz->refresh();
    expect($quiz->available_from->format('Y-m-d H:i'))->toBe('2026-04-01 09:00');
    expect($quiz->available_until->format('Y-m-d H:i'))->toBe('2026-04-15 17:30');
});

/*
|--------------------------------------------------------------------------
| Validation rules
|--------------------------------------------------------------------------
*/

it('validates available_until must be after or equal to available_from', function (): void {
    $quizData = [
        'title' => 'Quiz invalide',
        'description' => null,
        'duration_minutes' => 30,
        'passing_score' => 60,
        'available_from' => '2026-03-21T10:00',
        'available_until' => '2026-03-20T09:00',
        'is_active' => true,
        'questions' => [
            [
                'question' => 'Question?',
                'type' => 'true_false',
                'options' => null,
                'correct_answers' => [true],
                'points' => 5,
            ],
        ],
    ];

    $response = $this->actingAs($this->teacher)
        ->post(route('trainings.quizzes.store', $this->training->uuid), $quizData);

    $response->assertSessionHasErrors('available_until');
});

it('validates available_until time must be after available_from on same day', function (): void {
    $quizData = [
        'title' => 'Quiz meme jour',
        'description' => null,
        'duration_minutes' => 30,
        'passing_score' => 60,
        'available_from' => '2026-03-20T14:00',
        'available_until' => '2026-03-20T10:00',
        'is_active' => true,
        'questions' => [
            [
                'question' => 'Question?',
                'type' => 'true_false',
                'options' => null,
                'correct_answers' => [true],
                'points' => 5,
            ],
        ],
    ];

    $response = $this->actingAs($this->teacher)
        ->post(route('trainings.quizzes.store', $this->training->uuid), $quizData);

    $response->assertSessionHasErrors('available_until');
});

it('allows available_from and available_until at the same datetime', function (): void {
    $quizData = [
        'title' => 'Quiz instant',
        'description' => null,
        'duration_minutes' => 30,
        'passing_score' => 60,
        'available_from' => '2026-03-20T10:00',
        'available_until' => '2026-03-20T10:00',
        'is_active' => true,
        'questions' => [
            [
                'question' => 'Question?',
                'type' => 'true_false',
                'options' => null,
                'correct_answers' => [true],
                'points' => 5,
            ],
        ],
    ];

    $response = $this->actingAs($this->teacher)
        ->post(route('trainings.quizzes.store', $this->training->uuid), $quizData);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
});

/*
|--------------------------------------------------------------------------
| Availability check with datetime precision
|--------------------------------------------------------------------------
*/

it('prevents starting quiz before available_from datetime', function (): void {
    Carbon::setTestNow('2026-03-20 09:59:00');

    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'available_from' => '2026-03-20 10:00:00',
        'available_until' => '2026-03-21 23:59:00',
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Ce quiz n\'est pas encore disponible.');

    Carbon::setTestNow();
});

it('allows starting quiz exactly at available_from datetime', function (): void {
    Carbon::setTestNow('2026-03-20 10:00:00');

    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'available_from' => '2026-03-20 10:00:00',
        'available_until' => '2026-03-21 23:59:00',
    ]);
    QuizQuestion::factory()->count(2)->create(['quiz_id' => $quiz->id]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Quiz/Take'));

    Carbon::setTestNow();
});

it('allows starting quiz during availability window', function (): void {
    Carbon::setTestNow('2026-03-20 15:30:00');

    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'available_from' => '2026-03-20 10:00:00',
        'available_until' => '2026-03-21 23:59:00',
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertStatus(200);

    Carbon::setTestNow();
});

it('prevents starting quiz after available_until datetime', function (): void {
    Carbon::setTestNow('2026-03-21 23:59:01');

    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'available_from' => '2026-03-20 00:00:00',
        'available_until' => '2026-03-21 23:59:00',
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Ce quiz n\'est plus disponible.');

    Carbon::setTestNow();
});

/*
|--------------------------------------------------------------------------
| Datetime format in API response
|--------------------------------------------------------------------------
*/

it('returns datetime in ISO format for index listing', function (): void {
    Quiz::factory()->create([
        'training_id' => $this->training->id,
        'available_from' => '2026-03-20 08:00:00',
        'available_until' => '2026-03-21 18:30:00',
    ]);

    $response = $this->actingAs($this->teacher)
        ->get(route('trainings.quizzes.index', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Quiz/Index')
        ->has('quizzes', 1)
        ->where('quizzes.0.available_from', '2026-03-20T08:00')
        ->where('quizzes.0.available_until', '2026-03-21T18:30')
    );
});

it('returns datetime in ISO format for edit page', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'available_from' => '2026-03-20 14:00:00',
        'available_until' => '2026-03-21 22:00:00',
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $response = $this->actingAs($this->teacher)
        ->get(route('trainings.quizzes.edit', [$this->training->uuid, $quiz->uuid]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Quiz/Edit')
        ->where('quiz.available_from', '2026-03-20T14:00')
        ->where('quiz.available_until', '2026-03-21T22:00')
    );
});

/*
|--------------------------------------------------------------------------
| Edge cases
|--------------------------------------------------------------------------
*/

it('handles quiz available with only available_from set', function (): void {
    Carbon::setTestNow('2026-03-20 12:00:00');

    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'available_from' => '2026-03-20 10:00:00',
        'available_until' => null,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertStatus(200);

    Carbon::setTestNow();
});

it('handles quiz available with only available_until set', function (): void {
    Carbon::setTestNow('2026-03-20 12:00:00');

    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'available_from' => null,
        'available_until' => '2026-03-21 23:59:00',
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertStatus(200);

    Carbon::setTestNow();
});

it('stores datetime with minute precision', function (): void {
    $quizData = [
        'title' => 'Quiz precision minutes',
        'description' => null,
        'duration_minutes' => 30,
        'passing_score' => 60,
        'available_from' => '2026-03-20T09:15',
        'available_until' => '2026-03-21T17:45',
        'is_active' => true,
        'questions' => [
            [
                'question' => 'Question?',
                'type' => 'true_false',
                'options' => null,
                'correct_answers' => [true],
                'points' => 5,
            ],
        ],
    ];

    $response = $this->actingAs($this->teacher)
        ->post(route('trainings.quizzes.store', $this->training->uuid), $quizData);

    $response->assertRedirect();

    $quiz = Quiz::where('training_id', $this->training->id)->first();
    expect($quiz->available_from->format('H:i'))->toBe('09:15');
    expect($quiz->available_until->format('H:i'))->toBe('17:45');
});
