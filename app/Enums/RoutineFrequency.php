<?php

namespace App\Enums;

enum RoutineFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case OnDemand = 'on_demand';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Quotidienne',
            self::Weekly => 'Hebdomadaire',
            self::Biweekly => 'Bihebdomadaire',
            self::Monthly => 'Mensuelle',
            self::Quarterly => 'Trimestrielle',
            self::OnDemand => 'À la demande',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Biweekly => 'Biweekly',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::OnDemand => 'On Demand',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::Daily => 'Täglich',
            self::Weekly => 'Wöchentlich',
            self::Biweekly => 'Zweiwöchentlich',
            self::Monthly => 'Monatlich',
            self::Quarterly => 'Vierteljährlich',
            self::OnDemand => 'Auf Anfrage',
        };
    }
}
