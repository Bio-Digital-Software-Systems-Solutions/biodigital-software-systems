<?php

namespace App\Http\Controllers\Scheduling;

use App\Enums\Scheduling\SwapRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\ShiftSwapRequest;
use App\Services\Scheduling\ConflictDetectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftSwapController extends Controller
{
    public function __construct(
        protected ConflictDetectionService $conflictService
    ) {}

    /**
     * Display swap requests for a department
     */
    public function index(Request $request, Department $department): Response
    {
        $this->authorize('view', $department);

        $query = ShiftSwapRequest::whereHas('requestedShift', function ($q) use ($department) {
            $q->where('department_id', $department->id);
        })->with([
            'requester',
            'targetUser',
            'requestedShift',
            'offeredShift',
            'approvedByUser',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $swapRequests = $query->orderBy('created_at', 'desc')->paginate(20);

        // Pending counts
        $pendingColleague = ShiftSwapRequest::whereHas('requestedShift', function ($q) use ($department) {
            $q->where('department_id', $department->id);
        })->where('status', SwapRequestStatus::PENDING_COLLEAGUE)->count();

        $pendingManager = ShiftSwapRequest::whereHas('requestedShift', function ($q) use ($department) {
            $q->where('department_id', $department->id);
        })->where('status', SwapRequestStatus::PENDING_MANAGER)->count();

        return Inertia::render('Departments/Schedule/SwapRequests/Index', [
            'department' => $department,
            'swapRequests' => $swapRequests,
            'pendingColleague' => $pendingColleague,
            'pendingManager' => $pendingManager,
            'swapStatuses' => collect(SwapRequestStatus::cases())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Show my swap requests (for current user)
     */
    public function mySwapRequests(Request $request, Department $department): Response
    {
        $user = $request->user();

        // Requests I made
        $outgoing = ShiftSwapRequest::where('requester_id', $user->id)
            ->whereHas('requestedShift', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })
            ->with(['targetUser', 'requestedShift', 'offeredShift'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Requests where I'm the target
        $incoming = ShiftSwapRequest::where('target_user_id', $user->id)
            ->whereHas('requestedShift', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })
            ->where('status', SwapRequestStatus::PENDING_COLLEAGUE)
            ->with(['requester', 'requestedShift', 'offeredShift'])
            ->orderBy('created_at', 'desc')
            ->get();

        // My available shifts for swap
        $myShifts = Shift::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('date', '>=', now())
            ->whereNotIn('status', [\App\Enums\Scheduling\ShiftStatus::CANCELLED, \App\Enums\Scheduling\ShiftStatus::COMPLETED])
            ->orderBy('date')
            ->get();

        return Inertia::render('Departments/Schedule/SwapRequests/MySwapRequests', [
            'department' => $department,
            'outgoing' => $outgoing,
            'incoming' => $incoming,
            'myShifts' => $myShifts,
            'swapStatuses' => collect(SwapRequestStatus::cases())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    /**
     * Show the form to create a new swap request
     */
    public function create(Request $request, Department $department): Response
    {
        $user = $request->user();

        // Get shifts available for swap (future shifts not assigned to current user)
        $availableShifts = Shift::where('department_id', $department->id)
            ->where('date', '>=', now())
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $user->id)
            ->whereNotIn('status', [\App\Enums\Scheduling\ShiftStatus::CANCELLED, \App\Enums\Scheduling\ShiftStatus::COMPLETED])
            ->with('user')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Get my shifts that I can offer
        $myShifts = Shift::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('date', '>=', now())
            ->whereNotIn('status', [\App\Enums\Scheduling\ShiftStatus::CANCELLED, \App\Enums\Scheduling\ShiftStatus::COMPLETED])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('Departments/Schedule/SwapRequests/Create', [
            'department' => $department,
            'availableShifts' => $availableShifts,
            'myShifts' => $myShifts,
        ]);
    }

    /**
     * Create a new swap request
     */
    public function store(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'requested_shift_id' => 'required|exists:shifts,id',
            'offered_shift_id' => 'nullable|exists:shifts,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $requestedShift = Shift::findOrFail($validated['requested_shift_id']);

        // Verify the shift belongs to the department
        if ($requestedShift->department_id !== $department->id) {
            return back()->with('error', 'Ce shift n\'appartient pas à ce département.');
        }

        // Can't request own shift
        if ($requestedShift->user_id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas demander à échanger votre propre shift.');
        }

        // If offering a shift, verify ownership
        $offeredShift = null;
        if (isset($validated['offered_shift_id'])) {
            $offeredShift = Shift::findOrFail($validated['offered_shift_id']);
            if ($offeredShift->user_id !== $request->user()->id) {
                return back()->with('error', 'Vous ne pouvez offrir que vos propres shifts.');
            }
        }

        // Check for conflicts if swap would happen
        $conflicts = $this->conflictService->detectConflicts($requestedShift, $request->user());
        if ($conflicts['has_blocking_conflicts']) {
            return back()->with('error', 'Des conflits empêchent cet échange.');
        }

        ShiftSwapRequest::create([
            'requester_id' => $request->user()->id,
            'target_user_id' => $requestedShift->user_id,
            'requested_shift_id' => $requestedShift->id,
            'offered_shift_id' => $offeredShift?->id,
            'status' => SwapRequestStatus::PENDING_COLLEAGUE,
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', 'Demande d\'échange envoyée.');
    }

    /**
     * Show a specific swap request
     */
    public function show(Department $department, ShiftSwapRequest $swapRequest): Response
    {
        $this->authorize('view', $department);

        $swapRequest->load([
            'requester',
            'targetUser',
            'requestedShift.weeklySchedule',
            'offeredShift.weeklySchedule',
            'approvedByUser',
        ]);

        // Check conflicts for both parties
        $requesterConflicts = $swapRequest->requestedShift
            ? $this->conflictService->detectConflicts($swapRequest->requestedShift, $swapRequest->requester)
            : null;

        $targetConflicts = $swapRequest->offeredShift && $swapRequest->targetUser
            ? $this->conflictService->detectConflicts($swapRequest->offeredShift, $swapRequest->targetUser)
            : null;

        return Inertia::render('Departments/Schedule/SwapRequests/Show', [
            'department' => $department,
            'swapRequest' => $swapRequest,
            'requesterConflicts' => $requesterConflicts,
            'targetConflicts' => $targetConflicts,
        ]);
    }

    /**
     * Colleague accepts the swap request
     */
    public function acceptByColleague(Request $request, Department $department, ShiftSwapRequest $swapRequest): RedirectResponse
    {
        // Only target can accept
        if ($swapRequest->target_user_id !== $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas accepter cette demande.');
        }

        if ($swapRequest->status !== SwapRequestStatus::PENDING_COLLEAGUE) {
            return back()->with('error', 'Cette demande n\'est plus en attente.');
        }

        // Check if manager approval is required
        $settings = $department->schedulingSettings;
        $requiresManagerApproval = $settings?->require_swap_approval ?? true;

        if ($requiresManagerApproval) {
            $swapRequest->acceptByColleague();
            return back()->with('success', 'Échange accepté, en attente d\'approbation du responsable.');
        }

        // No manager approval needed, execute swap
        $this->executeSwap($swapRequest, $request->user());

        return back()->with('success', 'Échange effectué avec succès.');
    }

    /**
     * Colleague rejects the swap request
     */
    public function rejectByColleague(Request $request, Department $department, ShiftSwapRequest $swapRequest): RedirectResponse
    {
        if ($swapRequest->target_user_id !== $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas refuser cette demande.');
        }

        if ($swapRequest->status !== SwapRequestStatus::PENDING_COLLEAGUE) {
            return back()->with('error', 'Cette demande n\'est plus en attente.');
        }

        $swapRequest->rejectByColleague();

        return back()->with('success', 'Demande d\'échange refusée.');
    }

    /**
     * Manager approves the swap request
     */
    public function approveByManager(Request $request, Department $department, ShiftSwapRequest $swapRequest): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($swapRequest->status !== SwapRequestStatus::PENDING_MANAGER) {
            return back()->with('error', 'Cette demande n\'est pas en attente d\'approbation.');
        }

        $this->executeSwap($swapRequest, $request->user());

        return back()->with('success', 'Échange approuvé et effectué.');
    }

    /**
     * Manager rejects the swap request
     */
    public function rejectByManager(Request $request, Department $department, ShiftSwapRequest $swapRequest): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($swapRequest->status !== SwapRequestStatus::PENDING_MANAGER) {
            return back()->with('error', 'Cette demande n\'est pas en attente d\'approbation.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $swapRequest->rejectByManager($request->user(), $validated['rejection_reason']);

        return back()->with('success', 'Demande d\'échange refusée.');
    }

    /**
     * Cancel a swap request (by requester)
     */
    public function cancel(Request $request, Department $department, ShiftSwapRequest $swapRequest): RedirectResponse
    {
        if ($swapRequest->requester_id !== $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas annuler cette demande.');
        }

        if (!$swapRequest->status->isPending()) {
            return back()->with('error', 'Cette demande ne peut plus être annulée.');
        }

        $swapRequest->update(['status' => SwapRequestStatus::CANCELLED]);

        return back()->with('success', 'Demande d\'échange annulée.');
    }

    /**
     * Delete a swap request (admin only)
     */
    public function destroy(Department $department, ShiftSwapRequest $swapRequest): RedirectResponse
    {
        $this->authorize('update', $department);

        $swapRequest->delete();

        return back()->with('success', 'Demande supprimée.');
    }

    /**
     * Execute the swap between shifts
     */
    protected function executeSwap(ShiftSwapRequest $swapRequest, $approver): void
    {
        $requestedShift = $swapRequest->requestedShift;
        $offeredShift = $swapRequest->offeredShift;

        // Swap the users
        $requesterUserId = $swapRequest->requester_id;
        $targetUserId = $swapRequest->target_user_id;

        // Update requested shift
        $requestedShift->update(['user_id' => $requesterUserId]);

        // Update offered shift (if exists)
        if ($offeredShift) {
            $offeredShift->update(['user_id' => $targetUserId]);
        }

        // Update swap request status
        $swapRequest->approve($approver);
    }

    /**
     * Get pending count for current user (API)
     */
    public function pendingCount(Request $request, Department $department)
    {
        $user = $request->user();

        // Incoming requests pending my response
        $incoming = ShiftSwapRequest::where('target_user_id', $user->id)
            ->whereHas('requestedShift', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })
            ->where('status', SwapRequestStatus::PENDING_COLLEAGUE)
            ->count();

        return response()->json(['count' => $incoming]);
    }
}
