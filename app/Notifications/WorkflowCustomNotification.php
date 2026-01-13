<?php

namespace App\Notifications;

use App\Models\WorkflowStepInstance;
use App\Models\WorkflowInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowCustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subject,
        public string $message,
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
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting("Bonjour {$notifiable->name},")
            ->line($this->message)
            ->action('Voir le workflow', url("/workflow-instances/{$this->workflowInstance->uuid}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'workflow_custom_notification',
            'workflow_instance_id' => $this->workflowInstance->id,
            'workflow_instance_uuid' => $this->workflowInstance->uuid,
            'workflow_name' => $this->workflowInstance->workflow->name,
            'step_instance_id' => $this->stepInstance->id,
            'step_instance_uuid' => $this->stepInstance->uuid,
            'step_name' => $this->stepInstance->step->name,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
