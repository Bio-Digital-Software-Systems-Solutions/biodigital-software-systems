<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHomepageSectionRequest;
use App\Http\Requests\StoreHomepageSubsectionRequest;
use App\Http\Requests\UpdateHomepageSectionRequest;
use App\Http\Requests\UpdateHomepageSubsectionRequest;
use App\Models\HomepageSection;
use App\Models\HomepageSubsection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomepageSectionController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = HomepageSection::with('subsections')
            ->orderBy('order')
            ->get();

        return response()->json(['sections' => $sections]);
    }

    public function store(StoreHomepageSectionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['order'] ??= (HomepageSection::max('order') ?? 0) + 1;
        $validated['is_active'] ??= true;
        $validated['key'] ??= $this->generateKey($validated['type']);
        $validated['content'] ??= [];
        $validated['design_settings'] ??= [];

        HomepageSection::create($validated);

        return redirect()->route('settings.homepage')
            ->with('success', 'Section ajoutée avec succès.');
    }

    public function update(UpdateHomepageSectionRequest $request, HomepageSection $homepageSection): RedirectResponse
    {
        $homepageSection->update($request->validated());

        return redirect()->route('settings.homepage')
            ->with('success', 'Section mise à jour avec succès.');
    }

    public function destroy(HomepageSection $homepageSection): RedirectResponse
    {
        $homepageSection->delete();

        return redirect()->route('settings.homepage')
            ->with('success', 'Section supprimée avec succès.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.id' => ['required', 'exists:homepage_sections,id'],
            'sections.*.order' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['sections'] as $row) {
                HomepageSection::where('id', $row['id'])->update(['order' => $row['order']]);
            }
        });

        return back()->with('success', 'Ordre des sections mis à jour.');
    }

    public function storeSubsection(
        StoreHomepageSubsectionRequest $request,
        HomepageSection $homepageSection
    ): RedirectResponse {
        $validated = $request->validated();

        $validated['homepage_section_id'] = $homepageSection->id;
        $validated['order'] ??= ($homepageSection->subsections()->max('order') ?? 0) + 1;
        $validated['is_active'] ??= true;
        $validated['design_settings'] ??= [];

        HomepageSubsection::create($validated);

        return redirect()->route('settings.homepage')
            ->with('success', 'Bloc ajouté avec succès.');
    }

    public function updateSubsection(
        UpdateHomepageSubsectionRequest $request,
        HomepageSection $homepageSection,
        HomepageSubsection $homepageSubsection
    ): RedirectResponse {
        $homepageSubsection->update($request->validated());

        return redirect()->route('settings.homepage')
            ->with('success', 'Bloc mis à jour avec succès.');
    }

    public function destroySubsection(
        HomepageSection $homepageSection,
        HomepageSubsection $homepageSubsection
    ): RedirectResponse {
        $homepageSubsection->delete();

        return redirect()->route('settings.homepage')
            ->with('success', 'Bloc supprimé avec succès.');
    }

    public function reorderSubsections(Request $request, HomepageSection $homepageSection): RedirectResponse
    {
        $validated = $request->validate([
            'subsections' => ['required', 'array'],
            'subsections.*.id' => ['required', 'exists:homepage_subsections,id'],
            'subsections.*.order' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $homepageSection): void {
            foreach ($validated['subsections'] as $row) {
                HomepageSubsection::where('id', $row['id'])
                    ->where('homepage_section_id', $homepageSection->id)
                    ->update(['order' => $row['order']]);
            }
        });

        return back()->with('success', 'Ordre des blocs mis à jour.');
    }

    private function generateKey(string $type): string
    {
        $base = $type === 'custom' ? 'custom-'.Str::random(6) : $type;

        $candidate = $base;
        $suffix = 2;
        while (HomepageSection::where('key', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}
