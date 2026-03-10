<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\TrainingClassSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingClassScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create basic permissions
        \Spatie\Permission\Models\Permission::create(['name' => 'view trainings']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage trainings']);
    }

    public function test_can_get_schedule_attendance_data(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view trainings');

        // Create a training with students
        $training = Training::factory()->create();
        $students = User::factory()->count(3)->create();

        foreach ($students as $student) {
            $training->students()->attach($student->id, [
                'status' => 'approved',
                'grade' => random_int(60, 100),
                'progress' => random_int(0, 100),
                'attendance_rate' => random_int(50, 100),
            ]);
        }

        // Create training class
        $trainingClass = TrainingClass::factory()->create([
            'training_id' => $training->id,
        ]);

        // Create schedule
        $schedule = TrainingClassSchedule::factory()->create([
            'training_class_id' => $trainingClass->id,
            'day_of_week' => 'Lundi',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_active' => true,
        ]);

        // Create attendance records
        Attendance::create([
            'training_class_id' => $trainingClass->id,
            'training_class_schedule_id' => $schedule->id,
            'student_id' => $students[0]->id,
            'status' => 'present',
            'notes' => null,
        ]);

        Attendance::create([
            'training_class_id' => $trainingClass->id,
            'training_class_schedule_id' => $schedule->id,
            'student_id' => $students[1]->id,
            'status' => 'absent',
            'notes' => 'Malade',
        ]);

        $response = $this->actingAs($user)->getJson(route('training-class-schedules.attendance', $schedule->uuid));

        $response->assertOk();
        $response->assertJsonCount(3); // Should return all 3 students

        // Verify response structure
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'email',
                'grade',
                'progress',
                'attendance_rate',
                'attendance_status',
                'attendance_reason',
            ]
        ]);

        // Verify attendance data is correct
        $data = $response->json();

        $student1Data = collect($data)->firstWhere('id', $students[0]->id);
        $this->assertEquals('present', $student1Data['attendance_status']);
        $this->assertNull($student1Data['attendance_reason']);

        $student2Data = collect($data)->firstWhere('id', $students[1]->id);
        $this->assertEquals('absent', $student2Data['attendance_status']);
        $this->assertEquals('Malade', $student2Data['attendance_reason']);

        $student3Data = collect($data)->firstWhere('id', $students[2]->id);
        $this->assertNull($student3Data['attendance_status']);
    }

    public function test_can_mark_attendance_for_schedule(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage trainings');

        // Create a training with students
        $training = Training::factory()->create();
        $students = User::factory()->count(3)->create();

        foreach ($students as $student) {
            $training->students()->attach($student->id, [
                'status' => 'approved',
                'grade' => null,
                'progress' => 0,
                'attendance_rate' => 0,
            ]);
        }

        // Create training class
        $trainingClass = TrainingClass::factory()->create([
            'training_id' => $training->id,
        ]);

        // Create schedule
        $schedule = TrainingClassSchedule::factory()->create([
            'training_class_id' => $trainingClass->id,
            'day_of_week' => 'Lundi',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_active' => true,
        ]);

        $attendanceData = [
            'attendances' => [
                [
                    'student_id' => $students[0]->id,
                    'status' => 'present',
                ],
                [
                    'student_id' => $students[1]->id,
                    'status' => 'absent',
                    'reason' => 'Malade',
                ],
                [
                    'student_id' => $students[2]->id,
                    'status' => 'excused',
                    'reason' => 'Rendez-vous médical',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(
            route('training-class-schedules.mark-attendance', $schedule->uuid),
            $attendanceData
        );

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Présences enregistrées avec succès',
        ]);

        // Verify attendance records were created
        $this->assertDatabaseHas('attendances', [
            'training_class_id' => $trainingClass->id,
            'training_class_schedule_id' => $schedule->id,
            'student_id' => $students[0]->id,
            'status' => 'present',
        ]);

        $this->assertDatabaseHas('attendances', [
            'training_class_id' => $trainingClass->id,
            'training_class_schedule_id' => $schedule->id,
            'student_id' => $students[1]->id,
            'status' => 'absent',
            'notes' => 'Malade',
        ]);

        $this->assertDatabaseHas('attendances', [
            'training_class_id' => $trainingClass->id,
            'training_class_schedule_id' => $schedule->id,
            'student_id' => $students[2]->id,
            'status' => 'excused',
            'notes' => 'Rendez-vous médical',
        ]);
    }

    public function test_can_update_existing_attendance(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage trainings');

        // Create a training with students
        $training = Training::factory()->create();
        $student = User::factory()->create();

        $training->students()->attach($student->id, [
            'status' => 'approved',
            'grade' => null,
            'progress' => 0,
            'attendance_rate' => 0,
        ]);

        // Create training class
        $trainingClass = TrainingClass::factory()->create([
            'training_id' => $training->id,
        ]);

        // Create schedule
        $schedule = TrainingClassSchedule::factory()->create([
            'training_class_id' => $trainingClass->id,
            'is_active' => true,
        ]);

        // Create initial attendance
        $attendance = Attendance::create([
            'training_class_id' => $trainingClass->id,
            'training_class_schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'status' => 'present',
            'notes' => null,
        ]);

        // Update attendance
        $attendanceData = [
            'attendances' => [
                [
                    'student_id' => $student->id,
                    'status' => 'absent',
                    'reason' => 'Malade',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(
            route('training-class-schedules.mark-attendance', $schedule->uuid),
            $attendanceData
        );

        $response->assertOk();

        // Verify attendance was updated, not duplicated
        $this->assertEquals(1, Attendance::where('student_id', $student->id)
            ->where('training_class_schedule_id', $schedule->id)
            ->count());

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'student_id' => $student->id,
            'status' => 'absent',
            'notes' => 'Malade',
        ]);
    }

    public function test_validates_attendance_data(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage trainings');

        $training = Training::factory()->create();
        $trainingClass = TrainingClass::factory()->create(['training_id' => $training->id]);
        $schedule = TrainingClassSchedule::factory()->create(['training_class_id' => $trainingClass->id]);

        // Missing required fields
        $response = $this->actingAs($user)->postJson(
            route('training-class-schedules.mark-attendance', $schedule->uuid),
            []
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('attendances');

        // Invalid status
        $response = $this->actingAs($user)->postJson(
            route('training-class-schedules.mark-attendance', $schedule->uuid),
            [
                'attendances' => [
                    [
                        'student_id' => 1,
                        'status' => 'invalid',
                    ],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('attendances.0.status');

        // Non-existent student
        $response = $this->actingAs($user)->postJson(
            route('training-class-schedules.mark-attendance', $schedule->uuid),
            [
                'attendances' => [
                    [
                        'student_id' => 99999,
                        'status' => 'present',
                    ],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('attendances.0.student_id');
    }

    public function test_unauthorized_user_cannot_access_schedule_attendance(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        $training = Training::factory()->create();
        $trainingClass = TrainingClass::factory()->create(['training_id' => $training->id]);
        $schedule = TrainingClassSchedule::factory()->create(['training_class_id' => $trainingClass->id]);

        $response = $this->actingAs($user)->getJson(
            route('training-class-schedules.attendance', $schedule->uuid)
        );

        // Should be denied (either 403 Forbidden or redirect)
        $this->assertContains($response->status(), [403, 302]);
    }

    public function test_unauthorized_user_cannot_mark_attendance(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        $training = Training::factory()->create();
        $trainingClass = TrainingClass::factory()->create(['training_id' => $training->id]);
        $schedule = TrainingClassSchedule::factory()->create(['training_class_id' => $trainingClass->id]);

        $response = $this->actingAs($user)->postJson(
            route('training-class-schedules.mark-attendance', $schedule->uuid),
            [
                'attendances' => [
                    [
                        'student_id' => 1,
                        'status' => 'present',
                    ],
                ],
            ]
        );

        // Should be denied (either 403 Forbidden or redirect)
        $this->assertContains($response->status(), [403, 302]);
    }

    public function test_attendance_rate_is_updated_after_marking_attendance(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage trainings');

        // Create a training with a student
        $training = Training::factory()->create();
        $student = User::factory()->create();

        $training->students()->attach($student->id, [
            'status' => 'approved',
            'grade' => null,
            'progress' => 0,
            'attendance_rate' => 0,
        ]);

        // Create training class with 2 schedules
        $trainingClass = TrainingClass::factory()->create([
            'training_id' => $training->id,
        ]);

        $schedule1 = TrainingClassSchedule::factory()->create([
            'training_class_id' => $trainingClass->id,
            'day_of_week' => 'Lundi',
            'is_active' => true,
        ]);

        $schedule2 = TrainingClassSchedule::factory()->create([
            'training_class_id' => $trainingClass->id,
            'day_of_week' => 'Mardi',
            'is_active' => true,
        ]);

        // Mark attendance as present for schedule 1
        $this->actingAs($user)->postJson(
            route('training-class-schedules.mark-attendance', $schedule1->uuid),
            [
                'attendances' => [
                    [
                        'student_id' => $student->id,
                        'status' => 'present',
                    ],
                ],
            ]
        );

        // Check attendance rate should be 50% (1 out of 2 schedules)
        $training->refresh();
        $pivotData = $training->students()->where('user_id', $student->id)->first()->pivot;
        $this->assertEquals(50, $pivotData->attendance_rate);

        // Mark attendance as present for schedule 2
        $this->actingAs($user)->postJson(
            route('training-class-schedules.mark-attendance', $schedule2->uuid),
            [
                'attendances' => [
                    [
                        'student_id' => $student->id,
                        'status' => 'present',
                    ],
                ],
            ]
        );

        // Check attendance rate should be 100% (2 out of 2 schedules)
        $training->refresh();
        $pivotData = $training->students()->where('user_id', $student->id)->first()->pivot;
        $this->assertEquals(100, $pivotData->attendance_rate);
    }
}
