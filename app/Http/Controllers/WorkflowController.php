<?php

namespace App\Http\Controllers;

use App\Enums\Workflow\StepType;
use App\Enums\Workflow\WorkflowScope;
use App\Enums\Workflow\WorkflowStatus;
use App\Enums\Workflow\WorkflowTriggerType;
use App\Models\Department;
use App\Models\DepartmentWorkflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\WorkflowTransition;
use App\Services\Workflow\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    /**
     * Display a listing of workflows.
     */
    public function index(Request $request)
    {
        $query = DepartmentWorkflow::with(['department', 'creator'])
            ->withCount(['steps', 'instances']);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $workflows = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Workflows/Index', [
            'workflows' => $workflows,
            'departments' => Department::active()->ordered()->get(),
            'statuses' => WorkflowStatus::toSelectOptions(),
            'filters' => $request->only(['department_id', 'status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new workflow.
     */
    public function create(Request $request)
    {
        return Inertia::render('Workflows/Create', [
            'departments' => Department::active()->ordered()->get(),
            'triggerTypes' => WorkflowTriggerType::toSelectOptions(),
            'scopes' => WorkflowScope::toSelectOptions(),
            'stepTypes' => StepType::groupedOptions(),
            'departmentId' => $request->department_id,
        ]);
    }

    /**
     * Store a newly created workflow.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string',
            'scope' => 'required|string',
            'trigger_config' => 'nullable|array',
            'variables' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = WorkflowStatus::DRAFT;

        $workflow = $this->workflowService->createWorkflow($validated);

        return redirect()->route('workflows.edit', $workflow)
            ->with('success', 'Workflow created successfully.');
    }

    /**
     * Display the specified workflow.
     */
    public function show(DepartmentWorkflow $workflow)
    {
        $workflow->load([
            'department',
            'creator',
            'steps.form',
            'transitions',
        ]);

        $stats = $this->workflowService->getWorkflowStats($workflow);

        return Inertia::render('Workflows/Show', [
            'workflow' => $workflow,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the workflow (workflow builder).
     */
    public function edit(DepartmentWorkflow $workflow)
    {
        $workflow->load([
            'department',
            'steps.form',
            'transitions',
        ]);

        return Inertia::render('Workflows/Builder', [
            'workflow' => $workflow,
            'stepTypes' => StepType::groupedOptions(),
            'triggerTypes' => WorkflowTriggerType::toSelectOptions(),
            'scopes' => WorkflowScope::toSelectOptions(),
        ]);
    }

    /**
     * Update the specified workflow.
     */
    public function update(Request $request, DepartmentWorkflow $workflow)
    {
        if ($workflow->isActive()) {
            return back()->with('error', 'Cannot modify an active workflow. Deprecate it first.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'sometimes|required|string',
            'scope' => 'sometimes|required|string',
            'trigger_config' => 'nullable|array',
            'variables' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $this->workflowService->updateWorkflow($workflow, $validated);

        return back()->with('success', 'Workflow updated successfully.');
    }

    /**
     * Activate the workflow.
     */
    public function activate(DepartmentWorkflow $workflow)
    {
        try {
            $this->workflowService->activateWorkflow($workflow);
            return back()->with('success', 'Workflow activated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Deprecate the workflow.
     */
    public function deprecate(DepartmentWorkflow $workflow)
    {
        $workflow->deprecate();
        return back()->with('success', 'Workflow deprecated successfully.');
    }

    /**
     * Duplicate the workflow.
     */
    public function duplicate(DepartmentWorkflow $workflow)
    {
        $newWorkflow = $workflow->duplicate();
        return redirect()->route('workflows.edit', $newWorkflow)
            ->with('success', 'Workflow duplicated successfully.');
    }

    /**
     * Remove the specified workflow.
     */
    public function destroy(DepartmentWorkflow $workflow)
    {
        if ($workflow->isActive()) {
            return back()->with('error', 'Cannot delete an active workflow. Deprecate it first.');
        }

        if ($workflow->instances()->exists()) {
            return back()->with('error', 'Cannot delete workflow with existing instances.');
        }

        $workflow->delete();
        return redirect()->route('workflows.index')
            ->with('success', 'Workflow deleted successfully.');
    }

    /**
     * Save workflow steps and transitions.
     */
    public function saveSteps(Request $request, DepartmentWorkflow $workflow)
    {
        $validated = $request->validate([
            'steps' => 'required|array',
            'steps.*.uuid' => 'required|string',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.description' => 'nullable|string',
            'steps.*.type' => ['required', 'string', new \Illuminate\Validation\Rules\Enum(StepType::class)],
            'steps.*.order' => 'required|integer',
            'steps.*.position_x' => 'required|numeric',
            'steps.*.position_y' => 'required|numeric',
            'steps.*.is_start' => 'boolean',
            'steps.*.is_end' => 'boolean',
            'steps.*.config' => 'nullable|array',
            'steps.*.form_id' => 'nullable|integer|exists:department_forms,id',
            'steps.*.approval_type' => 'nullable|string',
            'steps.*.approvers' => 'nullable|array',
            'steps.*.timeout_hours' => 'nullable|integer',
            'steps.*.timeout_action' => 'nullable|string',
            'transitions' => 'present|array',
            'transitions.*.uuid' => 'required|string',
            'transitions.*.from_step_uuid' => 'required|string',
            'transitions.*.to_step_uuid' => 'required|string',
            'transitions.*.name' => 'nullable|string',
            'transitions.*.condition_type' => 'nullable|string',
            'transitions.*.condition_config' => 'nullable|array',
            'transitions.*.is_default' => 'boolean',
            'transitions.*.priority' => 'nullable|integer',
        ]);

        // Map UUIDs to IDs
        $stepUuidToId = [];

        // Delete existing steps and transitions
        $workflow->transitions()->delete();
        $workflow->steps()->delete();

        // Create new steps
        foreach ($validated['steps'] as $stepData) {
            $step = WorkflowStep::create([
                'uuid' => $stepData['uuid'],
                'workflow_id' => $workflow->id,
                'name' => $stepData['name'],
                'description' => $stepData['description'] ?? null,
                'type' => $stepData['type'],
                'order' => $stepData['order'],
                'position_x' => $stepData['position_x'],
                'position_y' => $stepData['position_y'],
                'is_start' => $stepData['is_start'] ?? false,
                'is_end' => $stepData['is_end'] ?? false,
                'config' => $stepData['config'] ?? null,
                'form_id' => $stepData['form_id'] ?? null,
                'approval_type' => $stepData['approval_type'] ?? null,
                'approvers' => $stepData['approvers'] ?? null,
                'timeout_hours' => $stepData['timeout_hours'] ?? null,
                'timeout_action' => $stepData['timeout_action'] ?? null,
            ]);

            $stepUuidToId[$stepData['uuid']] = $step->id;
        }

        // Create transitions
        foreach ($validated['transitions'] as $transitionData) {
            $fromStepId = $stepUuidToId[$transitionData['from_step_uuid']] ?? null;
            $toStepId = $stepUuidToId[$transitionData['to_step_uuid']] ?? null;

            if ($fromStepId && $toStepId) {
                WorkflowTransition::create([
                    'uuid' => $transitionData['uuid'],
                    'workflow_id' => $workflow->id,
                    'from_step_id' => $fromStepId,
                    'to_step_id' => $toStepId,
                    'name' => $transitionData['name'] ?? null,
                    'condition_type' => $transitionData['condition_type'] ?? 'always',
                    'condition_config' => $transitionData['condition_config'] ?? null,
                    'is_default' => $transitionData['is_default'] ?? false,
                    'priority' => $transitionData['priority'] ?? 0,
                ]);
            }
        }

        return back()->with('success', 'Workflow steps saved successfully.');
    }

    /**
     * Start a new workflow instance.
     */
    public function startInstance(Request $request, DepartmentWorkflow $workflow)
    {
        $validated = $request->validate([
            'input_data' => 'nullable|array',
        ]);

        try {
            $instance = $this->workflowService->startWorkflow(
                $workflow,
                Auth::id(),
                $validated['input_data'] ?? []
            );

            return redirect()->route('workflow-instances.show', $instance)
                ->with('success', 'Workflow started successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * List workflow instances.
     */
    public function instances(Request $request, DepartmentWorkflow $workflow)
    {
        $instances = $workflow->instances()
            ->with(['starter'])
            ->withCount('stepInstances')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Workflows/Instances', [
            'workflow' => $workflow,
            'instances' => $instances,
        ]);
    }
}
