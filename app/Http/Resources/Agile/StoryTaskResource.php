<?php

namespace App\Http\Resources\Agile;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Agile-context wrapper over the legacy Task model. Only the subset of
 * columns that make sense for a story-level technical task is exposed.
 *
 * @mixin Task
 */
class StoryTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_story_id' => $this->taskable_id,
            'title' => $this->title,
            'description' => $this->description,
            'work_type' => $this->work_type?->value,
            'work_type_label' => $this->work_type?->label(),
            'status_id' => $this->status_id,
            'priority' => $this->priority,
            'assigned_to' => $this->assigned_to,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
