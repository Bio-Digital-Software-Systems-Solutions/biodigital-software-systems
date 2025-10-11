import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ProjectTask, Priority, TaskStatus, TaskType, Project } from '@/Types/Project';
import { User } from '@/Types';
import {
    ListBulletIcon,
    TableCellsIcon,
    Squares2X2Icon,
    MagnifyingGlassIcon,
    FunnelIcon,
    XMarkIcon,
    ArrowLeftIcon,
} from '@heroicons/react/24/outline';

interface PaginatedTasks {
    data: ProjectTask[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    search?: string;
    status?: string;
    priority?: string;
    type?: string;
    project_id?: string;
    assignee_id?: string;
}

interface Props {
    tasks: PaginatedTasks;
    projects: Project[];
    users: User[];
    filters: Filters;
}

type ViewMode = 'list' | 'table' | 'grid';

export default function Index({ tasks, projects, users, filters }: Props) {
    const [viewMode, setViewMode] = useState<ViewMode>('list');
    const [showFilters, setShowFilters] = useState(false);
    const [search, setSearch] = useState(filters.search || '');
    const [localFilters, setLocalFilters] = useState<Filters>(filters);
    const [selectedTasks, setSelectedTasks] = useState<number[]>([]);

    const priorityConfig: Record<Priority, { label: string; color: string }> = {
        [Priority.HIGHEST]: { label: 'Très Haute', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
        [Priority.HIGH]: { label: 'Haute', color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' },
        [Priority.MEDIUM]: { label: 'Moyenne', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' },
        [Priority.LOW]: { label: 'Basse', color: 'bg-blue-100 text-primary dark:bg-blue-900/30 dark:text-blue-300' },
        [Priority.LOWEST]: { label: 'Très Basse', color: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300' },
    };

    const statusConfig: Record<TaskStatus, { label: string; color: string }> = {
        [TaskStatus.TODO]: { label: 'À faire', color: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300' },
        [TaskStatus.IN_PROGRESS]: { label: 'En cours', color: 'bg-blue-100 text-primary dark:bg-blue-900/30 dark:text-blue-300' },
        [TaskStatus.IN_REVIEW]: { label: 'En révision', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' },
        [TaskStatus.BLOCKED]: { label: 'Bloqué', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
        [TaskStatus.DONE]: { label: 'Terminé', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' },
        [TaskStatus.CANCELLED]: { label: 'Annulé', color: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300' },
    };

    const typeConfig: Record<TaskType, { label: string; color: string }> = {
        [TaskType.TASK]: { label: 'Tâche', color: 'bg-blue-50 text-primary dark:bg-blue-900/20 dark:text-blue-300' },
        [TaskType.BUG]: { label: 'Bug', color: 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300' },
        [TaskType.FEATURE]: { label: 'Fonctionnalité', color: 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' },
        [TaskType.STORY]: { label: 'Story', color: 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300' },
        [TaskType.EPIC]: { label: 'Epic', color: 'bg-indigo-50 text-primary dark:bg-indigo-900/20 dark:text-primary' },
        [TaskType.SUBTASK]: { label: 'Sous-tâche', color: 'bg-gray-50 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300' },
    };

    // Debounced search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== filters.search) {
                handleFilterChange('search', search);
            }
        }, 500);

        return () => clearTimeout(timer);
    }, [search]);

    const handleFilterChange = (key: string, value: string) => {
        const newFilters = { ...localFilters, [key]: value };

        // Remove empty filters
        Object.keys(newFilters).forEach(k => {
            if (!newFilters[k as keyof Filters]) {
                delete newFilters[k as keyof Filters];
            }
        });

        setLocalFilters(newFilters);

        router.get('/tasks', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setLocalFilters({});
        router.get('/tasks', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const hasActiveFilters = Object.keys(localFilters).length > 0;

    const toggleTaskCompletion = (task: ProjectTask, e: React.ChangeEvent<HTMLInputElement>) => {
        e.stopPropagation();

        const newStatus = task.status === TaskStatus.DONE ? TaskStatus.TODO : TaskStatus.DONE;

        router.post('/tasks/bulk-update', {
            task_ids: [task.id],
            status: newStatus,
        }, {
            preserveScroll: true,
        });
    };

    return (
        <DashboardLayout>
            <Head title="Tâches" />

            <div className="p-6">
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-4">
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/projects">
                                    <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                    Retour aux Projets
                                </Link>
                            </Button>
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                    Toutes les tâches
                                </h1>
                                <p className="mt-2 text-gray-600 dark:text-gray-400">
                                    {tasks.total} tâche{tasks.total > 1 ? 's' : ''} au total
                                </p>
                            </div>
                        </div>

                        {/* View Mode Toggle */}
                        <div className="flex gap-2">
                            <Button
                                variant={viewMode === 'list' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setViewMode('list')}
                            >
                                <ListBulletIcon className="h-4 w-4 mr-2" />
                                Liste
                            </Button>
                            <Button
                                variant={viewMode === 'table' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setViewMode('table')}
                            >
                                <TableCellsIcon className="h-4 w-4 mr-2" />
                                Tableau
                            </Button>
                            <Button
                                variant={viewMode === 'grid' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setViewMode('grid')}
                            >
                                <Squares2X2Icon className="h-4 w-4 mr-2" />
                                Grille
                            </Button>
                        </div>
                    </div>

                    {/* Search and Filters */}
                    <div className="space-y-4">
                        <div className="flex gap-4">
                            {/* Search */}
                            <div className="flex-1 relative">
                                <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Rechercher par titre, description ou clé..."
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"
                                />
                            </div>

                            {/* Filter Toggle Button */}
                            <Button
                                variant="outline"
                                onClick={() => setShowFilters(!showFilters)}
                            >
                                <FunnelIcon className="h-5 w-5 mr-2" />
                                Filtres
                                {hasActiveFilters && (
                                    <span className="ml-2 px-2 py-0.5 bg-primary text-white text-xs rounded-full">
                                        {Object.keys(localFilters).length}
                                    </span>
                                )}
                            </Button>

                            {/* Clear Filters */}
                            {hasActiveFilters && (
                                <Button
                                    variant="ghost"
                                    onClick={clearFilters}
                                >
                                    <XMarkIcon className="h-5 w-5 mr-2" />
                                    Réinitialiser
                                </Button>
                            )}
                        </div>

                        {/* Filter Panel */}
                        {showFilters && (
                            <Card>
                                <CardContent className="p-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        {/* Status Filter */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Statut
                                            </label>
                                            <select
                                                value={localFilters.status || ''}
                                                onChange={(e) => handleFilterChange('status', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                            >
                                                <option value="">Tous les statuts</option>
                                                <option value="todo">À faire</option>
                                                <option value="in_progress">En cours</option>
                                                <option value="in_review">En révision</option>
                                                <option value="blocked">Bloqué</option>
                                                <option value="done">Terminé</option>
                                                <option value="cancelled">Annulé</option>
                                            </select>
                                        </div>

                                        {/* Priority Filter */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Priorité
                                            </label>
                                            <select
                                                value={localFilters.priority || ''}
                                                onChange={(e) => handleFilterChange('priority', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                            >
                                                <option value="">Toutes les priorités</option>
                                                <option value="highest">Très Haute</option>
                                                <option value="high">Haute</option>
                                                <option value="medium">Moyenne</option>
                                                <option value="low">Basse</option>
                                                <option value="lowest">Très Basse</option>
                                            </select>
                                        </div>

                                        {/* Type Filter */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Type
                                            </label>
                                            <select
                                                value={localFilters.type || ''}
                                                onChange={(e) => handleFilterChange('type', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                            >
                                                <option value="">Tous les types</option>
                                                <option value="task">Tâche</option>
                                                <option value="bug">Bug</option>
                                                <option value="feature">Fonctionnalité</option>
                                                <option value="story">Story</option>
                                                <option value="epic">Epic</option>
                                                <option value="subtask">Sous-tâche</option>
                                            </select>
                                        </div>

                                        {/* Project Filter */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Projet
                                            </label>
                                            <select
                                                value={localFilters.project_id || ''}
                                                onChange={(e) => handleFilterChange('project_id', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                            >
                                                <option value="">Tous les projets</option>
                                                {projects.map(project => (
                                                    <option key={project.id} value={project.id}>
                                                        {project.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        {/* Assignee Filter */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Assigné à
                                            </label>
                                            <select
                                                value={localFilters.assignee_id || ''}
                                                onChange={(e) => handleFilterChange('assignee_id', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                            >
                                                <option value="">Tous les utilisateurs</option>
                                                {users.map(user => (
                                                    <option key={user.id} value={user.id}>
                                                        {user.first_name} {user.last_name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>

                {/* List View */}
                {viewMode === 'list' && (
                    <div className="space-y-3">
                    {tasks.data.map((task) => (
                        <div key={task.id} className="block">
                            <Card className="hover:shadow-md transition-shadow">
                                <CardContent className="p-4">
                                    <div className="flex items-start gap-4">
                                        <div className="flex items-start pt-1">
                                            <input
                                                type="checkbox"
                                                checked={task.status === TaskStatus.DONE}
                                                onChange={(e) => toggleTaskCompletion(task, e)}
                                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800 cursor-pointer"
                                            />
                                        </div>
                                        <Link
                                            href={`/tasks/${task.id}?from=/tasks`}
                                            className="flex-1 min-w-0"
                                        >
                                        <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-3 mb-2">
                                                <div
                                                    className="w-3 h-3 rounded-full flex-shrink-0"
                                                    style={{ backgroundColor: task.project?.color }}
                                                />
                                                <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                                    {task.key}
                                                </span>
                                                <span className={`text-xs px-2 py-1 rounded-full ${typeConfig[task.type]?.color}`}>
                                                    {typeConfig[task.type]?.label}
                                                </span>
                                            </div>

                                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2 truncate">
                                                {task.title}
                                            </h3>

                                            {task.description && (
                                                <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
                                                    {task.description}
                                                </p>
                                            )}

                                            <div className="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                                <span className="flex items-center gap-1">
                                                    <span className="font-medium">Projet:</span>
                                                    {task.project?.name}
                                                </span>
                                                {task.assignee && (
                                                    <span className="flex items-center gap-1">
                                                        <span className="font-medium">Assigné à:</span>
                                                        {task.assignee.first_name} {task.assignee.last_name}
                                                    </span>
                                                )}
                                                {task.due_date && (
                                                    <span className="flex items-center gap-1">
                                                        <span className="font-medium">Échéance:</span>
                                                        {new Date(task.due_date).toLocaleDateString('fr-FR')}
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex flex-col gap-2 items-end flex-shrink-0">
                                            <span className={`text-xs px-3 py-1 rounded-full font-medium ${statusConfig[task.status]?.color}`}>
                                                {statusConfig[task.status]?.label}
                                            </span>
                                            <span className={`text-xs px-3 py-1 rounded-full font-medium ${priorityConfig[task.priority]?.color}`}>
                                                {priorityConfig[task.priority]?.label}
                                            </span>
                                        </div>
                                        </div>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    ))}

                    {tasks.data.length === 0 && (
                        <Card>
                            <CardContent className="p-12 text-center">
                                <p className="text-gray-500 dark:text-gray-400">
                                    Aucune tâche trouvée
                                </p>
                            </CardContent>
                        </Card>
                    )}
                    </div>
                )}

                {/* Table View */}
                {viewMode === 'table' && (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">

                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Clé
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Titre
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Projet
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Priorité
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Assigné
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Échéance
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                {tasks.data.map((task) => (
                                    <tr
                                        key={task.id}
                                        className="hover:bg-gray-50 dark:hover:bg-gray-800"
                                    >
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                checked={task.status === TaskStatus.DONE}
                                                onChange={(e) => toggleTaskCompletion(task, e)}
                                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800 cursor-pointer"
                                            />
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap cursor-pointer" onClick={() => window.location.href = `/tasks/${task.id}?from=/tasks`}>
                                            <div className="flex items-center gap-2">
                                                <div
                                                    className="w-3 h-3 rounded-full"
                                                    style={{ backgroundColor: task.project?.color }}
                                                />
                                                <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                    {task.key}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 cursor-pointer" onClick={() => window.location.href = `/tasks/${task.id}?from=/tasks`}>
                                            <span className="text-sm text-gray-900 dark:text-white">
                                                {task.title}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap cursor-pointer" onClick={() => window.location.href = `/tasks/${task.id}?from=/tasks`}>
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {task.project?.name}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap cursor-pointer" onClick={() => window.location.href = `/tasks/${task.id}?from=/tasks`}>
                                            <span className={`text-xs px-2 py-1 rounded-full ${statusConfig[task.status]?.color}`}>
                                                {statusConfig[task.status]?.label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap cursor-pointer" onClick={() => window.location.href = `/tasks/${task.id}?from=/tasks`}>
                                            <span className={`text-xs px-2 py-1 rounded-full ${priorityConfig[task.priority]?.color}`}>
                                                {priorityConfig[task.priority]?.label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap cursor-pointer" onClick={() => window.location.href = `/tasks/${task.id}?from=/tasks`}>
                                            <span className={`text-xs px-2 py-1 rounded-full ${typeConfig[task.type]?.color}`}>
                                                {typeConfig[task.type]?.label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap cursor-pointer" onClick={() => window.location.href = `/tasks/${task.id}?from=/tasks`}>
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {task.assignee ? `${task.assignee.first_name} ${task.assignee.last_name}` : '-'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap cursor-pointer" onClick={() => window.location.href = `/tasks/${task.id}?from=/tasks`}>
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {task.due_date ? new Date(task.due_date).toLocaleDateString('fr-FR') : '-'}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {tasks.data.length === 0 && (
                            <div className="p-12 text-center">
                                <p className="text-gray-500 dark:text-gray-400">
                                    Aucune tâche trouvée
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Grid View */}
                {viewMode === 'grid' && (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {tasks.data.map((task) => (
                            <div key={task.id} className="relative">
                                <Card className="h-full hover:shadow-md transition-shadow">
                                    <CardContent className="p-4">
                                        <div className="flex items-start gap-3 mb-3">
                                            <input
                                                type="checkbox"
                                                checked={task.status === TaskStatus.DONE}
                                                onChange={(e) => toggleTaskCompletion(task, e)}
                                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800 cursor-pointer mt-1"
                                            />
                                            <Link
                                                href={`/tasks/${task.id}?from=/tasks`}
                                                className="flex-1"
                                            >
                                        <div className="flex items-start justify-between mb-3">
                                            <div className="flex items-center gap-2">
                                                <div
                                                    className="w-3 h-3 rounded-full"
                                                    style={{ backgroundColor: task.project?.color }}
                                                />
                                                <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                                    {task.key}
                                                </span>
                                            </div>
                                            <span className={`text-xs px-2 py-1 rounded-full ${typeConfig[task.type]?.color}`}>
                                                {typeConfig[task.type]?.label}
                                            </span>
                                        </div>

                                        <h3 className="text-base font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                            {task.title}
                                        </h3>

                                        {task.description && (
                                            <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-3 mb-3">
                                                {task.description}
                                            </p>
                                        )}

                                        <div className="space-y-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                    Statut
                                                </span>
                                                <span className={`text-xs px-2 py-1 rounded-full ${statusConfig[task.status]?.color}`}>
                                                    {statusConfig[task.status]?.label}
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                    Priorité
                                                </span>
                                                <span className={`text-xs px-2 py-1 rounded-full ${priorityConfig[task.priority]?.color}`}>
                                                    {priorityConfig[task.priority]?.label}
                                                </span>
                                            </div>
                                            {task.assignee && (
                                                <div className="flex items-center justify-between">
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        Assigné
                                                    </span>
                                                    <span className="text-xs text-gray-600 dark:text-gray-400">
                                                        {task.assignee.first_name} {task.assignee.last_name}
                                                    </span>
                                                </div>
                                            )}
                                            {task.due_date && (
                                                <div className="flex items-center justify-between">
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        Échéance
                                                    </span>
                                                    <span className="text-xs text-gray-600 dark:text-gray-400">
                                                        {new Date(task.due_date).toLocaleDateString('fr-FR')}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                            </Link>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        ))}
                        {tasks.data.length === 0 && (
                            <div className="col-span-full">
                                <Card>
                                    <CardContent className="p-12 text-center">
                                        <p className="text-gray-500 dark:text-gray-400">
                                            Aucune tâche trouvée
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>
                        )}
                    </div>
                )}

                {/* Pagination */}
                {tasks.last_page > 1 && (
                    <div className="mt-6 flex justify-center gap-2">
                        {Array.from({ length: tasks.last_page }, (_, i) => i + 1).map((page) => (
                            <Link
                                key={page}
                                href={`/tasks?page=${page}`}
                                className={`px-4 py-2 rounded-md ${
                                    page === tasks.current_page
                                        ? 'bg-primary text-white'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                }`}
                            >
                                {page}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
