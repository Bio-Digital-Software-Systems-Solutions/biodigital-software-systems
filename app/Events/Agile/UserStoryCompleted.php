<?php

namespace App\Events\Agile;

use App\Models\Agile\UserStory;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStoryCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UserStory $story,
        public readonly User $actor,
    ) {}
}
