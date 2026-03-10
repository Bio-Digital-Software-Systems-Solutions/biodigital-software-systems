<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Rules\CaptchaRule;
use App\Services\CaptchaService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly CaptchaService $captchaService
    ) {}

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        $captcha = $this->captchaService->generate();

        return Inertia::render('Auth/Register', [
            'captcha' => $captcha,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'birth_date' => 'required|date|before:today',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms_accepted' => 'required|accepted',
            'newsletter' => 'boolean',
            'captcha_answer' => ['required', new CaptchaRule],
            'captcha_token' => 'required|string',
        ], [
            'terms_accepted.required' => 'Vous devez accepter les conditions générales d\'utilisation.',
            'terms_accepted.accepted' => 'Vous devez accepter les conditions générales d\'utilisation.',
            'captcha_answer.required' => 'Veuillez répondre à la question de sécurité.',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'birth_date' => $request->birth_date,
            'avatar' => $avatarPath,
            'password' => Hash::make($request->password),
            'email_verified_at' => null,
            'terms_accepted_at' => now(),
            'newsletter' => $request->boolean('newsletter'),
        ]);

        $user->assignRole(Role::MEMBER);

        // Generate email verification URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1((string) $user->email)]
        );

        // Send welcome email with verification link
        Mail::to($user->email)->send(new WelcomeMail($user, $verificationUrl));

        event(new Registered($user));

        return redirect(route('verification.notice', absolute: false));
    }
}
