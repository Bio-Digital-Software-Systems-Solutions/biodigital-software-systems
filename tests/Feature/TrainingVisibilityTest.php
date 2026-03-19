<?php

use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create roles
    $adminRole = Role::create(['name' => 'admin']);
    $memberRole = Role::create(['name' => 'member']);
    $teacherRole = Role::create(['name' => 'teacher']);
    $studentRole = Role::create(['name' => 'student']);
    Role::create(['name' => 'super-admin']);

    // Create permissions
    Permission::create(['name' => 'manage training access']);
    Permission::create(['name' => 'manage trainings']);
    Permission::create(['name' => 'create trainings']);
    Permission::create(['name' => 'edit trainings']);
    Permission::create(['name' => 'delete trainings']);
    Permission::create(['name' => 'view trainings']);
    Permission::create(['name' => 'access student dashboard']);

    // Assign permissions to roles
    $adminRole->givePermissionTo([
        'manage training access', 'manage trainings', 'create trainings',
        'edit trainings', 'delete trainings', 'view trainings',
    ]);
    $teacherRole->givePermissionTo([
        'manage training access', 'manage trainings', 'create trainings',
        'edit trainings', 'view trainings',
    ]);
    $memberRole->givePermissionTo(['view trainings']);
    $studentRole->givePermissionTo(['view trainings', 'access student dashboard']);
});

// ============================================================
// Public API Tests (/api/trainings)
// ============================================================

test('public API only returns public active trainings', function (): void {
    Training::factory()->create(['is_active' => true, 'visibility' => 'public', 'title' => 'Public Training']);
    Training::factory()->create(['is_active' => true, 'visibility' => 'private', 'title' => 'Private Training']);
    Training::factory()->create(['is_active' => false, 'visibility' => 'public', 'title' => 'Inactive Training']);

    $response = $this->get('/api/trainings');

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['title' => 'Public Training'])
        ->assertJsonMissing(['title' => 'Private Training'])
        ->assertJsonMissing(['title' => 'Inactive Training']);
});

test('private active trainings are not returned on public API', function (): void {
    Training::factory()->create(['is_active' => true, 'visibility' => 'private', 'title' => 'Secret Training']);

    $response = $this->get('/api/trainings');

    $response->assertOk()
        ->assertJsonCount(0);
});

// ============================================================
// View / Access Tests
// ============================================================

test('any authenticated user can view public active training', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');

    $training = Training::factory()->create(['is_active' => true, 'visibility' => 'public']);

    $response = $this->actingAs($user)->get("/trainings/{$training->uuid}");

    $response->assertOk();
});

test('user without access cannot view private training', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');

    $training = Training::factory()->create(['is_active' => true, 'visibility' => 'private']);

    // 403 is converted to redirect back by the exception handler (Inertia pattern)
    $response = $this->actingAs($user)->get("/trainings/{$training->uuid}");

    $response->assertRedirect();
    $response->assertSessionHas('unauthorized');
});

test('user with direct user access can view private training', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');

    $training = Training::factory()->create(['is_active' => true, 'visibility' => 'private']);
    $training->accessUsers()->attach($user->id);

    $response = $this->actingAs($user)->get("/trainings/{$training->uuid}");

    $response->assertOk();
});

test('user with role access can view private training', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');

    $training = Training::factory()->create(['is_active' => true, 'visibility' => 'private']);
    $memberRole = Role::findByName('member');
    $training->accessRoles()->attach($memberRole->id);

    $response = $this->actingAs($user)->get("/trainings/{$training->uuid}");

    $response->assertOk();
});

test('training teacher can view own private training', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $training = Training::factory()->create([
        'is_active' => true,
        'visibility' => 'private',
        'teacher_id' => $teacher->id,
    ]);

    $response = $this->actingAs($teacher)->get("/trainings/{$training->uuid}");

    $response->assertOk();
});

test('admin can view any private training', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $training = Training::factory()->create(['is_active' => true, 'visibility' => 'private']);

    $response = $this->actingAs($admin)->get("/trainings/{$training->uuid}");

    $response->assertOk();
});

// ============================================================
// Enrollment Tests
// ============================================================

test('user without access cannot enroll in private training', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');

    $training = Training::factory()->create(['is_active' => true, 'visibility' => 'private']);
    $class = TrainingClass::create([
        'training_id' => $training->id,
        'name' => 'Test Class',
        'date' => now()->addDays(7)->toDateString(),
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
    ]);

    // 403 is converted to redirect back by the exception handler (Inertia pattern)
    $response = $this->actingAs($user)->post("/trainings/{$training->uuid}/enroll", [
        'selectedClassId' => $class->id,
        'motivation' => str_repeat('Je suis motivé pour cette formation privée. ', 3),
        'paymentMethod' => 'card',
        'hasReadTerms' => true,
        'hasReadPrivacyPolicy' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('unauthorized');
});

test('user with access can enroll in private training', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');

    $training = Training::factory()->create(['is_active' => true, 'visibility' => 'private']);
    $training->accessUsers()->attach($user->id);

    $class = TrainingClass::create([
        'training_id' => $training->id,
        'name' => 'Test Class',
        'date' => now()->addDays(7)->toDateString(),
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
    ]);

    $response = $this->actingAs($user)->post("/trainings/{$training->uuid}/enroll", [
        'selectedClassId' => $class->id,
        'motivation' => str_repeat('Je suis motivé pour cette formation privée. ', 3),
        'paymentMethod' => 'card',
        'hasReadTerms' => true,
        'hasReadPrivacyPolicy' => true,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('training_enrollments', [
        'user_id' => $user->id,
        'training_id' => $training->id,
        'status' => 'pending',
    ]);
});

test('public training enrollment works as before', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');

    $training = Training::factory()->create(['is_active' => true, 'visibility' => 'public']);
    $class = TrainingClass::create([
        'training_id' => $training->id,
        'name' => 'Test Class',
        'date' => now()->addDays(7)->toDateString(),
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
    ]);

    $response = $this->actingAs($user)->post("/trainings/{$training->uuid}/enroll", [
        'selectedClassId' => $class->id,
        'motivation' => str_repeat('Je suis motivé pour cette formation publique. ', 3),
        'paymentMethod' => 'card',
        'hasReadTerms' => true,
        'hasReadPrivacyPolicy' => true,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('training_enrollments', [
        'user_id' => $user->id,
        'training_id' => $training->id,
    ]);
});

// ============================================================
// Access Management Tests
// ============================================================

test('admin can grant user access to private training', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $userToGrant = User::factory()->create();
    $training = Training::factory()->create(['visibility' => 'private']);

    $response = $this->actingAs($admin)
        ->post(route('trainings.access.grant-users', $training), [
            'user_ids' => [$userToGrant->id],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('training_user_access', [
        'training_id' => $training->id,
        'user_id' => $userToGrant->id,
        'granted_by' => $admin->id,
    ]);
});

test('admin can revoke user access', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();
    $training = Training::factory()->create(['visibility' => 'private']);
    $training->accessUsers()->attach($user->id, ['granted_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->delete(route('trainings.access.revoke-users', $training), [
            'user_ids' => [$user->id],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseMissing('training_user_access', [
        'training_id' => $training->id,
        'user_id' => $user->id,
    ]);
});

test('admin can grant role access to private training', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $training = Training::factory()->create(['visibility' => 'private']);
    $memberRole = Role::findByName('member');

    $response = $this->actingAs($admin)
        ->post(route('trainings.access.grant-roles', $training), [
            'role_ids' => [$memberRole->id],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('training_role_access', [
        'training_id' => $training->id,
        'role_id' => $memberRole->id,
        'granted_by' => $admin->id,
    ]);
});

test('admin can revoke role access', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $training = Training::factory()->create(['visibility' => 'private']);
    $memberRole = Role::findByName('member');
    $training->accessRoles()->attach($memberRole->id, ['granted_by' => $admin->id]);

    $response = $this->actingAs($admin)
        ->delete(route('trainings.access.revoke-roles', $training), [
            'role_ids' => [$memberRole->id],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseMissing('training_role_access', [
        'training_id' => $training->id,
        'role_id' => $memberRole->id,
    ]);
});

test('teacher can manage access on own training', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $training = Training::factory()->create([
        'visibility' => 'private',
        'teacher_id' => $teacher->id,
    ]);

    $userToGrant = User::factory()->create();

    $response = $this->actingAs($teacher)
        ->post(route('trainings.access.grant-users', $training), [
            'user_ids' => [$userToGrant->id],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('training_user_access', [
        'training_id' => $training->id,
        'user_id' => $userToGrant->id,
    ]);
});

test('teacher cannot manage access on other teacher training', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $otherTeacher = User::factory()->create();

    $training = Training::factory()->create([
        'visibility' => 'private',
        'teacher_id' => $otherTeacher->id,
    ]);

    // 403 is converted to redirect back by the exception handler (Inertia pattern)
    $response = $this->actingAs($teacher)
        ->post(route('trainings.access.grant-users', $training), [
            'user_ids' => [User::factory()->create()->id],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('unauthorized');
});

test('member cannot access the access management page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');
    $user->assignRole('student');

    $training = Training::factory()->create(['visibility' => 'private']);

    // 403 is converted to redirect back by the exception handler (Inertia pattern)
    $response = $this->actingAs($user)
        ->get(route('trainings.access', $training));

    $response->assertRedirect();
    $response->assertSessionHas('unauthorized');
});

// ============================================================
// CRUD Tests
// ============================================================

test('training can be created with visibility field', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('trainings.store'), [
        'title' => 'Private Formation',
        'description' => 'A private training course',
        'duration' => '3 mois',
        'level' => 'beginner',
        'price' => 100,
        'category' => 'Test',
        'is_active' => true,
        'visibility' => 'private',
    ]);

    $response->assertRedirect(route('trainings.index'));

    $this->assertDatabaseHas('trainings', [
        'title' => 'Private Formation',
        'visibility' => 'private',
    ]);
});

test('training can be updated to change visibility', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $training = Training::factory()->create(['visibility' => 'public']);

    $response = $this->actingAs($admin)->put(route('trainings.update', $training->uuid), [
        'title' => $training->title,
        'description' => $training->description,
        'duration' => $training->duration,
        'level' => $training->level,
        'price' => $training->price,
        'category' => $training->category,
        'is_active' => $training->is_active,
        'visibility' => 'private',
    ]);

    $response->assertRedirect();

    $training->refresh();
    expect($training->visibility)->toBe('private');
});

test('visibility defaults to public when not provided', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('trainings.store'), [
        'title' => 'Default Visibility Training',
        'description' => 'Testing default visibility',
        'duration' => '2 mois',
        'level' => 'intermediate',
        'price' => 50,
        'category' => 'Test',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('trainings.index'));

    $this->assertDatabaseHas('trainings', [
        'title' => 'Default Visibility Training',
        'visibility' => 'public',
    ]);
});

// ============================================================
// Model Helper Tests
// ============================================================

test('isAccessibleBy returns true for public trainings', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');

    $training = Training::factory()->create(['visibility' => 'public']);

    expect($training->isAccessibleBy($user))->toBeTrue();
});

test('isAccessibleBy returns false for private training without access', function (): void {
    $user = User::factory()->create();
    $user->assignRole('member');

    $training = Training::factory()->create(['visibility' => 'private']);

    expect($training->isAccessibleBy($user))->toBeFalse();
});

test('isAccessibleBy returns true for admin on private training', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $training = Training::factory()->create(['visibility' => 'private']);

    expect($training->isAccessibleBy($admin))->toBeTrue();
});

test('isAccessibleBy returns true for teacher who owns the training', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $training = Training::factory()->create([
        'visibility' => 'private',
        'teacher_id' => $teacher->id,
    ]);

    expect($training->isAccessibleBy($teacher))->toBeTrue();
});

test('admin can filter trainings by visibility in admin index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Training::factory()->create(['visibility' => 'public', 'title' => 'Public One']);
    Training::factory()->create(['visibility' => 'private', 'title' => 'Private One']);

    $response = $this->actingAs($admin)->get('/trainings?visibility=private');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Training/Index')
            ->has('trainings.data', 1)
            ->where('trainings.data.0.title', 'Private One')
        );
});
