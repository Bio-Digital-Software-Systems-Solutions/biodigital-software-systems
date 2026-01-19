import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    PlayIcon,
    EyeIcon,
    DocumentDuplicateIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import type { DepartmentWorkflow, WorkflowStatus } from '@/Types/workflow';
import type { PaginatedData } from '@/Types';

interface Props {
    workflows: PaginatedData<DepartmentWorkflow>;
}

const statusConfig: Record<WorkflowStatus, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
    active: { label: 'Actif', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
    deprecated: { label: 'Obsolète', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
};

export default function WorkflowsIndex({ workflows: paginatedWorkflows }: Props) {
    const workflows = paginatedWorkflows?.data || [];
    const [deleteWorkflow, setDeleteWorkflow] = React.useState<DepartmentWorkflow | null>(null);

    const handleDelete = () => {
        if (!deleteWorkflow) return;

        router.delete(route('workflows.destroy', deleteWorkflow.uuid), {
            onSuccess: () => {
                toast.success('Workflow supprimé avec succès');
                setDeleteWorkflow(null);
            },
            onError: () => {
                toast.error('Erreur lors de la suppression');
            },
        });
    };

    const handleDuplicate = (workflow: DepartmentWorkflow) => {
        router.post(route('workflows.duplicate', workflow.uuid), {}, {
            onSuccess: () => {
                toast.success('Workflow dupliqué avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la duplication');
            },
        });
    };

    const handleActivate = (workflow: DepartmentWorkflow) => {
        router.post(route('workflows.activate', workflow.uuid), {}, {
            onSuccess: () => {
                toast.success('Workflow activé avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de l\'activation');
            },
        });
    };

    return (
        <DashboardLayout>
            <Head title="Workflows" />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Workflows
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Gérez vos workflows et automatisations
                            </p>
                        </div>
                        <Link
                            href={route('workflows.create')}
                            className="
                                inline-flex items-center gap-2 px-4 py-2 rounded-md
                                bg-primary text-white font-medium
                                hover:bg-primary/90 transition-colors
                            "
                        >
                            <PlusIcon className="h-5 w-5" />
                            Nouveau workflow
                        </Link>
                    </div>

                    {/* Workflows List */}
                    {workflows.length === 0 ? (
                        <div className="
                            bg-white dark:bg-gray-800 rounded-lg
                            border border-gray-200 dark:border-gray-700
                            p-12 text-center
                        ">
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Aucun workflow créé
                            </p>
                            <Link
                                href={route('workflows.create')}
                                className="
                                    inline-flex items-center gap-2 px-4 py-2 rounded-md
                                    bg-primary text-white font-medium
                                    hover:bg-primary/90 transition-colors
                                "
                            >
                                <PlusIcon className="h-5 w-5" />
                                Créer votre premier workflow
                            </Link>
                        </div>
                    ) : (
                        <div className="grid gap-4">
                            {workflows.map((workflow) => {
                                const status = statusConfig[workflow.status];
                                return (
                                    <div
                                        key={workflow.uuid}
                                        className="
                                            bg-white dark:bg-gray-800 rounded-lg
                                            border border-gray-200 dark:border-gray-700
                                            p-4 flex items-center justify-between
                                            hover:shadow-md transition-shadow
                                        "
                                    >
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-3 mb-1">
                                                <Link
                                                    href={route('workflows.show', workflow.uuid)}
                                                    className="text-lg font-medium text-gray-900 dark:text-white truncate hover:text-primary dark:hover:text-primary transition-colors"
                                                >
                                                    {workflow.name}
                                                </Link>
                                                <span className={`px-2 py-0.5 rounded text-xs font-medium ${status.color}`}>
                                                    {status.label}
                                                </span>
                                            </div>
                                            {workflow.description && (
                                                <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                    {workflow.description}
                                                </p>
                                            )}
                                            <div className="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                                <span>
                                                    {workflow.steps_count || 0} étapes
                                                </span>
                                                <span>
                                                    Version {workflow.version}
                                                </span>
                                                {workflow.department && (
                                                    <span>
                                                        {workflow.department.name}
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        {/* Actions */}
                                        <div className="flex items-center gap-2 ml-4">
                                            {workflow.status === 'draft' && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleActivate(workflow)}
                                                    className="
                                                        p-2 rounded-md
                                                        text-green-600 hover:bg-green-50
                                                        dark:text-green-400 dark:hover:bg-green-900/20
                                                    "
                                                    title="Activer"
                                                >
                                                    <PlayIcon className="h-5 w-5" />
                                                </button>
                                            )}
                                            <Link
                                                href={route('workflows.show', workflow.uuid)}
                                                className="
                                                    p-2 rounded-md
                                                    text-gray-600 hover:bg-gray-100
                                                    dark:text-gray-400 dark:hover:bg-gray-700
                                                "
                                                title="Voir"
                                            >
                                                <EyeIcon className="h-5 w-5" />
                                            </Link>
                                            <Link
                                                href={route('workflows.edit', workflow.uuid)}
                                                className="
                                                    p-2 rounded-md
                                                    text-gray-600 hover:bg-gray-100
                                                    dark:text-gray-400 dark:hover:bg-gray-700
                                                "
                                                title="Modifier"
                                            >
                                                <PencilIcon className="h-5 w-5" />
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => handleDuplicate(workflow)}
                                                className="
                                                    p-2 rounded-md
                                                    text-gray-600 hover:bg-gray-100
                                                    dark:text-gray-400 dark:hover:bg-gray-700
                                                "
                                                title="Dupliquer"
                                            >
                                                <DocumentDuplicateIcon className="h-5 w-5" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setDeleteWorkflow(workflow)}
                                                className="
                                                    p-2 rounded-md
                                                    text-red-600 hover:bg-red-50
                                                    dark:text-red-400 dark:hover:bg-red-900/20
                                                "
                                                title="Supprimer"
                                            >
                                                <TrashIcon className="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            {/* Delete Confirmation */}
            <DeleteConfirmationDialog
                open={!!deleteWorkflow}
                onOpenChange={(open) => !open && setDeleteWorkflow(null)}
                onConfirm={handleDelete}
                title="Supprimer le workflow"
                description={`Êtes-vous sûr de vouloir supprimer le workflow "${deleteWorkflow?.name}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
}
