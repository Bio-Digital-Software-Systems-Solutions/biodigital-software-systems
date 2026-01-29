<?php

use App\Models\Event;
use App\Models\Event\EventMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');

    // Create permissions
    Permission::firstOrCreate(['name' => 'view events']);
    Permission::firstOrCreate(['name' => 'create events']);
    Permission::firstOrCreate(['name' => 'edit events']);
    Permission::firstOrCreate(['name' => 'delete events']);

    // Create admin role with all permissions
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $adminRole->syncPermissions(['view events', 'create events', 'edit events', 'delete events']);

    // Create member role with view only
    $memberRole = Role::firstOrCreate(['name' => 'member']);
    $memberRole->syncPermissions(['view events']);
});

describe('EventMedia Model', function () {
    it('can be created with required fields', function () {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        $media = EventMedia::create([
            'event_id' => $event->id,
            'uploaded_by' => $user->id,
            'title' => 'Test Image',
            'file_path' => 'events/media/test.jpg',
            'file_name' => 'test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 1024,
            'media_type' => EventMedia::TYPE_IMAGE,
            'collection' => EventMedia::COLLECTION_GALLERY,
        ]);

        expect($media)->toBeInstanceOf(EventMedia::class);
        expect($media->title)->toBe('Test Image');
        expect($media->media_type)->toBe('image');
        expect($media->collection)->toBe('gallery');
    });

    it('belongs to an event', function () {
        $event = Event::factory()->create();
        $media = EventMedia::factory()->create(['event_id' => $event->id]);

        expect($media->event)->toBeInstanceOf(Event::class);
        expect($media->event->id)->toBe($event->id);
    });

    it('belongs to an uploader', function () {
        $user = User::factory()->create();
        $media = EventMedia::factory()->create(['uploaded_by' => $user->id]);

        expect($media->uploader)->toBeInstanceOf(User::class);
        expect($media->uploader->id)->toBe($user->id);
    });

    it('can filter by media type', function () {
        $event = Event::factory()->create();

        EventMedia::factory()->image()->count(3)->create(['event_id' => $event->id]);
        EventMedia::factory()->video()->count(2)->create(['event_id' => $event->id]);

        expect(EventMedia::where('event_id', $event->id)->images()->count())->toBe(3);
        expect(EventMedia::where('event_id', $event->id)->videos()->count())->toBe(2);
    });

    it('can filter by collection', function () {
        $event = Event::factory()->create();

        EventMedia::factory()->banner()->count(1)->create(['event_id' => $event->id]);
        EventMedia::factory()->gallery()->count(4)->create(['event_id' => $event->id]);

        expect(EventMedia::where('event_id', $event->id)->banner()->count())->toBe(1);
        expect(EventMedia::where('event_id', $event->id)->gallery()->count())->toBe(4);
    });

    it('can determine if media is image', function () {
        $image = EventMedia::factory()->image()->create();
        $video = EventMedia::factory()->video()->create();

        expect($image->isImage())->toBeTrue();
        expect($video->isImage())->toBeFalse();
    });

    it('can determine if media is video', function () {
        $image = EventMedia::factory()->image()->create();
        $video = EventMedia::factory()->video()->create();

        expect($image->isVideo())->toBeFalse();
        expect($video->isVideo())->toBeTrue();
    });

    it('can set media as featured', function () {
        $event = Event::factory()->create();
        $media1 = EventMedia::factory()->featured()->create(['event_id' => $event->id]);
        $media2 = EventMedia::factory()->create(['event_id' => $event->id, 'is_featured' => false]);

        expect($media1->is_featured)->toBeTrue();
        expect($media2->is_featured)->toBeFalse();

        $media2->setAsFeatured();

        $media1->refresh();
        $media2->refresh();

        expect($media1->is_featured)->toBeFalse();
        expect($media2->is_featured)->toBeTrue();
    });

    it('generates correct file url', function () {
        $media = EventMedia::factory()->create([
            'file_path' => 'events/media/test.jpg',
        ]);

        expect($media->file_url)->toContain('storage/events/media/test.jpg');
    });

    it('formats file size for humans', function () {
        $media = EventMedia::factory()->create(['file_size' => 1536]);
        expect($media->file_size_for_humans)->toBe('1.5 KB');

        $media->file_size = 1572864;
        expect($media->file_size_for_humans)->toBe('1.5 MB');
    });

    it('formats duration for humans', function () {
        $media = EventMedia::factory()->video()->create(['duration' => 125]);
        expect($media->duration_for_humans)->toBe('2:05');

        $media->duration = 3725;
        expect($media->duration_for_humans)->toBe('1:02:05');
    });

    it('detects media type from mime', function () {
        expect(EventMedia::getMediaTypeFromMime('image/jpeg'))->toBe('image');
        expect(EventMedia::getMediaTypeFromMime('image/png'))->toBe('image');
        expect(EventMedia::getMediaTypeFromMime('video/mp4'))->toBe('video');
        expect(EventMedia::getMediaTypeFromMime('video/webm'))->toBe('video');
        expect(EventMedia::getMediaTypeFromMime('application/pdf'))->toBeNull();
    });
});

describe('Event Model Media Relationships', function () {
    it('has many media', function () {
        $event = Event::factory()->create();
        EventMedia::factory()->count(5)->create(['event_id' => $event->id]);

        expect($event->media)->toHaveCount(5);
    });

    it('can get only images', function () {
        $event = Event::factory()->create();
        EventMedia::factory()->image()->count(3)->create(['event_id' => $event->id]);
        EventMedia::factory()->video()->count(2)->create(['event_id' => $event->id]);

        expect($event->images()->get())->toHaveCount(3);
    });

    it('can get only videos', function () {
        $event = Event::factory()->create();
        EventMedia::factory()->image()->count(3)->create(['event_id' => $event->id]);
        EventMedia::factory()->video()->count(2)->create(['event_id' => $event->id]);

        expect($event->videos()->get())->toHaveCount(2);
    });

    it('can get banners', function () {
        $event = Event::factory()->create();
        EventMedia::factory()->image()->banner()->create(['event_id' => $event->id]);
        EventMedia::factory()->image()->gallery()->count(3)->create(['event_id' => $event->id]);

        expect($event->banners()->get())->toHaveCount(1);
    });

    it('can get gallery media', function () {
        $event = Event::factory()->create();
        EventMedia::factory()->banner()->create(['event_id' => $event->id]);
        EventMedia::factory()->gallery()->count(4)->create(['event_id' => $event->id]);

        expect($event->galleryMedia()->get())->toHaveCount(4);
    });
});

describe('EventMediaController', function () {
    it('requires authentication to upload media', function () {
        $event = Event::factory()->create();

        $response = $this->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertUnauthorized();
    });

    it('requires edit events permission to upload media', function () {
        $user = User::factory()->create();
        $user->assignRole('member');
        $event = Event::factory()->create();

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertForbidden();
    });

    it('allows admin to upload image', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
            'title' => 'Test Image',
            'collection' => 'gallery',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'media' => ['id', 'uuid', 'title', 'file_path', 'media_type'],
        ]);

        $this->assertDatabaseHas('event_media', [
            'event_id' => $event->id,
            'title' => 'Test Image',
            'media_type' => 'image',
            'collection' => 'gallery',
        ]);
    });

    it('allows admin to upload video', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4'),
            'title' => 'Test Video',
            'collection' => 'gallery',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('event_media', [
            'event_id' => $event->id,
            'title' => 'Test Video',
            'media_type' => 'video',
        ]);
    });

    it('validates file type', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf'),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    });

    it('allows admin to update media', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();
        $media = EventMedia::factory()->create(['event_id' => $event->id]);

        $response = $this->actingAs($user)->putJson(
            route('events.media.update', [$event->uuid, $media->uuid]),
            [
                'title' => 'Updated Title',
                'description' => 'Updated Description',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('event_media', [
            'id' => $media->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ]);
    });

    it('allows admin to delete media', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();
        $media = EventMedia::factory()->create(['event_id' => $event->id]);

        $response = $this->actingAs($user)->deleteJson(
            route('events.media.destroy', [$event->uuid, $media->uuid])
        );

        $response->assertOk();
        $this->assertSoftDeleted('event_media', ['id' => $media->id]);
    });

    it('allows admin to set media as banner', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();
        $media = EventMedia::factory()->image()->gallery()->create(['event_id' => $event->id]);

        $response = $this->actingAs($user)->postJson(
            route('events.media.set-banner', [$event->uuid, $media->uuid])
        );

        $response->assertOk();

        $media->refresh();
        expect($media->collection)->toBe('banner');
    });

    it('allows admin to set media as featured', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();
        $media = EventMedia::factory()->create([
            'event_id' => $event->id,
            'is_featured' => false,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('events.media.set-featured', [$event->uuid, $media->uuid])
        );

        $response->assertOk();

        $media->refresh();
        expect($media->is_featured)->toBeTrue();
    });

    it('allows admin to reorder media', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        $media1 = EventMedia::factory()->create(['event_id' => $event->id, 'sort_order' => 0]);
        $media2 = EventMedia::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
        $media3 = EventMedia::factory()->create(['event_id' => $event->id, 'sort_order' => 2]);

        $response = $this->actingAs($user)->postJson(
            route('events.media.reorder', $event->uuid),
            [
                'media_ids' => [$media3->id, $media1->id, $media2->id],
            ]
        );

        $response->assertOk();

        $media1->refresh();
        $media2->refresh();
        $media3->refresh();

        expect($media3->sort_order)->toBe(0);
        expect($media1->sort_order)->toBe(1);
        expect($media2->sort_order)->toBe(2);
    });

    it('prevents updating media from different event', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event1 = Event::factory()->create();
        $event2 = Event::factory()->create();
        $media = EventMedia::factory()->create(['event_id' => $event2->id]);

        $response = $this->actingAs($user)->putJson(
            route('events.media.update', [$event1->uuid, $media->uuid]),
            ['title' => 'New Title']
        );

        $response->assertNotFound();
    });

    it('allows admin to upload banner image directly', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->image('banner.jpg', 1920, 1080),
            'collection' => 'banner',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('event_media', [
            'event_id' => $event->id,
            'media_type' => 'image',
            'collection' => 'banner',
        ]);
    });

    it('replaces previous banner when uploading new one', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        // Upload first banner
        $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->image('banner1.jpg', 1920, 1080),
            'collection' => 'banner',
        ]);

        // Upload second banner via set-banner action
        $media = EventMedia::factory()->image()->gallery()->create(['event_id' => $event->id]);
        Storage::disk('public')->put($media->file_path, 'fake content');

        $this->actingAs($user)->postJson(
            route('events.media.set-banner', [$event->uuid, $media->uuid])
        );

        // Old banner should now be in gallery
        expect($event->banners()->count())->toBe(1);
        expect($event->banners()->first()->id)->toBe($media->id);
    });

    it('validates video MIME types correctly', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        // Test valid video types
        $validTypes = ['mp4', 'webm', 'mov'];
        foreach ($validTypes as $type) {
            $mimeType = match ($type) {
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'mov' => 'video/quicktime',
            };

            $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
                'file' => UploadedFile::fake()->create("test.{$type}", 1024, $mimeType),
            ]);

            $response->assertCreated();
        }
    });

    it('rejects invalid video types', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->create('test.avi', 1024, 'video/x-msvideo'),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    });

    it('validates image MIME types correctly', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        // Test valid image types
        $validTypes = ['jpg', 'png', 'gif', 'webp'];
        foreach ($validTypes as $type) {
            $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
                'file' => UploadedFile::fake()->image("test.{$type}", 800, 600),
            ]);

            $response->assertCreated();
        }
    });
});

describe('EventController with Media', function () {
    it('includes media in event show page', function () {
        $user = User::factory()->create();
        $user->assignRole('member');
        $event = Event::factory()->create();

        // Create media with actual files
        $bannerMedia = EventMedia::factory()->image()->banner()->create(['event_id' => $event->id]);
        Storage::disk('public')->put($bannerMedia->file_path, 'fake content');

        $galleryMedia = EventMedia::factory()->image()->gallery()->create(['event_id' => $event->id]);
        Storage::disk('public')->put($galleryMedia->file_path, 'fake content');

        $response = $this->actingAs($user)->get(route('events.show', $event->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->has('banners')
            ->has('galleryImages')
            ->has('galleryVideos')
        );
    });

    it('includes media in event edit page', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create(['user_id' => $user->id]);

        // Create media with actual files
        $media = EventMedia::factory()->create(['event_id' => $event->id]);
        Storage::disk('public')->put($media->file_path, 'fake content');

        $response = $this->actingAs($user)->get(route('events.edit', $event->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Events/Edit')
            ->has('event.media')
            ->has('banners')
            ->has('galleryImages')
            ->has('galleryVideos')
        );
    });

    it('filters out media without existing files', function () {
        $user = User::factory()->create();
        $user->assignRole('member');
        $event = Event::factory()->create();

        // Create media WITHOUT actual file (orphaned)
        EventMedia::factory()->image()->gallery()->create([
            'event_id' => $event->id,
            'file_path' => 'events/media/non-existent.jpg',
        ]);

        // Create media WITH actual file
        $validMedia = EventMedia::factory()->image()->gallery()->create(['event_id' => $event->id]);
        Storage::disk('public')->put($validMedia->file_path, 'fake content');

        $response = $this->actingAs($user)->get(route('events.show', $event->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('galleryImages', function ($images) {
                return count($images) === 1;
            })
        );
    });
});

describe('EventMedia File Validation', function () {
    it('can check if file exists', function () {
        $media = EventMedia::factory()->create();

        // File doesn't exist
        expect($media->fileExists())->toBeFalse();
        expect($media->file_exists)->toBeFalse();

        // Create the file
        Storage::disk('public')->put($media->file_path, 'fake content');

        // Now file exists
        expect($media->fileExists())->toBeTrue();
        expect($media->file_exists)->toBeTrue();
    });

    it('returns correct file size for humans', function () {
        $media = EventMedia::factory()->create(['file_size' => 500]);
        expect($media->file_size_for_humans)->toBe('500 B');

        $media->file_size = 1536;
        expect($media->file_size_for_humans)->toBe('1.5 KB');

        $media->file_size = 1572864;
        expect($media->file_size_for_humans)->toBe('1.5 MB');

        $media->file_size = 1610612736;
        expect($media->file_size_for_humans)->toBe('1.5 GB');
    });
});

describe('Video Thumbnail Generation', function () {
    it('stores thumbnail_path when video is uploaded', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        // Mock the VideoThumbnailService to return a thumbnail path
        $mockThumbnailService = Mockery::mock(\App\Services\VideoThumbnailService::class);
        $mockThumbnailService->shouldReceive('generate')
            ->once()
            ->andReturn('events/media/'.$event->id.'/thumbnails/test_thumb.jpg');
        $mockThumbnailService->shouldReceive('getVideoDuration')
            ->once()
            ->andReturn(120.5);

        app()->instance(\App\Services\VideoThumbnailService::class, $mockThumbnailService);

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertCreated();

        $media = EventMedia::where('event_id', $event->id)->first();
        expect($media->thumbnail_path)->toBe('events/media/'.$event->id.'/thumbnails/test_thumb.jpg');
        expect($media->duration)->toBe(121); // Rounded from 120.5
    });

    it('stores video duration when video is uploaded', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        $mockThumbnailService = Mockery::mock(\App\Services\VideoThumbnailService::class);
        $mockThumbnailService->shouldReceive('generate')->andReturn(null);
        $mockThumbnailService->shouldReceive('getVideoDuration')->andReturn(300.0);

        app()->instance(\App\Services\VideoThumbnailService::class, $mockThumbnailService);

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertCreated();

        $media = EventMedia::where('event_id', $event->id)->first();
        expect($media->duration)->toBe(300);
    });

    it('handles null duration gracefully', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        $mockThumbnailService = Mockery::mock(\App\Services\VideoThumbnailService::class);
        $mockThumbnailService->shouldReceive('generate')->andReturn(null);
        $mockThumbnailService->shouldReceive('getVideoDuration')->andReturn(null);

        app()->instance(\App\Services\VideoThumbnailService::class, $mockThumbnailService);

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertCreated();

        $media = EventMedia::where('event_id', $event->id)->first();
        expect($media->duration)->toBeNull();
    });

    it('does not generate thumbnail for images', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $event = Event::factory()->create();

        $mockThumbnailService = Mockery::mock(\App\Services\VideoThumbnailService::class);
        // Should NOT be called for images
        $mockThumbnailService->shouldNotReceive('generate');
        $mockThumbnailService->shouldNotReceive('getVideoDuration');

        app()->instance(\App\Services\VideoThumbnailService::class, $mockThumbnailService);

        $response = $this->actingAs($user)->postJson(route('events.media.store', $event->uuid), [
            'file' => UploadedFile::fake()->image('test.jpg', 800, 600),
        ]);

        $response->assertCreated();

        $media = EventMedia::where('event_id', $event->id)->first();
        expect($media->media_type)->toBe('image');
        expect($media->thumbnail_path)->toBeNull();
    });

    it('returns thumbnail_url in API response', function () {
        $media = EventMedia::factory()->video()->create([
            'thumbnail_path' => 'events/media/1/thumbnails/thumb.jpg',
        ]);

        // asset() returns full URL, so we check it ends with the correct path
        expect($media->thumbnail_url)->toEndWith('/storage/events/media/1/thumbnails/thumb.jpg');
    });

    it('returns null thumbnail_url when no thumbnail exists', function () {
        $media = EventMedia::factory()->video()->create([
            'thumbnail_path' => null,
        ]);

        expect($media->thumbnail_url)->toBeNull();
    });

    it('formats duration for humans correctly', function () {
        $media = EventMedia::factory()->video()->create(['duration' => 65]);
        expect($media->duration_for_humans)->toBe('1:05');

        $media->duration = 3661;
        expect($media->duration_for_humans)->toBe('1:01:01');

        $media->duration = 30;
        expect($media->duration_for_humans)->toBe('0:30');

        $media->duration = null;
        expect($media->duration_for_humans)->toBeNull();
    });
});
