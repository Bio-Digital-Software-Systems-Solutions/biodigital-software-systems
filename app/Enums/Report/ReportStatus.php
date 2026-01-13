<?php

namespace App\Enums\Report;

enum ReportStatus: string
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case UNDER_REVIEW = 'under_review';
    case REVISION_REQUESTED = 'revision_requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PENDING_REVIEW => 'En attente de validation',
            self::UNDER_REVIEW => 'En cours de révision',
            self::REVISION_REQUESTED => 'Révision demandée',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté',
            self::PUBLISHED => 'Publié',
            self::ARCHIVED => 'Archivé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING_REVIEW => 'yellow',
            self::UNDER_REVIEW => 'blue',
            self::REVISION_REQUESTED => 'orange',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::PUBLISHED => 'indigo',
            self::ARCHIVED => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'file-edit',
            self::PENDING_REVIEW => 'clock',
            self::UNDER_REVIEW => 'eye',
            self::REVISION_REQUESTED => 'alert-circle',
            self::APPROVED => 'check-circle',
            self::REJECTED => 'x-circle',
            self::PUBLISHED => 'globe',
            self::ARCHIVED => 'archive',
        };
    }

    public function canTransitionTo(ReportStatus $status): bool
    {
        return in_array($status, $this->allowedTransitions());
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING_REVIEW, self::ARCHIVED],
            self::PENDING_REVIEW => [self::UNDER_REVIEW, self::DRAFT],
            self::UNDER_REVIEW => [self::APPROVED, self::REJECTED, self::REVISION_REQUESTED],
            self::REVISION_REQUESTED => [self::DRAFT, self::PENDING_REVIEW],
            self::APPROVED => [self::PUBLISHED, self::REVISION_REQUESTED],
            self::REJECTED => [self::DRAFT, self::ARCHIVED],
            self::PUBLISHED => [self::ARCHIVED],
            self::ARCHIVED => [self::DRAFT],
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::REVISION_REQUESTED]);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::PUBLISHED, self::ARCHIVED]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}
