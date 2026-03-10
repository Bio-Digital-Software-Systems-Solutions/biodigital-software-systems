<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Interest;
use App\Models\ProfileSkill;
use App\Models\SpokenLanguage;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the public profile view (accessible to everyone).
     * Respects user privacy settings.
     */
    public function publicShow(User $user): Response
    {
        $user->load([
            'departments',
            'groups',
            'trainings',
            'roles',
            'profileSkills',
            'interests',
            'spokenLanguages',
        ]);

        $privacySettings = $user->getPrivacySettings();

        // Build user data respecting privacy settings
        $userData = [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar' => $user->avatar,
            'is_calendar_public' => $user->is_calendar_public,
            'created_at' => $user->created_at,
            // Always show departments, groups, trainings, roles (public organizational data)
            'departments' => $user->departments->map(fn ($dept): array => [
                'id' => $dept->id,
                'uuid' => $dept->uuid,
                'name' => $dept->name,
            ]),
            'groups' => $user->groups->map(fn ($group): array => [
                'id' => $group->id,
                'uuid' => $group->uuid,
                'name' => $group->name,
            ]),
            'trainings' => $user->trainings->map(fn ($training): array => [
                'id' => $training->id,
                'uuid' => $training->uuid,
                'title' => $training->title,
            ]),
            'roles' => $user->roles->pluck('name'),
        ];

        // Add conditional fields based on privacy settings
        if ($privacySettings['email']) {
            $userData['email'] = $user->email;
        }
        if ($privacySettings['phone_number']) {
            $userData['phone_number'] = $user->phone_number;
        }
        if ($privacySettings['birth_date']) {
            $userData['birth_date'] = $user->birth_date;
        }
        if ($privacySettings['address']) {
            $userData['address'] = $user->address;
        }
        if ($privacySettings['bio']) {
            $userData['bio'] = $user->bio;
        }
        if ($privacySettings['position']) {
            $userData['position'] = $user->position;
        }
        // Only include languages, interests, skills if privacy settings allow
        // (not including the key at all when private, so frontend can check with 'key' in user)
        if ($privacySettings['languages']) {
            $userData['languages'] = $user->spokenLanguages->map(fn ($lang): array => [
                'id' => $lang->id,
                'uuid' => $lang->uuid,
                'name' => $lang->name,
                'code' => $lang->code,
                'native_name' => $lang->native_name,
                'level' => $lang->pivot->level,
            ]);
        }
        if ($privacySettings['interests']) {
            $userData['interests'] = $user->interests->map(fn ($interest): array => [
                'id' => $interest->id,
                'uuid' => $interest->uuid,
                'name' => $interest->name,
                'icon' => $interest->icon,
            ]);
        }
        if ($privacySettings['skills']) {
            $userData['skills'] = $user->profileSkills->map(fn ($skill): array => [
                'id' => $skill->id,
                'uuid' => $skill->uuid,
                'name' => $skill->name,
                'category' => $skill->category,
                'level' => $skill->pivot->level,
            ]);
        }

        return Inertia::render('Profile/Public', [
            'user' => $userData,
        ]);
    }

    /**
     * Display the specified user's profile (authenticated users only).
     */
    public function show(User $user): Response
    {
        $user->load(['departments', 'groups', 'roles']);

        return Inertia::render('Profile/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar' => $user->avatar,
                'birth_date' => $user->birth_date,
                'status' => $user->status ?? 'active',
                'created_at' => $user->created_at,
                'last_login_at' => $user->last_login_at,
                'last_login_ip' => $user->last_login_ip,
                'last_login_user_agent' => $user->last_login_user_agent,
                'departments' => $user->departments->map(fn ($dept): array => [
                    'id' => $dept->id,
                    'uuid' => $dept->uuid,
                    'name' => $dept->name,
                    'code' => $dept->code,
                ]),
                'groups' => $user->groups->map(fn ($group): array => [
                    'id' => $group->id,
                    'uuid' => $group->uuid,
                    'name' => $group->name,
                    'code' => $group->code,
                ]),
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load(['spokenLanguages', 'interests', 'profileSkills']);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'availableLanguages' => SpokenLanguage::orderBy('name')->get()->map(fn ($lang): array => [
                'id' => $lang->id,
                'uuid' => $lang->uuid,
                'name' => $lang->name,
                'code' => $lang->code,
                'native_name' => $lang->native_name,
            ]),
            'availableInterests' => Interest::orderBy('name')->get()->map(fn ($interest): array => [
                'id' => $interest->id,
                'uuid' => $interest->uuid,
                'name' => $interest->name,
                'icon' => $interest->icon,
            ]),
            'availableSkills' => ProfileSkill::orderBy('category')->orderBy('name')->get()->map(fn ($skill): array => [
                'id' => $skill->id,
                'uuid' => $skill->uuid,
                'name' => $skill->name,
                'category' => $skill->category,
            ]),
            'userLanguages' => $user->spokenLanguages->map(fn ($lang): array => [
                'id' => $lang->id,
                'level' => $lang->pivot->level,
            ]),
            'userInterests' => $user->interests->pluck('id'),
            'userSkills' => $user->profileSkills->map(fn ($skill): array => [
                'id' => $skill->id,
                'level' => $skill->pivot->level,
            ]),
            'privacySettings' => $user->getPrivacySettings(),
            'defaultPrivacySettings' => User::DEFAULT_PRIVACY_SETTINGS,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if it exists
            if ($request->user()->avatar) {
                \Storage::disk('public')->delete($request->user()->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        // Handle avatar from TUS upload (just filename)
        elseif ($request->filled('avatar') && is_string($request->avatar)) {
            // Delete old avatar if it exists
            if ($request->user()->avatar) {
                \Storage::disk('public')->delete($request->user()->avatar);
            }
            // Avatar has already been uploaded via TUS to avatars directory
            $validated['avatar'] = 'avatars/'.$request->avatar;
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
