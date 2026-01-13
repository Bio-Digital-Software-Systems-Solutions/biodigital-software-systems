<?php

namespace App\Notifications;

use App\Models\WorkflowStepInstance;
use App\Models\WorkflowInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowStepCompleted extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject("Étape terminée: {$step->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une étape de votre workflow a été complétée.")
            ->line("**Workflow:** {$workflow->name}")
            ->line("**Étape terminée:** {$step->name}")
            ->action('Voir le workflow', url("/workflow-instances/{$this->workflowInstance->uuid}"))
            ->line('Le workflow continue son exécution.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'workflow_step_completed',
            'workflow_instance_id' => $this->workflowInstance->id,
            'workflow_instance_uuid' => $this->workflowInstance->uuid,
            'workflow_name' => $this->workflowInstance->workflow->name,
            'step_instance_id' => $this->stepInstance->id,
            'step_instance_uuid' => $this->stepInstance->uuid,
            'step_name' => $this->stepInstance->step->name,
            'message' => "Étape terminée: {$this->stepInstance->step->name}",
        ];
    }
}
