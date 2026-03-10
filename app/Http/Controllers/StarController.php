<?php

namespace App\Http\Controllers;

use App\Enums\Star\StarCategory;
use App\Enums\Star\StarStatus;
use App\Enums\Star\StarType;
use App\Models\Department;
use App\Models\Star;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StarController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view stars')->only(['index', 'show']);
        $this->middleware('can:manage stars')->except(['index', 'show']);
    }

    /**
     * Display a listing of stars.
     */
    public function index(Request $request): Response
    {
        $query = Star::with(['user', 'department', 'nominator'])
            ->when($request->filled('search'), function ($q) use ($request): void {
                $q->search($request->search);
            })
            ->when($request->filled('status'), function ($q) use ($request): void {
                $q->where('status', $request->status);
            })
            ->when($request->filled('type'), function ($q) use ($request): void {
                $q->where('type', $request->type);
            })
            ->when($request->filled('category'), function ($q) use ($request): void {
                $q->where('category', $request->category);
            })
            ->when($request->filled('department'), function ($q) use ($request): void {
                $q->where('department_id', $request->department);
            })
            ->when($request->filled('level'), function ($q) use ($request): void {
                $q->where('level', '>=', $request->level);
            })
            ->when($request->boolean('featured'), function ($q): void {
                $q->featured();
            })
            ->orderBy('is_featured', 'desc')
            ->orderBy('level', 'desc')
            ->orderBy('points', 'desc');

        $stars = $query->paginate(15)->withQueryString();

        // Transform stars for frontend
        $stars->getCollection()->transform(fn($star): array => [
            'id' => $star->id,
            'uuid' => $star->uuid,
            'star_number' => $star->star_number,
            'full_name' => $star->full_name,
            'title' => $star->title,
            'status' => [
                'value' => $star->status->value,
                'label' => $star->status->label(),
                'color' => $star->status->color(),
            ],
            'type' => [
                'value' => $star->type->value,
                'label' => $star->type->label(),
                'color' => $star->type->color(),
            ],
            'category' => $star->category ? [
                'value' => $star->category->value,
                'label' => $star->category->label(),
                'color' => $star->category->color(),
            ] : null,
            'department' => $star->department ? [
                'id' => $star->department->id,
                'uuid' => $star->department->uuid,
                'name' => $star->department->name,
            ] : null,
            'level' => $star->level,
            'level_title' => $star->level_title,
            'points' => $star->points,
            'total_hours_served' => $star->total_hours_served,
            'is_featured' => $star->is_featured,
            'is_public_profile' => $star->is_public_profile,
            'recognition_date' => $star->recognition_date?->format('Y-m-d'),
            'user' => $star->user ? [
                'id' => $star->user->id,
                'uuid' => $star->user->uuid,
                'name' => $star->user->full_name,
                'email' => $star->user->email,
                'avatar' => $star->user->avatar,
            ] : null,
            'avatar' => $star->avatar ? Storage::url($star->avatar) : null,
        ]);

        return Inertia::render('Stars/Index', [
            'stars' => $stars,
            'filters' => $request->only(['search', 'status', 'type', 'category', 'department', 'level', 'featured']),
            'statuses' => collect(StarStatus::cases())->map(fn($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'types' => collect(StarType::cases())->map(fn($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
            ]),
            'categories' => collect(StarCategory::cases())->map(fn($c): array => [
                'value' => $c->value,
                'label' => $c->label(),
                'color' => $c->color(),
            ]),
            'departments' => Department::active()->orderBy('name')->get(['id', 'uuid', 'name']),
            'stats' => [
                'total' => Star::count(),
                'active' => Star::active()->count(),
                'featured' => Star::featured()->count(),
                'new_this_month' => Star::where('recognition_date', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new star.
     */
    public function create(): Response
    {
        return Inertia::render('Stars/Create', [
            'users' => User::doesntHave('star')
                ->orderBy('first_name')
                ->get(['id', 'uuid', 'first_name', 'last_name', 'email']),
            'departments' => Department::active()->orderBy('name')->get(['id', 'uuid', 'name']),
            'nominators' => User::orderBy('first_name')->get(['id', 'uuid', 'first_name', 'last_name']),
            'statuses' => collect(StarStatus::cases())->map(fn($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'types' => collect(StarType::cases())->map(fn($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'categories' => collect(StarCategory::cases())->map(fn($c): array => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    /**
     * Store a newly created star.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:stars,user_id',
            'department_id' => 'nullable|exists:departments,id',
            'nominated_by' => 'nullable|exists:users,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => ['required', Rule::enum(StarStatus::class)],
            'type' => ['required', Rule::enum(StarType::class)],
            'category' => ['nullable', Rule::enum(StarCategory::class)],
            'points' => 'nullable|integer|min:0',
            'level' => 'nullable|integer|min:1|max:5',
            'recognition_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:recognition_date',
            'achievements' => 'nullable|array',
            'badges' => 'nullable|array',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'areas_of_service' => 'nullable|array',
            'areas_of_service.*' => 'string|max:100',
            'available_days' => 'nullable|array',
            'available_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'available_from' => 'nullable|date_format:H:i',
            'available_to' => 'nullable|date_format:H:i|after:available_from',
            'hours_per_week' => 'nullable|integer|min:0',
            'is_contactable' => 'boolean',
            'preferred_contact_method' => 'nullable|in:email,phone,sms',
            'receive_notifications' => 'boolean',
            'bio' => 'nullable|string|max:2000',
            'is_public_profile' => 'boolean',
            'is_featured' => 'boolean',
            'testimonial' => 'nullable|string|max:1000',
            'favorite_verse' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'internal_notes' => 'nullable|string|max:5000',
            'avatar' => 'nullable|image|max:2048',
            'cover_image' => 'nullable|image|max:4096',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('stars/avatars', 'public');
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('stars/covers', 'public');
        }

        // Set default recognition date if not provided
        if (empty($validated['recognition_date'])) {
            $validated['recognition_date'] = now();
        }

        $star = Star::create($validated);

        return redirect()
            ->route('stars.show', $star)
            ->with('success', 'Star créé avec succès.');
    }

    /**
     * Display the specified star.
     */
    public function show(Request $request, Star $star): Response
    {
        $star->load([
            'user',
            'department',
            'nominator',
        ]);

        return Inertia::render('Stars/Show', [
            'star' => [
                'id' => $star->id,
                'uuid' => $star->uuid,
                'star_number' => $star->star_number,
                'full_name' => $star->full_name,
                'title' => $star->title,
                'description' => $star->description,
                'status' => [
                    'value' => $star->status->value,
                    'label' => $star->status->label(),
                    'color' => $star->status->color(),
                ],
                'type' => [
                    'value' => $star->type->value,
                    'label' => $star->type->label(),
                    'color' => $star->type->color(),
                ],
                'category' => $star->category ? [
                    'value' => $star->category->value,
                    'label' => $star->category->label(),
                    'color' => $star->category->color(),
                ] : null,
                'points' => $star->points,
                'level' => $star->level,
                'level_title' => $star->level_title,
                'next_level_points' => $star->next_level_points,
                'progress_to_next_level' => $star->progress_to_next_level,
                'recognition_date' => $star->recognition_date?->format('Y-m-d'),
                'expiry_date' => $star->expiry_date?->format('Y-m-d'),
                'is_expired' => $star->is_expired,
                'days_until_expiry' => $star->days_until_expiry,
                'service_duration' => $star->service_duration,
                'achievements' => $star->achievements,
                'badges' => $star->badges,
                'skills' => $star->skills,
                'areas_of_service' => $star->areas_of_service,
                'available_days' => $star->available_days,
                'available_from' => $star->available_from,
                'available_to' => $star->available_to,
                'hours_per_week' => $star->hours_per_week,
                'total_hours_served' => $star->total_hours_served,
                'is_contactable' => $star->is_contactable,
                'preferred_contact_method' => $star->preferred_contact_method,
                'receive_notifications' => $star->receive_notifications,
                'bio' => $star->bio,
                'avatar' => $star->avatar ? Storage::url($star->avatar) : null,
                'cover_image' => $star->cover_image ? Storage::url($star->cover_image) : null,
                'is_public_profile' => $star->is_public_profile,
                'is_featured' => $star->is_featured,
                'display_order' => $star->display_order,
                'testimonial' => $star->testimonial,
                'favorite_verse' => $star->favorite_verse,
                'notes' => $star->notes,
                'internal_notes' => $star->internal_notes,
                'created_at' => $star->created_at->format('Y-m-d H:i'),
                'updated_at' => $star->updated_at->format('Y-m-d H:i'),
                'user' => $star->user ? [
                    'id' => $star->user->id,
                    'uuid' => $star->user->uuid,
                    'name' => $star->user->full_name,
                    'email' => $star->user->email,
                    'avatar' => $star->user->avatar,
                ] : null,
                'department' => $star->department ? [
                    'id' => $star->department->id,
                    'uuid' => $star->department->uuid,
                    'name' => $star->department->name,
                ] : null,
                'nominator' => $star->nominator ? [
                    'id' => $star->nominator->id,
                    'uuid' => $star->nominator->uuid,
                    'name' => $star->nominator->full_name,
                ] : null,
            ],
            'canManage' => $request->user()?->can('manage stars'),
        ]);
    }

    /**
     * Show the form for editing the specified star.
     */
    public function edit(Star $star): Response
    {
        $star->load(['user', 'department', 'nominator']);

        return Inertia::render('Stars/Edit', [
            'star' => [
                'id' => $star->id,
                'uuid' => $star->uuid,
                'user_id' => $star->user_id,
                'department_id' => $star->department_id,
                'nominated_by' => $star->nominated_by,
                'star_number' => $star->star_number,
                'title' => $star->title,
                'description' => $star->description,
                'status' => $star->status->value,
                'type' => $star->type->value,
                'category' => $star->category?->value,
                'points' => $star->points,
                'level' => $star->level,
                'recognition_date' => $star->recognition_date?->format('Y-m-d'),
                'expiry_date' => $star->expiry_date?->format('Y-m-d'),
                'achievements' => $star->achievements,
                'badges' => $star->badges,
                'skills' => $star->skills,
                'areas_of_service' => $star->areas_of_service,
                'available_days' => $star->available_days,
                'available_from' => $star->available_from,
                'available_to' => $star->available_to,
                'hours_per_week' => $star->hours_per_week,
                'total_hours_served' => $star->total_hours_served,
                'is_contactable' => $star->is_contactable,
                'preferred_contact_method' => $star->preferred_contact_method,
                'receive_notifications' => $star->receive_notifications,
                'bio' => $star->bio,
                'is_public_profile' => $star->is_public_profile,
                'is_featured' => $star->is_featured,
                'testimonial' => $star->testimonial,
                'favorite_verse' => $star->favorite_verse,
                'notes' => $star->notes,
                'internal_notes' => $star->internal_notes,
                'avatar' => $star->avatar ? Storage::url($star->avatar) : null,
                'cover_image' => $star->cover_image ? Storage::url($star->cover_image) : null,
                'user' => $star->user ? [
                    'id' => $star->user->id,
                    'name' => $star->user->full_name,
                    'email' => $star->user->email,
                ] : null,
            ],
            'departments' => Department::active()->orderBy('name')->get(['id', 'uuid', 'name']),
            'nominators' => User::orderBy('first_name')->get(['id', 'uuid', 'first_name', 'last_name']),
            'statuses' => collect(StarStatus::cases())->map(fn($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'types' => collect(StarType::cases())->map(fn($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'categories' => collect(StarCategory::cases())->map(fn($c): array => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    /**
     * Update the specified star.
     */
    public function update(Request $request, Star $star): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'nominated_by' => 'nullable|exists:users,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => ['required', Rule::enum(StarStatus::class)],
            'type' => ['required', Rule::enum(StarType::class)],
            'category' => ['nullable', Rule::enum(StarCategory::class)],
            'points' => 'nullable|integer|min:0',
            'level' => 'nullable|integer|min:1|max:5',
            'recognition_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'achievements' => 'nullable|array',
            'badges' => 'nullable|array',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'areas_of_service' => 'nullable|array',
            'areas_of_service.*' => 'string|max:100',
            'available_days' => 'nullable|array',
            'available_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'available_from' => 'nullable|date_format:H:i',
            'available_to' => 'nullable|date_format:H:i',
            'hours_per_week' => 'nullable|integer|min:0',
            'total_hours_served' => 'nullable|integer|min:0',
            'is_contactable' => 'boolean',
            'preferred_contact_method' => 'nullable|in:email,phone,sms',
            'receive_notifications' => 'boolean',
            'bio' => 'nullable|string|max:2000',
            'is_public_profile' => 'boolean',
            'is_featured' => 'boolean',
            'testimonial' => 'nullable|string|max:1000',
            'favorite_verse' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'internal_notes' => 'nullable|string|max:5000',
            'avatar' => 'nullable|image|max:2048',
            'cover_image' => 'nullable|image|max:4096',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($star->avatar) {
                Storage::disk('public')->delete($star->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('stars/avatars', 'public');
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            if ($star->cover_image) {
                Storage::disk('public')->delete($star->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('stars/covers', 'public');
        }

        $star->update($validated);

        return redirect()
            ->route('stars.show', $star)
            ->with('success', 'Star mis à jour avec succès.');
    }

    /**
     * Remove the specified star.
     */
    public function destroy(Star $star): RedirectResponse
    {
        // Delete avatar if exists
        if ($star->avatar) {
            Storage::disk('public')->delete($star->avatar);
        }
        // Delete cover image if exists
        if ($star->cover_image) {
            Storage::disk('public')->delete($star->cover_image);
        }

        $star->delete();

        return redirect()
            ->route('stars.index')
            ->with('success', 'Star supprimé avec succès.');
    }

    /**
     * Activate a star.
     */
    public function activate(Star $star): RedirectResponse
    {
        $star->activate();

        return back()->with('success', 'Star activé avec succès.');
    }

    /**
     * Deactivate a star.
     */
    public function deactivate(Star $star): RedirectResponse
    {
        $star->deactivate();

        return back()->with('success', 'Star désactivé.');
    }

    /**
     * Set star on break.
     */
    public function setOnBreak(Star $star): RedirectResponse
    {
        $star->setOnBreak();

        return back()->with('success', 'Star mis en pause.');
    }

    /**
     * Graduate a star.
     */
    public function graduate(Star $star): RedirectResponse
    {
        $star->graduate();

        return back()->with('success', 'Star diplômé avec succès.');
    }

    /**
     * Suspend a star.
     */
    public function suspend(Star $star): RedirectResponse
    {
        $star->suspend();

        return back()->with('success', 'Star suspendu.');
    }

    /**
     * Add points to a star.
     */
    public function addPoints(Request $request, Star $star): RedirectResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1|max:1000',
        ]);

        $star->addPoints($validated['points']);

        return back()->with('success', "{$validated['points']} points ajoutés.");
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(Star $star): RedirectResponse
    {
        $star->setFeatured(!$star->is_featured);

        $message = $star->is_featured ? 'Star mis en vedette.' : 'Star retiré de la vedette.';
        return back()->with('success', $message);
    }

    /**
     * Renew star membership.
     */
    public function renew(Request $request, Star $star): RedirectResponse
    {
        $validated = $request->validate([
            'months' => 'required|integer|min:1|max:24',
        ]);

        $star->renewForMonths($validated['months']);

        return back()->with('success', "Star renouvelé pour {$validated['months']} mois.");
    }

    /**
     * Export stars list.
     */
    public function export(Request $request)
    {
        $stars = Star::with(['user', 'department'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->get();

        return response()->json([
            'stars' => $stars->map(fn($s): array => [
                'star_number' => $s->star_number,
                'name' => $s->full_name,
                'email' => $s->user?->email,
                'title' => $s->title,
                'department' => $s->department?->name,
                'status' => $s->status->label(),
                'type' => $s->type->label(),
                'category' => $s->category?->label(),
                'level' => $s->level,
                'points' => $s->points,
                'total_hours_served' => $s->total_hours_served,
                'recognition_date' => $s->recognition_date?->format('Y-m-d'),
            ]),
        ]);
    }
}
