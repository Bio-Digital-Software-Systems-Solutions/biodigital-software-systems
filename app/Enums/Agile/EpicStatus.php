<?php

namespace App\Enums\Agile;

enum EpicStatus: string
{
    case DRAFT = 'draft';
    case READY = 'ready';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return __('agile.statuses.epic.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::READY => 'blue',
            self::IN_PROGRESS => 'indigo',
            self::DONE => 'green',
            self::ARCHIVED => 'zinc',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::DRAFT, self::READY, self::IN_PROGRESS], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::DONE, self::ARCHIVED], true);
    }
}
