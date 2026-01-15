<?php

namespace App\Enums\Event;

enum NotificationType: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';
    case IN_APP = 'in_app';

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'E-mail',
            self::SMS => 'SMS',
            self::PUSH => 'Notification Push',
            self::IN_APP => 'In-App',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
            self::PUSH => 'Push Notification',
            self::IN_APP => 'In-App',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::EMAIL => 'E-Mail',
            self::SMS => 'SMS',
            self::PUSH => 'Push-Benachrichtigung',
            self::IN_APP => 'In-App',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::EMAIL => 'envelope',
            self::SMS => 'device-phone-mobile',
            self::PUSH => 'bell',
            self::IN_APP => 'chat-bubble-left',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EMAIL => 'blue',
            self::SMS => 'green',
            self::PUSH => 'purple',
            self::IN_APP => 'cyan',
        };
    }

    public function isExternal(): bool
    {
        return in_array($this, [self::EMAIL, self::SMS, self::PUSH]);
    }

    public function requiresPhoneNumber(): bool
    {
        return $this === self::SMS;
    }

    public function requiresEmailAddress(): bool
    {
        return $this === self::EMAIL;
    }

    public function requiresDeviceToken(): bool
    {
        return $this === self::PUSH;
    }
}
