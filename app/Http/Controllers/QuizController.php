<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function start(Request $request, Quiz $quiz): Response
    {
        $user = $request->user();

        // Vérifier si l'étudiant a déjà une tentative en cours
        $existingAttempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        // Vérifier si l'étudiant a déjà complété le quiz
        $completedAttempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->where('status', 'completed')
            ->first();

        if ($completedAttempt) {
            return back()->with('error', 'Vous avez déjà complété ce quiz.');
        }

        if (! $existingAttempt) {
            // Créer une nouvelle tentative
            $existingAttempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $user->id,
                'started_at' => now(),
                'time_remaining_seconds' => $quiz->duration_minutes * 60,
                'status' => 'in_progress',
            ]);
        }

        // Calculer le temps restant
        $elapsedSeconds = now()->diffInSeconds($existingAttempt->started_at);
        $timeRemainingSeconds = max(0, ($quiz->duration_minutes * 60) - $elapsedSeconds);

        // Si le temps est écoulé, marquer comme abandonné
        if ($timeRemainingSeconds <= 0) {
            $existingAttempt->update([
                'status' => 'abandoned',
                'completed_at' => now(),
            ]);

            return back()->with('error', 'Le temps pour ce quiz est écoulé.');
        }

        return Inertia::render('Quiz/Take', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'duration_minutes' => $quiz->duration_minutes,
                'max_score' => $quiz->max_score,
                'passing_score' => $quiz->passing_score,
            ],
            'attempt' => [
                'id' => $existingAttempt->id,
                'started_at' => $existingAttempt->started_at->toISOString(),
                'time_remaining_seconds' => $timeRemainingSeconds,
            ],
        ]);
    }

    public function submit(Request $request, QuizAttempt $attempt)
    {
        $user = $request->user();

        // Vérifier que c'est bien la tentative de l'utilisateur
        if ($attempt->student_id !== $user->id) {
            return back()->with('error', 'Tentative invalide.');
        }

        // Vérifier que la tentative est encore en cours
        if ($attempt->status !== 'in_progress') {
            return back()->with('error', 'Cette tentative est déjà terminée.');
        }

        $validated = $request->validate([
            'answers' => 'nullable|array',
            'score' => 'nullable|integer',
        ]);

        // Calculer le score (pour l'instant on accepte le score du frontend)
        // TODO: Implémenter la logique de calcul du score côté serveur
        $score = $validated['score'] ?? 0;

        $attempt->update([
            'completed_at' => now(),
            'status' => 'completed',
            'score' => $score,
            'answers' => $validated['answers'] ?? [],
        ]);

        return redirect()->route('student.dashboard')
            ->with('success', 'Quiz soumis avec succès! Score: '.$score.'/'.$attempt->quiz->max_score);
    }
}
