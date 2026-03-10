<?php

namespace App\Http\Controllers;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Employee\EmploymentType;
use App\Enums\Employee\PaymentMethod;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view employees')->only(['index', 'show']);
        $this->middleware('can:manage employees')->except(['index', 'show']);
    }

    /**
     * Display a listing of employees.
     */
    public function index(Request $request): Response
    {
        $query = Employee::with(['user', 'department', 'manager.user'])
            ->when($request->filled('search'), function ($q) use ($request): void {
                $q->search($request->search);
            })
            ->when($request->filled('status'), function ($q) use ($request): void {
                $q->where('status', $request->status);
            })
            ->when($request->filled('employment_type'), function ($q) use ($request): void {
                $q->where('employment_type', $request->employment_type);
            })
            ->when($request->filled('department'), function ($q) use ($request): void {
                $q->where('department_id', $request->department);
            })
            ->orderBy('created_at', 'desc');

        $employees = $query->paginate(15)->withQueryString();

        // Transform employees for frontend
        $employees->getCollection()->transform(fn($employee): array => [
            'id' => $employee->id,
            'uuid' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->full_name,
            'position' => $employee->position,
            'job_title' => $employee->job_title,
            'status' => [
                'value' => $employee->status->value,
                'label' => $employee->status->label(),
                'color' => $employee->status->color(),
            ],
            'employment_type' => [
                'value' => $employee->employment_type->value,
                'label' => $employee->employment_type->label(),
                'color' => $employee->employment_type->color(),
            ],
            'department' => $employee->department ? [
                'id' => $employee->department->id,
                'uuid' => $employee->department->uuid,
                'name' => $employee->department->name,
            ] : null,
            'hire_date' => $employee->hire_date?->format('Y-m-d'),
            'years_of_service' => $employee->years_of_service,
            'user' => $employee->user ? [
                'id' => $employee->user->id,
                'uuid' => $employee->user->uuid,
                'name' => $employee->user->full_name,
                'email' => $employee->user->email,
                'avatar' => $employee->user->avatar,
            ] : null,
            'avatar' => $employee->avatar,
        ]);

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'status', 'employment_type', 'department']),
            'statuses' => collect(EmployeeStatus::cases())->map(fn($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'employmentTypes' => collect(EmploymentType::cases())->map(fn($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
            ]),
            'departments' => Department::active()->orderBy('name')->get(['id', 'uuid', 'name']),
            'stats' => [
                'total' => Employee::count(),
                'active' => Employee::active()->count(),
                'on_leave' => Employee::onLeave()->count(),
                'new_this_month' => Employee::where('hire_date', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): Response
    {
        return Inertia::render('Employees/Create', [
            'users' => User::doesntHave('employee')
                ->orderBy('first_name')
                ->get(['id', 'uuid', 'first_name', 'last_name', 'email']),
            'departments' => Department::active()->orderBy('name')->get(['id', 'uuid', 'name']),
            'managers' => Employee::active()->with('user')->get()->map(fn($e): array => [
                'id' => $e->id,
                'uuid' => $e->uuid,
                'name' => $e->full_name,
                'position' => $e->position,
            ]),
            'statuses' => collect(EmployeeStatus::cases())->map(fn($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'employmentTypes' => collect(EmploymentType::cases())->map(fn($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn($p): array => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:employees,user_id',
            'department_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'position' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:100',
            'social_security_number' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'personal_email' => 'nullable|email|max:255',
            'work_phone' => 'nullable|string|max:50',
            'personal_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'hire_date' => 'nullable|date',
            'probation_end_date' => 'nullable|date|after_or_equal:hire_date',
            'contract_end_date' => 'nullable|date|after_or_equal:hire_date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'bank_name' => 'nullable|string|max:255',
            'bank_iban' => 'nullable|string|max:50',
            'bank_bic' => 'nullable|string|max:20',
            'weekly_hours' => 'nullable|numeric|min:0|max:168',
            'working_days' => 'nullable|array',
            'working_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'default_start_time' => 'nullable|date_format:H:i',
            'default_end_time' => 'nullable|date_format:H:i|after:default_start_time',
            'annual_leave_days' => 'nullable|integer|min:0|max:365',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:100',
            'notes' => 'nullable|string|max:5000',
            'internal_notes' => 'nullable|string|max:5000',
            'avatar' => 'nullable|image|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('employees/avatars', 'public');
        }

        // Set default remaining leave days
        if (isset($validated['annual_leave_days']) && !isset($validated['remaining_leave_days'])) {
            $validated['remaining_leave_days'] = $validated['annual_leave_days'];
        }

        $employee = Employee::create($validated);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Employé créé avec succès.');
    }

    /**
     * Display the specified employee.
     */
    public function show(Request $request, Employee $employee): Response
    {
        $employee->load([
            'user',
            'department',
            'manager.user',
            'subordinates.user',
        ]);

        return Inertia::render('Employees/Show', [
            'employee' => [
                'id' => $employee->id,
                'uuid' => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
                'position' => $employee->position,
                'job_title' => $employee->job_title,
                'birth_date' => $employee->birth_date?->format('Y-m-d'),
                'age' => $employee->age,
                'nationality' => $employee->nationality,
                'social_security_number' => $employee->social_security_number,
                'tax_id' => $employee->tax_id,
                'personal_email' => $employee->personal_email,
                'work_phone' => $employee->work_phone,
                'personal_phone' => $employee->personal_phone,
                'address' => $employee->address,
                'city' => $employee->city,
                'postal_code' => $employee->postal_code,
                'country' => $employee->country,
                'emergency_contact_name' => $employee->emergency_contact_name,
                'emergency_contact_phone' => $employee->emergency_contact_phone,
                'emergency_contact_relationship' => $employee->emergency_contact_relationship,
                'status' => [
                    'value' => $employee->status->value,
                    'label' => $employee->status->label(),
                    'color' => $employee->status->color(),
                ],
                'employment_type' => [
                    'value' => $employee->employment_type->value,
                    'label' => $employee->employment_type->label(),
                    'color' => $employee->employment_type->color(),
                ],
                'hire_date' => $employee->hire_date?->format('Y-m-d'),
                'probation_end_date' => $employee->probation_end_date?->format('Y-m-d'),
                'contract_end_date' => $employee->contract_end_date?->format('Y-m-d'),
                'termination_date' => $employee->termination_date?->format('Y-m-d'),
                'termination_reason' => $employee->termination_reason,
                'is_on_probation' => $employee->is_on_probation,
                'years_of_service' => $employee->years_of_service,
                'remaining_probation_days' => $employee->remaining_probation_days,
                'contract_remaining_days' => $employee->contract_remaining_days,
                'hourly_rate' => $employee->hourly_rate,
                'monthly_salary' => $employee->monthly_salary,
                'payment_method' => $employee->payment_method ? [
                    'value' => $employee->payment_method->value,
                    'label' => $employee->payment_method->label(),
                ] : null,
                'bank_name' => $employee->bank_name,
                'bank_iban' => $employee->bank_iban,
                'bank_bic' => $employee->bank_bic,
                'weekly_hours' => $employee->weekly_hours,
                'working_days' => $employee->working_days,
                'default_start_time' => $employee->default_start_time,
                'default_end_time' => $employee->default_end_time,
                'annual_leave_days' => $employee->annual_leave_days,
                'remaining_leave_days' => $employee->remaining_leave_days,
                'sick_days_taken' => $employee->sick_days_taken,
                'skills' => $employee->skills,
                'certifications' => $employee->certifications,
                'languages' => $employee->languages,
                'avatar' => $employee->avatar ? Storage::url($employee->avatar) : null,
                'contract_document' => $employee->contract_document,
                'id_document' => $employee->id_document,
                'notes' => $employee->notes,
                'internal_notes' => $employee->internal_notes,
                'created_at' => $employee->created_at->format('Y-m-d H:i'),
                'updated_at' => $employee->updated_at->format('Y-m-d H:i'),
                'user' => $employee->user ? [
                    'id' => $employee->user->id,
                    'uuid' => $employee->user->uuid,
                    'name' => $employee->user->full_name,
                    'email' => $employee->user->email,
                    'avatar' => $employee->user->avatar,
                ] : null,
                'department' => $employee->department ? [
                    'id' => $employee->department->id,
                    'uuid' => $employee->department->uuid,
                    'name' => $employee->department->name,
                ] : null,
                'manager' => $employee->manager ? [
                    'id' => $employee->manager->id,
                    'uuid' => $employee->manager->uuid,
                    'name' => $employee->manager->full_name,
                    'position' => $employee->manager->position,
                ] : null,
                'subordinates' => $employee->subordinates->map(fn($s): array => [
                    'id' => $s->id,
                    'uuid' => $s->uuid,
                    'name' => $s->full_name,
                    'position' => $s->position,
                ]),
            ],
            'canManage' => $request->user()?->can('manage employees'),
        ]);
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee): Response
    {
        $employee->load(['user', 'department', 'manager']);

        return Inertia::render('Employees/Edit', [
            'employee' => [
                'id' => $employee->id,
                'uuid' => $employee->uuid,
                'user_id' => $employee->user_id,
                'department_id' => $employee->department_id,
                'manager_id' => $employee->manager_id,
                'employee_number' => $employee->employee_number,
                'position' => $employee->position,
                'job_title' => $employee->job_title,
                'birth_date' => $employee->birth_date?->format('Y-m-d'),
                'nationality' => $employee->nationality,
                'social_security_number' => $employee->social_security_number,
                'tax_id' => $employee->tax_id,
                'personal_email' => $employee->personal_email,
                'work_phone' => $employee->work_phone,
                'personal_phone' => $employee->personal_phone,
                'address' => $employee->address,
                'city' => $employee->city,
                'postal_code' => $employee->postal_code,
                'country' => $employee->country,
                'emergency_contact_name' => $employee->emergency_contact_name,
                'emergency_contact_phone' => $employee->emergency_contact_phone,
                'emergency_contact_relationship' => $employee->emergency_contact_relationship,
                'status' => $employee->status->value,
                'employment_type' => $employee->employment_type->value,
                'hire_date' => $employee->hire_date?->format('Y-m-d'),
                'probation_end_date' => $employee->probation_end_date?->format('Y-m-d'),
                'contract_end_date' => $employee->contract_end_date?->format('Y-m-d'),
                'termination_date' => $employee->termination_date?->format('Y-m-d'),
                'termination_reason' => $employee->termination_reason,
                'hourly_rate' => $employee->hourly_rate,
                'monthly_salary' => $employee->monthly_salary,
                'payment_method' => $employee->payment_method?->value,
                'bank_name' => $employee->bank_name,
                'bank_iban' => $employee->bank_iban,
                'bank_bic' => $employee->bank_bic,
                'weekly_hours' => $employee->weekly_hours,
                'working_days' => $employee->working_days,
                'default_start_time' => $employee->default_start_time,
                'default_end_time' => $employee->default_end_time,
                'annual_leave_days' => $employee->annual_leave_days,
                'remaining_leave_days' => $employee->remaining_leave_days,
                'skills' => $employee->skills,
                'languages' => $employee->languages,
                'notes' => $employee->notes,
                'internal_notes' => $employee->internal_notes,
                'avatar' => $employee->avatar ? Storage::url($employee->avatar) : null,
                'user' => $employee->user ? [
                    'id' => $employee->user->id,
                    'name' => $employee->user->full_name,
                    'email' => $employee->user->email,
                ] : null,
            ],
            'departments' => Department::active()->orderBy('name')->get(['id', 'uuid', 'name']),
            'managers' => Employee::active()
                ->where('id', '!=', $employee->id)
                ->with('user')
                ->get()
                ->map(fn($e): array => [
                    'id' => $e->id,
                    'uuid' => $e->uuid,
                    'name' => $e->full_name,
                    'position' => $e->position,
                ]),
            'statuses' => collect(EmployeeStatus::cases())->map(fn($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'employmentTypes' => collect(EmploymentType::cases())->map(fn($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn($p): array => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
        ]);
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'manager_id' => ['nullable', 'exists:employees,id', Rule::notIn([$employee->id])],
            'position' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:100',
            'social_security_number' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'personal_email' => 'nullable|email|max:255',
            'work_phone' => 'nullable|string|max:50',
            'personal_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'hire_date' => 'nullable|date',
            'probation_end_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'termination_reason' => 'nullable|string|max:500',
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'bank_name' => 'nullable|string|max:255',
            'bank_iban' => 'nullable|string|max:50',
            'bank_bic' => 'nullable|string|max:20',
            'weekly_hours' => 'nullable|numeric|min:0|max:168',
            'working_days' => 'nullable|array',
            'working_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'default_start_time' => 'nullable|date_format:H:i',
            'default_end_time' => 'nullable|date_format:H:i',
            'annual_leave_days' => 'nullable|integer|min:0|max:365',
            'remaining_leave_days' => 'nullable|integer|min:0',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:100',
            'notes' => 'nullable|string|max:5000',
            'internal_notes' => 'nullable|string|max:5000',
            'avatar' => 'nullable|image|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($employee->avatar) {
                Storage::disk('public')->delete($employee->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('employees/avatars', 'public');
        }

        $employee->update($validated);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Employé mis à jour avec succès.');
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        // Delete avatar if exists
        if ($employee->avatar) {
            Storage::disk('public')->delete($employee->avatar);
        }

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employé supprimé avec succès.');
    }

    /**
     * Terminate an employee.
     */
    public function terminate(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'termination_date' => 'required|date',
            'termination_reason' => 'required|string|max:500',
        ]);

        $employee->terminate(
            $validated['termination_reason'],
            \Carbon\Carbon::parse($validated['termination_date'])
        );

        return back()->with('success', 'Employé terminé avec succès.');
    }

    /**
     * Reactivate a terminated or inactive employee.
     */
    public function activate(Employee $employee): RedirectResponse
    {
        $employee->activate();

        return back()->with('success', 'Employé réactivé avec succès.');
    }

    /**
     * Set employee on leave.
     */
    public function setOnLeave(Employee $employee): RedirectResponse
    {
        $employee->setOnLeave();

        return back()->with('success', 'Employé mis en congé.');
    }

    /**
     * Reset annual leave for employee.
     */
    public function resetLeave(Employee $employee): RedirectResponse
    {
        $employee->resetAnnualLeave();

        return back()->with('success', 'Congés annuels réinitialisés.');
    }

    /**
     * Export employees list.
     */
    public function export(Request $request)
    {
        $employees = Employee::with(['user', 'department'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->get();

        // Return as JSON for now, can be extended to Excel/CSV
        return response()->json([
            'employees' => $employees->map(fn($e): array => [
                'employee_number' => $e->employee_number,
                'name' => $e->full_name,
                'email' => $e->user?->email,
                'position' => $e->position,
                'department' => $e->department?->name,
                'status' => $e->status->label(),
                'employment_type' => $e->employment_type->label(),
                'hire_date' => $e->hire_date?->format('Y-m-d'),
            ]),
        ]);
    }
}
