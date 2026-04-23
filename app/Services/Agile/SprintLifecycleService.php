<?php

namespace App\Services\Agile;

use App\Events\Agile\SprintClosed;
use App\Events\Agile\SprintStarted;
use App\Exceptions\Agile\ActiveSprintAlreadyExistsException;
use App\Exceptions\Agile\ClosedSprintCannotAcceptStoriesException;
use App\Models\Agile\UserStory;
use App\Models\Sprint;

class SprintLifecycleService
{
    public function start(Sprint $sprint): Sprint
    {
        $conflicting = Sprint::query()
            ->where('project_id', $sprint->project_id)
            ->where('status', 'active')
            ->where('id', '!=', $sprint->id)
            ->first();

        if ($conflicting !== null) {
            throw new ActiveSprintAlreadyExistsException($sprint, $conflicting);
        }

        $sprint->forceFill(['status' => 'active'])->save();

        event(new SprintStarted($sprint));

        return $sprint;
    }

    public function close(Sprint $sprint): Sprint
    {
        $sprint->forceFill(['status' => 'completed'])->save();

        event(new SprintClosed($sprint));

        return $sprint;
    }

    public function moveStoryToSprint(UserStory $story, ?Sprint $target): UserStory
    {
        if ($target !== null && $target->status === 'completed') {
            throw new ClosedSprintCannotAcceptStoriesException($target);
        }

        $story->forceFill(['sprint_id' => $target?->id])->save();

        return $story;
    }
}
