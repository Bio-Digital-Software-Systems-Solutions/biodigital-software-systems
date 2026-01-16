import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    EyeIcon,
    PlayIcon,
    PauseIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    ExclamationCircleIcon,
    ArrowPathIcon,
    FunnelIcon,
} from '@heroicons/react/24/outline';
import type { WorkflowInstance, WorkflowInstanceStatus } from '@/Types/workflow';
import type { PaginatedData } from '@/Types';

interface Filters {
    [key: string]: string | undefined;
    department_id?: string;
    workflow_id?: string;
    status?: WorkflowInstanceStatus;
    started_by?: string;
}

interface Props {
    instances: PaginatedData<WorkflowInstance & { step_instances_count: number }>;
    filters: Filters;
}

const statusConfig: Record<WorkflowInstanceStatus, { label: string; color: string; bgColor: string; icon: React.ElementType }> = {
    pending: {
        label: 'En attente',
        color: 'text-gray-700 dark:text-gray-300',
        bgColor: 'bg-gray-100 dark:bg-gray-700',
        icon: ClockIcon,
    },
    active: {
        label: 'En cours',
        color: 'text-blue-700 dark:text-blue-400',
        bgColor: 'bg-blue-100 dark:bg-blue-900/30',
        icon: ArrowPathIcon,
    },
    paused: {
        label: 'En pause',
        color: 'text-yellow-700 dark:text-yellow-400',
        bgColor: 'bg-yellow-100 dark:bg-yellow-900/30',
        icon: PauseIcon,
    },
    completed: {
        label: 'Terminé',
        color: 'text-green-700 dark:text-green-400',
        bgColor: 'bg-green-100 dark:bg-green-900/30',
        icon: CheckCircleIcon,
    },
    cancelled: {
        label: 'Annulé',
        color: 'text-red-700 dark:text-red-400',
        bgColor: 'bg-red-100 dark:bg-red-900/30',
        icon: XCircleIcon,
    },
    failed: {
        label: 'Échoué',
        color: 'text-red-700 dark:text-red-400',
        bgColor: 'bg-red-100 dark:bg-red-900/30',
        icon: ExclamationCircleIcon,
    },
};

export default function WorkflowInstancesIndex({ instances, filters }: Props) {
    const [showFilters, setShowFilters] = React.useState(
        Object.values(filters).some(Boolean)
    );
    const [localFilters, setLocalFilters] = React.useState<Filters>(filters);

    const handleFilterChange = (key: keyof Filters, value: string) => {
        const newFilters = { ...localFilters, [key]: value || undefined };
        setLocalFilters(newFilters);
    };

    const applyFilters = () => {
        router.get(route('workflow-instances.index'), localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setLocalFilters({});
        router.get(route('workflow-instances.index'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const instanceData = instances?.data || [];

    return (
        <DashboardLayout>
            <Head title="Instances de workflow" />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Instances de workflow
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Gérez et suivez l'exécution de vos workflows
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setShowFilters(!showFilters)}
                            className={`
                                inline-flex items-center gap-2 px-3 py-2 rounded-md
                                border text-sm
                                ${showFilters
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                }
                            `}
                        >
                            <FunnelIcon className="h-4 w-4" />
                            Filtres
                        </button>
                    </div>

                    {/* Filters */}
                    {showFilters && (
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Statut
                                    </label>
                                    <select
                                        value={localFilters.status || ''}
                                        onChange={(e) => handleFilterChange('status', e.target.value as WorkflowInstanceStatus)}
                                        className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                    >
                                        <option value="">Tous</option>
                                        <option value="pending">En attente</option>
                                        <option value="active">En cours</option>
                                        <option value="paused">En pause</option>
                                        <option value="completed">Terminé</option>
                                        <option value="cancelled">Annulé</option>
                                        <option value="failed">Échoué</option>
                                    </select>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 mt-4">
                                <button
                                    type="button"
                                    onClick={applyFilters}
                                    className="px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-primary/90"
                                >
                                    Appliquer
                                </button>
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-md text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    Réinitialiser
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Instances Table */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {instanceData.length > 0 ? (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-900/50">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Workflow
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Statut
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Département
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Démarré par
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Date
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Étapes
                                                </th>
                                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {instanceData.map((instance) => {
                                                const status = statusConfig[instance.status];
                                                const StatusIcon = status.icon;

                                                return (
                                                    <tr
                                                        key={instance.uuid}
                                                        className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                                    >
                                                        <td className="px-4 py-3">
                                                            <div>
                                                                <p className="font-medium text-gray-900 dark:text-white">
                                                                    {instance.name || instance.workflow?.name || 'Instance'}
                                                                </p>
                                                                {instance.workflow?.name && instance.name && (
                                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                                        {instance.workflow.name}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium ${status.bgColor} ${status.color}`}>
                                                                <StatusIcon className="h-3.5 w-3.5" />
                                                                {status.label}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                            {instance.department?.name || '-'}
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                            {instance.starter
                                                                ? `${instance.starter.first_name} ${instance.starter.last_name}`
                                                                : '-'
                                                            }
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                            {formatDate(instance.started_at ?? null)}
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                            {instance.step_instances_count || 0}
                                                        </td>
                                                        <td className="px-4 py-3 text-right">
                                                            <Link
                                                                href={route('workflow-instances.show', instance.uuid)}
                                                                className="inline-flex items-center gap-1 text-primary hover:text-primary/80 text-sm"
                                                            >
                                                                <EyeIcon className="h-4 w-4" />
                                                                Voir
                                                            </Link>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Pagination */}
                                {instances.meta && instances.meta.last_page > 1 && (
                                    <div className="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            Affichage de {instances.meta.from} à {instances.meta.to} sur {instances.meta.total} résultats
                                        </p>
                                        <div className="flex items-center gap-2">
                                            {instances.links.prev && (
                                                <Link
                                                    href={instances.links.prev}
                                                    className="px-3 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                >
                                                    Précédent
                                                </Link>
                                            )}
                                            {instances.links.next && (
                                                <Link
                                                    href={instances.links.next}
                                                    className="px-3 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                >
                                                    Suivant
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="p-12 text-center">
                                <PlayIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    Aucune instance de workflow
                                </h3>
                                <p className="text-gray-500 dark:text-gray-400">
                                    Démarrez un workflow pour voir les instances ici
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
