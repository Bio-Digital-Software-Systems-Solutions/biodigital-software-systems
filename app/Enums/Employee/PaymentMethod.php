<?php

namespace App\Enums\Employee;

enum PaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case CHECK = 'check';

    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Virement bancaire',
            self::CASH => 'Espèces',
            self::CHECK => 'Chèque',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CASH => 'Cash',
            self::CHECK => 'Check',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Banküberweisung',
            self::CASH => 'Bargeld',
            self::CHECK => 'Scheck',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'building-library',
            self::CASH => 'banknotes',
            self::CHECK => 'document-text',
        };
    }
}
