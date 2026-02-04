<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class CaptchaService
{
    private const SESSION_KEY = 'captcha_data';

    private const SESSION_TIMESTAMP_KEY = 'captcha_timestamp';

    private const CAPTCHA_LENGTH = 5;

    private const IMAGE_WIDTH = 280;

    private const IMAGE_HEIGHT = 80;

    /**
     * Generate a new CAPTCHA with image.
     */
    public function generate(): array
    {
        // Generate random alphanumeric code (excluding confusing characters)
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < self::CAPTCHA_LENGTH; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Generate unique token
        $token = bin2hex(random_bytes(16));

        // Store in session
        Session::put(self::SESSION_KEY, [
            'code' => strtoupper($code),
            'token' => $token,
        ]);
        Session::put(self::SESSION_TIMESTAMP_KEY, now()->timestamp);

        // Generate the image
        $imageData = $this->generateImage($code);

        return [
            'image' => $imageData,
            'token' => $token,
        ];
    }

    /**
     * Generate CAPTCHA image using GD.
     */
    private function generateImage(string $code): string
    {
        // Create image
        $image = imagecreatetruecolor(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);

        // Enable alpha blending
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Background color (light gray)
        $bgColor = imagecolorallocate($image, 245, 245, 245);
        imagefilledrectangle($image, 0, 0, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, $bgColor);

        // Add noise lines
        $this->addNoiseLines($image);

        // Add noise dots
        $this->addNoiseDots($image);

        // Draw each character with random styling
        $this->drawCharacters($image, $code);

        // Add more noise lines on top
        $this->addNoiseLines($image, 3);

        // Output as base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($imageData);
    }

    /**
     * Add noise lines to the image.
     */
    private function addNoiseLines(\GdImage $image, int $count = 5): void
    {
        for ($i = 0; $i < $count; $i++) {
            $color = imagecolorallocate(
                $image,
                random_int(100, 200),
                random_int(100, 200),
                random_int(100, 200)
            );

            // Draw curved/wavy lines
            $x1 = random_int(0, self::IMAGE_WIDTH / 4);
            $y1 = random_int(0, self::IMAGE_HEIGHT);
            $x2 = random_int(self::IMAGE_WIDTH * 3 / 4, self::IMAGE_WIDTH);
            $y2 = random_int(0, self::IMAGE_HEIGHT);

            imagesetthickness($image, random_int(1, 2));
            imageline($image, $x1, $y1, $x2, $y2, $color);
        }
    }

    /**
     * Add noise dots to the image.
     */
    private function addNoiseDots(\GdImage $image): void
    {
        for ($i = 0; $i < 200; $i++) {
            $color = imagecolorallocate(
                $image,
                random_int(150, 220),
                random_int(150, 220),
                random_int(150, 220)
            );
            imagesetpixel(
                $image,
                random_int(0, self::IMAGE_WIDTH),
                random_int(0, self::IMAGE_HEIGHT),
                $color
            );
        }
    }

    /**
     * Draw characters with random colors and positions.
     */
    private function drawCharacters(\GdImage $image, string $code): void
    {
        $colors = [
            [220, 38, 38],   // Red
            [34, 197, 94],   // Green
            [59, 130, 246],  // Blue
            [168, 85, 247],  // Purple
            [234, 179, 8],   // Yellow/Orange
            [236, 72, 153],  // Pink
            [20, 184, 166],  // Teal
        ];

        $charWidth = (self::IMAGE_WIDTH - 40) / strlen($code);
        $fontSize = 5; // Built-in font size (1-5)

        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];

            // Random color from palette
            $colorIndex = array_rand($colors);
            $rgb = $colors[$colorIndex];
            $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

            // Calculate position with some randomness
            $x = 20 + ($i * $charWidth) + random_int(-5, 5);
            $y = random_int(20, 40);

            // Draw character multiple times at slightly different positions for a bolder effect
            for ($dx = 0; $dx <= 2; $dx++) {
                for ($dy = 0; $dy <= 2; $dy++) {
                    imagestring($image, $fontSize, (int) $x + $dx, (int) $y + $dy, $char, $color);
                }
            }

            // Add a slight shadow for depth
            $shadowColor = imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], 80);
            imagestring($image, $fontSize, (int) $x + 3, (int) $y + 3, $char, $shadowColor);
        }
    }

    /**
     * Validate the CAPTCHA answer.
     */
    public function validate(?string $answer, ?string $token): bool
    {
        $stored = Session::get(self::SESSION_KEY);
        $timestamp = Session::get(self::SESSION_TIMESTAMP_KEY);

        // Clear the CAPTCHA after validation attempt (single use)
        Session::forget([self::SESSION_KEY, self::SESSION_TIMESTAMP_KEY]);

        if (! $stored || ! $timestamp) {
            return false;
        }

        // Verify token matches
        if ($stored['token'] !== $token) {
            return false;
        }

        // Check if CAPTCHA has expired (5 minutes)
        if (now()->timestamp - $timestamp > 300) {
            return false;
        }

        // Compare answer (case-insensitive)
        return strtoupper(trim($answer ?? '')) === strtoupper($stored['code']);
    }

    /**
     * Get the current CAPTCHA code (for testing only).
     */
    public function getCurrentCode(): ?string
    {
        $stored = Session::get(self::SESSION_KEY);

        return $stored['code'] ?? null;
    }
}
