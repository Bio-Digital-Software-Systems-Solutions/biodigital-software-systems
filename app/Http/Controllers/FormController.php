<?php

namespace App\Http\Controllers;

use App\Enums\Form\FormFieldType;
use App\Enums\Form\FormStatus;
use App\Enums\Form\SubmissionStatus;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormField;
use App\Models\FormShareLink;
use App\Services\Form\FormService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FormController extends Controller
{
    public function __construct(
        protected FormService $formService
    ) {}

    /**
     * Display a listing of forms.
     */
    public function index(Request $request)
    {
        $query = DepartmentForm::with(['department', 'creator'])
            ->withCount(['fields', 'submissions']);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $forms = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Forms/Index', [
            'forms' => $forms,
            'departments' => Department::active()->ordered()->get(),
            'statuses' => FormStatus::toSelectOptions(),
            'filters' => $request->only(['department_id', 'status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new form.
     */
    public function create(Request $request)
    {
        return Inertia::render('Forms/Create', [
            'departments' => Department::active()->ordered()->get(),
            'fieldTypes' => FormFieldType::groupedOptions(),
            'departmentId' => $request->department_id,
        ]);
    }

    /**
     * Store a newly created form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_multi_step' => 'boolean',
            'settings' => 'nullable|array',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = FormStatus::DRAFT;

        $form = $this->formService->createForm($validated);

        return redirect()->route('forms.edit', $form)
            ->with('success', 'Form created successfully.');
    }

    /**
     * Display the specified form.
     */
    public function show(DepartmentForm $form)
    {
        $formData = $this->formService->getFormWithFields($form);
        $form->load(['department', 'creator', 'submissions.user']);

        // Get all submissions (already eager loaded)
        $allSubmissions = $form->submissions;

        // Get recent submissions from the loaded collection
        $recentSubmissions = $allSubmissions->sortByDesc('created_at')->take(5)->values();

        // Get stats from the loaded collection
        $stats = [
            'total_submissions' => $allSubmissions->count(),
            'completed_submissions' => $allSubmissions->where('status', 'completed')->count(),
            'pending_submissions' => $allSubmissions->whereIn('status', ['draft', 'submitted', 'processing'])->count(),
        ];

        return Inertia::render('Forms/Show', [
            'form' => $form,
            'fields' => $formData['fields'],
            'submissionCount' => $allSubmissions->count(),
            'recentSubmissions' => $recentSubmissions,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing (form builder).
     */
    public function edit(DepartmentForm $form)
    {
        $formData = $this->formService->getFormWithFields($form);

        return Inertia::render('Forms/Builder', [
            'form' => $form,
            'fields' => $formData['fields'],
            'fieldTypes' => FormFieldType::groupedOptions(),
        ]);
    }

    /**
     * Update the specified form.
     */
    public function update(Request $request, DepartmentForm $form)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_multi_step' => 'boolean',
            'settings' => 'nullable|array',
            'validation_rules' => 'nullable|array',
            'conditional_logic' => 'nullable|array',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
        ]);

        $this->formService->updateForm($form, $validated);

        return back()->with('success', 'Form updated successfully.');
    }

    /**
     * Publish the form.
     */
    public function publish(DepartmentForm $form)
    {
        try {
            $this->formService->publishForm($form);

            return back()->with('success', 'Form published successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unpublish the form.
     */
    public function unpublish(DepartmentForm $form)
    {
        $form->unpublish();

        return back()->with('success', 'Form unpublished successfully.');
    }

    /**
     * Archive the form.
     */
    public function archive(DepartmentForm $form)
    {
        $form->archive();

        return back()->with('success', 'Form archived successfully.');
    }

    /**
     * Duplicate the form.
     */
    public function duplicate(DepartmentForm $form)
    {
        $newForm = $form->duplicate();

        return redirect()->route('forms.edit', $newForm)
            ->with('success', 'Form duplicated successfully.');
    }

    /**
     * Remove the specified form.
     */
    public function destroy(DepartmentForm $form)
    {
        try {
            if ($form->submissions()->exists()) {
                return back()->with('error', 'Cannot delete form with existing submissions.');
            }

            $form->delete();

            return redirect()->route('forms.index')
                ->with('success', 'Form deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Form deletion failed: '.$e->getMessage(), [
                'form_id' => $form->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erreur lors de la suppression du formulaire: '.$e->getMessage());
        }
    }

    /**
     * Save form fields.
     */
    public function saveFields(Request $request, DepartmentForm $form)
    {
        // Get raw fields data first (before validation strips extra fields)
        $rawFields = $request->input('fields', []);

        // Validate top-level fields structure
        $request->validate([
            'fields' => 'present|array',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => 'required|string',
        ]);

        try {
            DB::transaction(function () use ($form, $rawFields): void {
                // Delete existing fields (cascade will handle nested fields)
                $form->fields()->delete();

                // Create new fields using raw data to preserve children
                if (! empty($rawFields)) {
                    $this->createFieldsRecursively($form->id, $rawFields, null);
                }
            });

            // Refresh the count after transaction
            $fieldsCount = $form->fields()->count();

            // Return JSON response for fetch requests
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Form fields saved successfully.',
                    'fields_count' => $fieldsCount,
                ]);
            }

            return back()->with('success', 'Form fields saved successfully.');
        } catch (\Exception $e) {
            \Log::error('Error saving form fields', [
                'form_id' => $form->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error saving form fields: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Error saving form fields.');
        }
    }

    /**
     * Create fields recursively.
     */
    protected function createFieldsRecursively(int $formId, array $fields, ?int $parentId): void
    {
        // Allowed fields from the FormField model (exclude uuid - let model generate it)
        $allowedFields = [
            'name', 'label', 'description', 'type', 'order', 'step',
            'placeholder', 'help_text', 'default_value', 'options',
            'validation', 'conditional_logic', 'config', 'is_required',
            'is_readonly', 'is_hidden', 'column_span',
        ];

        foreach ($fields as $index => $fieldData) {
            // Extract children before filtering
            $children = [];
            if (isset($fieldData['children']) && is_array($fieldData['children'])) {
                $children = $fieldData['children'];
            }

            // Remove non-allowed fields (including 'children', 'id', 'uuid')
            $filteredData = array_intersect_key($fieldData, array_flip($allowedFields));

            // Ensure required fields
            $filteredData['form_id'] = $formId;
            $filteredData['parent_field_id'] = $parentId;
            $filteredData['order'] = $index;

            // Set default step if not provided
            if (! isset($filteredData['step'])) {
                $filteredData['step'] = 1;
            }

            // Handle JSON fields - ensure they are arrays
            foreach (['options', 'validation', 'conditional_logic', 'config'] as $jsonField) {
                if (isset($filteredData[$jsonField]) && is_string($filteredData[$jsonField])) {
                    $filteredData[$jsonField] = json_decode($filteredData[$jsonField], true);
                }
            }

            // Handle type - ensure it's a string value if it's an object
            if (isset($filteredData['type'])) {
                $filteredData['type'] = is_array($filteredData['type'])
                    ? ($filteredData['type']['value'] ?? $filteredData['type'])
                    : $filteredData['type'];
            }

            $field = FormField::create($filteredData);

            // Recursively create children
            if ($children !== []) {
                $this->createFieldsRecursively($formId, $children, $field->id);
            }
        }
    }

    /**
     * Preview the form.
     */
    public function preview(DepartmentForm $form)
    {
        $formData = $this->formService->getFormWithFields($form);

        return Inertia::render('Forms/Preview', [
            'form' => $form,
            'fields' => $formData['fields'],
        ]);
    }

    /**
     * Render public form for submission.
     */
    public function renderForm(DepartmentForm $form)
    {
        if (! $form->isPublished()) {
            abort(404, 'Form not found.');
        }

        $formData = $this->formService->getFormWithFields($form);

        return Inertia::render('Forms/Render', [
            'form' => $form,
            'fields' => $formData['fields'],
        ]);
    }

    /**
     * Start and submit a form submission directly.
     */
    public function startSubmission(Request $request, DepartmentForm $form)
    {
        try {
            // Create the submission
            $submission = $this->formService->startSubmission($form, Auth::id());

            // If data is provided, save it and submit
            if ($request->has('data') || $request->except(['_token'])) {
                $data = $request->has('data') ? $request->input('data') : $request->except(['_token']);
                $submission->data = $data;
                $submission->status = SubmissionStatus::SUBMITTED;
                $submission->submitted_at = now();
                $submission->save();

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $form->success_message ?? 'Formulaire soumis avec succès!',
                        'redirect_url' => $form->redirect_url,
                    ]);
                }

                return redirect()->back()->with('success', $form->success_message ?? 'Formulaire soumis avec succès!');
            }

            // If no data, redirect to edit page
            return redirect()->route('form-submissions.edit', $submission);
        } catch (\Exception $e) {
            \Log::error('Form submission error', [
                'form_id' => $form->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la soumission: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export form to JSON.
     */
    public function export(DepartmentForm $form)
    {
        $data = $this->formService->exportForm($form);

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="'.$form->name.'.json"');
    }

    /**
     * Import form from JSON.
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'file' => 'required|file|mimes:json',
        ]);

        $content = file_get_contents($request->file('file')->path());
        $formData = json_decode($content, true);

        if (! $formData) {
            return back()->with('error', 'Invalid JSON file.');
        }

        $form = $this->formService->importForm(
            $validated['department_id'],
            Auth::id(),
            $formData
        );

        return redirect()->route('forms.edit', $form)
            ->with('success', 'Form imported successfully.');
    }

    /**
     * List form submissions.
     */
    public function submissions(Request $request, DepartmentForm $form)
    {
        $user = Auth::user();
        $submissions = $form->submissions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Check if user can manage submissions (view all and delete)
        $canManageSubmissions = $user->hasAnyRole(['admin', 'super-admin']) || $user->can('manage forms');

        return Inertia::render('Forms/Submissions', [
            'form' => $form,
            'submissions' => $submissions,
            'canManageSubmissions' => $canManageSubmissions,
            'currentUserId' => $user->id,
        ]);
    }

    /**
     * Generate a secure share link for a form.
     */
    public function generateShareLink(Request $request, DepartmentForm $form)
    {
        // Log the request for debugging
        \Log::info('generateShareLink called', [
            'form_id' => $form->id,
            'form_status' => $form->status,
            'form_status_value' => $form->status?->value ?? $form->getRawOriginal('status'),
        ]);

        // Ensure form is published - handle both enum and string comparison
        $isPublished = $form->status === FormStatus::PUBLISHED
            || $form->status?->value === FormStatus::PUBLISHED->value
            || $form->getRawOriginal('status') === FormStatus::PUBLISHED->value;

        if (! $isPublished) {
            return response()->json([
                'error' => 'Seuls les formulaires publiés peuvent être partagés.',
                'status' => $form->status?->value ?? $form->getRawOriginal('status'),
            ], 422);
        }

        // Validation - let Laravel handle validation errors (returns 422 automatically)
        $validated = $request->validate([
            'expires_in_hours' => 'nullable|integer|min:1|max:8760',
            'max_uses' => 'nullable|integer|min:1|max:10000',
        ]);

        try {
            $expiresInHours = $validated['expires_in_hours'] ?? 24;
            $maxUses = $validated['max_uses'] ?? null;

            $shareLink = FormShareLink::createForForm(
                $form,
                Auth::id(),
                (int) $expiresInHours,
                $maxUses ? (int) $maxUses : null
            );

            $url = $shareLink->getUrl();

            // Generate QR code with error handling
            $qrCodeBase64 = null;
            try {
                $qrCodeService = new QrCodeService;
                $qrCodeBase64 = $qrCodeService->generateBase64($url);
            } catch (\Throwable $e) {
                \Log::error('QR Code generation failed: '.$e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue without QR code - it's not critical
            }

            return response()->json([
                'url' => $url,
                'token' => $shareLink->token,
                'expires_at' => $shareLink->expires_at->toIso8601String(),
                'max_uses' => $shareLink->max_uses,
                'qr_code' => $qrCodeBase64,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Share link generation failed: '.$e->getMessage(), [
                'form_id' => $form->id ?? 'unknown',
                'exception_class' => $e::class,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la génération du lien de partage.',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Render a form via secure share link.
     */
    public function renderSharedForm(string $token)
    {
        $shareLink = FormShareLink::findValidByToken($token);

        if (!$shareLink instanceof \App\Models\FormShareLink) {
            return Inertia::render('Forms/SharedExpired', [
                'message' => 'Ce lien de partage est invalide ou a expiré.',
            ]);
        }

        $form = $shareLink->form;

        // Ensure form is still published
        if ($form->status !== FormStatus::PUBLISHED) {
            return Inertia::render('Forms/SharedExpired', [
                'message' => 'Ce formulaire n\'est plus disponible.',
            ]);
        }

        // Increment use count
        $shareLink->incrementUseCount();

        // Get form with fields structure
        $formData = $this->formService->getFormWithFields($form);

        return Inertia::render('Forms/Render', [
            'form' => $form,
            'fields' => $formData['fields'],
            'sharedToken' => $token, // Pass token for submission
        ]);
    }

    /**
     * Submit a form via secure share link (anonymous submission).
     * This endpoint does not require authentication.
     */
    public function submitSharedForm(Request $request, string $token)
    {
        try {
            // Validate the share link
            $shareLink = FormShareLink::findValidByToken($token);

            if (!$shareLink instanceof \App\Models\FormShareLink) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce lien de partage est invalide ou a expiré.',
                ], 403);
            }

            $form = $shareLink->form;

            // Ensure form is still published
            if ($form->status !== FormStatus::PUBLISHED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce formulaire n\'est plus disponible.',
                ], 403);
            }

            // Get the authenticated user ID if logged in, null otherwise
            $userId = Auth::id();

            // Create the submission (anonymous if not logged in)
            $submission = $this->formService->startSubmission($form, $userId, [], $token);

            // Save the submitted data
            $data = $request->input('data', $request->except(['_token']));
            $submission->data = $data;
            $submission->status = SubmissionStatus::SUBMITTED;
            $submission->submitted_at = now();
            $submission->save();

            return response()->json([
                'success' => true,
                'message' => $form->success_message ?? 'Formulaire soumis avec succès!',
                'redirect_url' => $form->redirect_url,
                'submission_id' => $submission->uuid,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Shared form submission error', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la soumission du formulaire.',
            ], 500);
        }
    }
}
