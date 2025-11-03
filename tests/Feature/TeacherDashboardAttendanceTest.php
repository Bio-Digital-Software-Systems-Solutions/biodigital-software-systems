<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherDashboardAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected Training $training;
    protected TrainingClass $trainingClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Run permission seeder
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

        // Create teacher
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');
        $this->teacher->givePermissionTo('manage trainings');

        // Create training
        $this->training = Training::factory()->create([
            'teacher_id' => $this->teacher->id,
        ]);

        // Create training class
        $this->trainingClass = TrainingClass::factory()->create([
            'training_id' => $this->training->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    /** @test */
    public function teacher_can_view_attendance_data()
    {
        // Create students
        $student1 = User::factory()->create();
        $student2 = User::factory()->create();
        $student3 = User::factory()->create();

        // Enroll students
        foreach ([$student1, $student2, $student3] as $student) {
            $this->training->students()->attach($student->id, [
                'training_class_id' => $this->trainingClass->id,
                'status' => 'approved',
                'enrolled_at' => now(),
            ]);
        }

        // Create attendance records
        Attendance::create([
            'training_class_id' => $this->trainingClass->id,
            'student_id' => $student1->id,
            'status' => 'present',
        ]);

        Attendance::create([
            'training_class_id' => $this->trainingClass->id,
            'student_id' => $student2->id,
            'status' => 'absent',
        ]);

        Attendance::create([
            'training_class_id' => $this->trainingClass->id,
            'student_id' => $student3->id,
            'status' => 'excused',
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $attendanceData = $response->viewData('page')['props']['attendanceData'];

        $this->assertNotEmpty($attendanceData);
        $this->assertEquals(3, $attendanceData[0]['total_students']);
        $this->assertEquals(1, $attendanceData[0]['present_count']);
        $this->assertEquals(1, $attendanceData[0]['absent_count']);
        $this->assertEquals(1, $attendanceData[0]['excused_count']);
    }

    /** @test */
    public function attendance_rate_is_calculated_correctly()
    {
        // Create 10 students
        $students = User::factory()->count(10)->create();

        foreach ($students as $student) {
            $this->training->students()->attach($student->id, [
                'training_class_id' => $this->trainingClass->id,
                'status' => 'approved',
                'enrolled_at' => now(),
            ]);
        }

        // 7 present, 2 absent, 1 excused
        for ($i = 0; $i < 7; $i++) {
            Attendance::create([
                'training_class_id' => $this->trainingClass->id,
                'student_id' => $students[$i]->id,
                'status' => 'present',
            ]);
        }

        for ($i = 7; $i < 9; $i++) {
            Attendance::create([
                'training_class_id' => $this->trainingClass->id,
                'student_id' => $students[$i]->id,
                'status' => 'absent',
            ]);
        }

        Attendance::create([
            'training_class_id' => $this->trainingClass->id,
            'student_id' => $students[9]->id,
            'status' => 'excused',
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $attendanceData = $response->viewData('page')['props']['attendanceData'];

        $this->assertEquals(10, $attendanceData[0]['total_students']);
        $this->assertEquals(7, $attendanceData[0]['present_count']);
        $this->assertEquals(70.0, $attendanceData[0]['attendance_rate']); // 7/10 * 100
    }

    /** @test */
    public function teacher_only_sees_their_own_class_attendance()
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');
        $otherTeacher->givePermissionTo('manage trainings');

        $otherTraining = Training::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $otherClass = TrainingClass::factory()->create([
            'training_id' => $otherTraining->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        $student = User::factory()->create();

        $otherTraining->students()->attach($student->id, [
            'training_class_id' => $otherClass->id,
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);

        Attendance::create([
            'training_class_id' => $otherClass->id,
            'student_id' => $student->id,
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $attendanceData = $response->viewData('page')['props']['attendanceData'];

        // Should have teacher's class but not other teacher's class
        $classIds = array_column($attendanceData, 'id');
        $this->assertContains($this->trainingClass->id, $classIds);
        $this->assertNotContains($otherClass->id, $classIds);
    }

    /** @test */
    public function class_with_no_attendance_shows_zero_rate()
    {
        // Create students but no attendance records
        $students = User::factory()->count(5)->create();

        foreach ($students as $student) {
            $this->training->students()->attach($student->id, [
                'training_class_id' => $this->trainingClass->id,
                'status' => 'approved',
                'enrolled_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $attendanceData = $response->viewData('page')['props']['attendanceData'];

        $this->assertEquals(5, $attendanceData[0]['total_students']);
        $this->assertEquals(0, $attendanceData[0]['present_count']);
        $this->assertEquals(0.0, $attendanceData[0]['attendance_rate']);
    }

    /** @test */
    public function class_with_no_students_shows_zero_rate()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $attendanceData = $response->viewData('page')['props']['attendanceData'];

        $this->assertEquals(0, $attendanceData[0]['total_students']);
        $this->assertEquals(0.0, $attendanceData[0]['attendance_rate']);
    }
}
