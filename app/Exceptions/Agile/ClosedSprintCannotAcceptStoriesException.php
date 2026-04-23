<?php

namespace App\Exceptions\Agile;

use App\Models\Sprint;
use RuntimeException;

class ClosedSprintCannotAcceptStoriesException extends RuntimeException
{
    public function __construct(
        public readonly Sprint $sprint,
    ) {
        parent::__construct(
            __('agile.errors.closed_sprint_cannot_accept_stories', ['name' => $sprint->name])
        );
    }
}
