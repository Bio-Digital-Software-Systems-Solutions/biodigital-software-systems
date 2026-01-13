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
        const steps = workflow.steps || [];
        setSteps(steps);

        // Enrich transitions with UUIDs from steps for proper edge rendering
        const transitions = (workflow.transitions || []).map((transition) => {
            const fromStep = steps.find((s) => s.id === transition.from_step_id);
            const toStep = steps.find((s) => s.id === transition.to_step_id);
            return {
                ...transition,
                from_step_uuid: fromStep?.uuid,
                to_step_uuid: toStep?.uuid,
            };
        });
        setTransitions(transitions);

        return () => reset();
    }, [workflow]);

    const handleSave = () => {
        const store = useWorkflowStore.getState();

        // Prepare steps with order index - only include serializable fields
        const stepsToSave = store.steps.map((step, index) => ({
            uuid: step.uuid,
            name: step.name,
            description: step.description || null,
            type: step.type,
            order: index,
            position_x: step.position_x,
            position_y: step.position_y,
            is_start: step.is_start || false,
            is_end: step.is_end || false,
            config: step.config || null,
            form_id: step.form_id || null,
            approval_type: step.approval_type || null,
            approvers: step.approvers || null,
            timeout_hours: step.timeout_hours || null,
            timeout_action: step.timeout_action || null,
        }));

        // Prepare transitions with from_step_uuid and to_step_uuid
        const transitionsToSave = store.transitions.map((transition) => ({
            uuid: transition.uuid,
            from_step_uuid: transition.from_step_uuid,
            to_step_uuid: transition.to_step_uuid,
            name: transition.name || null,
            condition_type: transition.condition_type || 'always',
            condition_config: transition.condition_config || null,
            is_default: transition.is_default || false,
            priority: transition.priority || 0,
        }));

        router.post(
            route('workflows.save-steps', workflow.uuid),
            {
                steps: stepsToSave as any,
                transitions: transitionsToSave as any,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Workflow enregistré');
                    store.setIsDirty(false);
                },
                onError: (errors) => {
                    console.error('Save errors:', errors);
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
