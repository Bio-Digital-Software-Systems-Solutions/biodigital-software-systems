<?php

use App\Models\CareService;
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
    $careServiceAgentRole = Role::create(['name' => 'care-service-agent']);
    $pastorRole = Role::create(['name' => 'pastor']);
    $memberRole = Role::create(['name' => 'member']);

    // Create permissions
    $viewCareServiceDashboard = Permission::create(['name' => 'view care service dashboard']);
    $viewCareService = Permission::create(['name' => 'view care service']);
    Permission::create(['name' => 'transfer care service']);
    Permission::create(['name' => 'view all care service']);
    Permission::create(['name' => 'view care service statistics']);

    // Assign permissions to roles
    $adminRole->givePermissionTo([
        'view care service dashboard',
        'view care service',
        'transfer care service',
        'view all care service',
        'view care service statistics',
    ]);
    $superAdminRole->givePermissionTo([
        'view care service dashboard',
        'view care service',
        'transfer care service',
        'view all care service',
        'view care service statistics',
    ]);
    // care-service-agent can access care service dashboard but only sees their own appointments
    // They do NOT have "view all care service" permission by default
    $careServiceAgentRole->givePermissionTo([
        'view care service dashboard',
        'view care service',
        'transfer care service',
        'view care service statistics',
    ]);
    $pastorRole->givePermissionTo([
        'view care service dashboard',
        'view care service',
        'view care service statistics',
    ]);
    $memberRole->givePermissionTo([
        'view care service',
    ]);
});

it('allows admin to see all appointments in care service dashboard', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    // Create appointments for different pastors with polymorphic assignment
    CareService::factory()->count(3)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    CareService::factory()->count(2)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($admin)->get(route('care-service.dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('CareService/Dashboard')
        ->has('appointments.data', 5)
    );
});

it('allows super-admin to see all appointments in care service dashboard', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    CareService::factory()->count(3)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    CareService::factory()->count(2)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($superAdmin)->get(route('care-service.dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('CareService/Dashboard')
        ->has('appointments.data', 5)
    );
});

it('allows user with view all care service permission to see all appointments', function (): void {
    // Create a user with special permission to view all
    $specialUser = User::factory()->create();
    $specialUser->assignRole('care-service-agent');
    $specialUser->givePermissionTo('view all care service');

    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    CareService::factory()->count(3)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    CareService::factory()->count(2)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($specialUser)->get(route('care-service.dashboard'));

    $response->assertStatus(200);
    // User with "view all care service" permission should see all 5 appointments
    $response->assertInertia(fn ($page) => $page
        ->component('CareService/Dashboard')
        ->has('appointments.data', 5)
    );
});

it('allows care-service-agent to only see their own appointments in care service dashboard', function (): void {
    $careServiceAgent = User::factory()->create();
    $careServiceAgent->assignRole('care-service-agent');

    $pastor = User::factory()->create();
    $pastor->assignRole('pastor');

    // Create appointments assigned to the care-service-agent using polymorphic relationship
    CareService::factory()->count(3)->create([
        'pastor_id' => null, // care service agents don't use pastor_id
        'assigned_agent_id' => $careServiceAgent->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Create appointments for a pastor (care-service-agent should NOT see these)
    CareService::factory()->count(5)->create([
        'pastor_id' => $pastor->id,
        'assigned_agent_id' => $pastor->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

    $response->assertStatus(200);
    // care-service-agent should only see their own 3 appointments, not all 8
    $response->assertInertia(fn ($page) => $page
        ->component('CareService/Dashboard')
        ->has('appointments.data', 3)
    );
});

it('allows pastor to only see their own appointments in care service dashboard', function (): void {
    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    // Create appointments for pastor1 - uses pastor_id which syncs to assigned_agent
    CareService::factory()->count(3)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Create appointments for pastor2
    CareService::factory()->count(5)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Pastor1 should only see their 3 appointments
    $response = $this->actingAs($pastor1)->get(route('care-service.dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('CareService/Dashboard')
        ->has('appointments.data', 3)
    );
});

it('denies member access to care service dashboard', function (): void {
    $member = User::factory()->create();
    $member->assignRole('member');

    $response = $this->actingAs($member)->get(route('care-service.dashboard'));

    // Members without proper permission are denied (redirect or 403)
    expect($response->getStatusCode())->toBeIn([302, 403]);
});

it('denies access to users without view care service dashboard permission', function (): void {
    $user = User::factory()->create();
    // No role, no permissions

    $response = $this->actingAs($user)->get(route('care-service.dashboard'));

    // Users without permission are denied (redirect or 403)
    expect($response->getStatusCode())->toBeIn([302, 403]);
});

it('filters statistics for pastor viewing care service dashboard', function (): void {
    $pastor1 = User::factory()->create();
    $pastor1->assignRole('pastor');

    $pastor2 = User::factory()->create();
    $pastor2->assignRole('pastor');

    // Create completed appointments for pastor1
    CareService::factory()->count(2)->create([
        'pastor_id' => $pastor1->id,
        'assigned_agent_id' => $pastor1->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
        'status' => 'completed',
    ]);

    // Create completed appointments for pastor2
    CareService::factory()->count(5)->create([
        'pastor_id' => $pastor2->id,
        'assigned_agent_id' => $pastor2->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
        'status' => 'completed',
    ]);

    // Pastor1's statistics should only reflect their 2 appointments
    $response = $this->actingAs($pastor1)->get(route('care-service.dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('CareService/Dashboard')
        ->where('stats.overview.total', 2)
        ->where('stats.overview.completed', 2)
    );
});

it('care-service-agent sees filtered statistics for their own appointments', function (): void {
    $careServiceAgent = User::factory()->create();
    $careServiceAgent->assignRole('care-service-agent');

    $pastor = User::factory()->create();
    $pastor->assignRole('pastor');

    // Create completed appointments for the care-service-agent
    CareService::factory()->count(4)->create([
        'pastor_id' => null,
        'assigned_agent_id' => $careServiceAgent->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
        'status' => 'completed',
    ]);

    // Create completed appointments for a pastor (care-service-agent should NOT see these in stats)
    CareService::factory()->count(10)->create([
        'pastor_id' => $pastor->id,
        'assigned_agent_id' => $pastor->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
        'status' => 'completed',
    ]);

    // care service agent's statistics should only reflect their 4 appointments
    $response = $this->actingAs($careServiceAgent)->get(route('care-service.dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('CareService/Dashboard')
        ->where('stats.overview.total', 4)
        ->where('stats.overview.completed', 4)
    );
});

it('polymorphic assigned_agent relationship is synced with pastor_id on creation', function (): void {
    $pastor = User::factory()->create();
    $pastor->assignRole('pastor');

    // Create appointment with only pastor_id set
    $appointment = CareService::factory()->create([
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

    $careServiceAgent = User::factory()->create();
    $careServiceAgent->assignRole('care-service-agent');

    // Create appointment with pastor
    $appointment = CareService::factory()->create([
        'pastor_id' => $pastor->id,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Use assignAgent to reassign to care service agent
    $appointment->assignAgent($careServiceAgent);

    $appointment->refresh();

    expect($appointment->assigned_agent_id)->toBe($careServiceAgent->id);
    expect($appointment->assigned_agent_type)->toBe(User::class);
    expect($appointment->assignedAgent->id)->toBe($careServiceAgent->id);
});

it('forAssignedAgent scope filters appointments correctly', function (): void {
    $careServiceAgent = User::factory()->create();
    $careServiceAgent->assignRole('care-service-agent');

    $pastor = User::factory()->create();
    $pastor->assignRole('pastor');

    // Create appointments for care-service-agent
    CareService::factory()->count(3)->create([
        'pastor_id' => null,
        'assigned_agent_id' => $careServiceAgent->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Create appointments for pastor
    CareService::factory()->count(5)->create([
        'pastor_id' => $pastor->id,
        'assigned_agent_id' => $pastor->id,
        'assigned_agent_type' => User::class,
        'appointment_date' => Carbon::now()->format('Y-m-d'),
    ]);

    // Use scope to filter by care-service-agent
    $careServiceAgentAppointments = CareService::forAssignedAgent($careServiceAgent->id, User::class)->get();
    $pastorAppointments = CareService::forAssignedAgent($pastor->id)->get();

    expect($careServiceAgentAppointments)->toHaveCount(3);
    expect($pastorAppointments)->toHaveCount(5);
});
