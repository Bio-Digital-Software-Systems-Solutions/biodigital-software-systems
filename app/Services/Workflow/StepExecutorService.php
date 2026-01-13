<?php

namespace App\Services\Workflow;

use App\Enums\Workflow\ApprovalType;
use App\Enums\Workflow\StepInstanceStatus;
use App\Enums\Workflow\StepType;
use App\Enums\Workflow\TimeoutAction;
use App\Models\StepApproval;
use App\Models\WorkflowActivityLog;
use App\Models\WorkflowStepInstance;
use Illuminate\Support\Facades\Log;

class StepExecutorService
{
    /**
     * Execute a step instance.
     */
    public function execute(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;

        try {
            $stepInstance->start();

            match ($step->type) {
                StepType::START => $this->executeStart($stepInstance),
                StepType::END => $this->executeEnd($stepInstance),
                StepType::TASK => $this->executeTask($stepInstance),
                StepType::APPROVAL => $this->executeApproval($stepInstance),
                StepType::FORM => $this->executeForm($stepInstance),
                StepType::CONDITION => $this->executeCondition($stepInstance),
                StepType::PARALLEL => $this->executeParallel($stepInstance),
                StepType::NOTIFICATION => $this->executeNotification($stepInstance),
                StepType::DELAY => $this->executeDelay($stepInstance),
                StepType::SCRIPT => $this->executeScript($stepInstance),
                StepType::SUB_WORKFLOW => $this->executeSubWorkflow($stepInstance),
            };
        } catch (\Exception $e) {
            Log::error('Step execution failed', [
                'step_instance_id' => $stepInstance->id,
                'step_type' => $step->type->value,
                'error' => $e->getMessage(),
            ]);

            if ($stepInstance->canRetry()) {
                // Will be retried later
                $stepInstance->update(['status' => StepInstanceStatus::PENDING]);
            } else {
                $stepInstance->fail($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        }
    }

    /**
     * Execute start step - immediately complete.
     */
    protected function executeStart(WorkflowStepInstance $stepInstance): void
    {
        $stepInstance->complete();
    }

    /**
     * Execute end step - marks workflow as complete.
     */
    protected function executeEnd(WorkflowStepInstance $stepInstance): void
    {
        $stepInstance->complete($stepInstance->workflowInstance->context ?? []);
    }

    /**
     * Execute task step - waits for user action.
     */
    protected function executeTask(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $config = $step->config ?? [];

        // Assign to user if specified
        if (!empty($config['assignee_id'])) {
            $stepInstance->assign($config['assignee_id']);
        } elseif (!empty($config['assignee_role'])) {
            // Assign to first user with role
            $user = \App\Models\User::role($config['assignee_role'])->first();
            if ($user) {
                $stepInstance->assign($user->id);
            }
        }

        // Task remains active until manually completed
        // Status is already 'active' from start()
    }

    /**
     * Execute approval step - creates approval requests.
     */
    protected function executeApproval(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $approvers = $step->getApproverUsers();

        if ($approvers->isEmpty()) {
            // No approvers, auto-approve
            $stepInstance->updateContext(['approval_result' => 'approved']);
            $stepInstance->complete();
            return;
        }

        $approvalType = $step->approval_type ?? ApprovalType::ANY;
        $order = 0;

        foreach ($approvers as $approver) {
            StepApproval::create([
                'step_instance_id' => $stepInstance->id,
                'approver_id' => $approver->id,
                'order' => $approvalType === ApprovalType::SEQUENTIAL ? $order++ : 0,
                'is_required' => true,
                'due_at' => $step->timeout_hours ? now()->addHours($step->timeout_hours) : null,
            ]);
        }

        // Notify first approver(s)
        $this->notifyApprovers($stepInstance);
    }

    /**
     * Process an approval decision.
     */
    public function processApproval(StepApproval $approval, string $decision, ?string $comments = null): void
    {
        $stepInstance = $approval->stepInstance;
        $step = $stepInstance->step;

        // Record the decision
        match ($decision) {
            'approved' => $approval->approve($comments),
            'rejected' => $approval->reject($comments),
            'abstained' => $approval->abstain($comments),
            default => throw new \Exception("Unknown approval decision: {$decision}"),
        };

        WorkflowActivityLog::approvalDecision($approval, $decision);

        // Check if approval step is complete
        $this->checkApprovalCompletion($stepInstance);
    }

    /**
     * Check if approval step is complete based on approval type.
     */
    protected function checkApprovalCompletion(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $approvalType = $step->approval_type ?? ApprovalType::ANY;
        $approvals = $stepInstance->approvals;

        $totalApprovals = $approvals->count();
        $decidedApprovals = $approvals->whereNotNull('decision');
        $approvedCount = $decidedApprovals->where('decision', 'approved')->count();
        $rejectedCount = $decidedApprovals->where('decision', 'rejected')->count();

        $isComplete = false;
        $result = null;

        switch ($approvalType) {
            case ApprovalType::ANY:
                // First approval or rejection decides
                if ($approvedCount > 0) {
                    $isComplete = true;
                    $result = 'approved';
                } elseif ($rejectedCount > 0) {
                    $isComplete = true;
                    $result = 'rejected';
                }
                break;

            case ApprovalType::ALL:
                // All must approve, one rejection fails
                if ($rejectedCount > 0) {
                    $isComplete = true;
                    $result = 'rejected';
                } elseif ($approvedCount === $totalApprovals) {
                    $isComplete = true;
                    $result = 'approved';
                }
                break;

            case ApprovalType::MAJORITY:
                // Majority decides
                $threshold = ceil($totalApprovals / 2);
                if ($approvedCount >= $threshold) {
                    $isComplete = true;
                    $result = 'approved';
                } elseif ($rejectedCount >= $threshold) {
                    $isComplete = true;
                    $result = 'rejected';
                }
                break;

            case ApprovalType::SEQUENTIAL:
                // Current approver must decide before next
                $minApprovals = $step->min_approvals ?? $totalApprovals;
                $pendingApprovals = $approvals->whereNull('decision')->sortBy('order');

                if ($rejectedCount > 0) {
                    $isComplete = true;
                    $result = 'rejected';
                } elseif ($approvedCount >= $minApprovals) {
                    $isComplete = true;
                    $result = 'approved';
                } elseif ($pendingApprovals->isNotEmpty()) {
                    // Notify next approver in sequence
                    $nextApproval = $pendingApprovals->first();
                    $nextApproval->notify();
                    // TODO: Send notification to next approver
                }
                break;
        }

        if ($isComplete) {
            $stepInstance->updateContext(['approval_result' => $result]);
            $stepInstance->complete(['approval_result' => $result]);
        }
    }

    /**
     * Execute form step - waits for form submission.
     */
    protected function executeForm(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;

        if (!$step->form_id) {
            $stepInstance->fail('No form configured for this step');
            return;
        }

        // Form step remains active until form is submitted
        // Status is already 'active' from start()
    }

    /**
     * Process a form submission for a step.
     */
    public function processFormSubmission(WorkflowStepInstance $stepInstance, array $formData): void
    {
        $stepInstance->updateContext(['form_data' => $formData]);
        $stepInstance->complete(['form_data' => $formData]);

        // Update workflow instance context
        $stepInstance->workflowInstance->updateContext(['form_data' => $formData]);
    }

    /**
     * Execute condition step - evaluates conditions and routes accordingly.
     */
    protected function executeCondition(WorkflowStepInstance $stepInstance): void
    {
        // Condition steps complete immediately
        // Routing is handled by transition evaluation
        $stepInstance->complete();
    }

    /**
     * Execute parallel step - creates parallel branches.
     */
    protected function executeParallel(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $config = $step->config ?? [];
        $branches = $config['branches'] ?? [];

        if (empty($branches)) {
            $stepInstance->complete();
            return;
        }

        // Mark as waiting for parallel branches
        $stepInstance->waitForSubWorkflow();

        // The parallel execution is handled by the workflow service
        // which will follow all outgoing transitions
    }

    /**
     * Execute notification step - sends notifications.
     */
    protected function executeNotification(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $config = $step->config ?? [];

        // TODO: Implement actual notification sending
        // For now, just log and complete
        Log::info('Notification step executed', [
            'step_instance_id' => $stepInstance->id,
            'config' => $config,
        ]);

        $stepInstance->complete();
    }

    /**
     * Execute delay step - schedules delayed execution.
     */
    protected function executeDelay(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $config = $step->config ?? [];
        $delayMinutes = $config['delay_minutes'] ?? 0;

        if ($delayMinutes <= 0) {
            $stepInstance->complete();
            return;
        }

        // Set due date and mark as waiting
        $stepInstance->update([
            'due_at' => now()->addMinutes($delayMinutes),
            'status' => StepInstanceStatus::WAITING,
        ]);

        // A scheduled job will complete this step when due
    }

    /**
     * Execute script step - runs custom logic.
     */
    protected function executeScript(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $config = $step->config ?? [];
        $script = $config['script'] ?? '';

        // For security, script execution should be carefully controlled
        // This is a placeholder for custom script logic

        Log::info('Script step executed', [
            'step_instance_id' => $stepInstance->id,
            'script' => $script,
        ]);

        $stepInstance->complete();
    }

    /**
     * Execute sub-workflow step - starts a child workflow.
     */
    protected function executeSubWorkflow(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $config = $step->config ?? [];
        $subWorkflowId = $config['workflow_id'] ?? null;

        if (!$subWorkflowId) {
            $stepInstance->fail('No sub-workflow configured');
            return;
        }

        $subWorkflow = \App\Models\DepartmentWorkflow::find($subWorkflowId);
        if (!$subWorkflow || !$subWorkflow->isActive()) {
            $stepInstance->fail('Sub-workflow not found or inactive');
            return;
        }

        $stepInstance->waitForSubWorkflow();

        // Start the sub-workflow
        $workflowService = app(WorkflowService::class);
        $workflowService->startWorkflow(
            $subWorkflow,
            $stepInstance->workflowInstance->started_by,
            $stepInstance->input_data ?? [],
            $stepInstance->workflow_instance_id,
            $stepInstance->id
        );
    }

    /**
     * Handle step timeout.
     */
    public function handleTimeout(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $timeoutAction = $step->timeout_action ?? TimeoutAction::FAIL;

        match ($timeoutAction) {
            TimeoutAction::ESCALATE => $this->escalateStep($stepInstance),
            TimeoutAction::SKIP => $stepInstance->skip(),
            TimeoutAction::FAIL => $stepInstance->fail('Step timed out'),
            TimeoutAction::AUTO_APPROVE => $this->autoApprove($stepInstance, true),
            TimeoutAction::AUTO_REJECT => $this->autoApprove($stepInstance, false),
            TimeoutAction::NOTIFY => $this->notifyTimeout($stepInstance),
            TimeoutAction::REASSIGN => $this->reassignStep($stepInstance),
        };
    }

    /**
     * Escalate a step to the escalation user.
     */
    protected function escalateStep(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;

        if ($step->escalation_user_id) {
            $stepInstance->escalate($step->escalation_user_id);
            // TODO: Send notification to escalation user
        } else {
            $stepInstance->fail('No escalation user configured');
        }
    }

    /**
     * Auto-approve or auto-reject a step.
     */
    protected function autoApprove(WorkflowStepInstance $stepInstance, bool $approve): void
    {
        $result = $approve ? 'approved' : 'rejected';
        $stepInstance->updateContext(['approval_result' => $result, 'auto_decided' => true]);
        $stepInstance->complete(['approval_result' => $result]);
    }

    /**
     * Notify about timeout.
     */
    protected function notifyTimeout(WorkflowStepInstance $stepInstance): void
    {
        // TODO: Send timeout notification
        Log::warning('Step timeout notification', [
            'step_instance_id' => $stepInstance->id,
        ]);
    }

    /**
     * Reassign step to another user.
     */
    protected function reassignStep(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $config = $step->config ?? [];
        $fallbackUserId = $config['fallback_assignee_id'] ?? null;

        if ($fallbackUserId) {
            $stepInstance->assign($fallbackUserId);
            // TODO: Send notification to new assignee
        } else {
            $stepInstance->fail('No fallback assignee configured');
        }
    }

    /**
     * Notify approvers about pending approval.
     */
    protected function notifyApprovers(WorkflowStepInstance $stepInstance): void
    {
        $step = $stepInstance->step;
        $approvalType = $step->approval_type ?? ApprovalType::ANY;

        $approvalsToNotify = $stepInstance->approvals()
            ->whereNull('notified_at');

        if ($approvalType === ApprovalType::SEQUENTIAL) {
            $approvalsToNotify = $approvalsToNotify->orderBy('order')->limit(1);
        }

        foreach ($approvalsToNotify->get() as $approval) {
            $approval->notify();
            // TODO: Send actual notification (email, push, etc.)
        }
    }
}
