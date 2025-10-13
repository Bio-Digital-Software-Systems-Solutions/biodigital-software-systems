<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainingClassNPlusOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        \Spatie\Permission\Models\Permission::create(['name' => 'view trainings']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage trainings']);
    }

    public function test_index_does_not_have_n_plus_one_queries(): void
    {
        // Create user with permissions
        $user = User::factory()->create();
        $user->givePermissionTo('view trainings');

        // Create multiple trainings with students
        $trainings = Training::factory()->count(5)->create();

        foreach ($trainings as $training) {
            // Create 3 students per training
            $students = User::factory()->count(3)->create();
            foreach ($students as $student) {
                $training->students()->attach($student->id, [
                    'status' => 'approved',
                    'grade' => rand(60, 100),
                    'progress' => rand(0, 100),
                    'attendance_rate' => rand(50, 100),
                ]);
            }

            // Create 2 training classes per training with teacher
            $teacher = User::factory()->create();
            TrainingClass::factory()->count(2)->create([
                'training_id' => $training->id,
                'teacher_id' => $teacher->id,
            ]);
        }

        // Clear any queries that happened during setup
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Make the request
        $response = $this->actingAs($user)->get(route('training-classes.index'));

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // With proper eager loading, we should have a reasonable number of queries
        // Without N+1, we expect:
        // 1. Select training classes with relations
        // 2-3. Any additional queries for auth/session
        // We should NOT have queries proportional to the number of classes (10 classes in this case)

        $this->assertLessThan(15, $queryCount,
            "Expected less than 15 queries, but got {$queryCount}. Possible N+1 query detected.\n" .
            "Queries: " . json_encode(array_map(fn($q) => $q['query'], $queries), JSON_PRETTY_PRINT)
        );

        $response->assertStatus(200);
    }

    public function test_show_does_not_have_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view trainings');

        // Create training with students
        $training = Training::factory()->create();
        $students = User::factory()->count(10)->create();

        foreach ($students as $student) {
            $training->students()->attach($student->id, [
                'status' => 'approved',
                'grade' => rand(60, 100),
                'progress' => rand(0, 100),
                'attendance_rate' => rand(50, 100),
            ]);
        }

        $teacher = User::factory()->create();
        $trainingClass = TrainingClass::factory()->create([
            'training_id' => $training->id,
            'teacher_id' => $teacher->id,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('training-classes.show', $trainingClass->uuid));

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Should not have queries proportional to number of students
        $this->assertLessThan(15, $queryCount,
            "Expected less than 15 queries, but got {$queryCount}. Possible N+1 query detected.\n" .
            "Queries: " . json_encode(array_map(fn($q) => $q['query'], $queries), JSON_PRETTY_PRINT)
        );

        $response->assertStatus(200);
    }

    public function test_store_does_not_have_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage trainings');

        // Create training with students
        $training = Training::factory()->create();
        $students = User::factory()->count(5)->create();

        foreach ($students as $student) {
            $training->students()->attach($student->id, [
                'status' => 'approved',
                'grade' => null,
                'progress' => 0,
                'attendance_rate' => 0,
            ]);
        }

        $teacher = User::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->postJson(route('training-classes.store'), [
            'training_id' => $training->id,
            'teacher_id' => $teacher->id,
            'name' => 'Test Class',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'room' => 'Room A',
            'max_students' => 30,
            'notes' => 'Test notes',
            'schedules' => [
                [
                    'day_of_week' => 'Lundi',
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'room' => 'Room A',
                ],
            ],
        ]);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Should not have queries proportional to number of students
        $this->assertLessThan(20, $queryCount,
            "Expected less than 20 queries, but got {$queryCount}. Possible N+1 query detected.\n" .
            "Queries: " . json_encode(array_map(fn($q) => $q['query'], $queries), JSON_PRETTY_PRINT)
        );

        $response->assertStatus(200);
    }

    public function test_update_does_not_have_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage trainings');

        // Create training with students
        $training = Training::factory()->create();
        $students = User::factory()->count(5)->create();

        foreach ($students as $student) {
            $training->students()->attach($student->id, [
                'status' => 'approved',
                'grade' => null,
                'progress' => 0,
                'attendance_rate' => 0,
            ]);
        }

        $teacher = User::factory()->create();
        $trainingClass = TrainingClass::factory()->create([
            'training_id' => $training->id,
            'teacher_id' => $teacher->id,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->putJson(route('training-classes.update', $trainingClass->uuid), [
            'training_id' => $training->id,
            'teacher_id' => $teacher->id,
            'name' => 'Updated Class',
            'date' => $trainingClass->date,
            'room' => 'Room B',
            'max_students' => 25,
            'notes' => 'Updated notes',
            'schedules' => [
                [
                    'day_of_week' => 'Mardi',
                    'start_time' => '14:00',
                    'end_time' => '17:00',
                    'room' => 'Room B',
                ],
            ],
        ]);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Should not have queries proportional to number of students
        $this->assertLessThan(25, $queryCount,
            "Expected less than 25 queries, but got {$queryCount}. Possible N+1 query detected.\n" .
            "Queries: " . json_encode(array_map(fn($q) => $q['query'], $queries), JSON_PRETTY_PRINT)
        );

        $response->assertStatus(200);
    }

    public function test_schedules_does_not_have_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view trainings');

        // Create multiple trainings with classes and teachers
        $trainings = Training::factory()->count(5)->create();

        foreach ($trainings as $training) {
            $teachers = User::factory()->count(2)->create();

            foreach ($teachers as $teacher) {
                TrainingClass::factory()->count(2)->create([
                    'training_id' => $training->id,
                    'teacher_id' => $teacher->id,
                    'date' => now()->addDays(rand(1, 30))->format('Y-m-d'),
                ]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->getJson(route('training-classes.schedules'));

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // With 5 trainings x 4 classes = 20 total classes
        // Should not have 20+ teacher queries (N+1)
        $this->assertLessThan(10, $queryCount,
            "Expected less than 10 queries, but got {$queryCount}. Possible N+1 query detected.\n" .
            "Queries: " . json_encode(array_map(fn($q) => $q['query'], $queries), JSON_PRETTY_PRINT)
        );

        $response->assertStatus(200);
    }

    public function test_no_n_plus_one_with_large_dataset(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view trainings');

        // Create a large dataset to make N+1 issues more obvious
        $trainings = Training::factory()->count(10)->create();

        foreach ($trainings as $training) {
            // 10 students per training
            $students = User::factory()->count(10)->create();
            foreach ($students as $student) {
                $training->students()->attach($student->id, [
                    'status' => 'approved',
                    'grade' => rand(60, 100),
                    'progress' => rand(0, 100),
                    'attendance_rate' => rand(50, 100),
                ]);
            }

            // 5 classes per training
            $teachers = User::factory()->count(5)->create();
            foreach ($teachers as $teacher) {
                TrainingClass::factory()->create([
                    'training_id' => $training->id,
                    'teacher_id' => $teacher->id,
                ]);
            }
        }

        // Total: 10 trainings x 5 classes = 50 training classes
        // If there's an N+1, we'd see 50+ extra queries

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('training-classes.index'));

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // With 50 classes, if there's N+1, we'd have 50+ queries
        // With proper eager loading, should be much less
        $this->assertLessThan(20, $queryCount,
            "Expected less than 20 queries with 50 training classes, but got {$queryCount}. " .
            "This suggests an N+1 query problem.\n" .
            "Queries: " . json_encode(array_map(fn($q) => $q['query'], $queries), JSON_PRETTY_PRINT)
        );

        $response->assertStatus(200);
    }
}
