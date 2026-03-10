<?php

use App\Models\PastoralCare;
use App\Models\PastoralCareTheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Seed roles and permissions
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

    // Seed default themes
    $this->artisan('db:seed', ['--class' => 'PastoralCareThemeSeeder']);

    // Create a pastor user
    $this->pastor = User::factory()->create();
    $this->pastor->assignRole('pastor');

    // Create availability for pastor
    \App\Models\PastorAvailability::create([
        'pastor_id' => $this->pastor->id,
        'day_of_week' => now()->addDay()->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'location_type' => 'in_person',
        'is_recurring' => true,
    ]);
});

describe('Themes API', function (): void {
    it('returns all active themes', function (): void {
        $response = $this->getJson('/api/pastoral-care/themes');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'color',
                        'icon',
                    ],
                ],
            ]);

        expect($response->json('data'))->toHaveCount(13); // 13 seeded themes
    });

    it('only returns active themes', function (): void {
        // Deactivate one theme
        $theme = PastoralCareTheme::first();
        $theme->update(['is_active' => false]);

        $response = $this->getJson('/api/pastoral-care/themes');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(12);

        // Reactivate theme
        $theme->update(['is_active' => true]);
    });

    it('returns themes in sort order', function (): void {
        $response = $this->getJson('/api/pastoral-care/themes');

        $themes = $response->json('data');
        $firstTheme = $themes[0];
        $lastTheme = $themes[count($themes) - 1];

        expect($firstTheme['slug'])->toBe('spiritual-guidance'); // sort_order: 1
        expect($lastTheme['slug'])->toBe('other'); // sort_order: 99
    });
});

describe('Proposal with Themes', function (): void {
    it('can create a proposal with themes', function (): void {
        $themes = PastoralCareTheme::take(2)->pluck('id')->toArray();

        $response = $this->postJson('/api/pastoral-care/proposals', [
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'client_phone' => '+49 123 456789',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'notes' => 'Test notes',
            'proposal_reason' => 'The available slots do not fit my schedule',
            'theme_ids' => $themes,
        ]);

        $response->assertCreated();

        $proposal = PastoralCare::where('client_email', 'john@example.com')->first();
        expect($proposal)->not->toBeNull();
        expect($proposal->themes)->toHaveCount(2);
        $actualThemeIds = $proposal->themes->pluck('id')->sort()->values()->toArray();
        $expectedThemeIds = collect($themes)->sort()->values()->toArray();
        expect($actualThemeIds)->toBe($expectedThemeIds);
    });

    it('requires at least one theme for a proposal', function (): void {
        $response = $this->postJson('/api/pastoral-care/proposals', [
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'proposal_reason' => 'The available slots do not fit my schedule',
            'theme_ids' => [],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['theme_ids']);
    });

    it('validates that theme ids exist', function (): void {
        $response = $this->postJson('/api/pastoral-care/proposals', [
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
            'duration_minutes' => 60,
            'location_type' => 'in_person',
            'proposal_reason' => 'The available slots do not fit my schedule',
            'theme_ids' => [99999], // Non-existent ID
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['theme_ids.0']);
    });
});

describe('PastoralCareTheme Model', function (): void {
    it('has fillable attributes', function (): void {
        $theme = PastoralCareTheme::factory()->create([
            'name' => 'Test Theme',
            'description' => 'Test description',
            'color' => '#ff0000',
            'icon' => 'heart',
            'is_active' => true,
            'sort_order' => 50,
        ]);

        expect($theme->name)->toBe('Test Theme');
        expect($theme->description)->toBe('Test description');
        expect($theme->color)->toBe('#ff0000');
        expect($theme->icon)->toBe('heart');
        expect($theme->is_active)->toBeTrue();
        expect($theme->sort_order)->toBe(50);
    });

    it('auto-generates slug from name', function (): void {
        $theme = PastoralCareTheme::create([
            'name' => 'Test Theme Name',
            'color' => '#000000',
        ]);

        expect($theme->slug)->toBe('test-theme-name');
    });

    it('has many-to-many relationship with PastoralCare', function (): void {
        $theme = PastoralCareTheme::first();
        $pastoralCare = PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
        ]);

        $pastoralCare->themes()->attach($theme->id);

        expect($theme->pastoralCares)->toHaveCount(1);
        expect($theme->pastoralCares->first()->id)->toBe($pastoralCare->id);
    });

    it('scopes to active themes', function (): void {
        // Create inactive theme
        PastoralCareTheme::factory()->inactive()->create();

        $activeCount = PastoralCareTheme::active()->count();
        $totalCount = PastoralCareTheme::count();

        expect($activeCount)->toBeLessThan($totalCount);
    });

    it('scopes to ordered themes', function (): void {
        // Get first and last theme by order
        $ordered = PastoralCareTheme::ordered()->get();

        $first = $ordered->first();
        $last = $ordered->last();

        expect($first->sort_order)->toBeLessThanOrEqual($last->sort_order);
    });
});

describe('PastoralCare with Themes Relationship', function (): void {
    it('loads themes with pastoral care', function (): void {
        $themes = PastoralCareTheme::take(2)->get();
        $pastoralCare = PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
        ]);

        $pastoralCare->themes()->sync($themes->pluck('id'));

        // Reload with relationship
        $pastoralCare->load('themes');

        expect($pastoralCare->themes)->toHaveCount(2);
    });

    it('can sync themes on pastoral care', function (): void {
        $themes = PastoralCareTheme::take(3)->pluck('id')->toArray();
        $pastoralCare = PastoralCare::factory()->create([
            'pastor_id' => $this->pastor->id,
        ]);

        // Initial sync
        $pastoralCare->themes()->sync([$themes[0], $themes[1]]);
        expect($pastoralCare->themes()->count())->toBe(2);

        // Re-sync with different themes
        $pastoralCare->themes()->sync([$themes[2]]);
        expect($pastoralCare->themes()->count())->toBe(1);
    });
});
