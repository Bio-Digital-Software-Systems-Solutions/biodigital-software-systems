import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    EyeIcon,
    DocumentArrowDownIcon,
    DevicePhoneMobileIcon,
    ComputerDesktopIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { useFormBuilderStore } from '@/stores/formBuilderStore';
import FormCanvas from '@/Components/FormBuilder/FormCanvas';
import FieldPalette from '@/Components/FormBuilder/FieldPalette';
import FieldPropertiesPanel from '@/Components/FormBuilder/FieldPropertiesPanel';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import type { DepartmentForm, FormField, FormFieldType } from '@/Types/form';

interface Props {
    form: DepartmentForm;
    fields: FormField[];
}

export default function FormBuilder({ form, fields: initialFields }: Props) {
    const {
        setForm,
        setFields,
        addField,
        selectedFieldId,
        isDirty,
        previewMode,
        setPreviewMode,
        reset,
    } = useFormBuilderStore();

    const [previewDevice, setPreviewDevice] = useState<'desktop' | 'mobile'>('desktop');
    const [showLeaveConfirmation, setShowLeaveConfirmation] = useState(false);

    // Initialize store with form and fields
    useEffect(() => {
        setForm(form);
        setFields(initialFields || []);
        return () => reset();
    }, [form, initialFields]);

    const handleSave = async () => {
        const store = useFormBuilderStore.getState();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        try {
            // First save the form metadata (including settings) using POST with _method override
            const updateResponse = await fetch(route('forms.update', form.uuid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-HTTP-Method-Override': 'PUT',
                },
                body: JSON.stringify({
                    _method: 'PUT',
                    name: store.form?.name,
                    description: store.form?.description,
                    settings: store.form?.settings,
                    success_message: store.form?.success_message,
                }),
            });

            if (!updateResponse.ok) {
                const errorData = await updateResponse.json().catch(() => ({}));
                console.error('Form update failed:', errorData);
                toast.error('Erreur lors de l\'enregistrement du formulaire');
                return;
            }

            // Then save the fields
            const fieldsPayload = { fields: store.fields };
            const fieldsResponse = await fetch(route('forms.save-fields', form.uuid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(fieldsPayload),
            });

            const fieldsResult = await fieldsResponse.json().catch(() => ({}));

            if (fieldsResponse.ok) {
                toast.success(`Formulaire enregistré (${fieldsResult.fields_count || 0} champs)`);
                store.setIsDirty(false);
                // Reload the page to get fresh data with proper UUIDs
                router.reload({ only: ['fields'] });
            } else {
                console.error('Fields save failed:', fieldsResult);
                toast.error(fieldsResult.message || 'Erreur lors de l\'enregistrement des champs');
            }
        } catch (error) {
            console.error('Save error:', error);
            toast.error('Erreur lors de l\'enregistrement');
        }
    };

    const handlePublish = () => {
        router.post(route('forms.publish', form.uuid), {}, {
            onSuccess: () => {
                toast.success('Formulaire publié');
                router.visit(route('forms.index'));
            },
            onError: () => {
                toast.error('Erreur lors de la publication');
            },
        });
    };

    const handleBack = () => {
        if (isDirty) {
            setShowLeaveConfirmation(true);
        } else {
            router.get(route('forms.index'));
        }
    };

    const handleConfirmLeave = () => {
        setShowLeaveConfirmation(false);
        router.get(route('forms.index'));
    };

    const handleAddField = (type: FormFieldType) => {
        addField(type);
    };

    return (
        <>
            <Head title={`Formulaire: ${form.name}`} />

            {/* Confirmation dialog for leaving with unsaved changes */}
            <DeleteConfirmationDialog
                open={showLeaveConfirmation}
                onOpenChange={setShowLeaveConfirmation}
                onConfirm={handleConfirmLeave}
                title="Modifications non enregistrees"
                description="Vous avez des modifications non enregistrees. Voulez-vous vraiment quitter sans enregistrer ?"
                confirmText="Quitter"
                cancelText="Rester"
                variant="default"
            />

            <div className="h-screen flex flex-col bg-gray-100 dark:bg-gray-900">
                {/* Header */}
                <header className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <button
                                type="button"
                                onClick={handleBack}
                                className="
                                    p-2 rounded-md
                                    text-gray-600 hover:bg-gray-100
                                    dark:text-gray-400 dark:hover:bg-gray-700
                                "
                            >
                                <ArrowLeftIcon className="h-5 w-5" />
                            </button>
                            <div>
                                <h1 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    {form.name}
                                </h1>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    {form.status === 'draft' ? 'Brouillon' : 'Publié'}
                                    {isDirty && (
                                        <span className="ml-2 text-amber-500">
                                            • Modifications non enregistrées
                                        </span>
                                    )}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            {/* Preview Toggle */}
                            <div className="flex items-center border border-gray-300 dark:border-gray-600 rounded-md overflow-hidden">
                                <button
                                    type="button"
                                    onClick={() => setPreviewMode(!previewMode)}
                                    className={`
                                        inline-flex items-center gap-2 px-3 py-2 text-sm
                                        ${previewMode
                                            ? 'bg-primary text-white'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                        }
                                    `}
                                >
                                    <EyeIcon className="h-4 w-4" />
                                    {previewMode ? 'Éditer' : 'Aperçu'}
                                </button>
                            </div>

                            {/* Device Toggle (only in preview mode) */}
                            {previewMode && (
                                <div className="flex items-center border border-gray-300 dark:border-gray-600 rounded-md overflow-hidden">
                                    <button
                                        type="button"
                                        onClick={() => setPreviewDevice('desktop')}
                                        className={`
                                            p-2
                                            ${previewDevice === 'desktop'
                                                ? 'bg-gray-100 dark:bg-gray-700 text-primary'
                                                : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700'
                                            }
                                        `}
                                        title="Bureau"
                                    >
                                        <ComputerDesktopIcon className="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setPreviewDevice('mobile')}
                                        className={`
                                            p-2 border-l border-gray-300 dark:border-gray-600
                                            ${previewDevice === 'mobile'
                                                ? 'bg-gray-100 dark:bg-gray-700 text-primary'
                                                : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700'
                                            }
                                        `}
                                        title="Mobile"
                                    >
                                        <DevicePhoneMobileIcon className="h-4 w-4" />
                                    </button>
                                </div>
                            )}

                            <button
                                type="button"
                                onClick={handleSave}
                                disabled={!isDirty}
                                className="
                                    inline-flex items-center gap-2 px-3 py-2 rounded-md
                                    bg-primary text-white font-medium text-sm
                                    hover:bg-primary/90
                                    disabled:opacity-50 disabled:cursor-not-allowed
                                "
                            >
                                Enregistrer
                            </button>

                            {form.status === 'draft' && (
                                <button
                                    type="button"
                                    onClick={handlePublish}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-green-600 text-white font-medium text-sm
                                        hover:bg-green-700
                                    "
                                >
                                    <DocumentArrowDownIcon className="h-4 w-4" />
                                    Publier
                                </button>
                            )}
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <div className="flex-1 flex overflow-hidden">
                    {/* Left Sidebar - Field Palette (hidden in preview mode) */}
                    {!previewMode && (
                        <aside className="w-84 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-hidden">
                            <FieldPalette onAddField={handleAddField} />
                        </aside>
                    )}

                    {/* Canvas */}
                    <main
                        className={`
                            flex-1 overflow-hidden
                            ${previewMode && previewDevice === 'mobile' ? 'flex items-center justify-center bg-gray-200 dark:bg-gray-800' : ''}
                        `}
                    >
                        {previewMode && previewDevice === 'mobile' ? (
                            <div className="w-[375px] h-[667px] bg-white dark:bg-gray-900 rounded-3xl shadow-xl overflow-hidden border-8 border-gray-800">
                                <FormCanvas readOnly />
                            </div>
                        ) : (
                            <FormCanvas readOnly={previewMode} />
                        )}
                    </main>

                    {/* Right Sidebar - Properties (hidden in preview mode) */}
                    {!previewMode && (
                        <aside className="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 overflow-hidden">
                            <FieldPropertiesPanel />
                        </aside>
                    )}
                </div>
            </div>
        </>
    );
}
