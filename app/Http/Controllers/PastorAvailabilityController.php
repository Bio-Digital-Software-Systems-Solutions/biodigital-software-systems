<?php

namespace App\Http\Controllers;

use App\Models\PastorAvailability;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PastorAvailabilityController extends Controller
{
    /**
     * Display a listing of the current user's availability.
     */
    public function index()
    {
        $user = Auth::user();

        // Check if user has pastor role or is admin
        if (! $user->hasRole(['pastor', 'super-admin', 'admin'])) {
            abort(403, 'Seuls les pasteurs peuvent gérer leurs créneaux de disponibilité.');
        }

        $availabilities = PastorAvailability::where('pastor_id', $user->id)
            ->orderBy('type')
            ->orderBy('day_of_week')
            ->orderBy('specific_date')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('PastoralCare/Availability/Index', [
            'availabilities' => $availabilities,
            'pastor' => $user->load('roles'),
        ]);
    }

    /**
     * Show the form for creating a new availability.
     */
    public function create()
    {
        $user = Auth::user();

        if (! $user->hasRole(['pastor', 'super-admin', 'admin'])) {
            abort(403, 'Seuls les pasteurs peuvent gérer leurs créneaux de disponibilité.');
        }

        return Inertia::render('PastoralCare/Availability/Create', [
            'pastor' => $user,
        ]);
    }

    /**
     * Store a newly created availability in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasRole(['pastor', 'super-admin', 'admin'])) {
            abort(403, 'Seuls les pasteurs peuvent gérer leurs créneaux de disponibilité.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:weekly,specific_date'],
            'day_of_week' => [
                'nullable',
                'integer',
                'min:0',
                'max:6',
                Rule::requiredIf($request->type === 'weekly'),
            ],
            'specific_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
                Rule::requiredIf($request->type === 'specific_date'),
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:15|max:180',
            'is_active' => 'boolean',
            'consultation_mode' => 'required|in:in_person,online,hybrid',
            'meeting_link' => [
                'nullable',
                'url',
                'max:500',
                Rule::requiredIf(in_array($request->consultation_mode, ['online', 'hybrid'])),
            ],
            'location' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'selected_slots' => 'nullable|array',
            'selected_slots.*' => 'string',
        ]);

        // Check for conflicts with existing availability
        $conflictQuery = PastorAvailability::where('pastor_id', $user->id)
            ->where('is_active', true)
            ->where(function ($query) use ($validated) {
                if ($validated['type'] === 'weekly') {
                    $query->where('type', 'weekly')
                        ->where('day_of_week', $validated['day_of_week']);
                } else {
                    $query->where('type', 'specific_date')
                        ->where('specific_date', $validated['specific_date']);
                }
            });

        if ($conflictQuery->exists()) {
            $conflictType = $validated['type'] === 'weekly' ? 'ce jour de la semaine' : 'cette date';

            return back()->withErrors([
                'conflict' => "Vous avez déjà des créneaux de disponibilité définis pour {$conflictType}.",
            ]);
        }

        $validated['pastor_id'] = $user->id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        PastorAvailability::create($validated);

        return redirect()->route('pastoral-availability.index')
            ->with('success', 'Créneaux de disponibilité créés avec succès.');
    }

    /**
     * Display the specified availability.
     */
    public function show(PastorAvailability $availability)
    {
        $user = Auth::user();

        if (! $user->hasRole(['pastor', 'super-admin', 'admin']) || ($availability->pastor_id !== $user->id && ! $user->hasRole(['super-admin', 'admin']))) {
            abort(403);
        }

        // Generate sample time slots to show pastor what slots will be available
        try {
            if ($availability->type === 'specific_date' && $availability->specific_date) {
                $sampleDate = $availability->specific_date;
            } elseif ($availability->type === 'weekly' && $availability->day_of_week !== null) {
                // Ensure day_of_week is valid (1-7) and convert to JavaScript format (0-6) for Carbon
                $dayOfWeek = (int) $availability->day_of_week;
                if ($dayOfWeek >= 1 && $dayOfWeek <= 7) {
                    // Convert from our numbering (1-7) to JavaScript/Carbon numbering (0-6)
                    $carbonDayOfWeek = $dayOfWeek === 7 ? 0 : $dayOfWeek;
                    $sampleDate = now()->next($carbonDayOfWeek);
                } else {
                    $sampleDate = now(); // Fallback to today
                }
            } else {
                $sampleDate = now(); // Fallback to today
            }

            $sampleSlots = $availability->getTimeSlotsForDate($sampleDate);
        } catch (\Exception $e) {
            \Log::error('Error generating sample slots in show method:', [
                'availability_id' => $availability->id,
                'availability_type' => $availability->type,
                'day_of_week' => $availability->day_of_week,
                'specific_date' => $availability->specific_date,
                'error' => $e->getMessage(),
            ]);
            $sampleSlots = []; // Empty fallback
        }

        return Inertia::render('PastoralCare/Availability/Show', [
            'availability' => $availability,
            'sampleSlots' => $sampleSlots,
        ]);
    }

    /**
     * Show the form for editing the specified availability.
     */
    public function edit(PastorAvailability $availability)
    {
        $user = Auth::user();

        if (! $user->hasRole(['pastor', 'super-admin', 'admin']) || ($availability->pastor_id !== $user->id && ! $user->hasRole(['super-admin', 'admin']))) {
            abort(403);
        }

        return Inertia::render('PastoralCare/Availability/Edit', [
            'availability' => $availability,
        ]);
    }

    /**
     * Update the specified availability in storage.
     */
    public function update(Request $request, PastorAvailability $availability)
    {
        $user = Auth::user();

        if (! $user->hasRole(['pastor', 'super-admin', 'admin']) || ($availability->pastor_id !== $user->id && ! $user->hasRole(['super-admin', 'admin']))) {
            abort(403);
        }

        // Get current availability type to handle validation properly
        $currentType = $availability->type;
        $requestType = $request->get('type', $currentType);

        $validated = $request->validate([
            'type' => 'sometimes|in:weekly,specific_date',
            'day_of_week' => [
                'nullable',
                'integer',
                'min:0',
                'max:6',
                Rule::requiredIf($requestType === 'weekly' && $request->has('type')),
            ],
            'specific_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
                Rule::requiredIf($requestType === 'specific_date' && $request->has('type')),
            ],
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'slot_duration' => 'sometimes|integer|min:15|max:180',
            'is_active' => 'sometimes|boolean',
            'consultation_mode' => 'sometimes|in:in_person,online,hybrid',
            'meeting_link' => [
                'nullable',
                'url',
                'max:500',
                Rule::requiredIf(
                    $request->has('consultation_mode') &&
                    in_array($request->consultation_mode, ['online', 'hybrid'])
                ),
            ],
            'location' => 'sometimes|nullable|string|max:255',
            'room' => 'sometimes|nullable|string|max:255',
            'notes' => 'sometimes|nullable|string|max:500',
            'selected_slots' => 'sometimes|nullable|array',
            'selected_slots.*' => 'string',
        ]);

        // Only check for conflicts if type, day_of_week, or specific_date are being changed
        if (isset($validated['type']) || isset($validated['day_of_week']) || isset($validated['specific_date'])) {
            $checkType = $validated['type'] ?? $availability->type;
            $checkDayOfWeek = $validated['day_of_week'] ?? $availability->day_of_week;
            $checkSpecificDate = $validated['specific_date'] ?? $availability->specific_date;

            $conflictQuery = PastorAvailability::where('pastor_id', $user->id)
                ->where('id', '!=', $availability->id)
                ->where('is_active', true)
                ->where(function ($query) use ($checkType, $checkDayOfWeek, $checkSpecificDate) {
                    if ($checkType === 'weekly') {
                        $query->where('type', 'weekly')
                            ->where('day_of_week', $checkDayOfWeek);
                    } else {
                        $query->where('type', 'specific_date')
                            ->where('specific_date', $checkSpecificDate);
                    }
                });

            if ($conflictQuery->exists()) {
                $conflictType = $checkType === 'weekly' ? 'ce jour de la semaine' : 'cette date';

                return back()->withErrors([
                    'conflict' => "Vous avez déjà des créneaux de disponibilité définis pour {$conflictType}.",
                ]);
            }
        }

        // Log what we're updating for debugging
        \Log::info('Updating pastoral availability', [
            'availability_id' => $availability->id,
            'validated_fields' => array_keys($validated),
            'changes' => $validated,
        ]);

        // Only update fields that are present in validated data
        $availability->update($validated);

        return redirect()->route('pastoral-availability.index')
            ->with('success', 'Créneaux de disponibilité mis à jour avec succès.');
    }

    /**
     * Remove the specified availability from storage.
     */
    public function destroy(PastorAvailability $availability)
    {
        $user = Auth::user();

        if (! $user->hasRole(['pastor', 'super-admin', 'admin']) || ($availability->pastor_id !== $user->id && ! $user->hasRole(['super-admin', 'admin']))) {
            abort(403);
        }

        $availability->delete();

        return redirect()->route('pastoral-availability.index')
            ->with('success', 'Créneaux de disponibilité supprimés avec succès.');
    }

    /**
     * Toggle active status of the specified availability.
     */
    public function toggleStatus(PastorAvailability $availability)
    {
        $user = Auth::user();

        if (! $user->hasRole(['pastor', 'super-admin', 'admin']) || ($availability->pastor_id !== $user->id && ! $user->hasRole(['super-admin', 'admin']))) {
            abort(403);
        }

        $availability->update([
            'is_active' => ! $availability->is_active,
        ]);

        $status = $availability->is_active ? 'activés' : 'désactivés';

        return back()->with('success', "Créneaux de disponibilité {$status} avec succès.");
    }

    /**
     * Get available time slots for preview (AJAX endpoint)
     */
    public function previewSlots(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user has pastor role
            if (! $user->hasRole(['pastor', 'super-admin', 'admin'])) {
                return response()->json([
                    'error' => 'Seuls les pasteurs peuvent prévisualiser leurs créneaux de disponibilité.',
                ], 403);
            }

            // Log request data for debugging
            \Log::info('Preview slots request data:', [
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
                'slot_duration' => $request->input('slot_duration'),
            ]);

            $validated = $request->validate([
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'slot_duration' => 'required|integer|min:15|max:180',
            ]);

            // Create a temporary availability object to generate slots
            $tempAvailability = new PastorAvailability([
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'slot_duration' => $validated['slot_duration'],
            ]);

            $slots = $tempAvailability->getTimeSlotsForDate(now());

            \Log::info('Preview slots generated successfully', [
                'slots_count' => count($slots),
                'time_range' => "{$validated['start_time']}-{$validated['end_time']}",
                'slot_duration' => $validated['slot_duration'],
            ]);

            return response()->json([
                'slots' => $slots,
                'count' => count($slots),
            ]);
        } catch (\Exception $e) {
            \Log::error('Preview slots error: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error generating preview slots: '.$e->getMessage(),
            ], 500);
        }
    }
}
