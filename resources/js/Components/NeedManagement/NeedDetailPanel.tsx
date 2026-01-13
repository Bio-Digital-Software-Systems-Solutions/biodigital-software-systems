import React, { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import {
    XMarkIcon,
    CalendarIcon,
    CurrencyEuroIcon,
    UserCircleIcon,
    BuildingOfficeIcon,
    ChatBubbleLeftIcon,
    PaperClipIcon,
    ClockIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    PencilIcon,
    EyeIcon,
} from '@heroicons/react/24/outline';
import axios from 'axios';
import type { DepartmentNeed, NeedStatus, NeedComment, NeedAttachment, NeedStatusHistory } from '@/Types/need';

interface NeedDetailPanelProps {
    need: DepartmentNeed;
    onClose: () => void;
    onStatusChange?: (status: NeedStatus) => void;
    onAddComment?: (content: string) => void;
}

const statusConfig: Record<NeedStatus, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
    submitted: { label: 'Soumis', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
    under_review: { label: 'En révision', color: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' },
    approved: { label: 'Approuvé', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
    rejected: { label: 'Rejeté', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    in_progress: { label: 'En cours', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' },
    ordered: { label: 'Commandé', color: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400' },
    delivered: { label: 'Livré', color: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400' },
    completed: { label: 'Terminé', color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' },
    cancelled: { label: 'Annulé', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
};

const priorityConfig: Record<string, { label: string; color: string }> = {
    critical: { label: 'Critique', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    high: { label: 'Haute', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' },
    medium: { label: 'Moyenne', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
    low: { label: 'Basse', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
};

export default function NeedDetailPanel({
    need,
    onClose,
    onStatusChange,
    onAddComment,
}: NeedDetailPanelProps) {
    const [newComment, setNewComment] = useState('');
    const [activeTab, setActiveTab] = useState<'details' | 'comments' | 'history'>('details');
    const [history, setHistory] = useState<NeedStatusHistory[]>([]);
    const [historyLoading, setHistoryLoading] = useState(false);
    const [historyError, setHistoryError] = useState<string | null>(null);
    const [comments, setComments] = useState<NeedComment[]>([]);
    const [commentsLoading, setCommentsLoading] = useState(false);
    const [commentsError, setCommentsError] = useState<string | null>(null);

    const status = statusConfig[need.status];
    const priority = priorityConfig[need.priority];

    // Fetch history when tab is selected
    useEffect(() => {
        if (activeTab === 'history' && history.length === 0 && !historyLoading) {
            fetchHistory();
        }
    }, [activeTab]);

    // Fetch comments when tab is selected
    useEffect(() => {
        if (activeTab === 'comments' && comments.length === 0 && !commentsLoading) {
            fetchComments();
        }
    }, [activeTab]);

    // Reset state when need changes
    useEffect(() => {
        setHistory([]);
        setHistoryError(null);
        setComments([]);
        setCommentsError(null);
    }, [need.uuid]);

    const fetchHistory = async () => {
        setHistoryLoading(true);
        setHistoryError(null);
        try {
            const response = await axios.get(route('needs.history', need.uuid));
            if (response.data.success) {
                setHistory(response.data.history);
            }
        } catch (error) {
            console.error('Failed to fetch history:', error);
            setHistoryError('Impossible de charger l\'historique');
        } finally {
            setHistoryLoading(false);
        }
    };

    const fetchComments = async () => {
        setCommentsLoading(true);
        setCommentsError(null);
        try {
            const response = await axios.get(route('needs.comments.list', need.uuid));
            if (response.data.success) {
                setComments(response.data.comments);
            }
        } catch (error) {
            console.error('Failed to fetch comments:', error);
            setCommentsError('Impossible de charger les commentaires');
        } finally {
            setCommentsLoading(false);
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR',
        }).format(amount);
    };

    const handleSubmitComment = (e: React.FormEvent) => {
        e.preventDefault();
        if (newComment.trim() && onAddComment) {
            onAddComment(newComment.trim());
            setNewComment('');
            // Refresh comments after a short delay to allow the server to process
            setTimeout(() => fetchComments(), 500);
        }
    };

    return (
        <div className="h-full flex flex-col bg-white dark:bg-gray-800">
            {/* Header */}
            <div className="flex items-start justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div className="flex-1 min-w-0">
                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">
                        {need.reference}
                    </p>
                    <Link
                        href={route('needs.show', need.uuid)}
                        className="block text-lg font-semibold text-gray-900 dark:text-white truncate hover:text-primary dark:hover:text-primary transition-colors"
                    >
                        {need.title}
                    </Link>
                    <div className="flex items-center gap-2 mt-2">
                        <span className={`px-2 py-0.5 rounded text-xs font-medium ${status.color}`}>
                            {status.label}
                        </span>
                        <span className={`px-2 py-0.5 rounded text-xs font-medium ${priority.color}`}>
                            {priority.label}
                        </span>
                    </div>
                </div>
                <div className="flex items-center gap-1">
                    {/* View Details Button */}
                    <Link
                        href={route('needs.show', need.uuid)}
                        className="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 hover:text-primary transition-colors"
                        title="Voir les détails"
                    >
                        <EyeIcon className="h-5 w-5" />
                    </Link>
                    {/* Edit Button - only show for draft needs */}
                    {need.status === 'draft' && (
                        <Link
                            href={route('needs.edit', need.uuid)}
                            className="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 hover:text-primary transition-colors"
                            title="Modifier"
                        >
                            <PencilIcon className="h-5 w-5" />
                        </Link>
                    )}
                    {/* Close Button */}
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500"
                        title="Fermer"
                    >
                        <XMarkIcon className="h-5 w-5" />
                    </button>
                </div>
            </div>

            {/* Tabs */}
            <div className="flex border-b border-gray-200 dark:border-gray-700">
                {[
                    { id: 'details', label: 'Détails' },
                    { id: 'comments', label: 'Commentaires', count: comments.length || need.comments_count },
                    { id: 'history', label: 'Historique' },
                ].map((tab) => (
                    <button
                        key={tab.id}
                        type="button"
                        onClick={() => setActiveTab(tab.id as typeof activeTab)}
                        className={`
                            flex items-center gap-2 px-4 py-3 text-sm font-medium
                            border-b-2 -mb-px transition-colors
                            ${activeTab === tab.id
                                ? 'border-primary text-primary'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                            }
                        `}
                    >
                        {tab.label}
                        {tab.count !== undefined && tab.count > 0 && (
                            <span className="px-1.5 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                {tab.count}
                            </span>
                        )}
                    </button>
                ))}
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto">
                {activeTab === 'details' && (
                    <div className="p-6 space-y-6">
                        {/* Description */}
                        {need.description && (
                            <div>
                                <h3 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                    Description
                                </h3>
                                <p className="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                    {need.description}
                                </p>
                            </div>
                        )}

                        {/* Justification */}
                        {need.justification && (
                            <div>
                                <h3 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                    Justification
                                </h3>
                                <p className="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                    {need.justification}
                                </p>
                            </div>
                        )}

                        {/* Meta Info Grid */}
                        <div className="grid grid-cols-2 gap-4">
                            {/* Estimated Amount */}
                            {need.estimated_cost !== undefined && need.estimated_cost !== null && (
                                <div className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <CurrencyEuroIcon className="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Montant estimé
                                        </p>
                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                            {formatCurrency(Number(need.estimated_cost))}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Approved Amount */}
                            {need.approved_budget !== undefined && need.approved_budget !== null && (
                                <div className="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                    <CheckCircleIcon className="h-5 w-5 text-green-500" />
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Montant approuvé
                                        </p>
                                        <p className="text-sm font-medium text-green-700 dark:text-green-400">
                                            {formatCurrency(Number(need.approved_budget))}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Needed By Date */}
                            {need.needed_by_date && (
                                <div className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <CalendarIcon className="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Date souhaitée
                                        </p>
                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                            {formatDate(need.needed_by_date)}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Created At */}
                            <div className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <ClockIcon className="h-5 w-5 text-gray-400" />
                                <div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Créé le
                                    </p>
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                        {formatDate(need.created_at)}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* People */}
                        <div className="space-y-3">
                            {/* Created By */}
                            {need.created_by && (
                                <div className="flex items-center gap-3">
                                    <UserCircleIcon className="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Créé par
                                        </p>
                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                            {need.created_by.full_name}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Assigned To */}
                            {need.assigned_to && (
                                <div className="flex items-center gap-3">
                                    <UserCircleIcon className="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Assigné à
                                        </p>
                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                            {need.assigned_to.full_name}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Department */}
                            {need.department && (
                                <div className="flex items-center gap-3">
                                    <BuildingOfficeIcon className="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Département
                                        </p>
                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                            {need.department.name}
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Attachments */}
                        {need.attachments && need.attachments.length > 0 && (
                            <div>
                                <h3 className="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                    <PaperClipIcon className="h-4 w-4" />
                                    Pièces jointes ({need.attachments.length})
                                </h3>
                                <div className="space-y-2">
                                    {need.attachments.map((attachment) => (
                                        <a
                                            key={attachment.uuid}
                                            href={attachment.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="
                                                flex items-center gap-3 p-2 rounded-lg
                                                bg-gray-50 dark:bg-gray-900
                                                hover:bg-gray-100 dark:hover:bg-gray-800
                                                transition-colors
                                            "
                                        >
                                            <PaperClipIcon className="h-4 w-4 text-gray-400" />
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm text-gray-900 dark:text-white truncate">
                                                    {attachment.original_filename}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    {(attachment.size / 1024).toFixed(1)} Ko
                                                </p>
                                            </div>
                                        </a>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {activeTab === 'comments' && (
                    <div className="flex flex-col h-full">
                        {/* Comments List */}
                        <div className="flex-1 overflow-y-auto p-6 space-y-4">
                            {/* Loading state */}
                            {commentsLoading && (
                                <div className="flex items-center justify-center py-8">
                                    <ArrowPathIcon className="h-6 w-6 text-primary animate-spin" />
                                    <span className="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                        Chargement...
                                    </span>
                                </div>
                            )}

                            {/* Error state */}
                            {commentsError && !commentsLoading && (
                                <div className="text-center py-8">
                                    <p className="text-sm text-red-500 dark:text-red-400 mb-2">
                                        {commentsError}
                                    </p>
                                    <button
                                        type="button"
                                        onClick={fetchComments}
                                        className="text-sm text-primary hover:underline"
                                    >
                                        Réessayer
                                    </button>
                                </div>
                            )}

                            {/* Empty state */}
                            {!commentsLoading && !commentsError && comments.length === 0 && (
                                <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                                    Aucun commentaire
                                </p>
                            )}

                            {/* Comments list */}
                            {!commentsLoading && !commentsError && comments.map((comment) => (
                                <div
                                    key={comment.uuid}
                                    className="flex gap-3"
                                >
                                    {comment.user?.avatar ? (
                                        <img
                                            src={comment.user.avatar}
                                            alt={comment.user.full_name}
                                            className="h-8 w-8 rounded-full"
                                        />
                                    ) : (
                                        <UserCircleIcon className="h-8 w-8 text-gray-400" />
                                    )}
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2 mb-1">
                                            <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                {comment.user?.full_name || 'Utilisateur'}
                                            </span>
                                            <span className="text-xs text-gray-500">
                                                {formatDate(comment.created_at)}
                                            </span>
                                            {comment.is_internal && (
                                                <span className="px-1.5 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                    Interne
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {comment.content}
                                        </p>
                                        {/* Replies */}
                                        {comment.replies && comment.replies.length > 0 && (
                                            <div className="mt-3 ml-4 space-y-3 border-l-2 border-gray-200 dark:border-gray-700 pl-4">
                                                {comment.replies.map((reply) => (
                                                    <div key={reply.uuid} className="flex gap-2">
                                                        <UserCircleIcon className="h-6 w-6 text-gray-400" />
                                                        <div className="flex-1">
                                                            <div className="flex items-center gap-2 mb-0.5">
                                                                <span className="text-xs font-medium text-gray-900 dark:text-white">
                                                                    {reply.user?.full_name || 'Utilisateur'}
                                                                </span>
                                                                <span className="text-xs text-gray-500">
                                                                    {formatDate(reply.created_at)}
                                                                </span>
                                                            </div>
                                                            <p className="text-xs text-gray-600 dark:text-gray-400">
                                                                {reply.content}
                                                            </p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Comment Input */}
                        {onAddComment && (
                            <form
                                onSubmit={handleSubmitComment}
                                className="p-4 border-t border-gray-200 dark:border-gray-700"
                            >
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        value={newComment}
                                        onChange={(e) => setNewComment(e.target.value)}
                                        placeholder="Ajouter un commentaire..."
                                        className="
                                            flex-1 px-3 py-2 rounded-md border text-sm
                                            bg-white dark:bg-gray-900
                                            border-gray-300 dark:border-gray-600
                                            text-gray-900 dark:text-white
                                            focus:ring-2 focus:ring-primary focus:border-primary
                                        "
                                    />
                                    <button
                                        type="submit"
                                        disabled={!newComment.trim()}
                                        className="
                                            px-4 py-2 rounded-md text-sm font-medium
                                            bg-primary text-white
                                            hover:bg-primary/90
                                            disabled:opacity-50 disabled:cursor-not-allowed
                                        "
                                    >
                                        Envoyer
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                )}

                {activeTab === 'history' && (
                    <div className="p-6">
                        {/* Loading state */}
                        {historyLoading && (
                            <div className="flex items-center justify-center py-8">
                                <ArrowPathIcon className="h-6 w-6 text-primary animate-spin" />
                                <span className="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                    Chargement...
                                </span>
                            </div>
                        )}

                        {/* Error state */}
                        {historyError && !historyLoading && (
                            <div className="text-center py-8">
                                <p className="text-sm text-red-500 dark:text-red-400 mb-2">
                                    {historyError}
                                </p>
                                <button
                                    type="button"
                                    onClick={fetchHistory}
                                    className="text-sm text-primary hover:underline"
                                >
                                    Réessayer
                                </button>
                            </div>
                        )}

                        {/* Empty state */}
                        {!historyLoading && !historyError && history.length === 0 && (
                            <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                                Aucun historique de changement de statut
                            </p>
                        )}

                        {/* History list */}
                        {!historyLoading && !historyError && history.length > 0 && (
                            <div className="space-y-4">
                                {history.map((entry, index) => (
                                    <div
                                        key={entry.id}
                                        className="relative flex gap-4 pb-4"
                                    >
                                        {index < history.length - 1 && (
                                            <div className="absolute left-[11px] top-6 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700" />
                                        )}
                                        <div className="h-6 w-6 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                                            <CheckCircleIcon className="h-4 w-4 text-white" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm text-gray-900 dark:text-white">
                                                {entry.from_status ? (
                                                    <>
                                                        {statusConfig[entry.from_status]?.label || entry.from_status}
                                                        {' → '}
                                                    </>
                                                ) : (
                                                    'Créé avec le statut: '
                                                )}
                                                <span className="font-medium">
                                                    {statusConfig[entry.to_status]?.label || entry.to_status}
                                                </span>
                                            </p>
                                            {entry.reason && (
                                                <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                    {entry.reason}
                                                </p>
                                            )}
                                            <p className="text-xs text-gray-400 mt-1">
                                                {entry.user?.full_name || 'Système'} • {formatDate(entry.created_at)}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
