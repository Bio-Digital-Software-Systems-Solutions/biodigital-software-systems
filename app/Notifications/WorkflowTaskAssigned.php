<?php

namespace App\Notifications;

use App\Models\WorkflowStepInstance;
use App\Models\WorkflowInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowTaskAssigned extends Notification implements ShouldQueue
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
            ->subject("Nouvelle tâche: {$step->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle tâche vous a été assignée dans le workflow \"{$workflow->name}\".")
            ->line("**Tâche:** {$step->name}")
            ->when($step->description, fn($mail) => $mail->line("**Description:** {$step->description}"))
            ->action('Voir la tâche', url("/workflow-instances/{$this->workflowInstance->uuid}/steps/{$this->stepInstance->uuid}"))
            ->line('Merci de traiter cette tâche dans les meilleurs délais.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'workflow_task_assigned',
            'workflow_instance_id' => $this->workflowInstance->id,
            'workflow_instance_uuid' => $this->workflowInstance->uuid,
            'workflow_name' => $this->workflowInstance->workflow->name,
            'step_instance_id' => $this->stepInstance->id,
            'step_instance_uuid' => $this->stepInstance->uuid,
            'step_name' => $this->stepInstance->step->name,
            'step_description' => $this->stepInstance->step->description,
            'message' => "Nouvelle tâche assignée: {$this->stepInstance->step->name}",
        ];
    }
}
