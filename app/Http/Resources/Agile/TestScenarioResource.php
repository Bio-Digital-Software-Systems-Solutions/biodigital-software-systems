<?php

namespace App\Http\Resources\Agile;

use App\Models\Agile\TestScenario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TestScenario
 */
class TestScenarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'acceptance_criterion_id' => $this->acceptance_criterion_id,
            'title' => $this->title,
            'given' => $this->given,
            'when' => $this->when,
            'then' => $this->then,
            'free_form' => $this->free_form,
            'automated_test_ref' => $this->automated_test_ref,
            'execution_status' => $this->execution_status->value,
            'execution_status_label' => $this->execution_status->label(),
            'last_executed_by' => $this->last_executed_by,
            'last_executed_at' => $this->last_executed_at?->toIso8601String(),
            'failure_notes' => $this->failure_notes,
            'is_gherkin' => $this->isGherkin(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
