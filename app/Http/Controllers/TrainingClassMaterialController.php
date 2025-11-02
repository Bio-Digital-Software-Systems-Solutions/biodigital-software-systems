<?php

namespace App\Http\Controllers;

use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrainingClassMaterialController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of materials for a specific class (Teacher view).
     */
    public function index(Request $request, TrainingClass $trainingClass)
    {
        $this->authorize('viewAny', [TrainingClassMaterial::class, $trainingClass]);

        // Load the training relationship
        $trainingClass->load('training:id,title');

        $materials = $trainingClass->materials()
            ->with('uploadedBy:id,first_name,last_name')
            ->ordered()
            ->get();

        // If AJAX request from axios (not Inertia navigation), return JSON
        if ($request->ajax() && !$request->header('X-Inertia')) {
            return response()->json([
                'materials' => $materials,
                'class' => $trainingClass,
            ]);
        }

        // For Inertia navigation, return Inertia page
        return Inertia::render('Training/ClassMaterials', [
            'trainingClass' => [
                'id' => $trainingClass->id,
                'uuid' => $trainingClass->uuid,
                'name' => $trainingClass->name,
                'training' => $trainingClass->training ? [
                    'id' => $trainingClass->training->id,
                    'title' => $trainingClass->training->title,
                ] : null,
            ],
        ]);
    }

    /**
     * Store a newly created material for a class.
     */
    public function store(Request $request, TrainingClass $trainingClass)
    {
        $this->authorize('create', [TrainingClassMaterial::class, $trainingClass]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:pdf,video,audio,powerpoint,document',
            'file' => 'nullable|file|mimes:pdf,mp4,mp3,wav,ppt,pptx,doc,docx|max:102400', // 100MB
            'url' => 'nullable|url|max:500',
            'duration' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Ensure either file or URL is provided
        if (!$request->hasFile('file') && !$request->filled('url')) {
            return back()->withErrors(['file' => 'Vous devez fournir soit un fichier, soit une URL.']);
        }

        $filePath = null;

        // Handle file upload
        if ($request->hasFile('file')) {
            try {
                $uploadedPath = $this->fileUploadService->uploadFile(
                    $request->file('file'),
                    "class-materials/{$trainingClass->id}"
                );
                $filePath = $uploadedPath;
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['file' => $e->getMessage()]);
            }
        }

        // Get the next order if not provided
        if (!isset($validated['order'])) {
            $validated['order'] = $trainingClass->materials()->max('order') + 1;
        }

        $material = $trainingClass->materials()->create([
            'teacher_id' => $request->user()->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'file_path' => $filePath,
            'url' => $validated['url'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Support de cours ajouté avec succès.');
    }

    /**
     * Display the specified material.
     */
    public function show(TrainingClassMaterial $material)
    {
        $this->authorize('view', $material);

        $material->load(['trainingClass.training', 'uploadedBy:id,first_name,last_name']);

        return response()->json($material);
    }

    /**
     * Update the specified material.
     */
    public function update(Request $request, TrainingClass $trainingClass, TrainingClassMaterial $material)
    {
        $this->authorize('update', $material);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:pdf,video,audio,powerpoint,document',
            'file' => 'nullable|file|mimes:pdf,mp4,mp3,wav,ppt,pptx,doc,docx|max:102400',
            'url' => 'nullable|url|max:500',
            'duration' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle file replacement
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($material->file_path) {
                Storage::disk('public')->delete($material->file_path);
            }

            try {
                $uploadedPath = $this->fileUploadService->uploadFile(
                    $request->file('file'),
                    "class-materials/{$material->training_class_id}"
                );
                $validated['file_path'] = $uploadedPath;
                $validated['url'] = null; // Clear URL if uploading new file
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['file' => $e->getMessage()]);
            }
        }

        $material->update($validated);

        return back()->with('success', 'Support de cours mis à jour avec succès.');
    }

    /**
     * Remove the specified material.
     */
    public function destroy(TrainingClass $trainingClass, TrainingClassMaterial $material)
    {
        $this->authorize('delete', $material);

        // Delete associated file if exists
        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }

        $material->delete();

        return back()->with('success', 'Support de cours supprimé avec succès.');
    }

    /**
     * Display materials for students of a specific class.
     */
    public function studentIndex(TrainingClass $trainingClass)
    {
        $this->authorize('viewAny', [TrainingClassMaterial::class, $trainingClass]);

        $materials = $trainingClass->materials()
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'materials' => $materials,
            'class' => $trainingClass->load('training:id,title'),
        ]);
    }

    /**
     * Download or stream a material file.
     */
    public function download(TrainingClassMaterial $material)
    {
        $this->authorize('download', $material);

        // If it's an external URL, redirect to it
        if ($material->url && !$material->file_path) {
            return redirect($material->url);
        }

        // If it's a file, download it
        if ($material->file_path) {
            $path = Storage::disk('public')->path($material->file_path);

            if (!file_exists($path)) {
                abort(404, 'Fichier non trouvé.');
            }

            // Determine if we should stream or download based on file type
            $streamableTypes = ['video', 'audio', 'pdf'];
            $shouldStream = in_array($material->type, $streamableTypes);

            if ($shouldStream) {
                return response()->file($path);
            }

            return response()->download($path, $material->title);
        }

        abort(404, 'Aucun fichier ou URL disponible.');
    }

    /**
     * Reorder materials for a class.
     */
    public function reorder(Request $request, TrainingClass $trainingClass)
    {
        $this->authorize('create', [TrainingClassMaterial::class, $trainingClass]);

        $validated = $request->validate([
            'materials' => 'required|array',
            'materials.*.id' => 'required|exists:training_class_materials,id',
            'materials.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['materials'] as $materialData) {
            TrainingClassMaterial::where('id', $materialData['id'])
                ->where('training_class_id', $trainingClass->id)
                ->update(['order' => $materialData['order']]);
        }

        return back()->with('success', 'Ordre des supports mis à jour avec succès.');
    }
}
