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

    private const TTF_FONT_SIZE = 32;

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
     * Draw characters with random colors, rotation and positions.
     */
    private function drawCharacters(\GdImage $image, string $code): void
    {
        $colors = [
            [185, 28, 28],   // Red
            [21, 128, 61],   // Green
            [29, 78, 216],   // Blue
            [126, 34, 206],  // Purple
            [180, 83, 9],    // Orange
            [190, 24, 93],   // Pink
            [15, 118, 110],  // Teal
        ];

        $fontPath = $this->getFontPath();
        $charWidth = (self::IMAGE_WIDTH - 40) / strlen($code);

        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];

            // Random color from palette
            $colorIndex = array_rand($colors);
            $rgb = $colors[$colorIndex];
            $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

            $x = 20 + ($i * $charWidth) + random_int(-4, 4);

            if ($fontPath !== null) {
                $this->drawTtfCharacter($image, $char, (int) $x, $color, $rgb, $fontPath);
            } else {
                $this->drawBitmapCharacter($image, $char, (int) $x, $color, $rgb);
            }
        }
    }

    /**
     * Draw a large, slightly rotated character with a TTF font.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function drawTtfCharacter(\GdImage $image, string $char, int $x, int $color, array $rgb, string $fontPath): void
    {
        $angle = random_int(-12, 12);
        $y = random_int(52, 62);

        $shadowColor = imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], 90);
        imagettftext($image, self::TTF_FONT_SIZE, $angle, $x + 2, $y + 2, $shadowColor, $fontPath, $char);

        imagettftext($image, self::TTF_FONT_SIZE, $angle, $x, $y, $color, $fontPath, $char);
    }

    /**
     * Fallback rendering with the GD built-in font when no TTF font is available.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function drawBitmapCharacter(\GdImage $image, string $char, int $x, int $color, array $rgb): void
    {
        $fontSize = 5; // Built-in font size (1-5)
        $y = random_int(20, 40);

        for ($dx = 0; $dx <= 2; $dx++) {
            for ($dy = 0; $dy <= 2; $dy++) {
                imagestring($image, $fontSize, $x + $dx, $y + $dy, $char, $color);
            }
        }

        $shadowColor = imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], 80);
        imagestring($image, $fontSize, $x + 3, $y + 3, $char, $shadowColor);
    }

    /**
     * Resolve the TTF font used to render the CAPTCHA characters.
     *
     * Looks in storage/app/captcha/fonts first, then falls back to the
     * DejaVu font bundled with dompdf. Returns null when neither is
     * available (bitmap fallback is used in that case).
     */
    public function getFontPath(): ?string
    {
        $fonts = glob(storage_path('app/captcha/fonts/*.ttf')) ?: [];

        if ($fonts !== []) {
            return $fonts[array_rand($fonts)];
        }

        $bundledFont = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');

        return is_file($bundledFont) ? $bundledFont : null;
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
        return strtoupper(trim($answer ?? '')) === strtoupper((string) $stored['code']);
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
