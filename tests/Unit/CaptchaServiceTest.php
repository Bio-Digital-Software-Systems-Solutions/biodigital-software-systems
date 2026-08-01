<?php

use App\Services\CaptchaService;
use Illuminate\Support\Facades\Session;

beforeEach(function (): void {
    $this->service = new CaptchaService;
});

it('generates a valid base64 png image and a token', function (): void {
    $captcha = $this->service->generate();

    expect($captcha)->toHaveKeys(['image', 'token'])
        ->and($captcha['image'])->toStartWith('data:image/png;base64,')
        ->and($captcha['token'])->toHaveLength(32);

    $binary = base64_decode(substr($captcha['image'], strlen('data:image/png;base64,')), true);
    $image = imagecreatefromstring($binary);

    expect($image)->toBeInstanceOf(GdImage::class)
        ->and(imagesx($image))->toBe(280)
        ->and(imagesy($image))->toBe(80);
});

it('stores a 5 character code without ambiguous characters in the session', function (): void {
    $this->service->generate();

    $code = $this->service->getCurrentCode();

    expect($code)->toHaveLength(5)
        ->and($code)->toMatch('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]+$/');
});

it('renders characters with a readable ttf font', function (): void {
    $fontPath = $this->service->getFontPath();

    expect($fontPath)->not->toBeNull()
        ->and($fontPath)->toEndWith('.ttf')
        ->and(file_exists($fontPath))->toBeTrue();
});

it('validates the correct answer case-insensitively', function (): void {
    $captcha = $this->service->generate();
    $code = $this->service->getCurrentCode();

    expect($this->service->validate('  '.strtolower($code).'  ', $captcha['token']))->toBeTrue();
});

it('rejects a wrong answer', function (): void {
    $captcha = $this->service->generate();

    expect($this->service->validate('WRONG', $captcha['token']))->toBeFalse();
});

it('rejects a wrong token', function (): void {
    $this->service->generate();
    $code = $this->service->getCurrentCode();

    expect($this->service->validate($code, 'invalid-token'))->toBeFalse();
});

it('is single use', function (): void {
    $captcha = $this->service->generate();
    $code = $this->service->getCurrentCode();

    expect($this->service->validate($code, $captcha['token']))->toBeTrue()
        ->and($this->service->validate($code, $captcha['token']))->toBeFalse();
});

it('rejects an expired captcha', function (): void {
    $captcha = $this->service->generate();
    $code = $this->service->getCurrentCode();

    Session::put('captcha_timestamp', now()->subMinutes(6)->timestamp);

    expect($this->service->validate($code, $captcha['token']))->toBeFalse();
});

it('rejects when no captcha was generated', function (): void {
    expect($this->service->validate('ABCDE', 'some-token'))->toBeFalse();
});
