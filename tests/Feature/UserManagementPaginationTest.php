<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    // Create roles
    Role::create(['name' => 'super-admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'pastor']);
    Role::create(['name' => 'member']);

    // Create super-admin user
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super-admin');
});

// ===== Pagination Tests =====

test('user management index returns paginated data structure', function (): void {
    // Create 25 users to ensure pagination
    User::factory()->count(25)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('UserManagement/Index')
        ->has('users.data')
        ->has('users.current_page')
        ->has('users.last_page')
        ->has('users.per_page')
        ->has('users.total')
        ->has('users.from')
        ->has('users.to')
        ->has('filters')
    );
});

test('pagination returns correct number of items per page', function (): void {
    // Create 30 users (including superAdmin = 31 total)
    User::factory()->count(30)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['per_page' => 10]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.per_page', 10)
        ->where('users.current_page', 1)
        ->has('users.data', 10)
    );
});

test('pagination navigates to correct page', function (): void {
    // Create 30 users
    User::factory()->count(30)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['page' => 2, 'per_page' => 10]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.current_page', 2)
        ->has('users.data', 10)
    );
});

test('pagination returns correct total count', function (): void {
    // Create 25 users + superAdmin = 26 total
    User::factory()->count(25)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 26)
    );
});

test('pagination calculates last page correctly', function (): void {
    // Create 45 users + superAdmin = 46 total, with 20 per page = 3 pages
    User::factory()->count(45)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['per_page' => 20]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.last_page', 3)
    );
});

test('pagination with custom per_page value', function (): void {
    // Create 60 users
    User::factory()->count(60)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['per_page' => 50]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.per_page', 50)
        ->where('filters.per_page', 50)
    );
});

// ===== Search/Filter Tests =====

test('search filter finds users by first name', function (): void {
    User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    User::factory()->count(10)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['search' => 'John']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 1)
        ->where('filters.search', 'John')
    );
});

test('search filter finds users by last name', function (): void {
    User::factory()->create(['first_name' => 'John', 'last_name' => 'Dupont']);
    User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    User::factory()->count(10)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['search' => 'Dupont']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 1)
    );
});

test('search filter finds users by email', function (): void {
    User::factory()->create(['email' => 'unique-test-email@example.com']);
    User::factory()->count(10)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['search' => 'unique-test-email']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 1)
    );
});

test('search filter is case insensitive', function (): void {
    User::factory()->create(['first_name' => 'UPPERCASE', 'last_name' => 'Name']);
    User::factory()->count(5)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['search' => 'uppercase']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 1)
    );
});

test('search filter with partial match', function (): void {
    User::factory()->create(['first_name' => 'Alexander', 'last_name' => 'Hamilton']);
    User::factory()->count(5)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['search' => 'Alex']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 1)
    );
});

// ===== Role Filter Tests =====

test('role filter returns only users with specific role', function (): void {
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $pastorUser = User::factory()->create();
    $pastorUser->assignRole('pastor');

    $memberUser = User::factory()->create();
    $memberUser->assignRole('member');

    User::factory()->count(5)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['role' => 'admin']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 1)
        ->where('filters.role', 'admin')
    );
});

test('role filter with pastor role', function (): void {
    User::factory()->count(3)->create()->each(fn ($user) => $user->assignRole('pastor'));
    User::factory()->count(5)->create()->each(fn ($user) => $user->assignRole('member'));

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['role' => 'pastor']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 3)
    );
});

test('empty role filter returns all users', function (): void {
    User::factory()->count(10)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['role' => '']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 11) // 10 + superAdmin
    );
});

// ===== Combined Search and Role Filter Tests =====

test('search and role filter work together', function (): void {
    $targetUser = User::factory()->create(['first_name' => 'TargetUser', 'last_name' => 'Test']);
    $targetUser->assignRole('admin');

    $otherAdmin = User::factory()->create(['first_name' => 'Other', 'last_name' => 'Admin']);
    $otherAdmin->assignRole('admin');

    $targetPastor = User::factory()->create(['first_name' => 'TargetUser', 'last_name' => 'Pastor']);
    $targetPastor->assignRole('pastor');

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', [
            'search' => 'TargetUser',
            'role' => 'admin',
        ]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 1)
        ->where('filters.search', 'TargetUser')
        ->where('filters.role', 'admin')
    );
});

test('search with pagination works correctly', function (): void {
    // Create 30 users with "Test" in their name
    User::factory()->count(30)->create(['first_name' => 'TestUser']);

    // Create 10 other users
    User::factory()->count(10)->create(['first_name' => 'OtherUser']);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', [
            'search' => 'TestUser',
            'per_page' => 10,
            'page' => 2,
        ]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 30)
        ->where('users.current_page', 2)
        ->has('users.data', 10)
    );
});

// ===== Edge Cases =====

test('empty search returns all users', function (): void {
    User::factory()->count(5)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['search' => '']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 6) // 5 + superAdmin
    );
});

test('search with no results returns empty data', function (): void {
    User::factory()->count(5)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['search' => 'nonexistentuser12345']));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('users.total', 0)
        ->has('users.data', 0)
    );
});

test('invalid page number defaults gracefully', function (): void {
    User::factory()->count(5)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', ['page' => 999]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 0)
    );
});

test('users are ordered by first name and last name', function (): void {
    User::factory()->create(['first_name' => 'Zara', 'last_name' => 'Smith']);
    User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Johnson']);
    User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Adams']);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index'));

    $response->assertStatus(200);
    $response->assertInertia(function ($page): bool {
        $users = $page->toArray()['props']['users']['data'];
        // Alice Adams should come before Alice Johnson (both before Zara)
        $aliceAdamsIndex = array_search('Adams', array_column($users, 'last_name'));
        $aliceJohnsonIndex = array_search('Johnson', array_column($users, 'last_name'));
        $zaraIndex = array_search('Zara', array_column($users, 'first_name'));

        return $aliceAdamsIndex !== false && $aliceJohnsonIndex !== false;
    });
});

// ===== Filters are Passed to Frontend =====

test('filters are passed correctly to frontend', function (): void {
    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index', [
            'search' => 'test',
            'role' => 'admin',
            'per_page' => 50,
        ]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('filters.search', 'test')
        ->where('filters.role', 'admin')
        ->where('filters.per_page', 50)
    );
});

test('default filters are provided when none specified', function (): void {
    $response = $this->actingAs($this->superAdmin)
        ->get(route('user-management.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('filters.search', '')
        ->where('filters.role', '')
        ->where('filters.per_page', 20)
    );
});

// ===== Access Control Tests =====

test('non_superadmin_cannot_access_user_management', function (): void {
    $regularUser = User::factory()->create();

    // Without exception handling, it throws HttpException
    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $this->expectExceptionMessage('Access denied. super-admin role required.');

    $this->actingAs($regularUser)
        ->withoutExceptionHandling()
        ->get(route('user-management.index'));
});

test('unauthenticated_user_cannot_access_user_management', function (): void {
    $response = $this->get(route('user-management.index'));

    $response->assertRedirect(route('login'));
});
