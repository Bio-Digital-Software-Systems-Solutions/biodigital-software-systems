import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from '@/Components/ui/accordion';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import {
    ArrowLeftIcon,
    PlusIcon,
    Squares2X2Icon,
    ListBulletIcon,
    TableCellsIcon,
    PencilIcon,
    TrashIcon,
    FunnelIcon,
    DocumentIcon,
    PhotoIcon,
    VideoCameraIcon,
    ArrowDownTrayIcon,
    PaperClipIcon,
} from '@heroicons/react/24/outline';
import { CheckCircleIcon, ClockIcon, RocketLaunchIcon, PlayIcon } from '@heroicons/react/24/solid';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface Attachment {
    id: number;
    name: string;
    file_type: 'image' | 'video' | 'document';
    mime_type: string;
    file_size: number;
    human_file_size: string;
    file_path: string;
    download_url: string;
    uploaded_by: string;
    created_at: string;
}

interface Epic {
    id: number;
    uuid: string;
    key: string;
    title: string;
    description: string | null;
    status: {
        id: number;
        name: string;
    } | null;
    status_name: 'todo' | 'pending' | 'in_progress' | 'under_review' | 'completed';
    priority: 'lowest' | 'low' | 'medium' | 'high' | 'highest';
    project: {
        id: number;
        name: string;
    };
    assignee: {
        id: number;
        name: string;
        email: string;
    } | null;
    reporter: {
        id: number;
        name: string;
    };
    due_date: string | null;
    total_tasks: number;
    completed_tasks: number;
    progress: number;
    child_tasks: any[];
    attachments?: Attachment[];
}

interface Project {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Props {
    epicsByStatus: {
        todo: Epic[];
        pending: Epic[];
        in_progress: Epic[];
        under_review: Epic[];
        completed: Epic[];
    };
    projects: Project[];
    users: User[];
    filters: {
        project_id?: string;
        status?: string;
        priority?: string;
    };
}

type ViewMode = 'grid' | 'list' | 'table';

export default function EpicsIndex({ epicsByStatus, projects, users, filters }: Props) {
    const [viewMode, setViewMode] = useState<ViewMode>('grid');
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);
    const [selectedEpic, setSelectedEpic] = useState<Epic | null>(null);
    const [uploadingFile, setUploadingFile] = useState(false);
    const [deleteAttachmentDialogOpen, setDeleteAttachmentDialogOpen] = useState(false);
    const [attachmentToDelete, setAttachmentToDelete] = useState<number | null>(null);
    const [formData, setFormData] = useState({
        project_id: '',
        title: '',
        description: '',
        priority: 'medium',
        assignee_id: '',
        due_date: '',
        status: 'todo',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});

    const allEpics = [
        ...epicsByStatus.todo,
        ...epicsByStatus.pending,
        ...epicsByStatus.in_progress,
        ...epicsByStatus.under_review,
        ...epicsByStatus.completed,
    ];

    // Format users for SearchableSelect component
    const userOptions = [
        { value: '', label: 'Non assigné' },
        ...users.map(user => ({
            value: user.id.toString(),
            label: user.name,
        })),
    ];

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const submitData = {
            ...formData,
            assignee_id: formData.assignee_id || null,
        };
        router.post('/epics', submitData, {
            preserveScroll: true,
            onSuccess: () => {
                setIsCreateModalOpen(false);
                resetForm();
            },
            onError: (errors) => {
                setErrors(errors);
            },
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEpic) return;

        const submitData = {
            title: formData.title,
            description: formData.description,
            status: formData.status,
            priority: formData.priority,
            assignee_id: formData.assignee_id || null,
            due_date: formData.due_date,
        };

        router.patch(`/epics/${selectedEpic.uuid}`, submitData, {
            preserveScroll: true,
            onSuccess: () => {
                setIsEditModalOpen(false);
                setSelectedEpic(null);
                resetForm();
            },
            onError: (errors) => {
                setErrors(errors);
            },
        });
    };

    const handleDelete = () => {
        if (!selectedEpic) return;

        router.delete(`/epics/${selectedEpic.uuid}`, {
            preserveScroll: true,
            onSuccess: () => {
                setIsDeleteModalOpen(false);
                setSelectedEpic(null);
            },
        });
    };

    // Helper function to format date for input[type="date"]
    const formatDateForInput = (dateString: string | null): string => {
        if (!dateString) return '';
        // Extract YYYY-MM-DD from various date formats
        // Handles: "2025-10-09", "2025-10-09 14:30:00", "2025-10-09T14:30:00"
        return dateString.substring(0, 10);
    };

    const openEditModal = (epic: Epic) => {
        setSelectedEpic(epic);
        setFormData({
            project_id: epic.project.id.toString(),
            title: epic.title,
            description: epic.description || '',
            priority: epic.priority,
            assignee_id: epic.assignee?.id.toString() || '',
            due_date: formatDateForInput(epic.due_date),
            status: epic.status_name,
        });
        setErrors({});
        setIsEditModalOpen(true);
    };

    const openDeleteModal = (epic: Epic) => {
        setSelectedEpic(epic);
        setIsDeleteModalOpen(true);
    };

    const openDetailModal = (epic: Epic) => {
        setSelectedEpic(epic);
        setIsDetailModalOpen(true);
    };

    const resetForm = () => {
        setFormData({
            project_id: '',
            title: '',
            description: '',
            priority: 'medium',
            assignee_id: '',
            due_date: '',
            status: 'todo',
        });
        setErrors({});
    };

    const getStatusBadge = (status: string) => {
        const variants = {
            todo: { icon: ClockIcon, label: 'À faire', color: 'bg-gray-500' },
            pending: { icon: ClockIcon, label: 'En attente', color: 'bg-gray-400' },
            in_progress: { icon: PlayIcon, label: 'En cours', color: 'bg-primary' },
            under_review: { icon: RocketLaunchIcon, label: 'En revue', color: 'bg-purple-500' },
            completed: { icon: CheckCircleIcon, label: 'Terminé', color: 'bg-green-500' },
        };
        const variant = variants[status as keyof typeof variants];
        const Icon = variant.icon;

        return (
            <Badge className={`${variant.color} text-white`}>
                <Icon className="h-3 w-3 mr-1" />
                {variant.label}
            </Badge>
        );
    };

    const getPriorityBadge = (priority: string) => {
        const colors = {
            highest: 'bg-red-500',
            high: 'bg-orange-500',
            medium: 'bg-yellow-500',
            low: 'bg-primary',
            lowest: 'bg-gray-500',
        };
        return (
            <Badge className={`${colors[priority as keyof typeof colors]} text-white`}>
                {priority}
            </Badge>
        );
    };

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get('/epics', {
            ...filters,
            [key]: value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFileUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
        if (!event.target.files || !event.target.files[0] || !selectedEpic) return;

        const file = event.target.files[0];
        const formData = new FormData();
        formData.append('file', file);
        formData.append('attachable_type', 'App\\Models\\ProjectTask');
        formData.append('attachable_id', selectedEpic.id.toString());

        setUploadingFile(true);

        router.post('/attachments', formData, {
            preserveScroll: true,
            onSuccess: () => {
                setUploadingFile(false);
                // Reset file input
                event.target.value = '';
            },
            onError: () => {
                setUploadingFile(false);
            },
        });
    };

    const handleDeleteAttachment = (attachmentId: number) => {
        setAttachmentToDelete(attachmentId);
        setDeleteAttachmentDialogOpen(true);
    };

    const confirmDeleteAttachment = () => {
        if (attachmentToDelete) {
            router.delete(`/attachments/${attachmentToDelete}`, {
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
                return PhotoIcon;
            case 'video':
                return VideoCameraIcon;
            default:
                return DocumentIcon;
        }
    };

    return (
        <DashboardLayout
            title="Gestion des Epics"
            description="Fonctionnalités majeures et initiatives stratégiques"
            actions={
                <>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/projects">
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour aux Projets
                        </Link>
                    </Button>
                    <Button onClick={() => {
                        resetForm();
                        setIsCreateModalOpen(true);
                    }}>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Nouvel Epic
                    </Button>
                </>
            }
        >
            <Head title="Epics" />

            {/* Filters and View Mode */}
                <div className="mb-6 flex flex-wrap items-center gap-4">
                    <div className="flex items-center gap-2">
                        <FunnelIcon className="h-5 w-5 text-gray-500" />
                        <Select
                            value={filters.project_id || 'all'}
                            onValueChange={(value) => handleFilterChange('project_id', value === 'all' ? '' : value)}
                        >
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder="Tous les projets">
                                    {filters.project_id && filters.project_id !== 'all'
                                        ? projects.find(p => p.id.toString() === filters.project_id)?.name
                                        : 'Tous les projets'}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les projets</SelectItem>
                                {projects.map((project) => (
                                    <SelectItem key={project.id} value={project.id.toString()}>
                                        {project.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <Select
                        value={filters.status || 'all'}
                        onValueChange={(value) => handleFilterChange('status', value === 'all' ? '' : value)}
                    >
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Tous les statuts">
                                {filters.status === 'todo' && 'À faire'}
                                {filters.status === 'pending' && 'En attente'}
                                {filters.status === 'in_progress' && 'En cours'}
                                {filters.status === 'under_review' && 'En revue'}
                                {filters.status === 'completed' && 'Terminé'}
                                {(!filters.status || filters.status === 'all') && 'Tous les statuts'}
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tous les statuts</SelectItem>
                            <SelectItem value="todo">À faire</SelectItem>
                            <SelectItem value="pending">En attente</SelectItem>
                            <SelectItem value="in_progress">En cours</SelectItem>
                            <SelectItem value="under_review">En revue</SelectItem>
                            <SelectItem value="completed">Terminé</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.priority || 'all'}
                        onValueChange={(value) => handleFilterChange('priority', value === 'all' ? '' : value)}
                    >
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Toutes les priorités">
                                {filters.priority === 'highest' && 'Très haute'}
                                {filters.priority === 'high' && 'Haute'}
                                {filters.priority === 'medium' && 'Moyenne'}
                                {filters.priority === 'low' && 'Basse'}
                                {filters.priority === 'lowest' && 'Très basse'}
                                {(!filters.priority || filters.priority === 'all') && 'Toutes les priorités'}
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Toutes les priorités</SelectItem>
                            <SelectItem value="highest">Très haute</SelectItem>
                            <SelectItem value="high">Haute</SelectItem>
                            <SelectItem value="medium">Moyenne</SelectItem>
                            <SelectItem value="low">Basse</SelectItem>
                            <SelectItem value="lowest">Très basse</SelectItem>
                        </SelectContent>
                    </Select>

                    <div className="ml-auto flex gap-2">
                        <Button
                            variant={viewMode === 'grid' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setViewMode('grid')}
                        >
                            <Squares2X2Icon className="h-4 w-4" />
                        </Button>
                        <Button
                            variant={viewMode === 'list' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setViewMode('list')}
                        >
                            <ListBulletIcon className="h-4 w-4" />
                        </Button>
                        <Button
                            variant={viewMode === 'table' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setViewMode('table')}
                        >
                            <TableCellsIcon className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {/* Content based on view mode */}
                {viewMode === 'grid' && (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {allEpics.map((epic) => (
                            <Card key={epic.id} className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => openDetailModal(epic)}>
                                <CardHeader>
                                    <div className="flex items-start justify-between mb-2">
                                        <div className="flex-1">
                                            <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">{epic.key}</div>
                                            <CardTitle className="text-lg mb-2">{epic.title}</CardTitle>
                                            <CardDescription className="text-xs">
                                                {epic.project.name}
                                            </CardDescription>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 flex-wrap">
                                        {getStatusBadge(epic.status_name)}
                                        {getPriorityBadge(epic.priority)}
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {epic.description && (
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                            {epic.description}
                                        </p>
                                    )}
                                    <div className="space-y-3">
                                        {epic.assignee && epic.assignee.name && (
                                            <div className="flex items-center gap-2 text-sm">
                                                <div className="h-6 w-6 rounded-full bg-primary flex items-center justify-center text-white text-xs font-medium">
                                                    {epic.assignee.name.charAt(0).toUpperCase()}
                                                </div>
                                                <span className="text-gray-600 dark:text-gray-400">{epic.assignee.name}</span>
                                            </div>
                                        )}
                                        {epic.due_date && (
                                            <div className="text-xs text-gray-500">
                                                Échéance: {formatDate(epic.due_date)}
                                            </div>
                                        )}
                                        <div>
                                            <div className="flex justify-between text-sm mb-1">
                                                <span className="text-gray-600 dark:text-gray-400">Progression</span>
                                                <span className="font-medium">{epic.progress}%</span>
                                            </div>
                                            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    className="bg-primary h-2 rounded-full transition-all"
                                                    style={{ width: `${epic.progress}%` }}
                                                />
                                            </div>
                                            <p className="text-xs text-gray-500 mt-1">
                                                {epic.completed_tasks} / {epic.total_tasks} tâches terminées
                                            </p>
                                        </div>
                                        <div className="flex gap-2 pt-2" onClick={(e) => e.stopPropagation()}>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="flex-1"
                                                onClick={() => openEditModal(epic)}
                                            >
                                                <PencilIcon className="h-4 w-4 mr-1" />
                                                Modifier
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="text-red-600 hover:text-red-700"
                                                onClick={() => openDeleteModal(epic)}
                                            >
                                                <TrashIcon className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {viewMode === 'list' && (
                    <div className="space-y-3">
                        {allEpics.map((epic) => (
                            <Card key={epic.id} className="hover:shadow-md transition-shadow cursor-pointer" onClick={() => openDetailModal(epic)}>
                                <CardContent className="p-4">
                                    <div className="flex items-center gap-4">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-3 mb-1 flex-wrap">
                                                <span className="text-xs text-gray-500">{epic.key}</span>
                                                <h3 className="font-semibold text-gray-900 dark:text-white truncate">
                                                    {epic.title}
                                                </h3>
                                                {getStatusBadge(epic.status_name)}
                                                {getPriorityBadge(epic.priority)}
                                            </div>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {epic.project.name}
                                                {epic.assignee && ` · Assigné à ${epic.assignee.name}`}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <div className="text-center">
                                                <div className="text-2xl font-bold text-primary">{epic.progress}%</div>
                                                <div className="text-xs text-gray-500">
                                                    {epic.completed_tasks}/{epic.total_tasks}
                                                </div>
                                            </div>
                                            <div className="flex gap-2" onClick={(e) => e.stopPropagation()}>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => openEditModal(epic)}
                                                >
                                                    <PencilIcon className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="text-red-600"
                                                    onClick={() => openDeleteModal(epic)}
                                                >
                                                    <TrashIcon className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {viewMode === 'table' && (
                    <Card>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Epic
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Projet
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Assigné à
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Priorité
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Tâches
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Progression
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                        {allEpics.map((epic) => (
                                            <tr
                                                key={epic.id}
                                                className="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                                onClick={() => openDetailModal(epic)}
                                            >
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-xs text-gray-500 mb-1">{epic.key}</div>
                                                    <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                        {epic.title}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    {epic.project.name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {epic.assignee && epic.assignee.name ? (
                                                        <div className="flex items-center">
                                                            <div className="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white text-xs font-medium mr-2">
                                                                {epic.assignee.name.charAt(0).toUpperCase()}
                                                            </div>
                                                            <div className="text-sm text-gray-900 dark:text-white">
                                                                {epic.assignee.name}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm text-gray-400">Non assigné</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getPriorityBadge(epic.priority)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getStatusBadge(epic.status_name)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    {epic.completed_tasks} / {epic.total_tasks}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center gap-2">
                                                        <div className="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 w-24">
                                                            <div
                                                                className="bg-primary h-2 rounded-full"
                                                                style={{ width: `${epic.progress}%` }}
                                                            />
                                                        </div>
                                                        <span className="text-sm font-medium">{epic.progress}%</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm" onClick={(e) => e.stopPropagation()}>
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => openEditModal(epic)}
                                                        >
                                                            <PencilIcon className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="text-red-600"
                                                            onClick={() => openDeleteModal(epic)}
                                                        >
                                                            <TrashIcon className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {allEpics.length === 0 && (
                    <Card>
                        <CardContent className="py-12 text-center">
                            <RocketLaunchIcon className="h-12 w-12 mx-auto text-gray-400 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Aucun epic trouvé
                            </h3>
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Commencez par créer votre premier epic
                            </p>
                            <Button onClick={() => {
                                resetForm();
                                setIsCreateModalOpen(true);
                            }}>
                                <PlusIcon className="h-4 w-4 mr-2" />
                                Créer un Epic
                            </Button>
                        </CardContent>
                    </Card>
                )}

            {/* Create Epic Modal */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Créer un nouvel epic</DialogTitle>
                        <DialogDescription>
                            Définissez une fonctionnalité majeure ou initiative stratégique
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreateSubmit}>
                        <div className="space-y-4 py-4 px-6">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="project_id">Projet *</Label>
                                    <Select
                                        value={formData.project_id}
                                        onValueChange={(value) => setFormData({ ...formData, project_id: value })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner un projet">
                                                {formData.project_id && projects.find(p => p.id.toString() === formData.project_id)?.name}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            {projects.map((project) => (
                                                <SelectItem key={project.id} value={project.id.toString()}>
                                                    {project.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.project_id && (
                                        <p className="text-sm text-red-600 mt-1">{errors.project_id}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="priority">Priorité *</Label>
                                    <Select
                                        value={formData.priority}
                                        onValueChange={(value) => setFormData({ ...formData, priority: value })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue>
                                                {formData.priority === 'highest' && 'Très haute'}
                                                {formData.priority === 'high' && 'Haute'}
                                                {formData.priority === 'medium' && 'Moyenne'}
                                                {formData.priority === 'low' && 'Basse'}
                                                {formData.priority === 'lowest' && 'Très basse'}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="highest">Très haute</SelectItem>
                                            <SelectItem value="high">Haute</SelectItem>
                                            <SelectItem value="medium">Moyenne</SelectItem>
                                            <SelectItem value="low">Basse</SelectItem>
                                            <SelectItem value="lowest">Très basse</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="title">Titre *</Label>
                                <Input
                                    id="title"
                                    value={formData.title}
                                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                    placeholder="Authentification utilisateur"
                                />
                                {errors.title && (
                                    <p className="text-sm text-red-600 mt-1">{errors.title}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    placeholder="Décrire l'epic..."
                                    rows={3}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="assignee_id">Assigné à</Label>
                                    <SearchableSelect
                                        options={userOptions}
                                        value={formData.assignee_id}
                                        onChange={(value) => setFormData({ ...formData, assignee_id: value.toString() })}
                                        placeholder="Non assigné"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="due_date">Date d'échéance</Label>
                                    <Input
                                        id="due_date"
                                        type="date"
                                        value={formData.due_date}
                                        onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
                                    />
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateModalOpen(false)}
                            >
                                Annuler
                            </Button>
                            <Button type="submit">Créer</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Epic Modal */}
            <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Modifier l'epic</DialogTitle>
                        <DialogDescription>
                            Mettre à jour les informations de l'epic
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit}>
                        <div className="space-y-4 py-4 px-6">
                            <div>
                                <Label htmlFor="edit_title">Titre *</Label>
                                <Input
                                    id="edit_title"
                                    value={formData.title}
                                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                />
                                {errors.title && (
                                    <p className="text-sm text-red-600 mt-1">{errors.title}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="edit_description">Description</Label>
                                <Textarea
                                    id="edit_description"
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    rows={3}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="edit_status">Statut *</Label>
                                    <Select
                                        value={formData.status}
                                        onValueChange={(value) => setFormData({ ...formData, status: value })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue>
                                                {formData.status === 'todo' && 'À faire'}
                                                {formData.status === 'pending' && 'En attente'}
                                                {formData.status === 'in_progress' && 'En cours'}
                                                {formData.status === 'under_review' && 'En revue'}
                                                {formData.status === 'completed' && 'Terminé'}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="todo">À faire</SelectItem>
                                            <SelectItem value="pending">En attente</SelectItem>
                                            <SelectItem value="in_progress">En cours</SelectItem>
                                            <SelectItem value="under_review">En revue</SelectItem>
                                            <SelectItem value="completed">Terminé</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="edit_priority">Priorité *</Label>
                                    <Select
                                        value={formData.priority}
                                        onValueChange={(value) => setFormData({ ...formData, priority: value })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue>
                                                {formData.priority === 'highest' && 'Très haute'}
                                                {formData.priority === 'high' && 'Haute'}
                                                {formData.priority === 'medium' && 'Moyenne'}
                                                {formData.priority === 'low' && 'Basse'}
                                                {formData.priority === 'lowest' && 'Très basse'}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="highest">Très haute</SelectItem>
                                            <SelectItem value="high">Haute</SelectItem>
                                            <SelectItem value="medium">Moyenne</SelectItem>
                                            <SelectItem value="low">Basse</SelectItem>
                                            <SelectItem value="lowest">Très basse</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="edit_assignee_id">Assigné à</Label>
                                    <SearchableSelect
                                        options={userOptions}
                                        value={formData.assignee_id}
                                        onChange={(value) => setFormData({ ...formData, assignee_id: value.toString() })}
                                        placeholder="Non assigné"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="edit_due_date">Date d'échéance</Label>
                                    <Input
                                        id="edit_due_date"
                                        type="date"
                                        value={formData.due_date}
                                        onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
                                    />
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsEditModalOpen(false)}
                            >
                                Annuler
                            </Button>
                            <Button type="submit">Enregistrer</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Modal */}
            <Dialog open={isDeleteModalOpen} onOpenChange={setIsDeleteModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Supprimer l'epic</DialogTitle>
                        <DialogDescription>
                            Êtes-vous sûr de vouloir supprimer l'epic "{selectedEpic?.title}" ?
                            Cette action est irréversible.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setIsDeleteModalOpen(false)}
                        >
                            Annuler
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                        >
                            Supprimer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Epic Details Modal - Will be implemented similar to Sprint details */}
            <Dialog open={isDetailModalOpen} onOpenChange={setIsDetailModalOpen}>
                <DialogContent className="max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
                    {selectedEpic && (
                        <>
                            <DialogHeader className="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="text-sm text-gray-500 mb-1">{selectedEpic.key}</div>
                                        <DialogTitle className="text-2xl mb-2">{selectedEpic.title}</DialogTitle>
                                        <DialogDescription className="text-base">
                                            <span className="font-medium text-gray-700 dark:text-gray-300">
                                                Projet: {selectedEpic.project.name}
                                            </span>
                                        </DialogDescription>
                                    </div>
                                    <div className="flex gap-2">
                                        {getStatusBadge(selectedEpic.status_name)}
                                        {getPriorityBadge(selectedEpic.priority)}
                                    </div>
                                </div>
                            </DialogHeader>

                            <div className="flex-1 overflow-y-auto px-6 py-6">
                                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                                    {/* Info Card - Assignation */}
                                    <Card className="col-span-1">
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Assignation
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Assigné à</p>
                                                {selectedEpic.assignee && selectedEpic.assignee.name ? (
                                                    <div className="flex items-center gap-2">
                                                        <div className="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white text-xs font-medium">
                                                            {selectedEpic.assignee.name.charAt(0).toUpperCase()}
                                                        </div>
                                                        <p className="text-base font-semibold text-gray-900 dark:text-white">
                                                            {selectedEpic.assignee.name}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <p className="text-base text-gray-400">Non assigné</p>
                                                )}
                                            </div>
                                            {selectedEpic.due_date && (
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Échéance</p>
                                                    <p className="text-base font-semibold text-gray-900 dark:text-white">
                                                        {formatDate(selectedEpic.due_date)}
                                                    </p>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>

                                    {/* Info Card - Progression */}
                                    <Card className="col-span-1">
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Progression globale
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="text-center mb-4">
                                                <div className="text-5xl font-bold text-primary mb-2">
                                                    {selectedEpic.progress}%
                                                </div>
                                                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                                    <div
                                                        className="bg-primary h-4 rounded-full transition-all"
                                                        style={{ width: `${selectedEpic.progress}%` }}
                                                    />
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    {/* Info Card - Statistiques */}
                                    <Card className="col-span-1">
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Statistiques
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-gray-600 dark:text-gray-400">Total tâches</span>
                                                <span className="text-xl font-bold text-gray-900 dark:text-white">
                                                    {selectedEpic.total_tasks}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-gray-600 dark:text-gray-400">Terminées</span>
                                                <span className="text-xl font-bold text-green-600">
                                                    {selectedEpic.completed_tasks}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-gray-600 dark:text-gray-400">En cours</span>
                                                <span className="text-xl font-bold text-orange-600">
                                                    {selectedEpic.total_tasks - selectedEpic.completed_tasks}
                                                </span>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>

                                {/* Description Section */}
                                {selectedEpic.description && (
                                    <Card className="mb-6">
                                        <CardHeader>
                                            <CardTitle className="text-base">Description</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                                {selectedEpic.description}
                                            </p>
                                        </CardContent>
                                    </Card>
                                )}

                                {/* Tasks and Documents Accordion */}
                                <Accordion>
                                    <AccordionItem value="tasks">
                                        <AccordionTrigger>
                                            <span className="text-base font-semibold">
                                                Tâches de l'epic ({selectedEpic.child_tasks?.length || 0})
                                            </span>
                                        </AccordionTrigger>
                                        <AccordionContent className="p-0">
                                            {selectedEpic.child_tasks && selectedEpic.child_tasks.length > 0 ? (
                                                <div className="overflow-x-auto">
                                                    <table className="w-full">
                                                        <thead className="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                                            <tr>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                                    Tâche
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                                    Assigné à
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                                    Priorité
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                                    Statut
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                                    Type
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                                            {selectedEpic.child_tasks.map((task: any) => (
                                                                <tr
                                                                    key={task.id}
                                                                    className="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                                                    onClick={() => window.open(`/tasks/${task.id}`, '_blank')}
                                                                >
                                                                    <td className="px-6 py-4">
                                                                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                                            {task.title}
                                                                        </div>
                                                                        {task.description && (
                                                                            <div className="text-xs text-gray-500 line-clamp-1 mt-1">
                                                                                {task.description}
                                                                            </div>
                                                                        )}
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                                        {task.assignee && task.assignee.name ? (
                                                                            <div className="flex items-center">
                                                                                <div className="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white text-xs font-medium mr-2">
                                                                                    {task.assignee.name.charAt(0).toUpperCase()}
                                                                                </div>
                                                                                <div className="text-sm text-gray-900 dark:text-white">
                                                                                    {task.assignee.name}
                                                                                </div>
                                                                            </div>
                                                                        ) : (
                                                                            <span className="text-sm text-gray-400 dark:text-gray-500">
                                                                                Non assigné
                                                                            </span>
                                                                        )}
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                                        {getPriorityBadge(task.priority)}
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                                        {getStatusBadge(task.status)}
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                                        <span className="text-sm text-gray-600 dark:text-gray-400">
                                                                            {task.type}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            ) : (
                                                <div className="text-center py-12">
                                                    <p className="text-gray-500 dark:text-gray-400">
                                                        Aucune tâche assignée à cet epic
                                                    </p>
                                                </div>
                                            )}
                                        </AccordionContent>
                                    </AccordionItem>

                                    <AccordionItem value="documents">
                                        <AccordionTrigger>
                                            <span className="text-base font-semibold">
                                                Documents ({selectedEpic.attachments?.length || 0})
                                            </span>
                                        </AccordionTrigger>
                                        <AccordionContent className="p-4">
                                            {/* Upload section */}
                                            <div className="mb-4">
                                                <label className="flex items-center justify-center w-full px-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary dark:hover:border-blue-400 transition-colors">
                                                    <input
                                                        type="file"
                                                        className="hidden"
                                                        onChange={handleFileUpload}
                                                        disabled={uploadingFile}
                                                        accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
                                                    />
                                                    <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                                        {uploadingFile ? (
                                                            <>
                                                                <div className="animate-spin h-5 w-5 border-2 border-primary border-t-transparent rounded-full" />
                                                                <span>Upload en cours...</span>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <PaperClipIcon className="h-5 w-5" />
                                                                <span>Cliquez pour ajouter un fichier</span>
                                                            </>
                                                        )}
                                                    </div>
                                                </label>
                                            </div>

                                            {/* Documents list */}
                                            {selectedEpic.attachments && selectedEpic.attachments.length > 0 ? (
                                                <div className="space-y-2">
                                                    {selectedEpic.attachments.map((attachment) => {
                                                        const FileIcon = getFileIcon(attachment.file_type);
                                                        return (
                                                            <div
                                                                key={attachment.id}
                                                                className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                            >
                                                                <div className="flex items-center gap-3 flex-1 min-w-0">
                                                                    <FileIcon className="h-8 w-8 text-primary flex-shrink-0" />
                                                                    <div className="min-w-0 flex-1">
                                                                        <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                                            {attachment.name}
                                                                        </p>
                                                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                                                            {attachment.human_file_size} • {attachment.uploaded_by} • {attachment.created_at}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <div className="flex items-center gap-2 ml-4">
                                                                    <a
                                                                        href={attachment.download_url}
                                                                        className="p-2 text-primary hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                                        title="Télécharger"
                                                                    >
                                                                        <ArrowDownTrayIcon className="h-5 w-5" />
                                                                    </a>
                                                                    <button
                                                                        onClick={() => handleDeleteAttachment(attachment.id)}
                                                                        className="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                                        title="Supprimer"
                                                                    >
                                                                        <TrashIcon className="h-5 w-5" />
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            ) : (
                                                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                    <DocumentIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                                    <p>Aucun document attaché</p>
                                                </div>
                                            )}
                                        </AccordionContent>
                                    </AccordionItem>
                                </Accordion>
                            </div>

                            <DialogFooter className="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setIsDetailModalOpen(false);
                                        openEditModal(selectedEpic);
                                    }}
                                >
                                    <PencilIcon className="h-4 w-4 mr-2" />
                                    Modifier
                                </Button>
                                <Button onClick={() => setIsDetailModalOpen(false)}>
                                    Fermer
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>

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
