<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Event\EventMedia;
use App\Services\CacheService;
use App\Services\VideoThumbnailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EventMediaController extends Controller
{
    public function __construct(
        protected VideoThumbnailService $thumbnailService
    ) {
        $this->middleware('can:edit events');
    }

    /**
     * Display all media for an event.
     */
    public function index(Event $event): Response
    {
        $event->load(['media' => fn ($q) => $q->ordered()]);

        return Inertia::render('Events/Media/Index', [
            'event' => $event,
            'media' => $event->media,
            'images' => $event->images,
            'videos' => $event->videos,
            'banners' => $event->banners,
        ]);
    }

    /**
     * Store a newly uploaded media file.
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:102400', // 100MB max
                'mimes:jpeg,jpg,png,gif,webp,mp4,webm,ogg,mov',
            ],
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'collection' => 'nullable|string|in:banner,gallery',
            'is_featured' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $mimeType = $file->getMimeType();
        $mediaType = EventMedia::getMediaTypeFromMime($mimeType);

        if (! $mediaType) {
            return response()->json([
                'message' => 'Type de fichier non supporté.',
            ], 422);
        }

        // Store the file
        $path = $file->store('events/media/'.$event->id, 'public');
        $fullPath = Storage::disk('public')->path($path);

        // Get image dimensions if it's an image
        $width = null;
        $height = null;
        $duration = null;
        $thumbnailPath = null;

        if ($mediaType === EventMedia::TYPE_IMAGE) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        } elseif ($mediaType === EventMedia::TYPE_VIDEO) {
            // Generate thumbnail for video
            $thumbnailPath = $this->thumbnailService->generate($path);

            // Get video duration
            $duration = $this->thumbnailService->getVideoDuration($fullPath);
            if ($duration !== null) {
                $duration = (int) round($duration);
            }
        }

        // Create the media record
        $media = EventMedia::create([
            'event_id' => $event->id,
            'uploaded_by' => Auth::id(),
            'title' => $validated['title'] ?? $file->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $mimeType,
            'file_size' => $file->getSize(),
            'media_type' => $mediaType,
            'collection' => $validated['collection'] ?? EventMedia::COLLECTION_GALLERY,
            'is_featured' => $validated['is_featured'] ?? false,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
            'thumbnail_path' => $thumbnailPath,
        ]);

        // If set as featured, remove featured from other media
        if ($media->is_featured) {
            $media->setAsFeatured();
        }

        CacheService::forgetPattern('events');

        return response()->json([
            'message' => 'Média ajouté avec succès.',
            'media' => $media->fresh(),
        ], 201);
    }

    /**
     * Store media from TUS upload.
     */
    public function storeFromTus(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'filename' => 'required|string',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'collection' => 'nullable|string|in:banner,gallery',
            'is_featured' => 'nullable|boolean',
        ]);

        // The file has been uploaded via TUS to events/media directory
        $sourcePath = 'events/media/'.$validated['filename'];

        if (! Storage::disk('public')->exists($sourcePath)) {
            return response()->json([
                'message' => 'Fichier non trouvé.',
            ], 404);
        }

        // Move to event-specific directory
        $newPath = 'events/media/'.$event->id.'/'.$validated['filename'];
        Storage::disk('public')->move($sourcePath, $newPath);

        $fullPath = Storage::disk('public')->path($newPath);
        $mimeType = mime_content_type($fullPath);
        $mediaType = EventMedia::getMediaTypeFromMime($mimeType);

        if (! $mediaType) {
            Storage::disk('public')->delete($newPath);

            return response()->json([
                'message' => 'Type de fichier non supporté.',
            ], 422);
        }

        // Get image dimensions if it's an image, or video metadata
        $width = null;
        $height = null;
        $duration = null;
        $thumbnailPath = null;

        if ($mediaType === EventMedia::TYPE_IMAGE) {
            $imageInfo = getimagesize($fullPath);
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        } elseif ($mediaType === EventMedia::TYPE_VIDEO) {
            // Generate thumbnail for video
            $thumbnailPath = $this->thumbnailService->generate($newPath);

            // Get video duration
            $duration = $this->thumbnailService->getVideoDuration($fullPath);
            if ($duration !== null) {
                $duration = (int) round($duration);
            }
        }

        // Create the media record
        $media = EventMedia::create([
            'event_id' => $event->id,
            'uploaded_by' => Auth::id(),
            'title' => $validated['title'] ?? $validated['filename'],
            'description' => $validated['description'] ?? null,
            'file_path' => $newPath,
            'file_name' => $validated['filename'],
            'file_type' => $mimeType,
            'file_size' => Storage::disk('public')->size($newPath),
            'media_type' => $mediaType,
            'collection' => $validated['collection'] ?? EventMedia::COLLECTION_GALLERY,
            'is_featured' => $validated['is_featured'] ?? false,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
            'thumbnail_path' => $thumbnailPath,
        ]);

        // If set as featured, remove featured from other media
        if ($media->is_featured) {
            $media->setAsFeatured();
        }

        CacheService::forgetPattern('events');

        return response()->json([
            'message' => 'Média ajouté avec succès.',
            'media' => $media->fresh(),
        ], 201);
    }

    /**
     * Update the specified media.
     */
    public function update(Request $request, Event $event, EventMedia $media): JsonResponse
    {
        if ($media->event_id !== $event->id) {
            return response()->json(['message' => 'Média non trouvé.'], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'collection' => 'nullable|string|in:banner,gallery',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $media->update($validated);

        // If set as featured, remove featured from other media
        if ($media->is_featured) {
            $media->setAsFeatured();
        }

        CacheService::forgetPattern('events');

        return response()->json([
            'message' => 'Média mis à jour avec succès.',
            'media' => $media->fresh(),
        ]);
    }

    /**
     * Remove the specified media.
     */
    public function destroy(Event $event, EventMedia $media): JsonResponse
    {
        if ($media->event_id !== $event->id) {
            return response()->json(['message' => 'Média non trouvé.'], 404);
        }

        // Delete the file
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        // Delete thumbnail if exists
        if ($media->thumbnail_path && Storage::disk('public')->exists($media->thumbnail_path)) {
            Storage::disk('public')->delete($media->thumbnail_path);
        }

        $media->delete();

        CacheService::forgetPattern('events');

        return response()->json([
            'message' => 'Média supprimé avec succès.',
        ]);
    }

    /**
     * Reorder media items.
     */
    public function reorder(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'integer|exists:event_media,id',
        ]);

        foreach ($validated['media_ids'] as $order => $mediaId) {
            EventMedia::where('id', $mediaId)
                ->where('event_id', $event->id)
                ->update(['sort_order' => $order]);
        }

        CacheService::forgetPattern('events');

        return response()->json([
            'message' => 'Ordre des médias mis à jour.',
        ]);
    }

    /**
     * Set a media item as the event banner.
     */
    public function setBanner(Event $event, EventMedia $media): JsonResponse
    {
        if ($media->event_id !== $event->id) {
            return response()->json(['message' => 'Média non trouvé.'], 404);
        }

        // Remove banner collection from other media
        EventMedia::where('event_id', $event->id)
            ->where('collection', EventMedia::COLLECTION_BANNER)
            ->where('id', '!=', $media->id)
            ->update(['collection' => EventMedia::COLLECTION_GALLERY]);

        $media->update(['collection' => EventMedia::COLLECTION_BANNER]);

        CacheService::forgetPattern('events');

        return response()->json([
            'message' => 'Banner défini avec succès.',
            'media' => $media->fresh(),
        ]);
    }

    /**
     * Set a media item as featured.
     */
    public function setFeatured(Event $event, EventMedia $media): JsonResponse
    {
        if ($media->event_id !== $event->id) {
            return response()->json(['message' => 'Média non trouvé.'], 404);
        }

        $media->setAsFeatured();

        CacheService::forgetPattern('events');

        return response()->json([
            'message' => 'Média mis en avant avec succès.',
            'media' => $media->fresh(),
        ]);
    }
}
