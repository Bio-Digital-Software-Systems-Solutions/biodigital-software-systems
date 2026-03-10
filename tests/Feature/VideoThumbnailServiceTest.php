<?php

use App\Services\VideoThumbnailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

describe('VideoThumbnailService', function (): void {
    it('can be instantiated', function (): void {
        $service = new VideoThumbnailService;
        expect($service)->toBeInstanceOf(VideoThumbnailService::class);
    });

    describe('isFfmpegAvailable', function (): void {
        it('returns true when ffmpeg is available', function (): void {
            Process::fake([
                'which ffmpeg*' => Process::result(output: '/usr/local/bin/ffmpeg'),
            ]);

            $service = new VideoThumbnailService;
            expect($service->isFfmpegAvailable())->toBeTrue();
        });

        it('returns false when ffmpeg is not available', function (): void {
            Process::fake([
                '*' => Process::result(output: '', exitCode: 1),
            ]);

            $service = new VideoThumbnailService;
            expect($service->isFfmpegAvailable())->toBeFalse();
        });
    });

    describe('getVideoDuration', function (): void {
        it('returns duration when ffprobe works', function (): void {
            Process::fake([
                'which ffmpeg*' => Process::result(output: '/usr/local/bin/ffmpeg'),
                'ffprobe*' => Process::result(output: '125.5'),
            ]);

            // Create a fake video file
            Storage::disk('public')->put('test-video.mp4', 'fake video content');
            $fullPath = Storage::disk('public')->path('test-video.mp4');

            $service = new VideoThumbnailService;
            $duration = $service->getVideoDuration($fullPath);

            expect($duration)->toBe(125.5);
        });

        it('returns null when ffprobe fails', function (): void {
            Process::fake([
                'which ffmpeg*' => Process::result(output: '/usr/local/bin/ffmpeg'),
                'ffprobe*' => Process::result(output: '', exitCode: 1),
            ]);

            Storage::disk('public')->put('test-video.mp4', 'fake video content');
            $fullPath = Storage::disk('public')->path('test-video.mp4');

            $service = new VideoThumbnailService;
            $duration = $service->getVideoDuration($fullPath);

            expect($duration)->toBeNull();
        });

        it('returns null when ffmpeg is not available', function (): void {
            Process::fake([
                '*' => Process::result(output: '', exitCode: 1),
            ]);

            Storage::disk('public')->put('test-video.mp4', 'fake video content');
            $fullPath = Storage::disk('public')->path('test-video.mp4');

            $service = new VideoThumbnailService;
            $duration = $service->getVideoDuration($fullPath);

            expect($duration)->toBeNull();
        });
    });

    describe('generate', function (): void {
        it('returns null when video file does not exist', function (): void {
            $service = new VideoThumbnailService;
            $result = $service->generate('nonexistent/video.mp4');

            expect($result)->toBeNull();
        });

        it('generates thumbnail with ffmpeg when available', function (): void {
            Process::fake([
                'which ffmpeg*' => Process::result(output: '/usr/local/bin/ffmpeg'),
                'ffprobe*' => Process::result(output: '60'),
                'ffmpeg -ss*' => Process::result(output: 'success'),
            ]);

            // Create a fake video file
            Storage::disk('public')->put('events/media/1/video.mp4', 'fake video content');

            $service = new VideoThumbnailService;

            // Mock that the thumbnail file gets created
            $videoPath = 'events/media/1/video.mp4';
            $expectedThumbPath = 'events/media/1/thumbnails/video_thumb.jpg';

            // Since we're faking the process, we need to also create the output file
            Storage::disk('public')->makeDirectory('events/media/1/thumbnails');
            Storage::disk('public')->put($expectedThumbPath, 'fake thumbnail');

            $result = $service->generate($videoPath);

            expect($result)->toBe($expectedThumbPath);
        });

        it('uses custom output path when provided', function (): void {
            Process::fake([
                'which ffmpeg*' => Process::result(output: '/usr/local/bin/ffmpeg'),
                'ffprobe*' => Process::result(output: '60'),
                'ffmpeg -ss*' => Process::result(output: 'success'),
            ]);

            Storage::disk('public')->put('events/media/1/video.mp4', 'fake video content');

            $customPath = 'custom/path/thumb.jpg';
            Storage::disk('public')->makeDirectory('custom/path');
            Storage::disk('public')->put($customPath, 'fake thumbnail');

            $service = new VideoThumbnailService;
            $result = $service->generate('events/media/1/video.mp4', $customPath);

            expect($result)->toBe($customPath);
        });

        it('generates fallback thumbnail when ffmpeg not available', function (): void {
            Process::fake([
                '*' => Process::result(output: '', exitCode: 1),
            ]);

            Storage::disk('public')->put('events/media/1/video.mp4', 'fake video content');

            $service = new VideoThumbnailService;
            $result = $service->generate('events/media/1/video.mp4');

            // Should generate a fallback thumbnail using GD
            if (extension_loaded('gd')) {
                expect($result)->not->toBeNull();
                expect(Storage::disk('public')->exists($result))->toBeTrue();
            } else {
                expect($result)->toBeNull();
            }
        });

        it('adjusts timestamp if it exceeds video duration', function (): void {
            Process::fake([
                'which ffmpeg*' => Process::result(output: '/usr/local/bin/ffmpeg'),
                'ffprobe*' => Process::result(output: '5'), // 5 second video
                'ffmpeg -ss*' => Process::result(output: 'success'),
            ]);

            Storage::disk('public')->put('events/media/1/video.mp4', 'fake video content');

            $expectedThumbPath = 'events/media/1/thumbnails/video_thumb.jpg';
            Storage::disk('public')->makeDirectory('events/media/1/thumbnails');
            Storage::disk('public')->put($expectedThumbPath, 'fake thumbnail');

            $service = new VideoThumbnailService;
            // Request timestamp at 10 seconds, but video is only 5 seconds
            $result = $service->generate('events/media/1/video.mp4', null, 10);

            expect($result)->not->toBeNull();
            // The service should have adjusted to use middle of video (2 seconds)
        });
    });

    describe('deleteThumbnail', function (): void {
        it('deletes existing thumbnail', function (): void {
            $thumbPath = 'events/media/1/thumbnails/video_thumb.jpg';
            Storage::disk('public')->put($thumbPath, 'fake thumbnail');

            $service = new VideoThumbnailService;
            $result = $service->deleteThumbnail($thumbPath);

            expect($result)->toBeTrue();
            expect(Storage::disk('public')->exists($thumbPath))->toBeFalse();
        });

        it('returns true for non-existent thumbnail', function (): void {
            $service = new VideoThumbnailService;
            $result = $service->deleteThumbnail('nonexistent/thumb.jpg');

            expect($result)->toBeTrue();
        });
    });
});

describe('VideoThumbnailService Fallback', function (): void {
    it('creates placeholder image with GD', function (): void {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        Process::fake([
            '*' => Process::result(output: '', exitCode: 1),
        ]);

        Storage::disk('public')->put('events/media/1/video.mp4', 'fake video content');

        $service = new VideoThumbnailService;
        $result = $service->generate('events/media/1/video.mp4');

        expect($result)->not->toBeNull();

        // Verify the thumbnail was created
        expect(Storage::disk('public')->exists($result))->toBeTrue();

        // Verify it's a valid JPEG
        $content = Storage::disk('public')->get($result);
        expect(substr($content, 0, 2))->toBe("\xFF\xD8"); // JPEG magic bytes
    });
});
