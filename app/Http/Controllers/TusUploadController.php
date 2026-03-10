<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use TusPhp\Tus\Server as TusServer;

class TusUploadController extends Controller
{
    /**
     * Handle TUS protocol upload requests
     */
    public function __invoke(Request $request)
    {
        try {
            // Configure TUS server with file cache
            $server = new TusServer('file');

            // Set upload directory
            $uploadDir = storage_path('app/uploads/tus');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $server->setUploadDir($uploadDir);

            // Set API path
            $server->setApiPath('/api/files');

            // Set cache directory (same as upload for simplicity)
            $cacheDir = storage_path('app/uploads/tus');
            $server->setCache([
                'name' => 'file',
                'directory' => $cacheDir,
            ]);

            // After successful upload, move file to appropriate location
            $server->event()->addListener('tus-server.upload.complete', function ($event): void {
                $filePath = $event->getFile()->getFilePath();
                $fileName = basename($filePath); // Extract filename from path

                // Extract metadata from file cache
                $cache = $event->getFile()->details();
                $metadata = $cache['metadata'] ?? [];
                $fileKey = $cache['name'] ?? $fileName;

                // Get the upload type from metadata (e.g., 'event_image', 'article_document', etc.)
                $uploadType = $metadata['upload_type'] ?? 'general';

                // Move to permanent storage based on type
                $destinationPath = match($uploadType) {
                    'event_image' => 'events',
                    'event_avatar' => 'events/avatars',
                    'article_image' => 'articles/images',
                    'article_document' => 'articles/documents',
                    'book_cover' => 'books/covers',
                    'message_attachment' => 'messages/attachments',
                    'training_image' => 'trainings',
                    'profile_avatar' => 'avatars',
                    'task_image' => 'tasks',
                    'project_image' => 'projects',
                    'department_image' => 'departments',
                    'group_image' => 'groups',
                    'stock_image' => 'stocks',
                    'library_image' => 'libraries',
                    default => 'uploads',
                };

                // Move file from TUS upload dir to permanent storage in public disk
                $contents = file_get_contents($filePath);
                // Use putFileAs to control the filename
                Storage::disk('public')->put($destinationPath . '/' . $fileName, $contents);
                $storedPath = $destinationPath . '/' . $fileName;

                // Clean up TUS upload file
                @unlink($filePath);

                // Log successful upload
                \Log::info('File uploaded successfully', [
                    'file' => $fileName,
                    'type' => $uploadType,
                    'path' => $storedPath,
                    'key' => $fileKey,
                ]);
            });

            // Handle the request
            $response = $server->serve();

            return $response->send();
        } catch (\Exception $e) {
            \Log::error('TUS upload error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get upload metadata
     */
    public function metadata(Request $request, string $fileId)
    {
        $server = new TusServer('file');
        $uploadDir = storage_path('app/uploads/tus');
        $server->setUploadDir($uploadDir);
        $server->setApiPath('/api/files');

        // Set cache directory
        $server->setCache([
            'name' => 'file',
            'directory' => $uploadDir,
        ]);

        try {
            $file = $server->getCache()->get($fileId);
            return response()->json([
                'name' => $file['file_name'] ?? null,
                'size' => $file['file_size'] ?? null,
                'offset' => $file['offset'] ?? 0,
                'metadata' => $file['metadata'] ?? [],
            ]);
        } catch (\Exception) {
            return response()->json(['error' => 'File not found'], 404);
        }
    }
}
