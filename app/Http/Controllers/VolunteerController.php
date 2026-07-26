<?php

namespace App\Http\Controllers;

use App\Enums\Volunteer\VolunteerCategory;
use App\Enums\Volunteer\VolunteerStatus;
use App\Enums\Volunteer\VolunteerType;
use App\Models\Department;
use App\Models\Volunteer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VolunteerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view volunteers')->only(['index', 'show']);
        $this->middleware('can:manage volunteers')->except(['index', 'show']);
    }

    /**
     * Display a listing of volunteers.
     */
    public function index(Request $request): Response
    {
        $query = Volunteer::with(['user', 'department', 'nominator'])
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

        $volunteers = $query->paginate(15)->withQueryString();

        // Transform volunteers for frontend
        $volunteers->getCollection()->transform(fn($volunteer): array => [
            'id' => $volunteer->id,
            'uuid' => $volunteer->uuid,
            'volunteer_number' => $volunteer->volunteer_number,
            'full_name' => $volunteer->full_name,
            'title' => $volunteer->title,
            'status' => [
                'value' => $volunteer->status->value,
                'label' => $volunteer->status->label(),
                'color' => $volunteer->status->color(),
            ],
            'type' => [
                'value' => $volunteer->type->value,
                'label' => $volunteer->type->label(),
                'color' => $volunteer->type->color(),
            ],
            'category' => $volunteer->category ? [
                'value' => $volunteer->category->value,
                'label' => $volunteer->category->label(),
                'color' => $volunteer->category->color(),
            ] : null,
            'department' => $volunteer->department ? [
                'id' => $volunteer->department->id,
                'uuid' => $volunteer->department->uuid,
                'name' => $volunteer->department->name,
            ] : null,
            'level' => $volunteer->level,
            'level_title' => $volunteer->level_title,
            'points' => $volunteer->points,
            'total_hours_served' => $volunteer->total_hours_served,
            'is_featured' => $volunteer->is_featured,
            'is_public_profile' => $volunteer->is_public_profile,
            'recognition_date' => $volunteer->recognition_date?->format('Y-m-d'),
            'user' => $volunteer->user ? [
                'id' => $volunteer->user->id,
                'uuid' => $volunteer->user->uuid,
                'name' => $volunteer->user->full_name,
                'email' => $volunteer->user->email,
                'avatar' => $volunteer->user->avatar,
            ] : null,
            'avatar' => $volunteer->avatar ? Storage::url($volunteer->avatar) : null,
        ]);

        return Inertia::render('Volunteers/Index', [
            'volunteers' => $volunteers,
            'filters' => $request->only(['search', 'status', 'type', 'category', 'department', 'level', 'featured']),
            'statuses' => collect(VolunteerStatus::cases())->map(fn($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'types' => collect(VolunteerType::cases())->map(fn($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
            ]),
            'categories' => collect(VolunteerCategory::cases())->map(fn($c): array => [
                'value' => $c->value,
                'label' => $c->label(),
                'color' => $c->color(),
            ]),
            'departments' => Department::active()->orderBy('name')->get(['id', 'uuid', 'name']),
            'stats' => [
                'total' => Volunteer::count(),
                'active' => Volunteer::active()->count(),
                'featured' => Volunteer::featured()->count(),
                'new_this_month' => Volunteer::where('recognition_date', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new volunteer.
     */
    public function create(): Response
    {
        return Inertia::render('Volunteers/Create', [
            'users' => User::doesntHave('volunteer')
                ->orderBy('first_name')
                ->get(['id', 'uuid', 'first_name', 'last_name', 'email']),
            'departments' => Department::active()->orderBy('name')->get(['id', 'uuid', 'name']),
            'nominators' => User::orderBy('first_name')->get(['id', 'uuid', 'first_name', 'last_name']),
            'statuses' => collect(VolunteerStatus::cases())->map(fn($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'types' => collect(VolunteerType::cases())->map(fn($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'categories' => collect(VolunteerCategory::cases())->map(fn($c): array => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    /**
     * Store a newly created volunteer.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:volunteers,user_id',
            'department_id' => 'nullable|exists:departments,id',
            'nominated_by' => 'nullable|exists:users,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => ['required', Rule::enum(VolunteerStatus::class)],
            'type' => ['required', Rule::enum(VolunteerType::class)],
            'category' => ['nullable', Rule::enum(VolunteerCategory::class)],
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
            $validated['avatar'] = $request->file('avatar')->store('volunteers/avatars', 'public');
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('volunteers/covers', 'public');
        }

        // Set default recognition date if not provided
        if (empty($validated['recognition_date'])) {
            $validated['recognition_date'] = now();
        }

        $volunteer = Volunteer::create($validated);

        return redirect()
            ->route('volunteers.show', $volunteer)
            ->with('success', 'Volontaire créé avec succès.');
    }

    /**
     * Display the specified volunteer.
     */
    public function show(Request $request, Volunteer $volunteer): Response
    {
        $volunteer->load([
            'user',
            'department',
            'nominator',
        ]);

        return Inertia::render('Volunteers/Show', [
            'volunteer' => [
                'id' => $volunteer->id,
                'uuid' => $volunteer->uuid,
                'volunteer_number' => $volunteer->volunteer_number,
                'full_name' => $volunteer->full_name,
                'title' => $volunteer->title,
                'description' => $volunteer->description,
                'status' => [
                    'value' => $volunteer->status->value,
                    'label' => $volunteer->status->label(),
                    'color' => $volunteer->status->color(),
                ],
                'type' => [
                    'value' => $volunteer->type->value,
                    'label' => $volunteer->type->label(),
                    'color' => $volunteer->type->color(),
                ],
                'category' => $volunteer->category ? [
                    'value' => $volunteer->category->value,
                    'label' => $volunteer->category->label(),
                    'color' => $volunteer->category->color(),
                ] : null,
                'points' => $volunteer->points,
                'level' => $volunteer->level,
                'level_title' => $volunteer->level_title,
                'next_level_points' => $volunteer->next_level_points,
                'progress_to_next_level' => $volunteer->progress_to_next_level,
                'recognition_date' => $volunteer->recognition_date?->format('Y-m-d'),
                'expiry_date' => $volunteer->expiry_date?->format('Y-m-d'),
                'is_expired' => $volunteer->is_expired,
                'days_until_expiry' => $volunteer->days_until_expiry,
                'service_duration' => $volunteer->service_duration,
                'achievements' => $volunteer->achievements,
                'badges' => $volunteer->badges,
                'skills' => $volunteer->skills,
                'areas_of_service' => $volunteer->areas_of_service,
                'available_days' => $volunteer->available_days,
                'available_from' => $volunteer->available_from,
                'available_to' => $volunteer->available_to,
                'hours_per_week' => $volunteer->hours_per_week,
                'total_hours_served' => $volunteer->total_hours_served,
                'is_contactable' => $volunteer->is_contactable,
                'preferred_contact_method' => $volunteer->preferred_contact_method,
                'receive_notifications' => $volunteer->receive_notifications,
                'bio' => $volunteer->bio,
                'avatar' => $volunteer->avatar ? Storage::url($volunteer->avatar) : null,
                'cover_image' => $volunteer->cover_image ? Storage::url($volunteer->cover_image) : null,
                'is_public_profile' => $volunteer->is_public_profile,
                'is_featured' => $volunteer->is_featured,
                'display_order' => $volunteer->display_order,
                'testimonial' => $volunteer->testimonial,
                'favorite_verse' => $volunteer->favorite_verse,
                'notes' => $volunteer->notes,
                'internal_notes' => $volunteer->internal_notes,
                'created_at' => $volunteer->created_at->format('Y-m-d H:i'),
                'updated_at' => $volunteer->updated_at->format('Y-m-d H:i'),
                'user' => $volunteer->user ? [
                    'id' => $volunteer->user->id,
                    'uuid' => $volunteer->user->uuid,
                    'name' => $volunteer->user->full_name,
                    'email' => $volunteer->user->email,
                    'avatar' => $volunteer->user->avatar,
                ] : null,
                'department' => $volunteer->department ? [
                    'id' => $volunteer->department->id,
                    'uuid' => $volunteer->department->uuid,
                    'name' => $volunteer->department->name,
                ] : null,
                'nominator' => $volunteer->nominator ? [
                    'id' => $volunteer->nominator->id,
                    'uuid' => $volunteer->nominator->uuid,
                    'name' => $volunteer->nominator->full_name,
                ] : null,
            ],
            'canManage' => $request->user()?->can('manage volunteers'),
        ]);
    }

    /**
     * Show the form for editing the specified volunteer.
     */
    public function edit(Volunteer $volunteer): Response
    {
        $volunteer->load(['user', 'department', 'nominator']);

        return Inertia::render('Volunteers/Edit', [
            'volunteer' => [
                'id' => $volunteer->id,
                'uuid' => $volunteer->uuid,
                'user_id' => $volunteer->user_id,
                'department_id' => $volunteer->department_id,
                'nominated_by' => $volunteer->nominated_by,
                'volunteer_number' => $volunteer->volunteer_number,
                'title' => $volunteer->title,
                'description' => $volunteer->description,
                'status' => $volunteer->status->value,
                'type' => $volunteer->type->value,
                'category' => $volunteer->category?->value,
                'points' => $volunteer->points,
                'level' => $volunteer->level,
                'recognition_date' => $volunteer->recognition_date?->format('Y-m-d'),
                'expiry_date' => $volunteer->expiry_date?->format('Y-m-d'),
                'achievements' => $volunteer->achievements,
                'badges' => $volunteer->badges,
                'skills' => $volunteer->skills,
                'areas_of_service' => $volunteer->areas_of_service,
                'available_days' => $volunteer->available_days,
                'available_from' => $volunteer->available_from,
                'available_to' => $volunteer->available_to,
                'hours_per_week' => $volunteer->hours_per_week,
                'total_hours_served' => $volunteer->total_hours_served,
                'is_contactable' => $volunteer->is_contactable,
                'preferred_contact_method' => $volunteer->preferred_contact_method,
                'receive_notifications' => $volunteer->receive_notifications,
                'bio' => $volunteer->bio,
                'is_public_profile' => $volunteer->is_public_profile,
                'is_featured' => $volunteer->is_featured,
                'testimonial' => $volunteer->testimonial,
                'favorite_verse' => $volunteer->favorite_verse,
                'notes' => $volunteer->notes,
                'internal_notes' => $volunteer->internal_notes,
                'avatar' => $volunteer->avatar ? Storage::url($volunteer->avatar) : null,
                'cover_image' => $volunteer->cover_image ? Storage::url($volunteer->cover_image) : null,
                'user' => $volunteer->user ? [
                    'id' => $volunteer->user->id,
                    'name' => $volunteer->user->full_name,
                    'email' => $volunteer->user->email,
                ] : null,
            ],
            'departments' => Department::active()->orderBy('name')->get(['id', 'uuid', 'name']),
            'nominators' => User::orderBy('first_name')->get(['id', 'uuid', 'first_name', 'last_name']),
            'statuses' => collect(VolunteerStatus::cases())->map(fn($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'types' => collect(VolunteerType::cases())->map(fn($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'categories' => collect(VolunteerCategory::cases())->map(fn($c): array => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    /**
     * Update the specified volunteer.
     */
    public function update(Request $request, Volunteer $volunteer): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'nominated_by' => 'nullable|exists:users,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => ['required', Rule::enum(VolunteerStatus::class)],
            'type' => ['required', Rule::enum(VolunteerType::class)],
            'category' => ['nullable', Rule::enum(VolunteerCategory::class)],
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
            if ($volunteer->avatar) {
                Storage::disk('public')->delete($volunteer->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('volunteers/avatars', 'public');
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            if ($volunteer->cover_image) {
                Storage::disk('public')->delete($volunteer->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('volunteers/covers', 'public');
        }

        $volunteer->update($validated);

        return redirect()
            ->route('volunteers.show', $volunteer)
            ->with('success', 'Volontaire mis à jour avec succès.');
    }

    /**
     * Remove the specified volunteer.
     */
    public function destroy(Volunteer $volunteer): RedirectResponse
    {
        // Delete avatar if exists
        if ($volunteer->avatar) {
            Storage::disk('public')->delete($volunteer->avatar);
        }
        // Delete cover image if exists
        if ($volunteer->cover_image) {
            Storage::disk('public')->delete($volunteer->cover_image);
        }

        $volunteer->delete();

        return redirect()
            ->route('volunteers.index')
            ->with('success', 'Volontaire supprimé avec succès.');
    }

    /**
     * Activate a volunteer.
     */
    public function activate(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->activate();

        return back()->with('success', 'Volontaire activé avec succès.');
    }

    /**
     * Deactivate a volunteer.
     */
    public function deactivate(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->deactivate();

        return back()->with('success', 'Volontaire désactivé.');
    }

    /**
     * Set volunteer on break.
     */
    public function setOnBreak(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->setOnBreak();

        return back()->with('success', 'Volontaire mis en pause.');
    }

    /**
     * Graduate a volunteer.
     */
    public function graduate(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->graduate();

        return back()->with('success', 'Volontaire diplômé avec succès.');
    }

    /**
     * Suspend a volunteer.
     */
    public function suspend(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->suspend();

        return back()->with('success', 'Volontaire suspendu.');
    }

    /**
     * Add points to a volunteer.
     */
    public function addPoints(Request $request, Volunteer $volunteer): RedirectResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1|max:1000',
        ]);

        $volunteer->addPoints($validated['points']);

        return back()->with('success', "{$validated['points']} points ajoutés.");
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(Volunteer $volunteer): RedirectResponse
    {
        $volunteer->setFeatured(!$volunteer->is_featured);

        $message = $volunteer->is_featured ? 'Volontaire mis en vedette.' : 'Volontaire retiré de la vedette.';
        return back()->with('success', $message);
    }

    /**
     * Renew volunteer membership.
     */
    public function renew(Request $request, Volunteer $volunteer): RedirectResponse
    {
        $validated = $request->validate([
            'months' => 'required|integer|min:1|max:24',
        ]);

        $volunteer->renewForMonths($validated['months']);

        return back()->with('success', "Volontaire renouvelé pour {$validated['months']} mois.");
    }

    /**
     * Export volunteers list.
     */
    public function export(Request $request)
    {
        $volunteers = Volunteer::with(['user', 'department'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->get();

        return response()->json([
            'volunteers' => $volunteers->map(fn($s): array => [
                'volunteer_number' => $s->volunteer_number,
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
