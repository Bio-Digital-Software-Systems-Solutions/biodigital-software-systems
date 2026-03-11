<?php

use App\Models\Accounting\AccountingSystem;
use App\Models\Accounting\IfrsAccountClass;
use App\Models\Accounting\OhadaAccountClass;
use App\Models\Accounting\OhadaFinancialStatement;
use App\Models\Accounting\PcgAccountClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::create(['name' => 'view accounting']);
    Permission::create(['name' => 'manage accounting']);

    $adminRole = Role::create(['name' => 'admin']);
    $adminRole->givePermissionTo(['view accounting', 'manage accounting']);

    Role::create(['name' => 'member']);
});

it('shows accounting dashboard to authorized user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/accounting');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Accounting/Index'));
});

it('denies access to unauthorized user', function () {
    $user = User::factory()->create();
    $user->assignRole('member');

    // App redirects with 'unauthorized' flash instead of 403
    $response = $this->actingAs($user)->get('/accounting');

    $response->assertRedirect();
    $response->assertSessionHas('unauthorized');
});

it('denies access to unauthenticated user', function () {
    $response = $this->get('/accounting');

    $response->assertRedirect('/login');
});

it('passes correct props to the page', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    OhadaAccountClass::factory()->create();
    PcgAccountClass::factory()->create();
    IfrsAccountClass::factory()->create();
    $system = AccountingSystem::factory()->create();
    OhadaFinancialStatement::factory()->create(['accounting_system_id' => $system->id]);

    $response = $this->actingAs($user)->get('/accounting');

    $response->assertInertia(fn ($page) => $page
        ->component('Accounting/Index')
        ->has('ohadaClasses')
        ->has('pcgClasses')
        ->has('ifrsClasses')
        ->has('accountingSystems')
        ->has('financialStatements')
        ->has('stats')
        ->has('stats.totalOhadaAccounts')
        ->has('stats.totalPcgAccounts')
        ->has('stats.totalIfrsAccounts')
        ->has('stats.totalSystems')
    );
});

it('returns correct stats counts', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/accounting');

    $response->assertInertia(fn ($page) => $page
        ->where('stats.totalOhadaAccounts', 0)
        ->where('stats.totalPcgAccounts', 0)
        ->where('stats.totalIfrsAccounts', 0)
        ->where('stats.totalSystems', 0)
    );
});
