<?php

namespace App\Http\Controllers;

use App\Enums\Event\ParticipantRole;
use App\Enums\Event\RegistrationStatus;
use App\Models\Address;
use App\Models\Event;
use App\Models\Event\EventRegistration;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view events')->only(['index', 'show']);
        $this->middleware('can:create events')->only(['create', 'store']);
        $this->middleware('can:edit events')->only(['edit', 'update']);
        $this->middleware('can:delete events')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        $search = $request->get('search');

        $query = Event::with(['creator', 'address', 'participants']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $events = $query->latest()
            ->paginate(10)
            ->appends($request->query());

        return Inertia::render('Events/Index', [
            'events' => [
                'data' => $events->items(),
                'links' => $events->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                    'from' => $events->firstItem(),
                    'to' => $events->lastItem(),
                ],
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Events/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|string|in:event,task,appointment',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'nullable|string',
            'max_participants' => 'nullable|integer|min:1',
            'is_public' => 'boolean',
            'avatar' => 'nullable',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
            'address' => 'nullable|array',
            'address.street' => 'nullable|string',
            'address.city' => 'nullable|string',
            'address.postal_code' => 'nullable|string',
            'address.country' => 'nullable|string',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('events/avatars', 'public');
        }
        // Handle avatar from TUS upload (just filename)
        elseif ($request->filled('avatar') && is_string($request->avatar)) {
            // Avatar has already been uploaded via TUS to events/avatars directory
            $validated['avatar'] = 'events/avatars/'.$request->avatar;
        }

        $address = null;
        if ($request->has('address') && ! empty(array_filter($request->address))) {
            $address = Address::create($request->address);
        }

        $event = Event::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'location' => $validated['location'] ?? null,
            'max_participants' => $validated['max_participants'] ?? null,
            'is_public' => $validated['is_public'] ?? true,
            'avatar' => $validated['avatar'] ?? null,
            'status' => 'planned',
            'user_id' => Auth::id(),
            'address_id' => $address?->id,
        ]);

        // Attach participants if provided
        if ($request->has('participant_ids') && is_array($request->participant_ids)) {
            $event->participants()->attach($request->participant_ids);
        }

        // Invalidate events cache
        CacheService::forgetPattern('events');

        return redirect()->route('events.edit', $event->uuid)
            ->with('message', 'Événement créé avec succès. Vous pouvez maintenant ajouter des images et vidéos.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): Response
    {
        $event->load(['creator', 'address', 'participants', 'programme', 'media' => fn ($q) => $q->ordered()]);

        // Filter out media without existing files
        $mediaWithFiles = $event->media->filter(fn ($m) => $m->fileExists());
        $event->setRelation('media', $mediaWithFiles);

        $user = Auth::user();
        $isCreator = $user && $event->user_id === $user->id;
        $isSuperAdmin = $user && $user->hasRole('super-admin');

        // Calculate tab permissions
        $tabPermissions = [
            'canViewGallery' => $isSuperAdmin || $isCreator || ($user && $user->can('view event gallery')),
            'canManageTickets' => $isSuperAdmin || $isCreator || ($user && $user->can('manage tickets')),
            'canViewRegistrations' => $isSuperAdmin || $isCreator || ($user && ($user->can('view registrations') || $user->can('manage registrations'))),
            'canCheckIn' => $isSuperAdmin || $isCreator || ($user && ($user->can('checkin events') || $user->can('manage registrations'))),
            'canViewAnalytics' => $isSuperAdmin || $isCreator || ($user && $user->can('view event analytics')),
            'canViewProgramme' => $event->programme !== null,
        ];

        $programme = $event->programme;

        return Inertia::render('Events/Show', [
            'event' => $event,
            'banners' => $event->banners()->get()->filter(fn ($m) => $m->fileExists())->values(),
            'galleryImages' => $event->images()->gallery()->get()->filter(fn ($m) => $m->fileExists())->values(),
            'galleryVideos' => $event->videos()->gallery()->get()->filter(fn ($m) => $m->fileExists())->values(),
            'tabPermissions' => $tabPermissions,
            'programme' => $programme?->append(['file_url', 'file_size_for_humans', 'is_pdf', 'is_image', 'can_preview', 'share_url']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event): Response
    {
        $event->load(['address', 'participants', 'programme', 'media' => fn ($q) => $q->ordered()]);

        // Filter out media without existing files
        $mediaWithFiles = $event->media->filter(fn ($m) => $m->fileExists());
        $event->setRelation('media', $mediaWithFiles);

        $programme = $event->programme;

        return Inertia::render('Events/Edit', [
            'event' => $event,
            'banners' => $event->banners()->get()->filter(fn ($m) => $m->fileExists())->values(),
            'galleryImages' => $event->images()->gallery()->get()->filter(fn ($m) => $m->fileExists())->values(),
            'galleryVideos' => $event->videos()->gallery()->get()->filter(fn ($m) => $m->fileExists())->values(),
            'programme' => $programme?->append(['file_url', 'file_size_for_humans', 'is_pdf', 'is_image', 'can_preview']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        // Additional check with user-friendly message
        if (! $event->canBeModifiedBy(Auth::user())) {
            return back()->with('error', 'Cet événement est terminé et ne peut plus être modifié. Seuls les super-admins peuvent modifier les événements passés.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'nullable|string',
            'max_participants' => 'nullable|integer|min:1',
            'is_public' => 'boolean',
            'status' => 'required|in:planned,ongoing,completed,cancelled',
            'avatar' => 'nullable',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
            'address' => 'nullable|array',
            'address.street' => 'nullable|string',
            'address.city' => 'nullable|string',
            'address.postal_code' => 'nullable|string',
            'address.country' => 'nullable|string',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if it exists
            if ($event->avatar) {
                \Storage::disk('public')->delete($event->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('events/avatars', 'public');
        }
        // Handle avatar from TUS upload (just filename)
        elseif ($request->filled('avatar') && is_string($request->avatar)) {
            // Delete old avatar if it exists
            if ($event->avatar) {
                \Storage::disk('public')->delete($event->avatar);
            }
            // Avatar has already been uploaded via TUS to events/avatars directory
            $validated['avatar'] = 'events/avatars/'.$request->avatar;
        }

        if ($request->has('address') && ! empty(array_filter($request->address))) {
            if ($event->address) {
                $event->address->update($request->address);
            } else {
                $address = Address::create($request->address);
                $event->address_id = $address->id;
            }
        }

        $event->update($validated);

        // Sync participants if provided
        if ($request->has('participant_ids')) {
            $event->participants()->sync($request->participant_ids ?? []);
        }

        // Invalidate events cache
        CacheService::forgetPattern('events');

        return redirect()->route('events.index')
            ->with('message', 'Événement mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        // Additional check with user-friendly message
        if (! $event->canBeModifiedBy(Auth::user())) {
            return back()->with('error', 'Cet événement est terminé et ne peut plus être supprimé. Seuls les super-admins peuvent supprimer les événements passés.');
        }

        // Delete avatar if it exists
        if ($event->avatar) {
            \Storage::disk('public')->delete($event->avatar);
        }

        $event->delete();

        // Invalidate events cache
        CacheService::forgetPattern('events');

        return redirect()->route('events.index')
            ->with('message', 'Événement supprimé avec succès.');
    }

    /**
     * Join or leave an event
     */
    public function toggleParticipation(Event $event): RedirectResponse
    {
        $this->authorize('participate', $event);

        $user = Auth::user();

        // Additional check with user-friendly message
        if (! $event->canAcceptParticipationChanges($user)) {
            return back()->with('error', 'Cet événement est terminé et la participation ne peut plus être modifiée. Seuls les super-admins peuvent gérer la participation aux événements passés.');
        }

        if ($event->participants->contains($user)) {
            // Leave the event
            DB::transaction(function () use ($event, $user) {
                $event->participants()->detach($user);
                $this->cancelRegistration($event, $user);
            });
            $message = 'Vous avez quitté l\'événement.';
        } else {
            if ($event->max_participants && $event->participants->count() >= $event->max_participants) {
                return back()->with('error', 'L\'événement est complet.');
            }
            // Join the event
            DB::transaction(function () use ($event, $user) {
                $event->participants()->attach($user);
                $this->createRegistration($event, $user);
            });
            $message = 'Vous participez maintenant à l\'événement.';
        }

        // Invalidate events cache
        CacheService::forgetPattern('events');

        return back()->with('message', $message);
    }

    /**
     * Join an event
     */
    public function join(Event $event): RedirectResponse
    {
        $this->authorize('participate', $event);

        $user = Auth::user();

        // Additional check with user-friendly message
        if (! $event->canAcceptParticipationChanges($user)) {
            return back()->with('error', 'Cet événement est terminé et vous ne pouvez plus vous y inscrire. Seuls les super-admins peuvent gérer la participation aux événements passés.');
        }

        // Check if user is already a participant
        if ($event->participants->contains($user)) {
            return back()->with('message', 'Vous participez déjà à cet événement.');
        }

        // Check if event is full
        if ($event->max_participants && $event->participants->count() >= $event->max_participants) {
            return back()->with('error', 'L\'événement est complet.');
        }

        // Join the event with registration
        DB::transaction(function () use ($event, $user) {
            $event->participants()->attach($user);
            $this->createRegistration($event, $user);
        });

        // Invalidate events cache
        CacheService::forgetPattern('events');

        return back()->with('message', 'Vous participez maintenant à l\'événement.');
    }

    /**
     * Leave an event
     */
    public function leave(Event $event): RedirectResponse
    {
        $this->authorize('participate', $event);

        $user = Auth::user();

        // Additional check with user-friendly message
        if (! $event->canAcceptParticipationChanges($user)) {
            return back()->with('error', 'Cet événement est terminé et vous ne pouvez plus vous en désinscrire. Seuls les super-admins peuvent gérer la participation aux événements passés.');
        }

        // Check if user is a participant
        if (! $event->participants->contains($user)) {
            return back()->with('error', 'Vous ne participez pas à cet événement.');
        }

        // Leave the event with registration cancellation
        DB::transaction(function () use ($event, $user) {
            $event->participants()->detach($user);
            $this->cancelRegistration($event, $user);
        });

        // Invalidate events cache
        CacheService::forgetPattern('events');

        return back()->with('message', 'Vous avez quitté l\'événement.');
    }

    /**
     * Create an EventRegistration for a user joining an event.
     * This synchronizes the legacy participant system with the new registration system.
     */
    protected function createRegistration(Event $event, User $user): EventRegistration
    {
        // Check if registration already exists
        $existingRegistration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->whereNotIn('status', [RegistrationStatus::CANCELLED])
            ->first();

        if ($existingRegistration) {
            return $existingRegistration;
        }

        return EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'first_name' => $user->first_name ?? $user->name,
            'last_name' => $user->last_name ?? '',
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => RegistrationStatus::CONFIRMED,
            'participant_role' => ParticipantRole::ATTENDEE,
            'quantity' => 1,
            'unit_price' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'currency' => 'EUR',
            'registered_at' => now(),
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Cancel an EventRegistration when a user leaves an event.
     * This synchronizes the legacy participant system with the new registration system.
     */
    protected function cancelRegistration(Event $event, User $user): void
    {
        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->whereNotIn('status', [RegistrationStatus::CANCELLED])
            ->first();

        if ($registration) {
            $registration->cancel('Désinscription via le système de participation', $user->id);
        }
    }
}
