<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interest;
use App\Models\ProfileSkill;
use App\Models\SpokenLanguage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Update user's spoken languages.
     */
    public function updateLanguages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'languages' => ['present', 'array'],
            'languages.*.id' => ['required', 'exists:spoken_languages,id'],
            'languages.*.level' => ['required', 'in:beginner,intermediate,advanced,native'],
        ], [
            'languages.present' => 'Le champ langues est obligatoire.',
            'languages.*.id.required' => 'L\'identifiant de la langue est obligatoire.',
            'languages.*.id.exists' => 'La langue sélectionnée n\'existe pas.',
            'languages.*.level.required' => 'Le niveau de langue est obligatoire.',
            'languages.*.level.in' => 'Le niveau de langue doit être: débutant, intermédiaire, avancé ou natif.',
        ]);

        $user = $request->user();

        // Build the sync array with pivot data
        $syncData = [];
        foreach ($validated['languages'] as $language) {
            $syncData[$language['id']] = ['level' => $language['level']];
        }

        $user->spokenLanguages()->sync($syncData);

        return response()->json([
            'message' => 'Langues mises à jour avec succès.',
            'languages' => $user->spokenLanguages()->get()->map(fn ($lang): array => [
                'id' => $lang->id,
                'uuid' => $lang->uuid,
                'name' => $lang->name,
                'code' => $lang->code,
                'native_name' => $lang->native_name,
                'level' => $lang->pivot->level,
            ]),
        ]);
    }

    /**
     * Update user's interests.
     */
    public function updateInterests(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'interests' => ['present', 'array'],
            'interests.*' => ['exists:interests,id'],
        ], [
            'interests.present' => 'Le champ centres d\'intérêt est obligatoire.',
            'interests.*.exists' => 'Le centre d\'intérêt sélectionné n\'existe pas.',
        ]);

        $user = $request->user();
        $user->interests()->sync($validated['interests']);

        return response()->json([
            'message' => 'Centres d\'intérêt mis à jour avec succès.',
            'interests' => $user->interests()->get()->map(fn ($interest): array => [
                'id' => $interest->id,
                'uuid' => $interest->uuid,
                'name' => $interest->name,
                'icon' => $interest->icon,
            ]),
        ]);
    }

    /**
     * Update user's skills.
     */
    public function updateSkills(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'skills' => ['present', 'array'],
            'skills.*.id' => ['required', 'exists:profile_skills,id'],
            'skills.*.level' => ['nullable', 'in:beginner,intermediate,advanced,expert'],
        ], [
            'skills.present' => 'Le champ compétences est obligatoire.',
            'skills.*.id.required' => 'L\'identifiant de la compétence est obligatoire.',
            'skills.*.id.exists' => 'La compétence sélectionnée n\'existe pas.',
            'skills.*.level.in' => 'Le niveau de compétence doit être: débutant, intermédiaire, avancé ou expert.',
        ]);

        $user = $request->user();

        // Build the sync array with pivot data
        $syncData = [];
        foreach ($validated['skills'] as $skill) {
            $syncData[$skill['id']] = ['level' => $skill['level'] ?? null];
        }

        $user->profileSkills()->sync($syncData);

        return response()->json([
            'message' => 'Compétences mises à jour avec succès.',
            'skills' => $user->profileSkills()->get()->map(fn ($skill): array => [
                'id' => $skill->id,
                'uuid' => $skill->uuid,
                'name' => $skill->name,
                'category' => $skill->category,
                'level' => $skill->pivot->level,
            ]),
        ]);
    }

    /**
     * Get all available languages.
     */
    public function getLanguages(): JsonResponse
    {
        return response()->json([
            'languages' => SpokenLanguage::orderBy('name')->get()->map(fn ($lang): array => [
                'id' => $lang->id,
                'uuid' => $lang->uuid,
                'name' => $lang->name,
                'code' => $lang->code,
                'native_name' => $lang->native_name,
            ]),
        ]);
    }

    /**
     * Get all available interests.
     */
    public function getInterests(): JsonResponse
    {
        return response()->json([
            'interests' => Interest::orderBy('name')->get()->map(fn ($interest): array => [
                'id' => $interest->id,
                'uuid' => $interest->uuid,
                'name' => $interest->name,
                'icon' => $interest->icon,
            ]),
        ]);
    }

    /**
     * Get all available skills.
     */
    public function getSkills(): JsonResponse
    {
        return response()->json([
            'skills' => ProfileSkill::orderBy('category')->orderBy('name')->get()->map(fn ($skill): array => [
                'id' => $skill->id,
                'uuid' => $skill->uuid,
                'name' => $skill->name,
                'category' => $skill->category,
            ]),
        ]);
    }

    /**
     * Create a new custom interest.
     */
    public function createInterest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:interests,name'],
        ], [
            'name.required' => 'Le nom du centre d\'intérêt est obligatoire.',
            'name.unique' => 'Ce centre d\'intérêt existe déjà.',
        ]);

        $interest = Interest::create($validated);

        return response()->json([
            'message' => 'Centre d\'intérêt créé avec succès.',
            'interest' => [
                'id' => $interest->id,
                'uuid' => $interest->uuid,
                'name' => $interest->name,
                'icon' => $interest->icon,
            ],
        ], 201);
    }

    /**
     * Create a new custom skill.
     */
    public function createSkill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:soft,hard,technical'],
        ], [
            'name.required' => 'Le nom de la compétence est obligatoire.',
            'category.required' => 'La catégorie est obligatoire.',
            'category.in' => 'La catégorie doit être: soft, hard ou technical.',
        ]);

        // Check for unique name+category combination
        $exists = ProfileSkill::where('name', $validated['name'])
            ->where('category', $validated['category'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cette compétence existe déjà dans cette catégorie.',
                'errors' => ['name' => ['Cette compétence existe déjà dans cette catégorie.']],
            ], 422);
        }

        $skill = ProfileSkill::create($validated);

        return response()->json([
            'message' => 'Compétence créée avec succès.',
            'skill' => [
                'id' => $skill->id,
                'uuid' => $skill->uuid,
                'name' => $skill->name,
                'category' => $skill->category,
            ],
        ], 201);
    }

    /**
     * Get user's privacy settings.
     */
    public function getPrivacySettings(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'privacy_settings' => $user->getPrivacySettings(),
            'default_settings' => User::DEFAULT_PRIVACY_SETTINGS,
        ]);
    }

    /**
     * Update user's privacy settings.
     */
    public function updatePrivacySettings(Request $request): JsonResponse
    {
        $validFields = array_keys(User::DEFAULT_PRIVACY_SETTINGS);

        $validated = $request->validate([
            'privacy_settings' => ['required', 'array'],
            'privacy_settings.*' => ['boolean'],
        ], [
            'privacy_settings.required' => 'Les paramètres de confidentialité sont obligatoires.',
            'privacy_settings.array' => 'Les paramètres de confidentialité doivent être un tableau.',
            'privacy_settings.*.boolean' => 'Chaque paramètre de confidentialité doit être un booléen.',
        ]);

        // Filter to only allow valid fields
        $settings = collect($validated['privacy_settings'])
            ->only($validFields)
            ->toArray();

        $user = $request->user();
        $user->update(['privacy_settings' => $settings]);

        return response()->json([
            'message' => 'Paramètres de confidentialité mis à jour avec succès.',
            'privacy_settings' => $user->getPrivacySettings(),
        ]);
    }
}
