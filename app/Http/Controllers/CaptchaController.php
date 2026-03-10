<?php

namespace App\Http\Controllers;

use App\Services\CaptchaService;
use Illuminate\Http\JsonResponse;

class CaptchaController extends Controller
{
    public function __construct(
        private readonly CaptchaService $captchaService
    ) {}

    /**
     * Generate a new CAPTCHA challenge.
     */
    public function generate(): JsonResponse
    {
        $captcha = $this->captchaService->generate();

        return response()->json($captcha);
    }
}
