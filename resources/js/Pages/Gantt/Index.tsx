import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import TaskDetailsModal from '@/Components/Projects/TaskDetailsModal';
import ProjectDetailsModal from '@/Components/Projects/ProjectDetailsModal';
import {
    ArrowLeftIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChartBarIcon,
    CalendarIcon,
} from '@heroicons/react/24/outline';

interface GanttTask {
    id: string;
    uuid: string;
    name: string;
    start: string;
    end: string;
    progress: number;
    type: 'task';
    assignee?: string;
    priority: string;
    status: string;
}

interface GanttItem {
    id: string;
    uuid: string;
    name: string;
    start: string;
    end: string;
    progress: number;
    type: 'project' | 'task';
    color?: string;
    tasks?: GanttTask[];
}

interface Props {
    ganttData: GanttItem[];
    projects: Array<{ id: number; name: string }>;
    filters: {
        [key: string]: string | undefined;
        project_id?: string;
        status?: string;
        search?: string;
    };
}

type ViewMode = 'day' | 'week' | 'month' | 'year';
type DisplayMode = 'gantt' | 'calendar';

export default function GanttIndex({ ganttData, projects, filters }: Props) {
    const [showFilters, setShowFilters] = useState(false);
    const [localFilters, setLocalFilters] = useState(filters);
    const [viewMode, setViewMode] = useState<ViewMode>('month');
    const [displayMode, setDisplayMode] = useState<DisplayMode>('gantt');
    const [currentDate, setCurrentDate] = useState(new Date());
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedTask, setSelectedTask] = useState<{ task: GanttTask; project: GanttItem } | null>(null);
    const [selectedProject, setSelectedProject] = useState<GanttItem | null>(null);

    // Filter data based on search
    const filteredData = useMemo(() => {
        if (!searchTerm) return ganttData;

        return ganttData.map(project => ({
            ...project,
            tasks: project.tasks?.filter(task =>
                task.name.toLowerCase().includes(searchTerm.toLowerCase())
            )
        })).filter(project =>
            project.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (project.tasks && project.tasks.length > 0)
        );
    }, [ganttData, searchTerm]);

    const handleFilter = (key: string, value: string) => {
        const newFilters = { ...localFilters, [key]: value };
        if (!value) delete newFilters[key];
        setLocalFilters(newFilters);
        router.get(route('gantt.index'), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setLocalFilters({});
        setSearchTerm('');
        router.get(route('gantt.index'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Generate timeline based on view mode
    const timeline = useMemo(() => {
        const start = new Date(currentDate);
        const dates: Date[] = [];

        switch (viewMode) {
            case 'day':
                start.setHours(0, 0, 0, 0);
                for (let i = 0; i < 24; i++) {
                    const date = new Date(start);
                    date.setHours(i);
                    dates.push(date);
                }
                break;
            case 'week':
                start.setDate(start.getDate() - start.getDay());
                for (let i = 0; i < 7; i++) {
                    const date = new Date(start);
                    date.setDate(start.getDate() + i);
                    dates.push(date);
                }
                break;
            case 'month':
                start.setDate(1);
                const daysInMonth = new Date(start.getFullYear(), start.getMonth() + 1, 0).getDate();
                for (let i = 0; i < daysInMonth; i++) {
                    const date = new Date(start);
                    date.setDate(i + 1);
                    dates.push(date);
                }
                break;
            case 'year':
                start.setMonth(0, 1);
                for (let i = 0; i < 12; i++) {
                    const date = new Date(start);
                    date.setMonth(i);
                    dates.push(date);
                }
                break;
        }

        return dates;
    }, [currentDate, viewMode]);

    const navigateTimeline = (direction: 'prev' | 'next') => {
        const newDate = new Date(currentDate);

        switch (viewMode) {
            case 'day':
                newDate.setDate(newDate.getDate() + (direction === 'next' ? 1 : -1));
                break;
            case 'week':
                newDate.setDate(newDate.getDate() + (direction === 'next' ? 7 : -7));
                break;
            case 'month':
                newDate.setMonth(newDate.getMonth() + (direction === 'next' ? 1 : -1));
                break;
            case 'year':
                newDate.setFullYear(newDate.getFullYear() + (direction === 'next' ? 1 : -1));
                break;
        }

        setCurrentDate(newDate);
    };

    const formatTimelineHeader = (date: Date) => {
        switch (viewMode) {
            case 'day':
                return date.getHours() + 'h';
            case 'week':
                return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
            case 'month':
                return date.getDate().toString();
            case 'year':
                return date.toLocaleDateString('fr-FR', { month: 'short' });
        }
    };

    const getCurrentPeriodLabel = () => {
        switch (viewMode) {
            case 'day':
                return currentDate.toLocaleDateString('fr-FR', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            case 'week':
                const weekStart = new Date(currentDate);
                weekStart.setDate(weekStart.getDate() - weekStart.getDay());
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekEnd.getDate() + 6);
                return `${weekStart.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })} - ${weekEnd.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}`;
            case 'month':
                return currentDate.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
            case 'year':
                return currentDate.getFullYear().toString();
        }
    };

    const calculateBarPosition = (start: string, end: string) => {
        const startDate = new Date(start);
        const endDate = new Date(end);
        const timelineStart = timeline[0];
        const timelineEnd = timeline[timeline.length - 1];

        const totalDuration = timelineEnd.getTime() - timelineStart.getTime();
        const startOffset = startDate.getTime() - timelineStart.getTime();
        const duration = endDate.getTime() - startDate.getTime();

        const left = Math.max(0, (startOffset / totalDuration) * 100);
        const width = Math.min(100 - left, (duration / totalDuration) * 100);

        return { left: `${left}%`, width: `${width}%` };
    };

    const isTaskInView = (start: string, end: string) => {
        const startDate = new Date(start);
        const endDate = new Date(end);
        const viewStart = timeline[0];
        const viewEnd = timeline[timeline.length - 1];

        return !(endDate < viewStart || startDate > viewEnd);
    };

    const getProgressColor = (progress: number) => {
        if (progress >= 100) return 'bg-green-500';
        if (progress >= 75) return 'bg-primary';
        if (progress >= 50) return 'bg-yellow-500';
        if (progress >= 25) return 'bg-orange-500';
        return 'bg-red-500';
    };

    const activeFiltersCount = Object.keys(localFilters).filter(
        key => localFilters[key as keyof typeof localFilters]
    ).length;

    return (
        <DashboardLayout>
            <Head title="Diagramme Gantt" />

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
                                Diagramme de Gantt
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Planification et suivi temporel des projets
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
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="search">Recherche</Label>
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <Input
                                            id="search"
                                            type="text"
                                            placeholder="Rechercher..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
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
                                    <Label htmlFor="status">Statut</Label>
                                    <select
                                        id="status"
                                        value={localFilters.status || ''}
                                        onChange={(e) => handleFilter('status', e.target.value)}
                                        className="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:border-gray-700"
                                    >
                                        <option value="">Tous les statuts</option>
                                        <option value="planning">Planification</option>
                                        <option value="active">Actif</option>
                                        <option value="on_hold">En pause</option>
                                        <option value="completed">Terminé</option>
                                    </select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* View Controls */}
                <Card>
                    <CardContent className="p-4">
                        <div className="flex flex-col gap-4">
                            {/* Display Mode Toggle */}
                            <div className="flex items-center justify-between">
                                <div className="flex gap-2">
                                    <Button
                                        variant={displayMode === 'gantt' ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => setDisplayMode('gantt')}
                                    >
                                        <ChartBarIcon className="h-4 w-4 mr-2" />
                                        Gantt
                                    </Button>
                                    <Button
                                        variant={displayMode === 'calendar' ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => setDisplayMode('calendar')}
                                    >
                                        <CalendarIcon className="h-4 w-4 mr-2" />
                                        Calendrier
                                    </Button>
                                </div>
                            </div>

                            {/* Timeline Navigation */}
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => navigateTimeline('prev')}
                                    >
                                        <ChevronLeftIcon className="h-4 w-4" />
                                    </Button>
                                    <div className="text-sm font-medium min-w-[200px] text-center">
                                        {getCurrentPeriodLabel()}
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => navigateTimeline('next')}
                                    >
                                        <ChevronRightIcon className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setCurrentDate(new Date())}
                                    >
                                        Aujourd'hui
                                    </Button>
                                </div>

                                <div className="flex gap-2">
                                    {(['day', 'week', 'month', 'year'] as ViewMode[]).map((mode) => (
                                        <Button
                                            key={mode}
                                            variant={viewMode === mode ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setViewMode(mode)}
                                        >
                                            {mode === 'day' && 'Jour'}
                                            {mode === 'week' && 'Semaine'}
                                            {mode === 'month' && 'Mois'}
                                            {mode === 'year' && 'Année'}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Gantt Chart or Calendar */}
                {displayMode === 'gantt' ? (
                <Card>
                    <CardContent className="p-0 overflow-x-auto">
                        <div className="min-w-[800px]">
                            {/* Timeline Header */}
                            <div className="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                <div className="w-64 p-4 font-semibold border-r border-gray-200 dark:border-gray-700">
                                    Projet / Tâche
                                </div>
                                <div className="flex-1 flex">
                                    {timeline.map((date, index) => (
                                        <div
                                            key={index}
                                            className="flex-1 p-2 text-center text-xs border-r border-gray-200 dark:border-gray-700"
                                        >
                                            {formatTimelineHeader(date)}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Gantt Rows */}
                            <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                {filteredData.length === 0 ? (
                                    <div className="p-8 text-center text-gray-500 dark:text-gray-400">
                                        Aucun projet trouvé
                                    </div>
                                ) : (
                                    filteredData.map((project) => (
                                        <div key={project.id}>
                                            {/* Project Row */}
                                            <div className="flex hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <div className="w-64 p-4 border-r border-gray-200 dark:border-gray-700">
                                                    <div className="flex items-center gap-2">
                                                        <div
                                                            className="w-3 h-3 rounded-full"
                                                            style={{ backgroundColor: project.color || '#3B82F6' }}
                                                        />
                                                        <span className="font-semibold text-sm">
                                                            {project.name}
                                                        </span>
                                                    </div>
                                                    <div className="text-xs text-gray-500 mt-1">
                                                        {project.progress}% complété
                                                    </div>
                                                </div>
                                                <div className="flex-1 relative py-6 px-2">
                                                    {project.start && project.end && isTaskInView(project.start, project.end) && (
                                                        <div
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                setSelectedProject(project);
                                                            }}
                                                            className="absolute h-8 rounded-lg shadow-sm cursor-pointer transition-all hover:shadow-lg hover:scale-105"
                                                            style={{
                                                                ...calculateBarPosition(project.start, project.end),
                                                                backgroundColor: project.color || '#3B82F6',
                                                                opacity: 0.8,
                                                            }}
                                                        >
                                                            <div
                                                                className={`h-full rounded-lg ${getProgressColor(project.progress)}`}
                                                                style={{ width: `${project.progress}%`, opacity: 0.5 }}
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Task Rows */}
                                            {project.tasks?.map((task) => {
                                                return (
                                                <div
                                                    key={task.id}
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        setSelectedTask({ task, project });
                                                    }}
                                                    className="flex hover:bg-gray-50 dark:hover:bg-gray-800/50 bg-gray-25 dark:bg-gray-900/20 transition-colors cursor-pointer"
                                                >
                                                    <div className="w-64 p-4 pl-8 border-r border-gray-200 dark:border-gray-700">
                                                        <div className="text-sm hover:text-primary dark:hover:text-blue-400">{task.name}</div>
                                                        <div className="text-xs text-gray-500 mt-1">
                                                            {task.assignee && `Assigné: ${task.assignee}`}
                                                        </div>
                                                    </div>
                                                    <div className="flex-1 relative py-4 px-2">
                                                        {task.start && task.end && isTaskInView(task.start, task.end) && (
                                                            <div
                                                                className={`absolute h-6 rounded shadow-sm transition-all hover:shadow-lg hover:scale-105 ${
                                                                    task.priority === 'highest' || task.priority === 'high'
                                                                        ? 'bg-red-500'
                                                                        : task.priority === 'medium'
                                                                        ? 'bg-yellow-500'
                                                                        : 'bg-primary'
                                                                }`}
                                                                style={calculateBarPosition(task.start, task.end)}
                                                            >
                                                                <div
                                                                    className="h-full rounded bg-green-500"
                                                                    style={{ width: `${task.progress}%`, opacity: 0.6 }}
                                                                />
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                                );
                                            })}
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>
                ) : (
                    /* Calendar View with Gantt Bars */
                    <Card>
                        <CardContent className="p-0 overflow-x-auto">
                            <div className="min-w-[800px]">
                                {/* Calendar Grid Header */}
                                <div className="grid border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
                                     style={{ gridTemplateColumns: `repeat(${timeline.length}, minmax(150px, 1fr))` }}>
                                    {timeline.map((date, index) => (
                                        <div
                                            key={index}
                                            className="p-3 text-center font-semibold border-r border-gray-200 dark:border-gray-700 text-sm"
                                        >
                                            {formatTimelineHeader(date)}
                                        </div>
                                    ))}
                                </div>

                                {/* Calendar Grid with Gantt Bars */}
                                <div className="relative">
                                    {/* Background Grid */}
                                    <div className="grid"
                                         style={{ gridTemplateColumns: `repeat(${timeline.length}, minmax(150px, 1fr))` }}>
                                        {timeline.map((date, index) => (
                                            <div
                                                key={index}
                                                className="border-r border-gray-200 dark:border-gray-700 min-h-[600px]"
                                            />
                                        ))}
                                    </div>

                                    {/* Gantt Bars Overlay */}
                                    <div className="absolute inset-0 p-4 space-y-1">
                                        {filteredData.length === 0 ? (
                                            <div className="text-center text-gray-500 dark:text-gray-400 py-8">
                                                Aucun projet trouvé
                                            </div>
                                        ) : (
                                            filteredData.map((project) => (
                                                <div key={project.id} className="space-y-1">
                                                    {/* Project Bar */}
                                                    {project.start && project.end && isTaskInView(project.start, project.end) && (
                                                        <div className="relative h-10 mb-2">
                                                            <div
                                                                onClick={(e) => {
                                                                    e.preventDefault();
                                                                    setSelectedProject(project);
                                                                }}
                                                                className="absolute h-10 rounded-lg shadow-md flex items-center px-3 cursor-pointer transition-all hover:shadow-lg hover:scale-105"
                                                                style={{
                                                                    ...calculateBarPosition(project.start, project.end),
                                                                    backgroundColor: project.color || '#3B82F6',
                                                                    opacity: 0.9,
                                                                }}
                                                            >
                                                                <div className="text-white text-sm font-semibold truncate">
                                                                    {project.name}
                                                                </div>
                                                                <div
                                                                    className="absolute bottom-0 left-0 h-1 bg-green-500 rounded-b-lg"
                                                                    style={{ width: `${project.progress}%` }}
                                                                />
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* Task Bars */}
                                                    {project.tasks?.map((task) => {
                                                        if (!task.start || !task.end || !isTaskInView(task.start, task.end)) {
                                                            return null;
                                                        }

                                                        return (
                                                            <div key={task.id} className="relative h-8">
                                                                <div
                                                                    onClick={(e) => {
                                                                        e.preventDefault();
                                                                        setSelectedTask({ task, project });
                                                                    }}
                                                                    className={`absolute h-8 rounded shadow-sm flex items-center px-2 transition-all hover:shadow-lg hover:scale-105 cursor-pointer ${
                                                                        task.priority === 'highest' || task.priority === 'high'
                                                                            ? 'bg-red-500 hover:bg-red-600'
                                                                            : task.priority === 'medium'
                                                                            ? 'bg-yellow-500 hover:bg-yellow-600'
                                                                            : 'bg-primary hover:bg-primary'
                                                                    }`}
                                                                    style={calculateBarPosition(task.start, task.end)}
                                                                    title={`${task.name} - ${task.assignee || 'Non assigné'}`}
                                                                >
                                                                    <div className="text-white text-xs truncate">
                                                                        {task.name}
                                                                    </div>
                                                                    <div
                                                                        className="absolute bottom-0 left-0 h-1 bg-green-400"
                                                                        style={{ width: `${task.progress}%` }}
                                                                    />
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Modals */}
            {selectedTask && (
                <TaskDetailsModal
                    isOpen={true}
                    onClose={() => setSelectedTask(null)}
                    task={selectedTask.task}
                    projectName={selectedTask.project.name}
                    projectColor={selectedTask.project.color}
                />
            )}

            {selectedProject && (
                <ProjectDetailsModal
                    isOpen={true}
                    onClose={() => setSelectedProject(null)}
                    project={selectedProject}
                />
            )}
        </DashboardLayout>
    );
}
