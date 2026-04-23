<?php

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses(Tests\CreatesPermissions::class);

beforeEach(function (): void {
    $this->setupPermissions();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('displays visitors index page', function (): void {
    Visitor::factory()->count(3)->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->get(route('visitors.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Visitors/Index')
        ->has('visitors.data', 3)
    );
});

it('filters visitors by status', function (): void {
    Visitor::factory()->create(['created_by' => $this->admin->id, 'status' => 'active']);
    Visitor::factory()->create(['created_by' => $this->admin->id, 'status' => 'inactive']);

    $response = $this->actingAs($this->admin)->get(route('visitors.index', ['status' => 'active']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('visitors.data', 1));
});

it('filters visitors by search', function (): void {
    Visitor::factory()->create(['created_by' => $this->admin->id, 'first_name' => 'Jean', 'last_name' => 'Dupont']);
    Visitor::factory()->create(['created_by' => $this->admin->id, 'first_name' => 'Marie', 'last_name' => 'Martin']);

    $response = $this->actingAs($this->admin)->get(route('visitors.index', ['search' => 'Dupont']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('visitors.data', 1));
});

it('creates a visitor', function (): void {
    $response = $this->actingAs($this->admin)->post(route('visitors.store'), [
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'email' => 'jean@example.com',
        'first_visit_date' => '2026-03-15',
        'source' => 'friend',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('visitors', [
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'email' => 'jean@example.com',
        'created_by' => $this->admin->id,
    ]);
});

it('validates required fields on store', function (): void {
    $response = $this->actingAs($this->admin)->post(route('visitors.store'), []);

    $response->assertSessionHasErrors(['first_name', 'last_name', 'first_visit_date']);
});

it('validates unique email on store', function (): void {
    Visitor::factory()->create(['email' => 'taken@example.com', 'created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->post(route('visitors.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'taken@example.com',
        'first_visit_date' => '2026-03-15',
    ]);

    $response->assertSessionHasErrors(['email']);
});

it('shows a visitor with visits', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->get(route('visitors.show', $visitor));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Visitors/Show')
        ->has('visitor')
    );
});

it('updates a visitor', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->put(route('visitors.update', $visitor), [
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'first_visit_date' => '2026-03-15',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('visitors', [
        'id' => $visitor->id,
        'first_name' => 'Updated',
        'last_name' => 'Name',
    ]);
});

it('soft deletes a visitor', function (): void {
    $visitor = Visitor::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->delete(route('visitors.destroy', $visitor));

    $response->assertRedirect();
    $this->assertSoftDeleted('visitors', ['id' => $visitor->id]);
});

it('denies access to unauthorized users', function (): void {
    $member = User::factory()->create();
    $member->assignRole('member');

    $response = $this->actingAs($member)->get(route('visitors.index'));

    // The middleware redirects unauthorized users
    expect($response->status())->toBeIn([302, 403]);
});
