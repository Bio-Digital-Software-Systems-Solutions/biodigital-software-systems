<?php

use App\Mail\TrainingEnrollmentSubmitted;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@test.com',
    ]);
    $this->user->assignRole('member');

    $this->training = Training::factory()->create([
        'title' => 'Formation Laravel Avancé',
        'is_active' => true,
    ]);

    $this->trainingClass = TrainingClass::factory()->create([
        'training_id' => $this->training->id,
    ]);
});

it('sends a submission email when a user enrolls in a training', function (): void {
    Mail::fake();

    $response = $this->actingAs($this->user)
        ->post(route('trainings.enroll', $this->training), [
            'selectedClassId' => $this->trainingClass->id,
            'motivation' => str_repeat('Je suis très motivé pour suivre cette formation. ', 3),
            'paymentMethod' => 'monthly',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ]);

    $response->assertRedirect();

    Mail::assertSent(TrainingEnrollmentSubmitted::class, fn($mail): bool => $mail->hasTo('jane@test.com')
        && $mail->userName === 'Jane Smith'
        && $mail->trainingName === 'Formation Laravel Avancé'
        && $mail->paymentMethod === 'monthly');
});

it('does not send a submission email when enrollment validation fails', function (): void {
    Mail::fake();

    $response = $this->actingAs($this->user)
        ->postJson(route('trainings.enroll', $this->training), [
            'selectedClassId' => $this->trainingClass->id,
            'motivation' => 'Trop court',
            'paymentMethod' => 'monthly',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ]);

    $response->assertUnprocessable();

    Mail::assertNotSent(TrainingEnrollmentSubmitted::class);
});

it('does not send a submission email when user is already enrolled', function (): void {
    Mail::fake();

    $this->training->students()->attach($this->user->id, [
        'status' => 'pending',
        'enrolled_at' => now(),
        'training_class_id' => $this->trainingClass->id,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('trainings.enroll', $this->training), [
            'selectedClassId' => $this->trainingClass->id,
            'motivation' => str_repeat('Je suis très motivé pour suivre cette formation. ', 3),
            'paymentMethod' => 'monthly',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ]);

    Mail::assertNotSent(TrainingEnrollmentSubmitted::class);
});

it('includes correct subject in the submission email', function (): void {
    Mail::fake();

    $this->actingAs($this->user)
        ->post(route('trainings.enroll', $this->training), [
            'selectedClassId' => $this->trainingClass->id,
            'motivation' => str_repeat('Je suis très motivé pour suivre cette formation. ', 3),
            'paymentMethod' => 'full',
            'hasReadTerms' => true,
            'hasReadPrivacyPolicy' => true,
        ]);

    Mail::assertSent(TrainingEnrollmentSubmitted::class, fn($mail): bool => $mail->envelope()->subject === 'Demande d\'inscription soumise - Formation Laravel Avancé');
});
