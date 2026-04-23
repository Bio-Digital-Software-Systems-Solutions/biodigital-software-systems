<?php

namespace App\Http\Resources\Agile;

use App\Models\Agile\UserStory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserStory
 */
class UserStoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'epic_id' => $this->epic_id,
            'sprint_id' => $this->sprint_id,
            'assignee_id' => $this->assignee_id,
            'reporter_id' => $this->reporter_id,
            'title' => $this->title,
            'as_a' => $this->as_a,
            'i_want' => $this->i_want,
            'so_that' => $this->so_that,
            'story_points' => $this->story_points,
            'priority' => $this->priority,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'can_be_completed' => $this->whenLoaded('acceptanceCriteria', fn (): bool => $this->canBeCompleted()),
            'acceptance_criteria' => AcceptanceCriterionResource::collection($this->whenLoaded('acceptanceCriteria')),
            'acceptance_criteria_count' => $this->whenCounted('acceptanceCriteria'),
            'story_tasks_count' => $this->whenCounted('storyTasks'),
            'story_tasks' => StoryTaskResource::collection($this->whenLoaded('storyTasks')),
            'epic' => $this->whenLoaded('epic', fn () => $this->epic ? [
                'id' => $this->epic->id,
                'uuid' => $this->epic->uuid,
                'title' => $this->epic->title,
                'status' => $this->epic->status->value,
            ] : null),
            'sprint' => $this->whenLoaded('sprint', fn () => $this->sprint ? [
                'id' => $this->sprint->id,
                'name' => $this->sprint->name,
                'status' => $this->sprint->status,
            ] : null),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => trim(($this->assignee->first_name ?? '').' '.($this->assignee->last_name ?? '')) ?: $this->assignee->email,
            ] : null),
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter ? [
                'id' => $this->reporter->id,
                'name' => trim(($this->reporter->first_name ?? '').' '.($this->reporter->last_name ?? '')) ?: $this->reporter->email,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
