<?php

namespace App\Events;

use App\Models\DepartmentNeed;
use App\Enums\Need\NeedStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NeedStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DepartmentNeed $need,
        public NeedStatus $previousStatus,
        public NeedStatus $newStatus,
        public ?int $changedById = null,
        public ?string $comment = null
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("needs.{$this->need->department_id}"),
        ];

        if ($this->need->created_by_id) {
            $channels[] = new PrivateChannel("user.{$this->need->created_by_id}");
        }

        if ($this->need->assigned_to_id) {
            $channels[] = new PrivateChannel("user.{$this->need->assigned_to_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'need.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'need_id' => $this->need->id,
            'need_uuid' => $this->need->uuid,
            'need_title' => $this->need->title,
            'need_reference' => $this->need->reference,
            'previous_status' => $this->previousStatus->value,
            'new_status' => $this->newStatus->value,
            'changed_by_id' => $this->changedById,
            'comment' => $this->comment,
        ];
    }
}
