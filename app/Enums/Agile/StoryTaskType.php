<?php

namespace App\Enums\Agile;

enum StoryTaskType: string
{
    case DEV = 'dev';
    case TEST = 'test';
    case DEVOPS = 'devops';
    case DESIGN = 'design';
    case DOC = 'doc';

    public function label(): string
    {
        return __('agile.work_types.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DEV => 'blue',
            self::TEST => 'purple',
            self::DEVOPS => 'orange',
            self::DESIGN => 'pink',
            self::DOC => 'gray',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
