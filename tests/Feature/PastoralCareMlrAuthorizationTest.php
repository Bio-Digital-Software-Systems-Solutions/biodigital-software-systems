<?php

use App\Models\PastoralCare;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create roles
    $adminRole = Role::create(['name' => 'admin']);
    $superAdminRole = Role::create(['name' => 'super-admin']);
    $mlrAgentRole = Role::create(['name' => 'mlr-agent']);
    $pastorRole = Role::create(['name' => 'pastor']);
    $memberRole = Role::create(['name' => 'member']);

    // Create permissions
    $viewMlrDashboard = Permission::create(['name' => 'view mlr dashboard']);
    $viewPastoralCare = Permission::create(['name' => 'view pastoral care']);
    Permission::create(['name' => 'transfer pastoral care']);
    Permission::create(['name' => 'view all pastoral care']);
    Permission::create(['name' => 'view pastoral care statistics']);

    // Assign permissions to roles
    $adminRole->givePermissionTo([
        'view mlr dashboard',
        'view pastoral care',
        'transfer pastoral care',
        'view all pastoral care',
        'view pastoral care statistics',
    ]);
    $superAdminRole->givePermissionTo([
        'view mlr dashboard',
        'view pastoral care',
        'transfer pastoral care',
        'view all pastoral care',
        'view pastoral care statistics',
    ]);
    // mlr-agent can access MLR dashboard but only sees their own appointments
    // They do NOT have "view all pastoral care" permission by default
    $mlrAgentRole->givePermissionTo([
        'view mlr dashboard',
        'view pastoral care',
        'transfer pastoral care',
        'view pastoral care statistics',
    ]);
    $pastorRole->givePermissionTo([
        'view mlr dashboard',
        'view pastoral care',
        'view pastoral care statistics',
    ]);
    $memberRole->givePermissionTo([
        'view pastoral care',
    ]);
});

it('allows admin to see all appointments in MLR dashboard', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    // Create appointments for different pastors with polymorphic assignment
    PastoralCare::factory()->count(3)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    PastoralCare::factory()->count(2)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($admin)->get(route('pastoral-care.mlr'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('PastoralCare/Mlr')
        ->has('appointments.data', 5)
    );
});

it('allows super-admin to see all appointments in MLR dashboard', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    PastoralCare::factory()->count(3)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    PastoralCare::factory()->count(2)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($superAdmin)->get(route('pastoral-care.mlr'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('PastoralCare/Mlr')
        ->has('appointments.data', 5)
    );
});

it('allows user with view all pastoral care permission to see all appointments', function (): void {
    // Create a user with special permission to view all
    $specialUser = User::factory()->create();
    $specialUser->assignRole('mlr-agent');
    $specialUser->givePermissionTo('view all pastoral care');

    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    PastoralCare::factory()->count(3)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    PastoralCare::factory()->count(2)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($specialUser)->get(route('pastoral-care.mlr'));

    $response->assertStatus(200);
    // User with "view all pastoral care" permission should see all 5 appointments
    $response->assertInertia(fn ($page) => $page
        ->component('PastoralCare/Mlr')
        ->has('appointments.data', 5)
    );
});

it('allows mlr-agent to only see their own appointments in MLR dashboard', function (): void {
    $mlrAgent = User::factory()->create();
    $mlrAgent->assignRole('mlr-agent');

    $pastor = User::factory()->create();
    $pastor->assignRole('pastor');

    // Create appointments assigned to the mlr-agent using polymorphic relationship
    PastoralCare::factory()->count(3)->create([
        'pastor_id' => null, // MLR agents don't use pastor_id
        'assigned_agent_id' => $mlrAgent->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Create appointments for a pastor (mlr-agent should NOT see these)
    PastoralCare::factory()->count(5)->create([
        'pastor_id' => $pastor->id,
        'assigned_agent_id' => $pastor->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

    $response->assertStatus(200);
    // mlr-agent should only see their own 3 appointments, not all 8
    $response->assertInertia(fn ($page) => $page
        ->component('PastoralCare/Mlr')
        ->has('appointments.data', 3)
    );
});

it('allows pastor to only see their own appointments in MLR dashboard', function (): void {
    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    // Create appointments for pastor1 - uses pastor_id which syncs to assigned_agent
    PastoralCare::factory()->count(3)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Create appointments for pastor2
    PastoralCare::factory()->count(5)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Pastor1 should only see their 3 appointments
    $response = $this->actingAs($pastor1)->get(route('pastoral-care.mlr'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('PastoralCare/Mlr')
        ->has('appointments.data', 3)
    );
});

it('denies member access to MLR dashboard', function (): void {
    $member = User::factory()->create();
    $member->assignRole('member');

    $response = $this->actingAs($member)->get(route('pastoral-care.mlr'));

    // Members without proper permission are denied (redirect or 403)
    expect($response->getStatusCode())->toBeIn([302, 403]);
});

it('denies access to users without view mlr dashboard permission', function (): void {
    $user = User::factory()->create();
    // No role, no permissions

    $response = $this->actingAs($user)->get(route('pastoral-care.mlr'));

    // Users without permission are denied (redirect or 403)
    expect($response->getStatusCode())->toBeIn([302, 403]);
});

it('filters statistics for pastor viewing MLR dashboard', function (): void {
    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    // Create completed appointments for pastor1
    PastoralCare::factory()->count(2)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
        'status' => 'completed',
    ]);

    // Create completed appointments for pastor2
    PastoralCare::factory()->count(5)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
        'status' => 'completed',
    ]);

    // Pastor1's statistics should only reflect their 2 appointments
    $response = $this->actingAs($pastor1)->get(route('pastoral-care.mlr'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('PastoralCare/Mlr')
        ->where('stats.overview.total', 2)
        ->where('stats.overview.completed', 2)
    );
});

it('mlr-agent sees filtered statistics for their own appointments', function (): void {
    $mlrAgent = User::factory()->create();
    $mlrAgent->assignRole('mlr-agent');

    $pastor = User::factory()->create();
    $pastor->assignRole('pastor');

    // Create completed appointments for the mlr-agent
    PastoralCare::factory()->count(4)->create([
        'pastor_id' => null,
        'assigned_agent_id' => $mlrAgent->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
        'status' => 'completed',
    ]);

    // Create completed appointments for a pastor (mlr-agent should NOT see these in stats)
    PastoralCare::factory()->count(10)->create([
        'pastor_id' => $pastor->id,
        'assigned_agent_id' => $pastor->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
        'status' => 'completed',
    ]);

    // MLR agent's statistics should only reflect their 4 appointments
    $response = $this->actingAs($mlrAgent)->get(route('pastoral-care.mlr'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('PastoralCare/Mlr')
        ->where('stats.overview.total', 4)
        ->where('stats.overview.completed', 4)
    );
});

it('polymorphic assigned_agent relationship is synced with pastor_id on creation', function (): void {
    $pastor = User::factory()->create();
    $pastor->assignRole('pastor');

    // Create appointment with only pastor_id set
    $appointment = PastoralCare::factory()->create([
        'pastor_id' => $pastor->id,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Verify that assigned_agent is automatically synced
    expect($appointment->assigned_agent_id)->toBe($pastor->id);
    expect($appointment->assigned_agent_type)->toBe(User::class);
    expect($appointment->assignedAgent)->toBeInstanceOf(User::class);
    expect($appointment->assignedAgent->id)->toBe($pastor->id);
});

it('assignAgent method properly assigns polymorphic agent', function (): void {
    $pastor = User::factory()->create();
    $pastor->assignRole('pastor');

    $mlrAgent = User::factory()->create();
    $mlrAgent->assignRole('mlr-agent');

    // Create appointment with pastor
    $appointment = PastoralCare::factory()->create([
        'pastor_id' => $pastor->id,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Use assignAgent to reassign to MLR agent
    $appointment->assignAgent($mlrAgent);

    $appointment->refresh();

    expect($appointment->assigned_agent_id)->toBe($mlrAgent->id);
    expect($appointment->assigned_agent_type)->toBe(User::class);
    expect($appointment->assignedAgent->id)->toBe($mlrAgent->id);
});

it('forAssignedAgent scope filters appointments correctly', function (): void {
    $mlrAgent = User::factory()->create();
    $mlrAgent->assignRole('mlr-agent');

    $pastor = User::factory()->create();
    $pastor->assignRole('pastor');

    // Create appointments for mlr-agent
    PastoralCare::factory()->count(3)->create([
        'pastor_id' => null,
        'assigned_agent_id' => $mlrAgent->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Create appointments for pastor
    PastoralCare::factory()->count(5)->create([
        'pastor_id' => $pastor->id,
        'assigned_agent_id' => $pastor->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Use scope to filter by mlr-agent
    $mlrAgentAppointments = PastoralCare::forAssignedAgent($mlrAgent->id, User::class)->get();
    $pastorAppointments = PastoralCare::forAssignedAgent($pastor->id)->get();

    expect($mlrAgentAppointments)->toHaveCount(3);
    expect($pastorAppointments)->toHaveCount(5);
});
