<?php

namespace App\Services\Agile;

use App\Enums\Agile\AcceptanceCriterionStatus;
use App\Enums\Agile\TestScenarioExecutionStatus;
use App\Events\Agile\AcceptanceCriterionRejected;
use App\Events\Agile\AcceptanceCriterionValidated;
use App\Exceptions\Agile\AcceptanceCriterionHasPassedTestsException;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\UserStory;
use App\Models\User;

class AcceptanceCriterionValidationService
{
    public function validate(
        AcceptanceCriterion $criterion,
        User $validator,
        ?string $notes = null,
    ): AcceptanceCriterion {
        $criterion->forceFill([
            'status' => AcceptanceCriterionStatus::VALIDATED,
            'validated_by' => $validator->id,
            'validated_at' => now(),
            'validation_notes' => $notes,
        ])->save();

        event(new AcceptanceCriterionValidated($criterion, $validator, $notes));

        return $criterion;
    }

    public function reject(
        AcceptanceCriterion $criterion,
        User $validator,
        string $notes,
    ): AcceptanceCriterion {
        $criterion->forceFill([
            'status' => AcceptanceCriterionStatus::REJECTED,
            'validated_by' => $validator->id,
            'validated_at' => now(),
            'validation_notes' => $notes,
        ])->save();

        event(new AcceptanceCriterionRejected($criterion, $validator, $notes));

        return $criterion;
    }

    public function guardDelete(AcceptanceCriterion $criterion): void
    {
        $passed = $criterion->testScenarios()
            ->where('execution_status', TestScenarioExecutionStatus::PASSED->value)
            ->count();

        if ($passed > 0) {
            throw new AcceptanceCriterionHasPassedTestsException($criterion, $passed);
        }
    }

    /**
     * Reorder criteria of a story. $orderedIds must contain exactly the
     * ids of the story's existing criteria; silently ignores foreign ids.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(UserStory $story, array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $index => $id) {
            AcceptanceCriterion::query()
                ->where('id', $id)
                ->where('user_story_id', $story->id)
                ->update(['position' => $index + 1]);
        }
    }
}
