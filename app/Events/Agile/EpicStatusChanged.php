<?php

namespace App\Events\Agile;

use App\Enums\Agile\EpicStatus;
use App\Models\Agile\Epic;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EpicStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Epic $epic,
        public readonly EpicStatus $from,
        public readonly EpicStatus $to,
    ) {}
}
