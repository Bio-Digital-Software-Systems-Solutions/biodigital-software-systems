<?php

use App\Models\Interest;
use App\Models\ProfileSkill;
use App\Models\SpokenLanguage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ============================================
// Public Profile Privacy Tests
// ============================================

it('shows all data when all privacy settings are public', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'phone_number' => '1234567890',
        'bio' => 'My bio',
        'position' => 'Developer',
        'address' => '123 Main St',
        'birth_date' => '1990-01-01',
        'privacy_settings' => null, // All public by default
    ]);

    $language = SpokenLanguage::factory()->french()->create();
    $user->spokenLanguages()->attach($language->id, ['level' => 'native']);

    $interest = Interest::factory()->create(['name' => 'Reading']);
    $user->interests()->attach($interest->id);

    $skill = ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);
    $user->profileSkills()->attach($skill->id, ['level' => 'expert']);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->has('user.email')
        ->has('user.phone_number')
        ->has('user.bio')
        ->has('user.position')
        ->has('user.address')
        ->has('user.birth_date')
        ->has('user.languages', 1)
        ->has('user.interests', 1)
        ->has('user.skills', 1)
    );
});

it('hides email when privacy is set to private', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'privacy_settings' => ['email' => false],
    ]);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.email')
    );
});

it('hides phone number when privacy is set to private', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'phone_number' => '1234567890',
        'privacy_settings' => ['phone_number' => false],
    ]);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.phone_number')
    );
});

it('hides bio when privacy is set to private', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'bio' => 'My bio',
        'privacy_settings' => ['bio' => false],
    ]);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.bio')
    );
});

it('hides position when privacy is set to private', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'position' => 'Developer',
        'privacy_settings' => ['position' => false],
    ]);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.position')
    );
});

it('hides address when privacy is set to private', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'address' => '123 Main St',
        'privacy_settings' => ['address' => false],
    ]);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.address')
    );
});

it('hides birth date when privacy is set to private', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'birth_date' => '1990-01-01',
        'privacy_settings' => ['birth_date' => false],
    ]);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.birth_date')
    );
});

it('hides languages when privacy is set to private', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'privacy_settings' => ['languages' => false],
    ]);

    $language = SpokenLanguage::factory()->french()->create();
    $user->spokenLanguages()->attach($language->id, ['level' => 'native']);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.languages')
    );
});

it('hides interests when privacy is set to private', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'privacy_settings' => ['interests' => false],
    ]);

    $interest = Interest::factory()->create(['name' => 'Reading']);
    $user->interests()->attach($interest->id);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.interests')
    );
});

it('hides skills when privacy is set to private', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'privacy_settings' => ['skills' => false],
    ]);

    $skill = ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);
    $user->profileSkills()->attach($skill->id, ['level' => 'expert']);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.skills')
    );
});

it('always shows name and avatar regardless of privacy settings', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'avatar' => 'avatars/test.jpg',
        'privacy_settings' => [
            'email' => false,
            'phone_number' => false,
            'bio' => false,
        ],
    ]);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->has('user.first_name')
        ->has('user.last_name')
        ->has('user.avatar')
        ->has('user.name')
        ->where('user.first_name', 'John')
        ->where('user.last_name', 'Doe')
    );
});

it('applies mixed privacy settings correctly', function (): void {
    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'phone_number' => '1234567890',
        'bio' => 'My bio',
        'position' => 'Developer',
        'privacy_settings' => [
            'email' => false,
            'phone_number' => true,
            'bio' => true,
            'position' => false,
        ],
    ]);

    $response = $this->get(route('profile.public', $user));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Public')
        ->missing('user.email')
        ->has('user.phone_number')
        ->has('user.bio')
        ->missing('user.position')
    );
});

it('requires authentication to view public profile', function (): void {
    $user = User::factory()->create();

    $response = $this->get(route('profile.public', $user));

    $response->assertRedirect(route('login'));
});

// ============================================
// Profile Edit Page Tests
// ============================================

it('loads privacy settings on profile edit page', function (): void {
    $user = User::factory()->create([
        'privacy_settings' => ['email' => false, 'bio' => true],
    ]);
    $this->actingAs($user);

    $response = $this->get(route('profile.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Edit')
        ->has('privacySettings')
        ->has('defaultPrivacySettings')
        ->where('privacySettings.email', false)
        ->where('privacySettings.bio', true)
    );
});

it('loads default privacy settings for user without custom settings', function (): void {
    $user = User::factory()->create([
        'privacy_settings' => null,
    ]);
    $this->actingAs($user);

    $response = $this->get(route('profile.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Edit')
        ->has('privacySettings')
        ->where('privacySettings.email', true)
        ->where('privacySettings.phone_number', true)
        ->where('privacySettings.bio', true)
    );
});
