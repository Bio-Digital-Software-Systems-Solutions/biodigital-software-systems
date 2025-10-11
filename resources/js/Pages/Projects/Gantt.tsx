import React, { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Project } from '@/Types/Project';
import {
    ArrowLeftIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChartBarIcon,
    CalendarIcon,
} from '@heroicons/react/24/outline';

interface Props {
    project: Project;
}

type ViewMode = 'day' | 'week' | 'month' | 'year';
type DisplayMode = 'gantt' | 'calendar';

export default function ProjectGantt({ project }: Props) {
    const [viewMode, setViewMode] = useState<ViewMode>('month');
    const [displayMode, setDisplayMode] = useState<DisplayMode>('gantt');
    const [currentDate, setCurrentDate] = useState(new Date());

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

    const calculateTaskProgress = (task: any) => {
        return task.status === 'done' ? 100 : task.status === 'in_review' ? 75 : task.status === 'in_progress' ? 50 : 0;
    };

    return (
        <DashboardLayout>
            <Head title={`Gantt - ${project.name}`} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('projects.show', project.uuid)}>
                                <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                Retour
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                {project.name}
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Diagramme de Gantt
                            </p>
                        </div>
                    </div>
                </div>

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
                                        Tâche
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
                                    {project.tasks && project.tasks.length > 0 ? (
                                        project.tasks.map((task) => {
                                            const taskProgress = calculateTaskProgress(task);
                                            return (
                                                <Link
                                                    key={task.id}
                                                    href={route('projects.board', project.uuid)}
                                                    className="flex hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer"
                                                >
                                                    <div className="w-64 p-4 border-r border-gray-200 dark:border-gray-700">
                                                        <div className="text-sm hover:text-primary dark:hover:text-blue-400">
                                                            {task.title}
                                                        </div>
                                                        <div className="text-xs text-gray-500 mt-1">
                                                            {task.assignee && `Assigné: ${task.assignee.first_name} ${task.assignee.last_name}`}
                                                        </div>
                                                    </div>
                                                    <div className="flex-1 relative py-4 px-2">
                                                        {task.started_at && task.due_date && isTaskInView(task.started_at, task.due_date) ? (
                                                            <div
                                                                className={`absolute h-6 rounded shadow-sm transition-all hover:shadow-lg hover:scale-105 ${
                                                                    task.priority === 'highest' || task.priority === 'high'
                                                                        ? 'bg-red-500'
                                                                        : task.priority === 'medium'
                                                                        ? 'bg-yellow-500'
                                                                        : 'bg-primary'
                                                                }`}
                                                                style={calculateBarPosition(task.started_at, task.due_date)}
                                                            >
                                                                <div
                                                                    className={`h-full rounded ${getProgressColor(taskProgress)}`}
                                                                    style={{ width: `${taskProgress}%`, opacity: 0.6 }}
                                                                />
                                                            </div>
                                                        ) : (
                                                            <div className="text-xs text-gray-400 dark:text-gray-500 italic">
                                                                Dates non définies
                                                            </div>
                                                        )}
                                                    </div>
                                                </Link>
                                            );
                                        })
                                    ) : (
                                        <div className="p-8 text-center text-gray-500 dark:text-gray-400">
                                            Aucune tâche associée à ce projet
                                        </div>
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
                                        {project.tasks && project.tasks.length > 0 ? (
                                            project.tasks.map((task) => {
                                                const taskProgress = calculateTaskProgress(task);
                                                if (!task.started_at || !task.due_date || !isTaskInView(task.started_at, task.due_date)) {
                                                    return null;
                                                }

                                                return (
                                                    <div key={task.id} className="relative h-8">
                                                        <Link
                                                            href={route('projects.board', project.uuid)}
                                                            className={`absolute h-8 rounded shadow-sm flex items-center px-2 transition-all hover:shadow-lg hover:scale-105 cursor-pointer ${
                                                                task.priority === 'highest' || task.priority === 'high'
                                                                    ? 'bg-red-500 hover:bg-red-600'
                                                                    : task.priority === 'medium'
                                                                    ? 'bg-yellow-500 hover:bg-yellow-600'
                                                                    : 'bg-primary hover:bg-primary'
                                                            }`}
                                                            style={calculateBarPosition(task.started_at, task.due_date)}
                                                            title={`${task.title} - ${task.assignee ? `${task.assignee.first_name} ${task.assignee.last_name}` : 'Non assigné'}`}
                                                        >
                                                            <div className="text-white text-xs truncate">
                                                                {task.title}
                                                            </div>
                                                            <div
                                                                className="absolute bottom-0 left-0 h-1 bg-green-400"
                                                                style={{ width: `${taskProgress}%` }}
                                                            />
                                                        </Link>
                                                    </div>
                                                );
                                            })
                                        ) : (
                                            <div className="text-center text-gray-500 dark:text-gray-400 py-8">
                                                Aucune tâche associée à ce projet
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Info */}
                <Card>
                    <CardContent className="p-4">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            💡 Cliquez sur une tâche pour accéder au tableau Kanban du projet
                        </p>
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
