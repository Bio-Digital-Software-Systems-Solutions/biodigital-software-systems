<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailTwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EmailTwoFactorController extends Controller
{
    public function __construct(
        protected EmailTwoFactorService $emailTwoFactorService
    ) {}

    /**
     * Enable email 2FA for the authenticated user.
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($this->emailTwoFactorService->enable($user)) {
            return response()->json([
                'message' => 'Authentification par email activée avec succès.',
                'email_two_factor_enabled' => true,
            ]);
        }

        return response()->json([
            'message' => 'Erreur lors de l\'activation de l\'authentification par email.',
        ], 500);
    }

    /**
     * Disable email 2FA for the authenticated user.
     */
    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($this->emailTwoFactorService->disable($user)) {
            return response()->json([
                'message' => 'Authentification par email désactivée avec succès.',
                'email_two_factor_enabled' => false,
            ]);
        }

        return response()->json([
            'message' => 'Erreur lors de la désactivation de l\'authentification par email.',
        ], 500);
    }

    /**
     * Send a 2FA code during the login challenge.
     * This is called when the user is in the 2FA challenge phase.
     */
    public function sendCode(Request $request): JsonResponse
    {
        $userId = $request->session()->get('login.id');

        if (! $userId) {
            return response()->json([
                'message' => 'Session invalide. Veuillez vous reconnecter.',
            ], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Check if user has email 2FA enabled
        if (! $user->email_two_factor_enabled) {
            return response()->json([
                'message' => 'L\'authentification par email n\'est pas activée pour ce compte.',
            ], 400);
        }

        // Check if there's already a pending code that hasn't expired
        if ($this->emailTwoFactorService->hasPendingCode($user)) {
            $remainingTime = $this->emailTwoFactorService->getRemainingTime($user);

            return response()->json([
                'message' => 'Un code a déjà été envoyé.',
                'remaining_seconds' => $remainingTime,
                'can_resend' => false,
            ]);
        }

        if ($this->emailTwoFactorService->sendCode($user)) {
            return response()->json([
                'message' => 'Code de vérification envoyé à votre adresse email.',
                'expires_in_minutes' => EmailTwoFactorService::CODE_EXPIRATION_MINUTES,
                'can_resend' => false,
            ]);
        }

        return response()->json([
            'message' => 'Erreur lors de l\'envoi du code. Veuillez réessayer.',
        ], 500);
    }

    /**
     * Verify the email 2FA code and complete login.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'email_code' => ['required', 'string', 'size:8'],
        ], [
            'email_code.required' => 'Le code de vérification est requis.',
            'email_code.size' => 'Le code de vérification doit contenir 8 chiffres.',
        ]);

        $userId = $request->session()->get('login.id');

        if (! $userId) {
            throw ValidationException::withMessages([
                'email_code' => ['Session invalide. Veuillez vous reconnecter.'],
            ]);
        }

        $user = User::find($userId);

        if (! $user) {
            throw ValidationException::withMessages([
                'email_code' => ['Utilisateur non trouvé.'],
            ]);
        }

        if (! $this->emailTwoFactorService->verifyCode($user, $request->input('email_code'))) {
            throw ValidationException::withMessages([
                'email_code' => ['Code invalide ou expiré.'],
            ]);
        }

        // Complete the login
        $remember = $request->session()->get('login.remember', false);
        Auth::login($user, $remember);

        // Clear the login session data
        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        // Redirect to intended page
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Set the preferred 2FA method for the authenticated user.
     */
    public function setPreferredMethod(Request $request): JsonResponse
    {
        $request->validate([
            'method' => ['required', 'string', 'in:totp,email'],
        ]);

        $user = $request->user();
        $method = $request->input('method');

        if ($this->emailTwoFactorService->setPreferredMethod($user, $method)) {
            return response()->json([
                'message' => 'Méthode préférée mise à jour.',
                'preferred_method' => $method,
            ]);
        }

        return response()->json([
            'message' => 'Impossible de définir cette méthode. Assurez-vous qu\'elle est activée.',
        ], 400);
    }

    /**
     * Get the 2FA status for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'totp_enabled' => (bool) $user->two_factor_confirmed_at,
            'email_enabled' => (bool) $user->email_two_factor_enabled,
            'preferred_method' => $this->emailTwoFactorService->getPreferredMethod($user),
            'has_any_2fa' => $this->emailTwoFactorService->hasAnyTwoFactorEnabled($user),
        ]);
    }

    /**
     * Resend the email 2FA code (force resend even if previous code not expired).
     */
    public function resendCode(Request $request): JsonResponse
    {
        $userId = $request->session()->get('login.id');

        if (! $userId) {
            return response()->json([
                'message' => 'Session invalide. Veuillez vous reconnecter.',
            ], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        if (! $user->email_two_factor_enabled) {
            return response()->json([
                'message' => 'L\'authentification par email n\'est pas activée pour ce compte.',
            ], 400);
        }

        // Clear the old code first
        $this->emailTwoFactorService->clearCode($user);

        if ($this->emailTwoFactorService->sendCode($user)) {
            return response()->json([
                'message' => 'Nouveau code de vérification envoyé.',
                'expires_in_minutes' => EmailTwoFactorService::CODE_EXPIRATION_MINUTES,
            ]);
        }

        return response()->json([
            'message' => 'Erreur lors de l\'envoi du code. Veuillez réessayer.',
        ], 500);
    }
}
