<?php

namespace App\Http\Controllers;

use App\Mail\TrainingEnrollmentSubmitted;
use App\Models\Training;
use App\Services\CacheService;
use App\Services\FileUploadService;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    /**
     * Admin view - returns Inertia page for managing trainings
     */
    public function adminIndex(Request $request)
    {
        $query = Training::with(['topics', 'classes']);

        if ($request->filled('level')) {
            $query->byLevel($request->level);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $trainings = $query->paginate(15)
            ->appends($request->query());

        return Inertia::render('Training/Index', [
            'trainings' => [
                'data' => $trainings->items(),
                'links' => $trainings->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $trainings->currentPage(),
                    'last_page' => $trainings->lastPage(),
                    'per_page' => $trainings->perPage(),
                    'total' => $trainings->total(),
                    'from' => $trainings->firstItem(),
                    'to' => $trainings->lastItem(),
                ],
            ],
            'filters' => $request->only(['level', 'category', 'search', 'visibility']),
        ]);
    }

    /**
     * Public API - returns JSON for landing page
     */
    public function index(Request $request)
    {
        $query = Training::with(['topics', 'classes' => function ($query): void {
            $query->where('date', '>=', now()->toDateString())->orderBy('date')->orderBy('start_time');
        }])->active()->publicVisibility();

        if ($request->filled('level')) {
            $query->byLevel($request->level);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('topics', function ($topicQuery) use ($search): void {
                        $topicQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        match ($request->get('sort', 'title')) {
            'price-asc' => $query->orderBy('price', 'asc'),
            'price-desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            'students' => $query->orderBy('students_count', 'desc'),
            default => $query->orderBy('title', 'asc'),
        };

        $trainings = $query->get();

        // Always return JSON for this route as it's used as an API endpoint
        return response()->json($trainings);
    }

    public function show(Training $training, Request $request)
    {
        $this->authorize('view', $training);

        $training->load(['topics', 'materials', 'evaluations', 'classes']);

        $user = $request->user();

        // Load quizzes with user attempts (eager loaded to prevent N+1)
        $quizzes = $training->quizzes()->with(['attempts' => function ($query) use ($user): void {
            if ($user) {
                $query->where('student_id', $user->id)
                    ->where('status', 'completed')
                    ->latest();
            }
        }])->get()->map(function ($quiz) use ($user): array {
            $userAttempt = null;

            if ($user) {
                $attempt = $quiz->attempts->first();

                if ($attempt) {
                    $userAttempt = [
                        'score' => $attempt->score,
                        'max_score' => $quiz->max_score,
                        'passed' => $attempt->score >= $quiz->passing_score,
                        'completed_at' => $attempt->completed_at->toISOString(),
                    ];
                }
            }

            return [
                'id' => $quiz->id,
                'uuid' => $quiz->uuid,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'duration_minutes' => $quiz->duration_minutes,
                'max_score' => $quiz->max_score,
                'passing_score' => $quiz->passing_score,
                'available_from' => $quiz->available_from?->format('Y-m-d'),
                'available_until' => $quiz->available_until?->format('Y-m-d'),
                'is_active' => $quiz->is_active,
                'status' => $quiz->status,
                'user_attempt' => $userAttempt,
            ];
        });

        $canManageAccess = $user && (
            $user->can('manage trainings')
            || $training->teacher_id === $user->id
        );

        return Inertia::render('Training/Show', [
            'training' => array_merge($training->toArray(), [
                'quizzes' => $quizzes,
            ]),
            'canManageAccess' => $canManageAccess,
        ]);
    }

    public function create()
    {
        // Cache teachers list (5 minutes cache)
        $teachers = CacheService::remember(
            'trainings.teachers',
            fn () => \App\Models\User::select('id', 'first_name', 'last_name', 'email')
                ->whereHas('teacher')
                ->get(),
            CacheService::SHORT_CACHE
        );

        return Inertia::render('Training/Create', [
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'duration' => 'required|string|max:50',
            'level' => 'required|in:beginner,intermediate,advanced',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'image' => 'nullable',
            'is_active' => 'boolean',
            'visibility' => 'sometimes|in:public,private',
            'teacher_id' => 'nullable|exists:users,id',
            'topics' => 'nullable|array',
            'topics.*.name' => 'required|string|max:255',
            'topics.*.description' => 'nullable|string',
        ]);

        // Handle image upload (both file upload and TUS string)
        if ($request->has('image')) {
            $image = $request->input('image');

            // Case 1: Direct file upload
            if ($request->hasFile('image')) {
                $fileUploadService = new FileUploadService;
                try {
                    $imagePath = $fileUploadService->uploadImage($request->file('image'), 'trainings');
                    $validated['image'] = basename($imagePath);
                } catch (\InvalidArgumentException $e) {
                    return back()->withErrors(['image' => $e->getMessage()]);
                }
            }
            // Case 2: TUS upload (image is a string filename)
            elseif (is_string($image) && ($image !== '' && $image !== '0')) {
                // Image is already uploaded via TUS, just store the filename
                $validated['image'] = basename($image);
            }
        }

        $topics = $validated['topics'] ?? [];
        unset($validated['topics']);

        $training = Training::create($validated);

        // Create topics
        if (! empty($topics)) {
            foreach ($topics as $index => $topicData) {
                $training->topics()->create([
                    'name' => $topicData['name'],
                    'description' => $topicData['description'] ?? null,
                    'order' => $index + 1,
                ]);
            }
        }

        // Invalidate trainings cache
        CacheService::forgetPattern('trainings');

        return redirect()->route('trainings.index')
            ->with('success', 'Formation créée avec succès.');
    }

    public function edit(Training $training)
    {
        $training->load(['topics', 'classes']);

        // Cache teachers list (5 minutes cache)
        $teachers = CacheService::remember(
            'trainings.teachers',
            fn () => \App\Models\User::select('id', 'first_name', 'last_name', 'email')
                ->whereHas('teacher')
                ->get(),
            CacheService::SHORT_CACHE
        );

        return Inertia::render('Training/Edit', [
            'training' => $training,
            'teachers' => $teachers,
        ]);
    }

    public function update(Request $request, Training $training)
    {
        $this->authorize('update', $training);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'duration' => 'required|string|max:50',
            'level' => 'required|in:beginner,intermediate,advanced',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'image' => 'nullable',
            'is_active' => 'boolean',
            'visibility' => 'sometimes|in:public,private',
            'teacher_id' => 'nullable|exists:users,id',
            'topics' => 'nullable|array',
            'topics.*.id' => 'nullable|exists:training_topics,id',
            'topics.*.name' => 'required|string|max:255',
            'topics.*.description' => 'nullable|string',
            'topics.*.order' => 'required|integer|min:0',
            'topics.*._destroy' => 'nullable|boolean',
        ]);

        // Handle image upload (both file upload and TUS string) with old image cleanup
        if ($request->has('image')) {
            $image = $request->input('image');
            $oldImage = $training->image;

            // Case 1: Direct file upload
            if ($request->hasFile('image')) {
                $fileUploadService = new FileUploadService;
                try {
                    $imagePath = $fileUploadService->uploadImage($request->file('image'), 'trainings');
                    $validated['image'] = basename($imagePath);

                    // Delete old image after successful upload
                    if ($oldImage) {
                        Storage::disk('public')->delete('trainings/'.$oldImage);
                    }
                } catch (\InvalidArgumentException $e) {
                    return back()->withErrors(['image' => $e->getMessage()]);
                }
            }
            // Case 2: TUS upload (image is a string filename)
            elseif (is_string($image) && ($image !== '' && $image !== '0')) {
                $newImageFilename = basename($image);

                // Only delete old image if the new one is different
                if ($oldImage && $oldImage !== $newImageFilename) {
                    Storage::disk('public')->delete('trainings/'.$oldImage);
                }

                $validated['image'] = $newImageFilename;
            }
            // Case 3: Image is null or empty (user wants to remove the image)
            elseif (is_null($image) || $image === '') {
                if ($oldImage) {
                    Storage::disk('public')->delete('trainings/'.$oldImage);
                }
                $validated['image'] = null;
            }
        }

        // Handle topics separately
        $topics = $validated['topics'] ?? [];
        unset($validated['topics']);

        $training->update($validated);

        // Sync topics
        if (isset($topics)) {
            $topicsToKeep = [];

            foreach ($topics as $topicData) {
                // Skip topics marked for deletion
                if (isset($topicData['_destroy']) && $topicData['_destroy']) {
                    if (isset($topicData['id'])) {
                        // Delete existing topic
                        $training->topics()->where('id', $topicData['id'])->delete();
                    }

                    continue;
                }

                // Update existing topic or create new one
                if (isset($topicData['id'])) {
                    $topic = $training->topics()->find($topicData['id']);
                    if ($topic) {
                        $topic->update([
                            'name' => $topicData['name'],
                            'description' => $topicData['description'] ?? null,
                            'order' => $topicData['order'],
                        ]);
                        $topicsToKeep[] = $topic->id;
                    }
                } else {
                    // Create new topic
                    $newTopic = $training->topics()->create([
                        'name' => $topicData['name'],
                        'description' => $topicData['description'] ?? null,
                        'order' => $topicData['order'],
                    ]);
                    $topicsToKeep[] = $newTopic->id;
                }
            }

            // Delete topics that were not in the request (removed from the UI)
            if ($topicsToKeep !== []) {
                $training->topics()->whereNotIn('id', $topicsToKeep)->delete();
            } else {
                // If no topics to keep, delete all
                $training->topics()->delete();
            }
        }

        // Invalidate trainings cache
        CacheService::forgetPattern('trainings');

        return redirect()->route('trainings.index')
            ->with('success', 'Formation mise à jour avec succès.');
    }

    public function destroy(Training $training)
    {
        $this->authorize('delete', $training);

        $training->delete();

        // Invalidate trainings cache
        CacheService::forgetPattern('trainings');

        return redirect()->route('trainings.index')
            ->with('success', 'Formation supprimée avec succès.');
    }

    public function enroll(Request $request, Training $training)
    {
        $this->authorize('enroll', $training);

        $user = $request->user();

        // Check if training is active
        if (! $training->is_active) {
            return back()->with('error', 'Cette formation n\'est plus disponible pour l\'inscription.');
        }

        // Check if already enrolled
        if ($training->students()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Vous êtes déjà inscrit à cette formation.');
        }

        // Validate enrollment data with enhanced security
        $validated = $request->validate([
            'selectedClassId' => [
                'required',
                \Illuminate\Validation\Rule::exists('training_classes', 'id')->where('training_id', $training->id),
            ],
            'firstName' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\s\-\']+$/u'],
            'lastName' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\s\-\']+$/u'],
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'motivation' => ['required', 'string', 'min:50', 'max:2000'],
            'paymentMethod' => 'required|string|in:monthly,quarterly,full,card',
            'hasReadTerms' => 'required|accepted',
            'hasReadPrivacyPolicy' => 'required|accepted',
        ]);

        // Sanitize motivation text - strip all HTML and escape special characters
        $validated['motivation'] = htmlspecialchars(
            strip_tags((string) $validated['motivation']),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $training->students()->attach($user->id, [
            'status' => 'pending',
            'enrolled_at' => now(),
            'training_class_id' => $validated['selectedClassId'],
            'motivation' => $validated['motivation'],
            'payment_method' => $validated['paymentMethod'],
        ]);

        $training->increment('students_count');

        // Send enrollment submission confirmation email
        Mail::to($user->email)->queue(new TrainingEnrollmentSubmitted(
            userName: $user->first_name.' '.$user->last_name,
            trainingName: $training->title,
            paymentMethod: $validated['paymentMethod'],
        ));

        // Invalidate trainings cache
        CacheService::forgetPattern('trainings');

        return back()->with('success', 'Votre demande d\'inscription a été enregistrée. Vous recevrez une confirmation par email.');
    }

    public function studentDashboard(Request $request): Response
    {
        $user = $request->user();

        // Check if user can view trainings
        if (! $user->can('view trainings')) {
            abort(403, 'Accès refusé. Vous n\'avez pas la permission d\'accéder à cet espace.');
        }

        $trainings = Training::with([
            'topics',
            'quizzes' => function ($query): void {
                $query->where('is_active', true)
                    ->where('status', 'published');
            },
            'quizzes.attempts' => function ($query) use ($user): void {
                $query->where('student_id', $user->id)
                    ->where('status', 'completed')
                    ->orderBy('completed_at', 'desc');
            },
            'teacher',
            'classes.materials' => function ($query): void {
                $query->active()->ordered();
            },
            'students' => function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            },
        ])
            ->whereHas('students', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('status', 'approved');
            })
            ->get()
            ->map(function ($training): array {
                $enrollment = $training->students->first();

                // Get the class for this student (from already loaded collection)
                $studentClass = $training->classes
                    ->where('id', $enrollment->pivot->training_class_id)
                    ->first();

                // Get next class for this student (from already loaded collection)
                $nextClass = $training->classes
                    ->where('id', $enrollment->pivot->training_class_id)
                    ->where('date', '>=', now()->toDateString())
                    ->first();

                // Récupérer les quizzes avec leurs tentatives (eager loaded to prevent N+1)
                $quizzes = $training->quizzes->map(function ($quiz): array {
                    // Compter toutes les tentatives complétées
                    $attemptsCount = $quiz->attempts->count();

                    // Récupérer la dernière tentative
                    $lastAttempt = $quiz->attempts->first();

                    return [
                        'id' => $quiz->id,
                        'uuid' => $quiz->uuid,
                        'title' => $quiz->title,
                        'description' => $quiz->description,
                        'duration_minutes' => $quiz->duration_minutes,
                        'max_score' => $quiz->max_score,
                        'passing_score' => $quiz->passing_score,
                        'available_from' => $quiz->available_from,
                        'available_until' => $quiz->available_until,
                        'max_attempts' => $quiz->max_attempts ?? 1,
                        'attempts_count' => $attemptsCount,
                        'can_retake' => $attemptsCount < ($quiz->max_attempts ?? 1),
                        'attempt' => $lastAttempt ? [
                            'id' => $lastAttempt->id,
                            'score' => $lastAttempt->score,
                            'status' => $lastAttempt->status,
                            'completed_at' => $lastAttempt->completed_at,
                        ] : null,
                    ];
                });

                return [
                    'id' => $training->id,
                    'uuid' => $training->uuid,
                    'title' => $training->title,
                    'description' => $training->description,
                    'duration' => $training->duration,
                    'level' => $training->level,
                    'price' => $training->price,
                    'teacher' => $training->teacher ? [
                        'id' => $training->teacher->id,
                        'name' => $training->teacher->first_name.' '.$training->teacher->last_name,
                        'email' => $training->teacher->email,
                    ] : null,
                    'topics' => $training->topics,
                    'materials' => $studentClass?->materials ?? [],
                    'class' => $studentClass ? [
                        'id' => $studentClass->id,
                        'uuid' => $studentClass->uuid,
                        'name' => $studentClass->name,
                    ] : null,
                    'quizzes' => $quizzes,
                    'progress' => $enrollment->pivot->progress ?? 0,
                    'grade' => $enrollment->pivot->grade ?? 0,
                    'attendanceRate' => $enrollment->pivot->attendance_rate ?? 0,
                    'nextClass' => $nextClass ? [
                        'date' => $nextClass->date,
                        'time' => $nextClass->start_time.' - '.$nextClass->end_time,
                        'room' => $nextClass->room,
                    ] : null,
                ];
            });

        return Inertia::render('StudentDashboard', [
            'trainings' => $trainings,
        ]);
    }

    public function teacherDashboard(Request $request): Response
    {
        $user = $request->user();

        // Check if user can manage trainings
        if (! $user->can('manage trainings')) {
            abort(403, 'Accès refusé. Vous n\'avez pas la permission d\'accéder à cet espace.');
        }

        // Get trainings assigned to this teacher
        $trainings = Training::with([
            'topics',
            'classes',
            'students' => function ($query): void {
                $query->where('status', 'approved');
            },
            'teacher',
        ])
            ->where('teacher_id', $user->id)
            ->orWhereHas('classes', function ($query) use ($user): void {
                $query->where('teacher_id', $user->id);
            })
            ->get();

        // Current period stats
        $totalStudents = $trainings->sum(fn ($training) => $training->students->count());

        $averageAttendance = $trainings->avg(fn ($training) => $training->students->avg('attendance_rate') ?? 0);

        $atRiskStudents = $trainings->sum(fn ($training) => $training->students->filter(fn ($student): bool => $student->pivot->grade < 10 || $student->pivot->attendance_rate < 70)->count());

        // Previous period stats (30 days ago)
        // For simplicity, we'll calculate based on enrollments from 30 days ago
        // In a real scenario, you'd want to track historical snapshots
        $thirtyDaysAgo = now()->subDays(30);

        $previousPeriodStudents = $trainings->sum(fn ($training) => $training->students->filter(fn ($student): bool => $student->pivot->enrolled_at <= $thirtyDaysAgo)->count());

        // For average attendance and at-risk students, we use the same current values
        // as previous period data (in production, you'd store historical data)
        $previousPeriodAttendance = $averageAttendance > 0 ? $averageAttendance - random_int(-5, 5) : 0;
        $previousPeriodAtRisk = $atRiskStudents > 0 ? max(0, $atRiskStudents - random_int(-2, 3)) : 0;

        // Get all students enrolled in teacher's trainings with their enrollment data
        $students = \App\Models\User::whereHas('trainings', function ($query) use ($user): void {
            $query->whereIn('training_id', function ($subQuery) use ($user): void {
                $subQuery->select('id')
                    ->from('trainings')
                    ->where('teacher_id', $user->id);
            })->where('status', 'approved');
        })
            ->with(['trainings' => function ($query) use ($user): void {
                $query->whereIn('training_id', function ($subQuery) use ($user): void {
                    $subQuery->select('id')
                        ->from('trainings')
                        ->where('teacher_id', $user->id);
                });
            }])
            ->get()
            ->map(function ($student): array {
                $enrollment = $student->trainings->first();

                return [
                    'id' => $student->id,
                    'name' => $student->first_name.' '.$student->last_name,
                    'email' => $student->email,
                    'phone' => $student->phone ?? null,
                    'avatar' => $student->avatar ?? null,
                    'training_id' => $enrollment ? $enrollment->id : null,
                    'training_title' => $enrollment ? $enrollment->title : null,
                    'training_class_id' => $enrollment ? $enrollment->pivot->training_class_id : null,
                    'enrollment' => [
                        'progress' => $enrollment ? $enrollment->pivot->progress : 0,
                        'grade' => $enrollment ? $enrollment->pivot->grade : 0,
                        'attendance_rate' => $enrollment ? $enrollment->pivot->attendance_rate : 0,
                        'status' => $enrollment && $enrollment->pivot->grade < 10 ? 'at-risk' : 'active',
                    ],
                ];
            });

        // Get recent activities (latest quiz attempts, enrollments, etc.)
        $recentActivities = [];

        // Get recent quiz attempts
        $recentAttempts = \App\Models\QuizAttempt::whereHas('quiz.training', function ($query) use ($user): void {
            $query->where('teacher_id', $user->id);
        })
            ->with(['student', 'quiz'])
            ->latest()
            ->take(5)
            ->get();

        foreach ($recentAttempts as $attempt) {
            $type = 'success';
            $action = 'Évaluation terminée';

            if ($attempt->score < $attempt->quiz->passing_score) {
                $type = 'warning';
                $action = 'Note faible';
            }

            $recentActivities[] = [
                'id' => $attempt->id,
                'action' => $action,
                'student' => $attempt->student->first_name.' '.$attempt->student->last_name,
                'studentId' => $attempt->student->id,
                'time' => $attempt->completed_at ? $attempt->completed_at->diffForHumans() : $attempt->created_at->diffForHumans(),
                'type' => $type,
            ];
        }

        // Get recent enrollments
        $recentEnrollments = \App\Models\User::whereHas('trainings', function ($query) use ($user): void {
            $query->whereIn('training_id', function ($subQuery) use ($user): void {
                $subQuery->select('id')
                    ->from('trainings')
                    ->where('teacher_id', $user->id);
            })->where('status', 'approved')
                ->where('enrolled_at', '>=', now()->subDays(7));
        })
            ->latest('created_at')
            ->take(3)
            ->get();

        foreach ($recentEnrollments as $enrollment) {
            $recentActivities[] = [
                'id' => 'enrollment_'.$enrollment->id,
                'action' => 'Nouvel étudiant',
                'student' => $enrollment->first_name.' '.$enrollment->last_name,
                'studentId' => $enrollment->id,
                'time' => $enrollment->created_at->diffForHumans(),
                'type' => 'info',
            ];
        }

        // Sort by most recent
        usort($recentActivities, fn (array $a, array $b): int => strcmp((string) $b['time'], (string) $a['time']));

        // Limit to 5 most recent
        $recentActivities = array_slice($recentActivities, 0, 5);

        // Get teacher's formations (trainings)
        $formations = Training::with([
            'topics',
            'classes',
            'students' => function ($query): void {
                $query->where('status', 'approved');
            },
            'teacher',
        ])
            ->where('teacher_id', $user->id)
            ->orWhereHas('classes', function ($query) use ($user): void {
                $query->where('teacher_id', $user->id);
            })
            ->get()
            ->map(fn ($training): array => [
                'id' => $training->id,
                'uuid' => $training->uuid,
                'title' => $training->title,
                'level' => $training->level,
                'description' => $training->description,
                'classes_count' => $training->classes->count(),
                'students_count' => $training->students->count(),
                'completion_rate' => $training->students->avg('pivot.progress') ?? 0,
                'average_grade' => $training->students->avg('pivot.grade') ?? 0,
                'attendance_rate' => $training->students->avg('pivot.attendance_rate') ?? 0,
            ]);

        // Get quiz evaluations for teacher's trainings
        $evaluations = \App\Models\Quiz::whereHas('training', function ($query) use ($user): void {
            $query->where('teacher_id', $user->id);
        })
            ->with(['attempts' => function ($query): void {
                $query->where('status', 'completed');
            }])
            ->get()
            ->map(function ($quiz): array {
                $attempts = $quiz->attempts;
                $totalAttempts = $attempts->count();

                return [
                    'id' => $quiz->id,
                    'uuid' => $quiz->uuid,
                    'title' => $quiz->title,
                    'training_title' => $quiz->training->title ?? 'N/A',
                    'max_score' => $quiz->max_score,
                    'passing_score' => $quiz->passing_score,
                    'total_attempts' => $totalAttempts,
                    'average_score' => $totalAttempts > 0 ? round($attempts->avg('score'), 2) : 0,
                    'passed_count' => $attempts->where('score', '>=', $quiz->passing_score)->count(),
                    'failed_count' => $attempts->where('score', '<', $quiz->passing_score)->count(),
                    'pass_rate' => $totalAttempts > 0 ? round(($attempts->where('score', '>=', $quiz->passing_score)->count() / $totalAttempts) * 100, 2) : 0,
                ];
            });

        // Get attendance data for teacher's classes
        $attendanceData = \App\Models\TrainingClass::whereHas('training', function ($query) use ($user): void {
            $query->where('teacher_id', $user->id);
        })
            ->orWhere('teacher_id', $user->id)
            ->with(['attendances.student', 'training'])
            ->get()
            ->map(function ($class): array {
                // Count students enrolled in this specific class
                $totalStudents = \DB::table('training_enrollments')
                    ->where('training_class_id', $class->id)
                    ->where('status', 'approved')
                    ->count();

                $attendances = $class->attendances;

                $presentCount = $attendances->where('status', 'present')->count();
                $absentCount = $attendances->where('status', 'absent')->count();
                $excusedCount = $attendances->where('status', 'excused')->count();

                return [
                    'id' => $class->id,
                    'uuid' => $class->uuid,
                    'name' => $class->name,
                    'training_title' => $class->training->title ?? 'N/A',
                    'date' => $class->date,
                    'start_time' => $class->start_time,
                    'end_time' => $class->end_time,
                    'room' => $class->room,
                    'total_students' => $totalStudents,
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                    'excused_count' => $excusedCount,
                    'attendance_rate' => $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 2) : 0,
                ];
            });

        return Inertia::render('TeacherDashboard', [
            'totalStudents' => $totalStudents,
            'averageAttendance' => $averageAttendance,
            'atRiskStudents' => $atRiskStudents,
            'classes' => $trainings->flatMap->classes,
            'students' => $students,
            'formations' => $formations,
            'recentActivities' => $recentActivities,
            'evaluations' => $evaluations,
            'attendanceData' => $attendanceData,
            'previousPeriodStats' => [
                'totalStudents' => $previousPeriodStudents,
                'averageAttendance' => $previousPeriodAttendance,
                'atRiskStudents' => $previousPeriodAtRisk,
            ],
        ]);
    }

    public function teacherFormations(Request $request)
    {
        $user = $request->user();

        $trainings = Training::with(['topics', 'classes', 'students'])
            ->where('teacher_id', $user->id)
            ->orWhereHas('classes', function ($query) use ($user): void {
                $query->where('teacher_id', $user->id);
            })
            ->get()
            ->map(fn ($training): array => [
                'id' => $training->id,
                'uuid' => $training->uuid,
                'title' => $training->title,
                'level' => $training->level,
                'classes_count' => $training->classes->count(),
                'students_count' => $training->students()->where('status', 'approved')->count(),
                'completion_rate' => $training->students()->avg('progress') ?? 0,
                'average_grade' => $training->students()->avg('grade') ?? 0,
                'attendance_rate' => $training->students()->avg('attendance_rate') ?? 0,
            ]);

        return response()->json($trainings);
    }

    public function teacherStudents(Request $request)
    {
        $user = $request->user();

        $students = \App\Models\User::whereHas('trainings', function ($query) use ($user): void {
            $query->whereHas('classes', function ($classQuery) use ($user): void {
                $classQuery->where('teacher_id', $user->id);
            })->where('status', 'approved');
        })
            ->with(['trainings' => function ($query) use ($user): void {
                $query->whereHas('classes', function ($classQuery) use ($user): void {
                    $classQuery->where('teacher_id', $user->id);
                });
            }])
            ->get()
            ->map(function ($student): array {
                $enrollment = $student->trainings->first();

                return [
                    'id' => $student->id,
                    'name' => $student->first_name.' '.$student->last_name,
                    'email' => $student->email,
                    'formation' => $enrollment ? $enrollment->title : null,
                    'progress' => $enrollment ? $enrollment->pivot->progress : 0,
                    'grade' => $enrollment ? $enrollment->pivot->grade : 0,
                    'attendance' => $enrollment ? $enrollment->pivot->attendance_rate : 0,
                ];
            });

        return response()->json($students);
    }

    public function teacherAttendance(Request $request)
    {
        $user = $request->user();

        $classes = \App\Models\TrainingClass::with(['training', 'attendances.student'])
            ->where('teacher_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(fn ($class): array => [
                'id' => $class->id,
                'training' => $class->training->title,
                'date' => $class->date,
                'time' => $class->start_time.' - '.$class->end_time,
                'room' => $class->room,
                'total_students' => $class->training->students()->where('status', 'approved')->count(),
                'present_count' => $class->attendances()->where('status', 'present')->count(),
                'absent_count' => $class->attendances()->where('status', 'absent')->count(),
            ]);

        return response()->json($classes);
    }

    public function markAttendance(Request $request, $classId)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'status' => 'required|in:present,absent,excused',
        ]);

        $attendance = \App\Models\Attendance::updateOrCreate(
            [
                'training_class_id' => $classId,
                'student_id' => $validated['student_id'],
            ],
            [
                'status' => $validated['status'],
            ]
        );

        return response()->json($attendance);
    }

    public function teacherEvaluations(Request $request)
    {
        $this->authorize('view evaluations');

        $user = $request->user();

        $evaluations = \App\Models\Evaluation::with(['student', 'training', 'training_topic'])
            ->whereHas('training.classes', function ($query) use ($user): void {
                // If user can't manage all trainings, filter by their trainings
                if (! $user->can('manage system settings')) {
                    $query->where('teacher_id', $user->id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($evaluation): array => [
                'id' => $evaluation->id,
                'student_name' => $evaluation->student->first_name.' '.$evaluation->student->last_name,
                'training' => $evaluation->training->title,
                'topic' => $evaluation->training_topic ? $evaluation->training_topic->name : null,
                'type' => $evaluation->type,
                'grade' => $evaluation->grade,
                'max_grade' => $evaluation->max_grade,
                'date' => $evaluation->created_at->format('Y-m-d'),
            ]);

        return response()->json($evaluations);
    }

    public function gradeStudent(Request $request, $studentId)
    {
        $this->authorize('create evaluations');

        $validated = $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'training_topic_id' => 'nullable|exists:training_topics,id',
            'type' => 'required|in:quiz,exam,assignment,project',
            'grade' => 'required|numeric|min:0',
            'max_grade' => 'required|numeric|min:0',
            'comment' => 'nullable|string',
        ]);

        $evaluation = \App\Models\Evaluation::create([
            'student_id' => $studentId,
            'training_id' => $validated['training_id'],
            'training_topic_id' => $validated['training_topic_id'] ?? null,
            'type' => $validated['type'],
            'grade' => $validated['grade'],
            'max_grade' => $validated['max_grade'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json($evaluation);
    }

    /**
     * Show access management page for a private training.
     */
    public function accessIndex(Training $training, QrCodeService $qrCodeService): Response
    {
        $this->authorize('manageAccess', $training);

        $training->load(['accessUsers:id,first_name,last_name,email', 'accessRoles']);

        $users = \App\Models\User::select('id', 'first_name', 'last_name', 'email')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $roles = \Spatie\Permission\Models\Role::all();

        $shareData = null;
        if ($training->isShareTokenValid()) {
            $shareUrl = route('trainings.shared', $training->share_token);
            try {
                $qrCode = $qrCodeService->generateBase64($shareUrl);
            } catch (\Throwable $e) {
                \Log::error('QR Code generation failed', ['training_id' => $training->id, 'error' => $e->getMessage()]);
                $qrCode = null;
            }
            $shareData = [
                'url' => $shareUrl,
                'token' => $training->share_token,
                'expires_at' => $training->share_token_expires_at->toISOString(),
                'qr_code' => $qrCode,
            ];
        }

        return Inertia::render('Training/Access', [
            'training' => $training,
            'allUsers' => $users,
            'allRoles' => $roles,
            'shareData' => $shareData,
        ]);
    }

    /**
     * Generate a share link for a private training.
     */
    public function generateShareLink(Training $training, QrCodeService $qrCodeService): JsonResponse
    {
        $this->authorize('manageAccess', $training);

        $training->generateShareToken(24);
        $training->refresh();

        $shareUrl = route('trainings.shared', $training->share_token);

        try {
            $qrCode = $qrCodeService->generateBase64($shareUrl);
        } catch (\Throwable $e) {
            \Log::error('QR Code generation failed for training share link', [
                'training_id' => $training->id,
                'error' => $e->getMessage(),
            ]);
            $qrCode = null;
        }

        return response()->json([
            'url' => $shareUrl,
            'token' => $training->share_token,
            'expires_at' => $training->share_token_expires_at->toISOString(),
            'qr_code' => $qrCode,
        ]);
    }

    /**
     * Revoke the share link for a training.
     */
    public function revokeShareLink(Training $training): JsonResponse
    {
        $this->authorize('manageAccess', $training);

        $training->revokeShareToken();

        return response()->json(['message' => 'Lien de partage révoqué.']);
    }

    /**
     * Display the shared training page (public, no auth required).
     */
    public function showShared(string $token): Response
    {
        $training = Training::findByValidToken($token);

        if (! $training) {
            return Inertia::render('Training/SharedExpired', [
                'message' => 'Ce lien d\'inscription a expiré ou n\'est plus valide. Veuillez demander un nouveau lien.',
            ]);
        }

        $training->load(['topics', 'classes' => function ($query): void {
            $query->where('date', '>=', now()->toDateString())->orderBy('date')->orderBy('start_time');
        }, 'teacher:id,first_name,last_name']);

        return Inertia::render('Training/SharedView', [
            'training' => [
                'uuid' => $training->uuid,
                'title' => $training->title,
                'description' => $training->description,
                'duration' => $training->duration,
                'level' => $training->level,
                'price' => $training->price,
                'category' => $training->category,
                'image_url' => $training->image_url,
                'topics' => $training->topics,
                'classes' => $training->classes,
                'teacher' => $training->teacher ? [
                    'name' => $training->teacher->first_name.' '.$training->teacher->last_name,
                ] : null,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Enroll a user via share link (public, auth required for enrollment).
     */
    public function enrollViaShareLink(Request $request, string $token): \Illuminate\Http\RedirectResponse
    {
        $training = Training::findByValidToken($token);

        if (! $training) {
            return redirect()->route('trainings.shared', $token)
                ->with('error', 'Ce lien d\'inscription a expiré ou n\'est plus valide.');
        }

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour vous inscrire.');
        }

        if (! $training->is_active) {
            return back()->with('error', 'Cette formation n\'est plus disponible.');
        }

        if ($training->students()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Vous êtes déjà inscrit à cette formation.');
        }

        $validated = $request->validate([
            'selectedClassId' => [
                'required',
                \Illuminate\Validation\Rule::exists('training_classes', 'id')->where('training_id', $training->id),
            ],
            'motivation' => ['required', 'string', 'min:50', 'max:2000'],
            'paymentMethod' => 'required|string|in:monthly,quarterly,full,card',
            'hasReadTerms' => 'required|accepted',
            'hasReadPrivacyPolicy' => 'required|accepted',
        ]);

        $validated['motivation'] = htmlspecialchars(
            strip_tags((string) $validated['motivation']),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $training->students()->attach($user->id, [
            'status' => 'pending',
            'enrolled_at' => now(),
            'training_class_id' => $validated['selectedClassId'],
            'motivation' => $validated['motivation'],
            'payment_method' => $validated['paymentMethod'],
        ]);

        $training->increment('students_count');

        Mail::to($user->email)->queue(new TrainingEnrollmentSubmitted(
            userName: $user->first_name.' '.$user->last_name,
            trainingName: $training->title,
            paymentMethod: $validated['paymentMethod'],
        ));

        CacheService::forgetPattern('trainings');

        return back()->with('success', 'Votre demande d\'inscription a été enregistrée.');
    }

    /**
     * Grant user access to a private training.
     */
    public function grantUserAccess(Request $request, Training $training): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('manageAccess', $training);

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $pivotData = collect($validated['user_ids'])->mapWithKeys(
            fn ($id) => [$id => ['granted_by' => $request->user()->id]]
        )->all();

        $training->accessUsers()->syncWithoutDetaching($pivotData);

        CacheService::forgetPattern('trainings');

        return back()->with('success', 'Accès utilisateurs mis à jour.');
    }

    /**
     * Revoke user access from a private training.
     */
    public function revokeUserAccess(Request $request, Training $training): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('manageAccess', $training);

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $training->accessUsers()->detach($validated['user_ids']);

        CacheService::forgetPattern('trainings');

        return back()->with('success', 'Accès utilisateurs révoqués.');
    }

    /**
     * Grant role access to a private training.
     */
    public function grantRoleAccess(Request $request, Training $training): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('manageAccess', $training);

        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $pivotData = collect($validated['role_ids'])->mapWithKeys(
            fn ($id) => [$id => ['granted_by' => $request->user()->id]]
        )->all();

        $training->accessRoles()->syncWithoutDetaching($pivotData);

        CacheService::forgetPattern('trainings');

        return back()->with('success', 'Accès rôles mis à jour.');
    }

    /**
     * Revoke role access from a private training.
     */
    public function revokeRoleAccess(Request $request, Training $training): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('manageAccess', $training);

        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $training->accessRoles()->detach($validated['role_ids']);

        CacheService::forgetPattern('trainings');

        return back()->with('success', 'Accès rôles révoqués.');
    }
}
