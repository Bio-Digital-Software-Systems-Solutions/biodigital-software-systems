<?php

namespace App\Enums;

enum RoutineStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::PendingApproval => 'En attente d\'approbation',
            self::Approved => 'Approuvée',
            self::Active => 'Active',
            self::Archived => 'Archivée',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved',
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::PendingApproval => 'Genehmigung ausstehend',
            self::Approved => 'Genehmigt',
            self::Active => 'Aktiv',
            self::Archived => 'Archiviert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'yellow',
            self::Approved => 'blue',
            self::Active => 'green',
            self::Archived => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil',
            self::PendingApproval => 'clock',
            self::Approved => 'check-circle',
            self::Active => 'play',
            self::Archived => 'archive-box',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Draft => $newStatus === self::PendingApproval,
            self::PendingApproval => in_array($newStatus, [self::Approved, self::Draft]),
            self::Approved => $newStatus === self::Active,
            self::Active => $newStatus === self::Archived,
            self::Archived => $newStatus === self::Draft,
        };
    }
}
