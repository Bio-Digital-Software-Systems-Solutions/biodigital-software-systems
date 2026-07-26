<?php

use Illuminate\Notifications\Messages\MailMessage;

it('applies the brand wordmark and theme colors to notification mails', function () {
    $html = (string) (new MailMessage)
        ->line('Contenu de test')
        ->action('Voir', 'https://example.com')
        ->render();

    expect($html)
        ->toContain('Bio-')
        ->toContain('Digital')
        ->toContain('Software Systems Solutions UG')
        ->toContain('#EB5462')
        ->toContain('#D41F32')
        ->not->toContain('ICC');
});

it('brands every custom email template', function (string $template) {
    $content = file_get_contents(resource_path("views/emails/{$template}"));

    expect($content)
        ->toContain('brand-wordmark')
        ->toContain('#EB5462')
        ->toContain('#171717')
        ->not->toContain('ICC')
        ->not->toContain('#8b5cf6')
        ->not->toContain('#667eea');
})->with([
    'welcome' => 'welcome.blade.php',
    'appointment-created' => 'appointment-created.blade.php',
    'appointment-invitation' => 'appointment-invitation.blade.php',
    'appointment-reminder' => 'appointment-reminder.blade.php',
    'contact-submitted' => 'contact-submitted.blade.php',
    'training-enrollment-approved' => 'training-enrollment-approved.blade.php',
    'training-enrollment-rejected' => 'training-enrollment-rejected.blade.php',
    'training-enrollment-submitted' => 'training-enrollment-submitted.blade.php',
]);
