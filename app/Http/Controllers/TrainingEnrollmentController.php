<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Mail\TrainingEnrollmentApproved;
use App\Mail\TrainingEnrollmentRejected;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TrainingEnrollmentController extends Controller
{
    /**
     * Display a listing of all enrollment requests
     */
    public function index()
    {
        $enrollments = DB::table('training_enrollments')
            ->join('users', 'training_enrollments.user_id', '=', 'users.id')
            ->join('trainings', 'training_enrollments.training_id', '=', 'trainings.id')
            ->select(
                'training_enrollments.id',
                'training_enrollments.user_id',
                'training_enrollments.training_id',
                'trainings.title as training_name',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                'users.email as user_email',
                'training_enrollments.status',
                'training_enrollments.motivation',
                'training_enrollments.payment_method',
                'training_enrollments.enrolled_at',
                'training_enrollments.created_at',
                'training_enrollments.progress',
                'training_enrollments.grade',
                'training_enrollments.attendance_rate'
            )
            ->orderByRaw("CASE
                WHEN training_enrollments.status = 'pending' THEN 1
                WHEN training_enrollments.status = 'approved' THEN 2
                ELSE 3
            END")
            ->orderBy('training_enrollments.created_at', 'desc')
            ->get();

        return response()->json($enrollments);
    }

    /**
     * Approve an enrollment request
     */
    public function approve($id)
    {
        try {
            $enrollment = DB::table('training_enrollments')
                ->where('id', $id)
                ->first();

            if (! $enrollment) {
                return response()->json(['message' => 'Enrollment not found'], 404);
            }

            if ($enrollment->status !== 'pending') {
                return response()->json(['message' => 'Enrollment already processed'], 400);
            }

            // Get user and training details
            $user = User::find($enrollment->user_id);
            $training = Training::find($enrollment->training_id);

            if (! $user || ! $training) {
                return response()->json(['message' => 'User or training not found'], 404);
            }

            // Update enrollment status
            DB::table('training_enrollments')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'updated_at' => now(),
                ]);

            // Assign Student role to user if they don't have it
            if (! $user->hasRole(Role::STUDENT)) {
                $user->assignRole(Role::STUDENT);
            }

            // Get training class information if available
            $trainingClass = DB::table('training_classes')
                ->join('users as teachers', 'training_classes.teacher_id', '=', 'teachers.id')
                ->where('training_classes.training_id', $training->id)
                ->select(
                    'training_classes.*',
                    DB::raw("CONCAT(teachers.first_name, ' ', teachers.last_name) as teacher_name")
                )
                ->first();

            // Get schedules if training class exists
            $schedules = null;
            if ($trainingClass) {
                $schedules = DB::table('training_class_schedules')
                    ->where('training_class_id', $trainingClass->id)
                    ->select('day_of_week', 'start_time', 'end_time')
                    ->get()
                    ->map(function ($schedule): \stdClass {
                        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                        $schedule->day_name = $days[$schedule->day_of_week] ?? 'Jour inconnu';

                        return $schedule;
                    })
                    ->toArray();
            }

            // Send approval email
            Mail::to($user->email)->send(
                new TrainingEnrollmentApproved(
                    userName: $user->first_name.' '.$user->last_name,
                    trainingName: $training->title,
                    trainingClass: $trainingClass,
                    schedules: $schedules
                )
            );

            return response()->json([
                'message' => 'Enrollment approved successfully',
                'enrollment' => DB::table('training_enrollments')->where('id', $id)->first(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error approving enrollment: '.$e->getMessage());

            return response()->json(['message' => 'Error approving enrollment'], 500);
        }
    }

    /**
     * Reject an enrollment request
     */
    public function reject(Request $request, $id)
    {
        // Validate rejection reason
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            $enrollment = DB::table('training_enrollments')
                ->where('id', $id)
                ->first();

            if (! $enrollment) {
                return response()->json(['message' => 'Enrollment not found'], 404);
            }

            if ($enrollment->status !== 'pending') {
                return response()->json(['message' => 'Enrollment already processed'], 400);
            }

            // Get user and training details
            $user = User::find($enrollment->user_id);
            $training = Training::find($enrollment->training_id);

            if (! $user || ! $training) {
                return response()->json(['message' => 'User or training not found'], 404);
            }

            // Update enrollment status with rejection reason
            DB::table('training_enrollments')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'rejection_reason' => $validated['rejection_reason'],
                    'updated_at' => now(),
                ]);

            // Send rejection email
            Mail::to($user->email)->send(
                new TrainingEnrollmentRejected(
                    userName: $user->first_name.' '.$user->last_name,
                    trainingName: $training->title,
                    rejectionReason: $validated['rejection_reason']
                )
            );

            return response()->json([
                'message' => 'Enrollment rejected successfully',
                'enrollment' => DB::table('training_enrollments')->where('id', $id)->first(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error rejecting enrollment: '.$e->getMessage());

            return response()->json(['message' => 'Error rejecting enrollment'], 500);
        }
    }
}
