<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIntegrationPathwayRequest;
use App\Models\IntegrationPathwayStep;
use App\Models\IntegrationPathwayTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class IntegrationPathwayController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage integration pathways');
    }

    public function index()
    {
        $templates = IntegrationPathwayTemplate::with(['creator', 'steps'])
            ->withCount('steps')
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('IntegrationPathways/Index', [
            'templates' => $templates,
        ]);
    }

    public function store(StoreIntegrationPathwayRequest $request)
    {
        $validated = $request->validated();

        if (! empty($validated['is_default']) && $validated['is_default']) {
            IntegrationPathwayTemplate::where('target_type', $validated['target_type'] ?? null)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $template = IntegrationPathwayTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'target_type' => $validated['target_type'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => Auth::id(),
        ]);

        if (! empty($validated['steps'])) {
            foreach ($validated['steps'] as $stepData) {
                $template->steps()->create($stepData);
            }
        }

        return redirect()->route('integration-pathways.index')
            ->with('success', 'Parcours d\'intégration créé avec succès.');
    }

    public function show(IntegrationPathwayTemplate $template)
    {
        $template->load(['steps' => fn ($q) => $q->orderBy('order_index'), 'creator']);

        return Inertia::render('IntegrationPathways/Show', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, IntegrationPathwayTemplate $template)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_type' => ['nullable', 'in:group,department'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if (! empty($validated['is_default']) && $validated['is_default'] && ! $template->is_default) {
            IntegrationPathwayTemplate::where('target_type', $validated['target_type'] ?? null)
                ->where('is_default', true)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update($validated);

        return redirect()->route('integration-pathways.show', $template)
            ->with('success', 'Parcours d\'intégration mis à jour.');
    }

    public function destroy(IntegrationPathwayTemplate $template)
    {
        $template->delete();

        return redirect()->route('integration-pathways.index')
            ->with('success', 'Parcours d\'intégration supprimé.');
    }

    public function addStep(Request $request, IntegrationPathwayTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order_index' => ['required', 'integer', 'min:0'],
            'type' => ['required', 'in:attendance_count,activity_participation,meeting_attendance,training_completion,manual_approval,custom'],
            'criteria' => ['nullable', 'array'],
            'weight' => ['required', 'integer', 'min:1', 'max:10'],
            'is_required' => ['boolean'],
        ]);

        $step = $template->steps()->create($validated);

        return response()->json(['step' => $step, 'message' => 'Étape ajoutée avec succès.']);
    }

    public function updateStep(Request $request, IntegrationPathwayTemplate $template, IntegrationPathwayStep $step): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order_index' => ['required', 'integer', 'min:0'],
            'type' => ['required', 'in:attendance_count,activity_participation,meeting_attendance,training_completion,manual_approval,custom'],
            'criteria' => ['nullable', 'array'],
            'weight' => ['required', 'integer', 'min:1', 'max:10'],
            'is_required' => ['boolean'],
        ]);

        $step->update($validated);

        return response()->json(['step' => $step->fresh(), 'message' => 'Étape mise à jour.']);
    }

    public function removeStep(IntegrationPathwayTemplate $template, IntegrationPathwayStep $step): JsonResponse
    {
        $step->delete();

        return response()->json(['message' => 'Étape supprimée.']);
    }

    public function reorderSteps(Request $request, IntegrationPathwayTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'steps' => ['required', 'array'],
            'steps.*.id' => ['required', 'exists:integration_pathway_steps,id'],
            'steps.*.order_index' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['steps'] as $stepData) {
            IntegrationPathwayStep::where('id', $stepData['id'])
                ->where('template_id', $template->id)
                ->update(['order_index' => $stepData['order_index']]);
        }

        return response()->json(['message' => 'Ordre des étapes mis à jour.']);
    }
}
