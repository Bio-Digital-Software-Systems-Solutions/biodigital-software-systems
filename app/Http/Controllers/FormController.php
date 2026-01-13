<?php

namespace App\Http\Controllers;

use App\Enums\Form\FormFieldType;
use App\Enums\Form\FormStatus;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\DepartmentFormSubmission;
use App\Models\FormField;
use App\Services\Form\FormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $query->where(function ($q) use ($search) {
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
        $form->load(['department', 'creator']);

        return Inertia::render('Forms/Show', [
            'form' => $form,
            'fields' => $formData['fields'],
            'submissionCount' => $form->getSubmissionCount(),
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
        if ($form->submissions()->exists()) {
            return back()->with('error', 'Cannot delete form with existing submissions.');
        }

        $form->delete();
        return redirect()->route('forms.index')
            ->with('success', 'Form deleted successfully.');
    }

    /**
     * Save form fields.
     */
    public function saveFields(Request $request, DepartmentForm $form)
    {
        $validated = $request->validate([
            'fields' => 'required|array',
        ]);

        // Delete existing fields
        $form->fields()->delete();

        // Create new fields
        $this->createFieldsRecursively($form->id, $validated['fields'], null);

        // Return JSON response for fetch requests
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Form fields saved successfully.',
            ]);
        }

        return back()->with('success', 'Form fields saved successfully.');
    }

    /**
     * Create fields recursively.
     */
    protected function createFieldsRecursively(int $formId, array $fields, ?int $parentId): void
    {
        foreach ($fields as $index => $fieldData) {
            $children = $fieldData['children'] ?? [];
            unset($fieldData['children'], $fieldData['id']);

            $field = FormField::create(array_merge($fieldData, [
                'form_id' => $formId,
                'parent_field_id' => $parentId,
                'order' => $index,
            ]));

            if (!empty($children)) {
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
        if (!$form->isPublished()) {
            abort(404, 'Form not found.');
        }

        $formData = $this->formService->getFormWithFields($form);

        return Inertia::render('Forms/Render', [
            'form' => $form,
            'fields' => $formData['fields'],
        ]);
    }

    /**
     * Start a new form submission.
     */
    public function startSubmission(DepartmentForm $form)
    {
        try {
            $submission = $this->formService->startSubmission($form, Auth::id());
            return redirect()->route('form-submissions.edit', $submission);
        } catch (\Exception $e) {
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
            ->header('Content-Disposition', 'attachment; filename="' . $form->name . '.json"');
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

        if (!$formData) {
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
        $submissions = $form->submissions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Forms/Submissions', [
            'form' => $form,
            'submissions' => $submissions,
        ]);
    }
}
