<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Generate a QR code as a base64 data URL.
     * Uses SVG format for better compatibility (no GD extension required).
     */
    public function generateBase64(string $data, int $size = 300): string
    {
        // Use SVG for better compatibility - no GD extension required
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => true, // Explicitly request base64 output
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgViewBoxSize' => $size,
        ]);

        $qrcode = new QRCode($options);
        $result = $qrcode->render($data);

        // Log for debugging in production
        \Log::debug('QR Code generated', [
            'data_length' => strlen($data),
            'result_length' => strlen((string) $result),
            'starts_with_data_image' => str_starts_with((string) $result, 'data:image'),
            'first_50_chars' => substr((string) $result, 0, 50),
        ]);

        // Validate the result is a proper data URL
        if (! str_starts_with((string) $result, 'data:image')) {
            \Log::error('QR Code generation returned invalid format', [
                'result_preview' => substr((string) $result, 0, 200),
            ]);
            throw new \RuntimeException('QR Code generation failed: invalid output format');
        }

        return $result;
    }

    /**
     * Generate a QR code as SVG string.
     */
    public function generateSvg(string $data): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgViewBoxSize' => 300,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }

    /**
     * Generate a QR code and return as downloadable PNG binary.
     */
    public function generatePng(string $data): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 10,
            'imageBase64' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }
}
