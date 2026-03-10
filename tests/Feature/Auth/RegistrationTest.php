<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /**
     * Helper to get a valid CAPTCHA answer and token.
     */
    private function getValidCaptcha(): array
    {
        $captchaService = app(CaptchaService::class);
        $captcha = $captchaService->generate();

        // Get the code directly from the service (for testing)
        $code = $captchaService->getCurrentCode();

        return [
            'token' => $captcha['token'],
            'answer' => $code,
        ];
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_page_contains_captcha(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('captcha')
            ->has('captcha.image')
            ->has('captcha.token')
        );
    }

    public function test_new_users_can_register_with_terms_accepted(): void
    {
        $captcha = $this->getValidCaptcha();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'newsletter' => false,
            'captcha_answer' => $captcha['answer'],
            'captcha_token' => $captcha['token'],
        ]);

        // User is created but needs to verify email
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'newsletter' => false,
        ]);

        // Verify terms_accepted_at is set
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->terms_accepted_at);

        // User should be redirected (either to dashboard or email verification)
        $response->assertRedirect();
    }

    public function test_registration_fails_without_terms_accepted(): void
    {
        $captcha = $this->getValidCaptcha();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => false,
            'captcha_answer' => $captcha['answer'],
            'captcha_token' => $captcha['token'],
        ]);

        // User should not be created
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);

        // Validation error should be returned
        $response->assertSessionHasErrors('terms_accepted');
    }

    public function test_registration_fails_when_terms_not_provided(): void
    {
        $captcha = $this->getValidCaptcha();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'captcha_answer' => $captcha['answer'],
            'captcha_token' => $captcha['token'],
        ]);

        // User should not be created
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);

        // Validation error should be returned
        $response->assertSessionHasErrors('terms_accepted');
    }

    public function test_user_can_register_with_newsletter_consent(): void
    {
        $captcha = $this->getValidCaptcha();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-newsletter@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'newsletter' => true,
            'captcha_answer' => $captcha['answer'],
            'captcha_token' => $captcha['token'],
        ]);

        // User is created with newsletter consent
        $this->assertDatabaseHas('users', [
            'email' => 'test-newsletter@example.com',
            'newsletter' => true,
        ]);

        // User should be redirected
        $response->assertRedirect();
    }

    public function test_user_can_register_without_newsletter_consent(): void
    {
        $captcha = $this->getValidCaptcha();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-no-newsletter@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'newsletter' => false,
            'captcha_answer' => $captcha['answer'],
            'captcha_token' => $captcha['token'],
        ]);

        // User is created without newsletter consent
        $this->assertDatabaseHas('users', [
            'email' => 'test-no-newsletter@example.com',
            'newsletter' => false,
        ]);

        // User should be redirected
        $response->assertRedirect();
    }

    public function test_terms_accepted_timestamp_is_stored(): void
    {
        $this->freezeTime();
        $captcha = $this->getValidCaptcha();

        $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-terms-timestamp@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'captcha_answer' => $captcha['answer'],
            'captcha_token' => $captcha['token'],
        ]);

        $user = User::where('email', 'test-terms-timestamp@example.com')->first();

        $this->assertNotNull($user->terms_accepted_at);
        $this->assertEquals(now()->toDateTimeString(), $user->terms_accepted_at->toDateTimeString());
    }

    public function test_registration_fails_without_captcha_answer(): void
    {
        $captcha = $this->getValidCaptcha();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-no-captcha@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'captcha_token' => $captcha['token'],
        ]);

        // User should not be created
        $this->assertDatabaseMissing('users', [
            'email' => 'test-no-captcha@example.com',
        ]);

        $response->assertSessionHasErrors('captcha_answer');
    }

    public function test_registration_fails_with_invalid_captcha_answer(): void
    {
        $captcha = $this->getValidCaptcha();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-invalid-captcha@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'captcha_answer' => 'WRONG', // Wrong answer
            'captcha_token' => $captcha['token'],
        ]);

        // User should not be created
        $this->assertDatabaseMissing('users', [
            'email' => 'test-invalid-captcha@example.com',
        ]);

        $response->assertSessionHasErrors('captcha_answer');
    }

    public function test_registration_fails_with_invalid_captcha_token(): void
    {
        $captcha = $this->getValidCaptcha();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-invalid-token@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'captcha_answer' => $captcha['answer'],
            'captcha_token' => 'invalid-token',
        ]);

        // User should not be created
        $this->assertDatabaseMissing('users', [
            'email' => 'test-invalid-token@example.com',
        ]);

        $response->assertSessionHasErrors('captcha_answer');
    }

    public function test_captcha_cannot_be_reused(): void
    {
        $captcha = $this->getValidCaptcha();

        // First registration succeeds
        $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-first@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'captcha_answer' => $captcha['answer'],
            'captcha_token' => $captcha['token'],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test-first@example.com',
        ]);

        // Second registration with same captcha fails
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-second@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'captcha_answer' => $captcha['answer'],
            'captcha_token' => $captcha['token'],
        ]);

        // Second user should not be created
        $this->assertDatabaseMissing('users', [
            'email' => 'test-second@example.com',
        ]);

        $response->assertSessionHasErrors('captcha_answer');
    }

    public function test_captcha_generate_endpoint_returns_valid_data(): void
    {
        $response = $this->get('/captcha');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'image',
            'token',
        ]);

        $data = $response->json();
        $this->assertStringStartsWith('data:image/png;base64,', $data['image']);
        $this->assertNotEmpty($data['token']);
    }

    public function test_captcha_is_case_insensitive(): void
    {
        $captcha = $this->getValidCaptcha();

        // Submit with lowercase answer
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-lowercase@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
            'captcha_answer' => strtolower((string) $captcha['answer']),
            'captcha_token' => $captcha['token'],
        ]);

        // User should be created (case-insensitive validation)
        $this->assertDatabaseHas('users', [
            'email' => 'test-lowercase@example.com',
        ]);

        $response->assertRedirect();
    }
}
