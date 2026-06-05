<?php

namespace App\Http\Controllers;

use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
use App\Models\TrainingMaterial;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TrainingClassMaterialController extends Controller
{
    public function __construct(protected FileUploadService $fileUploadService) {}

    /**
     * List materials attached to a class (teacher view — includes inactive).
     */
    public function index(Request $request, TrainingClass $trainingClass)
    {
        $this->authorize('viewAny', [TrainingClassMaterial::class, $trainingClass]);

        $trainingClass->load('training:id,title');

        $materials = $trainingClass->materials()
            ->with('uploadedBy:id,first_name,last_name')
            ->get()
            ->map(fn (TrainingMaterial $material) => $this->presentMaterial($material));

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'materials' => $materials,
                'class' => $trainingClass,
            ]);
        }

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
     * Create a new TrainingMaterial under the class's training and attach it
     * to this class via the pivot. The same operation is the natural "add"
     * flow from the UI; future flows may attach an existing TrainingMaterial.
     */
    public function store(Request $request, TrainingClass $trainingClass)
    {
        $this->authorize('create', [TrainingClassMaterial::class, $trainingClass]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:pdf,video,audio,powerpoint,document',
            'file' => 'nullable|file|mimes:pdf,mp4,mp3,wav,ppt,pptx,doc,docx|max:102400',
            'url' => 'nullable|url|max:500',
            'duration' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if (! $request->hasFile('file') && ! $request->filled('url')) {
            return back()->withErrors(['file' => 'Vous devez fournir soit un fichier, soit une URL.']);
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            try {
                $filePath = $this->fileUploadService->uploadFile(
                    $request->file('file'),
                    "training-materials/{$trainingClass->training_id}"
                );
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['file' => $e->getMessage()]);
            }
        }

        DB::transaction(function () use ($trainingClass, $validated, $filePath, $request): void {
            $material = TrainingMaterial::create([
                'training_id' => $trainingClass->training_id,
                'teacher_id' => $request->user()->id,
                'title' => $validated['title'],
                'type' => $validated['type'],
                'file_path' => $filePath,
                'url' => $validated['url'] ?? null,
                'duration' => $validated['duration'] ?? null,
                'description' => $validated['description'] ?? null,
                'order' => $validated['order'] ?? 0,
                'is_active' => true,
            ]);

            $nextOrder = (int) $trainingClass->materialPivots()->max('order') + 1;

            TrainingClassMaterial::create([
                'training_class_id' => $trainingClass->id,
                'training_material_id' => $material->id,
                'teacher_id' => $request->user()->id,
                'is_active' => $validated['is_active'] ?? true,
                'order' => $validated['order'] ?? $nextOrder,
            ]);
        });

        return back()->with('success', 'Support de cours ajouté avec succès.');
    }

    public function show(TrainingClassMaterial $material)
    {
        $this->authorize('view', $material);

        $material->load(['trainingClass.training', 'material.uploadedBy:id,first_name,last_name']);

        return response()->json($this->presentPivot($material));
    }

    /**
     * Update splits two concerns:
     *   - content fields (title/type/file/url/duration/description) update
     *     the shared TrainingMaterial — it affects every class using it;
     *   - per-class fields (is_active, order) update the pivot only.
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

        $contentFields = array_intersect_key($validated, array_flip([
            'title', 'type', 'url', 'duration', 'description',
        ]));

        if ($request->hasFile('file')) {
            if ($material->material->file_path) {
                Storage::disk('public')->delete($material->material->file_path);
            }

            try {
                $contentFields['file_path'] = $this->fileUploadService->uploadFile(
                    $request->file('file'),
                    "training-materials/{$material->material->training_id}"
                );
                $contentFields['url'] = null;
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['file' => $e->getMessage()]);
            }
        }

        DB::transaction(function () use ($material, $contentFields, $validated): void {
            if (! empty($contentFields)) {
                $material->material->update($contentFields);
            }

            $pivotFields = array_intersect_key($validated, array_flip(['is_active', 'order']));
            if (! empty($pivotFields)) {
                $material->update($pivotFields);
            }
        });

        return back()->with('success', 'Support de cours mis à jour avec succès.');
    }

    /**
     * Detach the material from this class. The underlying TrainingMaterial
     * stays available for other classes.
     */
    public function destroy(TrainingClass $trainingClass, TrainingClassMaterial $material)
    {
        $this->authorize('delete', $material);

        $material->delete();

        return back()->with('success', 'Support de cours retiré de cette classe.');
    }

    /**
     * Student view — only materials whose pivot is active.
     */
    public function studentIndex(TrainingClass $trainingClass)
    {
        $this->authorize('viewAny', [TrainingClassMaterial::class, $trainingClass]);

        $materials = $trainingClass->activeMaterials()
            ->get()
            ->map(fn (TrainingMaterial $material) => $this->presentMaterial($material));

        return response()->json([
            'materials' => $materials,
            'class' => $trainingClass->load('training:id,title'),
        ]);
    }

    public function download(TrainingClassMaterial $material)
    {
        $this->authorize('download', $material);

        $content = $material->material;

        if ($content->url && ! $content->file_path) {
            return redirect($content->url);
        }

        if ($content->file_path) {
            $path = Storage::disk('public')->path($content->file_path);

            if (! file_exists($path)) {
                abort(404, 'Fichier non trouvé.');
            }

            $streamableTypes = ['video', 'audio', 'pdf'];
            if (in_array($content->type, $streamableTypes, true)) {
                return response()->file($path);
            }

            return response()->download($path, $content->title);
        }

        abort(404, 'Aucun fichier ou URL disponible.');
    }

    /**
     * Reorder pivot rows for a class. The order is per-class.
     */
    public function reorder(Request $request, TrainingClass $trainingClass)
    {
        $this->authorize('create', [TrainingClassMaterial::class, $trainingClass]);

        $validated = $request->validate([
            'materials' => 'required|array',
            'materials.*.id' => 'required|exists:training_class_materials,id',
            'materials.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['materials'] as $row) {
            TrainingClassMaterial::where('id', $row['id'])
                ->where('training_class_id', $trainingClass->id)
                ->update(['order' => $row['order']]);
        }

        return back()->with('success', 'Ordre des supports mis à jour avec succès.');
    }

    /**
     * Toggle the per-class visibility (pivot is_active) without touching
     * any other class that uses the same TrainingMaterial.
     */
    public function toggleActive(TrainingClass $trainingClass, TrainingClassMaterial $material)
    {
        $this->authorize('update', $material);

        $material->update(['is_active' => ! $material->is_active]);

        return back()->with('success', 'Visibilité du support mise à jour pour cette classe.');
    }

    /**
     * Attach an existing TrainingMaterial (from the parent training catalogue)
     * to this class. Idempotent thanks to the (class, material) unique index.
     */
    public function attach(Request $request, TrainingClass $trainingClass)
    {
        $this->authorize('create', [TrainingClassMaterial::class, $trainingClass]);

        $validated = $request->validate([
            'training_material_id' => 'required|integer|exists:training_materials,id',
            'is_active' => 'nullable|boolean',
        ]);

        $material = TrainingMaterial::findOrFail($validated['training_material_id']);

        abort_unless(
            $material->training_id === $trainingClass->training_id,
            422,
            'Ce support n\'appartient pas à la formation de cette classe.'
        );

        $nextOrder = (int) $trainingClass->materialPivots()->max('order') + 1;

        TrainingClassMaterial::firstOrCreate(
            [
                'training_class_id' => $trainingClass->id,
                'training_material_id' => $material->id,
            ],
            [
                'teacher_id' => $request->user()->id,
                'is_active' => $validated['is_active'] ?? true,
                'order' => $nextOrder,
            ]
        );

        return back()->with('success', 'Support attaché à la classe.');
    }

    /**
     * Flatten a TrainingMaterial + its pivot to the legacy frontend shape.
     */
    protected function presentMaterial(TrainingMaterial $material): array
    {
        $pivot = $material->pivot;

        return [
            'id' => $pivot->id,
            'uuid' => $pivot->uuid,
            'training_material_id' => $material->id,
            'training_material_uuid' => $material->uuid,
            'title' => $material->title,
            'type' => $material->type,
            'file_url' => $material->file_url,
            'url' => $material->url,
            'duration' => $material->duration,
            'description' => $material->description,
            'order' => (int) $pivot->order,
            'is_active' => (bool) $pivot->is_active,
            'uploaded_by' => $material->relationLoaded('uploadedBy') ? $material->uploadedBy : null,
        ];
    }

    /**
     * Flatten a pivot row (with material loaded) for single-resource responses.
     */
    protected function presentPivot(TrainingClassMaterial $pivot): array
    {
        $material = $pivot->material;
        $material->setRelation('pivot', $pivot);

        return $this->presentMaterial($material);
    }
}
