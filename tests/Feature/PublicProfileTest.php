<?php

use App\Models\Department;
use App\Models\Group;
use App\Models\Interest;
use App\Models\ProfileSkill;
use App\Models\SpokenLanguage;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('displays public profile page with all user info', function () {
    $viewer = User::factory()->create();
    $user = User::factory()->create([
        'bio' => 'This is my bio',
        'position' => 'Software Engineer',
        'address' => '123 Main St',
        'is_calendar_public' => true,
    ]);

    // Create and attach department
    $department = Department::factory()->create();
    $user->departments()->attach($department);

    // Create and attach group
    $group = Group::factory()->create();
    $user->groups()->attach($group);

    // Create and attach training
    $training = Training::factory()->create();
    $user->trainings()->attach($training->id, [
        'status' => 'approved',
        'progress' => 0,
    ]);

    // Create and attach spoken languages
    $french = SpokenLanguage::factory()->french()->create();
    $english = SpokenLanguage::factory()->english()->create();
    $user->spokenLanguages()->attach($french->id, ['level' => 'native']);
    $user->spokenLanguages()->attach($english->id, ['level' => 'advanced']);

    // Create and attach interests
    $interest1 = Interest::factory()->create(['name' => 'Reading']);
    $interest2 = Interest::factory()->create(['name' => 'Coding']);
    $user->interests()->attach([$interest1->id, $interest2->id]);

    // Create and attach skills
    $skill1 = ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);
    $skill2 = ProfileSkill::factory()->create(['name' => 'Leadership', 'category' => 'soft']);
    $user->profileSkills()->attach($skill1->id, ['level' => 'expert']);
    $user->profileSkills()->attach($skill2->id, ['level' => 'advanced']);

    $response = $this->actingAs($viewer)->get(route('profile.public', $user->uuid));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Profile/Public')
        ->has('user', fn (Assert $userProp) => $userProp
            ->where('id', $user->id)
            ->where('name', $user->name)
            ->where('email', $user->email)
            ->where('phone_number', $user->phone_number)
            ->where('bio', 'This is my bio')
            ->where('position', 'Software Engineer')
            ->where('address', '123 Main St')
            ->where('is_calendar_public', true)
            ->has('languages', 2)
            ->has('languages.0', fn (Assert $lang) => $lang
                ->where('name', 'French')
                ->where('code', 'fr')
                ->where('level', 'native')
                ->etc()
            )
            ->has('interests', 2)
            ->has('skills', 2)
            ->has('departments', 1)
            ->has('groups', 1)
            ->has('trainings', 1)
            ->has('roles')
            ->etc()
        )
    );
});

it('displays public profile page for user with no relationships', function () {
    $viewer = User::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($viewer)->get(route('profile.public', $user->uuid));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Profile/Public')
        ->has('user', fn (Assert $userProp) => $userProp
            ->has('languages', 0)
            ->has('interests', 0)
            ->has('skills', 0)
            ->has('departments', 0)
            ->has('groups', 0)
            ->has('trainings', 0)
            ->etc()
        )
    );
});

it('displays skills grouped by category', function () {
    $viewer = User::factory()->create();
    $user = User::factory()->create();

    // Create skills of different categories
    $softSkill = ProfileSkill::factory()->create(['name' => 'Communication', 'category' => 'soft']);
    $hardSkill = ProfileSkill::factory()->create(['name' => 'Project Management', 'category' => 'hard']);
    $techSkill = ProfileSkill::factory()->create(['name' => 'Laravel', 'category' => 'technical']);

    $user->profileSkills()->attach([
        $softSkill->id => ['level' => 'advanced'],
        $hardSkill->id => ['level' => 'intermediate'],
        $techSkill->id => ['level' => 'expert'],
    ]);

    $response = $this->actingAs($viewer)->get(route('profile.public', $user->uuid));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Profile/Public')
        ->has('user', fn (Assert $userProp) => $userProp
            ->has('skills', 3)
            ->has('skills.0', fn (Assert $skill) => $skill
                ->has('category')
                ->has('level')
                ->has('name')
                ->etc()
            )
            ->etc()
        )
    );
});

it('displays language levels correctly', function () {
    $viewer = User::factory()->create();
    $user = User::factory()->create();

    $german = SpokenLanguage::factory()->german()->create();
    $user->spokenLanguages()->attach($german->id, ['level' => 'beginner']);

    $response = $this->actingAs($viewer)->get(route('profile.public', $user->uuid));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Profile/Public')
        ->has('user', fn (Assert $userProp) => $userProp
            ->has('languages', 1)
            ->has('languages.0', fn (Assert $lang) => $lang
                ->where('name', 'German')
                ->where('code', 'de')
                ->where('native_name', 'Deutsch')
                ->where('level', 'beginner')
                ->etc()
            )
            ->etc()
        )
    );
});

it('requires authentication to view public profile', function () {
    $user = User::factory()->create();

    $response = $this->get(route('profile.public', $user->uuid));

    $response->assertRedirect(route('login'));
});
