<?php

namespace App\Policies\Agile;

use App\Models\Agile\AcceptanceCriterion;
use App\Models\User;

class AcceptanceCriterionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view acceptance criteria');
    }

    public function view(User $user, AcceptanceCriterion $criterion): bool
    {
        return $user->can('view acceptance criteria');
    }

    public function create(User $user): bool
    {
        return $user->can('create acceptance criteria');
    }

    public function update(User $user, AcceptanceCriterion $criterion): bool
    {
        return $user->can('edit acceptance criteria');
    }

    public function delete(User $user, AcceptanceCriterion $criterion): bool
    {
        return $user->can('delete acceptance criteria');
    }

    public function validate(User $user, AcceptanceCriterion $criterion): bool
    {
        if ($user->id === $criterion->userStory?->epic?->owner_id) {
            return true;
        }

        return $user->can('validate acceptance criteria');
    }

    public function reject(User $user, AcceptanceCriterion $criterion): bool
    {
        return $this->validate($user, $criterion);
    }
}
