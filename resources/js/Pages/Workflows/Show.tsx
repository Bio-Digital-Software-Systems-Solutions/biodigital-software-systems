import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ReactFlowProvider } from '@xyflow/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    PlayIcon,
    DocumentDuplicateIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import WorkflowCanvas from '@/Components/Workflow/WorkflowCanvas';
import { useWorkflowStore } from '@/stores/workflowStore';
import type { DepartmentWorkflow, WorkflowStatus } from '@/Types/workflow';

interface WorkflowStats {
    total_instances: number;
    active_instances: number;
    completed_instances: number;
    failed_instances: number;
    avg_completion_time?: string;
}

interface Props {
    workflow: DepartmentWorkflow;
    stats?: WorkflowStats;
}

const statusConfig: Record<WorkflowStatus, { label: string; color: string; icon: React.ElementType }> = {
    draft: {
        label: 'Brouillon',
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        icon: ClockIcon,
    },
    active: {
        label: 'Actif',
        color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        icon: CheckCircleIcon,
    },
    deprecated: {
        label: 'Obsolète',
        color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        icon: XCircleIcon,
    },
};

export default function WorkflowShow({ workflow, stats }: Props) {
    const status = statusConfig[workflow.status];
    const StatusIcon = status.icon;

    const handleActivate = () => {
        router.post(route('workflows.activate', workflow.uuid), {}, {
            onSuccess: () => {
                toast.success('Workflow activé avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de l\'activation');
            },
        });
    };

    const handleDuplicate = () => {
        router.post(route('workflows.duplicate', workflow.uuid), {}, {
            onSuccess: () => {
                toast.success('Workflow dupliqué avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la duplication');
            },
        });
    };

    const handleStartInstance = () => {
        router.post(route('workflows.start-instance', workflow.uuid), {}, {
            onSuccess: () => {
                toast.success('Instance du workflow démarrée');
            },
            onError: () => {
                toast.error('Erreur lors du démarrage');
            },
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Workflow: ${workflow.name}`} />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-start justify-between mb-6">
                        <div className="flex items-start gap-4">
                            <Link
                                href={route('workflows.index')}
                                className="
                                    p-2 rounded-md mt-1
                                    text-gray-600 hover:bg-gray-100
                                    dark:text-gray-400 dark:hover:bg-gray-700
                                "
                            >
                                <ArrowLeftIcon className="h-5 w-5" />
                            </Link>
                            <div>
                                <div className="flex items-center gap-3">
                                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                        {workflow.name}
                                    </h1>
                                    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium ${status.color}`}>
                                        <StatusIcon className="h-3.5 w-3.5" />
                                        {status.label}
                                    </span>
                                </div>
                                {workflow.description && (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-2xl">
                                        {workflow.description}
                                    </p>
                                )}
                                <div className="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                    <span>Version {workflow.version}</span>
                                    {workflow.department && (
                                        <span>{workflow.department.name}</span>
                                    )}
                                    <span>{(workflow.steps || []).length} étapes</span>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-2">
                            {workflow.status === 'active' && (
                                <button
                                    type="button"
                                    onClick={handleStartInstance}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-green-600 text-white font-medium text-sm
                                        hover:bg-green-700
                                    "
                                >
                                    <PlayIcon className="h-4 w-4" />
                                    Démarrer
                                </button>
                            )}
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
                            <Link
                                href={route('workflows.edit', workflow.uuid)}
                                className="
                                    inline-flex items-center gap-2 px-3 py-2 rounded-md
                                    border border-gray-300 dark:border-gray-600
                                    text-gray-700 dark:text-gray-300
                                    hover:bg-gray-50 dark:hover:bg-gray-700
                                    text-sm
                                "
                            >
                                <PencilIcon className="h-4 w-4" />
                                Modifier
                            </Link>
                            <button
                                type="button"
                                onClick={handleDuplicate}
                                className="
                                    inline-flex items-center gap-2 px-3 py-2 rounded-md
                                    border border-gray-300 dark:border-gray-600
                                    text-gray-700 dark:text-gray-300
                                    hover:bg-gray-50 dark:hover:bg-gray-700
                                    text-sm
                                "
                            >
                                <DocumentDuplicateIcon className="h-4 w-4" />
                                Dupliquer
                            </button>
                        </div>
                    </div>

                    {/* Stats */}
                    {stats && (
                        <div className="grid grid-cols-4 gap-4 mb-6">
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <p className="text-sm text-gray-500 dark:text-gray-400">Total instances</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    {stats.total_instances}
                                </p>
                            </div>
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <p className="text-sm text-gray-500 dark:text-gray-400">En cours</p>
                                <p className="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                                    {stats.active_instances}
                                </p>
                            </div>
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <p className="text-sm text-gray-500 dark:text-gray-400">Terminées</p>
                                <p className="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                                    {stats.completed_instances}
                                </p>
                            </div>
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <p className="text-sm text-gray-500 dark:text-gray-400">Échouées</p>
                                <p className="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                                    {stats.failed_instances}
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Workflow Preview */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                            <h2 className="font-medium text-gray-900 dark:text-white">
                                Aperçu du workflow
                            </h2>
                        </div>
                        <div className="h-[500px]">
                            <ReactFlowProvider>
                                <WorkflowCanvasPreview workflow={workflow} />
                            </ReactFlowProvider>
                        </div>
                    </div>

                    {/* Recent Instances */}
                    {workflow.status === 'active' && (
                        <div className="mt-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                                <h2 className="font-medium text-gray-900 dark:text-white">
                                    Instances récentes
                                </h2>
                                <Link
                                    href={route('workflows.instances', workflow.uuid)}
                                    className="text-sm text-primary hover:underline"
                                >
                                    Voir tout
                                </Link>
                            </div>
                            <div className="p-4">
                                <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                                    Aucune instance récente
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}

// Separate component for the preview canvas to properly use store
function WorkflowCanvasPreview({ workflow }: { workflow: DepartmentWorkflow }) {
    const { setWorkflow, setSteps, setTransitions, reset } = useWorkflowStore();

    React.useEffect(() => {
        setWorkflow(workflow);
        setSteps(workflow.steps || []);
        setTransitions(workflow.transitions || []);

        return () => reset();
    }, [workflow, setWorkflow, setSteps, setTransitions, reset]);

    return <WorkflowCanvas readOnly />;
}
