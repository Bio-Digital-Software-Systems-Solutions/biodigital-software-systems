import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import FieldRenderer from '@/Components/FormBuilder/FieldRenderer';
import type { DepartmentForm, FormField } from '@/Types/form';

interface Props {
    form: DepartmentForm;
    fields: FormField[];
}

export default function FormRender({ form, fields }: Props) {
    const [values, setValues] = useState<Record<string, any>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showCancelConfirmation, setShowCancelConfirmation] = useState(false);

    // Get button settings from form
    const settings = form.settings || {};
    const submitButtonText = settings.submit_button_text || 'Soumettre';
    const cancelButtonText = settings.cancel_button_text || 'Annuler';
    const showCancelButton = settings.show_cancel_button !== false;

    const handleChange = (fieldName: string, value: any) => {
        setValues((prev) => ({ ...prev, [fieldName]: value }));
        // Clear error when user starts typing
        if (errors[fieldName]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[fieldName];
                return newErrors;
            });
        }
    };

    const validateForm = (): boolean => {
        const newErrors: Record<string, string> = {};

        const validateFields = (fieldsToValidate: FormField[]) => {
            fieldsToValidate.forEach((field) => {
                // Check required fields
                if (field.is_required) {
                    const value = values[field.name];
                    if (value === undefined || value === null || value === '' ||
                        (Array.isArray(value) && value.length === 0)) {
                        newErrors[field.name] = 'Ce champ est requis';
                    }
                }

                // Validate children recursively
                if (field.children && field.children.length > 0) {
                    validateFields(field.children);
                }
            });
        };

        validateFields(fields);
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm()) {
            toast.error('Veuillez corriger les erreurs avant de soumettre');
            return;
        }

        setIsSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(route('forms.start-submission', form.uuid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ data: values }),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                toast.success(result.message || form.success_message || 'Formulaire soumis avec succes!');
                // Reset form
                setValues({});
                setErrors({});
                // Redirect if URL provided
                if (result.redirect_url) {
                    setTimeout(() => {
                        window.location.href = result.redirect_url;
                    }, 1500);
                }
            } else {
                toast.error(result.message || 'Erreur lors de la soumission');
            }
        } catch (error) {
            console.error('Submission error:', error);
            toast.error('Erreur lors de la soumission du formulaire');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleCancel = () => {
        if (Object.keys(values).length > 0) {
            setShowCancelConfirmation(true);
        }
    };

    const handleConfirmCancel = () => {
        setValues({});
        setErrors({});
        setShowCancelConfirmation(false);
        toast.info('Formulaire reinitialise');
    };

    const renderFields = (fieldsToRender: FormField[]) => {
        return fieldsToRender.map((field) => (
            <div key={field.uuid} className="mb-6">
                <FieldRenderer
                    field={field}
                    value={values[field.name]}
                    onChange={(value) => handleChange(field.name, value)}
                    preview={true}
                    error={errors[field.name]}
                />
            </div>
        ));
    };

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
            <Head title={form.name} />

            <div className="max-w-2xl mx-auto px-4">
                {/* Form Header */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        {form.name}
                    </h1>
                    {form.description && (
                        <p className="mt-2 text-gray-600 dark:text-gray-400">
                            {form.description}
                        </p>
                    )}
                </div>

                {/* Form Content */}
                <form onSubmit={handleSubmit}>
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        {fields.length === 0 ? (
                            <p className="text-center text-gray-500 dark:text-gray-400 py-8">
                                Ce formulaire ne contient aucun champ.
                            </p>
                        ) : (
                            <>
                                {renderFields(fields)}

                                {/* Action Buttons */}
                                <div className="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700 mt-6">
                                    {showCancelButton && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={handleCancel}
                                            disabled={isSubmitting}
                                        >
                                            {cancelButtonText}
                                        </Button>
                                    )}
                                    <Button
                                        type="submit"
                                        disabled={isSubmitting}
                                    >
                                        {isSubmitting ? 'Envoi en cours...' : submitButtonText}
                                    </Button>
                                </div>
                            </>
                        )}
                    </div>
                </form>

                {/* Footer */}
                <p className="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">
                    {form.department?.name && `Formulaire du departement ${form.department.name}`}
                </p>
            </div>

            {/* Cancel Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showCancelConfirmation}
                onOpenChange={setShowCancelConfirmation}
                onConfirm={handleConfirmCancel}
                title="Annuler la saisie"
                description="Voulez-vous vraiment annuler? Les donnees saisies seront perdues."
                confirmText="Oui, annuler"
                cancelText="Non, continuer"
                variant="default"
            />
        </div>
    );
}
