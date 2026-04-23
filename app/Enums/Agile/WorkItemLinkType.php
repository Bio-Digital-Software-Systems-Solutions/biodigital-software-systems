<?php

namespace App\Enums\Agile;

enum WorkItemLinkType: string
{
    case BLOCKS = 'blocks';
    case RELATES_TO = 'relates_to';
    case DUPLICATES = 'duplicates';
    case PARENT_OF = 'parent_of';

    public function label(): string
    {
        return __('agile.link_types.'.$this->value);
    }

    public function inverse(): self
    {
        return match ($this) {
            self::BLOCKS => self::BLOCKS,
            self::RELATES_TO => self::RELATES_TO,
            self::DUPLICATES => self::DUPLICATES,
            self::PARENT_OF => self::PARENT_OF,
        };
    }
}
