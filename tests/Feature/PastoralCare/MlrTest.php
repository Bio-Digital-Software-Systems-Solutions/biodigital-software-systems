<?php

use App\Models\PastoralCare;
use App\Models\PastorAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'pastor']);
    $mlrAgentRole = Role::create(['name' => 'mlr_agent']);
    Role::create(['name' => 'admin']);

    // Create permissions
    $permissions = [
        'view mlr dashboard',
        'view all pastoral care',
        'transfer pastoral care',
        'view pastoral care statistics',
        'view pastoral care',
        'create pastoral care',
        'edit pastoral care',
        'delete pastoral care',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    // Assign permissions to mlr_agent role
    $mlrAgentRole->syncPermissions([
        'view mlr dashboard',
        'view all pastoral care',
        'transfer pastoral care',
        'view pastoral care statistics',
        'view pastoral care',
    ]);
});

// Helper functions with mlr_ prefix to avoid conflicts with unit tests
function mlr_createMlrAgent(): User
{
    $user = User::factory()->create();
    $user->assignRole('mlr_agent');

    return $user;
}

function mlr_createPastor(): User
{
    $user = User::factory()->create();
    $user->assignRole('pastor');

    return $user;
}

function mlr_createRegularUser(): User
{
    return User::factory()->create();
}

describe('MLR Dashboard Access Control', function () {
    test('unauthenticated users cannot access MLR dashboard', function () {
        $response = $this->get(route('pastoral-care.mlr'));

        $response->assertRedirect(route('login'));
    });

    test('regular users without permission cannot access MLR dashboard', function () {
        $user = mlr_createRegularUser();

        $response = $this->actingAs($user)->get(route('pastoral-care.mlr'));

        // App converts 403 to redirect with 'unauthorized' session key
        $response->assertRedirect();
        $response->assertSessionHas('unauthorized');
    });

    test('pastors without MLR permission cannot access MLR dashboard', function () {
        $pastor = mlr_createPastor();

        $response = $this->actingAs($pastor)->get(route('pastoral-care.mlr'));

        // App converts 403 to redirect with 'unauthorized' session key
        $response->assertRedirect();
        $response->assertSessionHas('unauthorized');
    });

    test('MLR agents can access MLR dashboard', function () {
        $mlrAgent = mlr_createMlrAgent();

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
            ->has('stats')
            ->has('appointments')
            ->has('pastors')
        );
    });

    test('admins can access MLR dashboard', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Permission::findOrCreate('view mlr dashboard');
        $admin->givePermissionTo('view mlr dashboard');

        $response = $this->actingAs($admin)->get(route('pastoral-care.mlr'));

        $response->assertOk();
    });
});

describe('MLR Statistics Endpoint', function () {
    test('unauthenticated users cannot access statistics', function () {
        $response = $this->get(route('pastoral-care.mlr.statistics'));

        $response->assertRedirect(route('login'));
    });

    test('MLR agents can access statistics endpoint', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor = mlr_createPastor();

        // Create some appointments
        PastoralCare::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr.statistics', ['period' => 'month']));

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

    test('statistics endpoint accepts different period parameters', function () {
        $mlrAgent = mlr_createMlrAgent();

        foreach (['week', 'month', 'quarter', 'year'] as $period) {
            $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr.statistics', ['period' => $period]));

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

describe('Transfer Functionality', function () {
    test('unauthenticated users cannot transfer appointments', function () {
        $pastor = mlr_createPastor();
        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $pastor->id,
            'status' => 'pending',
        ]);

        $response = $this->post(route('pastoral-care.transfer', $appointment->uuid), [
            'transferred_to_id' => mlr_createPastor()->id,
        ]);

        $response->assertRedirect(route('login'));
    });

    test('regular users cannot transfer appointments', function () {
        $user = mlr_createRegularUser();
        $pastor = mlr_createPastor();
        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $pastor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('pastoral-care.transfer', $appointment->uuid), [
            'transferred_to_id' => mlr_createPastor()->id,
        ]);

        // App converts 403 to redirect with 'unauthorized' session key
        $response->assertRedirect();
        $response->assertSessionHas('unauthorized');
    });

    test('MLR agents can transfer pending appointments', function () {
        $mlrAgent = mlr_createMlrAgent();
        $originalPastor = mlr_createPastor();
        $newPastor = mlr_createPastor();

        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(5),
            'appointment_time' => now()->addDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($mlrAgent)->post(route('pastoral-care.transfer', $appointment->uuid), [
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

    test('MLR agents can transfer confirmed appointments', function () {
        $mlrAgent = mlr_createMlrAgent();
        $originalPastor = mlr_createPastor();
        $newPastor = mlr_createPastor();

        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'confirmed',
            'appointment_date' => now()->addDays(5),
            'appointment_time' => now()->addDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($mlrAgent)->post(route('pastoral-care.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $appointment->refresh();

        expect($appointment->pastor_id)->toBe($newPastor->id);
        expect($appointment->status)->toBe('pending'); // Reset to pending after transfer
    });

    test('cannot transfer to the same pastor', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor = mlr_createPastor();

        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $pastor->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(5),
            'appointment_time' => now()->addDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($mlrAgent)->post(route('pastoral-care.transfer', $appointment->uuid), [
            'transferred_to_id' => $pastor->id,
        ]);

        $response->assertSessionHasErrors('transferred_to_id');
    });

    test('cannot transfer completed appointments', function () {
        $mlrAgent = mlr_createMlrAgent();
        $originalPastor = mlr_createPastor();
        $newPastor = mlr_createPastor();

        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'completed',
            'appointment_date' => now()->subDays(5),
            'appointment_time' => now()->subDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($mlrAgent)->post(route('pastoral-care.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
        ]);

        // Controller catches exception and returns with 'error' flash message
        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    test('cannot transfer cancelled appointments', function () {
        $mlrAgent = mlr_createMlrAgent();
        $originalPastor = mlr_createPastor();
        $newPastor = mlr_createPastor();

        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($mlrAgent)->post(route('pastoral-care.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
        ]);

        // Controller catches exception and returns with 'error' flash message
        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    test('transfer requires a valid user ID', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor = mlr_createPastor();

        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $pastor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($mlrAgent)->post(route('pastoral-care.transfer', $appointment->uuid), [
            'transferred_to_id' => 99999, // Non-existent user
        ]);

        $response->assertSessionHasErrors('transferred_to_id');
    });

    test('transfer reason is optional', function () {
        $mlrAgent = mlr_createMlrAgent();
        $originalPastor = mlr_createPastor();
        $newPastor = mlr_createPastor();

        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(5),
            'appointment_time' => now()->addDays(5)->setHour(10),
        ]);

        $response = $this->actingAs($mlrAgent)->post(route('pastoral-care.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
            // No transfer_reason provided
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $appointment->refresh();
        expect($appointment->transfer_reason)->toBeNull();
    });

    test('transfer reason has max length validation', function () {
        $mlrAgent = mlr_createMlrAgent();
        $originalPastor = mlr_createPastor();
        $newPastor = mlr_createPastor();

        $appointment = PastoralCare::factory()->create([
            'pastor_id' => $originalPastor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($mlrAgent)->post(route('pastoral-care.transfer', $appointment->uuid), [
            'transferred_to_id' => $newPastor->id,
            'transfer_reason' => str_repeat('a', 1001), // Over 1000 character limit
        ]);

        $response->assertSessionHasErrors('transfer_reason');
    });
});

describe('MLR Dashboard Data', function () {
    test('dashboard shows correct appointment counts by status', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor = mlr_createPastor();

        // Create appointments with different statuses
        PastoralCare::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
        ]);

        PastoralCare::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'confirmed',
        ]);

        PastoralCare::factory()->count(5)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(15),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
            ->where('stats.overview.pending', 3)
            ->where('stats.overview.confirmed', 2)
            ->where('stats.overview.completed', 5)
            ->where('stats.overview.total', 10)
        );
    });

    test('dashboard includes availabilities for all pastors', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor1 = mlr_createPastor();
        $pastor2 = mlr_createPastor();

        PastorAvailability::factory()->create([
            'pastor_id' => $pastor1->id,
            'type' => 'weekly',
            'day_of_week' => 1,
            'is_active' => true,
        ]);

        PastorAvailability::factory()->create([
            'pastor_id' => $pastor2->id,
            'type' => 'weekly',
            'day_of_week' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
            ->has('stats.availabilities', 2)
        );
    });

    test('dashboard includes list of pastors for transfer dropdown', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor1 = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $pastor1->assignRole('pastor');
        $pastor2 = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Martin']);
        $pastor2->assignRole('pastor');

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
            // mlr_agent is also included in pastors list (3 = 1 mlr_agent + 2 pastors)
            ->has('pastors', 3)
        );
    });
});

describe('MLR Dashboard Pagination', function () {
    test('appointments are paginated', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor = mlr_createPastor();

        // Create 25 appointments
        PastoralCare::factory()->count(25)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
        ]);

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
            ->has('appointments.data') // Paginated data
            ->has('appointments.meta')
        );
    });
});

describe('MLR Dashboard Period Filter', function () {
    test('can filter by week period', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor = mlr_createPastor();

        // Create appointments this week
        PastoralCare::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfWeek()->addDays(2),
        ]);

        // Create appointments last week
        PastoralCare::factory()->count(5)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->subWeek()->startOfWeek()->addDays(2),
        ]);

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr', ['period' => 'week']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
            ->where('stats.overview.total', 3)
        );
    });
});

describe('MLR Analytics Data', function () {
    test('dashboard includes analytics data', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor = mlr_createPastor();

        PastoralCare::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'completed',
            'theme' => 'spiritual_guidance',
            'location_type' => 'in_person',
        ]);

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
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

    test('analytics includes correct status distribution', function () {
        $mlrAgent = mlr_createMlrAgent();
        $pastor = mlr_createPastor();

        PastoralCare::factory()->count(2)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(5),
            'status' => 'pending',
        ]);

        PastoralCare::factory()->count(3)->create([
            'pastor_id' => $pastor->id,
            'appointment_date' => now()->startOfMonth()->addDays(10),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
            ->where('stats.analytics.global_progress.total', 5)
            ->where('stats.analytics.global_progress.completed', 3)
            ->where('stats.analytics.global_progress.percentage', 60)
        );
    });

    test('analytics includes velocity metrics', function () {
        $mlrAgent = mlr_createMlrAgent();

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
            ->has('stats.analytics.velocity.daily')
            ->has('stats.analytics.velocity.weekly')
            ->has('stats.analytics.velocity.monthly')
        );
    });

    test('analytics includes evolution data for multiple periods', function () {
        $mlrAgent = mlr_createMlrAgent();

        $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PastoralCare/Mlr')
            ->has('stats.analytics.appointment_evolution.weekly')
            ->has('stats.analytics.appointment_evolution.monthly')
            ->has('stats.analytics.appointment_evolution.quarterly')
        );
    });
});
