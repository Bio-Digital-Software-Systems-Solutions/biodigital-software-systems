<?php

use App\Models\CareService;
use App\Models\CareServiceAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create roles
    Role::create(['name' => 'pastor']);
    $careServiceAgentRole = Role::create(['name' => 'care-service-agent']);
    Role::create(['name' => 'admin']);

    // Create permissions
    $permissions = [
        'view care service dashboard',
        'view all care service',
        'transfer care service',
        'view care service statistics',
        'view care service',
        'create care service',
        'edit care service',
        'delete care service',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    // Assign permissions to care-service-agent role
    $careServiceAgentRole->syncPermissions([
        'view care service dashboard',
        'view all care service',
        'transfer care service',
        'view care service statistics',
        'view care service',
    ]);
});

// Helper functions with dashboard_ prefix to avoid conflicts with unit tests
function dashboard_createCareServiceAgent(): User
{
    $user = User::factory()->create();
    $user->assignRole('care-service-agent');

    return $user;
}

function dashboard_createPastor(): User
{
    $user = User::factory()->create();
    $user->assignRole('pastor');

    return $user;
}

function dashboard_createRegularUser(): User
{
    return User::factory()->create();
}

describe('Care Service Dashboard Access Control', function (): void {
    test('unauthenticated users cannot access care service dashboard', function (): void {
        $response = $this->get(route('care-service.dashboard'));

        $response->assertRedirect(route('login'));
    });

    test('regular users without permission cannot access care service dashboard', function (): void {
        $user = dashboard_createRegularUser();

        $response = $this->actingAs($user)->get(route('care-service.dashboard'));

        // App converts 403 to redirect with 'unauthorized' session key
        $response->assertRedirect();
        $response->assertSessionHas('unauthorized');
    });

    test('pastors without care service dashboard permission cannot access care service dashboard', function (): void {
        $pastor = dashboard_createPastor();

        $response = $this->actingAs($pastor)->get(route('care-service.dashboard'));

        // App converts 403 to redirect with 'unauthorized' session key
        $response->assertRedirect();
        $response->assertSessionHas('unauthorized');
    });

    test('care service agents can access care service dashboard', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            ->has('stats')
            ->has('appointments')
            ->has('pastors')
        );
    });

    test('admins can access care service dashboard', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Permission::findOrCreate('view care service dashboard');
        $admin->givePermissionTo('view care service dashboard');

        $response = $this->actingAs($admin)->get(route('care-service.dashboard'));

        $response->assertOk();
    });
});

describe('Care Service Dashboard Statistics Endpoint', function (): void {
    test('unauthenticated users cannot access statistics', function (): void {
        $response = $this->get(route('care-service.dashboard.statistics'));

        $response->assertRedirect(route('login'));
    });

    test('care service agents can access statistics endpoint', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor = dashboard_createPastor();

        // Create some appointments
        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard.statistics', ['period' => 'month']));

        $response->assertOk();
        $response->assertJsonStructure([
            'period',
            'overview',
            'average_duration',
            'by_pastor',
            'by_theme',
            'by_status',
            'follow_ups',
            'transfers',
            'trend',
            'incoming',
            'availabilities',
        ]);
    });

    test('statistics endpoint accepts different period parameters', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();

        foreach (['week', 'month', 'quarter', 'year'] as $period) {
            $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard.statistics', ['period' => $period]));

            $response->assertOk();
            $response->assertJsonPath('period.label', match ($period) {
                'week' => 'Cette semaine',
                'month' => 'Ce mois',
                'quarter' => 'Ce trimestre',
                'year' => 'Cette année',
            });
        }
    });
});

describe('Transfer Functionality', function (): void {
    test('unauthenticated users cannot transfer appointments', function (): void {
        $pastor = dashboard_createPastor();
        $appointment = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'status' => 'pending',
        ]);

        $response = $this->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => dashboard_createPastor()->id,
        ]);

        $response->assertRedirect(route('login'));
    });

    test('regular users cannot transfer appointments', function (): void {
        $user = dashboard_createRegularUser();
        $pastor = dashboard_createPastor();
        $appointment = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => dashboard_createPastor()->id,
        ]);

        // App converts 403 to redirect with 'unauthorized' session key
        $response->assertRedirect();
        $response->assertSessionHas('unauthorized');
    });

    test('care service agents can transfer pending appointments', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $originalPastor = dashboard_createPastor();
        $newPastor = dashboard_createPastor();

        $appointment = CareService::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(5),
            'appointment_time' => now()->addDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($careServiceAgent)->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
            'transfer_reason' => 'Pastor is unavailable',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $appointment->refresh();

        expect($appointment->pastor_id)->toBe($newPastor->id);
        expect($appointment->transferred_from_id)->toBe($originalPastor->id);
        expect($appointment->transferred_to_id)->toBe($newPastor->id);
        expect($appointment->transfer_reason)->toBe('Pastor is unavailable');
        expect($appointment->transferred_at)->not->toBeNull();
    });

    test('care service agents can transfer confirmed appointments', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $originalPastor = dashboard_createPastor();
        $newPastor = dashboard_createPastor();

        $appointment = CareService::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'confirmed',
            'appointment_date' => now()->addDays(5),
            'appointment_time' => now()->addDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($careServiceAgent)->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $appointment->refresh();

        expect($appointment->pastor_id)->toBe($newPastor->id);
        expect($appointment->status)->toBe('pending'); // Reset to pending after transfer
    });

    test('cannot transfer to the same pastor', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor = dashboard_createPastor();

        $appointment = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(5),
            'appointment_time' => now()->addDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($careServiceAgent)->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => $pastor->id,
        ]);

        $response->assertSessionHasErrors('transferred_to_id');
    });

    test('cannot transfer completed appointments', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $originalPastor = dashboard_createPastor();
        $newPastor = dashboard_createPastor();

        $appointment = CareService::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'completed',
            'appointment_date' => now()->subDays(5),
            'appointment_time' => now()->subDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($careServiceAgent)->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
        ]);

        // Controller catches exception and returns with 'error' flash message
        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    test('cannot transfer cancelled appointments', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $originalPastor = dashboard_createPastor();
        $newPastor = dashboard_createPastor();

        $appointment = CareService::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($careServiceAgent)->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
        ]);

        // Controller catches exception and returns with 'error' flash message
        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    test('transfer requires a valid user ID', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor = dashboard_createPastor();

        $appointment = CareService::factory()->create([
            'pastor_id' => $pastor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($careServiceAgent)->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => 99999, // Non-existent user
        ]);

        $response->assertSessionHasErrors('transferred_to_id');
    });

    test('transfer reason is optional', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $originalPastor = dashboard_createPastor();
        $newPastor = dashboard_createPastor();

        $appointment = CareService::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(5),
            'appointment_time' => now()->addDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($careServiceAgent)->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
            // No transfer_reason provided
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $appointment->refresh();
        expect($appointment->transfer_reason)->toBeNull();
    });

    test('transfer reason has max length validation', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $originalPastor = dashboard_createPastor();
        $newPastor = dashboard_createPastor();

        $appointment = CareService::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($careServiceAgent)->post(route('care-service.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
            'transfer_reason' => str_repeat('a', 1001), // Over 1000 character limit
        ]);

        $response->assertSessionHasErrors('transfer_reason');
    });
});

describe('Care Service Dashboard Data', function (): void {
    test('dashboard shows correct appointment counts by status', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor = dashboard_createPastor();

        // Create appointments with different statuses
        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
        ]);

        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'confirmed',
        ]);

        CareService::factory()->count(5)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(15),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            ->where('stats.overview.pending', 3)
            ->where('stats.overview.confirmed', 2)
            ->where('stats.overview.completed', 5)
            ->where('stats.overview.total', 10)
        );
    });

    test('dashboard includes availabilities for all pastors', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor1 = dashboard_createPastor();
        $pastor2 = dashboard_createPastor();

        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor1->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'is_active' => true,
        ]);

        CareServiceAvailability::factory()->create([
            'pastor_id' => $pastor2->id,
            'type' => 'weekly',
            'day_of_week' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            ->has('stats.availabilities', 2)
        );
    });

    test('dashboard includes list of pastors for transfer dropdown', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $pastor1->assignRole('pastor');
        $pastor2 = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Martin']);
        $pastor2->assignRole('pastor');

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            // care service agent is also included in pastors list (3 = 1 care-service-agent + 2 pastors)
            ->has('pastors', 3)
        );
    });
});

describe('Care Service Dashboard Pagination', function (): void {
    test('appointments are paginated', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor = dashboard_createPastor();

        // Create 25 appointments
        CareService::factory()->count(25)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
        ]);

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            ->has('appointments.data') // Paginated data
            ->has('appointments.meta')
        );
    });
});

describe('Care Service Dashboard Period Filter', function (): void {
    test('can filter by week period', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor = dashboard_createPastor();

        // Create appointments this week
        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfWeek()->addDays(2),
        ]);

        // Create appointments last week
        CareService::factory()->count(5)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->subWeek()->startOfWeek()->addDays(2),
        ]);

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard', ['period' => 'week']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            ->where('stats.overview.total', 3)
        );
    });
});

describe('Care Service Dashboard Analytics Data', function (): void {
    test('dashboard includes analytics data', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor = dashboard_createPastor();

        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'completed',
            'theme' => 'spiritual_guidance',
            'location_type' => 'in_person',
        ]);

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            ->has('stats.analytics')
            ->has('stats.analytics.appointments_by_status')
            ->has('stats.analytics.appointments_by_theme')
            ->has('stats.analytics.appointments_by_pastor')
            ->has('stats.analytics.appointments_by_mode')
            ->has('stats.analytics.global_progress')
            ->has('stats.analytics.velocity')
            ->has('stats.analytics.appointment_evolution')
            ->has('stats.analytics.completion_by_pastor')
        );
    });

    test('analytics includes correct status distribution', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();
        $pastor = dashboard_createPastor();

        CareService::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
        ]);

        CareService::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            ->where('stats.analytics.global_progress.total', 5)
            ->where('stats.analytics.global_progress.completed', 3)
            ->where('stats.analytics.global_progress.percentage', 60)
        );
    });

    test('analytics includes velocity metrics', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            ->has('stats.analytics.velocity.daily')
            ->has('stats.analytics.velocity.weekly')
            ->has('stats.analytics.velocity.monthly')
        );
    });

    test('analytics includes evolution data for multiple periods', function (): void {
        $careServiceAgent = dashboard_createCareServiceAgent();

        $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): \Inertia\Testing\AssertableInertia => $page
            ->component('CareService/Dashboard')
            ->has('stats.analytics.appointment_evolution.weekly')
            ->has('stats.analytics.appointment_evolution.monthly')
            ->has('stats.analytics.appointment_evolution.quarterly')
        );
    });
});
