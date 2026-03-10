<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentPositionNomination;
use Illuminate\Http\Request;

class DepartmentPositionNominationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage departments');
    }

    public function index(Department $department)
    {
        $nominations = DepartmentPositionNomination::with(['position', 'user', 'nominatedBy'])
            ->where('department_id', $department->id)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($nominations);
    }

    public function store(Request $request, Department $department)
    {
        $validated = $request->validate([
            'department_position_id' => 'required|exists:department_positions,id',
            'user_id' => 'required|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Ensure position belongs to this department
        $positionBelongs = $department->positions()->where('id', $validated['department_position_id'])->exists();
        if (! $positionBelongs) {
            return back()->withErrors(['department_position_id' => 'Ce poste n\'appartient pas à ce département.']);
        }

        // Ensure user is a member of the department
        $isMember = $department->users()->where('users.id', $validated['user_id'])->exists();
        if (! $isMember) {
            return back()->withErrors(['user_id' => 'Cet utilisateur n\'est pas membre de ce département.']);
        }

        // Check for existing active nomination for same position + user
        $exists = DepartmentPositionNomination::where('department_position_id', $validated['department_position_id'])
            ->where('user_id', $validated['user_id'])
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            return back()->withErrors(['user_id' => 'Cet utilisateur est déjà nommé à ce poste.']);
        }

        DepartmentPositionNomination::create([
            ...$validated,
            'department_id' => $department->id,
            'nominated_by' => auth()->id(),
            'is_active' => true,
        ]);

        return back()->with('success', 'Nomination créée avec succès.');
    }

    public function update(Request $request, Department $department, DepartmentPositionNomination $nomination)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $nomination->update($validated);

        return back()->with('success', 'Nomination mise à jour avec succès.');
    }

    public function destroy(Department $department, DepartmentPositionNomination $nomination)
    {
        $nomination->update(['is_active' => false]);
        $nomination->delete();

        return back()->with('success', 'Nomination retirée avec succès.');
    }
}
