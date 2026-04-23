<?php

namespace App\Exceptions\Agile;

use App\Models\Sprint;
use RuntimeException;

class ActiveSprintAlreadyExistsException extends RuntimeException
{
    public function __construct(
        public readonly Sprint $attempted,
        public readonly Sprint $conflicting,
    ) {
        parent::__construct(
            __('agile.errors.active_sprint_already_exists', ['name' => $conflicting->name])
        );
    }
}
