import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    PaperClipIcon,
    ChatBubbleLeftIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    UserIcon,
    CalendarIcon,
    CurrencyEuroIcon,
    BuildingOfficeIcon,
    TrashIcon,
    ArrowUturnLeftIcon,
    NoSymbolIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import type { DepartmentNeed, NeedStatus, NeedCategory, NeedPriority } from '@/Types/need';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface Props {
    need: DepartmentNeed;
    canApprove?: boolean;
    canEdit?: boolean;
    canWithdraw?: boolean;
    canCancel?: boolean;
}

const statusConfig: Record<NeedStatus, { label: string; color: string; icon: React.ElementType }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', icon: ClockIcon },
    submitted: { label: 'Soumis', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', icon: ClockIcon },
    under_review: { label: 'En révision', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', icon: ClockIcon },
    approved: { label: 'Approuvé', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', icon: CheckCircleIcon },
    rejected: { label: 'Rejeté', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', icon: XCircleIcon },
    in_progress: { label: 'En cours', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400', icon: ClockIcon },
    ordered: { label: 'Commandé', color: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400', icon: ClockIcon },
    delivered: { label: 'Livré', color: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400', icon: CheckCircleIcon },
    completed: { label: 'Terminé', color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', icon: CheckCircleIcon },
    cancelled: { label: 'Annulé', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400', icon: XCircleIcon },
};

const categoryConfig: Record<NeedCategory, { label: string; color: string }> = {
    equipment: { label: 'Équipement', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
    software: { label: 'Logiciel', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
    furniture: { label: 'Mobilier', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
    supplies: { label: 'Fournitures', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
    services: { label: 'Services', color: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400' },
    training: { label: 'Formation', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
    recruitment: { label: 'Recrutement', color: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400' },
    other: { label: 'Autre', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
};

const priorityConfig: Record<NeedPriority, { label: string; color: string }> = {
    critical: { label: 'Critique', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    high: { label: 'Haute', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
    medium: { label: 'Moyenne', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
    low: { label: 'Basse', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
};

export default function NeedShow({ need, canApprove, canEdit, canWithdraw, canCancel }: Props) {
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [showWithdrawDialog, setShowWithdrawDialog] = useState(false);
    const [showCancelDialog, setShowCancelDialog] = useState(false);
    const [rejectionReason, setRejectionReason] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const status = statusConfig[need.status] || statusConfig.draft;
    const category = categoryConfig[need.category] || categoryConfig.other;
    const priority = priorityConfig[need.priority] || priorityConfig.medium;
    const StatusIcon = status.icon;

    const handleSubmit = () => {
        setIsSubmitting(true);
        router.post(route('needs.submit', need.uuid), {}, {
            onSuccess: () => toast.success('Besoin soumis avec succès'),
            onError: () => toast.error('Erreur lors de la soumission'),
            onFinish: () => setIsSubmitting(false),
        });
    };

    const handleApprove = () => {
        setIsSubmitting(true);
        router.post(route('needs.approve', need.uuid), {}, {
            onSuccess: () => toast.success('Besoin approuvé'),
            onError: () => toast.error('Erreur lors de l\'approbation'),
            onFinish: () => setIsSubmitting(false),
        });
    };

    const handleReject = () => {
        if (!rejectionReason.trim()) {
            toast.error('Veuillez fournir une raison de rejet');
            return;
        }
        setIsSubmitting(true);
        router.post(route('needs.reject', need.uuid), { reason: rejectionReason }, {
            onSuccess: () => {
                toast.success('Besoin rejeté');
                setShowRejectModal(false);
                setRejectionReason('');
            },
            onError: () => toast.error('Erreur lors du rejet'),
            onFinish: () => setIsSubmitting(false),
        });
    };

    const handleDelete = () => {
        router.delete(route('needs.destroy', need.uuid), {
            onSuccess: () => toast.success('Besoin supprimé'),
            onError: () => toast.error('Erreur lors de la suppression'),
        });
    };

    const handleWithdraw = () => {
        setIsSubmitting(true);
        router.post(route('needs.withdraw', need.uuid), {}, {
            onSuccess: () => {
                toast.success('Besoin retiré et remis en brouillon');
                setShowWithdrawDialog(false);
            },
            onError: () => toast.error('Erreur lors du retrait'),
            onFinish: () => setIsSubmitting(false),
        });
    };

    const handleCancel = () => {
        setIsSubmitting(true);
        router.post(route('needs.cancel', need.uuid), {}, {
            onSuccess: () => {
                toast.success('Besoin annulé');
                setShowCancelDialog(false);
            },
            onError: () => toast.error('Erreur lors de l\'annulation'),
            onFinish: () => setIsSubmitting(false),
        });
    };

    const handleStatusChange = (newStatus: NeedStatus) => {
        setIsSubmitting(true);
        router.patch(route('needs.update-status', need.uuid), { status: newStatus }, {
            onSuccess: () => toast.success('Statut mis à jour'),
            onError: () => toast.error('Erreur lors de la mise à jour'),
            onFinish: () => setIsSubmitting(false),
        });
    };

    // Get the needed_by field - try both possible names
    const neededByDate = (need as any).needed_by || need.needed_by_date;

    return (
        <DashboardLayout>
            <Head title={`Besoin: ${need.title}`} />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-start justify-between mb-6">
                        <div className="flex items-start gap-4">
                            <Link
                                href={route('needs.index')}
                                className="
                                    p-2 rounded-md mt-1
                                    text-gray-600 hover:bg-gray-100
                                    dark:text-gray-400 dark:hover:bg-gray-700
                                "
                            >
                                <ArrowLeftIcon className="h-5 w-5" />
                            </Link>
                            <div>
                                <div className="flex items-center gap-3 flex-wrap">
                                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                        {need.title}
                                    </h1>
                                    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium ${status.color}`}>
                                        <StatusIcon className="h-3.5 w-3.5" />
                                        {status.label}
                                    </span>
                                    <span className={`px-2 py-0.5 rounded text-xs font-medium ${category.color}`}>
                                        {category.label}
                                    </span>
                                    <span className={`px-2 py-0.5 rounded text-xs font-medium ${priority.color}`}>
                                        {priority.label}
                                    </span>
                                </div>
                                {need.reference && (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Réf: {need.reference}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-2">
                            {need.status === 'draft' && (
                                <button
                                    type="button"
                                    onClick={handleSubmit}
                                    disabled={isSubmitting}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-primary text-white font-medium text-sm
                                        hover:bg-primary/90 disabled:opacity-50
                                    "
                                >
                                    Soumettre
                                </button>
                            )}
                            {canWithdraw && (
                                <button
                                    type="button"
                                    onClick={() => setShowWithdrawDialog(true)}
                                    disabled={isSubmitting}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        border border-yellow-500 dark:border-yellow-600
                                        text-yellow-700 dark:text-yellow-400
                                        hover:bg-yellow-50 dark:hover:bg-yellow-900/20
                                        text-sm
                                    "
                                >
                                    <ArrowUturnLeftIcon className="h-4 w-4" />
                                    Retirer
                                </button>
                            )}
                            {canCancel && need.status !== 'draft' && (
                                <button
                                    type="button"
                                    onClick={() => setShowCancelDialog(true)}
                                    disabled={isSubmitting}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        border border-orange-500 dark:border-orange-600
                                        text-orange-700 dark:text-orange-400
                                        hover:bg-orange-50 dark:hover:bg-orange-900/20
                                        text-sm
                                    "
                                >
                                    <NoSymbolIcon className="h-4 w-4" />
                                    Annuler
                                </button>
                            )}
                            {canApprove && (need.status === 'under_review' || need.status === 'submitted') && (
                                <>
                                    <button
                                        type="button"
                                        onClick={handleApprove}
                                        disabled={isSubmitting}
                                        className="
                                            inline-flex items-center gap-2 px-3 py-2 rounded-md
                                            bg-green-600 text-white font-medium text-sm
                                            hover:bg-green-700 disabled:opacity-50
                                        "
                                    >
                                        <CheckCircleIcon className="h-4 w-4" />
                                        Approuver
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowRejectModal(true)}
                                        disabled={isSubmitting}
                                        className="
                                            inline-flex items-center gap-2 px-3 py-2 rounded-md
                                            bg-red-600 text-white font-medium text-sm
                                            hover:bg-red-700 disabled:opacity-50
                                        "
                                    >
                                        <XCircleIcon className="h-4 w-4" />
                                        Rejeter
                                    </button>
                                </>
                            )}
                            {canEdit && (
                                <Link
                                    href={route('needs.edit', need.uuid)}
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
                            )}
                            {canEdit && need.status === 'draft' && (
                                <button
                                    type="button"
                                    onClick={() => setShowDeleteDialog(true)}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        border border-red-300 dark:border-red-600
                                        text-red-700 dark:text-red-400
                                        hover:bg-red-50 dark:hover:bg-red-900/20
                                        text-sm
                                    "
                                >
                                    <TrashIcon className="h-4 w-4" />
                                    Supprimer
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Rejection Reason Alert */}
                    {need.status === 'rejected' && need.rejection_reason && (
                        <div className="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <div className="flex items-start gap-3">
                                <ExclamationTriangleIcon className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" />
                                <div>
                                    <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
                                        Raison du rejet
                                    </h3>
                                    <p className="mt-1 text-sm text-red-700 dark:text-red-300">
                                        {need.rejection_reason}
                                    </p>
                                    {need.rejecter && (
                                        <p className="mt-2 text-xs text-red-600 dark:text-red-400">
                                            Rejeté par {need.rejecter.full_name} le {need.rejected_at && new Date(need.rejected_at).toLocaleDateString('fr-FR')}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Description */}
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                    <h2 className="font-medium text-gray-900 dark:text-white">
                                        Description
                                    </h2>
                                </div>
                                <div className="p-4">
                                    {need.description ? (
                                        <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                            {need.description}
                                        </p>
                                    ) : (
                                        <p className="text-sm text-gray-500 dark:text-gray-400 italic">
                                            Aucune description
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Justification */}
                            {need.justification && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                        <h2 className="font-medium text-gray-900 dark:text-white">
                                            Justification
                                        </h2>
                                    </div>
                                    <div className="p-4">
                                        <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                            {need.justification}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Attachments */}
                            {need.attachments && need.attachments.length > 0 && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                        <h2 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                            <PaperClipIcon className="h-5 w-5" />
                                            Pièces jointes ({need.attachments.length})
                                        </h2>
                                    </div>
                                    <div className="p-4">
                                        <ul className="space-y-2">
                                            {need.attachments.map((attachment) => (
                                                <li
                                                    key={attachment.uuid}
                                                    className="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded"
                                                >
                                                    <span className="text-sm text-gray-700 dark:text-gray-300">
                                                        {attachment.original_filename}
                                                    </span>
                                                    <span className="text-xs text-gray-500">
                                                        {attachment.formatted_size}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>
                            )}

                            {/* Comments */}
                            {need.comments && need.comments.length > 0 && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                        <h2 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                            <ChatBubbleLeftIcon className="h-5 w-5" />
                                            Commentaires ({need.comments.length})
                                        </h2>
                                    </div>
                                    <div className="p-4 space-y-4">
                                        {need.comments.map((comment) => (
                                            <div key={comment.uuid} className="flex gap-3">
                                                <div className="flex-shrink-0 w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                    <UserIcon className="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                                </div>
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {comment.user?.full_name || 'Utilisateur'}
                                                        </span>
                                                        <span className="text-xs text-gray-500">
                                                            {new Date(comment.created_at).toLocaleDateString('fr-FR')}
                                                        </span>
                                                    </div>
                                                    <p className="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                                        {comment.content}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Details */}
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                    <h2 className="font-medium text-gray-900 dark:text-white">
                                        Détails
                                    </h2>
                                </div>
                                <div className="p-4 space-y-4">
                                    {need.department && (
                                        <div className="flex items-center gap-3">
                                            <BuildingOfficeIcon className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">Département</p>
                                                <p className="text-sm text-gray-900 dark:text-white">{need.department.name}</p>
                                            </div>
                                        </div>
                                    )}

                                    {need.created_by && (
                                        <div className="flex items-center gap-3">
                                            <UserIcon className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">Demandeur</p>
                                                <p className="text-sm text-gray-900 dark:text-white">{need.created_by.full_name}</p>
                                            </div>
                                        </div>
                                    )}

                                    {need.assigned_to && (
                                        <div className="flex items-center gap-3">
                                            <UserIcon className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">Assigné à</p>
                                                <p className="text-sm text-gray-900 dark:text-white">{need.assigned_to.full_name}</p>
                                            </div>
                                        </div>
                                    )}

                                    {need.estimated_cost !== null && need.estimated_cost !== undefined && (
                                        <div className="flex items-center gap-3">
                                            <CurrencyEuroIcon className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">Montant estimé</p>
                                                <p className="text-sm text-gray-900 dark:text-white">
                                                    {new Intl.NumberFormat('fr-FR', { style: 'currency', currency: need.currency || 'EUR' }).format(Number(need.estimated_cost))}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {neededByDate && (
                                        <div className="flex items-center gap-3">
                                            <CalendarIcon className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">Date souhaitée</p>
                                                <p className="text-sm text-gray-900 dark:text-white">
                                                    {new Date(neededByDate).toLocaleDateString('fr-FR')}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex items-center gap-3">
                                        <ClockIcon className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">Créé le</p>
                                            <p className="text-sm text-gray-900 dark:text-white">
                                                {new Date(need.created_at).toLocaleDateString('fr-FR', {
                                                    year: 'numeric',
                                                    month: 'long',
                                                    day: 'numeric',
                                                })}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Status Change Actions */}
                            {canApprove && !['draft', 'rejected', 'cancelled', 'completed'].includes(need.status) && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                        <h2 className="font-medium text-gray-900 dark:text-white">
                                            Changer le statut
                                        </h2>
                                    </div>
                                    <div className="p-4">
                                        <select
                                            value={need.status}
                                            onChange={(e) => handleStatusChange(e.target.value as NeedStatus)}
                                            disabled={isSubmitting}
                                            className="
                                                w-full px-3 py-2 rounded-md border text-sm
                                                bg-white dark:bg-gray-900
                                                border-gray-300 dark:border-gray-600
                                                text-gray-900 dark:text-white
                                                focus:ring-2 focus:ring-primary focus:border-primary
                                                disabled:opacity-50
                                            "
                                        >
                                            <option value="submitted">Soumis</option>
                                            <option value="under_review">En révision</option>
                                            <option value="approved">Approuvé</option>
                                            <option value="in_progress">En cours</option>
                                            <option value="ordered">Commandé</option>
                                            <option value="delivered">Livré</option>
                                            <option value="completed">Terminé</option>
                                            <option value="cancelled">Annulé</option>
                                        </select>
                                    </div>
                                </div>
                            )}

                            {/* Status History */}
                            {need.status_history && need.status_history.length > 0 && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                        <h2 className="font-medium text-gray-900 dark:text-white">
                                            Historique
                                        </h2>
                                    </div>
                                    <div className="p-4">
                                        <div className="space-y-3">
                                            {need.status_history.map((history) => (
                                                <div key={history.id} className="flex gap-3">
                                                    <div className="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-gray-400" />
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm text-gray-900 dark:text-white">
                                                            {statusConfig[history.to_status]?.label || history.to_status}
                                                        </p>
                                                        {history.reason && (
                                                            <p className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                                                {history.reason}
                                                            </p>
                                                        )}
                                                        <p className="text-xs text-gray-500">
                                                            {history.user?.full_name} - {new Date(history.created_at).toLocaleDateString('fr-FR')}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Rejection Modal */}
            {showRejectModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center">
                    <div
                        className="absolute inset-0 bg-black/50"
                        onClick={() => setShowRejectModal(false)}
                    />
                    <div className="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Rejeter le besoin
                        </h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Veuillez fournir une raison pour le rejet de ce besoin.
                        </p>
                        <textarea
                            value={rejectionReason}
                            onChange={(e) => setRejectionReason(e.target.value)}
                            rows={4}
                            className="
                                w-full px-3 py-2 rounded-md border text-sm
                                bg-white dark:bg-gray-900
                                border-gray-300 dark:border-gray-600
                                text-gray-900 dark:text-white
                                focus:ring-2 focus:ring-primary focus:border-primary
                            "
                            placeholder="Raison du rejet..."
                        />
                        <div className="flex justify-end gap-3 mt-4">
                            <button
                                type="button"
                                onClick={() => setShowRejectModal(false)}
                                className="
                                    px-4 py-2 rounded-md text-sm
                                    border border-gray-300 dark:border-gray-600
                                    text-gray-700 dark:text-gray-300
                                    hover:bg-gray-50 dark:hover:bg-gray-700
                                "
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                onClick={handleReject}
                                disabled={isSubmitting || !rejectionReason.trim()}
                                className="
                                    px-4 py-2 rounded-md text-sm
                                    bg-red-600 text-white
                                    hover:bg-red-700
                                    disabled:opacity-50
                                "
                            >
                                Rejeter
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
                onConfirm={handleDelete}
                title="Supprimer le besoin"
                description="Êtes-vous sûr de vouloir supprimer ce besoin ? Cette action est irréversible."
            />

            {/* Withdraw Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showWithdrawDialog}
                onOpenChange={setShowWithdrawDialog}
                onConfirm={handleWithdraw}
                title="Retirer le besoin"
                description="Êtes-vous sûr de vouloir retirer ce besoin ? Il sera remis en brouillon et vous pourrez le modifier avant de le soumettre à nouveau."
                confirmText="Retirer"
            />

            {/* Cancel Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showCancelDialog}
                onOpenChange={setShowCancelDialog}
                onConfirm={handleCancel}
                title="Annuler le besoin"
                description="Êtes-vous sûr de vouloir annuler ce besoin ? Cette action ne peut pas être annulée."
                confirmText="Confirmer l'annulation"
            />
        </DashboardLayout>
    );
}
