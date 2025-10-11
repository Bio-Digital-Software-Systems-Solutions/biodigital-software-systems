import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { apiLogger } from '@/utils/logger';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
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
    XMarkIcon,
} from '@heroicons/react/24/outline';
import { CheckCircleIcon, ClockIcon, RocketLaunchIcon } from '@heroicons/react/24/solid';

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

interface Sprint {
    id: number;
    name: string;
    goal: string | null;
    start_date: string;
    end_date: string;
    project: {
        id: number;
        name: string;
    };
    status: 'active' | 'upcoming' | 'completed';
    total_tasks: number;
    completed_tasks: number;
    progress: number;
    tasks: any[];
    attachments?: Attachment[];
}

interface Project {
    id: number;
    name: string;
}

interface Props {
    sprintsByStatus: {
        active: Sprint[];
        upcoming: Sprint[];
        completed: Sprint[];
    };
    projects: Project[];
    filters: {
        project_id?: string;
        status?: string;
    };
}

type ViewMode = 'grid' | 'list' | 'table';

export default function SprintsIndex({ sprintsByStatus, projects, filters }: Props) {
    const [viewMode, setViewMode] = useState<ViewMode>('grid');
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);
    const [selectedSprint, setSelectedSprint] = useState<Sprint | null>(null);
    const [uploadingFile, setUploadingFile] = useState(false);
    const [formData, setFormData] = useState({
        project_id: '',
        name: '',
        goal: '',
        start_date: '',
        end_date: '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});

    const allSprints = [
        ...sprintsByStatus.active,
        ...sprintsByStatus.upcoming,
        ...sprintsByStatus.completed,
    ];

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/sprints', formData, {
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
        if (!selectedSprint) return;

        router.patch(`/sprints/${selectedSprint.id}`, {
            name: formData.name,
            goal: formData.goal,
            start_date: formData.start_date,
            end_date: formData.end_date,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setIsEditModalOpen(false);
                setSelectedSprint(null);
                resetForm();
            },
            onError: (errors) => {
                setErrors(errors);
            },
        });
    };

    const handleDelete = () => {
        if (!selectedSprint) return;

        router.delete(`/sprints/${selectedSprint.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setIsDeleteModalOpen(false);
                setSelectedSprint(null);
            },
        });
    };

    const openEditModal = (sprint: Sprint) => {
        setSelectedSprint(sprint);
        setFormData({
            project_id: sprint.project.id.toString(),
            name: sprint.name,
            goal: sprint.goal || '',
            start_date: formatDateForInput(sprint.start_date),
            end_date: formatDateForInput(sprint.end_date),
        });
        setErrors({});
        setIsEditModalOpen(true);
    };

    const openDeleteModal = (sprint: Sprint) => {
        setSelectedSprint(sprint);
        setIsDeleteModalOpen(true);
    };

    const openDetailModal = (sprint: Sprint) => {
        setSelectedSprint(sprint);
        setIsDetailModalOpen(true);
    };

    const resetForm = () => {
        setFormData({
            project_id: '',
            name: '',
            goal: '',
            start_date: '',
            end_date: '',
        });
        setErrors({});
    };

    // Helper function to format date for input[type="date"]
    const formatDateForInput = (dateString: string): string => {
        if (!dateString) return '';
        // Extract YYYY-MM-DD from various date formats
        // Handles: "2025-10-09", "2025-10-09 14:30:00", "2025-10-09T14:30:00"
        return dateString.substring(0, 10);
    };

    const getStatusBadge = (status: string) => {
        const variants = {
            active: { icon: RocketLaunchIcon, label: 'Actif', color: 'bg-green-500' },
            upcoming: { icon: ClockIcon, label: 'À venir', color: 'bg-primary' },
            completed: { icon: CheckCircleIcon, label: 'Terminé', color: 'bg-gray-500' },
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

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get('/sprints', {
            ...filters,
            [key]: value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFileUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
        if (!event.target.files || !event.target.files[0] || !selectedSprint) return;

        const file = event.target.files[0];
        const formData = new FormData();
        formData.append('file', file);
        formData.append('attachable_type', 'App\\Models\\Sprint');
        formData.append('attachable_id', selectedSprint.id.toString());

        setUploadingFile(true);

        router.post('/attachments', formData, {
            preserveScroll: true,
            onSuccess: () => {
                setUploadingFile(false);
                event.target.value = '';
            },
            onError: (errors) => {
                setUploadingFile(false);
                apiLogger.error('Upload error:', errors);
            },
        });
    };

    const handleDeleteAttachment = (attachmentId: number) => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) return;

        router.delete(`/attachments/${attachmentId}`, {
            preserveScroll: true,
        });
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
            title="Gestion des Sprints"
            description="Planifiez et suivez vos sprints agiles"
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
                        Nouveau Sprint
                    </Button>
                </>
            }
        >
            <Head title="Gestion des Sprints" />

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
                                {filters.status === 'active' && 'Actif'}
                                {filters.status === 'upcoming' && 'À venir'}
                                {filters.status === 'completed' && 'Terminé'}
                                {(!filters.status || filters.status === 'all') && 'Tous les statuts'}
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tous les statuts</SelectItem>
                            <SelectItem value="active">Actif</SelectItem>
                            <SelectItem value="upcoming">À venir</SelectItem>
                            <SelectItem value="completed">Terminé</SelectItem>
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
                        {allSprints.map((sprint) => (
                            <Card key={sprint.id} className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => openDetailModal(sprint)}>
                                <CardHeader>
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <CardTitle className="text-lg mb-2">{sprint.name}</CardTitle>
                                            <CardDescription className="text-xs">
                                                {sprint.project.name}
                                            </CardDescription>
                                        </div>
                                        {getStatusBadge(sprint.status)}
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {sprint.goal && (
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                            {sprint.goal}
                                        </p>
                                    )}
                                    <div className="space-y-3">
                                        <div className="text-xs text-gray-500">
                                            <div className="flex justify-between mb-1">
                                                <span>Du {formatDate(sprint.start_date)}</span>
                                                <span>au {formatDate(sprint.end_date)}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div className="flex justify-between text-sm mb-1">
                                                <span className="text-gray-600 dark:text-gray-400">Progression</span>
                                                <span className="font-medium">{sprint.progress}%</span>
                                            </div>
                                            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    className="bg-primary h-2 rounded-full transition-all"
                                                    style={{ width: `${sprint.progress}%` }}
                                                />
                                            </div>
                                            <p className="text-xs text-gray-500 mt-1">
                                                {sprint.completed_tasks} / {sprint.total_tasks} tâches terminées
                                            </p>
                                        </div>
                                        <div className="flex gap-2 pt-2" onClick={(e) => e.stopPropagation()}>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="flex-1"
                                                onClick={() => openEditModal(sprint)}
                                            >
                                                <PencilIcon className="h-4 w-4 mr-1" />
                                                Modifier
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="text-red-600 hover:text-red-700"
                                                onClick={() => openDeleteModal(sprint)}
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
                        {allSprints.map((sprint) => (
                            <Card key={sprint.id} className="hover:shadow-md transition-shadow cursor-pointer" onClick={() => openDetailModal(sprint)}>
                                <CardContent className="p-4">
                                    <div className="flex items-center gap-4">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-3 mb-1">
                                                <h3 className="font-semibold text-gray-900 dark:text-white truncate">
                                                    {sprint.name}
                                                </h3>
                                                {getStatusBadge(sprint.status)}
                                            </div>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {sprint.project.name} · {formatDate(sprint.start_date)} - {formatDate(sprint.end_date)}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <div className="text-center">
                                                <div className="text-2xl font-bold text-primary">{sprint.progress}%</div>
                                                <div className="text-xs text-gray-500">
                                                    {sprint.completed_tasks}/{sprint.total_tasks}
                                                </div>
                                            </div>
                                            <div className="flex gap-2" onClick={(e) => e.stopPropagation()}>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => openEditModal(sprint)}
                                                >
                                                    <PencilIcon className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="text-red-600"
                                                    onClick={() => openDeleteModal(sprint)}
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
                                                Sprint
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Projet
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Dates
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
                                        {allSprints.map((sprint) => (
                                            <tr
                                                key={sprint.id}
                                                className="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                                onClick={() => openDetailModal(sprint)}
                                            >
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                        {sprint.name}
                                                    </div>
                                                    {sprint.goal && (
                                                        <div className="text-xs text-gray-500 line-clamp-1">
                                                            {sprint.goal}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    {sprint.project.name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    <div>{formatDate(sprint.start_date)}</div>
                                                    <div>{formatDate(sprint.end_date)}</div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getStatusBadge(sprint.status)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    {sprint.completed_tasks} / {sprint.total_tasks}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center gap-2">
                                                        <div className="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 w-24">
                                                            <div
                                                                className="bg-primary h-2 rounded-full"
                                                                style={{ width: `${sprint.progress}%` }}
                                                            />
                                                        </div>
                                                        <span className="text-sm font-medium">{sprint.progress}%</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm" onClick={(e) => e.stopPropagation()}>
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => openEditModal(sprint)}
                                                        >
                                                            <PencilIcon className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="text-red-600"
                                                            onClick={() => openDeleteModal(sprint)}
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

                {allSprints.length === 0 && (
                    <Card>
                        <CardContent className="py-12 text-center">
                            <RocketLaunchIcon className="h-12 w-12 mx-auto text-gray-400 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Aucun sprint trouvé
                            </h3>
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Commencez par créer votre premier sprint
                            </p>
                            <Button onClick={() => {
                                resetForm();
                                setIsCreateModalOpen(true);
                            }}>
                                <PlusIcon className="h-4 w-4 mr-2" />
                                Créer un Sprint
                            </Button>
                        </CardContent>
                    </Card>
                )}

            {/* Create Sprint Modal */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Créer un nouveau sprint</DialogTitle>
                        <DialogDescription>
                            Définissez les détails de votre sprint agile
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
                                    <Label htmlFor="name">Nom du sprint *</Label>
                                    <Input
                                        id="name"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        placeholder="Sprint 1"
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-red-600 mt-1">{errors.name}</p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="goal">Objectif</Label>
                                <Textarea
                                    id="goal"
                                    value={formData.goal}
                                    onChange={(e) => setFormData({ ...formData, goal: e.target.value })}
                                    placeholder="Décrire l'objectif du sprint..."
                                    rows={3}
                                />
                                {errors.goal && (
                                    <p className="text-sm text-red-600 mt-1">{errors.goal}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="start_date">Date de début *</Label>
                                    <Input
                                        id="start_date"
                                        type="date"
                                        value={formData.start_date}
                                        onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                                    />
                                    {errors.start_date && (
                                        <p className="text-sm text-red-600 mt-1">{errors.start_date}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="end_date">Date de fin *</Label>
                                    <Input
                                        id="end_date"
                                        type="date"
                                        value={formData.end_date}
                                        onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                    />
                                    {errors.end_date && (
                                        <p className="text-sm text-red-600 mt-1">{errors.end_date}</p>
                                    )}
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

            {/* Edit Sprint Modal */}
            <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Modifier le sprint</DialogTitle>
                        <DialogDescription>
                            Mettre à jour les informations du sprint
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit}>
                        <div className="space-y-4 py-4 px-6">
                            <div>
                                <Label htmlFor="edit_name">Nom du sprint *</Label>
                                <Input
                                    id="edit_name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                />
                                {errors.name && (
                                    <p className="text-sm text-red-600 mt-1">{errors.name}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="edit_goal">Objectif</Label>
                                <Textarea
                                    id="edit_goal"
                                    value={formData.goal}
                                    onChange={(e) => setFormData({ ...formData, goal: e.target.value })}
                                    rows={3}
                                />
                                {errors.goal && (
                                    <p className="text-sm text-red-600 mt-1">{errors.goal}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="edit_start_date">Date de début *</Label>
                                    <Input
                                        id="edit_start_date"
                                        type="date"
                                        value={formData.start_date}
                                        onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                                    />
                                    {errors.start_date && (
                                        <p className="text-sm text-red-600 mt-1">{errors.start_date}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="edit_end_date">Date de fin *</Label>
                                    <Input
                                        id="edit_end_date"
                                        type="date"
                                        value={formData.end_date}
                                        onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                    />
                                    {errors.end_date && (
                                        <p className="text-sm text-red-600 mt-1">{errors.end_date}</p>
                                    )}
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
                        <DialogTitle>Supprimer le sprint</DialogTitle>
                        <DialogDescription>
                            Êtes-vous sûr de vouloir supprimer le sprint "{selectedSprint?.name}" ?
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

            {/* Sprint Details Modal */}
            <Dialog open={isDetailModalOpen} onOpenChange={setIsDetailModalOpen}>
                <DialogContent className="max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
                    {selectedSprint && (
                        <>
                            <DialogHeader className="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <DialogTitle className="text-2xl mb-2">{selectedSprint.name}</DialogTitle>
                                        <DialogDescription className="text-base">
                                            <span className="font-medium text-gray-700 dark:text-gray-300">
                                                Projet: {selectedSprint.project.name}
                                            </span>
                                        </DialogDescription>
                                    </div>
                                    {getStatusBadge(selectedSprint.status)}
                                </div>
                            </DialogHeader>

                            <div className="flex-1 overflow-y-auto px-6 py-6">
                                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                                    {/* Info Card - Dates */}
                                    <Card className="col-span-1">
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Période du sprint
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Début</p>
                                                <p className="text-base font-semibold text-gray-900 dark:text-white">
                                                    {formatDate(selectedSprint.start_date)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Fin</p>
                                                <p className="text-base font-semibold text-gray-900 dark:text-white">
                                                    {formatDate(selectedSprint.end_date)}
                                                </p>
                                            </div>
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
                                                    {selectedSprint.progress}%
                                                </div>
                                                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                                    <div
                                                        className="bg-primary h-4 rounded-full transition-all"
                                                        style={{ width: `${selectedSprint.progress}%` }}
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
                                                    {selectedSprint.total_tasks}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-gray-600 dark:text-gray-400">Terminées</span>
                                                <span className="text-xl font-bold text-green-600">
                                                    {selectedSprint.completed_tasks}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-gray-600 dark:text-gray-400">En cours</span>
                                                <span className="text-xl font-bold text-orange-600">
                                                    {selectedSprint.total_tasks - selectedSprint.completed_tasks}
                                                </span>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>

                                {/* Objectif Section */}
                                {selectedSprint.goal && (
                                    <Card className="mb-6">
                                        <CardHeader>
                                            <CardTitle className="text-base">Objectif du sprint</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                                {selectedSprint.goal}
                                            </p>
                                        </CardContent>
                                    </Card>
                                )}

                                {/* Tasks and Documents Accordion */}
                                <Accordion>
                                    <AccordionItem value="tasks">
                                        <AccordionTrigger>
                                            <span className="text-base font-semibold">
                                                Tâches du sprint ({selectedSprint.tasks?.length || 0})
                                            </span>
                                        </AccordionTrigger>
                                        <AccordionContent className="p-0">
                                            {selectedSprint.tasks && selectedSprint.tasks.length > 0 ? (
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
                                                            {selectedSprint.tasks.map((task: any) => (
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
                                                                        {task.assignee ? (
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
                                                                        <Badge className={
                                                                            task.priority === 'highest' ? 'bg-red-500' :
                                                                            task.priority === 'high' ? 'bg-orange-500' :
                                                                            task.priority === 'medium' ? 'bg-yellow-500' :
                                                                            task.priority === 'low' ? 'bg-primary' :
                                                                            'bg-gray-500'
                                                                        }>
                                                                            {task.priority}
                                                                        </Badge>
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                                        <Badge className={
                                                                            task.status === 'done' ? 'bg-green-500' :
                                                                            task.status === 'in_progress' ? 'bg-primary' :
                                                                            task.status === 'review' ? 'bg-purple-500' :
                                                                            'bg-gray-500'
                                                                        }>
                                                                            {task.status}
                                                                        </Badge>
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
                                                        Aucune tâche assignée à ce sprint
                                                    </p>
                                                </div>
                                            )}
                                        </AccordionContent>
                                    </AccordionItem>

                                    <AccordionItem value="documents">
                                        <AccordionTrigger>
                                            <span className="text-base font-semibold">
                                                Documents ({selectedSprint.attachments?.length || 0})
                                            </span>
                                        </AccordionTrigger>
                                        <AccordionContent className="p-4">
                                            {/* Upload Section */}
                                            <div className="mb-6">
                                                <label className="flex items-center justify-center w-full px-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary dark:hover:border-blue-400 transition-colors">
                                                    <div className="text-center">
                                                        <DocumentIcon className="h-8 w-8 mx-auto text-gray-400 dark:text-gray-500 mb-2" />
                                                        <span className="text-sm text-gray-600 dark:text-gray-400">
                                                            {uploadingFile ? 'Upload en cours...' : 'Cliquez pour ajouter un fichier'}
                                                        </span>
                                                        <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                            Images, vidéos ou documents (max 50MB)
                                                        </p>
                                                    </div>
                                                    <input
                                                        type="file"
                                                        className="hidden"
                                                        onChange={handleFileUpload}
                                                        disabled={uploadingFile}
                                                        accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                                                    />
                                                </label>
                                            </div>

                                            {/* Documents List */}
                                            {selectedSprint.attachments && selectedSprint.attachments.length > 0 ? (
                                                <div className="space-y-2">
                                                    {selectedSprint.attachments.map((attachment) => {
                                                        const FileIcon = getFileIcon(attachment.file_type);
                                                        return (
                                                            <div
                                                                key={attachment.id}
                                                                className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                            >
                                                                <div className="flex items-center gap-3 flex-1 min-w-0">
                                                                    <FileIcon className="h-8 w-8 text-primary flex-shrink-0" />
                                                                    <div className="flex-1 min-w-0">
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
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        className="p-2 text-gray-600 hover:text-primary dark:text-gray-400 dark:hover:text-blue-400 transition-colors"
                                                                        title="Télécharger"
                                                                    >
                                                                        <ArrowDownTrayIcon className="h-5 w-5" />
                                                                    </a>
                                                                    <button
                                                                        onClick={() => handleDeleteAttachment(attachment.id)}
                                                                        className="p-2 text-gray-600 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors"
                                                                        title="Supprimer"
                                                                    >
                                                                        <XMarkIcon className="h-5 w-5" />
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            ) : (
                                                <div className="text-center py-8">
                                                    <DocumentIcon className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                                    <p className="text-gray-500 dark:text-gray-400 text-sm">
                                                        Aucun document attaché
                                                    </p>
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
                                        openEditModal(selectedSprint);
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
        </DashboardLayout>
    );
}
