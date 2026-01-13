<?php

namespace App\Events;

use App\Models\WorkflowStepInstance;
use App\Models\WorkflowInstance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStepExecuted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WorkflowStepInstance $stepInstance,
        public WorkflowInstance $workflowInstance
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("workflow.{$this->workflowInstance->workflow_id}"),
            new PrivateChannel("user.{$this->workflowInstance->initiated_by_id}"),
        ];

        if ($this->stepInstance->assigned_to_id) {
            $channels[] = new PrivateChannel("user.{$this->stepInstance->assigned_to_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'workflow.step.executed';
    }

    public function broadcastWith(): array
    {
        return [
            'workflow_instance_id' => $this->workflowInstance->id,
            'workflow_instance_uuid' => $this->workflowInstance->uuid,
            'step_instance_id' => $this->stepInstance->id,
            'step_instance_uuid' => $this->stepInstance->uuid,
            'step_name' => $this->stepInstance->step->name,
            'step_type' => $this->stepInstance->step->type->value,
            'status' => $this->stepInstance->status->value,
            'completed_at' => $this->stepInstance->completed_at?->toISOString(),
        ];
    }
}
