<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Generate a QR code as a base64 PNG data URL.
     */
    public function generateBase64(string $data, int $size = 300): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 10,
            'imageBase64' => true,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
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
