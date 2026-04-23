<?php

use App\Models\IntegrationPathwayStep;
use App\Models\IntegrationPathwayTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses(Tests\CreatesPermissions::class);

beforeEach(function (): void {
    $this->setupPermissions();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('lists integration pathway templates', function (): void {
    IntegrationPathwayTemplate::factory()->count(3)->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->get(route('integration-pathways.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('IntegrationPathways/Index')
        ->has('templates', 3)
    );
});

it('creates a pathway template with steps', function (): void {
    $response = $this->actingAs($this->admin)->post(route('integration-pathways.store'), [
        'name' => 'Parcours Standard',
        'description' => 'Le parcours par défaut',
        'target_type' => 'group',
        'is_default' => true,
        'is_active' => true,
        'steps' => [
            [
                'name' => 'Présence régulière',
                'order_index' => 0,
                'type' => 'attendance_count',
                'criteria' => ['min_attendance' => 4, 'period_weeks' => 8],
                'weight' => 3,
                'is_required' => true,
            ],
            [
                'name' => 'Approbation du leader',
                'order_index' => 1,
                'type' => 'manual_approval',
                'weight' => 1,
                'is_required' => true,
            ],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('integration_pathway_templates', ['name' => 'Parcours Standard']);
    expect(IntegrationPathwayStep::count())->toBe(2);
});

it('updates a pathway template', function (): void {
    $template = IntegrationPathwayTemplate::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->put(route('integration-pathways.update', $template), [
        'name' => 'Updated Name',
        'is_active' => false,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('integration_pathway_templates', [
        'id' => $template->id,
        'name' => 'Updated Name',
        'is_active' => false,
    ]);
});

it('deletes a pathway template', function (): void {
    $template = IntegrationPathwayTemplate::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->delete(route('integration-pathways.destroy', $template));

    $response->assertRedirect();
    $this->assertDatabaseMissing('integration_pathway_templates', ['id' => $template->id]);
});

it('adds a step to a template', function (): void {
    $template = IntegrationPathwayTemplate::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->postJson(route('integration-pathways.steps.store', $template), [
        'name' => 'New Step',
        'order_index' => 0,
        'type' => 'attendance_count',
        'criteria' => ['min_attendance' => 5],
        'weight' => 2,
        'is_required' => true,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('integration_pathway_steps', [
        'template_id' => $template->id,
        'name' => 'New Step',
    ]);
});

it('removes a step from a template', function (): void {
    $template = IntegrationPathwayTemplate::factory()->create(['created_by' => $this->admin->id]);
    $step = IntegrationPathwayStep::factory()->create(['template_id' => $template->id]);

    $response = $this->actingAs($this->admin)->deleteJson(route('integration-pathways.steps.destroy', [$template, $step]));

    $response->assertOk();
    $this->assertDatabaseMissing('integration_pathway_steps', ['id' => $step->id]);
});

it('reorders steps', function (): void {
    $template = IntegrationPathwayTemplate::factory()->create(['created_by' => $this->admin->id]);
    $step1 = IntegrationPathwayStep::factory()->create(['template_id' => $template->id, 'order_index' => 0]);
    $step2 = IntegrationPathwayStep::factory()->create(['template_id' => $template->id, 'order_index' => 1]);

    $response = $this->actingAs($this->admin)->postJson(route('integration-pathways.steps.reorder', $template), [
        'steps' => [
            ['id' => $step1->id, 'order_index' => 1],
            ['id' => $step2->id, 'order_index' => 0],
        ],
    ]);

    $response->assertOk();
    expect($step1->fresh()->order_index)->toBe(1);
    expect($step2->fresh()->order_index)->toBe(0);
});

it('denies non-admin access to pathways', function (): void {
    $member = User::factory()->create();
    $member->assignRole('member');

    $response = $this->actingAs($member)->get(route('integration-pathways.index'));

    expect($response->status())->toBeIn([302, 403]);
});

it('unsets previous default when creating new default template', function (): void {
    $existing = IntegrationPathwayTemplate::factory()->default()->forGroups()->create([
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->post(route('integration-pathways.store'), [
        'name' => 'New Default',
        'target_type' => 'group',
        'is_default' => true,
        'is_active' => true,
    ]);

    expect($existing->fresh()->is_default)->toBeFalse();
});
