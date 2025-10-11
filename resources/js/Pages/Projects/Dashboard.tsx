import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import {
    FolderIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    PlusIcon,
    ChartBarIcon,
    UsersIcon,
    CalendarIcon,
    ArrowLeftIcon,
} from '@heroicons/react/24/outline';

interface Project {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    description?: string;
    status: string;
    priority: string;
    color?: string;
    tasks_count: number;
    completed_tasks_count?: number;
    progress: number;
    manager?: {
        id: number;
        first_name: string;
        last_name: string;
    };
    members_count?: number;
    start_date?: string;
    end_date?: string;
}

interface Stats {
    total_projects: number;
    active_projects: number;
    completed_projects: number;
    on_hold_projects: number;
    total_tasks: number;
    completed_tasks: number;
    overdue_tasks: number;
    in_progress_tasks: number;
    total_epics: number;
    active_sprints: number;
    upcoming_sprints: number;
}

interface Props {
    projects: Project[];
    stats: Stats;
    recentProjects: Project[];
}

export default function ProjectsDashboard({ projects, stats, recentProjects }: Props) {
    const getStatusColor = (status: string) => {
        switch (status) {
            case 'active':
                return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
            case 'planning':
                return 'bg-blue-100 text-primary dark:bg-blue-900 dark:text-blue-300';
            case 'on_hold':
                return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300';
            case 'completed':
                return 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300';
            case 'cancelled':
                return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
            default:
                return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
        }
    };

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'highest':
                return 'text-red-600 dark:text-red-400';
            case 'high':
                return 'text-orange-600 dark:text-orange-400';
            case 'medium':
                return 'text-yellow-600 dark:text-yellow-400';
            case 'low':
                return 'text-primary dark:text-blue-400';
            case 'lowest':
                return 'text-gray-600 dark:text-gray-400';
            default:
                return 'text-gray-600 dark:text-gray-400';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'active':
                return 'Actif';
            case 'planning':
                return 'Planification';
            case 'on_hold':
                return 'En pause';
            case 'completed':
                return 'Terminé';
            case 'cancelled':
                return 'Annulé';
            default:
                return status;
        }
    };

    return (
        <DashboardLayout
            title="Gestion des Projets"
            description="Vue d'ensemble de vos projets et tâches"
            actions={
                <>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/dashboard">
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour au Dashboard
                        </Link>
                    </Button>
                    <Button asChild size="lg">
                        <Link href={route('projects.create')}>
                            <PlusIcon className="h-5 w-5 mr-2" />
                            Nouveau projet
                        </Link>
                    </Button>
                </>
            }
        >
            <Head title="Dashboard Projets" />

            <div className="space-y-6">

                {/* Main Statistics Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Link href="/projects/all">
                        <Card className="bg-gradient-to-br from-blue-500 to-blue-600 text-white border-0 hover:shadow-xl transition-all duration-200 cursor-pointer transform hover:scale-105">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-blue-100 text-sm font-medium">Total Projets</p>
                                        <p className="text-4xl font-bold mt-2">{stats.total_projects}</p>
                                        <p className="text-blue-100 text-xs mt-1">{stats.active_projects} actifs</p>
                                    </div>
                                    <div className="h-14 w-14 bg-white/20 rounded-full flex items-center justify-center">
                                        <FolderIcon className="h-7 w-7" />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href="/tasks">
                        <Card className="bg-gradient-to-br from-purple-500 to-purple-600 text-white border-0 hover:shadow-xl transition-all duration-200 cursor-pointer transform hover:scale-105">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-purple-100 text-sm font-medium">Tâches</p>
                                        <p className="text-4xl font-bold mt-2">{stats.total_tasks}</p>
                                        <p className="text-purple-100 text-xs mt-1">
                                            {stats.completed_tasks} terminées, {stats.in_progress_tasks} en cours
                                        </p>
                                    </div>
                                    <div className="h-14 w-14 bg-white/20 rounded-full flex items-center justify-center">
                                        <CheckCircleIcon className="h-7 w-7" />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href="/epics">
                        <Card className="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white border-0 hover:shadow-xl transition-all duration-200 cursor-pointer transform hover:scale-105">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-indigo-100 text-sm font-medium">Epics</p>
                                        <p className="text-4xl font-bold mt-2">{stats.total_epics}</p>
                                        <p className="text-indigo-100 text-xs mt-1">Fonctionnalités majeures</p>
                                    </div>
                                    <div className="h-14 w-14 bg-white/20 rounded-full flex items-center justify-center">
                                        <ChartBarIcon className="h-7 w-7" />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href="/sprints">
                        <Card className="bg-gradient-to-br from-green-500 to-green-600 text-white border-0 hover:shadow-xl transition-all duration-200 cursor-pointer transform hover:scale-105">
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-green-100 text-sm font-medium">Sprints</p>
                                        <p className="text-4xl font-bold mt-2">{stats.active_sprints}</p>
                                        <p className="text-green-100 text-xs mt-1">
                                            {stats.upcoming_sprints} à venir
                                        </p>
                                    </div>
                                    <div className="h-14 w-14 bg-white/20 rounded-full flex items-center justify-center">
                                        <CalendarIcon className="h-7 w-7" />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                {/* Quick Stats Bar */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">Tâches en retard</p>
                                    <p className="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                                        {stats.overdue_tasks}
                                    </p>
                                </div>
                                <ExclamationTriangleIcon className="h-8 w-8 text-red-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">Projets terminés</p>
                                    <p className="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                                        {stats.completed_projects}
                                    </p>
                                </div>
                                <CheckCircleIcon className="h-8 w-8 text-green-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">En pause</p>
                                    <p className="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                                        {stats.on_hold_projects}
                                    </p>
                                </div>
                                <ClockIcon className="h-8 w-8 text-yellow-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">Taux de complétion</p>
                                    <p className="text-2xl font-bold text-primary dark:text-blue-400 mt-1">
                                        {stats.total_tasks > 0
                                            ? Math.round((stats.completed_tasks / stats.total_tasks) * 100)
                                            : 0}%
                                    </p>
                                </div>
                                <ChartBarIcon className="h-8 w-8 text-primary" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Quick Actions and Views */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Quick Actions */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Actions Rapides</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Button asChild variant="outline" className="w-full justify-start h-12 text-base">
                                <Link href={route('projects.create')}>
                                    <PlusIcon className="h-5 w-5 mr-3" />
                                    Créer un projet
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="w-full justify-start h-12 text-base">
                                <Link href="/projects/all">
                                    <FolderIcon className="h-5 w-5 mr-3" />
                                    Tous les projets
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="w-full justify-start h-12 text-base">
                                <Link href="/projects/all?status=active">
                                    <CheckCircleIcon className="h-5 w-5 mr-3" />
                                    Projets actifs
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Views */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Vues</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Button asChild variant="outline" className="w-full justify-start h-12 text-base">
                                <Link href="/kanban">
                                    <svg className="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                    </svg>
                                    Kanban Board
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="w-full justify-start h-12 text-base">
                                <Link href="/gantt">
                                    <ChartBarIcon className="h-5 w-5 mr-3" />
                                    Diagramme Gantt
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="w-full justify-start h-12 text-base">
                                <Link href="/tasks">
                                    <CheckCircleIcon className="h-5 w-5 mr-3" />
                                    Toutes les tâches
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="w-full justify-start h-12 text-base">
                                <Link href="/sprints">
                                    <CalendarIcon className="h-5 w-5 mr-3" />
                                    Gestion Sprints
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                {/* Projects Grid and Activity */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Recent Projects */}
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader className="border-b border-gray-200 dark:border-gray-700">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-xl">Projets Récents</CardTitle>
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link href="/projects/all">Voir tout</Link>
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {recentProjects.length > 0 ? (
                                        recentProjects.map((project) => (
                                            <Link
                                                key={project.id}
                                                href={route('projects.show', project.uuid)}
                                                className="block p-6 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                            >
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-center gap-3 mb-2">
                                                            <div
                                                                className="w-3 h-3 rounded-full flex-shrink-0"
                                                                style={{ backgroundColor: project.color || '#3B82F6' }}
                                                            />
                                                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white truncate hover:text-primary dark:hover:text-blue-400 transition-colors">
                                                                {project.name}
                                                            </h3>
                                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(project.status)}`}>
                                                                {getStatusLabel(project.status)}
                                                            </span>
                                                        </div>
                                                        {project.description && (
                                                            <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
                                                                {project.description}
                                                            </p>
                                                        )}
                                                        <div className="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                                            <span className="flex items-center gap-1">
                                                                <ChartBarIcon className="h-4 w-4" />
                                                                {project.tasks_count || 0} tâches
                                                            </span>
                                                            {project.members_count !== undefined && (
                                                                <span className="flex items-center gap-1">
                                                                    <UsersIcon className="h-4 w-4" />
                                                                    {project.members_count} membres
                                                                </span>
                                                            )}
                                                            {project.manager && (
                                                                <span className="flex items-center gap-1">
                                                                    {project.manager.first_name} {project.manager.last_name}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="ml-4 flex-shrink-0">
                                                        <div className="w-20">
                                                            <div className="flex items-center justify-between mb-1">
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                                    {project.progress || 0}%
                                                                </span>
                                                            </div>
                                                            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                                <div
                                                                    className="h-2 rounded-full transition-all"
                                                                    style={{
                                                                        width: `${project.progress || 0}%`,
                                                                        backgroundColor: project.color || '#3B82F6',
                                                                    }}
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </Link>
                                        ))
                                    ) : (
                                        <div className="p-12 text-center">
                                            <FolderIcon className="mx-auto h-12 w-12 text-gray-400" />
                                            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                                Aucun projet
                                            </h3>
                                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                Commencez par créer un nouveau projet.
                                            </p>
                                            <div className="mt-6">
                                                <Button asChild>
                                                    <Link href={route('projects.create')}>
                                                        <PlusIcon className="h-5 w-5 mr-2" />
                                                        Nouveau projet
                                                    </Link>
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Status Overview & Quick Actions */}
                    <div className="space-y-6">
                        {/* Status Overview */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Aperçu des Statuts</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600 dark:text-gray-400">Actifs</span>
                                        <span className="text-sm font-semibold text-gray-900 dark:text-white">
                                            {stats.active_projects}
                                        </span>
                                    </div>
                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            className="bg-green-500 h-2 rounded-full"
                                            style={{
                                                width: `${stats.total_projects > 0 ? (stats.active_projects / stats.total_projects) * 100 : 0}%`,
                                            }}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600 dark:text-gray-400">Terminés</span>
                                        <span className="text-sm font-semibold text-gray-900 dark:text-white">
                                            {stats.completed_projects}
                                        </span>
                                    </div>
                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            className="bg-purple-500 h-2 rounded-full"
                                            style={{
                                                width: `${stats.total_projects > 0 ? (stats.completed_projects / stats.total_projects) * 100 : 0}%`,
                                            }}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600 dark:text-gray-400">En pause</span>
                                        <span className="text-sm font-semibold text-gray-900 dark:text-white">
                                            {stats.on_hold_projects}
                                        </span>
                                    </div>
                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            className="bg-yellow-500 h-2 rounded-full"
                                            style={{
                                                width: `${stats.total_projects > 0 ? (stats.on_hold_projects / stats.total_projects) * 100 : 0}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Task Completion */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Progression Globale</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-center">
                                    <div className="inline-flex items-center justify-center w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white mb-4">
                                        <div className="text-center">
                                            <div className="text-3xl font-bold">
                                                {stats.total_tasks > 0
                                                    ? Math.round((stats.completed_tasks / stats.total_tasks) * 100)
                                                    : 0}
                                                %
                                            </div>
                                            <div className="text-xs opacity-80">Complété</div>
                                        </div>
                                    </div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        {stats.completed_tasks} sur {stats.total_tasks} tâches terminées
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
