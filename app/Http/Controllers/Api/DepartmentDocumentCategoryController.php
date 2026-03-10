<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentDocumentCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DepartmentDocumentCategoryController extends Controller
{
    /**
     * List all categories for a department/year/month.
     * If year/month not provided, uses current date.
     */
    public function index(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = $validated['year'] ?? now()->year;
        $month = $validated['month'] ?? now()->month;

        $categories = DepartmentDocumentCategory::getCategoriesForMonth(
            $department->id,
            $year,
            $month
        );

        return response()->json([
            'success' => true,
            'data' => $categories->map(fn(\App\Models\DepartmentDocumentCategory $cat): array => $this->formatCategory($cat)),
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * Create a new category (subfolder).
     */
    public function store(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $slug = Str::slug($validated['name']);

        // Check if category already exists
        $existing = DepartmentDocumentCategory::where('department_id', $department->id)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Un sous-dossier avec ce nom existe déjà pour ce mois.',
            ], 422);
        }

        // Get max sort order for this month
        $maxSortOrder = DepartmentDocumentCategory::where('department_id', $department->id)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->max('sort_order') ?? 0;

        $category = DepartmentDocumentCategory::create([
            'department_id' => $department->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'year' => $validated['year'],
            'month' => $validated['month'],
            'is_system' => false,
            'sort_order' => $maxSortOrder + 1,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sous-dossier créé avec succès.',
            'data' => $this->formatCategory($category),
        ], 201);
    }

    /**
     * Update a category name.
     */
    public function update(Request $request, Department $department, DepartmentDocumentCategory $category): JsonResponse
    {
        if ($category->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette catégorie n\'appartient pas à ce département.',
            ], 404);
        }

        if ($category->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Les dossiers système ne peuvent pas être modifiés.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $newSlug = Str::slug($validated['name']);

        // Check if new slug would conflict with existing category
        $existing = DepartmentDocumentCategory::where('department_id', $department->id)
            ->where('year', $category->year)
            ->where('month', $category->month)
            ->where('slug', $newSlug)
            ->where('id', '!=', $category->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Un sous-dossier avec ce nom existe déjà pour ce mois.',
            ], 422);
        }

        $oldSlug = $category->slug;
        $category->update([
            'name' => $validated['name'],
            'slug' => $newSlug,
        ]);

        // Update documents with old category slug to new slug
        if ($oldSlug !== $newSlug) {
            \App\Models\DepartmentDocument::where('department_id', $department->id)
                ->where('year', $category->year)
                ->where('month', $category->month)
                ->where('category', $oldSlug)
                ->update(['category' => $newSlug]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sous-dossier mis à jour avec succès.',
            'data' => $this->formatCategory($category),
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy(Department $department, DepartmentDocumentCategory $category): JsonResponse
    {
        if ($category->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette catégorie n\'appartient pas à ce département.',
            ], 404);
        }

        if ($category->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Les dossiers système ne peuvent pas être supprimés.',
            ], 403);
        }

        // Check if there are documents in this category
        $documentCount = \App\Models\DepartmentDocument::where('department_id', $department->id)
            ->where('year', $category->year)
            ->where('month', $category->month)
            ->where('category', $category->slug)
            ->count();

        if ($documentCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Ce sous-dossier contient {$documentCount} document(s). Veuillez d'abord les déplacer ou les supprimer.",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sous-dossier supprimé avec succès.',
        ]);
    }

    /**
     * Format category for API response.
     */
    private function formatCategory(DepartmentDocumentCategory $category): array
    {
        return [
            'uuid' => $category->uuid,
            'name' => $category->name,
            'slug' => $category->slug,
            'key' => $category->slug,
            'year' => $category->year,
            'month' => $category->month,
            'month_name' => $category->month_name,
            'is_system' => $category->is_system,
            'sort_order' => $category->sort_order,
            'created_at' => $category->created_at?->toISOString(),
        ];
    }
}
