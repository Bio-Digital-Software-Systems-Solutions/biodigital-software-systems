<?php

namespace App\Events\Agile;

use App\Models\Sprint;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SprintClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Sprint $sprint,
    ) {}
}
