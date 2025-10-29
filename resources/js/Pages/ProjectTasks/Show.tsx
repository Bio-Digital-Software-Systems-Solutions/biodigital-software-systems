import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { apiLogger } from '@/utils/logger';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    PlayIcon,
    PauseIcon,
    StopIcon,
    CheckCircleIcon,
    UserPlusIcon,
    ChatBubbleLeftIcon,
    PaperClipIcon,
    DocumentIcon,
    PhotoIcon,
    VideoCameraIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';

interface ProjectTask {
    id: number;
    uuid: string;
    key: string;
    title: string;
    description: string | null;
    status: string;
    priority: string;
    type: string;
    due_date: string | null;
    estimated_hours: number | null;
    assignee_id?: number;
    epic_id?: number;
    sprint_id?: number;
    started_at: string | null;
    paused_at: string | null;
    stopped_at: string | null;
    reviewed: boolean;
    reviewed_at: string | null;
    created_at: string;
    updated_at: string;
    project: {
        id: number;
        name: string;
        color: string;
    };
    assignee: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
    } | null;
    reporter: {
        id: number;
        first_name: string;
        last_name: string;
    } | null;
    reviewer: {
        id: number;
        first_name: string;
        last_name: string;
    } | null;
    participants: Array<{
        id: number;
        uuid: string;
        first_name: string;
        last_name: string;
        email: string;
    }>;
    comments: Array<{
        id: number;
        content: string;
        created_at: string;
        user: {
            id: number;
            first_name: string;
            last_name: string;
        };
    }>;
    attachments: Array<{
        id: number;
        uuid: string;
        name: string;
        file_path: string;
        file_type: 'image' | 'video' | 'document';
        mime_type: string;
        file_size: number;
        file_url: string;
        created_at: string;
        uploader: {
            id: number;
            first_name: string;
            last_name: string;
        };
    }>;
}

interface Props {
    task: ProjectTask;
    users: Array<{
        id: number;
        first_name: string;
        last_name: string;
        email: string;
    }>;
    epics?: Array<{
        id: number;
        title: string;
        key: string;
    }>;
    sprints?: Array<{
        id: number;
        name: string;
    }>;
}

export default function Show({ task, users, epics = [], sprints = [] }: Props) {
    const [showAddParticipant, setShowAddParticipant] = useState(false);
    const [selectedUser, setSelectedUser] = useState('');
    const [uploading, setUploading] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [commentContent, setCommentContent] = useState('');
    const [submittingComment, setSubmittingComment] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [deleteAttachmentDialogOpen, setDeleteAttachmentDialogOpen] = useState(false);
    const [attachmentToDelete, setAttachmentToDelete] = useState<string | null>(null);

    // Déterminer l'URL de retour intelligente
    const getBackUrl = () => {
        // Vérifier s'il y a un paramètre 'from' dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const fromParam = urlParams.get('from');

        if (fromParam) {
            return fromParam;
        }

        // Sinon, vérifier le document.referrer
        const referrer = document.referrer;
        if (referrer) {
            const referrerUrl = new URL(referrer);
            const referrerPath = referrerUrl.pathname;

            // Si on vient d'une page de projet spécifique
            if (referrerPath.match(/^\/projects\/\d+$/)) {
                return referrerPath;
            }
            // Si on vient du kanban
            if (referrerPath === '/kanban') {
                return '/kanban';
            }
            // Si on vient de la liste des tâches
            if (referrerPath === '/tasks') {
                return '/tasks';
            }
            // Si on vient du gantt
            if (referrerPath === '/gantt') {
                return '/gantt';
            }
        }

        // Par défaut, retourner à la liste des tâches
        return '/tasks';
    };

    const backUrl = getBackUrl();
    const getBackLabel = () => {
        if (backUrl.match(/^\/projects\/\d+$/)) {
            return 'Retour au Projet';
        }
        if (backUrl === '/kanban') {
            return 'Retour au Kanban';
        }
        if (backUrl === '/gantt') {
            return 'Retour au Gantt';
        }
        return 'Retour aux Tâches';
    };

    const editForm = useForm({
        title: task.title,
        description: task.description || '',
        priority: task.priority,
        status: task.status,
        due_date: task.due_date || '',
        assignee_id: task.assignee_id || '',
        epic_id: task.epic_id || '',
        sprint_id: task.sprint_id || '',
    });

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

    const handleStatusAction = (action: string) => {
        const timestamps: Record<string, string | null> = {};

        switch (action) {
            case 'start':
                timestamps.started_at = new Date().toISOString();
                timestamps.paused_at = null;
                break;
            case 'pause':
                timestamps.paused_at = new Date().toISOString();
                break;
            case 'stop':
                timestamps.stopped_at = new Date().toISOString();
                break;
        }

        router.patch(`/api/tasks/${task.uuid}`, timestamps, {
            preserveScroll: true,
            onFinish: () => {
                router.reload({ only: ['task'] });
            },
        });
    };

    const handleApprove = () => {
        router.patch(`/api/tasks/${task.uuid}`, {
            reviewed: true,
            reviewed_at: new Date().toISOString(),
        }, {
            preserveScroll: true,
            onFinish: () => {
                router.reload({ only: ['task'] });
            },
        });
    };

    const handleEdit = (e: React.FormEvent) => {
        e.preventDefault();

        // Convert empty strings to null for epic_id and sprint_id
        editForm.transform((data) => ({
            ...data,
            epic_id: data.epic_id === '' ? null : data.epic_id,
            sprint_id: data.sprint_id === '' ? null : data.sprint_id,
        }));

        editForm.patch(`/api/tasks/${task.uuid}`, {
            preserveScroll: true,
            onSuccess: () => {
                setShowEditModal(false);
            },
            onError: (errors: Record<string, string>) => {
                apiLogger.error('Erreur lors de la mise à jour:', errors);
                // Display first error message
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    alert(`Erreur: ${firstError}`);
                }
            },
        });
    };

    const addParticipant = async () => {
        if (!selectedUser) return;

        try {
            const response = await fetch(`/api/tasks/${task.uuid}/participants`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ user_id: selectedUser }),
            });

            if (response.ok) {
                setSelectedUser('');
                setShowAddParticipant(false);
                router.reload({ preserveState: true, preserveScroll: true } as any);
            } else {
                alert('Erreur lors de l\'ajout du participant');
            }
        } catch (error) {
            apiLogger.error('Error adding participant:', error);
            alert('Erreur lors de l\'ajout du participant');
        }
    };

    const removeParticipant = async (userUuid: string) => {
        try {
            const response = await fetch(`/api/tasks/${task.uuid}/participants/${userUuid}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                router.reload({ preserveState: true, preserveScroll: true } as any);
            } else {
                alert('Erreur lors de la suppression du participant');
            }
        } catch (error) {
            apiLogger.error('Error removing participant:', error);
            alert('Erreur lors de la suppression du participant');
        }
    };

    const submitComment = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!commentContent.trim()) return;

        setSubmittingComment(true);

        try {
            const response = await fetch(`/api/tasks/${task.uuid}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ content: commentContent }),
            });

            if (response.ok) {
                setCommentContent('');
                router.reload({ preserveState: true, preserveScroll: true } as any);
            } else {
                alert('Erreur lors de l\'ajout du commentaire');
            }
        } catch (error) {
            apiLogger.error('Error submitting comment:', error);
            alert('Erreur lors de l\'ajout du commentaire');
        } finally {
            setSubmittingComment(false);
        }
    };

    const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setUploading(true);

        const formData = new FormData();
        formData.append('file', file);

        try {
            await fetch(`/api/tasks/${task.uuid}/attachments`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            router.reload({ preserveState: true, preserveScroll: true } as any);
        } catch (error) {
            apiLogger.error('Upload failed:', error);
            alert('Échec du téléchargement');
        } finally {
            setUploading(false);
            e.target.value = '';
        }
    };

    const handleDeleteAttachment = (attachmentUuid: string) => {
        setAttachmentToDelete(attachmentUuid);
        setDeleteAttachmentDialogOpen(true);
    };

    const confirmDeleteAttachment = () => {
        if (attachmentToDelete) {
            router.delete(route('attachments.destroy', attachmentToDelete), {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteAttachmentDialogOpen(false);
                    setAttachmentToDelete(null);
                },
            });
        }
    };

    const getFileIcon = (fileType: string) => {
        switch (fileType) {
            case 'image':
                return <PhotoIcon className="h-8 w-8 text-primary" />;
            case 'video':
                return <VideoCameraIcon className="h-8 w-8 text-purple-500" />;
            default:
                return <DocumentIcon className="h-8 w-8 text-gray-500" />;
        }
    };

    const formatFileSize = (bytes: number): string => {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return `${size.toFixed(2)} ${units[unitIndex]}`;
    };

    const priorityConfig: Record<string, { label: string; color: string }> = {
        highest: { label: 'Très Haute', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
        high: { label: 'Haute', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' },
        medium: { label: 'Moyenne', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' },
        low: { label: 'Basse', color: 'bg-blue-100 text-primary dark:bg-blue-900/30 dark:text-blue-300' },
        lowest: { label: 'Très Basse', color: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300' },
    };

    const statusConfig: Record<string, { label: string; color: string }> = {
        todo: { label: 'À faire', color: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300' },
        in_progress: { label: 'En cours', color: 'bg-blue-100 text-primary dark:bg-blue-900/30 dark:text-blue-300' },
        in_review: { label: 'En révision', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' },
        blocked: { label: 'Bloqué', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
        done: { label: 'Terminé', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' },
    };

    return (
        <DashboardLayout>
            <Head title={`${task.key} - ${task.title}`} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={backUrl}>
                                <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                {getBackLabel()}
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-2 mb-1">
                                <div
                                    className="w-3 h-3 rounded-full"
                                    style={{ backgroundColor: task.project.color }}
                                />
                                <span className="text-sm text-gray-600 dark:text-gray-400">
                                    {task.project.name}
                                </span>
                            </div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                {task.key} - {task.title}
                            </h1>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        {!task.started_at && (
                            <Button onClick={() => handleStatusAction('start')} size="sm">
                                <PlayIcon className="h-4 w-4 mr-2" />
                                Démarrer
                            </Button>
                        )}
                        {task.started_at && !task.paused_at && !task.stopped_at && (
                            <Button onClick={() => handleStatusAction('pause')} variant="outline" size="sm">
                                <PauseIcon className="h-4 w-4 mr-2" />
                                Mettre en pause
                            </Button>
                        )}
                        {task.started_at && !task.stopped_at && (
                            <Button onClick={() => handleStatusAction('stop')} variant="outline" size="sm">
                                <StopIcon className="h-4 w-4 mr-2" />
                                Arrêter
                            </Button>
                        )}
                        <Button onClick={() => setShowEditModal(true)} variant="outline" size="sm">
                            <PencilIcon className="h-4 w-4 mr-2" />
                            Modifier
                        </Button>
                        <Button onClick={handleDelete} variant="destructive" size="sm">
                            <TrashIcon className="h-4 w-4 mr-2" />
                            Supprimer
                            </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column - Description */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Description */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Description</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                    {task.description || 'Aucune description'}
                                </p>
                            </CardContent>
                        </Card>

                        {/* Comments */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ChatBubbleLeftIcon className="h-5 w-5" />
                                    Commentaires ({task.comments.length})
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Comment List */}
                                <div className="space-y-3 max-h-96 overflow-y-auto">
                                    {task.comments.map((comment) => (
                                        <div key={comment.id} className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="font-medium text-sm text-gray-900 dark:text-white">
                                                    {comment.user.first_name} {comment.user.last_name}
                                                </span>
                                                <span className="text-xs text-gray-500">
                                                    {new Date(comment.created_at).toLocaleString('fr-FR')}
                                                </span>
                                            </div>
                                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                                {comment.content}
                                            </p>
                                        </div>
                                    ))}
                                    {task.comments.length === 0 && (
                                        <p className="text-center text-gray-500 py-4">
                                            Aucun commentaire
                                        </p>
                                    )}
                                </div>

                                {/* Add Comment Form */}
                                <form onSubmit={submitComment} className="border-t pt-4 dark:border-gray-700">
                                    <textarea
                                        value={commentContent}
                                        onChange={(e) => setCommentContent(e.target.value)}
                                        placeholder="Ajouter un commentaire..."
                                        className="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                        rows={3}
                                        disabled={submittingComment}
                                    />
                                    <div className="flex justify-end mt-2">
                                        <Button
                                            type="submit"
                                            disabled={!commentContent.trim() || submittingComment}
                                            size="sm"
                                        >
                                            {submittingComment ? 'Envoi...' : 'Commenter'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        {/* Attachments */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <PaperClipIcon className="h-5 w-5" />
                                    Pièces jointes ({task.attachments.length})
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Upload Section */}
                                <div className="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-6 text-center">
                                    <input
                                        type="file"
                                        id="file-upload"
                                        className="hidden"
                                        onChange={handleFileUpload}
                                        disabled={uploading}
                                    />
                                    <label
                                        htmlFor="file-upload"
                                        className="cursor-pointer"
                                    >
                                        <PaperClipIcon className="h-10 w-10 mx-auto text-gray-400 mb-2" />
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {uploading ? 'Téléchargement...' : 'Cliquez pour ajouter une image, vidéo ou document'}
                                        </p>
                                        <p className="text-xs text-gray-500 mt-1">
                                            Max 50 MB
                                        </p>
                                    </label>
                                </div>

                                {/* Attachments List */}
                                <div className="space-y-2">
                                    {task.attachments.map((attachment) => (
                                        <div
                                            key={attachment.id}
                                            className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg group hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            <div className="flex-shrink-0">
                                                {getFileIcon(attachment.file_type)}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <a
                                                    href={attachment.file_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-sm font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 block truncate"
                                                >
                                                    {attachment.name}
                                                </a>
                                                <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                    <span>{formatFileSize(attachment.file_size)}</span>
                                                    <span>•</span>
                                                    <span>{attachment.uploader.first_name} {attachment.uploader.last_name}</span>
                                                    <span>•</span>
                                                    <span>{new Date(attachment.created_at).toLocaleDateString('fr-FR')}</span>
                                                </div>
                                            </div>
                                            <button
                                                onClick={() => handleDeleteAttachment(attachment.uuid)}
                                                className="flex-shrink-0 p-1 text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                <XMarkIcon className="h-5 w-5" />
                                            </button>
                                        </div>
                                    ))}
                                    {task.attachments.length === 0 && (
                                        <p className="text-center text-gray-500 py-4 text-sm">
                                            Aucune pièce jointe
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column - Details */}
                    <div className="space-y-6">
                        {/* Status & Priority */}
                        <Card>
                            <CardContent className="pt-6 space-y-4">
                                <div>
                                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">
                                        Statut
                                    </label>
                                    <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${statusConfig[task.status]?.color || ''}`}>
                                        {statusConfig[task.status]?.label || task.status}
                                    </span>
                                </div>

                                <div>
                                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">
                                        Priorité
                                    </label>
                                    <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${priorityConfig[task.priority]?.color || ''}`}>
                                        {priorityConfig[task.priority]?.label || task.priority}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* People */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Personnes</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-1">
                                        Assigné à
                                    </label>
                                    <p className="text-sm text-gray-900 dark:text-white">
                                        {task.assignee
                                            ? `${task.assignee.first_name} ${task.assignee.last_name}`
                                            : 'Non assigné'}
                                    </p>
                                </div>

                                <div>
                                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-1">
                                        Rapporteur
                                    </label>
                                    <p className="text-sm text-gray-900 dark:text-white">
                                        {task.reporter
                                            ? `${task.reporter.first_name} ${task.reporter.last_name}`
                                            : 'N/A'}
                                    </p>
                                </div>

                                <div>
                                    <label className="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-1">
                                        Reviewer
                                    </label>
                                    <p className="text-sm text-gray-900 dark:text-white">
                                        {task.reviewer
                                            ? `${task.reviewer.first_name} ${task.reviewer.last_name}`
                                            : 'Aucun'}
                                    </p>
                                    {task.reviewer && !task.reviewed && (
                                        <Button onClick={handleApprove} variant="outline" size="sm" className="mt-2">
                                            <CheckCircleIcon className="h-4 w-4 mr-2" />
                                            Approuver
                                        </Button>
                                    )}
                                    {task.reviewed && (
                                        <p className="text-xs text-green-600 dark:text-green-400 mt-1">
                                            ✓ Approuvé le {new Date(task.reviewed_at!).toLocaleDateString('fr-FR')}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Participants ({task.participants.length})
                                        </label>
                                        <Button
                                            onClick={() => setShowAddParticipant(!showAddParticipant)}
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <UserPlusIcon className="h-4 w-4" />
                                        </Button>
                                    </div>

                                    {showAddParticipant && (
                                        <div className="mb-3 space-y-2">
                                            <select
                                                value={selectedUser}
                                                onChange={(e) => setSelectedUser(e.target.value)}
                                                className="w-full px-3 py-2 border rounded-md text-sm dark:bg-gray-800 dark:border-gray-700"
                                            >
                                                <option value="">Sélectionner un utilisateur</option>
                                                {users.filter(u =>
                                                    !task.participants.find(p => p.id === u.id)
                                                ).map(user => (
                                                    <option key={user.id} value={user.id}>
                                                        {user.first_name} {user.last_name}
                                                    </option>
                                                ))}
                                            </select>
                                            <Button onClick={addParticipant} size="sm" className="w-full">
                                                Ajouter
                                            </Button>
                                        </div>
                                    )}

                                    <div className="space-y-2">
                                        {task.participants.map((participant) => (
                                            <div
                                                key={participant.id}
                                                className="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-800 rounded"
                                            >
                                                <span className="text-sm text-gray-900 dark:text-white">
                                                    {participant.first_name} {participant.last_name}
                                                </span>
                                                <button
                                                    onClick={() => removeParticipant(participant.uuid)}
                                                    className="text-red-600 hover:text-red-700 text-xs"
                                                >
                                                    Retirer
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Dates */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Dates</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {task.due_date && (
                                    <div>
                                        <label className="text-gray-500 dark:text-gray-400 block mb-1">
                                            Date d'échéance
                                        </label>
                                        <p className="text-gray-900 dark:text-white">
                                            {new Date(task.due_date).toLocaleDateString('fr-FR')}
                                        </p>
                                    </div>
                                )}
                                <div>
                                    <label className="text-gray-500 dark:text-gray-400 block mb-1">
                                        Créé le
                                    </label>
                                    <p className="text-gray-900 dark:text-white">
                                        {new Date(task.created_at).toLocaleDateString('fr-FR')}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-gray-500 dark:text-gray-400 block mb-1">
                                        Mis à jour le
                                    </label>
                                    <p className="text-gray-900 dark:text-white">
                                        {new Date(task.updated_at).toLocaleDateString('fr-FR')}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Edit Modal */}
                {showEditModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div className="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                            <div className="p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h2 className="text-2xl font-bold dark:text-white">Modifier la tâche</h2>
                                    <button
                                        onClick={() => setShowEditModal(false)}
                                        className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                    >
                                        <XMarkIcon className="h-6 w-6" />
                                    </button>
                                </div>

                                <form onSubmit={handleEdit} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Titre
                                        </label>
                                        <input
                                            type="text"
                                            value={editForm.data.title}
                                            onChange={(e) => editForm.setData('title', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Description
                                        </label>
                                        <textarea
                                            value={editForm.data.description}
                                            onChange={(e) => editForm.setData('description', e.target.value)}
                                            rows={4}
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Priorité
                                            </label>
                                            <select
                                                value={editForm.data.priority}
                                                onChange={(e) => editForm.setData('priority', e.target.value as any)}
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"
                                            >
                                                <option value="lowest">Très basse</option>
                                                <option value="low">Basse</option>
                                                <option value="medium">Moyenne</option>
                                                <option value="high">Haute</option>
                                                <option value="highest">Très haute</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Statut
                                            </label>
                                            <select
                                                value={editForm.data.status}
                                                onChange={(e) => editForm.setData('status', e.target.value as any)}
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"
                                            >
                                                <option value="todo">À faire</option>
                                                <option value="in_progress">En cours</option>
                                                <option value="in_review">En révision</option>
                                                <option value="blocked">Bloquée</option>
                                                <option value="done">Terminée</option>
                                                <option value="cancelled">Annulée</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Date d'échéance
                                            </label>
                                            <input
                                                type="date"
                                                value={editForm.data.due_date}
                                                onChange={(e) => editForm.setData('due_date', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Assigné à
                                            </label>
                                            <select
                                                value={editForm.data.assignee_id}
                                                onChange={(e) => editForm.setData('assignee_id', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"
                                            >
                                                <option value="">Non assigné</option>
                                                {users.map((user) => (
                                                    <option key={user.id} value={user.id}>
                                                        {user.first_name} {user.last_name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Epic
                                            </label>
                                            <select
                                                value={editForm.data.epic_id}
                                                onChange={(e) => editForm.setData('epic_id', e.target.value)}
                                                className={`w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white ${
                                                    editForm.errors.epic_id
                                                        ? 'border-red-500 dark:border-red-500'
                                                        : 'border-gray-300 dark:border-gray-600'
                                                }`}
                                            >
                                                <option value="">Aucun epic</option>
                                                {epics.map((epic) => (
                                                    <option key={epic.id} value={epic.id}>
                                                        {epic.key} - {epic.title}
                                                    </option>
                                                ))}
                                            </select>
                                            {editForm.errors.epic_id && (
                                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                                    {editForm.errors.epic_id}
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Sprint
                                            </label>
                                            <select
                                                value={editForm.data.sprint_id}
                                                onChange={(e) => editForm.setData('sprint_id', e.target.value)}
                                                className={`w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white ${
                                                    editForm.errors.sprint_id
                                                        ? 'border-red-500 dark:border-red-500'
                                                        : 'border-gray-300 dark:border-gray-600'
                                                }`}
                                            >
                                                <option value="">Aucun sprint</option>
                                                {sprints.map((sprint) => (
                                                    <option key={sprint.id} value={sprint.id}>
                                                        {sprint.name}
                                                    </option>
                                                ))}
                                            </select>
                                            {editForm.errors.sprint_id && (
                                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                                    {editForm.errors.sprint_id}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex justify-end gap-3 mt-6">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setShowEditModal(false)}
                                        >
                                            Annuler
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={editForm.processing}
                                        >
                                            {editForm.processing ? 'Enregistrement...' : 'Enregistrer'}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={confirmDelete}
                title="Supprimer la tâche"
                description={`Êtes-vous sûr de vouloir supprimer la tâche "${task.title}" ? Cette action est irréversible.`}
            />

            <DeleteConfirmationDialog
                open={deleteAttachmentDialogOpen}
                onOpenChange={setDeleteAttachmentDialogOpen}
                onConfirm={confirmDeleteAttachment}
                title="Supprimer le fichier"
                description="Êtes-vous sûr de vouloir supprimer ce fichier ? Cette action est irréversible."
            />
        </DashboardLayout>
    );
}
