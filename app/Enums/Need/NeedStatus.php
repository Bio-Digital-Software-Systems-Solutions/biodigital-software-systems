<?php

namespace App\Enums\Need;

enum NeedStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case IN_PROGRESS = 'in_progress';
    case ORDERED = 'ordered';
    case DELIVERED = 'delivered';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumis',
            self::UNDER_REVIEW => 'En révision',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté',
            self::IN_PROGRESS => 'En cours',
            self::ORDERED => 'Commandé',
            self::DELIVERED => 'Livré',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'blue',
            self::UNDER_REVIEW => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::IN_PROGRESS => 'purple',
            self::ORDERED => 'cyan',
            self::DELIVERED => 'teal',
            self::COMPLETED => 'emerald',
            self::CANCELLED => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'pencil',
            self::SUBMITTED => 'paper-airplane',
            self::UNDER_REVIEW => 'eye',
            self::APPROVED => 'check',
            self::REJECTED => 'x-mark',
            self::IN_PROGRESS => 'arrow-path',
            self::ORDERED => 'shopping-cart',
            self::DELIVERED => 'truck',
            self::COMPLETED => 'check-circle',
            self::CANCELLED => 'ban',
        };
    }

    public function kanbanColumn(): string
    {
        return match ($this) {
            self::DRAFT, self::SUBMITTED => 'backlog',
            self::UNDER_REVIEW => 'review',
            self::APPROVED, self::IN_PROGRESS, self::ORDERED => 'in_progress',
            self::DELIVERED, self::COMPLETED => 'done',
            self::REJECTED, self::CANCELLED => 'cancelled',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SUBMITTED, self::CANCELLED],
            self::SUBMITTED => [self::UNDER_REVIEW, self::DRAFT, self::CANCELLED], // DRAFT added for withdraw
            self::UNDER_REVIEW => [self::APPROVED, self::REJECTED],
            self::APPROVED => [self::IN_PROGRESS, self::CANCELLED],
            self::REJECTED => [self::DRAFT],
            self::IN_PROGRESS => [self::ORDERED, self::CANCELLED],
            self::ORDERED => [self::DELIVERED, self::CANCELLED],
            self::DELIVERED => [self::COMPLETED],
            self::COMPLETED, self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::REJECTED]);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Need\NeedStatus $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }

    public static function kanbanColumns(): array
    {
        return [
            'backlog' => ['label' => 'À traiter', 'statuses' => [self::DRAFT, self::SUBMITTED]],
            'review' => ['label' => 'En révision', 'statuses' => [self::UNDER_REVIEW]],
            'in_progress' => ['label' => 'En cours', 'statuses' => [self::APPROVED, self::IN_PROGRESS, self::ORDERED]],
            'done' => ['label' => 'Terminé', 'statuses' => [self::DELIVERED, self::COMPLETED]],
            'cancelled' => ['label' => 'Annulé/Rejeté', 'statuses' => [self::REJECTED, self::CANCELLED]],
        ];
    }
}
