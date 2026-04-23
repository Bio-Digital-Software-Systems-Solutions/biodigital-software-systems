<?php

namespace App\Policies\Agile;

use App\Models\Agile\TestScenario;
use App\Models\User;

class TestScenarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view test scenarios');
    }

    public function view(User $user, TestScenario $scenario): bool
    {
        return $user->can('view test scenarios');
    }

    public function create(User $user): bool
    {
        return $user->can('create test scenarios');
    }

    public function update(User $user, TestScenario $scenario): bool
    {
        return $user->can('edit test scenarios');
    }

    public function delete(User $user, TestScenario $scenario): bool
    {
        return $user->can('delete test scenarios');
    }

    public function recordRun(User $user, TestScenario $scenario): bool
    {
        return $user->can('execute test scenarios');
    }
}
