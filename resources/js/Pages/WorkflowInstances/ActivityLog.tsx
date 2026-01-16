import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    ArrowLeftIcon,
    ClockIcon,
    UserIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    PauseIcon,
    PlayIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import type { WorkflowInstance, WorkflowActivityLog } from '@/Types/workflow';
import type { PaginatedData } from '@/Types';

interface Props {
    instance: WorkflowInstance;
    logs: PaginatedData<WorkflowActivityLog>;
}

const actionConfig: Record<string, { label: string; color: string; icon: React.ElementType }> = {
    created: {
        label: 'Créé',
        color: 'text-blue-600 dark:text-blue-400',
        icon: DocumentTextIcon,
    },
    started: {
        label: 'Démarré',
        color: 'text-green-600 dark:text-green-400',
        icon: PlayIcon,
    },
    completed: {
        label: 'Terminé',
        color: 'text-green-600 dark:text-green-400',
        icon: CheckCircleIcon,
    },
    paused: {
        label: 'Mis en pause',
        color: 'text-yellow-600 dark:text-yellow-400',
        icon: PauseIcon,
    },
    resumed: {
        label: 'Repris',
        color: 'text-blue-600 dark:text-blue-400',
        icon: PlayIcon,
    },
    cancelled: {
        label: 'Annulé',
        color: 'text-red-600 dark:text-red-400',
        icon: XCircleIcon,
    },
    failed: {
        label: 'Échoué',
        color: 'text-red-600 dark:text-red-400',
        icon: ExclamationTriangleIcon,
    },
    updated: {
        label: 'Mis à jour',
        color: 'text-gray-600 dark:text-gray-400',
        icon: ArrowPathIcon,
    },
    step_started: {
        label: 'Étape démarrée',
        color: 'text-blue-600 dark:text-blue-400',
        icon: PlayIcon,
    },
    step_completed: {
        label: 'Étape terminée',
        color: 'text-green-600 dark:text-green-400',
        icon: CheckCircleIcon,
    },
    approval_submitted: {
        label: 'Approbation soumise',
        color: 'text-purple-600 dark:text-purple-400',
        icon: CheckCircleIcon,
    },
    approval_delegated: {
        label: 'Approbation déléguée',
        color: 'text-orange-600 dark:text-orange-400',
        icon: ArrowPathIcon,
    },
};

const getActionConfig = (action: string) => {
    return actionConfig[action] || {
        label: action,
        color: 'text-gray-600 dark:text-gray-400',
        icon: DocumentTextIcon,
    };
};

export default function ActivityLog({ instance, logs }: Props) {
    const logData = logs?.data || [];

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatChanges = (oldValues?: Record<string, any>, newValues?: Record<string, any>) => {
        if (!oldValues && !newValues) return null;

        const changes: { field: string; from?: string; to?: string }[] = [];

        if (newValues) {
            Object.keys(newValues).forEach((key) => {
                if (key === 'updated_at' || key === 'created_at') return;
                changes.push({
                    field: key,
                    from: oldValues?.[key]?.toString(),
                    to: newValues[key]?.toString(),
                });
            });
        }

        return changes.length > 0 ? changes : null;
    };

    return (
        <DashboardLayout>
            <Head title={`Journal d'activité - ${instance.workflow?.name || 'Workflow'}`} />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-start gap-4 mb-6">
                        <Link
                            href={route('workflow-instances.show', instance.uuid)}
                            className="
                                p-2 rounded-md mt-1
                                text-gray-600 hover:bg-gray-100
                                dark:text-gray-400 dark:hover:bg-gray-700
                            "
                        >
                            <ArrowLeftIcon className="h-5 w-5" />
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Journal d'activité
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {instance.workflow?.name || 'Instance de workflow'}
                            </p>
                        </div>
                    </div>

                    {/* Activity Log */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        {logData.length > 0 ? (
                            <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                {logData.map((log, index) => {
                                    const config = getActionConfig(log.action);
                                    const ActionIcon = config.icon;
                                    const changes = formatChanges(log.old_values, log.new_values);

                                    return (
                                        <div
                                            key={log.id}
                                            className="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                        >
                                            <div className="flex items-start gap-4">
                                                <div className={`p-2 rounded-full bg-gray-100 dark:bg-gray-700 ${config.color}`}>
                                                    <ActionIcon className="h-5 w-5" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <p className={`font-medium ${config.color}`}>
                                                            {config.label}
                                                        </p>
                                                        <span className="text-xs text-gray-400 flex items-center gap-1">
                                                            <ClockIcon className="h-3.5 w-3.5" />
                                                            {formatDate(log.created_at)}
                                                        </span>
                                                    </div>

                                                    {log.user && (
                                                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-1">
                                                            <UserIcon className="h-4 w-4" />
                                                            {log.user.first_name} {log.user.last_name}
                                                        </p>
                                                    )}

                                                    {log.entity_type && (
                                                        <p className="text-xs text-gray-400 mt-1">
                                                            {log.entity_type} #{log.entity_id}
                                                        </p>
                                                    )}

                                                    {changes && (
                                                        <div className="mt-2 p-2 bg-gray-50 dark:bg-gray-900/50 rounded text-xs">
                                                            {changes.map((change, i) => (
                                                                <div key={i} className="flex items-center gap-2">
                                                                    <span className="font-medium text-gray-600 dark:text-gray-400">
                                                                        {change.field}:
                                                                    </span>
                                                                    {change.from && (
                                                                        <>
                                                                            <span className="text-red-500 line-through">
                                                                                {change.from}
                                                                            </span>
                                                                            <span className="text-gray-400">→</span>
                                                                        </>
                                                                    )}
                                                                    <span className="text-green-500">
                                                                        {change.to}
                                                                    </span>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}

                                                    {log.metadata && Object.keys(log.metadata).length > 0 && (
                                                        <details className="mt-2">
                                                            <summary className="text-xs text-gray-400 cursor-pointer hover:text-gray-600 dark:hover:text-gray-300">
                                                                Détails techniques
                                                            </summary>
                                                            <pre className="mt-1 p-2 bg-gray-50 dark:bg-gray-900/50 rounded text-xs overflow-x-auto">
                                                                {JSON.stringify(log.metadata, null, 2)}
                                                            </pre>
                                                        </details>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="p-12 text-center">
                                <DocumentTextIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <p className="text-gray-500 dark:text-gray-400">
                                    Aucune activité enregistrée
                                </p>
                            </div>
                        )}

                        {/* Pagination */}
                        {logs.meta && logs.meta.last_page > 1 && (
                            <div className="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Page {logs.meta.current_page} sur {logs.meta.last_page}
                                </p>
                                <div className="flex items-center gap-2">
                                    {logs.links.prev && (
                                        <Link
                                            href={logs.links.prev}
                                            className="px-3 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >
                                            Précédent
                                        </Link>
                                    )}
                                    {logs.links.next && (
                                        <Link
                                            href={logs.links.next}
                                            className="px-3 py-1 text-sm rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >
                                            Suivant
                                        </Link>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
