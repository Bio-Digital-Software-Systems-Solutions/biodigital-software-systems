<?php

namespace App\Http\Resources\Agile;

use App\Models\Agile\AcceptanceCriterion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcceptanceCriterion
 */
class AcceptanceCriterionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_story_id' => $this->user_story_id,
            'position' => $this->position,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'validated_by' => $this->validated_by,
            'validated_at' => $this->validated_at?->toIso8601String(),
            'validation_notes' => $this->validation_notes,
            'test_scenarios_count' => $this->whenCounted('testScenarios'),
            'test_scenarios' => TestScenarioResource::collection($this->whenLoaded('testScenarios')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'validator' => $this->whenLoaded('validatedBy', fn () => $this->validatedBy ? [
                'id' => $this->validatedBy->id,
                'name' => trim(($this->validatedBy->first_name ?? '').' '.($this->validatedBy->last_name ?? '')) ?: $this->validatedBy->email,
            ] : null),
        ];
    }
}
