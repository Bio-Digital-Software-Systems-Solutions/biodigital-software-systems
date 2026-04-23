<?php

use App\Enums\Agile\AcceptanceCriterionStatus;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists an acceptance criterion with enum cast status', function (): void {
    $ac = AcceptanceCriterion::factory()->pending()->create();

    expect($ac->status)->toBe(AcceptanceCriterionStatus::PENDING)
        ->and($ac->isValidated())->toBeFalse()
        ->and($ac->isFinal())->toBeFalse();
});

it('recognises a validated criterion', function (): void {
    $validator = User::factory()->create();
    $ac = AcceptanceCriterion::factory()->validated($validator)->create();

    expect($ac->status)->toBe(AcceptanceCriterionStatus::VALIDATED)
        ->and($ac->isValidated())->toBeTrue()
        ->and($ac->isFinal())->toBeTrue()
        ->and($ac->validated_by)->toBe($validator->id)
        ->and($ac->validated_at)->not->toBeNull();
});

it('treats a rejected criterion as final but not validated', function (): void {
    $ac = AcceptanceCriterion::factory()->rejected()->create();

    expect($ac->isValidated())->toBeFalse()
        ->and($ac->isFinal())->toBeTrue()
        ->and($ac->validation_notes)->not->toBeNull();
});
