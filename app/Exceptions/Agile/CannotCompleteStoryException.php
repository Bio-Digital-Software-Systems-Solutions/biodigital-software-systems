<?php

namespace App\Exceptions\Agile;

use App\Models\Agile\UserStory;
use RuntimeException;

class CannotCompleteStoryException extends RuntimeException
{
    public function __construct(
        public readonly UserStory $story,
        public readonly int $pendingCriteriaCount,
    ) {
        parent::__construct(
            __('agile.errors.cannot_complete_story', ['count' => $pendingCriteriaCount])
        );
    }
}
