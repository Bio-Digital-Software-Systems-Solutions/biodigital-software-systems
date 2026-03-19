<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoutineStepRequest;
use App\Http\Requests\UpdateRoutineStepRequest;
use App\Models\Department;
use App\Models\Routine;
use App\Models\RoutineStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoutineStepController extends Controller
{
    public function store(StoreRoutineStepRequest $request, Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        $maxOrder = $routine->allSteps()
            ->where('parent_id', $request->input('parent_id'))
            ->max('sort_order') ?? -1;

        RoutineStep::create([
            ...$request->validated(),
            'routine_id' => $routine->id,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Étape ajoutée avec succès.');
    }

    public function update(UpdateRoutineStepRequest $request, Department $department, Routine $routine, RoutineStep $step): RedirectResponse
    {
        $this->authorize('update', $department);

        $step->update($request->validated());

        return back()->with('success', 'Étape mise à jour avec succès.');
    }

    public function destroy(Department $department, Routine $routine, RoutineStep $step): RedirectResponse
    {
        $this->authorize('update', $department);

        $step->delete();

        return back()->with('success', 'Étape supprimée avec succès.');
    }

    public function reorder(Request $request, Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        $request->validate([
            'steps' => ['required', 'array'],
            'steps.*.id' => ['required', 'exists:routine_steps,id'],
            'steps.*.sort_order' => ['required', 'integer', 'min:0'],
            'steps.*.parent_id' => ['nullable', 'exists:routine_steps,id'],
        ]);

        foreach ($request->input('steps') as $stepData) {
            RoutineStep::where('id', $stepData['id'])
                ->where('routine_id', $routine->id)
                ->update([
                    'sort_order' => $stepData['sort_order'],
                    'parent_id' => $stepData['parent_id'] ?? null,
                ]);
        }

        return back()->with('success', 'Ordre des étapes mis à jour.');
    }

    public function validateStep(Request $request, Department $department, Routine $routine, RoutineStep $step): RedirectResponse
    {
        $this->authorize('update', $department);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $step->validateStep($request->user(), $request->input('notes'));

        return back()->with('success', 'Étape validée avec succès.');
    }

    public function rejectStep(Request $request, Department $department, Routine $routine, RoutineStep $step): RedirectResponse
    {
        $this->authorize('update', $department);

        $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        $step->rejectStep($request->user(), $request->input('notes'));

        return back()->with('success', 'Étape rejetée.');
    }
}
