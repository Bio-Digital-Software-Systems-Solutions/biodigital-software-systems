<?php

namespace App\Http\Controllers\Scheduling;

use App\Enums\Scheduling\AbsenceStatus;
use App\Enums\Scheduling\AbsenceType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Scheduling\Absence;
use App\Models\Scheduling\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AbsenceController extends Controller
{
    /**
     * Display absences for a department
     */
    public function index(Request $request, Department $department): Response
    {
        $this->authorize('view', $department);

        $query = Absence::where('department_id', $department->id)
            ->with(['user', 'approvedByUser']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by date range
        if ($request->filled('from')) {
            $query->where('end_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('start_date', '<=', $request->input('to'));
        }

        $absences = $query->orderBy('start_date', 'desc')->paginate(20);

        // Pending requests count
        $pendingCount = Absence::where('department_id', $department->id)
            ->where('status', AbsenceStatus::PENDING)
            ->count();

        return Inertia::render('Departments/Schedule/Absences/Index', [
            'department' => $department,
            'absences' => $absences,
            'pendingCount' => $pendingCount,
            'absenceTypes' => collect(AbsenceType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
                'requiresApproval' => $t->requiresApproval(),
                'deductsFromBalance' => $t->deductsFromBalance(),
            ]),
            'absenceStatuses' => collect(AbsenceStatus::cases())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'filters' => $request->only(['status', 'type', 'from', 'to']),
        ]);
    }

    /**
     * Show my absences (for current user)
     */
    public function myAbsences(Request $request, Department $department): Response
    {
        $user = $request->user();

        $absences = Absence::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->with('approvedByUser')
            ->orderBy('start_date', 'desc')
            ->get();

        // Get leave balances
        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('year', Carbon::now()->year)
            ->get()
            ->keyBy('leave_type');

        return Inertia::render('Departments/Schedule/Absences/MyAbsences', [
            'department' => $department,
            'absences' => $absences,
            'balances' => $balances,
            'absenceTypes' => collect(AbsenceType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
                'requiresApproval' => $t->requiresApproval(),
                'deductsFromBalance' => $t->deductsFromBalance(),
            ]),
        ]);
    }

    /**
     * Show form to request absence
     */
    public function create(Department $department): Response
    {
        $balances = LeaveBalance::where('user_id', auth()->id())
            ->where('department_id', $department->id)
            ->where('year', Carbon::now()->year)
            ->get()
            ->keyBy('leave_type');

        // Get department members for interim selection (exclude current user)
        $departmentMembers = $department->users()
            ->where('users.id', '!=', auth()->id())
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'full_name' => $u->first_name . ' ' . $u->last_name,
            ]);

        return Inertia::render('Departments/Schedule/Absences/Create', [
            'department' => $department,
            'balances' => $balances,
            'departmentMembers' => $departmentMembers,
            'absenceTypes' => collect(AbsenceType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
                'requiresApproval' => $t->requiresApproval(),
                'deductsFromBalance' => $t->deductsFromBalance(),
            ]),
        ]);
    }

    /**
     * Store a new absence request
     */
    public function store(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
            'is_half_day' => 'boolean',
            'half_day_period' => 'nullable|string|in:morning,afternoon',
            'interim_user_id' => 'nullable|exists:users,id',
            'interim_notes' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $type = AbsenceType::from($validated['type']);

        // Calculate days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $validated['is_half_day'] ?? false ? 0.5 : $startDate->diffInDays($endDate) + 1;

        // Check leave balance if applicable
        if ($type->deductsFromBalance()) {
            $balance = LeaveBalance::where('user_id', $request->user()->id)
                ->where('department_id', $department->id)
                ->where('year', $startDate->year)
                ->where('leave_type', $type)
                ->first();

            if ($balance && $balance->remaining < $totalDays) {
                return back()->with('error', 'Solde de congés insuffisant.');
            }
        }

        // Handle attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('absences', 'public');
        }

        $absence = Absence::create([
            'user_id' => $request->user()->id,
            'department_id' => $department->id,
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $validated['reason'] ?? null,
            'is_half_day' => $validated['is_half_day'] ?? false,
            'half_day_period' => $validated['half_day_period'] ?? null,
            'interim_user_id' => $validated['interim_user_id'] ?? null,
            'interim_notes' => $validated['interim_notes'] ?? null,
            'status' => $type->requiresApproval() ? AbsenceStatus::PENDING : AbsenceStatus::APPROVED,
            'attachment_path' => $attachmentPath,
        ]);

        // Auto-approve if no approval required
        if (!$type->requiresApproval()) {
            $absence->update([
                'approved_at' => now(),
            ]);

            // Deduct from balance
            if ($type->deductsFromBalance()) {
                $this->deductFromBalance($absence);
            }
        }

        $message = $type->requiresApproval()
            ? 'Demande d\'absence envoyée, en attente d\'approbation.'
            : 'Absence enregistrée.';

        return redirect()
            ->route('departments.absences.my', ['department' => $department])
            ->with('success', $message);
    }

    /**
     * Show form to edit a pending absence
     */
    public function edit(Request $request, Department $department, Absence $absence): Response
    {
        // Only allow editing own pending absences
        if ($absence->user_id !== $request->user()->id) {
            abort(403, 'Vous ne pouvez pas modifier cette demande.');
        }

        if ($absence->status !== AbsenceStatus::PENDING) {
            abort(403, 'Seules les demandes en attente peuvent être modifiées.');
        }

        $balances = LeaveBalance::where('user_id', auth()->id())
            ->where('department_id', $department->id)
            ->where('year', Carbon::now()->year)
            ->get()
            ->keyBy('leave_type');

        // Get department members for interim selection (exclude current user)
        $departmentMembers = $department->users()
            ->where('users.id', '!=', auth()->id())
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'full_name' => $u->first_name . ' ' . $u->last_name,
            ]);

        $absence->load('interimUser');

        return Inertia::render('Departments/Schedule/Absences/Edit', [
            'department' => $department,
            'absence' => $absence,
            'balances' => $balances,
            'departmentMembers' => $departmentMembers,
            'absenceTypes' => collect(AbsenceType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
                'requiresApproval' => $t->requiresApproval(),
                'deductsFromBalance' => $t->deductsFromBalance(),
            ]),
        ]);
    }

    /**
     * Update a pending absence request
     */
    public function update(Request $request, Department $department, Absence $absence): RedirectResponse
    {
        // Only allow editing own pending absences
        if ($absence->user_id !== $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas modifier cette demande.');
        }

        if ($absence->status !== AbsenceStatus::PENDING) {
            return back()->with('error', 'Seules les demandes en attente peuvent être modifiées.');
        }

        $validated = $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
            'is_half_day' => 'boolean',
            'half_day_period' => 'nullable|string|in:morning,afternoon',
            'interim_user_id' => 'nullable|exists:users,id',
            'interim_notes' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $type = AbsenceType::from($validated['type']);

        // Calculate days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $validated['is_half_day'] ?? false ? 0.5 : $startDate->diffInDays($endDate) + 1;

        // Check leave balance if applicable
        if ($type->deductsFromBalance()) {
            $balance = LeaveBalance::where('user_id', $request->user()->id)
                ->where('department_id', $department->id)
                ->where('year', $startDate->year)
                ->where('leave_type', $type)
                ->first();

            if ($balance && $balance->remaining < $totalDays) {
                return back()->with('error', 'Solde de congés insuffisant.');
            }
        }

        // Handle attachment
        $attachmentPath = $absence->document_path;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('absences', 'public');
        }

        $absence->update([
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => $totalDays,
            'reason' => $validated['reason'] ?? null,
            'is_half_day_start' => $validated['is_half_day'] ?? false,
            'interim_user_id' => $validated['interim_user_id'] ?? null,
            'interim_notes' => $validated['interim_notes'] ?? null,
            'document_path' => $attachmentPath,
        ]);

        return redirect()
            ->route('departments.absences.my', ['department' => $department])
            ->with('success', 'Demande modifiée avec succès.');
    }

    /**
     * Show a specific absence
     */
    public function show(Department $department, Absence $absence): Response
    {
        $this->authorize('view', $department);

        $absence->load(['user', 'approvedByUser']);

        return Inertia::render('Departments/Schedule/Absences/Show', [
            'department' => $department,
            'absence' => $absence,
        ]);
    }

    /**
     * Approve an absence request
     */
    public function approve(Request $request, Department $department, Absence $absence): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($absence->status !== AbsenceStatus::PENDING) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $absence->approve($request->user());

        // Deduct from balance if applicable
        if ($absence->type->deductsFromBalance()) {
            $this->deductFromBalance($absence);
        }

        return back()->with('success', 'Demande approuvée.');
    }

    /**
     * Reject an absence request
     */
    public function reject(Request $request, Department $department, Absence $absence): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($absence->status !== AbsenceStatus::PENDING) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $absence->reject($request->user(), $validated['rejection_reason']);

        return back()->with('success', 'Demande refusée.');
    }

    /**
     * Cancel an absence request (by the requester)
     */
    public function cancel(Request $request, Department $department, Absence $absence): RedirectResponse
    {
        if ($absence->user_id !== $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas annuler cette demande.');
        }

        if (!in_array($absence->status, [AbsenceStatus::PENDING, AbsenceStatus::APPROVED])) {
            return back()->with('error', 'Cette demande ne peut plus être annulée.');
        }

        // Restore balance if was approved and deducted
        if ($absence->status === AbsenceStatus::APPROVED && $absence->type->deductsFromBalance()) {
            $this->restoreBalance($absence);
        }

        $absence->update(['status' => AbsenceStatus::CANCELLED]);

        return back()->with('success', 'Demande annulée.');
    }

    /**
     * Delete an absence (admin only)
     */
    public function destroy(Department $department, Absence $absence): RedirectResponse
    {
        $this->authorize('update', $department);

        // Restore balance if was approved and deducted
        if ($absence->status === AbsenceStatus::APPROVED && $absence->type->deductsFromBalance()) {
            $this->restoreBalance($absence);
        }

        $absence->delete();

        return back()->with('success', 'Absence supprimée.');
    }

    /**
     * Get pending requests count (API)
     */
    public function pendingCount(Department $department)
    {
        $this->authorize('view', $department);

        $count = Absence::where('department_id', $department->id)
            ->where('status', AbsenceStatus::PENDING)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get calendar data for absences (API)
     */
    public function calendar(Request $request, Department $department)
    {
        $this->authorize('view', $department);

        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ]);

        $absences = Absence::where('department_id', $department->id)
            ->whereIn('status', [AbsenceStatus::APPROVED, AbsenceStatus::PENDING])
            ->where('end_date', '>=', $validated['start'])
            ->where('start_date', '<=', $validated['end'])
            ->with('user')
            ->get()
            ->map(fn($absence) => [
                'id' => $absence->uuid,
                'title' => $absence->user->full_name . ' - ' . $absence->type->label(),
                'start' => $absence->start_date->toDateString(),
                'end' => $absence->end_date->addDay()->toDateString(), // FullCalendar end is exclusive
                'color' => $absence->type->color(),
                'status' => $absence->status->value,
            ]);

        return response()->json($absences);
    }

    /**
     * Deduct days from leave balance
     */
    protected function deductFromBalance(Absence $absence): void
    {
        $balance = LeaveBalance::firstOrCreate(
            [
                'user_id' => $absence->user_id,
                'department_id' => $absence->department_id,
                'year' => $absence->start_date->year,
                'leave_type' => $absence->type->value,
            ],
            [
                'entitled_days' => 25, // Default
                'taken_days' => 0,
                'pending_days' => 0,
                'carried_over' => 0,
            ]
        );

        $balance->increment('taken_days', $absence->days_count ?? 1);
    }

    /**
     * Restore days to leave balance
     */
    protected function restoreBalance(Absence $absence): void
    {
        $balance = LeaveBalance::where('user_id', $absence->user_id)
            ->where('department_id', $absence->department_id)
            ->where('year', $absence->start_date->year)
            ->where('leave_type', $absence->type->value)
            ->first();

        if ($balance) {
            $balance->decrement('taken_days', min($balance->taken_days, $absence->days_count ?? 1));
        }
    }
}
