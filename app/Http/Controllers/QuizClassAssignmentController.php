<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuizClassAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage trainings')->only(['assignToClass', 'removeFromClass']);
        $this->middleware('can:manage quizzes')->only(['assignToMaterial', 'removeFromMaterial']);
    }

    /**
     * Show the quiz assignment page for a specific quiz.
     */
    public function show(Training $training, Quiz $quiz): Response
    {
        $quiz->load([
            'training.classes.materials',
            'trainingClasses' => function ($query): void {
                $query->withPivot(['assigned_at', 'available_from', 'available_until', 'is_active']);
            },
            'trainingClassMaterials' => function ($query): void {
                $query->withPivot(['assigned_at', 'is_active', 'order']);
            }
        ]);

        // Get all classes for this training
        $availableClasses = $quiz->training->classes()
            ->with(['materials', 'students'])
            ->get()
            ->map(function ($class) use ($quiz): array {
                $isAssigned = $quiz->trainingClasses->contains('id', $class->id);
                $assignmentData = null;

                if ($isAssigned) {
                    $assignment = $quiz->trainingClasses->where('id', $class->id)->first();
                    $assignmentData = $assignment->pivot;
                }

                return [
                    'id' => $class->id,
                    'uuid' => $class->uuid,
                    'name' => $class->name,
                    'date' => $class->date,
                    'start_time' => $class->start_time,
                    'end_time' => $class->end_time,
                    'room' => $class->room,
                    'students_count' => $class->students->count(),
                    'materials_count' => $class->materials->count(),
                    'is_assigned' => $isAssigned,
                    'assignment_data' => $assignmentData,
                    'completion_stats' => $isAssigned ? $quiz->getClassCompletionStats($class) : null,
                ];
            });

        return Inertia::render('Quiz/ClassAssignments', [
            'quiz' => $quiz,
            'availableClasses' => $availableClasses,
        ]);
    }

    /**
     * Assign a quiz to a training class.
     */
    public function assignToClass(Request $request, Training $training, Quiz $quiz, TrainingClass $trainingClass): JsonResponse
    {
        $validated = $request->validate([
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after_or_equal:available_from',
        ]);

        // Check if already assigned
        if ($quiz->trainingClasses()->where('training_class_id', $trainingClass->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce quiz est déjà assigné à cette classe.'
            ], 422);
        }

        // Check if the training class belongs to the same training as the quiz
        if ($trainingClass->training_id !== $quiz->training_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette classe n\'appartient pas à la même formation que le quiz.'
            ], 422);
        }

        $quiz->trainingClasses()->attach($trainingClass->id, [
            'assigned_at' => now(),
            'available_from' => $validated['available_from'] ?? null,
            'available_until' => $validated['available_until'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz assigné à la classe avec succès.',
            'completion_stats' => $quiz->getClassCompletionStats($trainingClass),
        ]);
    }

    /**
     * Remove a quiz from a training class.
     */
    public function removeFromClass(Training $training, Quiz $quiz, TrainingClass $trainingClass): JsonResponse
    {
        $quiz->trainingClasses()->detach($trainingClass->id);

        return response()->json([
            'success' => true,
            'message' => 'Quiz retiré de la classe avec succès.'
        ]);
    }

    /**
     * Update assignment settings for a quiz-class relationship.
     */
    public function updateClassAssignment(Request $request, Training $training, Quiz $quiz, TrainingClass $trainingClass): JsonResponse
    {
        $validated = $request->validate([
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after_or_equal:available_from',
            'is_active' => 'boolean',
        ]);

        $quiz->trainingClasses()->updateExistingPivot($trainingClass->id, [
            'available_from' => $validated['available_from'] ?? null,
            'available_until' => $validated['available_until'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paramètres d\'assignation mis à jour avec succès.',
            'completion_stats' => $quiz->getClassCompletionStats($trainingClass),
        ]);
    }

    /**
     * Assign a quiz to a training class material.
     */
    public function assignToMaterial(Request $request, Training $training, Quiz $quiz, TrainingClassMaterial $material): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'integer|min:0',
        ]);

        // Check if already assigned
        if ($quiz->trainingClassMaterials()->where('training_class_material_id', $material->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce quiz est déjà assigné à ce support de cours.'
            ], 422);
        }

        // Check if the material belongs to a class of the same training
        if ($material->trainingClass->training_id !== $quiz->training_id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce support de cours n\'appartient pas à la même formation que le quiz.'
            ], 422);
        }

        $quiz->trainingClassMaterials()->attach($material->id, [
            'assigned_at' => now(),
            'is_active' => true,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz assigné au support de cours avec succès.'
        ]);
    }

    /**
     * Remove a quiz from a training class material.
     */
    public function removeFromMaterial(Training $training, Quiz $quiz, TrainingClassMaterial $material): JsonResponse
    {
        $quiz->trainingClassMaterials()->detach($material->id);

        return response()->json([
            'success' => true,
            'message' => 'Quiz retiré du support de cours avec succès.'
        ]);
    }

    /**
     * Get completion statistics for a quiz across all assigned classes.
     */
    public function getQuizStats(Training $training, Quiz $quiz): JsonResponse
    {
        $quiz->load(['trainingClasses', 'training']);

        $stats = [];
        $totalStudents = 0;
        $totalCompleted = 0;
        $totalPassed = 0;

        foreach ($quiz->trainingClasses as $class) {
            $classStats = $quiz->getClassCompletionStats($class);
            $stats[] = [
                'class' => $class,
                'stats' => $classStats,
            ];

            $totalStudents += $classStats['total_students'];
            $totalCompleted += $classStats['completed_attempts'];
            $totalPassed += $classStats['passed_attempts'];
        }

        return response()->json([
            'quiz' => $quiz,
            'total_students' => $totalStudents,
            'total_completed' => $totalCompleted,
            'total_passed' => $totalPassed,
            'overall_completion_rate' => $totalStudents > 0 ? round(($totalCompleted / $totalStudents) * 100, 2) : 0,
            'overall_pass_rate' => $totalCompleted > 0 ? round(($totalPassed / $totalCompleted) * 100, 2) : 0,
            'class_stats' => $stats,
        ]);
    }

    /**
     * Bulk assign a quiz to multiple classes.
     */
    public function bulkAssignToClasses(Request $request, Training $training, Quiz $quiz): JsonResponse
    {
        $validated = $request->validate([
            'class_ids' => 'required|array',
            'class_ids.*' => 'exists:training_classes,id',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after_or_equal:available_from',
        ]);

        $assignedCount = 0;
        $errors = [];

        foreach ($validated['class_ids'] as $classId) {
            $trainingClass = TrainingClass::find($classId);

            // Check if the class belongs to the same training
            if ($trainingClass->training_id !== $quiz->training_id) {
                $errors[] = "La classe {$trainingClass->name} n'appartient pas à la même formation.";
                continue;
            }

            // Check if already assigned
            if ($quiz->trainingClasses()->where('training_class_id', $classId)->exists()) {
                $errors[] = "Le quiz est déjà assigné à la classe {$trainingClass->name}.";
                continue;
            }

            $quiz->trainingClasses()->attach($classId, [
                'assigned_at' => now(),
                'available_from' => $validated['available_from'] ?? null,
                'available_until' => $validated['available_until'] ?? null,
                'is_active' => true,
            ]);

            $assignedCount++;
        }

        return response()->json([
            'success' => $assignedCount > 0,
            'message' => "Quiz assigné à {$assignedCount} classe(s).",
            'assigned_count' => $assignedCount,
            'errors' => $errors,
        ]);
    }
}