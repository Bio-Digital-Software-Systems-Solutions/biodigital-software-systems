import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    ArrowLeftIcon,
    PauseIcon,
    PlayIcon,
    XMarkIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import type {
    WorkflowInstance,
    WorkflowStepInstance,
    WorkflowInstanceStatus,
    StepInstanceStatus,
    WorkflowActivityLog,
} from '@/Types/workflow';

interface Props {
    instance: WorkflowInstance;
    currentStep: WorkflowStepInstance | null;
    progress: number;
}

const instanceStatusConfig: Record<WorkflowInstanceStatus, { label: string; color: string; icon: React.ElementType }> = {
    pending: {
        label: 'En attente',
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        icon: ClockIcon,
    },
    active: {
        label: 'En cours',
        color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        icon: ArrowPathIcon,
    },
    paused: {
        label: 'En pause',
        color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        icon: PauseIcon,
    },
    completed: {
        label: 'Terminé',
        color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        icon: CheckCircleIcon,
    },
    cancelled: {
        label: 'Annulé',
        color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        icon: XCircleIcon,
    },
    failed: {
        label: 'Échoué',
        color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        icon: ExclamationCircleIcon,
    },
};

const stepStatusConfig: Record<StepInstanceStatus, { label: string; color: string; dotColor: string }> = {
    pending: {
        label: 'En attente',
        color: 'text-gray-500 dark:text-gray-400',
        dotColor: 'bg-gray-400',
    },
    active: {
        label: 'En cours',
        color: 'text-blue-600 dark:text-blue-400',
        dotColor: 'bg-blue-500',
    },
    completed: {
        label: 'Terminé',
        color: 'text-green-600 dark:text-green-400',
        dotColor: 'bg-green-500',
    },
    skipped: {
        label: 'Ignoré',
        color: 'text-gray-500 dark:text-gray-400',
        dotColor: 'bg-gray-400',
    },
    failed: {
        label: 'Échoué',
        color: 'text-red-600 dark:text-red-400',
        dotColor: 'bg-red-500',
    },
    cancelled: {
        label: 'Annulé',
        color: 'text-red-600 dark:text-red-400',
        dotColor: 'bg-red-500',
    },
    waiting: {
        label: 'En attente',
        color: 'text-yellow-600 dark:text-yellow-400',
        dotColor: 'bg-yellow-500',
    },
};

export default function WorkflowInstanceShow({ instance, currentStep, progress }: Props) {
    const [showCancelDialog, setShowCancelDialog] = React.useState(false);
    const status = instanceStatusConfig[instance.status];
    const StatusIcon = status.icon;

    const handlePause = () => {
        router.post(route('workflow-instances.pause', instance.uuid), {}, {
            onSuccess: () => {
                toast.success('Workflow mis en pause');
            },
            onError: () => {
                toast.error('Erreur lors de la mise en pause');
            },
        });
    };

    const handleResume = () => {
        router.post(route('workflow-instances.resume', instance.uuid), {}, {
            onSuccess: () => {
                toast.success('Workflow repris');
            },
            onError: () => {
                toast.error('Erreur lors de la reprise');
            },
        });
    };

    const handleCancel = () => {
        router.post(route('workflow-instances.cancel', instance.uuid), {}, {
            onSuccess: () => {
                toast.success('Workflow annulé');
                setShowCancelDialog(false);
            },
            onError: () => {
                toast.error('Erreur lors de l\'annulation');
            },
        });
    };

    const canPause = instance.status === 'active';
    const canResume = instance.status === 'paused';
    const canCancel = ['pending', 'active', 'paused'].includes(instance.status);

    const stepInstances = instance.step_instances || [];

    return (
        <DashboardLayout>
            <Head title={`Instance: ${instance.workflow?.name || 'Workflow'}`} />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-start justify-between mb-6">
                        <div className="flex items-start gap-4">
                            <Link
                                href={route('workflow-instances.index')}
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
                                        {instance.name || instance.workflow?.name || 'Instance de workflow'}
                                    </h1>
                                    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium ${status.color}`}>
                                        <StatusIcon className="h-3.5 w-3.5" />
                                        {status.label}
                                    </span>
                                </div>
                                {instance.workflow && (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Workflow: {instance.workflow.name}
                                    </p>
                                )}
                                <div className="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                    {instance.starter && (
                                        <span>Démarré par {instance.starter.first_name} {instance.starter.last_name}</span>
                                    )}
                                    {instance.started_at && (
                                        <span>Le {new Date(instance.started_at).toLocaleDateString('fr-FR')}</span>
                                    )}
                                    {instance.department && (
                                        <span>{instance.department.name}</span>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-2">
                            {canPause && (
                                <button
                                    type="button"
                                    onClick={handlePause}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        border border-yellow-300 dark:border-yellow-600
                                        text-yellow-700 dark:text-yellow-400
                                        hover:bg-yellow-50 dark:hover:bg-yellow-900/20
                                        text-sm
                                    "
                                >
                                    <PauseIcon className="h-4 w-4" />
                                    Pause
                                </button>
                            )}
                            {canResume && (
                                <button
                                    type="button"
                                    onClick={handleResume}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-green-600 text-white font-medium text-sm
                                        hover:bg-green-700
                                    "
                                >
                                    <PlayIcon className="h-4 w-4" />
                                    Reprendre
                                </button>
                            )}
                            {canCancel && (
                                <button
                                    type="button"
                                    onClick={() => setShowCancelDialog(true)}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        border border-red-300 dark:border-red-600
                                        text-red-700 dark:text-red-400
                                        hover:bg-red-50 dark:hover:bg-red-900/20
                                        text-sm
                                    "
                                >
                                    <XMarkIcon className="h-4 w-4" />
                                    Annuler
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Progress Bar */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Progression
                            </span>
                            <span className="text-sm text-gray-500 dark:text-gray-400">
                                {progress.toFixed(0)}%
                            </span>
                        </div>
                        <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div
                                className="bg-primary h-2.5 rounded-full transition-all duration-300"
                                style={{ width: `${progress}%` }}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Current Step */}
                        <div className="lg:col-span-1">
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <h2 className="font-medium text-gray-900 dark:text-white mb-4">
                                    Étape actuelle
                                </h2>
                                {currentStep ? (
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-2">
                                            <div className={`w-2 h-2 rounded-full ${stepStatusConfig[currentStep.status]?.dotColor || 'bg-gray-400'}`} />
                                            <span className="font-medium text-gray-900 dark:text-white">
                                                {currentStep.step?.name || 'Étape'}
                                            </span>
                                        </div>
                                        {currentStep.step?.description && (
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                {currentStep.step.description}
                                            </p>
                                        )}
                                        {currentStep.assigned_user && (
                                            <p className="text-xs text-gray-400">
                                                Assigné à: {currentStep.assigned_user.first_name} {currentStep.assigned_user.last_name}
                                            </p>
                                        )}
                                        {currentStep.due_at && (
                                            <p className="text-xs text-gray-400">
                                                Échéance: {new Date(currentStep.due_at).toLocaleDateString('fr-FR')}
                                            </p>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        {instance.status === 'completed'
                                            ? 'Workflow terminé'
                                            : 'Aucune étape active'
                                        }
                                    </p>
                                )}
                            </div>

                            {/* Instance Info */}
                            {(instance.cancellation_reason || instance.failure_reason) && (
                                <div className="mt-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 p-4">
                                    <h3 className="font-medium text-red-800 dark:text-red-300 mb-2">
                                        {instance.cancellation_reason ? 'Raison de l\'annulation' : 'Raison de l\'échec'}
                                    </h3>
                                    <p className="text-sm text-red-700 dark:text-red-400">
                                        {instance.cancellation_reason || instance.failure_reason}
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Step Instances Timeline */}
                        <div className="lg:col-span-2">
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="font-medium text-gray-900 dark:text-white">
                                        Historique des étapes
                                    </h2>
                                    <Link
                                        href={route('workflow-instances.activity-log', instance.uuid)}
                                        className="text-sm text-primary hover:underline"
                                    >
                                        Voir le journal
                                    </Link>
                                </div>

                                {stepInstances.length > 0 ? (
                                    <div className="space-y-4">
                                        {stepInstances.map((stepInstance, index) => {
                                            const stepStatus = stepStatusConfig[stepInstance.status] || stepStatusConfig.pending;
                                            return (
                                                <div key={stepInstance.uuid} className="relative">
                                                    {index < stepInstances.length - 1 && (
                                                        <div className="absolute left-[5px] top-6 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700" />
                                                    )}
                                                    <div className="flex items-start gap-3">
                                                        <div className={`w-3 h-3 rounded-full mt-1 ${stepStatus.dotColor}`} />
                                                        <div className="flex-1 min-w-0">
                                                            <div className="flex items-center justify-between">
                                                                <span className={`font-medium ${stepStatus.color}`}>
                                                                    {stepInstance.step?.name || 'Étape'}
                                                                </span>
                                                                <span className="text-xs text-gray-400">
                                                                    {stepStatus.label}
                                                                </span>
                                                            </div>
                                                            <div className="flex items-center gap-4 mt-1 text-xs text-gray-400">
                                                                {stepInstance.started_at && (
                                                                    <span>
                                                                        Début: {new Date(stepInstance.started_at).toLocaleString('fr-FR')}
                                                                    </span>
                                                                )}
                                                                {stepInstance.completed_at && (
                                                                    <span>
                                                                        Fin: {new Date(stepInstance.completed_at).toLocaleString('fr-FR')}
                                                                    </span>
                                                                )}
                                                            </div>
                                                            {stepInstance.error_message && (
                                                                <p className="text-xs text-red-500 mt-1">
                                                                    {stepInstance.error_message}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                                        Aucune étape exécutée
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Cancel Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showCancelDialog}
                onOpenChange={setShowCancelDialog}
                onConfirm={handleCancel}
                title="Annuler le workflow"
                description="Êtes-vous sûr de vouloir annuler cette instance de workflow ? Cette action est irréversible."
            />
        </DashboardLayout>
    );
}
