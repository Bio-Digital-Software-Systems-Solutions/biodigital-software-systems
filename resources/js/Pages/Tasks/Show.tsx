import React, { useState, useEffect, useCallback } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Task, Program, Status, User, TaskParticipant, TaskComment, TaskAttachment, PageProps } from '@/Types';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { useToast } from '@/Components/ui/toast';
import { useConfirm } from '@/Components/ui/confirm-dialog';
import TaskCalendarWidget from '@/Components/Calendar/TaskCalendarWidget';
import CreateTaskAppointmentModal from '@/Components/Calendar/CreateTaskAppointmentModal';
import TaskAppointmentDetailModal from '@/Components/Calendar/TaskAppointmentDetailModal';
import axios from 'axios';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    UserPlusIcon,
    ChatBubbleLeftIcon,
    PaperClipIcon,
    DocumentTextIcon,
    PhotoIcon,
    VideoCameraIcon,
    CheckCircleIcon,
    ArrowUturnLeftIcon,
    PlusIcon,
    MinusIcon,
    CalendarIcon,
} from '@heroicons/react/24/outline';

interface Project {
    id: number;
    uuid: string;
    name: string;
}

interface ActivityStatus {
    id: number;
    name: string;
    color: string;
}

interface Activity {
    id: number;
    description: string;
    old_status: ActivityStatus | null;
    new_status: ActivityStatus | null;
    causer: {
        id: number;
        first_name: string;
        last_name: string;
    } | null;
    created_at: string;
}

interface Props extends PageProps {
    task: Task & {
        program?: Program;
        project?: Project;
        status: Status;
        assigned_user: User;
        participants?: TaskParticipant[];
        comments?: TaskComment[];
        task_attachments?: TaskAttachment[];
    };
    users: User[];
    activities: Activity[];
}

export default function Show({ task, users, activities }: Props) {
    const { auth, flash } = usePage<PageProps & { flash?: { success?: string; error?: string } }>().props;
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [commentContent, setCommentContent] = useState('');
    const [replyingTo, setReplyingTo] = useState<number | null>(null);
    const [replyContent, setReplyContent] = useState('');
    const [showAddParticipant, setShowAddParticipant] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState('');
    const [selectedRole, setSelectedRole] = useState<string>('member');
    const [editingProgress, setEditingProgress] = useState(false);
    const [progressValue, setProgressValue] = useState(task.progress || 0);
    const [historyExpanded, setHistoryExpanded] = useState(false);
    const [calendarExpanded, setCalendarExpanded] = useState(true);
    const { showSuccess, showError } = useToast();
    const confirm = useConfirm();

    // Calendar state
    const [appointments, setAppointments] = useState<any[]>([]);
    const [loadingAppointments, setLoadingAppointments] = useState(false);
    const [showCreateAppointmentModal, setShowCreateAppointmentModal] = useState(false);
    const [selectedAppointmentDate, setSelectedAppointmentDate] = useState<Date>(new Date());
    const [showAppointmentDetailModal, setShowAppointmentDetailModal] = useState(false);
    const [selectedAppointment, setSelectedAppointment] = useState<any>(null);

    // Show flash messages
    useEffect(() => {
        if (flash?.success) {
            showSuccess(flash.success);
        }
        if (flash?.error) {
            showError(flash.error);
        }
    }, [flash]);

    // Load appointments for the task
    const fetchAppointments = useCallback(async () => {
        setLoadingAppointments(true);
        try {
            const response = await axios.get(`/api/tasks/${task.uuid}/appointments`);
            setAppointments(response.data.data || []);
        } catch (error) {
            console.error('Error fetching appointments:', error);
        } finally {
            setLoadingAppointments(false);
        }
    }, [task.uuid]);

    useEffect(() => {
        fetchAppointments();
    }, [fetchAppointments]);

    const handleCreateAppointmentClick = (date: Date) => {
        setSelectedAppointmentDate(date);
        setShowCreateAppointmentModal(true);
    };

    const handleAppointmentClick = (appointment: any) => {
        setSelectedAppointment(appointment);
        setShowAppointmentDetailModal(true);
    };

    const handleAppointmentCreated = () => {
        fetchAppointments();
    };

    const handleAppointmentUpdated = () => {
        fetchAppointments();
    };

    const handleAppointmentDeleted = () => {
        fetchAppointments();
        setShowAppointmentDetailModal(false);
    };

    // Check if task is completed or closed (cannot delete comments)
    const isTaskClosed = ['completed', 'closed', 'terminé', 'fermé'].includes(task.status?.name?.toLowerCase() || '');

    // Check if current user is super-admin
    const isSuperAdmin = auth.user?.roles?.some((role: { name: string }) => role.name === 'super-admin') || false;

    // Check if user can delete a specific comment
    const canDeleteComment = (comment: TaskComment): boolean => {
        if (isTaskClosed) return false;
        const isAuthor = comment.user_id === auth.user?.id;
        return isAuthor || isSuperAdmin;
    };

    const handleDelete = () => {
        setDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        router.delete(route('tasks.destroy', task.uuid), {
            onSuccess: () => {
                setDeleteDialogOpen(false);
            },
        });
    };

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'high': return 'text-red-600 bg-red-50 dark:bg-red-900/30';
            case 'medium': return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/30';
            case 'low': return 'text-green-600 bg-green-50 dark:bg-green-900/30';
            default: return 'text-gray-600 bg-gray-50 dark:bg-gray-900/30';
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed': return 'text-green-600 bg-green-50 dark:bg-green-900/30';
            case 'in_progress': return 'text-primary bg-blue-50 dark:bg-blue-900/30';
            case 'pending': return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/30';
            default: return 'text-gray-600 bg-gray-50 dark:bg-gray-900/30';
        }
    };

    const handleProgressUpdate = async () => {
        try {
            const response = await fetch(route('tasks.update-progress', task.uuid), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ progress: progressValue }),
            });
            if (response.ok) {
                showSuccess('Progression mise à jour avec succès');
                setEditingProgress(false);
                router.reload({ preserveState: true, preserveScroll: true } as any);
            } else {
                showError('Échec de la mise à jour de la progression');
            }
        } catch (error) {
            showError('Échec de la mise à jour de la progression');
        }
    };

    const handleAddParticipant = async () => {
        if (!selectedUserId) return;

        try {
            const response = await fetch(route('tasks.participants.add', task.uuid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    user_id: selectedUserId,
                    role: selectedRole,
                }),
            });
            if (response.ok) {
                showSuccess('Participant ajouté avec succès');
                router.reload({ preserveState: true, preserveScroll: true } as any);
                setShowAddParticipant(false);
                setSelectedUserId('');
            } else {
                const data = await response.json();
                showError(data.message || 'Échec de l\'ajout du participant');
            }
        } catch (error) {
            showError('Échec de l\'ajout du participant');
        }
    };

    const handleRemoveParticipant = async (participantId: number) => {
        const confirmed = await confirm.confirm({
            title: 'Retirer le participant',
            message: 'Êtes-vous sûr de vouloir retirer ce participant de la tâche?',
            confirmText: 'Retirer',
            cancelText: 'Annuler',
            type: 'warning',
        });

        if (!confirmed) return;

        try {
            const response = await fetch(route('tasks.participants.remove', { task: task.uuid, participant: participantId }), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            if (response.ok) {
                showSuccess('Participant retiré avec succès');
                router.reload({ preserveState: true, preserveScroll: true } as any);
            } else {
                showError('Échec du retrait du participant');
            }
        } catch (error) {
            showError('Échec du retrait du participant');
        }
    };

    const handleAddComment = async (e: React.FormEvent, parentId?: number) => {
        e.preventDefault();
        const content = parentId ? replyContent : commentContent;
        if (!content.trim()) return;

        try {
            const response = await fetch(route('tasks.comments.add', task.uuid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ content, parent_id: parentId || null }),
            });
            if (response.ok) {
                showSuccess(parentId ? 'Réponse ajoutée avec succès' : 'Commentaire ajouté avec succès');
                if (parentId) {
                    setReplyContent('');
                    setReplyingTo(null);
                } else {
                    setCommentContent('');
                }
                router.reload({ preserveState: true, preserveScroll: true } as any);
            } else {
                showError(parentId ? 'Échec de l\'ajout de la réponse' : 'Échec de l\'ajout du commentaire');
            }
        } catch (error) {
            showError(parentId ? 'Échec de l\'ajout de la réponse' : 'Échec de l\'ajout du commentaire');
        }
    };

    const handleDeleteComment = async (commentId: number) => {
        const confirmed = await confirm.confirm({
            title: 'Supprimer le commentaire',
            message: 'Êtes-vous sûr de vouloir supprimer ce commentaire?',
            confirmText: 'Supprimer',
            cancelText: 'Annuler',
            type: 'danger',
        });

        if (!confirmed) return;

        try {
            const response = await fetch(route('tasks.comments.delete', { task: task.uuid, comment: commentId }), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            if (response.ok) {
                showSuccess('Commentaire supprimé avec succès');
                router.reload({ preserveState: true, preserveScroll: true } as any);
            } else {
                showError('Échec de la suppression du commentaire');
            }
        } catch (error) {
            showError('Échec de la suppression du commentaire');
        }
    };

    const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setUploading(true);
        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch(route('tasks.attachments.add', task.uuid), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            if (response.ok) {
                showSuccess('Fichier téléchargé avec succès');
                router.reload({ preserveState: true, preserveScroll: true } as any);
            } else {
                showError('Échec du téléchargement du fichier');
            }
        } catch (error) {
            showError('Échec du téléchargement du fichier');
        } finally {
            setUploading(false);
            e.target.value = '';
        }
    };

    const handleDeleteAttachment = async (attachmentId: number) => {
        const confirmed = await confirm.confirm({
            title: 'Supprimer le fichier',
            message: 'Êtes-vous sûr de vouloir supprimer ce fichier?',
            confirmText: 'Supprimer',
            cancelText: 'Annuler',
            type: 'danger',
        });

        if (!confirmed) return;

        try {
            const response = await fetch(route('tasks.attachments.delete', { task: task.uuid, attachment: attachmentId }), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            if (response.ok) {
                showSuccess('Fichier supprimé avec succès');
                router.reload({ preserveState: true, preserveScroll: true } as any);
            } else {
                showError('Échec de la suppression du fichier');
            }
        } catch (error) {
            showError('Échec de la suppression du fichier');
        }
    };

    const getFileIcon = (fileType: string) => {
        if (fileType.match(/^(jpg|jpeg|png|gif|webp|svg)$/i)) {
            return <PhotoIcon className="h-8 w-8 text-primary" />;
        }
        if (fileType.match(/^(mp4|avi|mov|wmv|webm)$/i)) {
            return <VideoCameraIcon className="h-8 w-8 text-purple-500" />;
        }
        return <DocumentTextIcon className="h-8 w-8 text-gray-500" />;
    };

    const formatFileSize = (bytes: number): string => {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        while (size > 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        return `${size.toFixed(2)} ${units[unitIndex]}`;
    };

    return (
        <DashboardLayout>
            <Head title={`Task - ${task.title}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6 text-gray-900 dark:text-gray-100">
                        <div className="flex justify-between items-start mb-6">
                            <div className="flex items-center">
                                <Link
                                    href={route('tasks.index')}
                                    className="flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mr-4"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-1" />
                                    Retour aux tâches
                                </Link>
                                <h1 className="text-2xl font-semibold">{task.title}</h1>
                            </div>

                            <div className="flex space-x-2">
                                <Link
                                    href={route('tasks.edit', task.uuid)}
                                    className="flex items-center px-3 py-2 bg-primary hover:bg-primary text-white text-sm font-medium rounded-md"
                                >
                                    <PencilIcon className="w-4 h-4 mr-1" />
                                    Modifier
                                </Link>
                                <button
                                    onClick={handleDelete}
                                    className="flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md"
                                >
                                    <TrashIcon className="w-4 h-4 mr-1" />
                                    Supprimer
                                </button>
                            </div>
                        </div>

                        {/* Progress Bar Section */}
                        <div className="mb-6">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Progression</span>
                                <div className="flex items-center gap-2">
                                    {editingProgress ? (
                                        <>
                                            <input
                                                type="number"
                                                min="0"
                                                max="100"
                                                value={progressValue}
                                                onChange={(e) => setProgressValue(Math.min(100, Math.max(0, parseInt(e.target.value) || 0)))}
                                                className="w-16 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white"
                                            />
                                            <span className="text-sm text-gray-500">%</span>
                                            <button
                                                onClick={handleProgressUpdate}
                                                className="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700"
                                            >
                                                Enregistrer
                                            </button>
                                            <button
                                                onClick={() => {
                                                    setEditingProgress(false);
                                                    setProgressValue(task.progress || 0);
                                                }}
                                                className="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-500"
                                            >
                                                Annuler
                                            </button>
                                        </>
                                    ) : (
                                        <>
                                            <span className="text-sm font-bold text-gray-900 dark:text-white">{task.progress || 0}%</span>
                                            <button
                                                onClick={() => setEditingProgress(true)}
                                                className="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-500"
                                            >
                                                Modifier
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>
                            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div
                                    className="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300 flex items-center justify-end pr-2"
                                    style={{ width: `${editingProgress ? progressValue : (task.progress || 0)}%` }}
                                >
                                    {(editingProgress ? progressValue : (task.progress || 0)) > 10 && (
                                        <CheckCircleIcon className="h-3 w-3 text-white" />
                                    )}
                                </div>
                            </div>
                            {editingProgress && (
                                <input
                                    type="range"
                                    min="0"
                                    max="100"
                                    value={progressValue}
                                    onChange={(e) => setProgressValue(parseInt(e.target.value))}
                                    className="w-full mt-2 accent-primary"
                                />
                            )}
                        </div>
                    </div>
                </div>

                {/* Main Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column - Details */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Task Details Card */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Détails de la tâche</h3>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                {task.project && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Projet</dt>
                                        <dd className="mt-1">
                                            <Link
                                                href={route('projects.show', task.project.uuid)}
                                                className="text-sm text-primary hover:text-primary-dark hover:underline"
                                            >
                                                {task.project.name}
                                            </Link>
                                        </dd>
                                    </div>
                                )}

                                {task.program && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Programme</dt>
                                        <dd className="mt-1">
                                            <Link
                                                href={route('programs.show', task.program.uuid)}
                                                className="text-sm text-primary hover:text-primary-dark hover:underline"
                                            >
                                                {task.program.name}
                                            </Link>
                                        </dd>
                                    </div>
                                )}

                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Statut</dt>
                                    <dd className="mt-1">
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(task.status?.name)}`}>
                                            {task.status?.name}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Priorité</dt>
                                    <dd className="mt-1">
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColor(task.priority)}`}>
                                            {task.priority}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Assigné à</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {task.assigned_user
                                            ? `${task.assigned_user.first_name} ${task.assigned_user.last_name}`
                                            : 'Non assigné'}
                                    </dd>
                                </div>

                                {task.due_date && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Date d'échéance</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {new Date(task.due_date).toLocaleDateString('fr-FR')}
                                        </dd>
                                    </div>
                                )}

                                {task.estimated_hours && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Heures estimées</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{task.estimated_hours}h</dd>
                                    </div>
                                )}

                                {task.actual_hours && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Heures réelles</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{task.actual_hours}h</dd>
                                    </div>
                                )}
                            </div>

                            {task.description && (
                                <div className="mt-6">
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</dt>
                                    <dd className="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        {task.description}
                                    </dd>
                                </div>
                            )}

                            {task.notes && (
                                <div className="mt-4">
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Notes</dt>
                                    <dd className="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        {task.notes}
                                    </dd>
                                </div>
                            )}
                        </div>

                        {/* Comments Section */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 className="text-xl font-semibold dark:text-white mb-4 flex items-center gap-2">
                                <ChatBubbleLeftIcon className="h-6 w-6" />
                                Commentaires ({task.comments?.length || 0})
                            </h2>

                            {/* Add Comment Form */}
                            {!isTaskClosed ? (
                                <form onSubmit={handleAddComment} className="mb-6">
                                    <textarea
                                        value={commentContent}
                                        onChange={(e) => setCommentContent(e.target.value)}
                                        placeholder="Ajouter un commentaire..."
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white resize-none"
                                        rows={3}
                                    />
                                    <button
                                        type="submit"
                                        disabled={!commentContent.trim()}
                                        className="mt-2 px-4 py-2 bg-primary text-white rounded hover:bg-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Publier
                                    </button>
                                </form>
                            ) : (
                                <div className="mb-6 p-4 bg-gray-100 dark:bg-gray-700 rounded text-sm text-gray-600 dark:text-gray-400">
                                    Les commentaires sont désactivés pour les tâches terminées ou fermées.
                                </div>
                            )}

                            {/* Comments List */}
                            <div className="space-y-4">
                                {task.comments && task.comments.length > 0 ? (
                                    task.comments.map((comment) => (
                                        <div key={comment.id} className="space-y-3">
                                            {/* Main Comment */}
                                            <div className="flex gap-3">
                                                <div className="w-8 h-8 rounded-full bg-gray-500 flex items-center justify-center text-white text-sm font-semibold flex-shrink-0">
                                                    {comment.user.first_name?.charAt(0)}{comment.user.last_name?.charAt(0)}
                                                </div>
                                                <div className="flex-1">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <span className="font-medium dark:text-white">
                                                                {comment.user.first_name} {comment.user.last_name}
                                                            </span>
                                                            <span className="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                                                {new Date(comment.created_at).toLocaleString('fr-FR')}
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            {!isTaskClosed && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setReplyingTo(replyingTo === comment.id ? null : comment.id)}
                                                                    className="text-gray-500 hover:text-primary flex items-center gap-1 text-sm"
                                                                    title="Répondre"
                                                                >
                                                                    <ArrowUturnLeftIcon className="h-4 w-4" />
                                                                    <span className="hidden sm:inline">Répondre</span>
                                                                </button>
                                                            )}
                                                            {canDeleteComment(comment) && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDeleteComment(comment.id)}
                                                                    className="text-red-600 hover:text-red-700"
                                                                    title="Supprimer"
                                                                >
                                                                    <TrashIcon className="h-4 w-4" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <p className="text-gray-700 dark:text-gray-300 mt-1">{comment.content}</p>

                                                    {/* Reply Form */}
                                                    {replyingTo === comment.id && (
                                                        <form onSubmit={(e) => handleAddComment(e, comment.id)} className="mt-3">
                                                            <textarea
                                                                value={replyContent}
                                                                onChange={(e) => setReplyContent(e.target.value)}
                                                                placeholder="Écrire une réponse..."
                                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white resize-none text-sm"
                                                                rows={2}
                                                                autoFocus
                                                            />
                                                            <div className="flex gap-2 mt-2">
                                                                <button
                                                                    type="submit"
                                                                    disabled={!replyContent.trim()}
                                                                    className="px-3 py-1 bg-primary text-white text-sm rounded hover:bg-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                                                >
                                                                    Répondre
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => {
                                                                        setReplyingTo(null);
                                                                        setReplyContent('');
                                                                    }}
                                                                    className="px-3 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm rounded hover:bg-gray-300 dark:hover:bg-gray-500"
                                                                >
                                                                    Annuler
                                                                </button>
                                                            </div>
                                                        </form>
                                                    )}

                                                    {/* Replies */}
                                                    {comment.replies && comment.replies.length > 0 && (
                                                        <div className="mt-3 ml-4 border-l-2 border-gray-200 dark:border-gray-600 pl-4 space-y-3">
                                                            {comment.replies.map((reply) => (
                                                                <div key={reply.id} className="flex gap-3">
                                                                    <div className="w-6 h-6 rounded-full bg-gray-400 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                                                        {reply.user.first_name?.charAt(0)}{reply.user.last_name?.charAt(0)}
                                                                    </div>
                                                                    <div className="flex-1">
                                                                        <div className="flex items-center justify-between">
                                                                            <div>
                                                                                <span className="font-medium text-sm dark:text-white">
                                                                                    {reply.user.first_name} {reply.user.last_name}
                                                                                </span>
                                                                                <span className="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                                                                    {new Date(reply.created_at).toLocaleString('fr-FR')}
                                                                                </span>
                                                                            </div>
                                                                            {canDeleteComment(reply) && (
                                                                                <button
                                                                                    type="button"
                                                                                    onClick={() => handleDeleteComment(reply.id)}
                                                                                    className="text-red-600 hover:text-red-700"
                                                                                    title="Supprimer"
                                                                                >
                                                                                    <TrashIcon className="h-3 w-3" />
                                                                                </button>
                                                                            )}
                                                                        </div>
                                                                        <p className="text-gray-700 dark:text-gray-300 text-sm mt-1">{reply.content}</p>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                        Aucun commentaire pour le moment
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Attachments Section */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h2 className="text-xl font-semibold dark:text-white flex items-center gap-2">
                                    <PaperClipIcon className="h-6 w-6" />
                                    Documents ({task.task_attachments?.length || 0})
                                </h2>
                                <label className="px-3 py-1 bg-primary text-white text-sm rounded hover:bg-primary cursor-pointer flex items-center gap-1">
                                    <input
                                        type="file"
                                        onChange={handleFileUpload}
                                        className="hidden"
                                        disabled={uploading}
                                    />
                                    {uploading ? 'Téléchargement...' : 'Ajouter un fichier'}
                                </label>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                {task.task_attachments && task.task_attachments.length > 0 ? (
                                    task.task_attachments.map((attachment) => (
                                        <div
                                            key={attachment.id}
                                            className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700"
                                        >
                                            <div className="flex-shrink-0">{getFileIcon(attachment.file_type)}</div>
                                            <div className="flex-1 min-w-0">
                                                <a
                                                    href={attachment.file_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-sm font-medium text-primary hover:text-primary block truncate"
                                                >
                                                    {attachment.file_name}
                                                </a>
                                                <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                    <span>{formatFileSize(attachment.file_size)}</span>
                                                    {attachment.user && (
                                                        <>
                                                            <span>•</span>
                                                            <span>
                                                                {attachment.user.first_name} {attachment.user.last_name}
                                                            </span>
                                                        </>
                                                    )}
                                                </div>
                                            </div>
                                            <button
                                                onClick={() => handleDeleteAttachment(attachment.id)}
                                                className="text-red-600 hover:text-red-700 flex-shrink-0"
                                            >
                                                <TrashIcon className="h-4 w-4" />
                                            </button>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4 col-span-2">
                                        Aucun document pour le moment
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Right Column - Participants & Timestamps */}
                    <div className="space-y-6">
                        {/* Participants Section */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h2 className="text-lg font-semibold dark:text-white">Participants</h2>
                                <button
                                    onClick={() => setShowAddParticipant(!showAddParticipant)}
                                    className="px-3 py-1 bg-primary text-white text-sm rounded hover:bg-primary flex items-center gap-1"
                                >
                                    <UserPlusIcon className="h-4 w-4" />
                                    Ajouter
                                </button>
                            </div>

                            {showAddParticipant && (
                                <div className="mb-4 p-4 bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                                    <div className="space-y-3 mb-3">
                                        <select
                                            value={selectedUserId}
                                            onChange={(e) => setSelectedUserId(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-800 dark:text-white"
                                        >
                                            <option value="">Sélectionner un utilisateur</option>
                                            {users.map((user) => (
                                                <option key={user.id} value={user.id}>
                                                    {user.first_name} {user.last_name}
                                                </option>
                                            ))}
                                        </select>
                                        <select
                                            value={selectedRole}
                                            onChange={(e) => setSelectedRole(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-800 dark:text-white"
                                        >
                                            <option value="member">Membre</option>
                                            <option value="contributor">Contributeur</option>
                                            <option value="reviewer">Réviseur</option>
                                            <option value="observer">Observateur</option>
                                        </select>
                                    </div>
                                    <button
                                        onClick={handleAddParticipant}
                                        className="px-4 py-2 bg-primary text-white rounded hover:bg-primary text-sm"
                                    >
                                        Ajouter participant
                                    </button>
                                </div>
                            )}

                            <div className="space-y-3">
                                {/* Assigned User (highlighted) */}
                                {task.assigned_user && (
                                    <div className="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-semibold">
                                                {task.assigned_user.first_name?.charAt(0)}{task.assigned_user.last_name?.charAt(0)}
                                            </div>
                                            <div>
                                                <p className="font-medium dark:text-white">
                                                    {task.assigned_user.first_name} {task.assigned_user.last_name}
                                                </p>
                                                <p className="text-xs text-gray-600 dark:text-gray-400">Assigné</p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Participants */}
                                {task.participants && task.participants.length > 0 ? (
                                    task.participants.map((participant) => (
                                        <div
                                            key={participant.id}
                                            className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded"
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-full bg-gray-500 flex items-center justify-center text-white font-semibold">
                                                    {participant.user.first_name?.charAt(0)}{participant.user.last_name?.charAt(0)}
                                                </div>
                                                <div>
                                                    <p className="font-medium dark:text-white">
                                                        {participant.user.first_name} {participant.user.last_name}
                                                    </p>
                                                    <p className="text-xs text-gray-600 dark:text-gray-400 capitalize">
                                                        {participant.role}
                                                    </p>
                                                </div>
                                            </div>
                                            <button
                                                onClick={() => handleRemoveParticipant(participant.id)}
                                                className="text-red-600 hover:text-red-700"
                                            >
                                                <TrashIcon className="h-4 w-4" />
                                            </button>
                                        </div>
                                    ))
                                ) : (
                                    !task.assigned_user && (
                                        <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                            Aucun participant pour le moment
                                        </p>
                                    )
                                )}
                            </div>
                        </div>

                        {/* Timestamps */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Horodatage</h3>
                            <div className="space-y-4">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Créé le</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {new Date(task.created_at).toLocaleString('fr-FR')}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Mis à jour le</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {new Date(task.updated_at).toLocaleString('fr-FR')}
                                    </dd>
                                </div>
                            </div>
                        </div>

                        {/* Calendar Widget */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <button
                                type="button"
                                onClick={() => setCalendarExpanded(!calendarExpanded)}
                                className="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors rounded-t-lg"
                            >
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <CalendarIcon className="h-5 w-5" />
                                    Calendrier
                                    {appointments.length > 0 && (
                                        <span className="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                            ({appointments.length})
                                        </span>
                                    )}
                                </h3>
                                {calendarExpanded ? (
                                    <MinusIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                ) : (
                                    <PlusIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                )}
                            </button>
                            {calendarExpanded && (
                                <div className="px-6 pb-6">
                                    {loadingAppointments ? (
                                        <div className="flex items-center justify-center py-8">
                                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                        </div>
                                    ) : (
                                        <TaskCalendarWidget
                                            taskId={task.id}
                                            taskUuid={task.uuid}
                                            appointments={appointments}
                                            onCreateClick={handleCreateAppointmentClick}
                                            onAppointmentClick={handleAppointmentClick}
                                        />
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Activity Feed */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <button
                                type="button"
                                onClick={() => setHistoryExpanded(!historyExpanded)}
                                className="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors rounded-lg"
                            >
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Historique
                                    {activities && activities.length > 0 && (
                                        <span className="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                            ({activities.length})
                                        </span>
                                    )}
                                </h3>
                                {historyExpanded ? (
                                    <MinusIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                ) : (
                                    <PlusIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                )}
                            </button>
                            {historyExpanded && (
                                <div className="px-6 pb-6">
                                    {activities && activities.length > 0 ? (
                                        <div className="relative">
                                            {/* Timeline line */}
                                            <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700" />

                                            <div className="space-y-6">
                                                {activities.map((activity, index) => {
                                                    const isCreated = activity.description === 'created';
                                                    const isCompleted = activity.new_status?.name?.toLowerCase() === 'completed';
                                                    const isInProgress = activity.new_status?.name?.toLowerCase() === 'in_progress';

                                                    return (
                                                        <div key={activity.id} className="relative flex items-start gap-4">
                                                            {/* Timeline dot */}
                                                            <div className={`relative z-10 flex items-center justify-center w-8 h-8 rounded-full border-2 ${
                                                                isCompleted
                                                                    ? 'bg-green-100 border-green-500 dark:bg-green-900/30'
                                                                    : isInProgress
                                                                        ? 'bg-blue-100 border-blue-500 dark:bg-blue-900/30'
                                                                        : isCreated
                                                                            ? 'bg-purple-100 border-purple-500 dark:bg-purple-900/30'
                                                                            : 'bg-gray-100 border-gray-300 dark:bg-gray-700 dark:border-gray-600'
                                                            }`}>
                                                                {isCompleted ? (
                                                                    <CheckCircleIcon className="w-4 h-4 text-green-600" />
                                                                ) : (
                                                                    <span className={`text-xs font-semibold ${
                                                                        isInProgress
                                                                            ? 'text-blue-600'
                                                                            : isCreated
                                                                                ? 'text-purple-600'
                                                                                : 'text-gray-500 dark:text-gray-400'
                                                                    }`}>
                                                                        {activities.length - index}
                                                                    </span>
                                                                )}
                                                            </div>

                                                            {/* Content */}
                                                            <div className="flex-1 min-w-0 pb-2">
                                                                <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                    {isCreated ? (
                                                                        'Tâche créée'
                                                                    ) : activity.new_status ? (
                                                                        <>
                                                                            Statut changé
                                                                            {activity.old_status && (
                                                                                <>
                                                                                    {' de '}
                                                                                    <span
                                                                                        className="inline-flex px-2 py-0.5 text-xs font-medium rounded-full"
                                                                                        style={{
                                                                                            backgroundColor: `${activity.old_status.color}20`,
                                                                                            color: activity.old_status.color,
                                                                                        }}
                                                                                    >
                                                                                        {activity.old_status.name}
                                                                                    </span>
                                                                                </>
                                                                            )}
                                                                            {' à '}
                                                                            <span
                                                                                className="inline-flex px-2 py-0.5 text-xs font-medium rounded-full"
                                                                                style={{
                                                                                    backgroundColor: `${activity.new_status.color}20`,
                                                                                    color: activity.new_status.color,
                                                                                }}
                                                                            >
                                                                                {activity.new_status.name}
                                                                            </span>
                                                                        </>
                                                                    ) : (
                                                                        'Mise à jour'
                                                                    )}
                                                                </p>
                                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                    {activity.causer
                                                                        ? `Par ${activity.causer.first_name} ${activity.causer.last_name}`
                                                                        : 'Système'}
                                                                    {' • '}
                                                                    {new Date(activity.created_at).toLocaleString('fr-FR')}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                            Aucun historique disponible
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={confirmDelete}
                title="Supprimer la tâche"
                description={`Êtes-vous sûr de vouloir supprimer la tâche "${task.title}" ? Cette action est irréversible.`}
            />

            {/* Create Appointment Modal */}
            <CreateTaskAppointmentModal
                isOpen={showCreateAppointmentModal}
                onClose={() => setShowCreateAppointmentModal(false)}
                onSuccess={handleAppointmentCreated}
                taskId={task.id}
                taskUuid={task.uuid}
                users={users}
                initialDate={selectedAppointmentDate}
            />

            {/* Appointment Detail Modal */}
            <TaskAppointmentDetailModal
                isOpen={showAppointmentDetailModal}
                onClose={() => setShowAppointmentDetailModal(false)}
                onUpdate={handleAppointmentUpdated}
                onDelete={handleAppointmentDeleted}
                appointment={selectedAppointment}
                taskId={task.id}
                taskUuid={task.uuid}
                users={users}
                canEdit={true}
            />
        </DashboardLayout>
    );
}
