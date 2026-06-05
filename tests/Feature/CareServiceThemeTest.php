<?php

use App\Models\CareService;
use App\Models\CareServiceTheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Seed roles and permissions
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

    // Seed default themes
    $this->artisan('db:seed', ['--class' => 'CareServiceThemeSeeder']);

    // Create a pastor user
    $this->pastor = User::factory()->create();
    $this->pastor->assignRole('pastor');

    // Create availability for pastor
    \App\Models\CareServiceAvailability::create([
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
        $response = $this->getJson('/api/care-service/themes');

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
        $theme = CareServiceTheme::first();
        $theme->update(['is_active' => false]);

        $response = $this->getJson('/api/care-service/themes');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(12);

        // Reactivate theme
        $theme->update(['is_active' => true]);
    });

    it('returns themes in sort order', function (): void {
        $response = $this->getJson('/api/care-service/themes');

        $themes = $response->json('data');
        $firstTheme = $themes[0];
        $lastTheme = $themes[count($themes) - 1];

        expect($firstTheme['slug'])->toBe('spiritual-guidance'); // sort_order: 1
        expect($lastTheme['slug'])->toBe('other'); // sort_order: 99
    });
});

describe('Proposal with Themes', function (): void {
    it('can create a proposal with themes', function (): void {
        $themes = CareServiceTheme::take(2)->pluck('id')->toArray();

        $response = $this->postJson('/api/care-service/proposals', [
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

        $proposal = CareService::where('client_email', 'john@example.com')->first();
        expect($proposal)->not->toBeNull();
        expect($proposal->themes)->toHaveCount(2);
        $actualThemeIds = $proposal->themes->pluck('id')->sort()->values()->toArray();
        $expectedThemeIds = collect($themes)->sort()->values()->toArray();
        expect($actualThemeIds)->toBe($expectedThemeIds);
    });

    it('requires at least one theme for a proposal', function (): void {
        $response = $this->postJson('/api/care-service/proposals', [
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
        $response = $this->postJson('/api/care-service/proposals', [
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

describe('CareServiceTheme Model', function (): void {
    it('has fillable attributes', function (): void {
        $theme = CareServiceTheme::factory()->create([
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
        $theme = CareServiceTheme::create([
            'name' => 'Test Theme Name',
            'color' => '#000000',
        ]);

        expect($theme->slug)->toBe('test-theme-name');
    });

    it('has many-to-many relationship with CareService', function (): void {
        $theme = CareServiceTheme::first();
        $careService = CareService::factory()->create([
            'pastor_id' => $this->pastor->id,
        ]);

        $careService->themes()->attach($theme->id);

        expect($theme->careServices)->toHaveCount(1);
        expect($theme->careServices->first()->id)->toBe($careService->id);
    });

    it('scopes to active themes', function (): void {
        // Create inactive theme
        CareServiceTheme::factory()->inactive()->create();

        $activeCount = CareServiceTheme::active()->count();
        $totalCount = CareServiceTheme::count();

        expect($activeCount)->toBeLessThan($totalCount);
    });

    it('scopes to ordered themes', function (): void {
        // Get first and last theme by order
        $ordered = CareServiceTheme::ordered()->get();

        $first = $ordered->first();
        $last = $ordered->last();

        expect($first->sort_order)->toBeLessThanOrEqual($last->sort_order);
    });
});

describe('CareService with Themes Relationship', function (): void {
    it('loads themes with care service', function (): void {
        $themes = CareServiceTheme::take(2)->get();
        $careService = CareService::factory()->create([
            'pastor_id' => $this->pastor->id,
        ]);

        $careService->themes()->sync($themes->pluck('id'));

        // Reload with relationship
        $careService->load('themes');

        expect($careService->themes)->toHaveCount(2);
    });

    it('can sync themes on care service', function (): void {
        $themes = CareServiceTheme::take(3)->pluck('id')->toArray();
        $careService = CareService::factory()->create([
            'pastor_id' => $this->pastor->id,
        ]);

        // Initial sync
        $careService->themes()->sync([$themes[0], $themes[1]]);
        expect($careService->themes()->count())->toBe(2);

        // Re-sync with different themes
        $careService->themes()->sync([$themes[2]]);
        expect($careService->themes()->count())->toBe(1);
    });
});
