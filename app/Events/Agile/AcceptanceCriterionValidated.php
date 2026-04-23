<?php

namespace App\Events\Agile;

use App\Models\Agile\AcceptanceCriterion;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcceptanceCriterionValidated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AcceptanceCriterion $criterion,
        public readonly User $validator,
        public readonly ?string $notes = null,
    ) {}
}
