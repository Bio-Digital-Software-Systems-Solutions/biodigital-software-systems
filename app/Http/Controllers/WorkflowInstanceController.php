<?php

namespace App\Http\Controllers;

use App\Models\StepApproval;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStepInstance;
use App\Services\Workflow\StepExecutorService;
use App\Services\Workflow\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkflowInstanceController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected StepExecutorService $stepExecutor
    ) {}

    /**
     * Display a listing of workflow instances.
     */
    public function index(Request $request)
    {
        $query = WorkflowInstance::with(['workflow', 'department', 'starter'])
            ->withCount('stepInstances');

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('workflow_id')) {
            $query->where('workflow_id', $request->workflow_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('started_by')) {
            $query->where('started_by', $request->started_by);
        }

        $instances = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('WorkflowInstances/Index', [
            'instances' => $instances,
            'filters' => $request->only(['department_id', 'workflow_id', 'status', 'started_by']),
        ]);
    }

    /**
     * Display the specified workflow instance.
     */
    public function show(WorkflowInstance $workflowInstance)
    {
        $workflowInstance->load([
            'workflow.steps',
            'department',
            'starter',
            'stepInstances.step',
            'stepInstances.assignedUser',
            'stepInstances.approvals.approver',
            'activityLogs.user',
        ]);

        $currentStep = $workflowInstance->getCurrentStep();

        return Inertia::render('WorkflowInstances/Show', [
            'instance' => $workflowInstance,
            'currentStep' => $currentStep,
            'progress' => $workflowInstance->getProgress(),
        ]);
    }

    /**
     * Cancel a workflow instance.
     */
    public function cancel(Request $request, WorkflowInstance $workflowInstance)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $this->workflowService->cancelWorkflow($workflowInstance, $validated['reason'] ?? null);
            return back()->with('success', 'Workflow cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pause a workflow instance.
     */
    public function pause(WorkflowInstance $workflowInstance)
    {
        try {
            $this->workflowService->pauseWorkflow($workflowInstance);
            return back()->with('success', 'Workflow paused successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Resume a workflow instance.
     */
    public function resume(WorkflowInstance $workflowInstance)
    {
        try {
            $this->workflowService->resumeWorkflow($workflowInstance);
            return back()->with('success', 'Workflow resumed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete a task step.
     */
    public function completeStep(Request $request, WorkflowStepInstance $stepInstance)
    {
        $validated = $request->validate([
            'output_data' => 'nullable|array',
        ]);

        $stepInstance->complete($validated['output_data'] ?? [], Auth::id());
        $this->workflowService->executeNextSteps($stepInstance->workflowInstance);

        return back()->with('success', 'Step completed successfully.');
    }

    /**
     * Submit form for a form step.
     */
    public function submitForm(Request $request, WorkflowStepInstance $stepInstance)
    {
        $validated = $request->validate([
            'form_data' => 'required|array',
        ]);

        $this->stepExecutor->processFormSubmission($stepInstance, $validated['form_data']);
        $this->workflowService->executeNextSteps($stepInstance->workflowInstance);

        return back()->with('success', 'Form submitted successfully.');
    }

    /**
     * Submit approval decision.
     */
    public function submitApproval(Request $request, StepApproval $approval)
    {
        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected,abstained',
            'comments' => 'nullable|string|max:1000',
        ]);

        $this->stepExecutor->processApproval(
            $approval,
            $validated['decision'],
            $validated['comments'] ?? null
        );

        $stepInstance = $approval->stepInstance;
        if ($stepInstance->isCompleted()) {
            $this->workflowService->executeNextSteps($stepInstance->workflowInstance);
        }

        return back()->with('success', 'Approval submitted successfully.');
    }

    /**
     * Delegate approval to another user.
     */
    public function delegateApproval(Request $request, StepApproval $approval)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $approval->delegate($validated['user_id'], $validated['reason'] ?? null);

        return back()->with('success', 'Approval delegated successfully.');
    }

    /**
     * Get pending approvals for current user.
     */
    public function myApprovals(Request $request)
    {
        $approvals = StepApproval::with([
            'stepInstance.workflowInstance.workflow',
            'stepInstance.step',
        ])
            ->where('approver_id', Auth::id())
            ->whereNull('decision')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('WorkflowInstances/MyApprovals', [
            'approvals' => $approvals,
        ]);
    }

    /**
     * Get my tasks (assigned step instances).
     */
    public function myTasks(Request $request)
    {
        $tasks = WorkflowStepInstance::with([
            'workflowInstance.workflow',
            'step',
        ])
            ->where('assigned_to', Auth::id())
            ->whereIn('status', ['pending', 'active'])
            ->orderBy('due_at', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('WorkflowInstances/MyTasks', [
            'tasks' => $tasks,
        ]);
    }

    /**
     * Get workflow instance activity log.
     */
    public function activityLog(WorkflowInstance $workflowInstance)
    {
        $logs = $workflowInstance->activityLogs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('WorkflowInstances/ActivityLog', [
            'instance' => $workflowInstance,
            'logs' => $logs,
        ]);
    }
}
