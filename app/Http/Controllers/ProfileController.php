<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
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
     * Display the specified user's profile.
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
                'departments' => $user->departments->map(fn ($dept) => [
                    'id' => $dept->id,
                    'uuid' => $dept->uuid,
                    'name' => $dept->name,
                    'code' => $dept->code,
                ]),
                'groups' => $user->groups->map(fn ($group) => [
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
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
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
            $validated['avatar'] = 'avatars/' . $request->avatar;
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
