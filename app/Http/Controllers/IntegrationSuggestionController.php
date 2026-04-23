<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\IntegrationSuggestion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IntegrationSuggestionController extends Controller
{
    public function index(): JsonResponse
    {
        $suggestions = IntegrationSuggestion::with([
            'visitorVisit.visitor',
            'visitorVisit.visitable',
        ])
            ->where('suggested_to', Auth::id())
            ->pending()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (IntegrationSuggestion $suggestion) => [
                'uuid' => $suggestion->uuid,
                'visitor_name' => $suggestion->visitorVisit->visitor->name,
                'visitor_uuid' => $suggestion->visitorVisit->visitor->uuid,
                'group_or_department' => $suggestion->visitorVisit->visitable->name ?? 'N/A',
                'visitable_type' => class_basename($suggestion->visitorVisit->visitable_type),
                'score' => (float) $suggestion->score_at_suggestion,
                'status' => $suggestion->status,
                'created_at' => $suggestion->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json(['suggestions' => $suggestions]);
    }

    public function respond(Request $request, IntegrationSuggestion $suggestion): JsonResponse
    {
        if ($suggestion->suggested_to !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:accepted,rejected,deferred'],
            'response_notes' => ['nullable', 'string'],
        ]);

        $suggestion->update([
            'status' => $validated['status'],
            'responded_at' => now(),
            'response_notes' => $validated['response_notes'] ?? null,
        ]);

        if ($validated['status'] === 'accepted') {
            $this->integrateVisitor($suggestion);
        }

        return response()->json(['message' => 'Réponse enregistrée avec succès.']);
    }

    protected function integrateVisitor(IntegrationSuggestion $suggestion): void
    {
        $visit = $suggestion->visitorVisit;
        $visitor = $visit->visitor;

        $visit->update([
            'integration_status' => 'integrated',
        ]);

        $visitor->update([
            'status' => 'integrated',
        ]);

        if ($visit->visitable_type === Group::class) {
            $group = $visit->visitable;

            if ($visitor->user_id) {
                $user = User::find($visitor->user_id);
                if ($user && ! $group->isMember($user)) {
                    $group->users()->attach($user->id, ['joined_at' => now()]);
                }
            }
        }
    }
}
