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

        // Cache events list per page (5 minutes cache)
        $events = CacheService::rememberPaginated(
            'events.index',
            $page,
            fn() => Event::with(['creator', 'address', 'participants'])
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
            'end_date' => 'required|date|after:start_date',
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
        $event->load('address');

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

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string',
            'max_participants' => 'nullable|integer|min:1',
            'is_public' => 'boolean',
            'status' => 'required|in:planned,ongoing,completed,cancelled',
            'avatar' => 'nullable',
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
}
