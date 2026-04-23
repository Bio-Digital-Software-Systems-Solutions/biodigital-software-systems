<?php

namespace App\Http\Resources\Agile;

use App\Models\Agile\Epic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Epic
 */
class EpicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'project_id' => $this->project_id,
            'owner_id' => $this->owner_id,
            'title' => $this->title,
            'description' => $this->description,
            'business_value' => $this->business_value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority,
            'target_date' => $this->target_date?->toDateString(),
            'labels' => $this->labels ?? [],
            'user_stories_count' => $this->whenCounted('userStories'),
            'user_stories' => UserStoryResource::collection($this->whenLoaded('userStories')),
            'completion_percentage' => $this->completionPercentage(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => trim(($this->owner->first_name ?? '').' '.($this->owner->last_name ?? '')) ?: $this->owner->email,
            ]),
        ];
    }
}
