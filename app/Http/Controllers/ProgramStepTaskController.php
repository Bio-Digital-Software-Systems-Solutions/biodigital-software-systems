<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramStep;
use App\Models\Status;
use App\Models\Task;
use Illuminate\Http\Request;

class ProgramStepTaskController extends Controller
{
    public function store(Request $request, Program $program, ProgramStep $step)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Get or create default status
        $status = Status::firstOrCreate(
            ['name' => 'todo'],
            ['description' => 'Task to do']
        );

        $validated['program_id'] = $program->id;
        $validated['program_step_id'] = $step->id;
        $validated['status_id'] = $status->id;

        $task = Task::create($validated);

        return back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Program $program, ProgramStep $step, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'status_id' => 'nullable|exists:statuses,id',
        ]);

        $task->update($validated);

        return back()->with('success', 'Task updated successfully.');
    }

    public function destroy(Program $program, ProgramStep $step, Task $task)
    {
        $task->delete();

        return back()->with('success', 'Task deleted successfully.');
    }

    public function updateStatus(Request $request, Program $program, ProgramStep $step, Task $task)
    {
        $validated = $request->validate([
            'status_id' => 'required|exists:statuses,id',
        ]);

        $task->update($validated);

        return back()->with('success', 'Task status updated successfully.');
    }
}
