<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->student = User::factory()->create();
    $this->student->assignRole('student');

    $this->training = Training::factory()->create(['visibility' => 'public', 'is_active' => true]);
});

/*
|--------------------------------------------------------------------------
| Training show page returns correct attempt data
|--------------------------------------------------------------------------
*/

it('returns completed_attempts_count of 0 when student has no attempts', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 3,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.completed_attempts_count', 0)
        ->where('training.quizzes.0.max_attempts', 3)
        ->where('training.quizzes.0.can_retry', false)
        ->where('training.quizzes.0.user_attempt', null)
    );
});

it('returns completed_attempts_count of 1 and can_retry true after first attempt', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 3,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'status' => 'completed',
        'score' => 5,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.completed_attempts_count', 1)
        ->where('training.quizzes.0.max_attempts', 3)
        ->where('training.quizzes.0.can_retry', true)
    );
});

it('returns can_retry false when max attempts reached', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 2,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    // Create 2 completed attempts
    QuizAttempt::factory()->count(2)->create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'status' => 'completed',
        'score' => 5,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.completed_attempts_count', 2)
        ->where('training.quizzes.0.max_attempts', 2)
        ->where('training.quizzes.0.can_retry', false)
    );
});

it('returns can_retry true at penultimate attempt', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 5,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    // Create 4 out of 5 completed attempts
    QuizAttempt::factory()->count(4)->create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'status' => 'completed',
        'score' => 3,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.completed_attempts_count', 4)
        ->where('training.quizzes.0.max_attempts', 5)
        ->where('training.quizzes.0.can_retry', true)
    );
});

it('does not count in_progress attempts as completed', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 3,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    // 1 completed + 1 in_progress
    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'status' => 'completed',
        'score' => 5,
        'completed_at' => now(),
    ]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'status' => 'in_progress',
        'score' => 0,
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.completed_attempts_count', 1)
        ->where('training.quizzes.0.can_retry', true)
    );
});

it('returns single attempt quiz with can_retry false after completion', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 1,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'status' => 'completed',
        'score' => 8,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.completed_attempts_count', 1)
        ->where('training.quizzes.0.max_attempts', 1)
        ->where('training.quizzes.0.can_retry', false)
    );
});

/*
|--------------------------------------------------------------------------
| Quiz start endpoint respects max attempts
|--------------------------------------------------------------------------
*/

it('allows student to start quiz with zero attempts', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 3,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Quiz/Take'));
});

it('allows student to retry quiz when under max attempts', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 3,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'status' => 'completed',
        'score' => 5,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Quiz/Take'));
});

it('blocks student from starting quiz when max attempts reached', function (): void {
    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 2,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    QuizAttempt::factory()->count(2)->create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'status' => 'completed',
        'score' => 5,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Vous avez atteint le nombre maximum de tentatives (2).');
});

it('does not count other students attempts against current student', function (): void {
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');

    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 1,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    // Other student completed the quiz
    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'student_id' => $otherStudent->id,
        'status' => 'completed',
        'score' => 10,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->student)
        ->get(route('quizzes.start', $quiz->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Quiz/Take'));
});

/*
|--------------------------------------------------------------------------
| Attempt data isolation between students
|--------------------------------------------------------------------------
*/

it('returns attempt data only for current student on training show', function (): void {
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');

    $quiz = Quiz::factory()->create([
        'training_id' => $this->training->id,
        'is_active' => true,
        'status' => 'published',
        'max_attempts' => 3,
    ]);
    QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    // Other student has 2 attempts
    QuizAttempt::factory()->count(2)->create([
        'quiz_id' => $quiz->id,
        'student_id' => $otherStudent->id,
        'status' => 'completed',
        'score' => 5,
        'completed_at' => now(),
    ]);

    // Current student has no attempts
    $response = $this->actingAs($this->student)
        ->get(route('trainings.show', $this->training->uuid));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('training.quizzes.0.completed_attempts_count', 0)
        ->where('training.quizzes.0.can_retry', false)
        ->where('training.quizzes.0.user_attempt', null)
    );
});
