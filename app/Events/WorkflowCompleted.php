<?php

namespace App\Events;

use App\Models\WorkflowInstance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WorkflowInstance $workflowInstance
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("workflow.{$this->workflowInstance->workflow_id}"),
            new PrivateChannel("user.{$this->workflowInstance->initiated_by_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'workflow.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'workflow_instance_id' => $this->workflowInstance->id,
            'workflow_instance_uuid' => $this->workflowInstance->uuid,
            'workflow_name' => $this->workflowInstance->workflow->name,
            'status' => $this->workflowInstance->status->value,
            'completed_at' => $this->workflowInstance->completed_at?->toISOString(),
        ];
    }
}
