import React, { useEffect } from 'react';
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
import type { DepartmentForm, FormField, FormFieldType } from '@/Types/form';

interface Props {
    form: DepartmentForm;
}

export default function FormBuilder({ form }: Props) {
    const {
        setForm,
        addField,
        selectedFieldId,
        isDirty,
        previewMode,
        setPreviewMode,
        reset,
    } = useFormBuilderStore();

    const [previewDevice, setPreviewDevice] = React.useState<'desktop' | 'mobile'>('desktop');

    // Initialize store
    useEffect(() => {
        setForm(form);
        return () => reset();
    }, [form]);

    const handleSave = async () => {
        const store = useFormBuilderStore.getState();

        // First save the form metadata
        router.put(
            route('forms.update', form.uuid),
            {
                name: store.form?.name,
                description: store.form?.description,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Then save the fields using fetch to handle complex data
                    fetch(route('forms.save-fields', form.uuid), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify({ fields: store.fields }),
                    })
                        .then((response) => {
                            if (response.ok) {
                                toast.success('Formulaire enregistré');
                                store.setIsDirty(false);
                            } else {
                                toast.error('Erreur lors de l\'enregistrement des champs');
                            }
                        })
                        .catch(() => {
                            toast.error('Erreur lors de l\'enregistrement des champs');
                        });
                },
                onError: () => {
                    toast.error('Erreur lors de l\'enregistrement');
                },
            }
        );
    };

    const handlePublish = () => {
        router.post(route('forms.publish', form.uuid), {}, {
            onSuccess: () => {
                toast.success('Formulaire publié');
            },
            onError: () => {
                toast.error('Erreur lors de la publication');
            },
        });
    };

    const handleBack = () => {
        if (isDirty) {
            if (confirm('Vous avez des modifications non enregistrées. Voulez-vous vraiment quitter ?')) {
                router.get(route('forms.index'));
            }
        } else {
            router.get(route('forms.index'));
        }
    };

    const handleAddField = (type: FormFieldType) => {
        addField(type);
    };

    return (
        <>
            <Head title={`Formulaire: ${form.name}`} />

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
