import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { toast } from 'sonner';
import {
    AcademicCapIcon,
    CalendarDaysIcon,
    ClockIcon,
    MapPinIcon,
    UsersIcon,
    PlusIcon,
    TrashIcon,
    PencilIcon,
    CheckIcon,
    XMarkIcon,
    ChartBarIcon,
} from '@heroicons/react/24/outline';

interface Quiz {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    duration_minutes: number;
    max_score: number;
    passing_score: number;
    available_from?: string;
    available_until?: string;
    is_active: boolean;
    training: {
        id: number;
        title: string;
    };
}

interface CompletionStats {
    total_students: number;
    total_attempts: number;
    completed_attempts: number;
    passed_attempts: number;
    completion_rate: number;
    pass_rate: number;
}

interface TrainingClass {
    id: number;
    uuid: string;
    name: string;
    date: string;
    start_time: string;
    end_time: string;
    room?: string;
    students_count: number;
    materials_count: number;
    is_assigned: boolean;
    assignment_data?: {
        assigned_at: string;
        available_from?: string;
        available_until?: string;
        is_active: boolean;
    };
    completion_stats?: CompletionStats;
}

interface Props {
    quiz: Quiz;
    availableClasses: TrainingClass[];
}

export default function ClassAssignments({ quiz, availableClasses }: Props) {
    const [editingClass, setEditingClass] = useState<number | null>(null);
    const [showBulkAssign, setShowBulkAssign] = useState(false);

    const { data, setData, post, delete: destroy, put, processing } = useForm({
        available_from: '',
        available_until: '',
        is_active: true,
        class_ids: [] as number[],
    });

    const assignToClass = (classId: number) => {
        (post as any)(route('trainings.quizzes.assign-to-class', [quiz.training.id, quiz.uuid, classId]), {
            available_from: (data as any).available_from || null,
            available_until: (data as any).available_until || null,
            onSuccess: () => {
                toast.success('Quiz assigné à la classe avec succès');
                setData({ ...data, available_from: '', available_until: '' });
            },
            onError: (errors: any) => {
                console.log('Errors:', errors);
                toast.error('Erreur lors de l\'assignation du quiz');
            },
        });
    };

    const removeFromClass = (classId: number) => {
        destroy(route('trainings.quizzes.remove-from-class', [quiz.training.id, quiz.uuid, classId]), {
            onSuccess: () => {
                toast.success('Quiz retiré de la classe avec succès');
            },
            onError: () => {
                toast.error('Erreur lors du retrait du quiz');
            },
        });
    };

    const updateAssignment = (classId: number, assignmentData: any) => {
        put(route('trainings.quizzes.update-class-assignment', [quiz.training.id, quiz.uuid, classId]), {
            ...assignmentData,
            onSuccess: () => {
                toast.success('Paramètres d\'assignation mis à jour');
                setEditingClass(null);
            },
            onError: () => {
                toast.error('Erreur lors de la mise à jour');
            },
        });
    };

    const bulkAssignClasses = () => {
        (post as any)(route('trainings.quizzes.bulk-assign-classes', [quiz.training.id, quiz.uuid]), {
            class_ids: (data as any).class_ids,
            available_from: (data as any).available_from || null,
            available_until: (data as any).available_until || null,
            onSuccess: () => {
                toast.success(`Quiz assigné à ${data.class_ids.length} classe(s)`);
                setData({ ...data, class_ids: [], available_from: '', available_until: '' });
                setShowBulkAssign(false);
            },
            onError: () => {
                toast.error('Erreur lors de l\'assignation en masse');
            },
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const formatTime = (timeString: string) => {
        return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getCompletionColor = (rate: number) => {
        if (rate >= 80) return 'text-green-600 dark:text-green-400';
        if (rate >= 60) return 'text-yellow-600 dark:text-yellow-400';
        return 'text-red-600 dark:text-red-400';
    };

    const assignedClasses = availableClasses.filter(c => c.is_assigned);
    const unassignedClasses = availableClasses.filter(c => !c.is_assigned);

    return (
        <DashboardLayout
            title="Assignations de Quiz"
            description={`Gérez les assignations du quiz "${quiz.title}" aux classes`}
            actions={
                <div className="flex items-center gap-3">
                    <button
                        onClick={() => setShowBulkAssign(true)}
                        className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        disabled={unassignedClasses.length === 0}
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Assignation en masse
                    </button>
                    <Link
                        href={route('trainings.quizzes.stats', [quiz.training.id, quiz.uuid])}
                        className="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                        <ChartBarIcon className="w-4 h-4 mr-2" />
                        Statistiques
                    </Link>
                    <Link
                        href={route('trainings.quizzes.index', quiz.training.id)}
                        className="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                        Retour aux quiz
                    </Link>
                </div>
            }
        >
            <Head title={`Assignations - ${quiz.title}`} />

            {/* Quiz Info */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            {quiz.title}
                        </h2>
                        {quiz.description && (
                            <p className="text-gray-600 dark:text-gray-400 mb-4">
                                {quiz.description}
                            </p>
                        )}
                        <div className="flex items-center gap-6 text-sm text-gray-500 dark:text-gray-400">
                            <div className="flex items-center gap-1">
                                <ClockIcon className="w-4 h-4" />
                                {quiz.duration_minutes} minutes
                            </div>
                            <div className="flex items-center gap-1">
                                <AcademicCapIcon className="w-4 h-4" />
                                {quiz.passing_score}% requis
                            </div>
                            <div className="flex items-center gap-1">
                                <span className={`px-2 py-1 rounded-full text-xs ${
                                    quiz.is_active
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                }`}>
                                    {quiz.is_active ? 'Actif' : 'Inactif'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Bulk Assignment Modal */}
            {showBulkAssign && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Assignation en masse
                                </h3>
                                <button
                                    onClick={() => setShowBulkAssign(false)}
                                    className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                >
                                    <XMarkIcon className="w-5 h-5" />
                                </button>
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Sélectionner les classes
                                    </label>
                                    <div className="space-y-2 max-h-40 overflow-y-auto">
                                        {unassignedClasses.map((trainingClass) => (
                                            <label key={trainingClass.id} className="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={data.class_ids.includes(trainingClass.id)}
                                                    onChange={(e) => {
                                                        if (e.target.checked) {
                                                            setData('class_ids', [...data.class_ids, trainingClass.id]);
                                                        } else {
                                                            setData('class_ids', data.class_ids.filter(id => id !== trainingClass.id));
                                                        }
                                                    }}
                                                    className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                />
                                                <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                    {trainingClass.name}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Disponible à partir de
                                        </label>
                                        <input
                                            type="datetime-local"
                                            value={data.available_from}
                                            onChange={(e) => setData('available_from', e.target.value)}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Disponible jusqu'à
                                        </label>
                                        <input
                                            type="datetime-local"
                                            value={data.available_until}
                                            onChange={(e) => setData('available_until', e.target.value)}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 mt-6">
                                <button
                                    onClick={() => setShowBulkAssign(false)}
                                    className="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                >
                                    Annuler
                                </button>
                                <button
                                    onClick={bulkAssignClasses}
                                    disabled={processing || data.class_ids.length === 0}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                                >
                                    Assigner ({data.class_ids.length})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Assigned Classes */}
            <div className="mb-8">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Classes assignées ({assignedClasses.length})
                    </h3>
                </div>

                {assignedClasses.length === 0 ? (
                    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-8 text-center">
                        <AcademicCapIcon className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-500 dark:text-gray-400">
                            Aucune classe assignée pour ce quiz
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4">
                        {assignedClasses.map((trainingClass) => (
                            <div key={trainingClass.id} className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-4 mb-3">
                                            <h4 className="text-lg font-medium text-gray-900 dark:text-white">
                                                {trainingClass.name}
                                            </h4>
                                            <span className="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full text-xs">
                                                Assigné
                                            </span>
                                        </div>

                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <CalendarDaysIcon className="w-4 h-4" />
                                                {formatDate(trainingClass.date)}
                                            </div>
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <ClockIcon className="w-4 h-4" />
                                                {formatTime(trainingClass.start_time)} - {formatTime(trainingClass.end_time)}
                                            </div>
                                            {trainingClass.room && (
                                                <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                    <MapPinIcon className="w-4 h-4" />
                                                    {trainingClass.room}
                                                </div>
                                            )}
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <UsersIcon className="w-4 h-4" />
                                                {trainingClass.students_count} étudiants
                                            </div>
                                        </div>

                                        {/* Completion Stats */}
                                        {trainingClass.completion_stats && (
                                            <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                                                <h5 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                                    Statistiques de completion
                                                </h5>
                                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                    <div>
                                                        <span className="text-gray-600 dark:text-gray-400">Tentatives:</span>
                                                        <div className="font-medium">{trainingClass.completion_stats.completed_attempts}/{trainingClass.completion_stats.total_students}</div>
                                                    </div>
                                                    <div>
                                                        <span className="text-gray-600 dark:text-gray-400">Taux de completion:</span>
                                                        <div className={`font-medium ${getCompletionColor(trainingClass.completion_stats.completion_rate)}`}>
                                                            {trainingClass.completion_stats.completion_rate}%
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span className="text-gray-600 dark:text-gray-400">Réussite:</span>
                                                        <div className={`font-medium ${getCompletionColor(trainingClass.completion_stats.pass_rate)}`}>
                                                            {trainingClass.completion_stats.pass_rate}%
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span className="text-gray-600 dark:text-gray-400">Score moyen:</span>
                                                        <div className="font-medium">{trainingClass.completion_stats.passed_attempts}/{trainingClass.completion_stats.completed_attempts}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Assignment Info */}
                                        {trainingClass.assignment_data && (
                                            <div className="text-xs text-gray-500 dark:text-gray-400">
                                                Assigné le {new Date(trainingClass.assignment_data.assigned_at).toLocaleDateString('fr-FR')}
                                                {trainingClass.assignment_data.available_from && (
                                                    <span> • Disponible à partir du {new Date(trainingClass.assignment_data.available_from).toLocaleDateString('fr-FR')}</span>
                                                )}
                                                {trainingClass.assignment_data.available_until && (
                                                    <span> • Jusqu'au {new Date(trainingClass.assignment_data.available_until).toLocaleDateString('fr-FR')}</span>
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <button
                                            onClick={() => setEditingClass(editingClass === trainingClass.id ? null : trainingClass.id)}
                                            className="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                            title="Modifier les paramètres"
                                        >
                                            <PencilIcon className="w-4 h-4" />
                                        </button>
                                        <button
                                            onClick={() => removeFromClass(trainingClass.id)}
                                            className="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                            title="Retirer de cette classe"
                                        >
                                            <TrashIcon className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                {/* Edit Assignment Settings */}
                                {editingClass === trainingClass.id && (
                                    <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                        <div className="grid grid-cols-3 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Disponible à partir de
                                                </label>
                                                <input
                                                    type="datetime-local"
                                                    defaultValue={trainingClass.assignment_data?.available_from || ''}
                                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Disponible jusqu'à
                                                </label>
                                                <input
                                                    type="datetime-local"
                                                    defaultValue={trainingClass.assignment_data?.available_until || ''}
                                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                />
                                            </div>
                                            <div className="flex items-end">
                                                <label className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        defaultChecked={trainingClass.assignment_data?.is_active}
                                                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    />
                                                    <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                        Actif
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div className="flex justify-end gap-2 mt-4">
                                            <button
                                                onClick={() => setEditingClass(null)}
                                                className="px-3 py-1 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                            >
                                                Annuler
                                            </button>
                                            <button
                                                onClick={() => updateAssignment(trainingClass.id, { is_active: true })}
                                                className="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                                            >
                                                Sauvegarder
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Unassigned Classes */}
            <div>
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Classes disponibles ({unassignedClasses.length})
                    </h3>
                </div>

                {unassignedClasses.length === 0 ? (
                    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-8 text-center">
                        <CheckIcon className="w-12 h-12 text-green-500 mx-auto mb-4" />
                        <p className="text-gray-500 dark:text-gray-400">
                            Toutes les classes sont assignées à ce quiz
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4">
                        {unassignedClasses.map((trainingClass) => (
                            <div key={trainingClass.id} className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <h4 className="text-lg font-medium text-gray-900 dark:text-white mb-3">
                                            {trainingClass.name}
                                        </h4>

                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <CalendarDaysIcon className="w-4 h-4" />
                                                {formatDate(trainingClass.date)}
                                            </div>
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <ClockIcon className="w-4 h-4" />
                                                {formatTime(trainingClass.start_time)} - {formatTime(trainingClass.end_time)}
                                            </div>
                                            {trainingClass.room && (
                                                <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                    <MapPinIcon className="w-4 h-4" />
                                                    {trainingClass.room}
                                                </div>
                                            )}
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <UsersIcon className="w-4 h-4" />
                                                {trainingClass.students_count} étudiants
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        onClick={() => assignToClass(trainingClass.id)}
                                        disabled={processing}
                                        className="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-1" />
                                        Assigner
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}