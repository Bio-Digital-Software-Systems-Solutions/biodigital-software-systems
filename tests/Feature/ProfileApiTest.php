<?php

use App\Models\Interest;
use App\Models\ProfileSkill;
use App\Models\SpokenLanguage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ============================================
// Languages API Tests
// ============================================

it('can get available languages', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    SpokenLanguage::factory()->french()->create();
    SpokenLanguage::factory()->english()->create();

    $response = $this->getJson('/api/profile/languages');

    $response->assertOk()
        ->assertJsonCount(2, 'languages')
        ->assertJsonStructure([
            'languages' => [
                '*' => ['id', 'uuid', 'name', 'code', 'native_name'],
            ],
        ]);
});

it('can update user languages', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $french = SpokenLanguage::factory()->french()->create();
    $english = SpokenLanguage::factory()->english()->create();

    $response = $this->putJson('/api/profile/languages', [
        'languages' => [
            ['id' => $french->id, 'level' => 'native'],
            ['id' => $english->id, 'level' => 'advanced'],
        ],
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'languages');

    expect($user->spokenLanguages()->count())->toBe(2);
    expect($user->spokenLanguages()->find($french->id)->pivot->level)->toBe('native');
    expect($user->spokenLanguages()->find($english->id)->pivot->level)->toBe('advanced');
});

it('validates language level', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $french = SpokenLanguage::factory()->french()->create();

    $response = $this->putJson('/api/profile/languages', [
        'languages' => [
            ['id' => $french->id, 'level' => 'invalid_level'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['languages.0.level']);
});

it('validates language exists', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/languages', [
        'languages' => [
            ['id' => 999, 'level' => 'native'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['languages.0.id']);
});

it('can clear all user languages', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $french = SpokenLanguage::factory()->french()->create();
    $user->spokenLanguages()->attach($french->id, ['level' => 'native']);

    $response = $this->putJson('/api/profile/languages', [
        'languages' => [],
    ]);

    $response->assertOk();
    expect($user->spokenLanguages()->count())->toBe(0);
});

// ============================================
// Interests API Tests
// ============================================

it('can get available interests', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Interest::factory()->create(['name' => 'Reading']);
    Interest::factory()->create(['name' => 'Music']);

    $response = $this->getJson('/api/profile/interests');

    $response->assertOk()
        ->assertJsonCount(2, 'interests')
        ->assertJsonStructure([
            'interests' => [
                '*' => ['id', 'uuid', 'name', 'icon'],
            ],
        ]);
});

it('can update user interests', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $reading = Interest::factory()->create(['name' => 'Reading']);
    $music = Interest::factory()->create(['name' => 'Music']);

    $response = $this->putJson('/api/profile/interests', [
        'interests' => [$reading->id, $music->id],
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'interests');

    expect($user->interests()->count())->toBe(2);
});

it('can create a new interest', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/profile/interests', [
        'name' => 'Photography',
    ]);

    $response->assertCreated()
        ->assertJsonPath('interest.name', 'Photography');

    expect(Interest::where('name', 'Photography')->exists())->toBeTrue();
});

it('validates interest name is unique', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Interest::factory()->create(['name' => 'Photography']);

    $response = $this->postJson('/api/profile/interests', [
        'name' => 'Photography',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('validates interest exists when updating', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/interests', [
        'interests' => [999],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['interests.0']);
});

// ============================================
// Skills API Tests
// ============================================

it('can get available skills', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);
    ProfileSkill::factory()->create(['name' => 'Leadership', 'category' => 'soft']);

    $response = $this->getJson('/api/profile/skills');

    $response->assertOk()
        ->assertJsonCount(2, 'skills')
        ->assertJsonStructure([
            'skills' => [
                '*' => ['id', 'uuid', 'name', 'category'],
            ],
        ]);
});

it('can update user skills with levels', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $php = ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);
    $leadership = ProfileSkill::factory()->create(['name' => 'Leadership', 'category' => 'soft']);

    $response = $this->putJson('/api/profile/skills', [
        'skills' => [
            ['id' => $php->id, 'level' => 'expert'],
            ['id' => $leadership->id, 'level' => 'advanced'],
        ],
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'skills');

    expect($user->profileSkills()->count())->toBe(2);
    expect($user->profileSkills()->find($php->id)->pivot->level)->toBe('expert');
    expect($user->profileSkills()->find($leadership->id)->pivot->level)->toBe('advanced');
});

it('can create a new skill', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/profile/skills', [
        'name' => 'Laravel',
        'category' => 'technical',
    ]);

    $response->assertCreated()
        ->assertJsonPath('skill.name', 'Laravel')
        ->assertJsonPath('skill.category', 'technical');

    expect(ProfileSkill::where('name', 'Laravel')->where('category', 'technical')->exists())->toBeTrue();
});

it('validates skill category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/profile/skills', [
        'name' => 'Test Skill',
        'category' => 'invalid_category',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['category']);
});

it('prevents duplicate skill in same category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);

    $response = $this->postJson('/api/profile/skills', [
        'name' => 'PHP',
        'category' => 'technical',
    ]);

    $response->assertUnprocessable();
});

it('allows same skill name in different category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    ProfileSkill::factory()->create(['name' => 'Communication', 'category' => 'soft']);

    $response = $this->postJson('/api/profile/skills', [
        'name' => 'Communication',
        'category' => 'hard',
    ]);

    $response->assertCreated();
});

it('validates skill level', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $skill = ProfileSkill::factory()->create();

    $response = $this->putJson('/api/profile/skills', [
        'skills' => [
            ['id' => $skill->id, 'level' => 'invalid_level'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['skills.0.level']);
});

// ============================================
// Authentication Tests
// ============================================

it('requires authentication to access profile languages api', function () {
    $response = $this->getJson('/api/profile/languages');
    $response->assertUnauthorized();
});

it('requires authentication to update profile languages', function () {
    $response = $this->putJson('/api/profile/languages', ['languages' => []]);
    $response->assertUnauthorized();
});

it('requires authentication to access profile interests api', function () {
    $response = $this->getJson('/api/profile/interests');
    $response->assertUnauthorized();
});

it('requires authentication to update profile interests', function () {
    $response = $this->putJson('/api/profile/interests', ['interests' => []]);
    $response->assertUnauthorized();
});

it('requires authentication to access profile skills api', function () {
    $response = $this->getJson('/api/profile/skills');
    $response->assertUnauthorized();
});

it('requires authentication to update profile skills', function () {
    $response = $this->putJson('/api/profile/skills', ['skills' => []]);
    $response->assertUnauthorized();
});

// ============================================
// Privacy Settings API Tests
// ============================================

it('can get user privacy settings', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/profile/privacy');

    $response->assertOk()
        ->assertJsonStructure([
            'privacy_settings' => [
                'email',
                'phone_number',
                'birth_date',
                'address',
                'bio',
                'position',
                'languages',
                'interests',
                'skills',
            ],
            'default_settings',
        ]);
});

it('returns default privacy settings for new user', function () {
    $user = User::factory()->create(['privacy_settings' => null]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/profile/privacy');

    $response->assertOk();

    // All fields should be public by default
    $settings = $response->json('privacy_settings');
    foreach ($settings as $value) {
        expect($value)->toBeTrue();
    }
});

it('can update user privacy settings', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/privacy', [
        'privacy_settings' => [
            'email' => false,
            'phone_number' => false,
            'birth_date' => true,
            'address' => false,
            'bio' => true,
            'position' => true,
            'languages' => true,
            'interests' => false,
            'skills' => true,
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('privacy_settings.email', false)
        ->assertJsonPath('privacy_settings.phone_number', false)
        ->assertJsonPath('privacy_settings.bio', true);

    $user->refresh();
    expect($user->privacy_settings['email'])->toBeFalse();
    expect($user->privacy_settings['phone_number'])->toBeFalse();
});

it('can update partial privacy settings', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Update only email privacy
    $response = $this->putJson('/api/profile/privacy', [
        'privacy_settings' => [
            'email' => false,
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('privacy_settings.email', false)
        ->assertJsonPath('privacy_settings.phone_number', true); // Default
});

it('validates privacy settings must be boolean', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/privacy', [
        'privacy_settings' => [
            'email' => 'not_a_boolean',
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['privacy_settings.email']);
});

it('validates privacy settings must be an array', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/privacy', [
        'privacy_settings' => 'not_an_array',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['privacy_settings']);
});

it('ignores invalid privacy setting fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile/privacy', [
        'privacy_settings' => [
            'email' => false,
            'invalid_field' => false,
        ],
    ]);

    $response->assertOk();

    $user->refresh();
    expect($user->privacy_settings)->not->toHaveKey('invalid_field');
});

it('requires authentication to access privacy settings', function () {
    $response = $this->getJson('/api/profile/privacy');
    $response->assertUnauthorized();
});

it('requires authentication to update privacy settings', function () {
    $response = $this->putJson('/api/profile/privacy', [
        'privacy_settings' => ['email' => false],
    ]);
    $response->assertUnauthorized();
});

// ============================================
// Privacy Settings - User Model Tests
// ============================================

it('user has default privacy settings constant', function () {
    expect(User::DEFAULT_PRIVACY_SETTINGS)->toBeArray();
    expect(User::DEFAULT_PRIVACY_SETTINGS)->toHaveKeys([
        'email',
        'phone_number',
        'birth_date',
        'address',
        'bio',
        'position',
        'languages',
        'interests',
        'skills',
    ]);
});

it('user can get merged privacy settings', function () {
    $user = User::factory()->create([
        'privacy_settings' => ['email' => false],
    ]);

    $settings = $user->getPrivacySettings();

    expect($settings['email'])->toBeFalse();
    expect($settings['phone_number'])->toBeTrue(); // Default
});

it('user can check if field is public', function () {
    $user = User::factory()->create([
        'privacy_settings' => ['email' => false, 'bio' => true],
    ]);

    expect($user->isFieldPublic('email'))->toBeFalse();
    expect($user->isFieldPublic('bio'))->toBeTrue();
    expect($user->isFieldPublic('address'))->toBeTrue(); // Default
});

it('user can get public profile data respecting privacy', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'phone_number' => '1234567890',
        'bio' => 'My bio',
        'privacy_settings' => [
            'email' => false,
            'phone_number' => true,
            'bio' => true,
        ],
    ]);

    $publicData = $user->getPublicProfileData();

    expect($publicData)->not->toHaveKey('email');
    expect($publicData)->toHaveKey('phone_number');
    expect($publicData['phone_number'])->toBe('1234567890');
    expect($publicData)->toHaveKey('bio');
    expect($publicData['bio'])->toBe('My bio');
});
