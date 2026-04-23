<?php

namespace App\Policies\Agile;

use App\Models\Agile\UserStory;
use App\Models\User;

class UserStoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view user stories');
    }

    public function view(User $user, UserStory $story): bool
    {
        if ($this->isStakeholder($user, $story)) {
            return true;
        }

        return $user->can('view user stories');
    }

    public function create(User $user): bool
    {
        return $user->can('create user stories');
    }

    public function update(User $user, UserStory $story): bool
    {
        if ($user->id === $story->assignee_id || $user->id === $story->reporter_id) {
            return true;
        }

        if ($user->id === $story->epic?->owner_id) {
            return true;
        }

        return $user->can('edit user stories');
    }

    public function delete(User $user, UserStory $story): bool
    {
        if ($user->id === $story->reporter_id) {
            return true;
        }

        return $user->can('delete user stories');
    }

    public function complete(User $user, UserStory $story): bool
    {
        if ($user->id === $story->epic?->owner_id) {
            return true;
        }

        return $user->can('complete user stories');
    }

    public function moveToSprint(User $user, UserStory $story): bool
    {
        if ($user->id === $story->epic?->owner_id) {
            return true;
        }

        return $user->can('move stories to sprint');
    }

    private function isStakeholder(User $user, UserStory $story): bool
    {
        return $user->id === $story->assignee_id
            || $user->id === $story->reporter_id
            || $user->id === $story->epic?->owner_id;
    }
}
