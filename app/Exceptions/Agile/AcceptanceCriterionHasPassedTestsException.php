<?php

namespace App\Exceptions\Agile;

use App\Models\Agile\AcceptanceCriterion;
use RuntimeException;

class AcceptanceCriterionHasPassedTestsException extends RuntimeException
{
    public function __construct(
        public readonly AcceptanceCriterion $criterion,
        public readonly int $passedScenariosCount,
    ) {
        parent::__construct(
            __('agile.errors.acceptance_criterion_has_passed_tests', ['count' => $passedScenariosCount])
        );
    }
}
