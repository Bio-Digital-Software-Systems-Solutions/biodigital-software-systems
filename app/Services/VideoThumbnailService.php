<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class VideoThumbnailService
{
    /**
     * Generate a thumbnail for a video file.
     *
     * @param  string  $videoPath  The video file path relative to storage/app/public
     * @param  string|null  $outputPath  The thumbnail output path (auto-generated if null)
     * @param  int  $timestamp  The timestamp in seconds to capture (default: 1)
     * @param  int  $width  The thumbnail width (height auto-scaled)
     * @return string|null The thumbnail path relative to storage/app/public, or null on failure
     */
    public function generate(
        string $videoPath,
        ?string $outputPath = null,
        int $timestamp = 1,
        int $width = 480
    ): ?string {
        // Get the full path to the video
        $fullVideoPath = Storage::disk('public')->path($videoPath);

        if (! file_exists($fullVideoPath)) {
            Log::warning('VideoThumbnailService: Video file not found', ['path' => $fullVideoPath]);

            return null;
        }

        // Generate output path if not provided
        if (! $outputPath) {
            $directory = dirname($videoPath);
            $filename = pathinfo($videoPath, PATHINFO_FILENAME);
            $outputPath = $directory.'/thumbnails/'.$filename.'_thumb.jpg';
        }

        // Ensure the thumbnail directory exists
        $fullOutputPath = Storage::disk('public')->path($outputPath);
        $outputDir = dirname($fullOutputPath);

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Check if ffmpeg is available
        if (! $this->isFfmpegAvailable()) {
            Log::warning('VideoThumbnailService: FFmpeg not available, attempting PHP-based fallback');

            return $this->generateFallbackThumbnail($videoPath, $outputPath);
        }

        // Get video duration to ensure timestamp is valid
        $duration = $this->getVideoDuration($fullVideoPath);
        if ($duration !== null && $timestamp > $duration) {
            $timestamp = max(0, intval($duration / 2)); // Use middle of video
        }

        // Generate thumbnail using ffmpeg
        // -ss: seek to timestamp
        // -i: input file
        // -vframes 1: extract one frame
        // -vf scale: scale to width with auto height
        // -y: overwrite output file
        $command = sprintf(
            'ffmpeg -ss %d -i %s -vframes 1 -vf "scale=%d:-1" -y %s 2>&1',
            $timestamp,
            escapeshellarg($fullVideoPath),
            $width,
            escapeshellarg($fullOutputPath)
        );

        $result = Process::run($command);

        if (! $result->successful() || ! file_exists($fullOutputPath)) {
            Log::error('VideoThumbnailService: FFmpeg failed', [
                'command' => $command,
                'output' => $result->output(),
                'error' => $result->errorOutput(),
            ]);

            return $this->generateFallbackThumbnail($videoPath, $outputPath);
        }

        Log::info('VideoThumbnailService: Thumbnail generated successfully', [
            'video' => $videoPath,
            'thumbnail' => $outputPath,
        ]);

        return $outputPath;
    }

    /**
     * Check if FFmpeg is available on the system.
     */
    public function isFfmpegAvailable(): bool
    {
        $result = Process::run('which ffmpeg 2>/dev/null || where ffmpeg 2>nul');

        return $result->successful() && !in_array(trim($result->output()), ['', '0'], true);
    }

    /**
     * Get the duration of a video in seconds.
     */
    public function getVideoDuration(string $fullVideoPath): ?float
    {
        if (! $this->isFfmpegAvailable()) {
            return null;
        }

        $command = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($fullVideoPath)
        );

        $result = Process::run($command);

        if ($result->successful()) {
            $duration = trim($result->output());
            if (is_numeric($duration)) {
                return (float) $duration;
            }
        }

        return null;
    }

    /**
     * Generate a fallback placeholder thumbnail when FFmpeg is not available.
     */
    protected function generateFallbackThumbnail(string $videoPath, string $outputPath): ?string
    {
        $fullOutputPath = Storage::disk('public')->path($outputPath);
        $outputDir = dirname($fullOutputPath);

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Create a simple placeholder image using GD
        if (! extension_loaded('gd')) {
            Log::warning('VideoThumbnailService: GD extension not available for fallback');

            return null;
        }

        $width = 480;
        $height = 270; // 16:9 aspect ratio

        $image = imagecreatetruecolor($width, $height);
        if (! $image) {
            return null;
        }

        // Dark gray background
        $bgColor = imagecolorallocate($image, 55, 65, 81);
        imagefill($image, 0, 0, $bgColor);

        // Draw a play icon circle
        $circleColor = imagecolorallocate($image, 100, 116, 139);
        $centerX = $width / 2;
        $centerY = $height / 2;
        $radius = 40;
        imagefilledellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $circleColor);

        // Draw play triangle
        $triangleColor = imagecolorallocate($image, 255, 255, 255);
        $points = [
            $centerX - 12, $centerY - 20, // top left
            $centerX - 12, $centerY + 20, // bottom left
            $centerX + 20, $centerY,       // right point
        ];
        imagefilledpolygon($image, $points, $triangleColor);

        // Save the image
        imagejpeg($image, $fullOutputPath, 85);
        imagedestroy($image);

        if (file_exists($fullOutputPath)) {
            Log::info('VideoThumbnailService: Fallback thumbnail generated', [
                'video' => $videoPath,
                'thumbnail' => $outputPath,
            ]);

            return $outputPath;
        }

        return null;
    }

    /**
     * Delete thumbnail for a video.
     */
    public function deleteThumbnail(string $thumbnailPath): bool
    {
        if (Storage::disk('public')->exists($thumbnailPath)) {
            return Storage::disk('public')->delete($thumbnailPath);
        }

        return true;
    }
}
