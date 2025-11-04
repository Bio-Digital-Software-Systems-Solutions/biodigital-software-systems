<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('privacy policy page can be accessed', function () {
    $response = $this->get('/privacy-policy');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/PrivacyPolicy')
    );
});

test('terms of service page can be accessed', function () {
    $response = $this->get('/terms-of-service');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Legal/TermsOfService')
    );
});

test('legal pages routes are registered', function () {
    expect(route('privacy-policy'))->toBe(url('/privacy-policy'));
    expect(route('terms-of-service'))->toBe(url('/terms-of-service'));
});