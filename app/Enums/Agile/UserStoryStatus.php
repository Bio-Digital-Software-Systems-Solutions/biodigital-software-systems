<?php

namespace App\Enums\Agile;

enum UserStoryStatus: string
{
    case BACKLOG = 'backlog';
    case READY = 'ready';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case DONE = 'done';

    public function label(): string
    {
        return __('agile.statuses.user_story.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::BACKLOG => 'gray',
            self::READY => 'blue',
            self::IN_PROGRESS => 'indigo',
            self::REVIEW => 'yellow',
            self::DONE => 'green',
        };
    }

    public function isDone(): bool
    {
        return $this === self::DONE;
    }

    public function isOpen(): bool
    {
        return ! $this->isDone();
    }
}
