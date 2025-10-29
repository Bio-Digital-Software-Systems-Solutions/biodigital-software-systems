import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { apiLogger } from '@/utils/logger';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    ArrowLeftIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import axios from 'axios';
import { toast } from 'sonner';

interface Task {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    status: string;
    priority: string;
    type: string;
    assignee?: {
        id: number;
        first_name: string;
        last_name: string;
    };
    project?: {
        id: number;
        name: string;
        color?: string;
    };
    due_date?: string;
}

interface Props {
    tasksByStatus: {
        todo: Task[];
        in_progress: Task[];
        under_review: Task[];
        blocked: Task[];
        completed: Task[];
    };
    projects: Array<{ id: number; name: string }>;
    users: Array<{ id: number; first_name: string; last_name: string }>;
    sprints: Array<{ id: number; name: string; project_id: number }>;
    filters: {
        [key: string]: string | undefined;
        project_id?: string;
        assignee_id?: string;
        priority?: string;
        type?: string;
        sprint_id?: string;
        search?: string;
    };
}

const statusConfig = {
    todo: {
        label: 'À faire',
        color: 'bg-gray-100 dark:bg-gray-800',
        headerColor: 'bg-gray-200 dark:bg-gray-700',
        textColor: 'text-gray-700 dark:text-gray-300',
    },
    in_progress: {
        label: 'En cours',
        color: 'bg-blue-50 dark:bg-blue-900/20',
        headerColor: 'bg-blue-100 dark:bg-blue-900/40',
        textColor: 'text-primary dark:text-blue-300',
    },
    under_review: {
        label: 'En révision',
        color: 'bg-yellow-50 dark:bg-yellow-900/20',
        headerColor: 'bg-yellow-100 dark:bg-yellow-900/40',
        textColor: 'text-yellow-700 dark:text-yellow-300',
    },
    blocked: {
        label: 'Bloqué',
        color: 'bg-red-50 dark:bg-red-900/20',
        headerColor: 'bg-red-100 dark:bg-red-900/40',
        textColor: 'text-red-700 dark:text-red-300',
    },
    completed: {
        label: 'Terminé',
        color: 'bg-green-50 dark:bg-green-900/20',
        headerColor: 'bg-green-100 dark:bg-green-900/40',
        textColor: 'text-green-700 dark:text-green-300',
    },
};

const priorityColors = {
    highest: 'border-l-4 border-red-500',
    high: 'border-l-4 border-orange-500',
    medium: 'border-l-4 border-yellow-500',
    low: 'border-l-4 border-primary',
    lowest: 'border-l-4 border-gray-500',
};

export default function KanbanIndex({ tasksByStatus, projects, users, sprints, filters }: Props) {
    const [showFilters, setShowFilters] = useState(false);
    const [localFilters, setLocalFilters] = useState(filters);
    const [draggedTask, setDraggedTask] = useState<Task | null>(null);

    const handleFilter = (key: string, value: string) => {
        const newFilters = { ...localFilters, [key]: value };
        if (!value) delete newFilters[key];
        setLocalFilters(newFilters);
        router.get(route('kanban.index'), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setLocalFilters({});
        router.get(route('kanban.index'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleDragStart = (task: Task) => {
        setDraggedTask(task);
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
    };

    const handleDrop = async (status: string) => {
        if (!draggedTask) return;

        try {
            await axios.patch(route('kanban.tasks.update-status', draggedTask.uuid), {
                status
            });
            toast.success('Statut de la tâche mis à jour avec succès');
            router.reload({ preserveState: true, preserveScroll: true } as any);
        } catch (error) {
            apiLogger.error('Error updating task status:', error);
            toast.error('Erreur lors de la mise à jour du statut de la tâche');
        }

        setDraggedTask(null);
    };

    const getPriorityLabel = (priority: string) => {
        return priority.charAt(0).toUpperCase() + priority.slice(1);
    };

    const getTypeLabel = (type: string) => {
        const labels: Record<string, string> = {
            task: 'Tâche',
            bug: 'Bug',
            feature: 'Fonctionnalité',
            story: 'Story',
            epic: 'Epic',
            subtask: 'Sous-tâche',
        };
        return labels[type] || type;
    };

    const activeFiltersCount = Object.keys(localFilters).filter(key => localFilters[key as keyof typeof localFilters]).length;

    return (
        <DashboardLayout>
            <Head title="Kanban Board" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/projects">
                                <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                Retour
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Kanban Board
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Glissez-déposez les tâches pour changer leur statut
                            </p>
                        </div>
                    </div>
                    <Button
                        variant={showFilters ? 'default' : 'outline'}
                        onClick={() => setShowFilters(!showFilters)}
                    >
                        <FunnelIcon className="h-4 w-4 mr-2" />
                        Filtres {activeFiltersCount > 0 && `(${activeFiltersCount})`}
                    </Button>
                </div>

                {/* Filters */}
                {showFilters && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>Filtres</CardTitle>
                                {activeFiltersCount > 0 && (
                                    <Button variant="ghost" size="sm" onClick={clearFilters}>
                                        <XMarkIcon className="h-4 w-4 mr-2" />
                                        Effacer tout
                                    </Button>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="search">Recherche</Label>
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <Input
                                            id="search"
                                            type="text"
                                            placeholder="Rechercher..."
                                            value={localFilters.search || ''}
                                            onChange={(e) => handleFilter('search', e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="project">Projet</Label>
                                    <select
                                        id="project"
                                        value={localFilters.project_id || ''}
                                        onChange={(e) => handleFilter('project_id', e.target.value)}
                                        className="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
                                    >
                                        <option value="">Tous les projets</option>
                                        {projects.map((project) => (
                                            <option key={project.id} value={project.id}>
                                                {project.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="assignee">Assigné à</Label>
                                    <select
                                        id="assignee"
                                        value={localFilters.assignee_id || ''}
                                        onChange={(e) => handleFilter('assignee_id', e.target.value)}
                                        className="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
                                    >
                                        <option value="">Tous les utilisateurs</option>
                                        {users.map((user) => (
                                            <option key={user.id} value={user.id}>
                                                {user.first_name} {user.last_name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="priority">Priorité</Label>
                                    <select
                                        id="priority"
                                        value={localFilters.priority || ''}
                                        onChange={(e) => handleFilter('priority', e.target.value)}
                                        className="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
                                    >
                                        <option value="">Toutes les priorités</option>
                                        <option value="highest">Très haute</option>
                                        <option value="high">Haute</option>
                                        <option value="medium">Moyenne</option>
                                        <option value="low">Basse</option>
                                        <option value="lowest">Très basse</option>
                                    </select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="type">Type</Label>
                                    <select
                                        id="type"
                                        value={localFilters.type || ''}
                                        onChange={(e) => handleFilter('type', e.target.value)}
                                        className="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
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

                                <div className="space-y-2">
                                    <Label htmlFor="sprint">Sprint</Label>
                                    <select
                                        id="sprint"
                                        value={localFilters.sprint_id || ''}
                                        onChange={(e) => handleFilter('sprint_id', e.target.value)}
                                        className="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
                                    >
                                        <option value="">Tous les sprints</option>
                                        {sprints.map((sprint) => (
                                            <option key={sprint.id} value={sprint.id}>
                                                {sprint.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Kanban Board */}
                <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    {Object.entries(statusConfig).map(([status, config]) => (
                        <div
                            key={status}
                            className="flex flex-col"
                            onDragOver={handleDragOver}
                            onDrop={() => handleDrop(status)}
                        >
                            <div className={`${config.headerColor} px-4 py-3 rounded-t-lg`}>
                                <div className="flex items-center justify-between">
                                    <h3 className={`font-semibold ${config.textColor}`}>
                                        {config.label}
                                    </h3>
                                    <span className={`text-sm ${config.textColor}`}>
                                        {tasksByStatus[status as keyof typeof tasksByStatus].length}
                                    </span>
                                </div>
                            </div>
                            <div className={`${config.color} rounded-b-lg p-2 space-y-2 min-h-[500px] flex-1`}>
                                {tasksByStatus[status as keyof typeof tasksByStatus].map((task) => (
                                    <div
                                        key={task.id}
                                        draggable
                                        onDragStart={() => handleDragStart(task)}
                                        className={`bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow cursor-move ${
                                            priorityColors[task.priority as keyof typeof priorityColors]
                                        }`}
                                    >
                                        <div className="space-y-2">
                                            {task.project && (
                                                <div className="flex items-center gap-2">
                                                    <div
                                                        className="w-2 h-2 rounded-full"
                                                        style={{ backgroundColor: task.project.color || '#3B82F6' }}
                                                    />
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        {task.project.name}
                                                    </span>
                                                </div>
                                            )}

                                            <Link
                                                href={`/tasks/${task.uuid}`}
                                                className="font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 transition-colors block"
                                            >
                                                {task.title}
                                            </Link>

                                            {task.description && (
                                                <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                                    {task.description}
                                                </p>
                                            )}

                                            <div className="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                                        {getTypeLabel(task.type)}
                                                    </span>
                                                    <span className={`text-xs px-2 py-1 rounded ${
                                                        task.priority === 'highest' || task.priority === 'high'
                                                            ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                            : task.priority === 'medium'
                                                            ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                            : 'bg-blue-100 text-primary dark:bg-blue-900/30 dark:text-blue-400'
                                                    }`}>
                                                        {getPriorityLabel(task.priority)}
                                                    </span>
                                                </div>

                                                {task.assignee && (
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-6 h-6 rounded-full bg-primary flex items-center justify-center text-white text-xs">
                                                            {task.assignee.first_name[0]}{task.assignee.last_name[0]}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>

                                            {task.due_date && (
                                                <div className="text-xs text-gray-500 dark:text-gray-400">
                                                    Échéance: {new Date(task.due_date).toLocaleDateString()}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}

                                {tasksByStatus[status as keyof typeof tasksByStatus].length === 0 && (
                                    <div className="text-center py-8 text-gray-400 dark:text-gray-600 text-sm">
                                        Aucune tâche
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
