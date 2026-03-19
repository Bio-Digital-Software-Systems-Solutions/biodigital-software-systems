<?php

namespace App\Http\Controllers;

use App\Enums\RoutineAssigneeRole;
use App\Enums\RoutineFrequency;
use App\Enums\RoutineSopStatus;
use App\Enums\RoutineStatus;
use App\Http\Requests\StoreRoutineRequest;
use App\Http\Requests\UpdateRoutineRequest;
use App\Models\Department;
use App\Models\Routine;
use App\Models\RoutineAssignee;
use App\Models\RoutineSop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoutineController extends Controller
{
    public function index(Request $request, Department $department): Response
    {
        $this->authorize('view', $department);

        $query = Routine::where('department_id', $department->id)
            ->with(['responsible', 'creator', 'approver'])
            ->withCount(['allSteps', 'allSops']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->input('frequency'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $routines = $query->orderBy('sort_order')->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Departments/Routines/Index', [
            'department' => $department,
            'routines' => $routines,
            'statuses' => collect(RoutineStatus::cases())->map(fn ($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'frequencies' => collect(RoutineFrequency::cases())->map(fn ($f): array => [
                'value' => $f->value,
                'label' => $f->label(),
            ]),
            'filters' => $request->only(['status', 'frequency', 'search']),
        ]);
    }

    public function create(Department $department): Response
    {
        $this->authorize('update', $department);

        $departmentUsers = $department->users()->select(['users.id', 'users.first_name', 'users.last_name', 'users.email'])->get();

        return Inertia::render('Departments/Routines/Create', [
            'department' => $department,
            'departmentUsers' => $departmentUsers,
            'frequencies' => collect(RoutineFrequency::cases())->map(fn ($f): array => [
                'value' => $f->value,
                'label' => $f->label(),
            ]),
        ]);
    }

    public function store(StoreRoutineRequest $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $routine = Routine::create([
            ...$request->validated(),
            'department_id' => $department->id,
            'created_by' => $request->user()->id,
            'status' => RoutineStatus::Draft,
        ]);

        return redirect()
            ->route('departments.routines.show', [$department, $routine])
            ->with('success', 'Routine créée avec succès.');
    }

    public function show(Department $department, Routine $routine): Response
    {
        $this->authorize('view', $department);

        $routine->load([
            'responsible',
            'creator',
            'approver',
            'steps.children.assignees.user',
            'steps.children.sops',
            'steps.assignees.user',
            'steps.sops',
            'assignees.user',
            'assignees.assignedByUser',
            'sops.uploader',
            'sops.routine',
            'steps.sops.routine',
            'steps.children.sops.routine',
        ]);

        $departmentUsers = $department->users()->select(['users.id', 'users.first_name', 'users.last_name', 'users.email'])->get();

        return Inertia::render('Departments/Routines/Show', [
            'department' => $department,
            'routine' => $routine,
            'departmentUsers' => $departmentUsers,
            'statuses' => collect(RoutineStatus::cases())->map(fn ($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
                'icon' => $s->icon(),
            ]),
            'frequencies' => collect(RoutineFrequency::cases())->map(fn ($f): array => [
                'value' => $f->value,
                'label' => $f->label(),
            ]),
            'assigneeRoles' => collect(RoutineAssigneeRole::cases())->map(fn ($r): array => [
                'value' => $r->value,
                'label' => $r->label(),
            ]),
            'sopStatuses' => collect(RoutineSopStatus::cases())->map(fn ($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'canManage' => $request = request()->user()?->can('manage departments') ?? false,
        ]);
    }

    public function edit(Department $department, Routine $routine): Response
    {
        $this->authorize('update', $department);

        if (! $routine->is_editable) {
            return redirect()
                ->route('departments.routines.show', [$department, $routine])
                ->with('error', 'Cette routine ne peut plus être modifiée.');
        }

        $departmentUsers = $department->users()->select(['users.id', 'users.first_name', 'users.last_name', 'users.email'])->get();

        return Inertia::render('Departments/Routines/Edit', [
            'department' => $department,
            'routine' => $routine,
            'departmentUsers' => $departmentUsers,
            'frequencies' => collect(RoutineFrequency::cases())->map(fn ($f): array => [
                'value' => $f->value,
                'label' => $f->label(),
            ]),
        ]);
    }

    public function update(UpdateRoutineRequest $request, Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        $routine->update($request->validated());

        return redirect()
            ->route('departments.routines.show', [$department, $routine])
            ->with('success', 'Routine mise à jour avec succès.');
    }

    public function destroy(Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($routine->status !== RoutineStatus::Draft) {
            return back()->with('error', 'Seules les routines en brouillon peuvent être supprimées.');
        }

        $routine->delete();

        return redirect()
            ->route('departments.routines.index', $department)
            ->with('success', 'Routine supprimée avec succès.');
    }

    // Status transitions

    public function submitForApproval(Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        if (! $routine->submitForApproval()) {
            return back()->with('error', 'Cette routine ne peut pas être soumise pour approbation.');
        }

        return back()->with('success', 'Routine soumise pour approbation.');
    }

    public function approve(Request $request, Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        if (! $routine->approve($request->user())) {
            return back()->with('error', 'Cette routine ne peut pas être approuvée.');
        }

        return back()->with('success', 'Routine approuvée avec succès.');
    }

    public function reject(Request $request, Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        if (! $routine->reject()) {
            return back()->with('error', 'Cette routine ne peut pas être rejetée.');
        }

        return back()->with('success', 'Routine rejetée et renvoyée en brouillon.');
    }

    public function activate(Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        if (! $routine->activate()) {
            return back()->with('error', 'Cette routine ne peut pas être activée.');
        }

        return back()->with('success', 'Routine activée avec succès.');
    }

    public function archive(Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        if (! $routine->archive()) {
            return back()->with('error', 'Cette routine ne peut pas être archivée.');
        }

        return back()->with('success', 'Routine archivée.');
    }

    // SOPs

    public function storeSop(Request $request, Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,gif,webp,mp4,webm,mov,avi'],
            'routine_step_id' => ['nullable', 'exists:routine_steps,id'],
        ]);

        $file = $request->file('file');
        $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $filePath = $file->storeAs("routines/sops/{$routine->id}", $fileName, 'public');

        RoutineSop::create([
            'routine_id' => $routine->id,
            'routine_step_id' => $request->input('routine_step_id'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'original_name' => $file->getClientOriginalName(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'extension' => strtolower($file->getClientOriginalExtension()),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'SOP ajoutée avec succès.');
    }

    public function downloadSop(Department $department, Routine $routine, RoutineSop $sop)
    {
        $this->authorize('view', $department);

        $path = Storage::disk('public')->path($sop->file_path);

        if (! file_exists($path)) {
            abort(404, 'Fichier non trouvé.');
        }

        $streamableExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'mov', 'mp3', 'wav', 'ogg'];

        if (in_array($sop->extension, $streamableExtensions)) {
            return response()->file($path);
        }

        return response()->download($path, $sop->original_name);
    }

    public function updateSopStatus(Request $request, Department $department, Routine $routine, RoutineSop $sop): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:draft,active,validated,obsolete,inactive'],
        ]);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'validated') {
            $updateData['validated_by'] = $request->user()->id;
            $updateData['validated_at'] = now();
        }

        $sop->update($updateData);

        return back()->with('success', 'Statut du SOP mis à jour.');
    }

    public function destroySop(Department $department, Routine $routine, RoutineSop $sop): RedirectResponse
    {
        $this->authorize('update', $department);

        Storage::disk('public')->delete($sop->file_path);
        $sop->delete();

        return back()->with('success', 'SOP supprimée avec succès.');
    }

    // Assignees

    public function addAssignee(Request $request, Department $department, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'string', 'in:assignee,validator,observer'],
            'routine_step_id' => ['nullable', 'exists:routine_steps,id'],
        ]);

        RoutineAssignee::updateOrCreate(
            [
                'routine_id' => $routine->id,
                'routine_step_id' => $validated['routine_step_id'] ?? null,
                'user_id' => $validated['user_id'],
                'role' => $validated['role'],
            ],
            [
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
            ]
        );

        return back()->with('success', 'Personne assignée avec succès.');
    }

    public function removeAssignee(Department $department, Routine $routine, RoutineAssignee $assignee): RedirectResponse
    {
        $this->authorize('update', $department);

        $assignee->delete();

        return back()->with('success', 'Assignation retirée.');
    }
}
