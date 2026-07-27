<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('privacy policy page can be accessed', function (): void {
    $response = $this->get('/privacy-policy');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/PrivacyPolicy')
    );
});

test('terms of service page can be accessed', function (): void {
    $response = $this->get('/terms-of-service');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/TermsOfService')
    );
});

test('imprint page can be accessed', function (): void {
    $response = $this->get('/imprint');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/Imprint')
    );
});

test('localized imprint aliases redirect to the imprint page', function (string $alias): void {
    $this->get($alias)->assertRedirect('/imprint');
})->with(['/impressum', '/mentions-legales']);

test('localized privacy aliases redirect to the privacy policy page', function (string $alias): void {
    $this->get($alias)->assertRedirect('/privacy-policy');
})->with(['/datenschutz', '/politique-de-confidentialite']);

test('legal pages routes are registered', function (): void {
    expect(route('privacy-policy'))->toBe(url('/privacy-policy'));
    expect(route('terms-of-service'))->toBe(url('/terms-of-service'));
    expect(route('imprint'))->toBe(url('/imprint'));
});
