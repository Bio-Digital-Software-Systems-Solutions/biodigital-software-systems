<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveTrainingClassRequest;
use App\Http\Requests\StoreTrainingClassScheduleRequest;
use App\Http\Requests\UpdateTrainingClassScheduleRequest;
use App\Models\Attendance;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\TrainingClassSchedule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainingClassController extends Controller
{
    /**
     * Display the training class dashboard
     */
    public function index(Request $request): Response
    {
        $query = TrainingClass::with([
            'training.students' => function ($query) {
                $query->where('status', 'approved');
            },
            'teacher',
            'attendances',
            'schedules',
        ]);

        // Add filters
        if ($request->filled('training_id')) {
            $query->where('training_id', $request->training_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'archived') {
                $query->archived();
            } elseif ($request->status === 'upcoming') {
                $query->active()->where('date', '>=', now()->toDateString());
            } elseif ($request->status === 'past') {
                $query->active()->where('date', '<', now()->toDateString());
            } else {
                $query->active();
            }
        } else {
            $query->active();
        }

        $classes = $query->orderBy('date', 'desc')
            ->paginate(12)
            ->appends($request->query());

        $classesData = $classes->getCollection()->map(fn ($class) => $this->formatClassData($class));

        $trainings = Training::select('id', 'title')->get();
        $teachers = User::whereHas('teacher')->select('id', 'first_name', 'last_name')->get();

        return Inertia::render('TrainingClass/Dashboard', [
            'classes' => [
                'data' => $classesData,
                'links' => $classes->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $classes->currentPage(),
                    'last_page' => $classes->lastPage(),
                    'per_page' => $classes->perPage(),
                    'total' => $classes->total(),
                    'from' => $classes->firstItem(),
                    'to' => $classes->lastItem(),
                ],
            ],
            'trainings' => $trainings,
            'teachers' => $teachers,
            'filters' => $request->only(['training_id', 'teacher_id', 'status']),
        ]);
    }

    /**
     * Show a single training class
     */
    public function show(TrainingClass $trainingClass): Response
    {
        $trainingClass->load([
            'training.students' => function ($query) {
                $query->where('status', 'approved');
            },
            'teacher',
            'attendances.student',
            'materials.uploadedBy',
            'quizzes',
        ]);

        $students = $trainingClass->training->students
            ->map(function ($student) use ($trainingClass) {
                $attendance = $trainingClass->attendances->firstWhere('student_id', $student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->first_name.' '.$student->last_name,
                    'email' => $student->email,
                    'grade' => $student->pivot->grade,
                    'progress' => $student->pivot->progress,
                    'attendance_rate' => $student->pivot->attendance_rate,
                    'attendance_status' => $attendance ? $attendance->status : null,
                    'attendance_reason' => $attendance ? $attendance->reason : null,
                ];
            });

        $pendingEnrollments = DB::table('training_enrollments')
            ->join('users', 'training_enrollments.user_id', '=', 'users.id')
            ->where('training_enrollments.training_id', $trainingClass->training_id)
            ->where('training_enrollments.status', 'pending')
            ->select(
                'training_enrollments.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                'users.email as user_email',
                'training_enrollments.motivation',
                'training_enrollments.created_at',
            )
            ->orderBy('training_enrollments.created_at', 'desc')
            ->get();

        $materials = $trainingClass->materials->map(fn ($material) => [
            'id' => $material->id,
            'uuid' => $material->uuid,
            'title' => $material->title,
            'type' => $material->type,
            'file_url' => $material->file_url,
            'duration' => $material->duration,
            'description' => $material->description,
            'is_active' => $material->is_active,
            'uploaded_by_name' => $material->uploadedBy?->first_name.' '.$material->uploadedBy?->last_name,
        ]);

        $quizzes = $trainingClass->quizzes->map(fn ($quiz) => [
            'id' => $quiz->id,
            'uuid' => $quiz->uuid,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'duration_minutes' => $quiz->duration_minutes,
            'max_score' => $quiz->max_score,
            'passing_score' => $quiz->passing_score,
            'status' => $quiz->status,
            'available_from' => $quiz->pivot->available_from,
            'available_until' => $quiz->pivot->available_until,
        ]);

        return Inertia::render('TrainingClass/Show', [
            'class' => $this->formatClassData($trainingClass),
            'students' => $students,
            'pendingEnrollments' => $pendingEnrollments,
            'materials' => $materials,
            'quizzes' => $quizzes,
        ]);
    }

    /**
     * Store a new training class
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'teacher_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'room' => 'nullable|string|max:255',
            'max_students' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'schedules' => 'required|array|min:1',
            'schedules.*.day_of_week' => 'required|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required',
            'schedules.*.room' => 'nullable|string|max:255',
        ]);

        // Use the first schedule's time as default for the class record
        $firstSchedule = $validated['schedules'][0];

        $class = TrainingClass::create([
            'training_id' => $validated['training_id'],
            'teacher_id' => $validated['teacher_id'],
            'name' => $validated['name'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'] ?? $firstSchedule['start_time'],
            'end_time' => $validated['end_time'] ?? $firstSchedule['end_time'],
            'room' => $validated['room'],
            'max_students' => $validated['max_students'],
            'notes' => $validated['notes'],
        ]);

        // Create schedules if provided
        if (! empty($validated['schedules'])) {
            foreach ($validated['schedules'] as $scheduleData) {
                TrainingClassSchedule::create([
                    'training_class_id' => $class->id,
                    'day_of_week' => $scheduleData['day_of_week'],
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'room' => $scheduleData['room'] ?? null,
                    'is_active' => true,
                ]);
            }
        }

        $class->load([
            'training.students' => function ($query) {
                $query->where('status', 'approved');
            },
            'teacher',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Classe créée avec succès',
            'class' => $this->formatClassData($class),
        ]);
    }

    /**
     * Update an existing training class
     */
    public function update(Request $request, TrainingClass $trainingClass)
    {
        if ($trainingClass->isArchived()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier une classe archivée.',
            ], 422);
        }

        $validated = $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'teacher_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'room' => 'nullable|string|max:255',
            'max_students' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'schedules' => 'nullable|array',
            'schedules.*.uuid' => 'nullable|exists:training_class_schedules,uuid',
            'schedules.*.day_of_week' => 'required_with:schedules|string',
            'schedules.*.start_time' => 'required_with:schedules',
            'schedules.*.end_time' => 'required_with:schedules',
            'schedules.*.room' => 'nullable|string|max:255',
        ]);

        // Handle schedules if provided
        $firstScheduleStartTime = null;
        $firstScheduleEndTime = null;

        if (isset($validated['schedules']) && count($validated['schedules']) > 0) {
            // Delete existing schedules not in the new list
            $newScheduleUuids = array_filter(array_column($validated['schedules'], 'uuid'));
            if (! empty($newScheduleUuids)) {
                $trainingClass->schedules()->whereNotIn('uuid', $newScheduleUuids)->delete();
            } else {
                $trainingClass->schedules()->delete();
            }

            // Update or create schedules
            foreach ($validated['schedules'] as $scheduleData) {
                if (isset($scheduleData['uuid'])) {
                    // Update existing schedule
                    $schedule = TrainingClassSchedule::where('uuid', $scheduleData['uuid'])->first();
                    if ($schedule && $schedule->training_class_id === $trainingClass->id) {
                        $schedule->update([
                            'day_of_week' => $scheduleData['day_of_week'],
                            'start_time' => $scheduleData['start_time'],
                            'end_time' => $scheduleData['end_time'],
                            'room' => $scheduleData['room'] ?? null,
                        ]);
                    }
                } else {
                    // Create new schedule
                    $trainingClass->schedules()->create([
                        'day_of_week' => $scheduleData['day_of_week'],
                        'start_time' => $scheduleData['start_time'],
                        'end_time' => $scheduleData['end_time'],
                        'room' => $scheduleData['room'] ?? null,
                    ]);
                }

                // Use first schedule times for the class start/end times
                if ($firstScheduleStartTime === null) {
                    $firstScheduleStartTime = $scheduleData['start_time'];
                    $firstScheduleEndTime = $scheduleData['end_time'];
                }
            }
        }

        // Update basic class info
        $trainingClass->update([
            'training_id' => $validated['training_id'],
            'teacher_id' => $validated['teacher_id'],
            'name' => $validated['name'],
            'date' => $validated['date'],
            'start_time' => $firstScheduleStartTime ?? $validated['start_time'] ?? $trainingClass->start_time,
            'end_time' => $firstScheduleEndTime ?? $validated['end_time'] ?? $trainingClass->end_time,
            'room' => $validated['room'] ?? null,
            'max_students' => $validated['max_students'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $trainingClass->load([
            'training.students' => function ($query) {
                $query->where('status', 'approved');
            },
            'teacher',
            'schedules',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Classe mise à jour avec succès',
            'class' => $this->formatClassData($trainingClass),
        ]);
    }

    /**
     * Delete a training class
     */
    public function destroy(TrainingClass $trainingClass)
    {
        if ($trainingClass->isArchived()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une classe archivée. Désarchivez-la d\'abord.',
            ], 422);
        }

        $trainingClass->delete();

        return response()->json([
            'success' => true,
            'message' => 'Classe supprimée avec succès',
        ]);
    }

    /**
     * Get students for a specific class
     */
    public function students(TrainingClass $trainingClass)
    {
        $training = $trainingClass->training;
        $students = $training->students()
            ->where('status', 'approved')
            ->get()
            ->map(function ($student) use ($trainingClass) {
                $attendance = Attendance::where('training_class_id', $trainingClass->id)
                    ->where('student_id', $student->id)
                    ->first();

                return [
                    'id' => $student->id,
                    'name' => $student->first_name.' '.$student->last_name,
                    'email' => $student->email,
                    'grade' => $student->pivot->grade,
                    'attendance_rate' => $student->pivot->attendance_rate,
                    'attendance_status' => $attendance ? $attendance->status : null,
                    'attendance_reason' => $attendance ? $attendance->reason : null,
                ];
            });

        return response()->json($students);
    }

    /**
     * Mark attendance for a class
     */
    public function markAttendance(Request $request, TrainingClass $trainingClass)
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,excused',
            'attendances.*.reason' => 'nullable|string',
        ]);

        foreach ($validated['attendances'] as $attendanceData) {
            Attendance::updateOrCreate(
                [
                    'training_class_id' => $trainingClass->id,
                    'student_id' => $attendanceData['student_id'],
                ],
                [
                    'status' => $attendanceData['status'],
                    'reason' => $attendanceData['reason'] ?? null,
                ]
            );
        }

        // Update attendance rates for students
        $this->updateAttendanceRates($trainingClass);

        return response()->json([
            'success' => true,
            'message' => 'Présences enregistrées avec succès',
        ]);
    }

    /**
     * Get schedules grouped by training
     */
    public function schedules()
    {
        $trainings = Training::with(['classes' => function ($query) {
            $query->where('date', '>=', now()->toDateString())
                ->with('teacher')
                ->orderBy('date')
                ->orderBy('start_time');
        }])->get();

        $schedules = $trainings->map(function ($training) {
            return [
                'training_id' => $training->id,
                'training_name' => $training->title,
                'classes' => $training->classes->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'uuid' => $class->uuid,
                        'name' => $class->name,
                        'date' => $class->date,
                        'day' => \Carbon\Carbon::parse($class->date)->locale('fr')->isoFormat('dddd'),
                        'start_time' => $class->start_time,
                        'end_time' => $class->end_time,
                        'room' => $class->room,
                        'teacher' => $class->teacher ? $class->teacher->first_name.' '.$class->teacher->last_name : 'N/A',
                    ];
                }),
            ];
        });

        return response()->json($schedules);
    }

    /**
     * Get schedules for a specific training class
     */
    public function getClassSchedules(TrainingClass $trainingClass)
    {
        $schedules = $trainingClass->schedules()->get()->map(function ($schedule) {
            return [
                'uuid' => $schedule->uuid,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'room' => $schedule->room,
            ];
        });

        return response()->json($schedules);
    }

    /**
     * Get week schedule for a training class
     */
    public function weekSchedule(TrainingClass $trainingClass)
    {
        $allDays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

        $schedules = $trainingClass->schedules()
            ->active()
            ->get()
            ->keyBy('day_of_week');

        $weekSchedule = collect($allDays)->map(function ($day) use ($schedules) {
            $schedule = $schedules->get($day);

            return [
                'uuid' => $schedule ? $schedule->uuid : null,
                'day_name' => $day,
                'start_time' => $schedule ? $schedule->start_time : null,
                'end_time' => $schedule ? $schedule->end_time : null,
                'room' => $schedule ? $schedule->room : null,
                'is_active' => $schedule ? $schedule->is_active : false,
                'has_schedule' => $schedule !== null,
            ];
        });

        return response()->json($weekSchedule);
    }

    /**
     * Store a new training class schedule
     */
    public function storeSchedule(StoreTrainingClassScheduleRequest $request)
    {
        $schedule = TrainingClassSchedule::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Horaire créé avec succès',
            'schedule' => $schedule,
        ]);
    }

    /**
     * Update a training class schedule
     */
    public function updateSchedule(UpdateTrainingClassScheduleRequest $request, TrainingClassSchedule $schedule)
    {
        $schedule->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Horaire mis à jour avec succès',
            'schedule' => $schedule->fresh(),
        ]);
    }

    /**
     * Delete a training class schedule
     */
    public function destroySchedule(TrainingClassSchedule $schedule)
    {
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Horaire supprimé avec succès',
        ]);
    }

    /**
     * Get attendance for a specific schedule and day
     */
    public function scheduleAttendance(TrainingClassSchedule $schedule)
    {
        $trainingClass = $schedule->trainingClass;
        $training = $trainingClass->training;

        $students = $training->students()
            ->where('status', 'approved')
            ->get()
            ->map(function ($student) use ($schedule) {
                $attendance = $schedule->attendances()
                    ->where('student_id', $student->id)
                    ->first();

                return [
                    'id' => $student->id,
                    'name' => $student->first_name.' '.$student->last_name,
                    'email' => $student->email,
                    'attendance_rate' => $student->pivot->attendance_rate ?? 0,
                    'attendance_status' => $attendance ? $attendance->status : null,
                    'attendance_reason' => $attendance ? $attendance->reason : null,
                ];
            });

        return response()->json($students);
    }

    /**
     * Mark attendance for a specific schedule
     */
    public function markScheduleAttendance(Request $request, TrainingClassSchedule $schedule)
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,excused',
            'attendances.*.reason' => 'nullable|string',
        ]);

        foreach ($validated['attendances'] as $attendanceData) {
            Attendance::updateOrCreate(
                [
                    'training_class_id' => $schedule->training_class_id,
                    'training_class_schedule_id' => $schedule->id,
                    'student_id' => $attendanceData['student_id'],
                ],
                [
                    'status' => $attendanceData['status'],
                    'notes' => $attendanceData['reason'] ?? null,
                ]
            );
        }

        // Update attendance rates
        $this->updateAttendanceRates($schedule->trainingClass);

        return response()->json([
            'success' => true,
            'message' => 'Présences enregistrées avec succès',
        ]);
    }

    /**
     * Get students for a specific training
     */
    public function trainingStudents($trainingId)
    {
        $training = Training::findOrFail($trainingId);

        $students = $training->students()
            ->where('status', 'approved')
            ->get()
            ->map(function ($student) use ($trainingId) {
                return [
                    'id' => $student->id,
                    'name' => $student->first_name.' '.$student->last_name,
                    'email' => $student->email,
                    'grade' => $student->pivot->grade,
                    'progress' => $student->pivot->progress,
                    'attendance_rate' => $student->pivot->attendance_rate ?? 0,
                    'training_id' => $trainingId,
                ];
            });

        return response()->json($students);
    }

    /**
     * Get attendance history for a specific student in a training
     */
    public function studentAttendanceHistory($studentId, $trainingId)
    {
        $history = Attendance::whereHas('trainingClass', function ($query) use ($trainingId) {
            $query->where('training_id', $trainingId);
        })
            ->where('student_id', $studentId)
            ->with('trainingClass')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($attendance) {
                return [
                    'class_id' => $attendance->training_class_id,
                    'class_date' => $attendance->trainingClass->date,
                    'start_time' => $attendance->trainingClass->start_time,
                    'end_time' => $attendance->trainingClass->end_time,
                    'room' => $attendance->trainingClass->room,
                    'status' => $attendance->status,
                    'reason' => $attendance->reason,
                ];
            });

        return response()->json($history);
    }

    /**
     * Get statistics for the dashboard
     */
    public function statistics()
    {
        $totalClasses = TrainingClass::count();
        $upcomingClasses = TrainingClass::where('date', '>=', now()->toDateString())->count();

        $allStudents = User::whereHas('trainings', function ($query) {
            $query->where('status', 'approved');
        })->get();

        $totalStudents = $allStudents->count();

        $averageGrade = $totalStudents > 0
            ? $allStudents->flatMap(function ($student) {
                return $student->trainings->pluck('pivot.grade')->filter();
            })->avg()
            : 0;

        $totalAttendances = Attendance::count();
        $presentCount = Attendance::where('status', 'present')->count();
        $attendanceRate = $totalAttendances > 0 ? ($presentCount / $totalAttendances) * 100 : 0;

        // Get top students
        $topStudents = User::whereHas('trainings', function ($query) {
            $query->where('status', 'approved');
        })->with('trainings')->get()->map(function ($student) {
            $grades = $student->trainings->pluck('pivot.grade')->filter();

            return [
                'id' => $student->id,
                'name' => $student->first_name.' '.$student->last_name,
                'email' => $student->email,
                'average_grade' => $grades->count() > 0 ? $grades->avg() : 0,
            ];
        })->sortByDesc('average_grade')->take(5)->values();

        // Get grade distribution by training
        $gradeDistribution = Training::with(['students' => function ($query) {
            $query->where('status', 'approved');
        }])->get()->map(function ($training) {
            $students = $training->students;
            $grades = $students->pluck('pivot.grade')->filter();

            return [
                'training_id' => $training->id,
                'training_name' => $training->title,
                'students_count' => $students->count(),
                'average_grade' => $grades->count() > 0 ? $grades->avg() : 0,
            ];
        });

        return response()->json([
            'total_classes' => $totalClasses,
            'upcoming_classes' => $upcomingClasses,
            'total_students' => $totalStudents,
            'average_grade' => round($averageGrade, 1),
            'attendance_rate' => round($attendanceRate, 1),
            'top_students' => $topStudents,
            'grade_distribution' => $gradeDistribution,
        ]);
    }

    /**
     * Archive a training class.
     */
    public function archive(ArchiveTrainingClassRequest $request, TrainingClass $trainingClass): JsonResponse
    {
        if ($trainingClass->isArchived()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette classe est déjà archivée.',
            ], 422);
        }

        $months = $request->validated('access_duration_months') ?? 6;
        $trainingClass->archive($months);

        return response()->json([
            'success' => true,
            'message' => 'Classe archivée avec succès.',
            'class' => $this->formatClassData($trainingClass->fresh()),
        ]);
    }

    /**
     * Restore a training class from archive.
     */
    public function unarchive(TrainingClass $trainingClass): JsonResponse
    {
        if (! $trainingClass->isArchived()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette classe n\'est pas archivée.',
            ], 422);
        }

        $trainingClass->unarchive();

        return response()->json([
            'success' => true,
            'message' => 'Classe restaurée avec succès.',
            'class' => $this->formatClassData($trainingClass->fresh()),
        ]);
    }

    /**
     * Duplicate a training class with its content.
     */
    public function duplicate(TrainingClass $trainingClass): JsonResponse
    {
        $validated = request()->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $trainingClass->load(['schedules', 'materials', 'allQuizzes']);

        $copy = $trainingClass->duplicate($validated['name'] ?? null);

        $copy->load([
            'training.students' => function ($query) {
                $query->where('status', 'approved');
            },
            'teacher',
            'schedules',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Classe dupliquée avec succès.',
            'class' => $this->formatClassData($copy),
        ]);
    }

    /**
     * Format a TrainingClass model into an array for API/Inertia responses.
     *
     * @return array<string, mixed>
     */
    private function formatClassData(TrainingClass $class): array
    {
        $schedules = $class->relationLoaded('schedules')
            ? $class->schedules->map(fn ($schedule) => [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'room' => $schedule->room,
            ])
            : [];

        if ($class->isArchived()) {
            $status = 'Archivée';
        } else {
            $status = $class->date >= now()->toDateString() ? 'À venir' : 'Passée';
        }

        $studentsCount = 0;
        if ($class->relationLoaded('training') && $class->training->relationLoaded('students')) {
            $studentsCount = $class->training->students->count();
        }

        return [
            'id' => $class->id,
            'uuid' => $class->uuid,
            'training_id' => $class->training_id,
            'training_name' => $class->training->title,
            'teacher_id' => $class->teacher_id,
            'teacher_name' => $class->teacher ? $class->teacher->first_name.' '.$class->teacher->last_name : 'N/A',
            'name' => $class->name,
            'date' => $class->date,
            'start_time' => $class->start_time,
            'end_time' => $class->end_time,
            'room' => $class->room,
            'max_students' => $class->max_students,
            'notes' => $class->notes,
            'students_count' => $studentsCount,
            'status' => $status,
            'archived_at' => $class->archived_at?->toISOString(),
            'archive_access_until' => $class->archive_access_until?->toISOString(),
            'schedules' => $schedules,
        ];
    }

    /**
     * Update attendance rates for all students in a class
     */
    private function updateAttendanceRates(TrainingClass $class)
    {
        $training = $class->training;
        $students = $training->students()->where('status', 'approved')->get();

        foreach ($students as $student) {
            $totalClasses = TrainingClass::where('training_id', $training->id)
                ->where('date', '<=', now()->toDateString())
                ->count();

            $attendedClasses = Attendance::whereHas('trainingClass', function ($query) use ($training) {
                $query->where('training_id', $training->id);
            })
                ->where('student_id', $student->id)
                ->where('status', 'present')
                ->count();

            $attendanceRate = $totalClasses > 0 ? ($attendedClasses / $totalClasses) * 100 : 0;

            $training->students()->updateExistingPivot($student->id, [
                'attendance_rate' => $attendanceRate,
            ]);
        }
    }
}
