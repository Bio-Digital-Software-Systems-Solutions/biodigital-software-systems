<?php

use App\Models\Group;
use App\Models\IntegrationSuggestion;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses(Tests\CreatesPermissions::class);

beforeEach(function (): void {
    $this->setupPermissions();
    $this->leader = User::factory()->create();
    $this->leader->assignRole('admin');
    $this->group = Group::factory()->create(['leader_id' => $this->leader->id]);
});

it('lists pending suggestions for the leader', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->leader->id]);
    $visit = VisitorVisit::factory()->forGroup($this->group)->ready()->create([
        'visitor_id' => $visitor->id,
    ]);
    IntegrationSuggestion::factory()->create([
        'visitor_visit_id' => $visit->id,
        'suggested_to' => $this->leader->id,
    ]);

    $response = $this->actingAs($this->leader)->getJson(route('integration-suggestions.index'));

    $response->assertOk();
    $response->assertJsonCount(1, 'suggestions');
});

it('does not list suggestions for other users', function (): void {
    $otherLeader = User::factory()->create();
    $otherLeader->assignRole('admin');
    $visitor = Visitor::factory()->create(['created_by' => $this->leader->id]);
    $visit = VisitorVisit::factory()->forGroup($this->group)->ready()->create([
        'visitor_id' => $visitor->id,
    ]);
    IntegrationSuggestion::factory()->create([
        'visitor_visit_id' => $visit->id,
        'suggested_to' => $this->leader->id,
    ]);

    $response = $this->actingAs($otherLeader)->getJson(route('integration-suggestions.index'));

    $response->assertOk();
    $response->assertJsonCount(0, 'suggestions');
});

it('accepts a suggestion and integrates visitor', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->leader->id]);
    $visit = VisitorVisit::factory()->forGroup($this->group)->ready()->create([
        'visitor_id' => $visitor->id,
    ]);
    $suggestion = IntegrationSuggestion::factory()->create([
        'visitor_visit_id' => $visit->id,
        'suggested_to' => $this->leader->id,
    ]);

    $response = $this->actingAs($this->leader)->postJson(
        route('integration-suggestions.respond', $suggestion),
        ['status' => 'accepted']
    );

    $response->assertOk();
    expect($suggestion->fresh()->status)->toBe('accepted');
    expect($suggestion->fresh()->responded_at)->not->toBeNull();
    expect($visit->fresh()->integration_status)->toBe('integrated');
    expect($visitor->fresh()->status)->toBe('integrated');
});

it('rejects a suggestion', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->leader->id]);
    $visit = VisitorVisit::factory()->forGroup($this->group)->ready()->create([
        'visitor_id' => $visitor->id,
    ]);
    $suggestion = IntegrationSuggestion::factory()->create([
        'visitor_visit_id' => $visit->id,
        'suggested_to' => $this->leader->id,
    ]);

    $response = $this->actingAs($this->leader)->postJson(
        route('integration-suggestions.respond', $suggestion),
        ['status' => 'rejected', 'response_notes' => 'Pas encore prêt.']
    );

    $response->assertOk();
    expect($suggestion->fresh()->status)->toBe('rejected');
    expect($suggestion->fresh()->response_notes)->toBe('Pas encore prêt.');
    expect($visit->fresh()->integration_status)->not->toBe('integrated');
});

it('defers a suggestion', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->leader->id]);
    $visit = VisitorVisit::factory()->forGroup($this->group)->ready()->create([
        'visitor_id' => $visitor->id,
    ]);
    $suggestion = IntegrationSuggestion::factory()->create([
        'visitor_visit_id' => $visit->id,
        'suggested_to' => $this->leader->id,
    ]);

    $response = $this->actingAs($this->leader)->postJson(
        route('integration-suggestions.respond', $suggestion),
        ['status' => 'deferred']
    );

    $response->assertOk();
    expect($suggestion->fresh()->status)->toBe('deferred');
});

it('prevents responding to other leaders suggestions', function (): void {
    $otherUser = User::factory()->create();
    $otherUser->assignRole('admin');
    $visitor = Visitor::factory()->create(['created_by' => $this->leader->id]);
    $visit = VisitorVisit::factory()->forGroup($this->group)->ready()->create([
        'visitor_id' => $visitor->id,
    ]);
    $suggestion = IntegrationSuggestion::factory()->create([
        'visitor_visit_id' => $visit->id,
        'suggested_to' => $this->leader->id,
    ]);

    $response = $this->actingAs($otherUser)->postJson(
        route('integration-suggestions.respond', $suggestion),
        ['status' => 'accepted']
    );

    $response->assertForbidden();
});

it('validates response status', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->leader->id]);
    $visit = VisitorVisit::factory()->forGroup($this->group)->ready()->create([
        'visitor_id' => $visitor->id,
    ]);
    $suggestion = IntegrationSuggestion::factory()->create([
        'visitor_visit_id' => $visit->id,
        'suggested_to' => $this->leader->id,
    ]);

    $response = $this->actingAs($this->leader)->postJson(
        route('integration-suggestions.respond', $suggestion),
        ['status' => 'invalid_status']
    );

    $response->assertUnprocessable();
});
