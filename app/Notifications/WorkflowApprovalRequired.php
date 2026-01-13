<?php

namespace App\Notifications;

use App\Models\WorkflowStepInstance;
use App\Models\WorkflowInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowApprovalRequired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public WorkflowStepInstance $stepInstance,
        public WorkflowInstance $workflowInstance,
        public array $channels = ['database', 'mail']
    ) {}

    public function via(object $notifiable): array
    {
        return array_intersect($this->channels, ['database', 'mail']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workflow = $this->workflowInstance->workflow;
        $step = $this->stepInstance->step;
        $initiator = $this->workflowInstance->initiatedBy;

        return (new MailMessage)
            ->subject("Approbation requise: {$workflow->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une demande d'approbation attend votre décision.")
            ->line("**Workflow:** {$workflow->name}")
            ->line("**Étape:** {$step->name}")
            ->when($initiator, function ($mail) use ($initiator) {
                return $mail->line("**Initié par:** {$initiator->name}");
            })
            ->when($step->description, function ($mail) use ($step) {
                return $mail->line("**Description:** {$step->description}");
            })
            ->action('Approuver / Rejeter', url("/workflow-instances/{$this->workflowInstance->uuid}/approve/{$this->stepInstance->uuid}"))
            ->line('Merci de prendre une décision rapidement.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'workflow_approval_required',
            'workflow_instance_id' => $this->workflowInstance->id,
            'workflow_instance_uuid' => $this->workflowInstance->uuid,
            'workflow_name' => $this->workflowInstance->workflow->name,
            'step_instance_id' => $this->stepInstance->id,
            'step_instance_uuid' => $this->stepInstance->uuid,
            'step_name' => $this->stepInstance->step->name,
            'initiated_by' => $this->workflowInstance->initiatedBy?->name,
            'message' => "Approbation requise pour: {$this->workflowInstance->workflow->name}",
        ];
    }
}
