<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    /**
     * Display the two factor challenge view.
     */
    public function create(Request $request): Response
    {
        $userId = $request->session()->get('login.id');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return Inertia::render('Auth/TwoFactorChallenge', [
                'totpEnabled' => false,
                'emailEnabled' => false,
                'preferredMethod' => 'totp',
            ]);
        }

        return Inertia::render('Auth/TwoFactorChallenge', [
            'totpEnabled' => (bool) $user->two_factor_confirmed_at,
            'emailEnabled' => (bool) $user->email_two_factor_enabled,
            'preferredMethod' => $user->preferred_two_factor_method ?? 'totp',
        ]);
    }
}
