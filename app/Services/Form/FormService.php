<?php

namespace App\Services\Form;

use App\Enums\Form\FormStatus;
use App\Enums\Form\SubmissionStatus;
use App\Models\DepartmentForm;
use App\Models\DepartmentFormSubmission;
use App\Models\FormField;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FormService
{
    /**
     * Create a new form.
     */
    public function createForm(array $data): DepartmentForm
    {
        return DepartmentForm::create($data);
    }

    /**
     * Update a form.
     */
    public function updateForm(DepartmentForm $form, array $data): DepartmentForm
    {
        if ($form->isPublished()) {
            // Create a new version instead of updating
            return $this->createNewVersion($form, $data);
        }

        $form->update($data);
        return $form->fresh();
    }

    /**
     * Create a new version of a form.
     */
    public function createNewVersion(DepartmentForm $form, array $data): DepartmentForm
    {
        return DB::transaction(function () use ($form, $data) {
            // Archive the current form
            $form->update(['status' => FormStatus::ARCHIVED]);

            // Create new version
            $newForm = $form->duplicate();
            $newForm->update(array_merge($data, [
                'version' => $form->version + 1,
            ]));

            return $newForm;
        });
    }

    /**
     * Add a field to a form.
     */
    public function addField(DepartmentForm $form, array $fieldData): FormField
    {
        // Get the max order for the form or parent field
        $parentId = $fieldData['parent_field_id'] ?? null;
        $maxOrder = FormField::where('form_id', $form->id)
            ->where('parent_field_id', $parentId)
            ->max('order') ?? -1;

        $fieldData['form_id'] = $form->id;
        $fieldData['order'] = $maxOrder + 1;

        return FormField::create($fieldData);
    }

    /**
     * Update a field.
     */
    public function updateField(FormField $field, array $data): FormField
    {
        $field->update($data);
        return $field->fresh();
    }

    /**
     * Remove a field.
     */
    public function removeField(FormField $field): void
    {
        DB::transaction(function () use ($field) {
            // Delete child fields first
            $field->children()->delete();
            $field->delete();

            // Reorder remaining fields
            $this->reorderFields($field->form_id, $field->parent_field_id);
        });
    }

    /**
     * Reorder fields after deletion.
     */
    protected function reorderFields(int $formId, ?int $parentFieldId): void
    {
        $fields = FormField::where('form_id', $formId)
            ->where('parent_field_id', $parentFieldId)
            ->orderBy('order')
            ->get();

        $order = 0;
        foreach ($fields as $field) {
            $field->update(['order' => $order++]);
        }
    }

    /**
     * Move a field to a new position.
     */
    public function moveField(FormField $field, int $newOrder, ?int $newParentId = null): void
    {
        DB::transaction(function () use ($field, $newOrder, $newParentId) {
            $oldOrder = $field->order;
            $oldParentId = $field->parent_field_id;

            // If changing parent, reorder old parent's children
            if ($newParentId !== $oldParentId) {
                $this->reorderFields($field->form_id, $oldParentId);
            }

            // Make room at new position
            FormField::where('form_id', $field->form_id)
                ->where('parent_field_id', $newParentId)
                ->where('order', '>=', $newOrder)
                ->increment('order');

            // Move the field
            $field->update([
                'order' => $newOrder,
                'parent_field_id' => $newParentId,
            ]);

            // Clean up gaps
            $this->reorderFields($field->form_id, $newParentId);
        });
    }

    /**
     * Duplicate a field.
     */
    public function duplicateField(FormField $field): FormField
    {
        $newField = $field->replicate(['uuid']);
        $newField->name = $field->name . '_copy';
        $newField->label = $field->label . ' (Copy)';
        $newField->order = $field->order + 1;
        $newField->save();

        // Shift other fields down
        FormField::where('form_id', $field->form_id)
            ->where('parent_field_id', $field->parent_field_id)
            ->where('id', '!=', $newField->id)
            ->where('order', '>=', $newField->order)
            ->increment('order');

        return $newField;
    }

    /**
     * Publish a form.
     */
    public function publishForm(DepartmentForm $form): DepartmentForm
    {
        if ($form->fields()->count() === 0) {
            throw new \Exception('Cannot publish a form without fields.');
        }

        return $form->publish();
    }

    /**
     * Start a new form submission.
     */
    public function startSubmission(DepartmentForm $form, int $userId, array $initialData = []): DepartmentFormSubmission
    {
        if (!$form->isPublished()) {
            throw new \Exception('Cannot submit to an unpublished form.');
        }

        return DepartmentFormSubmission::create([
            'form_id' => $form->id,
            'user_id' => $userId,
            'status' => SubmissionStatus::DRAFT,
            'data' => $initialData,
            'current_step' => 0,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Update submission data.
     */
    public function updateSubmission(DepartmentFormSubmission $submission, array $data): DepartmentFormSubmission
    {
        if (!$submission->isDraft()) {
            throw new \Exception('Cannot update a submitted form.');
        }

        $submission->updateData($data);
        return $submission->fresh();
    }

    /**
     * Submit a form submission.
     */
    public function submitForm(DepartmentFormSubmission $submission): DepartmentFormSubmission
    {
        // Validate the submission
        $errors = $this->validateSubmission($submission);

        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return $submission->submit();
    }

    /**
     * Validate a form submission.
     */
    public function validateSubmission(DepartmentFormSubmission $submission): array
    {
        $form = $submission->form;
        $data = $submission->data ?? [];
        $errors = [];

        foreach ($form->fields as $field) {
            // Skip hidden fields based on conditional logic
            if (!$field->evaluateConditionalLogic($data)) {
                continue;
            }

            $rules = $field->getValidationRules();
            $value = $data[$field->name] ?? null;

            $validator = Validator::make(
                [$field->name => $value],
                [$field->name => $rules],
                [],
                [$field->name => $field->label]
            );

            if ($validator->fails()) {
                $errors[$field->name] = $validator->errors()->get($field->name);
            }
        }

        return $errors;
    }

    /**
     * Get form with fields structure.
     */
    public function getFormWithFields(DepartmentForm $form): array
    {
        $form->load(['fields' => function ($query) {
            $query->orderBy('order');
        }]);

        return [
            'form' => $form->toArray(),
            'fields' => $form->getFieldsStructure(),
        ];
    }

    /**
     * Get submission data with field metadata.
     */
    public function getSubmissionWithFields(DepartmentFormSubmission $submission): array
    {
        $form = $submission->form;
        $formData = $this->getFormWithFields($form);
        $submissionData = $submission->data ?? [];

        // Add values to fields
        $fields = $this->addValuesToFields($formData['fields'], $submissionData);

        return [
            'form' => $formData['form'],
            'fields' => $fields,
            'submission' => $submission->toArray(),
        ];
    }

    /**
     * Add values to field structure.
     */
    protected function addValuesToFields(array $fields, array $data): array
    {
        return array_map(function ($field) use ($data) {
            $field['value'] = $data[$field['name']] ?? $field['default_value'] ?? null;

            if (!empty($field['children'])) {
                $field['children'] = $this->addValuesToFields($field['children'], $data);
            }

            return $field;
        }, $fields);
    }

    /**
     * Import form from JSON.
     */
    public function importForm(int $departmentId, int $userId, array $formData): DepartmentForm
    {
        return DB::transaction(function () use ($departmentId, $userId, $formData) {
            $form = DepartmentForm::create([
                'department_id' => $departmentId,
                'created_by' => $userId,
                'name' => $formData['name'] ?? 'Imported Form',
                'description' => $formData['description'] ?? null,
                'status' => FormStatus::DRAFT,
                'is_multi_step' => $formData['is_multi_step'] ?? false,
                'settings' => $formData['settings'] ?? null,
                'validation_rules' => $formData['validation_rules'] ?? null,
                'conditional_logic' => $formData['conditional_logic'] ?? null,
                'success_message' => $formData['success_message'] ?? null,
            ]);

            if (!empty($formData['fields'])) {
                $this->importFields($form, $formData['fields'], null);
            }

            return $form->fresh();
        });
    }

    /**
     * Import fields recursively.
     */
    protected function importFields(DepartmentForm $form, array $fields, ?int $parentId): void
    {
        $order = 0;
        foreach ($fields as $fieldData) {
            $children = $fieldData['children'] ?? [];
            unset($fieldData['children'], $fieldData['id'], $fieldData['uuid'], $fieldData['form_id']);

            $field = FormField::create(array_merge($fieldData, [
                'form_id' => $form->id,
                'parent_field_id' => $parentId,
                'order' => $order++,
            ]));

            if (!empty($children)) {
                $this->importFields($form, $children, $field->id);
            }
        }
    }

    /**
     * Export form to JSON.
     */
    public function exportForm(DepartmentForm $form): array
    {
        $formData = $form->only([
            'name',
            'description',
            'is_multi_step',
            'settings',
            'validation_rules',
            'conditional_logic',
            'success_message',
        ]);

        $formData['fields'] = $this->exportFields($form->rootFields);

        return $formData;
    }

    /**
     * Export fields recursively.
     */
    protected function exportFields($fields): array
    {
        return $fields->map(function ($field) {
            $data = $field->only([
                'name',
                'label',
                'type',
                'step',
                'placeholder',
                'help_text',
                'default_value',
                'options',
                'validation',
                'conditional_logic',
                'settings',
                'is_required',
                'is_readonly',
                'is_hidden',
                'width',
            ]);

            if ($field->children->isNotEmpty()) {
                $data['children'] = $this->exportFields($field->children);
            }

            return $data;
        })->toArray();
    }
}
