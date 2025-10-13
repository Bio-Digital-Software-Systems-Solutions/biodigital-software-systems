<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\TrainingClassSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingClassScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view trainings')->only(['attendance']);
        $this->middleware('can:manage trainings')->only(['markAttendance']);
    }

    /**
     * Get students with attendance for a specific schedule
     */
    public function attendance(TrainingClassSchedule $trainingClassSchedule): JsonResponse
    {
        // Load the training class with its training and students
        $trainingClassSchedule->load([
            'trainingClass.training.students' => function ($query) {
                $query->where('status', 'approved');
            },
            'attendances.student'
        ]);

        $trainingClass = $trainingClassSchedule->trainingClass;

        // Map students with their attendance data
        $students = $trainingClass->training->students->map(function ($student) use ($trainingClassSchedule) {
            $attendance = $trainingClassSchedule->attendances->firstWhere('student_id', $student->id);

            return [
                'id' => $student->id,
                'name' => $student->first_name . ' ' . $student->last_name,
                'email' => $student->email,
                'grade' => $student->pivot->grade ?? null,
                'progress' => $student->pivot->progress ?? null,
                'attendance_rate' => $student->pivot->attendance_rate ?? null,
                'attendance_status' => $attendance ? $attendance->status : null,
                'attendance_reason' => $attendance ? $attendance->notes : null,
            ];
        });

        return response()->json($students);
    }

    /**
     * Mark attendance for a specific schedule
     */
    public function markAttendance(Request $request, TrainingClassSchedule $trainingClassSchedule): JsonResponse
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,excused',
            'attendances.*.reason' => 'nullable|string',
        ]);

        $trainingClassSchedule->load('trainingClass');

        foreach ($validated['attendances'] as $attendanceData) {
            Attendance::updateOrCreate(
                [
                    'training_class_id' => $trainingClassSchedule->training_class_id,
                    'training_class_schedule_id' => $trainingClassSchedule->id,
                    'student_id' => $attendanceData['student_id'],
                ],
                [
                    'status' => $attendanceData['status'],
                    'notes' => $attendanceData['reason'] ?? null,
                ]
            );
        }

        // Update attendance rates for students in this training
        $this->updateAttendanceRates($trainingClassSchedule->trainingClass);

        return response()->json([
            'success' => true,
            'message' => 'Présences enregistrées avec succès',
        ]);
    }

    /**
     * Update attendance rates for students in a training class
     */
    private function updateAttendanceRates($trainingClass): void
    {
        $trainingClass->load('training.students');

        foreach ($trainingClass->training->students as $student) {
            // Count total possible attendances (all schedules)
            $totalSchedules = $trainingClass->schedules()->where('is_active', true)->count();

            if ($totalSchedules > 0) {
                // Count attendances marked as 'present' for this student across all schedules
                $presentCount = Attendance::where('training_class_id', $trainingClass->id)
                    ->where('student_id', $student->id)
                    ->where('status', 'present')
                    ->count();

                $attendanceRate = ($presentCount / $totalSchedules) * 100;

                // Update the pivot table
                $trainingClass->training->students()->updateExistingPivot($student->id, [
                    'attendance_rate' => round($attendanceRate, 2),
                ]);
            }
        }
    }
}
