<?php

namespace App\Http\Controllers;

use App\Models\DepartmentFormSubmission;
use App\Services\Form\FormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FormSubmissionController extends Controller
{
    public function __construct(
        protected FormService $formService
    ) {}

    /**
     * Display a listing of user's submissions.
     */
    public function index(Request $request)
    {
        $submissions = DepartmentFormSubmission::with(['form.department'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('FormSubmissions/Index', [
            'submissions' => $submissions,
        ]);
    }

    /**
     * Display the specified submission.
     */
    public function show(DepartmentFormSubmission $formSubmission)
    {
        $submissionData = $this->formService->getSubmissionWithFields($formSubmission);

        return Inertia::render('FormSubmissions/Show', [
            'form' => $submissionData['form'],
            'fields' => $submissionData['fields'],
            'submission' => $submissionData['submission'],
        ]);
    }

    /**
     * Show the form for editing the submission (continue filling).
     */
    public function edit(DepartmentFormSubmission $formSubmission)
    {
        if (!$formSubmission->isDraft()) {
            return redirect()->route('form-submissions.show', $formSubmission);
        }

        $submissionData = $this->formService->getSubmissionWithFields($formSubmission);

        return Inertia::render('FormSubmissions/Edit', [
            'form' => $submissionData['form'],
            'fields' => $submissionData['fields'],
            'submission' => $submissionData['submission'],
        ]);
    }

    /**
     * Update the submission data (save draft).
     */
    public function update(Request $request, DepartmentFormSubmission $formSubmission)
    {
        $validated = $request->validate([
            'data' => 'required|array',
            'current_step' => 'nullable|integer|min:0',
        ]);

        try {
            $this->formService->updateSubmission($formSubmission, $validated['data']);

            if (isset($validated['current_step'])) {
                $formSubmission->goToStep($validated['current_step']);
            }

            return back()->with('success', 'Draft saved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit the form.
     */
    public function submit(Request $request, DepartmentFormSubmission $formSubmission)
    {
        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        try {
            // Update with final data
            $this->formService->updateSubmission($formSubmission, $validated['data']);

            // Submit
            $this->formService->submitForm($formSubmission);

            $form = $formSubmission->form;
            $redirectUrl = $form->redirect_url ?? route('form-submissions.show', $formSubmission);
            $successMessage = $form->success_message ?? 'Form submitted successfully.';

            return redirect($redirectUrl)->with('success', $successMessage);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Go to next step in multi-step form.
     */
    public function nextStep(Request $request, DepartmentFormSubmission $formSubmission)
    {
        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        try {
            $this->formService->updateSubmission($formSubmission, $validated['data']);
            $formSubmission->nextStep();

            return back();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Go to previous step in multi-step form.
     */
    public function previousStep(Request $request, DepartmentFormSubmission $formSubmission)
    {
        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        try {
            $this->formService->updateSubmission($formSubmission, $validated['data']);
            $formSubmission->previousStep();

            return back();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified submission.
     */
    public function destroy(DepartmentFormSubmission $formSubmission)
    {
        if (!$formSubmission->isDraft()) {
            return back()->with('error', 'Cannot delete submitted form.');
        }

        $formSubmission->delete();

        return redirect()->route('form-submissions.index')
            ->with('success', 'Draft deleted successfully.');
    }

    /**
     * Validate current step data.
     */
    public function validateStep(Request $request, DepartmentFormSubmission $formSubmission)
    {
        $validated = $request->validate([
            'data' => 'required|array',
            'step' => 'required|integer|min:0',
        ]);

        // Get fields for current step only
        $form = $formSubmission->form;
        $stepFields = $form->fields()->where('step', $validated['step'])->get();

        $errors = [];
        foreach ($stepFields as $field) {
            if (!$field->evaluateConditionalLogic($validated['data'])) {
                continue;
            }

            $rules = $field->getValidationRules();
            $value = $validated['data'][$field->name] ?? null;

            $validator = \Illuminate\Support\Facades\Validator::make(
                [$field->name => $value],
                [$field->name => $rules],
                [],
                [$field->name => $field->label]
            );

            if ($validator->fails()) {
                $errors[$field->name] = $validator->errors()->get($field->name);
            }
        }

        if (!empty($errors)) {
            return response()->json(['valid' => false, 'errors' => $errors], 422);
        }

        return response()->json(['valid' => true]);
    }
}
