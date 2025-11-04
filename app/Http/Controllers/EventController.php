<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Event;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Cache events list per page (5 minutes cache)
        $cacheKey = $search ? 'events.index.search.' . md5($search) : 'events.index';
        $events = CacheService::rememberPaginated(
            $cacheKey,
            $page,
            fn() => Event::with(['creator', 'address', 'participants'])
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhere('location', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->paginate(10),
            CacheService::SHORT_CACHE
        );

        return Inertia::render('Events/Index', [
            'events' => $events,
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
            $validated['avatar'] = 'events/avatars/' . $request->avatar;
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

        return redirect()->route('events.index')
            ->with('message', 'Événement créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): Response
    {
        $event->load(['creator', 'address', 'participants']);

        return Inertia::render('Events/Show', [
            'event' => $event,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event): Response
    {
        $event->load('address', 'participants');

        return Inertia::render('Events/Edit', [
            'event' => $event,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        // Additional check with user-friendly message
        if (!$event->canBeModifiedBy(Auth::user())) {
            return back()->with('error', 'Cet événement est terminé et ne peut plus être modifié. Seuls les SuperAdmins peuvent modifier les événements passés.');
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
            $validated['avatar'] = 'events/avatars/' . $request->avatar;
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
        if (!$event->canBeModifiedBy(Auth::user())) {
            return back()->with('error', 'Cet événement est terminé et ne peut plus être supprimé. Seuls les SuperAdmins peuvent supprimer les événements passés.');
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
        if (!$event->canAcceptParticipationChanges($user)) {
            return back()->with('error', 'Cet événement est terminé et la participation ne peut plus être modifiée. Seuls les SuperAdmins peuvent gérer la participation aux événements passés.');
        }

        if ($event->participants->contains($user)) {
            $event->participants()->detach($user);
            $message = 'Vous avez quitté l\'événement.';
        } else {
            if ($event->max_participants && $event->participants->count() >= $event->max_participants) {
                return back()->with('error', 'L\'événement est complet.');
            }
            $event->participants()->attach($user);
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
        if (!$event->canAcceptParticipationChanges($user)) {
            return back()->with('error', 'Cet événement est terminé et vous ne pouvez plus vous y inscrire. Seuls les SuperAdmins peuvent gérer la participation aux événements passés.');
        }

        // Check if user is already a participant
        if ($event->participants->contains($user)) {
            return back()->with('message', 'Vous participez déjà à cet événement.');
        }

        // Check if event is full
        if ($event->max_participants && $event->participants->count() >= $event->max_participants) {
            return back()->with('error', 'L\'événement est complet.');
        }

        $event->participants()->attach($user);

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
        if (!$event->canAcceptParticipationChanges($user)) {
            return back()->with('error', 'Cet événement est terminé et vous ne pouvez plus vous en désinscrire. Seuls les SuperAdmins peuvent gérer la participation aux événements passés.');
        }

        // Check if user is a participant
        if (!$event->participants->contains($user)) {
            return back()->with('error', 'Vous ne participez pas à cet événement.');
        }

        $event->participants()->detach($user);

        // Invalidate events cache
        CacheService::forgetPattern('events');

        return back()->with('message', 'Vous avez quitté l\'événement.');
    }
}
