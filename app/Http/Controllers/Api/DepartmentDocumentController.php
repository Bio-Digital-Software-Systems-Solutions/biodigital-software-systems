<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\DepartmentDocumentCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class DepartmentDocumentController extends Controller
{
    /**
     * Allowed file extensions and their MIME types.
     */
    private array $allowedExtensions = [
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt', 'ods', 'odp',
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp',
        // Videos
        'mp4', 'webm', 'mov', 'avi', 'mkv',
        // Audio
        'mp3', 'wav', 'ogg', 'm4a',
        // Archives
        'zip', 'rar', '7z',
    ];

    /**
     * Display a listing of documents for a department as a tree structure.
     */
    public function index(Department $department): JsonResponse
    {
        $tree = DepartmentDocument::getTreeForDepartment($department->id);

        return response()->json([
            'success' => true,
            'data' => $tree,
            'total_documents' => $department->documents()->count(),
        ]);
    }

    /**
     * Get documents for a specific year.
     */
    public function byYear(Department $department, int $year): JsonResponse
    {
        $documents = $department->documents()
            ->byYear($year)
            ->with('uploader:id,first_name,last_name')
            ->orderBy('month', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($doc) => $this->formatDocument($doc));

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Get documents for a specific month.
     */
    public function byMonth(Department $department, int $year, int $month): JsonResponse
    {
        $documents = $department->documents()
            ->byMonth($year, $month)
            ->with('uploader:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($doc) => $this->formatDocument($doc));

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Search documents by title, original name, description, or category.
     */
    public function search(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:255',
        ]);

        $searchTerm = $validated['q'];

        $documents = $department->documents()
            ->where(function ($query) use ($searchTerm) {
                $query->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('original_name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('category', 'like', "%{$searchTerm}%");
            })
            ->with('uploader:id,first_name,last_name')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($doc) => $this->formatDocument($doc));

        return response()->json([
            'success' => true,
            'data' => $documents,
            'total' => $documents->count(),
            'search_term' => $searchTerm,
        ]);
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200', // 50MB max
                File::types($this->allowedExtensions),
            ],
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            // Generate unique filename
            $fileName = Str::uuid() . '.' . $extension;

            // Determine year and month (use provided values or current date)
            $year = $validated['year'] ?? now()->year;
            $month = $validated['month'] ?? now()->month;

            // Determine category - if not provided, leave null (document will appear directly under month)
            $category = !empty($validated['category']) ? $validated['category'] : null;

            // Store file in organized folder structure: department_documents/{department_id}/{year}/{month}/
            $path = "department_documents/{$department->id}/{$year}/{$month}";
            $filePath = $file->storeAs($path, $fileName, 'public');

            // Create document record
            $document = DepartmentDocument::create([
                'department_id' => $department->id,
                'uploaded_by' => auth()->id(),
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'extension' => $extension,
                'year' => $year,
                'month' => $month,
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'category' => $category,
            ]);

            $document->load('uploader:id,first_name,last_name');

            return response()->json([
                'success' => true,
                'message' => 'Document téléchargé avec succès.',
                'data' => $this->formatDocument($document),
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Document upload error', [
                'message' => $e->getMessage(),
                'department_id' => $department->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du document.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified document.
     */
    public function show(Department $department, DepartmentDocument $document): JsonResponse
    {
        if ($document->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce document n\'appartient pas à ce département.',
            ], 404);
        }

        $document->load('uploader:id,first_name,last_name');

        return response()->json([
            'success' => true,
            'data' => $this->formatDocument($document),
        ]);
    }

    /**
     * Update the specified document metadata.
     */
    public function update(Request $request, Department $department, DepartmentDocument $document): JsonResponse
    {
        if ($document->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce document n\'appartient pas à ce département.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
        ]);

        $document->update($validated);
        $document->load('uploader:id,first_name,last_name');

        return response()->json([
            'success' => true,
            'message' => 'Document mis à jour avec succès.',
            'data' => $this->formatDocument($document),
        ]);
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Department $department, DepartmentDocument $document): JsonResponse
    {
        if ($document->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce document n\'appartient pas à ce département.',
            ], 404);
        }

        try {
            // Delete the file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Soft delete the record
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès.',
            ]);

        } catch (\Exception $e) {
            \Log::error('Document deletion error', [
                'message' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du document.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download the specified document.
     */
    public function download(Department $department, DepartmentDocument $document)
    {
        if ($document->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce document n\'appartient pas à ce département.',
            ], 404);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier n\'existe plus.',
            ], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    /**
     * Stream the document for preview (inline display).
     */
    public function preview(Department $department, DepartmentDocument $document)
    {
        if ($document->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce document n\'appartient pas à ce département.',
            ], 404);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier n\'existe plus.',
            ], 404);
        }

        // Get the file
        $file = Storage::disk('public')->get($document->file_path);
        $mimeType = $document->mime_type;

        // Return file with inline disposition for preview
        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"')
            ->header('Accept-Ranges', 'bytes')
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Format document for API response.
     */
    private function formatDocument(DepartmentDocument $document): array
    {
        return [
            'uuid' => $document->uuid,
            'title' => $document->title ?? $document->original_name,
            'original_name' => $document->original_name,
            'file_name' => $document->file_name,
            'file_url' => $document->file_url,
            'preview_url' => $document->preview_url,
            'file_size' => $document->file_size,
            'formatted_file_size' => $document->formatted_file_size,
            'mime_type' => $document->mime_type,
            'extension' => $document->extension,
            'file_type' => $document->file_type,
            'can_preview' => $document->can_preview,
            'preview_type' => $document->preview_type,
            'year' => $document->year,
            'month' => $document->month,
            'month_name' => $document->month_name,
            'description' => $document->description,
            'category' => $document->category,
            'created_at' => $document->created_at->toISOString(),
            'updated_at' => $document->updated_at->toISOString(),
            'uploader' => $document->uploader ? [
                'id' => $document->uploader->id,
                'name' => $document->uploader->first_name . ' ' . $document->uploader->last_name,
            ] : null,
        ];
    }
}
