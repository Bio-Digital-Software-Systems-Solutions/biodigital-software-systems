<?php

use App\Models\Agile\Epic;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Frontend pages are out of scope for this backend module, so the Vite
    // manifest does not resolve — skip it for these tests.
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists epics filtered by project', function (): void {
    $pm = asRole('project-manager');
    $project = Project::factory()->create();
    Epic::factory()->for($project)->count(2)->create();
    Epic::factory()->count(3)->create();

    $response = $this->actingAs($pm)
        ->get(route('agile.epics.index', ['project_id' => $project->id]));

    $response->assertOk();

    // Inertia inlines the component + props into <div id="app" data-page='<json>'>;
    // parse it since the frontend React page does not exist in this backend-only scope.
    preg_match('/data-page="([^"]+)"/', $response->getContent(), $matches);
    $page = json_decode(html_entity_decode($matches[1] ?? '{}'), true);

    expect($page['component'] ?? null)->toBe('Agile/Epics/Index')
        ->and(count($page['props']['epics']['data'] ?? []))->toBe(2);
});

it('creates an epic via POST /epics', function (): void {
    $pm = asRole('project-manager');
    $project = Project::factory()->create();
    $owner = User::factory()->create();

    $this->actingAs($pm)
        ->post(route('agile.epics.store'), [
            'project_id' => $project->id,
            'owner_id' => $owner->id,
            'title' => 'First epic',
            'priority' => 2,
        ])
        ->assertRedirect();

    expect(Epic::where('title', 'First epic')->exists())->toBeTrue();
});

it('updates an epic via PATCH', function (): void {
    $pm = asRole('project-manager');
    $epic = Epic::factory()->create(['title' => 'Old title']);

    $this->actingAs($pm)
        ->patch(route('agile.epics.update', $epic), ['title' => 'New title'])
        ->assertRedirect();

    expect($epic->fresh()->title)->toBe('New title');
});

it('deletes an epic', function (): void {
    $pm = asRole('project-manager');
    $epic = Epic::factory()->create();

    $this->actingAs($pm)
        ->delete(route('agile.epics.destroy', $epic))
        ->assertRedirect();

    expect(Epic::find($epic->id))->toBeNull();
});

it('denies a member from creating an epic', function (): void {
    $member = asRole('member');
    $project = Project::factory()->create();
    $owner = User::factory()->create();

    $this->actingAs($member)
        ->postJson(route('agile.epics.store'), [
            'project_id' => $project->id,
            'owner_id' => $owner->id,
            'title' => 'Refused',
        ])
        ->assertStatus(403);
});

it('requires authentication for the epics index', function (): void {
    $this->get(route('agile.epics.index'))->assertRedirect(route('login'));
});
