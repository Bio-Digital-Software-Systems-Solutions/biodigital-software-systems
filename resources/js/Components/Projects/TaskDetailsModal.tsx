import React from 'react';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';
import {
    XMarkIcon,
    CalendarIcon,
    UserIcon,
    ClockIcon,
    FlagIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ArrowTopRightOnSquareIcon,
} from '@heroicons/react/24/outline';

interface TaskDetailsModalProps {
    isOpen: boolean;
    onClose: () => void;
    task: {
        id: string;
        uuid: string;
        name: string;
        start: string;
        end: string;
        progress: number;
        assignee?: string;
        priority: string;
        status: string;
    };
    projectName: string;
    projectColor?: string;
}

export default function TaskDetailsModal({ isOpen, onClose, task, projectName, projectColor }: TaskDetailsModalProps) {

    const priorityConfig = {
        highest: { label: 'Très Haute', color: 'text-red-600 bg-red-100 dark:bg-red-900/30', icon: '🔴' },
        high: { label: 'Haute', color: 'text-orange-600 bg-orange-100 dark:bg-orange-900/30', icon: '🟠' },
        medium: { label: 'Moyenne', color: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30', icon: '🟡' },
        low: { label: 'Basse', color: 'text-primary bg-blue-100 dark:bg-blue-900/30', icon: '🔵' },
        lowest: { label: 'Très Basse', color: 'text-gray-600 bg-gray-100 dark:bg-gray-900/30', icon: '⚪' },
    };

    const statusConfig = {
        todo: { label: 'À faire', color: 'text-gray-600 bg-gray-100 dark:bg-gray-900/30' },
        in_progress: { label: 'En cours', color: 'text-primary bg-blue-100 dark:bg-blue-900/30' },
        in_review: { label: 'En révision', color: 'text-purple-600 bg-purple-100 dark:bg-purple-900/30' },
        blocked: { label: 'Bloqué', color: 'text-red-600 bg-red-100 dark:bg-red-900/30' },
        done: { label: 'Terminé', color: 'text-green-600 bg-green-100 dark:bg-green-900/30' },
    };

    const priority = priorityConfig[task.priority as keyof typeof priorityConfig] || priorityConfig.medium;
    const status = statusConfig[task.status as keyof typeof statusConfig] || statusConfig.todo;

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const calculateDuration = () => {
        const start = new Date(task.start);
        const end = new Date(task.end);
        const diffTime = Math.abs(end.getTime() - start.getTime());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    return (
        <Modal show={isOpen} onClose={onClose} maxWidth="2xl">
            <div className="p-6">
                {/* Header */}
                <div className="flex items-start justify-between mb-6">
                    <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                            <div
                                className="w-3 h-3 rounded-full"
                                style={{ backgroundColor: projectColor || '#3B82F6' }}
                            />
                            <span className="text-sm text-gray-600 dark:text-gray-400">{projectName}</span>
                        </div>
                        <Link
                            href={`/project-tasks/${task.uuid}`}
                            className="text-2xl font-bold text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 transition-colors inline-block"
                        >
                            {task.name}
                        </Link>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        <XMarkIcon className="h-6 w-6" />
                    </button>
                </div>

                {/* Status and Priority Badges */}
                <div className="flex gap-3 mb-6">
                    <div className={`px-3 py-1 rounded-full text-sm font-medium ${status.color}`}>
                        {status.label}
                    </div>
                    <div className={`px-3 py-1 rounded-full text-sm font-medium ${priority.color}`}>
                        {priority.icon} {priority.label}
                    </div>
                </div>

                {/* Progress Bar */}
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Progression</span>
                        <span className="text-sm font-bold text-gray-900 dark:text-white">{task.progress}%</span>
                    </div>
                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div
                            className="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300 flex items-center justify-end pr-2"
                            style={{ width: `${task.progress}%` }}
                        >
                            {task.progress > 10 && (
                                <CheckCircleIcon className="h-3 w-3 text-white" />
                            )}
                        </div>
                    </div>
                </div>

                {/* Details Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    {/* Assigné */}
                    <div className="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <UserIcon className="h-5 w-5 text-primary dark:text-blue-400" />
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Assigné à</p>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {task.assignee || 'Non assigné'}
                            </p>
                        </div>
                    </div>

                    {/* Durée */}
                    <div className="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div className="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <ClockIcon className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Durée</p>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {calculateDuration()} jour{calculateDuration() > 1 ? 's' : ''}
                            </p>
                        </div>
                    </div>

                    {/* Date de début */}
                    <div className="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <CalendarIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Date de début</p>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {formatDate(task.start)}
                            </p>
                        </div>
                    </div>

                    {/* Date de fin */}
                    <div className="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div className="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <CalendarIcon className="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Date de fin</p>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {formatDate(task.end)}
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
                        <Link href={`/project-tasks/${task.uuid}`}>
                            <ArrowTopRightOnSquareIcon className="h-4 w-4 mr-2" />
                            Voir les détails complets
                        </Link>
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
