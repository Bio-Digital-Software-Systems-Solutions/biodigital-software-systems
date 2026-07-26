<?php

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the brand wordmark with the site theme colors', function () {
    $user = User::factory()->create();

    $html = (new WelcomeMail($user, 'https://example.com/verify'))->render();

    expect($html)
        ->toContain('Bio-<span class="brand-accent">Digital</span>')
        ->toContain('Software Systems Solutions UG (haftungsbeschränkt)')
        ->toContain('#D41F32')
        ->toContain('#EB5462');
});

it('contains the user details and the verification link', function () {
    $user = User::factory()->create([
        'first_name' => 'Elmarce',
        'last_name' => 'Bounda Ndinga',
    ]);

    $html = (new WelcomeMail($user, 'https://example.com/verify'))->render();

    expect($html)
        ->toContain('Bienvenue, Elmarce Bounda Ndinga !')
        ->toContain($user->email)
        ->toContain('https://example.com/verify');
});

it('does not reference ICC München anywhere', function () {
    $user = User::factory()->create();

    $mail = new WelcomeMail($user, 'https://example.com/verify');

    expect($mail->envelope()->subject)->not->toContain('ICC');
    expect($mail->render())->not->toContain('ICC');
});
