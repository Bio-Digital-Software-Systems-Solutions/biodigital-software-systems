<?php

namespace App\Enums\Workflow;

enum TimeoutAction: string
{
    case ESCALATE = 'escalate';
    case SKIP = 'skip';
    case FAIL = 'fail';
    case AUTO_APPROVE = 'auto_approve';
    case AUTO_REJECT = 'auto_reject';
    case NOTIFY = 'notify';
    case REASSIGN = 'reassign';

    public function label(): string
    {
        return match ($this) {
            self::ESCALATE => 'Escalader',
            self::SKIP => 'Ignorer l\'étape',
            self::FAIL => 'Échouer le workflow',
            self::AUTO_APPROVE => 'Approuver automatiquement',
            self::AUTO_REJECT => 'Rejeter automatiquement',
            self::NOTIFY => 'Notifier uniquement',
            self::REASSIGN => 'Réassigner',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ESCALATE => 'Transférer à un niveau supérieur',
            self::SKIP => 'Passer à l\'étape suivante',
            self::FAIL => 'Terminer le workflow en échec',
            self::AUTO_APPROVE => 'Considérer comme approuvé',
            self::AUTO_REJECT => 'Considérer comme rejeté',
            self::NOTIFY => 'Envoyer une notification de rappel',
            self::REASSIGN => 'Réassigner à un autre utilisateur',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ESCALATE => 'orange',
            self::SKIP => 'yellow',
            self::FAIL => 'red',
            self::AUTO_APPROVE => 'green',
            self::AUTO_REJECT => 'red',
            self::NOTIFY => 'blue',
            self::REASSIGN => 'purple',
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
        ], self::cases());
    }
}
