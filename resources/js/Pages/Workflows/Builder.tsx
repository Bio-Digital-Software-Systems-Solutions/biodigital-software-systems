import React, { useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import { ReactFlowProvider } from '@xyflow/react';
import {
    ArrowLeftIcon,
    PlayIcon,
    EyeIcon,
    Cog6ToothIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { useWorkflowStore } from '@/stores/workflowStore';
import WorkflowCanvas from '@/Components/Workflow/WorkflowCanvas';
import StepPalette from '@/Components/Workflow/StepPalette';
import StepPropertiesPanel from '@/Components/Workflow/StepPropertiesPanel';
import type { DepartmentWorkflow } from '@/Types/workflow';

interface Props {
    workflow: DepartmentWorkflow;
}

export default function WorkflowBuilder({ workflow }: Props) {
    const {
        setWorkflow,
        setSteps,
        setTransitions,
        selectedStepId,
        isDirty,
        reset,
    } = useWorkflowStore();

    // Initialize store with workflow data (steps and transitions are loaded via relationship)
    useEffect(() => {
        setWorkflow(workflow);
        setSteps(workflow.steps || []);
        setTransitions(workflow.transitions || []);

        return () => reset();
    }, [workflow]);

    const handleSave = () => {
        const store = useWorkflowStore.getState();

        router.put(
            route('workflows.update', workflow.uuid),
            {
                name: store.workflow?.name,
                description: store.workflow?.description,
                steps: JSON.stringify(store.steps),
                transitions: JSON.stringify(store.transitions),
            },
            {
                onSuccess: () => {
                    toast.success('Workflow enregistré');
                    store.setIsDirty(false);
                },
                onError: () => {
                    toast.error('Erreur lors de l\'enregistrement');
                },
            }
        );
    };

    const handleActivate = () => {
        router.post(route('workflows.activate', workflow.uuid), {}, {
            onSuccess: () => {
                toast.success('Workflow activé');
            },
            onError: () => {
                toast.error('Erreur lors de l\'activation');
            },
        });
    };

    const handleBack = () => {
        if (isDirty) {
            if (confirm('Vous avez des modifications non enregistrées. Voulez-vous vraiment quitter ?')) {
                router.get(route('workflows.index'));
            }
        } else {
            router.get(route('workflows.index'));
        }
    };

    return (
        <ReactFlowProvider>
            <Head title={`Workflow: ${workflow.name}`} />

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
                                    {workflow.name}
                                </h1>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Version {workflow.version}
                                    {isDirty && (
                                        <span className="ml-2 text-amber-500">
                                            • Modifications non enregistrées
                                        </span>
                                    )}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => router.get(route('workflows.show', workflow.uuid))}
                                className="
                                    inline-flex items-center gap-2 px-3 py-2 rounded-md
                                    border border-gray-300 dark:border-gray-600
                                    text-gray-700 dark:text-gray-300
                                    hover:bg-gray-50 dark:hover:bg-gray-700
                                    text-sm
                                "
                            >
                                <EyeIcon className="h-4 w-4" />
                                Aperçu
                            </button>

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

                            {workflow.status === 'draft' && (
                                <button
                                    type="button"
                                    onClick={handleActivate}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-green-600 text-white font-medium text-sm
                                        hover:bg-green-700
                                    "
                                >
                                    <PlayIcon className="h-4 w-4" />
                                    Activer
                                </button>
                            )}
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <div className="flex-1 flex overflow-hidden">
                    {/* Left Sidebar - Step Palette */}
                    <aside className="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-hidden">
                        <StepPalette />
                    </aside>

                    {/* Canvas */}
                    <main className="flex-1 overflow-hidden">
                        <WorkflowCanvas />
                    </main>

                    {/* Right Sidebar - Properties */}
                    <aside className="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 overflow-hidden">
                        {selectedStepId ? (
                            <StepPropertiesPanel />
                        ) : (
                            <div className="h-full flex items-center justify-center p-4">
                                <div className="text-center">
                                    <Cog6ToothIcon className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Sélectionnez une étape pour modifier ses propriétés
                                    </p>
                                </div>
                            </div>
                        )}
                    </aside>
                </div>
            </div>
        </ReactFlowProvider>
    );
}
