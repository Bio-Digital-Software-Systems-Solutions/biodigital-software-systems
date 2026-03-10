<?php

namespace App\Jobs;

use App\Models\WorkflowStepInstance;
use App\Models\User;
use App\Notifications\WorkflowTaskAssigned;
use App\Notifications\WorkflowApprovalRequired;
use App\Notifications\WorkflowStepCompleted;
use App\Enums\Workflow\StepType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendWorkflowNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public WorkflowStepInstance $stepInstance,
        public string $notificationType,
        public array $additionalData = []
    ) {}

    public function handle(): void
    {
        $stepInstance = $this->stepInstance->fresh(['step', 'workflowInstance.workflow', 'assignedTo']);

        if (!$stepInstance) {
            Log::warning('Step instance not found for notification', ['id' => $this->stepInstance->id]);
            return;
        }

        $step = $stepInstance->step;
        $workflowInstance = $stepInstance->workflowInstance;
        $config = $step->config ?? [];

        // Determine recipients
        $recipients = $this->getRecipients($stepInstance, $config);

        if ($recipients->isEmpty()) {
            Log::info('No recipients for notification', [
                'step_instance_id' => $stepInstance->id,
                'notification_type' => $this->notificationType,
            ]);
            return;
        }

        // Get notification channels
        $channels = $config['channels'] ?? ['database', 'mail'];

        try {
            switch ($this->notificationType) {
                case 'task_assigned':
                    Notification::send($recipients, new WorkflowTaskAssigned(
                        $stepInstance,
                        $workflowInstance,
                        $channels
                    ));
                    break;

                case 'approval_required':
                    Notification::send($recipients, new WorkflowApprovalRequired(
                        $stepInstance,
                        $workflowInstance,
                        $channels
                    ));
                    break;

                case 'step_completed':
                    // Notify initiator about step completion
                    $initiator = User::find($workflowInstance->initiated_by_id);
                    if ($initiator) {
                        $initiator->notify(new WorkflowStepCompleted(
                            $stepInstance,
                            $workflowInstance,
                            $channels
                        ));
                    }
                    break;

                case 'custom':
                    // Handle custom notification step
                    $this->sendCustomNotification($stepInstance, $recipients, $config);
                    break;
            }

            Log::info('Workflow notification sent', [
                'step_instance_id' => $stepInstance->id,
                'notification_type' => $this->notificationType,
                'recipients_count' => $recipients->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send workflow notification', [
                'step_instance_id' => $stepInstance->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getRecipients(WorkflowStepInstance $stepInstance, array $config): \Illuminate\Support\Collection
    {
        $recipients = collect();

        // Add assigned user
        if ($stepInstance->assigned_to_id) {
            $recipients->push(User::find($stepInstance->assigned_to_id));
        }

        // Add users from config
        if (isset($config['notify_users']) && is_array($config['notify_users'])) {
            $users = User::whereIn('id', $config['notify_users'])->get();
            $recipients = $recipients->merge($users);
        }

        // Add users by role
        if (isset($config['notify_roles']) && is_array($config['notify_roles'])) {
            foreach ($config['notify_roles'] as $role) {
                $users = User::role($role)->get();
                $recipients = $recipients->merge($users);
            }
        }

        // Add department members
        if (isset($config['notify_department_id'])) {
            $users = User::where('department_id', $config['notify_department_id'])->get();
            $recipients = $recipients->merge($users);
        }

        // Add initiator if specified
        if (($config['notify_initiator'] ?? false) && $stepInstance->workflowInstance->initiated_by_id) {
            $recipients->push(User::find($stepInstance->workflowInstance->initiated_by_id));
        }

        return $recipients->filter()->unique('id');
    }

    private function sendCustomNotification(WorkflowStepInstance $stepInstance, \Illuminate\Support\Collection $recipients, array $config): void
    {
        $subject = $config['subject'] ?? 'Notification Workflow';
        $message = $config['message'] ?? '';

        // Replace placeholders in message
        $workflowInstance = $stepInstance->workflowInstance;
        $data = $workflowInstance->data ?? [];

        $message = $this->replacePlaceholders($message, [
            'workflow_name' => $workflowInstance->workflow->name,
            'step_name' => $stepInstance->step->name,
            'initiator_name' => $workflowInstance->initiatedBy?->name ?? 'Unknown',
            ...$data,
        ]);

        $subject = $this->replacePlaceholders($subject, [
            'workflow_name' => $workflowInstance->workflow->name,
            'step_name' => $stepInstance->step->name,
        ]);

        // Send notification
        Notification::send($recipients, new \App\Notifications\WorkflowCustomNotification(
            $subject,
            $message,
            $stepInstance,
            $workflowInstance,
            $config['channels'] ?? ['database', 'mail']
        ));
    }

    private function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace("{{$key}}", (string) $value, $text);
            }
        }
        return $text;
    }
}
