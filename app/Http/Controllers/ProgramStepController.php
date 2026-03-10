<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramStep;
use Illuminate\Http\Request;

class ProgramStepController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:create program steps')->only(['store']);
        $this->middleware('permission:edit programs')->only(['update', 'attachParticipant', 'detachParticipant']);
        $this->middleware('permission:delete programs')->only(['destroy']);
    }

    public function store(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'required|integer|min:1',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'duration_minutes' => 'required|integer|min:1',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $validated['program_id'] = $program->id;

        ProgramStep::create($validated);

        // Update program progress
        $program->update([
            'progress_percentage' => $program->calculateProgress(),
        ]);

        return back()->with('success', 'Program step created successfully.');
    }

    public function update(Request $request, Program $program, ProgramStep $step)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'required|integer|min:1',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'duration_minutes' => 'required|integer|min:1',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $step->update($validated);

        // Update program progress
        $program->update([
            'progress_percentage' => $program->calculateProgress(),
        ]);

        return back()->with('success', 'Program step updated successfully.');
    }

    public function destroy(Program $program, ProgramStep $step)
    {
        $step->delete();

        // Update program progress
        $program->update([
            'progress_percentage' => $program->calculateProgress(),
        ]);

        return back()->with('success', 'Program step deleted successfully.');
    }

    public function attachParticipant(Request $request, Program $program, ProgramStep $step)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_in_step' => 'required|string|max:255',
        ]);

        $step->users()->attach($validated['user_id'], [
            'role_in_step' => $validated['role_in_step'],
        ]);

        return back()->with('success', 'Participant added to step successfully.');
    }

    public function detachParticipant(Program $program, ProgramStep $step, $userId)
    {
        $step->users()->detach($userId);

        return back()->with('success', 'Participant removed from step successfully.');
    }
}
