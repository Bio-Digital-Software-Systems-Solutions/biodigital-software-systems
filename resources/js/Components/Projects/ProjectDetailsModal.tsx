import React from 'react';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';
import {
    XMarkIcon,
    CalendarIcon,
    ChartBarIcon,
    ClockIcon,
    CheckCircleIcon,
    ArrowTopRightOnSquareIcon,
    ListBulletIcon,
} from '@heroicons/react/24/outline';

interface ProjectDetailsModalProps {
    isOpen: boolean;
    onClose: () => void;
    project: {
        id: string;
        uuid: string;
        name: string;
        start: string;
        end: string;
        progress: number;
        color?: string;
        tasks?: any[];
    };
}

export default function ProjectDetailsModal({ isOpen, onClose, project }: ProjectDetailsModalProps) {

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const calculateDuration = () => {
        const start = new Date(project.start);
        const end = new Date(project.end);
        const diffTime = Math.abs(end.getTime() - start.getTime());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    const taskStats = {
        total: project.tasks?.length || 0,
        completed: project.tasks?.filter(t => t.status === 'done').length || 0,
        inProgress: project.tasks?.filter(t => t.status === 'in_progress').length || 0,
        todo: project.tasks?.filter(t => t.status === 'todo').length || 0,
    };

    return (
        <Modal show={isOpen} onClose={onClose} maxWidth="2xl">
            <div className="p-6">
                {/* Header */}
                <div className="flex items-start justify-between mb-6">
                    <div className="flex-1">
                        <div className="flex items-center gap-3 mb-3">
                            <div
                                className="w-4 h-4 rounded-full"
                                style={{ backgroundColor: project.color || '#3B82F6' }}
                            />
                            <Link
                                href={`/projects/${project.uuid}`}
                                className="text-2xl font-bold text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 transition-colors"
                            >
                                {project.name}
                            </Link>
                        </div>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Vue d'ensemble du projet
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        <XMarkIcon className="h-6 w-6" />
                    </button>
                </div>

                {/* Progress Section */}
                <div className="mb-6 p-5 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl">
                    <div className="flex items-center justify-between mb-3">
                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Progression globale
                        </span>
                        <span className="text-2xl font-bold text-primary dark:text-blue-400">
                            {project.progress}%
                        </span>
                    </div>
                    <div className="w-full bg-white dark:bg-gray-700 rounded-full h-4 shadow-inner">
                        <div
                            className="bg-gradient-to-r from-blue-500 to-blue-600 h-4 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                            style={{ width: `${project.progress}%` }}
                        >
                            {project.progress > 15 && (
                                <CheckCircleIcon className="h-3 w-3 text-white" />
                            )}
                        </div>
                    </div>
                </div>

                {/* Task Statistics */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                        <div className="text-2xl font-bold text-gray-900 dark:text-white">
                            {taskStats.total}
                        </div>
                        <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Tâches totales
                        </div>
                    </div>
                    <div className="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
                        <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                            {taskStats.completed}
                        </div>
                        <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Terminées
                        </div>
                    </div>
                    <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                        <div className="text-2xl font-bold text-primary dark:text-blue-400">
                            {taskStats.inProgress}
                        </div>
                        <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            En cours
                        </div>
                    </div>
                    <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                        <div className="text-2xl font-bold text-gray-600 dark:text-gray-400">
                            {taskStats.todo}
                        </div>
                        <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            À faire
                        </div>
                    </div>
                </div>

                {/* Details Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    {/* Date de début */}
                    <div className="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <CalendarIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Date de début</p>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {formatDate(project.start)}
                            </p>
                        </div>
                    </div>

                    {/* Date de fin */}
                    <div className="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div className="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <CalendarIcon className="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Date de fin prévue</p>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {formatDate(project.end)}
                            </p>
                        </div>
                    </div>

                    {/* Durée */}
                    <div className="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div className="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <ClockIcon className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Durée totale</p>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {calculateDuration()} jour{calculateDuration() > 1 ? 's' : ''}
                            </p>
                        </div>
                    </div>

                    {/* Taux de complétion */}
                    <div className="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <ChartBarIcon className="h-5 w-5 text-primary dark:text-blue-400" />
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Taux de complétion</p>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {taskStats.total > 0
                                    ? `${Math.round((taskStats.completed / taskStats.total) * 100)}%`
                                    : '0%'}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Actions */}
                <div className="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button variant="outline" onClick={onClose} className="flex-1">
                        Fermer
                    </Button>
                    <Button asChild className="flex-1">
                        <Link href={`/projects/${project.uuid}`}>
                            <ArrowTopRightOnSquareIcon className="h-4 w-4 mr-2" />
                            Voir le projet complet
                        </Link>
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
