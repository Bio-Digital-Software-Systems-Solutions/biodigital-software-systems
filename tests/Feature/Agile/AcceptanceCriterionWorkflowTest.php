<?php

use App\Enums\Agile\AcceptanceCriterionStatus;
use App\Events\Agile\AcceptanceCriterionRejected;
use App\Events\Agile\AcceptanceCriterionValidated;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\TestScenario;
use App\Models\Agile\UserStory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('product-owner can validate an AC via POST /validate with optional notes', function (): void {
    Event::fake([AcceptanceCriterionValidated::class]);

    $po = asRole('product-owner');
    $ac = AcceptanceCriterion::factory()->pending()->create();

    $this->actingAs($po)
        ->postJson(route('api.agile.acceptance-criteria.validate', $ac), ['notes' => 'LGTM'])
        ->assertOk()
        ->assertJsonPath('data.status', AcceptanceCriterionStatus::VALIDATED->value);

    expect($ac->fresh())
        ->status->toBe(AcceptanceCriterionStatus::VALIDATED)
        ->validated_by->toBe($po->id)
        ->validation_notes->toBe('LGTM');

    Event::assertDispatched(AcceptanceCriterionValidated::class);
});

it('product-owner can reject an AC with mandatory notes', function (): void {
    Event::fake([AcceptanceCriterionRejected::class]);

    $po = asRole('product-owner');
    $ac = AcceptanceCriterion::factory()->pending()->create();

    $this->actingAs($po)
        ->postJson(route('api.agile.acceptance-criteria.reject', $ac), ['notes' => 'Edge case missing.'])
        ->assertOk()
        ->assertJsonPath('data.status', AcceptanceCriterionStatus::REJECTED->value);

    Event::assertDispatched(AcceptanceCriterionRejected::class);
});

it('reject requires notes — 422 without them', function (): void {
    $po = asRole('product-owner');
    $ac = AcceptanceCriterion::factory()->pending()->create();

    $this->actingAs($po)
        ->postJson(route('api.agile.acceptance-criteria.reject', $ac), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['notes']);
});

it('project-manager cannot validate an AC (only Product Owner can)', function (): void {
    $pm = asRole('project-manager');
    $ac = AcceptanceCriterion::factory()->pending()->create();

    $this->actingAs($pm)
        ->postJson(route('api.agile.acceptance-criteria.validate', $ac), [])
        ->assertStatus(403);
});

it('member cannot validate or reject an AC', function (): void {
    $member = asRole('member');
    $ac = AcceptanceCriterion::factory()->pending()->create();

    $this->actingAs($member)
        ->postJson(route('api.agile.acceptance-criteria.validate', $ac), [])
        ->assertStatus(403);
});

it('destroying an AC returns 422 when a test scenario has already passed', function (): void {
    $po = asRole('product-owner');
    $ac = AcceptanceCriterion::factory()->create();
    TestScenario::factory()->for($ac, 'acceptanceCriterion')->passed()->create();

    $this->actingAs($po)
        ->deleteJson(route('agile.acceptance-criteria.destroy', $ac))
        ->assertStatus(422);

    expect(AcceptanceCriterion::find($ac->id))->not->toBeNull();
});

it('reorders acceptance criteria of a story', function (): void {
    $pm = asRole('project-manager');
    $story = UserStory::factory()->create();
    $first = AcceptanceCriterion::factory()->for($story, 'userStory')->atPosition(1)->create();
    $second = AcceptanceCriterion::factory()->for($story, 'userStory')->atPosition(2)->create();
    $third = AcceptanceCriterion::factory()->for($story, 'userStory')->atPosition(3)->create();

    $this->actingAs($pm)
        ->postJson(route('api.agile.acceptance-criteria.reorder', $story), [
            'ordered_ids' => [$third->id, $first->id, $second->id],
        ])
        ->assertOk();

    expect($third->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2)
        ->and($second->fresh()->position)->toBe(3);
});
