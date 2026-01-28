<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentPosition;
use Illuminate\Http\Request;

class DepartmentPositionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage departments');
    }

    public function index(Department $department)
    {
        $positions = $department->positions()
            ->ordered()
            ->get();

        return response()->json($positions);
    }

    public function store(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'min_staff' => 'nullable|integer|min:0',
            'max_staff' => 'nullable|integer|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Check code uniqueness within department if provided
        if (! empty($validated['code'])) {
            $exists = $department->positions()
                ->where('code', $validated['code'])
                ->exists();

            if ($exists) {
                return back()->withErrors(['code' => 'Ce code est déjà utilisé dans ce département.']);
            }
        }

        $maxSortOrder = $department->positions()->max('sort_order') ?? 0;

        $position = $department->positions()->create([
            ...$validated,
            'sort_order' => $maxSortOrder + 1,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Poste créé avec succès.');
    }

    public function update(Request $request, Department $department, DepartmentPosition $position)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'min_staff' => 'nullable|integer|min:0',
            'max_staff' => 'nullable|integer|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Check code uniqueness within department if provided (excluding current)
        if (! empty($validated['code'])) {
            $exists = $department->positions()
                ->where('code', $validated['code'])
                ->where('id', '!=', $position->id)
                ->exists();

            if ($exists) {
                return back()->withErrors(['code' => 'Ce code est déjà utilisé dans ce département.']);
            }
        }

        $position->update($validated);

        return back()->with('success', 'Poste mis à jour avec succès.');
    }

    public function destroy(Department $department, DepartmentPosition $position)
    {
        // Check if position has active nominations
        $hasNominations = $position->nominations ?? false;
        if ($hasNominations && $position->nominations()->active()->exists()) {
            return back()->withErrors(['position' => 'Ce poste a des nominations actives. Retirez-les d\'abord.']);
        }

        $position->delete();

        return back()->with('success', 'Poste supprimé avec succès.');
    }

    public function reorder(Request $request, Department $department)
    {
        $validated = $request->validate([
            'positions' => 'required|array',
            'positions.*.id' => 'required|exists:department_positions,id',
            'positions.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['positions'] as $item) {
            DepartmentPosition::where('id', $item['id'])
                ->where('department_id', $department->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Ordre des postes mis à jour.');
    }
}
