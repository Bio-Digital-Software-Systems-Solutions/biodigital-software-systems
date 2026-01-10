import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Project, ProjectParticipant, ProjectComment, ProjectAttachment, TaskStatus } from '@/Types/Project';
import { User } from '@/Types';
import { useToast } from '@/Components/ui/toast';
import { useConfirm } from '@/Components/ui/confirm-dialog';
import { apiLogger } from '@/utils/logger';
import {
    PauseIcon,
    PlayIcon,
    XCircleIcon,
    PencilIcon,
    DocumentTextIcon,
    PhotoIcon,
    VideoCameraIcon,
    PaperClipIcon,
    TrashIcon,
    UserPlusIcon,
    ChatBubbleLeftIcon,
    PlusIcon,
    MinusIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowLeftIcon
} from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';

interface Activity {
    id: number;
    type: 'project' | 'participant' | 'attachment' | 'task';
    event: string;
    description: string;
    properties: Record<string, unknown>;
    causer: {
        id: number;
        first_name: string;
        last_name: string;
    } | null;
    created_at: string;
}

interface Props {
    project: Project;
    users: User[];
    activities: Activity[];
}

export default function ShowProject({ project, users, activities }: Props) {
    const [uploading, setUploading] = useState(false);
    const [commentContent, setCommentContent] = useState('');
    const [replyingTo, setReplyingTo] = useState<number | null>(null);
    const [replyContent, setReplyContent] = useState('');
    const [showAddParticipant, setShowAddParticipant] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState('');
    const [selectedRole, setSelectedRole] = useState<'member' | 'contributor' | 'observer'>('member');
    const [tasksExpanded, setTasksExpanded] = useState(true);
    const [historyExpanded, setHistoryExpanded] = useState(false);
    const { showSuccess, showError } = useToast();
    const confirm = useConfirm();

    const handleStatusChange = async (status: string) => {
        try {
            await fetch(`/api/projects/${project.uuid}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ status }),
            });
            showSuccess('Statut du projet mis à jour avec succès');
            router.reload({ preserveState: true, preserveScroll: true } as any);
        } catch (error) {
            apiLogger.error('Failed to update status:', error);
            showError('Échec de la mise à jour du statut');
        }
    };

    const handleAddParticipant = async () => {
        if (!selectedUserId) return;

        try {
            await fetch(`/api/projects/${project.uuid}/participants`, {
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
            showSuccess('Participant ajouté avec succès');
            router.reload({ preserveState: true, preserveScroll: true } as any);
            setShowAddParticipant(false);
            setSelectedUserId('');
        } catch (error) {
            apiLogger.error('Failed to add participant:', error);
            showError('Échec de l\'ajout du participant');
        }
    };

    const handleRemoveParticipant = async (participantId: number) => {
        const confirmed = await confirm.confirm({
            title: 'Retirer le participant',
            message: 'Êtes-vous sûr de vouloir retirer ce participant du projet?',
            confirmText: 'Retirer',
            cancelText: 'Annuler',
            type: 'warning',
        });

        if (!confirmed) return;

        try {
            await fetch(`/api/projects/${project.uuid}/participants/${participantId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            showSuccess('Participant retiré avec succès');
            router.reload({ preserveState: true, preserveScroll: true } as any);
        } catch (error) {
            apiLogger.error('Failed to remove participant:', error);
            showError('Échec du retrait du participant');
        }
    };

    const handleAddComment = async (e: React.FormEvent, parentId: number | null = null) => {
        e.preventDefault();
        const content = parentId ? replyContent : commentContent;
        if (!content.trim()) return;

        try {
            await fetch(`/api/projects/${project.uuid}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    content,
                    parent_id: parentId
                }),
            });
            showSuccess(parentId ? 'Réponse ajoutée avec succès' : 'Commentaire ajouté avec succès');
            if (parentId) {
                setReplyContent('');
                setReplyingTo(null);
            } else {
                setCommentContent('');
            }
            router.reload({ preserveState: true, preserveScroll: true } as any);
        } catch (error) {
            apiLogger.error('Failed to add comment:', error);
            showError('Échec de l\'ajout du commentaire');
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
            await fetch(`/api/projects/${project.uuid}/comments/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            showSuccess('Commentaire supprimé avec succès');
            router.reload({ preserveState: true, preserveScroll: true } as any);
        } catch (error) {
            apiLogger.error('Failed to delete comment:', error);
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
            await fetch(`/api/projects/${project.uuid}/attachments`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            showSuccess('Fichier téléchargé avec succès');
            router.reload({ preserveState: true, preserveScroll: true } as any);
        } catch (error) {
            apiLogger.error('Upload failed:', error);
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
            await fetch(`/api/projects/${project.uuid}/attachments/${attachmentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });
            showSuccess('Fichier supprimé avec succès');
            router.reload({ preserveState: true, preserveScroll: true } as any);
        } catch (error) {
            apiLogger.error('Failed to delete attachment:', error);
            showError('Échec de la suppression du fichier');
        }
    };

    const getFileIcon = (fileType: string) => {
        switch (fileType) {
            case 'image':
                return <PhotoIcon className="h-8 w-8 text-primary" />;
            case 'video':
                return <VideoCameraIcon className="h-8 w-8 text-purple-500" />;
            default:
                return <DocumentTextIcon className="h-8 w-8 text-gray-500" />;
        }
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
        <DashboardLayout
            title={project.name}
            description={project.description || 'Détails du projet'}
            actions={
                <>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/projects">
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour aux Projets
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('projects.board', project.uuid)}>
                            Vue Kanban
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('projects.gantt', project.uuid)}>
                            Vue Gantt
                        </Link>
                    </Button>
                    <Button size="sm" asChild>
                        <Link href={route('projects.edit', project.uuid)}>
                            <PencilIcon className="h-4 w-4 mr-2" />
                            Modifier
                        </Link>
                    </Button>
                </>
            }
        >
            <Head title={project.name} />

            <div className="space-y-6">

                {/* Main Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Project Details Card */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 className="text-xl font-semibold dark:text-white mb-4">Détails du projet</h2>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Statut</p>
                                    <span
                                        className={`inline-block px-3 py-1 text-sm rounded mt-1 ${
                                            project.status === 'active'
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                                : project.status === 'planning'
                                                ? 'bg-blue-100 text-primary dark:bg-blue-900 dark:text-blue-300'
                                                : project.status === 'on_hold'
                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'
                                                : project.status === 'completed'
                                                ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                        }`}
                                    >
                                        {project.status}
                                    </span>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Priorité</p>
                                    <p className="font-medium dark:text-white mt-1">{project.priority}</p>
                                </div>
                                {project.start_date && (
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Date de début</p>
                                        <p className="font-medium dark:text-white mt-1">
                                            {new Date(project.start_date).toLocaleDateString('fr-FR')}
                                        </p>
                                    </div>
                                )}
                                {project.end_date && (
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Date de fin</p>
                                        <p className="font-medium dark:text-white mt-1">
                                            {new Date(project.end_date).toLocaleDateString('fr-FR')}
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Progress Bar */}
                            <div className="mt-6">
                                <div className="flex justify-between items-center mb-2">
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Progression</p>
                                    <p className="text-sm font-medium dark:text-white">{project.progress || 0}%</p>
                                </div>
                                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                    <div
                                        className="bg-primary h-3 rounded-full transition-all"
                                        style={{ width: `${project.progress || 0}%` }}
                                    ></div>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {project.tasks_count || 0} tâches au total
                                </p>
                            </div>
                        </div>

                        {/* Tasks Accordion */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <button
                                onClick={() => setTasksExpanded(!tasksExpanded)}
                                className="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
                                <h2 className="text-xl font-semibold dark:text-white flex items-center gap-2">
                                    Tâches associées
                                    <span className="text-sm font-normal text-gray-500 dark:text-gray-400">
                                        ({project.tasks?.length || 0})
                                    </span>
                                </h2>
                                {tasksExpanded ? (
                                    <MinusIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                ) : (
                                    <PlusIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                )}
                            </button>

                            {tasksExpanded && (
                                <div className="px-6 pb-6">
                                    {project.tasks && project.tasks.length > 0 ? (
                                        <div className="space-y-3">
                                            {project.tasks.map((task) => (
                                                <Link
                                                    key={task.id}
                                                    href={route('tasks.show', task.uuid)}
                                                    className="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-primary dark:hover:border-blue-400 hover:shadow-md transition-all"
                                                >
                                                    <div className="flex items-start justify-between">
                                                        <div className="flex-1">
                                                            <div className="flex items-center gap-2 mb-2">
                                                                <span className="text-xs font-mono text-gray-500 dark:text-gray-400">
                                                                    {task.key}
                                                                </span>
                                                                <span
                                                                    className={`inline-flex items-center px-2 py-0.5 text-xs rounded ${
                                                                        task.status?.name === TaskStatus.COMPLETED
                                                                            ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                                                            : task.status?.name === TaskStatus.IN_PROGRESS
                                                                            ? 'bg-blue-100 text-primary dark:bg-blue-900 dark:text-blue-300'
                                                                            : task.status?.name === TaskStatus.UNDER_REVIEW || task.status?.name === TaskStatus.IN_REVIEW
                                                                            ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300'
                                                                            : task.status?.name === TaskStatus.BLOCKED
                                                                            ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                                    }`}
                                                                >
                                                                    {task.status?.name === TaskStatus.COMPLETED && <CheckCircleIcon className="h-3 w-3 mr-1" />}
                                                                    {task.status?.name === TaskStatus.IN_PROGRESS && <ClockIcon className="h-3 w-3 mr-1" />}
                                                                    {task.status?.name || 'N/A'}
                                                                </span>
                                                                <span
                                                                    className={`inline-block px-2 py-0.5 text-xs rounded ${
                                                                        task.priority === 'highest'
                                                                            ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                                                            : task.priority === 'high'
                                                                            ? 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300'
                                                                            : task.priority === 'medium'
                                                                            ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'
                                                                            : task.priority === 'low'
                                                                            ? 'bg-blue-100 text-primary dark:bg-blue-900 dark:text-blue-300'
                                                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                                    }`}
                                                                >
                                                                    {task.priority}
                                                                </span>
                                                            </div>
                                                            <h3 className="font-medium dark:text-white mb-1">
                                                                {task.title}
                                                            </h3>
                                                            {task.description && (
                                                                <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                                                    {task.description}
                                                                </p>
                                                            )}
                                                            <div className="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                {task.assignee && (
                                                                    <div className="flex items-center gap-1">
                                                                        <div className="w-5 h-5 rounded-full bg-primary flex items-center justify-center text-white text-xs">
                                                                            {task.assignee.first_name?.charAt(0)}{task.assignee.last_name?.charAt(0)}
                                                                        </div>
                                                                        <span>{task.assignee.first_name} {task.assignee.last_name}</span>
                                                                    </div>
                                                                )}
                                                                {task.due_date && (
                                                                    <span>
                                                                        Échéance: {new Date(task.due_date).toLocaleDateString('fr-FR')}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </Link>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                                            Aucune tâche associée à ce projet
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Team Section */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h2 className="text-xl font-semibold dark:text-white">Équipe</h2>
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
                                    <div className="grid grid-cols-2 gap-3 mb-3">
                                        <select
                                            value={selectedUserId}
                                            onChange={(e) => setSelectedUserId(e.target.value)}
                                            className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-800 dark:text-white"
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
                                            onChange={(e) => setSelectedRole(e.target.value as any)}
                                            className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-800 dark:text-white"
                                        >
                                            <option value="member">Membre</option>
                                            <option value="contributor">Contributeur</option>
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
                                {/* Manager */}
                                {project.manager && (
                                    <div className="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-semibold">
                                                {project.manager.first_name?.charAt(0)}{project.manager.last_name?.charAt(0)}
                                            </div>
                                            <div>
                                                <p className="font-medium dark:text-white">
                                                    {project.manager.first_name} {project.manager.last_name}
                                                </p>
                                                <p className="text-xs text-gray-600 dark:text-gray-400">Chef de projet</p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Reviewer */}
                                {project.reviewer && (
                                    <div className="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded border border-purple-200 dark:border-purple-800">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-purple-500 flex items-center justify-center text-white font-semibold">
                                                {project.reviewer.first_name?.charAt(0)}{project.reviewer.last_name?.charAt(0)}
                                            </div>
                                            <div>
                                                <p className="font-medium dark:text-white">
                                                    {project.reviewer.first_name} {project.reviewer.last_name}
                                                </p>
                                                <p className="text-xs text-gray-600 dark:text-gray-400">Réviseur</p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Participants */}
                                {project.participants && project.participants.length > 0 ? (
                                    project.participants.map((participant) => (
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
                                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                        Aucun participant pour le moment
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Comments Section */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 className="text-xl font-semibold dark:text-white mb-4 flex items-center gap-2">
                                <ChatBubbleLeftIcon className="h-6 w-6" />
                                Commentaires ({project.comments?.length || 0})
                            </h2>

                            {/* Add Comment Form */}
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

                            {/* Comments List */}
                            <div className="space-y-6">
                                {project.comments && project.comments.length > 0 ? (
                                    project.comments.map((comment) => (
                                        <div key={comment.id} className="space-y-3">
                                            {/* Parent Comment */}
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
                                                        <button
                                                            onClick={() => handleDeleteComment(comment.id)}
                                                            className="text-red-600 hover:text-red-700"
                                                        >
                                                            <TrashIcon className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                    <p className="text-gray-700 dark:text-gray-300 mt-1">{comment.content}</p>
                                                    <button
                                                        onClick={() => setReplyingTo(replyingTo === comment.id ? null : comment.id)}
                                                        className="text-sm text-primary dark:text-blue-400 hover:underline mt-2"
                                                    >
                                                        {replyingTo === comment.id ? 'Annuler' : 'Répondre'}
                                                    </button>

                                                    {/* Reply Form */}
                                                    {replyingTo === comment.id && (
                                                        <form onSubmit={(e) => handleAddComment(e, comment.id)} className="mt-3">
                                                            <textarea
                                                                value={replyContent}
                                                                onChange={(e) => setReplyContent(e.target.value)}
                                                                placeholder="Votre réponse..."
                                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white resize-none text-sm"
                                                                rows={2}
                                                                autoFocus
                                                            />
                                                            <div className="flex gap-2 mt-2">
                                                                <button
                                                                    type="submit"
                                                                    disabled={!replyContent.trim()}
                                                                    className="px-3 py-1.5 bg-primary text-white text-sm rounded hover:bg-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                                                >
                                                                    Répondre
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => {
                                                                        setReplyingTo(null);
                                                                        setReplyContent('');
                                                                    }}
                                                                    className="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                                                                >
                                                                    Annuler
                                                                </button>
                                                            </div>
                                                        </form>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Replies */}
                                            {comment.replies && comment.replies.length > 0 && (
                                                <div className="ml-11 space-y-3 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                                                    {comment.replies.map((reply) => (
                                                        <div key={reply.id} className="flex gap-3">
                                                            <div className="w-7 h-7 rounded-full bg-primary flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
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
                                                                    <button
                                                                        onClick={() => handleDeleteComment(reply.id)}
                                                                        className="text-red-600 hover:text-red-700"
                                                                    >
                                                                        <TrashIcon className="h-3.5 w-3.5" />
                                                                    </button>
                                                                </div>
                                                                <p className="text-sm text-gray-700 dark:text-gray-300 mt-1">{reply.content}</p>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
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
                                    Documents ({project.attachments?.length || 0})
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
                                {project.attachments && project.attachments.length > 0 ? (
                                    project.attachments.map((attachment) => (
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

                    {/* Right Column - Quick Actions */}
                    <div className="space-y-6">
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 className="text-lg font-semibold dark:text-white mb-4">Actions rapides</h2>
                            <div className="space-y-2">
                                {project.status === 'planning' && (
                                    <button
                                        onClick={() => handleStatusChange('active')}
                                        className="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 flex items-center justify-center gap-2"
                                    >
                                        <PlayIcon className="h-4 w-4" />
                                        Lancer le projet
                                    </button>
                                )}
                                {project.status === 'active' && (
                                    <button
                                        onClick={() => handleStatusChange('on_hold')}
                                        className="w-full px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 flex items-center justify-center gap-2"
                                    >
                                        <PauseIcon className="h-4 w-4" />
                                        Mettre en pause
                                    </button>
                                )}
                                {project.status === 'on_hold' && (
                                    <button
                                        onClick={() => handleStatusChange('active')}
                                        className="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 flex items-center justify-center gap-2"
                                    >
                                        <PlayIcon className="h-4 w-4" />
                                        Relancer le projet
                                    </button>
                                )}
                                {project.status !== 'cancelled' && project.status !== 'completed' && (
                                    <button
                                        onClick={async () => {
                                            const confirmed = await confirm.confirm({
                                                title: 'Annuler le projet',
                                                message: 'Êtes-vous sûr de vouloir annuler ce projet? Cette action ne peut pas être annulée.',
                                                confirmText: 'Annuler le projet',
                                                cancelText: 'Retour',
                                                type: 'danger',
                                            });
                                            if (confirmed) {
                                                handleStatusChange('cancelled');
                                            }
                                        }}
                                        className="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 flex items-center justify-center gap-2"
                                    >
                                        <XCircleIcon className="h-4 w-4" />
                                        Annuler le projet
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Project Stats */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 className="text-lg font-semibold dark:text-white mb-4">Statistiques</h2>
                            <div className="space-y-4">
                                <div>
                                    <div className="flex justify-between items-center mb-1">
                                        <span className="text-sm text-gray-600 dark:text-gray-400">Tâches</span>
                                        <span className="text-sm font-medium dark:text-white">
                                            {project.tasks_count || 0}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div className="flex justify-between items-center mb-1">
                                        <span className="text-sm text-gray-600 dark:text-gray-400">Membres</span>
                                        <span className="text-sm font-medium dark:text-white">
                                            {(project.participants?.length || 0) + (project.manager ? 1 : 0) + (project.reviewer ? 1 : 0)}
                                        </span>
                                    </div>
                                </div>
                                {project.budget && (
                                    <div>
                                        <div className="flex justify-between items-center mb-1">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">Budget</span>
                                            <span className="text-sm font-medium dark:text-white">
                                                {project.budget.toLocaleString('fr-FR')} €
                                            </span>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Activity Feed */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <button
                                type="button"
                                onClick={() => setHistoryExpanded(!historyExpanded)}
                                className="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors rounded-lg"
                            >
                                <h2 className="text-lg font-semibold dark:text-white">
                                    Historique
                                    {activities && activities.length > 0 && (
                                        <span className="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                            ({activities.length})
                                        </span>
                                    )}
                                </h2>
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

                                            <div className="space-y-4">
                                                {activities.map((activity, index) => {
                                            const isCreated = activity.event === 'created';
                                            const isDeleted = activity.event === 'deleted';
                                            const isStatusChange = activity.type === 'project' && activity.description.includes('Statut');

                                            const getIconColor = () => {
                                                if (activity.type === 'project') {
                                                    if (isCreated) return 'bg-purple-100 border-purple-500 dark:bg-purple-900/30';
                                                    if (isStatusChange) return 'bg-blue-100 border-blue-500 dark:bg-blue-900/30';
                                                    return 'bg-gray-100 border-gray-300 dark:bg-gray-700 dark:border-gray-600';
                                                }
                                                if (activity.type === 'participant') {
                                                    return isCreated
                                                        ? 'bg-green-100 border-green-500 dark:bg-green-900/30'
                                                        : 'bg-red-100 border-red-500 dark:bg-red-900/30';
                                                }
                                                if (activity.type === 'attachment') {
                                                    return isCreated
                                                        ? 'bg-blue-100 border-blue-500 dark:bg-blue-900/30'
                                                        : 'bg-red-100 border-red-500 dark:bg-red-900/30';
                                                }
                                                if (activity.type === 'task') {
                                                    return isCreated
                                                        ? 'bg-green-100 border-green-500 dark:bg-green-900/30'
                                                        : 'bg-red-100 border-red-500 dark:bg-red-900/30';
                                                }
                                                return 'bg-gray-100 border-gray-300 dark:bg-gray-700 dark:border-gray-600';
                                            };

                                            const getIcon = () => {
                                                if (activity.type === 'participant') {
                                                    return isCreated ? (
                                                        <UserPlusIcon className="w-3 h-3 text-green-600" />
                                                    ) : (
                                                        <TrashIcon className="w-3 h-3 text-red-600" />
                                                    );
                                                }
                                                if (activity.type === 'attachment') {
                                                    return isCreated ? (
                                                        <PaperClipIcon className="w-3 h-3 text-blue-600" />
                                                    ) : (
                                                        <TrashIcon className="w-3 h-3 text-red-600" />
                                                    );
                                                }
                                                if (activity.type === 'task') {
                                                    return isCreated ? (
                                                        <PlusIcon className="w-3 h-3 text-green-600" />
                                                    ) : (
                                                        <TrashIcon className="w-3 h-3 text-red-600" />
                                                    );
                                                }
                                                if (isCreated) {
                                                    return <CheckCircleIcon className="w-3 h-3 text-purple-600" />;
                                                }
                                                return (
                                                    <span className="text-[10px] font-semibold text-gray-500 dark:text-gray-400">
                                                        {activities.length - index}
                                                    </span>
                                                );
                                            };

                                            return (
                                                <div key={activity.id} className="relative flex items-start gap-3">
                                                    {/* Timeline dot */}
                                                    <div className={`relative z-10 flex items-center justify-center w-8 h-8 rounded-full border-2 ${getIconColor()}`}>
                                                        {getIcon()}
                                                    </div>

                                                    {/* Content */}
                                                    <div className="flex-1 min-w-0 pb-1">
                                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {activity.description}
                                                        </p>
                                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                            {activity.causer
                                                                ? `${activity.causer.first_name} ${activity.causer.last_name}`
                                                                : 'Système'}
                                                            {' • '}
                                                            {new Date(activity.created_at).toLocaleString('fr-FR', {
                                                                day: '2-digit',
                                                                month: '2-digit',
                                                                year: 'numeric',
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            })}
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
        </DashboardLayout>
    );
}
