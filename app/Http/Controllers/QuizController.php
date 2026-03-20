<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Training;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    /**
     * Display a listing of quizzes for a training
     */
    public function index(Training $training): Response
    {
        $this->authorize('manage quizzes');

        // Récupérer TOUS les quiz pour les enseignants/admins (pas seulement les actifs)
        $quizzes = Quiz::where('training_id', $training->id)->with('attempts')->get()->map(fn ($quiz): array => [
            'id' => $quiz->id,
            'uuid' => $quiz->uuid,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'duration_minutes' => $quiz->duration_minutes,
            'max_score' => $quiz->max_score,
            'passing_score' => $quiz->passing_score,
            'available_from' => $quiz->available_from?->format('Y-m-d\TH:i'),
            'available_until' => $quiz->available_until?->format('Y-m-d\TH:i'),
            'is_active' => $quiz->is_active,
            'max_attempts' => $quiz->max_attempts,
            'score_display' => $quiz->score_display,
            'status' => $quiz->status,
            'attempts_count' => $quiz->attempts->count(),
            'completed_attempts_count' => $quiz->attempts->where('status', 'completed')->count(),
        ]);

        return Inertia::render('Quiz/Index', [
            'training' => $training,
            'quizzes' => $quizzes,
        ]);
    }

    /**
     * Show the form for creating a new quiz
     */
    public function create(Training $training): Response
    {
        $this->authorize('create quizzes');

        // Load training classes with their materials and student counts
        $training->load(['classes.materials', 'classes.students']);

        $trainingClasses = $training->classes->map(fn ($class): array => [
            'id' => $class->id,
            'uuid' => $class->uuid,
            'name' => $class->name,
            'date' => $class->date,
            'start_time' => $class->start_time,
            'end_time' => $class->end_time,
            'room' => $class->room,
            'students_count' => $class->students->count(),
            'materials' => $class->materials->map(fn ($material): array => [
                'id' => $material->id,
                'uuid' => $material->uuid,
                'title' => $material->title,
                'type' => $material->type,
                'order' => $material->order,
            ]),
        ]);

        return Inertia::render('Quiz/Create', [
            'training' => $training,
            'trainingClasses' => $trainingClasses,
        ]);
    }

    /**
     * Store a newly created quiz
     */
    public function store(Request $request, Training $training)
    {
        $this->authorize('create quizzes');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1|max:240',
            'passing_score' => 'required|integer|min:0|max:100',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after_or_equal:available_from',
            'is_active' => 'boolean',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'score_display' => 'nullable|in:best,last,average',
            'status' => 'nullable|in:draft,published,archived',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.options' => 'nullable|array',
            'questions.*.correct_answers' => 'required|array',
            'questions.*.feedback_correct' => 'nullable|string|max:1000',
            'questions.*.feedback_incorrect' => 'nullable|string|max:1000',
            'questions.*.points' => 'required|integer|min:1',
            'assigned_classes' => 'nullable|array',
            'assigned_classes.*' => 'exists:training_classes,id',
            'assigned_materials' => 'nullable|array',
            'assigned_materials.*' => 'exists:training_class_materials,id',
        ]);

        // Calculate max_score from questions
        $max_score = collect($validated['questions'])->sum('points');

        $quiz = $training->quizzes()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'duration_minutes' => $validated['duration_minutes'],
            'max_score' => $max_score,
            'passing_score' => $validated['passing_score'],
            'available_from' => $validated['available_from'] ?? null,
            'available_until' => $validated['available_until'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'max_attempts' => $validated['max_attempts'] ?? 1,
            'score_display' => $validated['score_display'] ?? 'best',
            'status' => $validated['status'] ?? 'draft',
        ]);

        // Create questions
        foreach ($validated['questions'] as $index => $questionData) {
            $quiz->questions()->create([
                'question' => $questionData['question'],
                'type' => $questionData['type'],
                'options' => $questionData['options'] ?? null,
                'correct_answers' => $questionData['correct_answers'],
                'feedback_correct' => $questionData['feedback_correct'] ?? null,
                'feedback_incorrect' => $questionData['feedback_incorrect'] ?? null,
                'points' => $questionData['points'],
                'order' => $index,
            ]);
        }

        // Assign to training classes if specified
        if (! empty($validated['assigned_classes'])) {
            $classAssignments = [];
            foreach ($validated['assigned_classes'] as $classId) {
                $classAssignments[$classId] = [
                    'assigned_at' => now(),
                    'available_from' => $validated['available_from'] ?? null,
                    'available_until' => $validated['available_until'] ?? null,
                    'is_active' => true,
                ];
            }
            $quiz->trainingClasses()->attach($classAssignments);
        }

        // Assign to training class materials if specified
        if (! empty($validated['assigned_materials'])) {
            $materialAssignments = [];
            foreach ($validated['assigned_materials'] as $index => $materialId) {
                $materialAssignments[$materialId] = [
                    'assigned_at' => now(),
                    'is_active' => true,
                    'order' => $index,
                ];
            }
            $quiz->trainingClassMaterials()->attach($materialAssignments);
        }

        $successMessage = 'Quiz créé avec succès';
        $assignedClassesCount = count($validated['assigned_classes'] ?? []);
        $assignedMaterialsCount = count($validated['assigned_materials'] ?? []);

        if ($assignedClassesCount > 0) {
            $successMessage .= " et assigné à {$assignedClassesCount} classe(s)";
        }
        if ($assignedMaterialsCount > 0) {
            $successMessage .= " et {$assignedMaterialsCount} support(s) de cours";
        }

        return redirect()->route('trainings.quizzes.index', $training)
            ->with('success', $successMessage);
    }

    /**
     * Show the form for editing a quiz
     */
    public function edit(Training $training, Quiz $quiz): Response
    {
        $this->authorize('edit quizzes');

        $quiz->load(['questions', 'trainingClasses', 'trainingClassMaterials']);

        // Load training classes with their materials and student counts
        $training->load(['classes.materials', 'classes.students']);

        $trainingClasses = $training->classes->map(fn ($class): array => [
            'id' => $class->id,
            'uuid' => $class->uuid,
            'name' => $class->name,
            'date' => $class->date,
            'start_time' => $class->start_time,
            'end_time' => $class->end_time,
            'room' => $class->room,
            'students_count' => $class->students->count(),
            'materials' => $class->materials->map(fn ($material): array => [
                'id' => $material->id,
                'uuid' => $material->uuid,
                'title' => $material->title,
                'type' => $material->type,
                'order' => $material->order,
            ]),
        ]);

        // Get currently assigned classes and materials
        $assignedClasses = $quiz->trainingClasses->pluck('id')->toArray();
        $assignedMaterials = $quiz->trainingClassMaterials->pluck('id')->toArray();

        return Inertia::render('Quiz/Edit', [
            'training' => $training,
            'trainingClasses' => $trainingClasses,
            'assignedClasses' => $assignedClasses,
            'assignedMaterials' => $assignedMaterials,
            'quiz' => [
                'id' => $quiz->id,
                'uuid' => $quiz->uuid,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'duration_minutes' => $quiz->duration_minutes,
                'max_score' => $quiz->max_score,
                'passing_score' => $quiz->passing_score,
                'available_from' => $quiz->available_from?->format('Y-m-d\TH:i'),
                'available_until' => $quiz->available_until?->format('Y-m-d\TH:i'),
                'is_active' => $quiz->is_active,
                'max_attempts' => $quiz->max_attempts,
                'score_display' => $quiz->score_display,
                'status' => $quiz->status,
                'questions' => $quiz->questions->map(fn ($q): array => [
                    'id' => $q->id,
                    'question' => $q->question,
                    'type' => $q->type,
                    'options' => $q->options,
                    'correct_answers' => $q->correct_answers,
                    'feedback_correct' => $q->feedback_correct,
                    'feedback_incorrect' => $q->feedback_incorrect,
                    'points' => $q->points,
                    'order' => $q->order,
                ]),
            ],
        ]);
    }

    /**
     * Update the specified quiz
     */
    public function update(Request $request, Training $training, Quiz $quiz)
    {
        $this->authorize('edit quizzes');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1|max:240',
            'passing_score' => 'required|integer|min:0|max:100',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after_or_equal:available_from',
            'is_active' => 'boolean',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'score_display' => 'nullable|in:best,last,average',
            'status' => 'nullable|in:draft,published,archived',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|exists:quiz_questions,id',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.options' => 'nullable|array',
            'questions.*.correct_answers' => 'required|array',
            'questions.*.feedback_correct' => 'nullable|string|max:1000',
            'questions.*.feedback_incorrect' => 'nullable|string|max:1000',
            'questions.*.points' => 'required|integer|min:1',
            'assigned_classes' => 'nullable|array',
            'assigned_classes.*' => 'exists:training_classes,id',
            'assigned_materials' => 'nullable|array',
            'assigned_materials.*' => 'exists:training_class_materials,id',
        ]);

        // Calculate max_score from questions
        $max_score = collect($validated['questions'])->sum('points');

        $quiz->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'duration_minutes' => $validated['duration_minutes'],
            'max_score' => $max_score,
            'passing_score' => $validated['passing_score'],
            'available_from' => $validated['available_from'] ?? null,
            'available_until' => $validated['available_until'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'max_attempts' => $validated['max_attempts'] ?? 1,
            'score_display' => $validated['score_display'] ?? 'best',
            'status' => $validated['status'] ?? 'draft',
        ]);

        // Delete questions that are not in the update
        $questionIds = collect($validated['questions'])->pluck('id')->filter();
        $quiz->questions()->whereNotIn('id', $questionIds)->delete();

        // Update or create questions
        foreach ($validated['questions'] as $index => $questionData) {
            if (isset($questionData['id'])) {
                $question = QuizQuestion::find($questionData['id']);
                $question->update([
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                    'options' => $questionData['options'] ?? null,
                    'correct_answers' => $questionData['correct_answers'],
                    'feedback_correct' => $questionData['feedback_correct'] ?? null,
                    'feedback_incorrect' => $questionData['feedback_incorrect'] ?? null,
                    'points' => $questionData['points'],
                    'order' => $index,
                ]);
            } else {
                $quiz->questions()->create([
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                    'options' => $questionData['options'] ?? null,
                    'correct_answers' => $questionData['correct_answers'],
                    'feedback_correct' => $questionData['feedback_correct'] ?? null,
                    'feedback_incorrect' => $questionData['feedback_incorrect'] ?? null,
                    'points' => $questionData['points'],
                    'order' => $index,
                ]);
            }
        }

        // Update training class assignments
        $currentAssignedClasses = $quiz->trainingClasses->pluck('id')->toArray();
        $newAssignedClasses = $validated['assigned_classes'] ?? [];

        // Remove classes that are no longer assigned
        $classesToRemove = array_diff($currentAssignedClasses, $newAssignedClasses);
        if ($classesToRemove !== []) {
            $quiz->trainingClasses()->detach($classesToRemove);
        }

        // Add new class assignments
        $classesToAdd = array_diff($newAssignedClasses, $currentAssignedClasses);
        if ($classesToAdd !== []) {
            $classAssignments = [];
            foreach ($classesToAdd as $classId) {
                $classAssignments[$classId] = [
                    'assigned_at' => now(),
                    'available_from' => $validated['available_from'] ?? null,
                    'available_until' => $validated['available_until'] ?? null,
                    'is_active' => true,
                ];
            }
            $quiz->trainingClasses()->attach($classAssignments);
        }

        // Update training class material assignments
        $currentAssignedMaterials = $quiz->trainingClassMaterials->pluck('id')->toArray();
        $newAssignedMaterials = $validated['assigned_materials'] ?? [];

        // Remove materials that are no longer assigned
        $materialsToRemove = array_diff($currentAssignedMaterials, $newAssignedMaterials);
        if ($materialsToRemove !== []) {
            $quiz->trainingClassMaterials()->detach($materialsToRemove);
        }

        // Add new material assignments
        $materialsToAdd = array_diff($newAssignedMaterials, $currentAssignedMaterials);
        if ($materialsToAdd !== []) {
            $materialAssignments = [];
            foreach ($materialsToAdd as $index => $materialId) {
                $materialAssignments[$materialId] = [
                    'assigned_at' => now(),
                    'is_active' => true,
                    'order' => $index,
                ];
            }
            $quiz->trainingClassMaterials()->attach($materialAssignments);
        }

        // Build success message with assignment changes
        $successMessage = 'Quiz mis à jour avec succès';
        $assignmentChanges = [];

        if ($classesToAdd !== []) {
            $assignmentChanges[] = count($classesToAdd).' classe(s) ajoutée(s)';
        }
        if ($classesToRemove !== []) {
            $assignmentChanges[] = count($classesToRemove).' classe(s) retirée(s)';
        }
        if ($materialsToAdd !== []) {
            $assignmentChanges[] = count($materialsToAdd).' support(s) ajouté(s)';
        }
        if ($materialsToRemove !== []) {
            $assignmentChanges[] = count($materialsToRemove).' support(s) retiré(s)';
        }

        if ($assignmentChanges !== []) {
            $successMessage .= ' ('.implode(', ', $assignmentChanges).')';
        }

        return redirect()->route('trainings.quizzes.index', $training)
            ->with('success', $successMessage);
    }

    /**
     * Remove the specified quiz
     */
    public function destroy(Training $training, Quiz $quiz)
    {
        $this->authorize('delete quizzes');

        $quiz->delete();

        return redirect()->route('trainings.quizzes.index', $training)
            ->with('success', 'Quiz supprimé avec succès');
    }

    /**
     * Show quiz results to teachers
     */
    public function results(Training $training, Quiz $quiz): Response
    {
        $this->authorize('grade quizzes');

        $quiz->load(['questions', 'attempts.student']);

        $attempts = $quiz->attempts()
            ->with('student')
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(fn ($attempt): array => [
                'id' => $attempt->id,
                'student' => [
                    'id' => $attempt->student->id,
                    'name' => $attempt->student->first_name.' '.$attempt->student->last_name,
                    'email' => $attempt->student->email,
                ],
                'score' => $attempt->score,
                'max_score' => $quiz->max_score,
                'percentage' => $percentage = $quiz->max_score > 0 ? round(($attempt->score / $quiz->max_score) * 100, 2) : 0,
                'passed' => $percentage >= $quiz->passing_score,
                'started_at' => $attempt->started_at->format('Y-m-d H:i:s'),
                'completed_at' => $attempt->completed_at?->format('Y-m-d H:i:s'),
                'time_taken' => $attempt->started_at->diff($attempt->completed_at)->format('%H:%I:%S'),
            ]);

        return Inertia::render('Quiz/Results', [
            'training' => $training,
            'quiz' => [
                'id' => $quiz->id,
                'uuid' => $quiz->uuid,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'max_score' => $quiz->max_score,
                'passing_score' => $quiz->passing_score,
                'questions_count' => $quiz->questions->count(),
            ],
            'attempts' => $attempts,
            'statistics' => [
                'total_attempts' => $attempts->count(),
                'passed_count' => $attempts->where('passed', true)->count(),
                'failed_count' => $attempts->where('passed', false)->count(),
                'average_score' => $attempts->avg('percentage'),
                'highest_score' => $attempts->max('score'),
                'lowest_score' => $attempts->min('score'),
            ],
        ]);
    }

    /**
     * Start or resume a quiz attempt (for students)
     */
    public function start(Request $request, Quiz $quiz): Response|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('take quizzes');

        $user = $request->user();

        // Check if quiz is published
        if ($quiz->status !== 'published') {
            return back()->with('error', 'Ce quiz n\'est pas encore publié.');
        }

        // Check if quiz is available
        if (! $quiz->is_active) {
            return back()->with('error', 'Ce quiz n\'est pas disponible actuellement.');
        }

        if ($quiz->available_from && $quiz->available_from->isFuture()) {
            return back()->with('error', 'Ce quiz n\'est pas encore disponible.');
        }

        if ($quiz->available_until && $quiz->available_until->isPast()) {
            return back()->with('error', 'Ce quiz n\'est plus disponible.');
        }

        // Check if student has exceeded maximum attempts
        $completedAttemptsCount = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->where('status', 'completed')
            ->count();

        if ($completedAttemptsCount >= $quiz->max_attempts) {
            return back()->with('error', "Vous avez atteint le nombre maximum de tentatives ({$quiz->max_attempts}).");
        }

        // Check for existing in-progress attempt
        $existingAttempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if (! $existingAttempt) {
            // Create new attempt
            $existingAttempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $user->id,
                'started_at' => now(),
                'time_remaining_seconds' => $quiz->duration_minutes * 60,
                'status' => 'in_progress',
            ]);
        }

        // Calculate time remaining
        $elapsedSeconds = now()->diffInSeconds($existingAttempt->started_at);
        $timeRemainingSeconds = max(0, ($quiz->duration_minutes * 60) - $elapsedSeconds);

        // If time is up, mark as abandoned
        if ($timeRemainingSeconds <= 0) {
            $existingAttempt->update([
                'status' => 'abandoned',
                'completed_at' => now(),
            ]);

            return back()->with('error', 'Le temps pour ce quiz est écoulé.');
        }

        // Load questions (without showing correct answers)
        $quiz->load('questions');

        return Inertia::render('Quiz/Take', [
            'quiz' => [
                'id' => $quiz->id,
                'uuid' => $quiz->uuid,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'duration_minutes' => $quiz->duration_minutes,
                'max_score' => $quiz->max_score,
                'passing_score' => $quiz->passing_score,
                'questions' => $quiz->questions->map(fn ($q): array => [
                    'id' => $q->id,
                    'question' => $q->question,
                    'type' => $q->type,
                    'options' => $q->options,
                    'points' => $q->points,
                    'correct_answers_count' => count($q->correct_answers),
                    // Don't send correct_answers to frontend
                ]),
            ],
            'attempt' => [
                'id' => $existingAttempt->id,
                'uuid' => $existingAttempt->uuid,
                'started_at' => $existingAttempt->started_at->toISOString(),
                'time_remaining_seconds' => $timeRemainingSeconds,
            ],
        ]);
    }

    /**
     * Submit quiz answers and calculate score
     */
    public function submit(Request $request, QuizAttempt $attempt)
    {
        $user = $request->user();

        // Verify it's the user's attempt
        if ($attempt->student_id !== $user->id) {
            return back()->with('error', 'Tentative invalide.');
        }

        // Verify attempt is still in progress
        if ($attempt->status !== 'in_progress') {
            return back()->with('error', 'Cette tentative est déjà terminée.');
        }

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.answer' => 'nullable', // Can be string, array, or null
        ]);

        // Load quiz with questions
        $quiz = $attempt->quiz()->with('questions')->first();

        // Calculate score server-side
        $totalScore = 0;
        $answersWithResults = [];

        foreach ($validated['answers'] as $answerData) {
            $question = $quiz->questions->find($answerData['question_id']);
            if (! $question) {
                continue;
            }

            $studentAnswer = $answerData['answer'];
            $isCorrect = $question->isCorrectAnswer($studentAnswer);

            if ($isCorrect) {
                $totalScore += $question->points;
            }

            $answersWithResults[] = [
                'question_id' => $question->id,
                'question_text' => $question->question,
                'answer' => $studentAnswer,
                'is_correct' => $isCorrect,
                'points_earned' => $isCorrect ? $question->points : 0,
                'feedback' => $isCorrect ? $question->feedback_correct : $question->feedback_incorrect,
            ];
        }

        // Update attempt
        $attempt->update([
            'completed_at' => now(),
            'status' => 'completed',
            'score' => $totalScore,
            'answers' => $answersWithResults,
        ]);

        $percentage = $quiz->max_score > 0 ? round(($totalScore / $quiz->max_score) * 100, 2) : 0;
        $passed = $percentage >= $quiz->passing_score;

        // Redirect to results page with feedback
        return redirect()->route('quiz-attempts.show', $attempt)
            ->with('success',
                $passed
                    ? "Quiz terminé avec succès! Score: {$totalScore}/{$quiz->max_score} - Vous avez réussi!"
                    : "Quiz terminé. Score: {$totalScore}/{$quiz->max_score} - Score minimum requis: {$quiz->passing_score}%"
            );
    }

    /**
     * Show student's quiz attempt results with feedback
     */
    public function showAttempt(QuizAttempt $attempt): Response
    {
        $user = auth()->user();

        // Verify it's the user's attempt or user has permission to view all results
        if ($attempt->student_id !== $user->id && ! $user->can('grade quizzes')) {
            abort(403, 'Accès non autorisé.');
        }

        $attempt->load(['quiz.training', 'quiz.questions', 'student']);

        $percentage = $attempt->quiz->max_score > 0 ? round(($attempt->score / $attempt->quiz->max_score) * 100, 2) : 0;
        $passed = $percentage >= $attempt->quiz->passing_score;

        // Prepare questions with student answers and feedback
        $questionsWithAnswers = collect($attempt->answers)->map(function (array $answer) use ($attempt): array {
            $question = $attempt->quiz->questions->find($answer['question_id']);

            return [
                'question' => $answer['question_text'] ?? $question->question,
                'type' => $question->type,
                'options' => $question->options,
                'student_answer' => $answer['answer'],
                'is_correct' => $answer['is_correct'],
                'points_earned' => $answer['points_earned'],
                'max_points' => $question->points,
                'feedback' => $answer['feedback'] ?? null,
            ];
        });

        return Inertia::render('Quiz/AttemptResults', [
            'attempt' => [
                'id' => $attempt->id,
                'uuid' => $attempt->uuid,
                'score' => $attempt->score,
                'max_score' => $attempt->quiz->max_score,
                'passing_score' => $attempt->quiz->passing_score,
                'percentage' => $attempt->quiz->max_score > 0 ? round(($attempt->score / $attempt->quiz->max_score) * 100, 2) : 0,
                'passed' => $passed,
                'started_at' => $attempt->started_at->format('Y-m-d H:i:s'),
                'completed_at' => $attempt->completed_at?->format('Y-m-d H:i:s'),
                'time_taken' => $attempt->started_at->diff($attempt->completed_at)->format('%H:%I:%S'),
            ],
            'quiz' => [
                'id' => $attempt->quiz->id,
                'uuid' => $attempt->quiz->uuid,
                'title' => $attempt->quiz->title,
                'description' => $attempt->quiz->description,
            ],
            'training' => [
                'id' => $attempt->quiz->training->id,
                'uuid' => $attempt->quiz->training->uuid,
                'title' => $attempt->quiz->training->title,
            ],
            'student' => [
                'name' => $attempt->student->first_name.' '.$attempt->student->last_name,
            ],
            'questionsWithAnswers' => $questionsWithAnswers,
        ]);
    }

    /**
     * Export quiz results to CSV
     */
    public function exportCSV(Training $training, Quiz $quiz)
    {
        $this->authorize('grade quizzes');

        $quiz->load(['questions', 'attempts.student']);

        $attempts = $quiz->attempts()
            ->with('student')
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get();

        $filename = 'quiz_results_'.$quiz->title.'_'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($attempts, $quiz): void {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'Étudiant',
                'Email',
                'Score',
                'Score Maximum',
                'Pourcentage (%)',
                'Statut',
                'Temps écoulé',
                'Date de début',
                'Date de fin',
            ], ';');

            // Data rows
            foreach ($attempts as $attempt) {
                $percentage = $quiz->max_score > 0 ? round(($attempt->score / $quiz->max_score) * 100, 2) : 0;
                $passed = $percentage >= $quiz->passing_score;
                $timeTaken = $attempt->started_at->diff($attempt->completed_at)->format('%H:%I:%S');

                fputcsv($file, [
                    $attempt->student->first_name.' '.$attempt->student->last_name,
                    $attempt->student->email,
                    $attempt->score,
                    $quiz->max_score,
                    $percentage,
                    $passed ? 'Réussi' : 'Échoué',
                    $timeTaken,
                    $attempt->started_at->format('Y-m-d H:i:s'),
                    $attempt->completed_at?->format('Y-m-d H:i:s'),
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show teacher dashboard with global quiz statistics
     */
    public function teacherDashboard(): Response
    {
        $this->authorize('manage quizzes');

        $user = auth()->user();

        // Get all quizzes accessible to this teacher
        $allQuizzes = Quiz::with(['training', 'attempts', 'questions'])
            ->whereHas('training', function ($query) use ($user): void {
                // If user can't manage all trainings, filter by trainings they can access
                if (! $user->can('manage system settings')) {
                    $query->whereHas('students', function ($q) use ($user): void {
                        $q->where('users.id', $user->id);
                    });
                }
            })
            ->get();

        // Global statistics
        $stats = [
            'total_quizzes' => $allQuizzes->count(),
            'draft_quizzes' => $allQuizzes->where('status', 'draft')->count(),
            'published_quizzes' => $allQuizzes->where('status', 'published')->count(),
            'archived_quizzes' => $allQuizzes->where('status', 'archived')->count(),
            'total_questions' => $allQuizzes->sum(fn ($q) => $q->questions->count()),
            'total_attempts' => $allQuizzes->sum(fn ($q) => $q->attempts->where('status', 'completed')->count()),
            'average_score' => 0,
            'pass_rate' => 0,
        ];

        // Calculate average score and pass rate
        $allAttempts = collect();
        foreach ($allQuizzes as $quiz) {
            $completedAttempts = $quiz->attempts->where('status', 'completed');
            foreach ($completedAttempts as $attempt) {
                $allAttempts->push([
                    'score' => $attempt->score,
                    'max_score' => $quiz->max_score,
                    'passing_score' => $quiz->passing_score,
                    'passed' => $quiz->max_score > 0 ? round(($attempt->score / $quiz->max_score) * 100, 2) >= $quiz->passing_score : false,
                ]);
            }
        }

        if ($allAttempts->count() > 0) {
            $stats['average_score'] = round($allAttempts->avg(fn ($a): int|float => $a['max_score'] > 0 ? ($a['score'] / $a['max_score']) * 100 : 0
            ), 2);
            $stats['pass_rate'] = round(($allAttempts->where('passed', true)->count() / $allAttempts->count()) * 100, 2);
        }

        // Recent attempts (last 10)
        $recentAttempts = QuizAttempt::with(['quiz.training', 'student'])
            ->whereHas('quiz', function ($query) use ($user): void {
                $query->whereHas('training', function ($q) use ($user): void {
                    if (! $user->can('manage system settings')) {
                        $q->whereHas('students', function ($u) use ($user): void {
                            $u->where('users.id', $user->id);
                        });
                    }
                });
            })
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($attempt): array => [
                'id' => $attempt->id,
                'student_name' => $attempt->student->first_name.' '.$attempt->student->last_name,
                'quiz_title' => $attempt->quiz->title,
                'training_name' => $attempt->quiz->training->title,
                'score' => $attempt->score,
                'max_score' => $attempt->quiz->max_score,
                'percentage' => $percentage = $attempt->quiz->max_score > 0 ? round(($attempt->score / $attempt->quiz->max_score) * 100, 2) : 0,
                'passed' => $percentage >= $attempt->quiz->passing_score,
                'completed_at' => $attempt->completed_at->format('Y-m-d H:i'),
            ]);

        // Quiz performance breakdown
        $quizPerformance = $allQuizzes->map(function ($quiz): array {
            $completedAttempts = $quiz->attempts->where('status', 'completed');
            $passedAttempts = $completedAttempts->filter(fn ($a): bool => $quiz->max_score > 0 && round(($a->score / $quiz->max_score) * 100, 2) >= $quiz->passing_score);

            return [
                'uuid' => $quiz->uuid,
                'title' => $quiz->title,
                'training_name' => $quiz->training->title,
                'status' => $quiz->status,
                'total_attempts' => $completedAttempts->count(),
                'passed_attempts' => $passedAttempts->count(),
                'pass_rate' => $completedAttempts->count() > 0
                    ? round(($passedAttempts->count() / $completedAttempts->count()) * 100, 2)
                    : 0,
                'average_score' => $completedAttempts->count() > 0
                    ? round($completedAttempts->avg(fn ($a): int|float => $quiz->max_score > 0 ? ($a->score / $quiz->max_score) * 100 : 0
                    ), 2)
                    : 0,
            ];
        })->sortByDesc('total_attempts')->take(10)->values();

        return Inertia::render('Quiz/TeacherDashboard', [
            'statistics' => $stats,
            'recentAttempts' => $recentAttempts,
            'quizPerformance' => $quizPerformance,
        ]);
    }

    /**
     * Toggle quiz active status
     */
    public function toggleStatus(Training $training, Quiz $quiz)
    {
        $this->authorize('edit quizzes');

        $quiz->update([
            'is_active' => ! $quiz->is_active,
        ]);

        return back()->with('success', $quiz->is_active
            ? 'Quiz activé avec succès'
            : 'Quiz désactivé avec succès');
    }
}
