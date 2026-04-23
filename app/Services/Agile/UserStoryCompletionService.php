<?php

namespace App\Services\Agile;

use App\Enums\Agile\UserStoryStatus;
use App\Events\Agile\UserStoryCompleted;
use App\Exceptions\Agile\CannotCompleteStoryException;
use App\Models\Agile\UserStory;
use App\Models\User;

class UserStoryCompletionService
{
    public function complete(UserStory $story, User $actor): UserStory
    {
        $story->loadMissing('acceptanceCriteria');

        if (! $story->canBeCompleted()) {
            throw new CannotCompleteStoryException(
                $story,
                max($story->pendingCriteriaCount(), $story->acceptanceCriteria->isEmpty() ? 1 : 0)
            );
        }

        $story->forceFill([
            'status' => UserStoryStatus::DONE,
            'completed_at' => now(),
        ])->save();

        event(new UserStoryCompleted($story, $actor));

        return $story;
    }

    public function canBeCompleted(UserStory $story): bool
    {
        $story->loadMissing('acceptanceCriteria');

        return $story->canBeCompleted();
    }
}
