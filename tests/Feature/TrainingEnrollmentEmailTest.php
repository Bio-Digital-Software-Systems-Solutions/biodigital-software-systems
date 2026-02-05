<?php

namespace Tests\Feature;

use App\Mail\TrainingEnrollmentApproved;
use App\Mail\TrainingEnrollmentRejected;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrainingEnrollmentEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected Training $training;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create admin user
        $this->admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@test.com',
        ]);
        $this->admin->assignRole('admin');

        // Create student user
        $this->student = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'student@test.com',
        ]);

        // Create training
        $this->training = Training::factory()->create([
            'title' => 'Test Training Course',
        ]);
    }

    public function test_approval_email_is_sent_when_enrollment_is_approved()
    {
        Mail::fake();

        // Create a pending enrollment
        $enrollmentId = DB::table('training_enrollments')->insertGetId([
            'user_id' => $this->student->id,
            'training_id' => $this->training->id,
            'status' => 'pending',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'progress' => 0,
            'attendance_rate' => 0,
        ]);

        // Approve enrollment
        $response = $this->actingAs($this->admin)
            ->post(route('training-enrollments.approve', $enrollmentId));

        $response->assertStatus(200);

        // Assert email was sent
        Mail::assertSent(TrainingEnrollmentApproved::class, function ($mail) {
            return $mail->hasTo('student@test.com') &&
                   $mail->userName === 'John Doe' &&
                   $mail->trainingName === 'Test Training Course';
        });
    }

    public function test_rejection_email_is_sent_when_enrollment_is_rejected()
    {
        Mail::fake();

        // Create a pending enrollment
        $enrollmentId = DB::table('training_enrollments')->insertGetId([
            'user_id' => $this->student->id,
            'training_id' => $this->training->id,
            'status' => 'pending',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'progress' => 0,
            'attendance_rate' => 0,
        ]);

        $rejectionReason = 'Le profil ne correspond pas aux prérequis de la formation.';

        // Reject enrollment
        $response = $this->actingAs($this->admin)
            ->post(route('training-enrollments.reject', $enrollmentId), [
                'rejection_reason' => $rejectionReason,
            ]);

        $response->assertStatus(200);

        // Assert email was sent
        Mail::assertSent(TrainingEnrollmentRejected::class, function ($mail) use ($rejectionReason) {
            return $mail->hasTo('student@test.com') &&
                   $mail->userName === 'John Doe' &&
                   $mail->trainingName === 'Test Training Course' &&
                   $mail->rejectionReason === $rejectionReason;
        });
    }

    public function test_rejection_requires_reason()
    {
        // Create a pending enrollment
        $enrollmentId = DB::table('training_enrollments')->insertGetId([
            'user_id' => $this->student->id,
            'training_id' => $this->training->id,
            'status' => 'pending',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'progress' => 0,
            'attendance_rate' => 0,
        ]);

        // Try to reject without reason
        $response = $this->actingAs($this->admin)
            ->postJson(route('training-enrollments.reject', $enrollmentId), [
                'rejection_reason' => '',
            ]);

        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors(['rejection_reason']);
    }

    public function test_rejection_reason_must_be_at_least_10_characters()
    {
        // Create a pending enrollment
        $enrollmentId = DB::table('training_enrollments')->insertGetId([
            'user_id' => $this->student->id,
            'training_id' => $this->training->id,
            'status' => 'pending',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'progress' => 0,
            'attendance_rate' => 0,
        ]);

        // Try to reject with too short reason
        $response = $this->actingAs($this->admin)
            ->postJson(route('training-enrollments.reject', $enrollmentId), [
                'rejection_reason' => 'Too short',
            ]);

        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors(['rejection_reason']);
    }

    public function test_rejection_reason_is_stored_in_database()
    {
        Mail::fake();

        // Create a pending enrollment
        $enrollmentId = DB::table('training_enrollments')->insertGetId([
            'user_id' => $this->student->id,
            'training_id' => $this->training->id,
            'status' => 'pending',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'progress' => 0,
            'attendance_rate' => 0,
        ]);

        $rejectionReason = 'Le profil ne correspond pas aux prérequis de la formation.';

        // Reject enrollment
        $this->actingAs($this->admin)
            ->post(route('training-enrollments.reject', $enrollmentId), [
                'rejection_reason' => $rejectionReason,
            ]);

        // Assert rejection reason is stored
        $enrollment = DB::table('training_enrollments')->where('id', $enrollmentId)->first();
        $this->assertEquals('rejected', $enrollment->status);
        $this->assertEquals($rejectionReason, $enrollment->rejection_reason);
    }

    public function test_student_role_is_assigned_on_approval()
    {
        Mail::fake();

        // Create a pending enrollment
        $enrollmentId = DB::table('training_enrollments')->insertGetId([
            'user_id' => $this->student->id,
            'training_id' => $this->training->id,
            'status' => 'pending',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'progress' => 0,
            'attendance_rate' => 0,
        ]);

        // Verify student doesn't have Student role yet
        $this->assertFalse($this->student->hasRole('Student'));

        // Approve enrollment
        $this->actingAs($this->admin)
            ->post(route('training-enrollments.approve', $enrollmentId));

        // Verify student now has Student role
        $this->student->refresh();
        $this->assertTrue($this->student->hasRole('Student'));
    }

    public function test_student_with_role_can_access_student_dashboard()
    {
        // Assign Student role
        $this->student->assignRole('Student');

        // Create approved enrollment
        DB::table('training_enrollments')->insert([
            'user_id' => $this->student->id,
            'training_id' => $this->training->id,
            'status' => 'approved',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'progress' => 0,
            'attendance_rate' => 0,
        ]);

        // Access student dashboard
        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertStatus(200);
    }

    public function test_user_without_student_role_cannot_access_student_dashboard()
    {
        // User without Student role (only has default roles)
        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }
}
