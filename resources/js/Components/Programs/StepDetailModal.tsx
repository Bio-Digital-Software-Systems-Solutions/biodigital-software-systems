import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
    XMarkIcon,
    PencilIcon,
    PlusIcon,
    TrashIcon,
    UserPlusIcon,
    CheckIcon,
} from '@heroicons/react/24/outline';
import { Badge } from '@/Components/ui/badge';
import { SearchableSelect } from '@/Components/ui/searchable-select';

interface StepDetailModalProps {
    step: any;
    programId: string | number;
    onClose: () => void;
    onEdit: () => void;
    getStatusBadgeClass: (status: string) => string;
    getPriorityBadgeClass: (priority: string) => string;
    getTaskStatusBadgeClass: (status: string) => string;
    formatDateTime: (dateStr: string) => string;
    users?: any[];
    statuses?: any[];
}

export default function StepDetailModal({
    step,
    programId,
    onClose,
    onEdit,
    getStatusBadgeClass,
    getPriorityBadgeClass,
    getTaskStatusBadgeClass,
    formatDateTime,
    users = [],
    statuses = [],
}: StepDetailModalProps) {
    const [showTaskForm, setShowTaskForm] = useState(false);
    const [showParticipantForm, setShowParticipantForm] = useState(false);
    const [editingTask, setEditingTask] = useState<any>(null);

    const { data: taskData, setData: setTaskData, post: postTask, patch: patchTask, processing: taskProcessing, errors: taskErrors, reset: resetTask } = useForm({
        title: '',
        description: '',
        due_date: '',
        priority: 'medium',
        estimated_hours: '',
        assigned_to: '',
    });

    const { data: participantData, setData: setParticipantData, post: postParticipant, processing: participantProcessing, errors: participantErrors, reset: resetParticipant } = useForm({
        user_id: '',
        role_in_step: '',
    });

    const handleTaskSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingTask) {
            patchTask(route('programs.steps.tasks.update', { program: programId, step: step.id, task: editingTask.uuid }), {
                onSuccess: () => {
                    setShowTaskForm(false);
                    setEditingTask(null);
                    resetTask();
                },
            });
        } else {
            postTask(route('programs.steps.tasks.store', { program: programId, step: step.id }), {
                onSuccess: () => {
                    setShowTaskForm(false);
                    resetTask();
                },
            });
        }
    };

    const handleTaskDelete = (taskUuid: string) => {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
            router.delete(route('programs.steps.tasks.destroy', { program: programId, step: step.id, task: taskUuid }));
        }
    };

    const handleTaskEdit = (task: any) => {
        setEditingTask(task);
        setTaskData({
            title: task.title,
            description: task.description || '',
            due_date: task.due_date ? new Date(task.due_date).toISOString().slice(0, 16) : '',
            priority: task.priority,
            estimated_hours: task.estimated_hours || '',
            assigned_to: task.assigned_to || '',
        });
        setShowTaskForm(true);
    };

    const handleParticipantSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postParticipant(route('programs.steps.participants.attach', { program: programId, step: step.id }), {
            onSuccess: () => {
                setShowParticipantForm(false);
                resetParticipant();
            },
        });
    };

    const handleParticipantRemove = (userId: number) => {
        if (confirm('Êtes-vous sûr de vouloir retirer cet intervenant ?')) {
            router.delete(route('programs.steps.participants.detach', { program: programId, step: step.id, participant: userId }));
        }
    };

    const handleTaskToggle = (task: any) => {
        const completedStatus = statuses.find((s: any) => s.name === 'completed');
        const todoStatus = statuses.find((s: any) => s.name === 'todo');

        const isCompleted = task.status?.name === 'completed';
        const newStatusId = isCompleted ? todoStatus?.id : completedStatus?.id;

        if (newStatusId) {
            router.patch(route('programs.steps.tasks.update-status', {
                program: programId,
                step: step.id,
                task: task.uuid
            }), {
                status_id: newStatusId
            }, {
                preserveScroll: true
            });
        }
    };

    const handleToggleAllTasks = () => {
        const allCompleted = step.tasks?.every((task: any) => task.status?.name === 'completed');
        const completedStatus = statuses.find((s: any) => s.name === 'completed');
        const todoStatus = statuses.find((s: any) => s.name === 'todo');

        const targetStatusId = allCompleted ? todoStatus?.id : completedStatus?.id;

        if (targetStatusId && step.tasks) {
            step.tasks.forEach((task: any) => {
                if ((allCompleted && task.status?.name === 'completed') || (!allCompleted && task.status?.name !== 'completed')) {
                    router.patch(route('programs.steps.tasks.update-status', {
                        program: programId,
                        step: step.id,
                        task: task.uuid
                    }), {
                        status_id: targetStatusId
                    }, {
                        preserveScroll: true,
                        preserveState: true
                    });
                }
            });
        }
    };

    const userOptions = users.map(user => ({
        value: user.id,
        label: user.full_name || `${user.first_name} ${user.last_name}`,
    }));

    const allTasksCompleted = step.tasks?.length > 0 && step.tasks.every((task: any) => task.status?.name === 'completed');

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            {step.name}
                        </h2>
                        <Badge className={`${getStatusBadgeClass(step.status)} text-xs px-2 py-0.5 mt-2`}>
                            {step.status}
                        </Badge>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={onEdit}
                            className="px-3 py-2 text-sm font-medium text-primary dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md flex items-center gap-1"
                        >
                            <PencilIcon className="w-4 h-4" />
                            Modifier
                        </button>
                        <button
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                        >
                            <XMarkIcon className="w-6 h-6" />
                        </button>
                    </div>
                </div>

                {/* Content */}
                <div className="p-6 space-y-6">
                    {/* Description */}
                    {step.description && (
                        <div>
                            <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Description</h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400">{step.description}</p>
                        </div>
                    )}

                    {/* Details */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Début</h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400">{formatDateTime(step.start_datetime)}</p>
                        </div>
                        <div>
                            <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Fin</h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400">{formatDateTime(step.end_datetime)}</p>
                        </div>
                    </div>

                    {/* Participants */}
                    <div>
                        <div className="flex items-center justify-between mb-3">
                            <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                Intervenants ({step.users?.length || 0})
                            </h3>
                            <button
                                onClick={() => setShowParticipantForm(true)}
                                className="px-3 py-1 text-xs font-medium text-primary dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md flex items-center gap-1"
                            >
                                <UserPlusIcon className="w-4 h-4" />
                                Ajouter
                            </button>
                        </div>

                        {step.users && step.users.length > 0 ? (
                            <div className="space-y-2">
                                {step.users.map((user: any) => (
                                    <div key={user.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <div className="flex items-center gap-3">
                                            <div className="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-medium">
                                                {user.first_name?.charAt(0)?.toUpperCase()}{user.last_name?.charAt(0)?.toUpperCase()}
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {user.full_name || `${user.first_name} ${user.last_name}`}
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">{user.pivot?.role_in_step}</p>
                                            </div>
                                        </div>
                                        <button
                                            onClick={() => handleParticipantRemove(user.id)}
                                            className="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <TrashIcon className="w-4 h-4" />
                                        </button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500 dark:text-gray-400">Aucun intervenant assigné</p>
                        )}

                        {/* Participant Form */}
                        {showParticipantForm && (
                            <form onSubmit={handleParticipantSubmit} className="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg space-y-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Intervenant
                                    </label>
                                    <SearchableSelect
                                        options={userOptions}
                                        value={participantData.user_id}
                                        onChange={(value) => setParticipantData('user_id', String(value))}
                                        placeholder="Sélectionner un utilisateur..."
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Rôle
                                    </label>
                                    <input
                                        type="text"
                                        value={participantData.role_in_step}
                                        onChange={(e) => setParticipantData('role_in_step', e.target.value)}
                                        className="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        placeholder="Ex: Chef de projet, Analyste..."
                                        required
                                    />
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="submit"
                                        disabled={participantProcessing}
                                        className="px-3 py-1.5 text-xs font-medium text-white bg-primary rounded-md hover:bg-primary disabled:opacity-50"
                                    >
                                        Ajouter
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowParticipantForm(false);
                                            resetParticipant();
                                        }}
                                        className="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                                    >
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>

                    {/* Tasks */}
                    <div>
                        <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center gap-3">
                                {step.tasks && step.tasks.length > 0 && (
                                    <input
                                        type="checkbox"
                                        checked={allTasksCompleted}
                                        onChange={handleToggleAllTasks}
                                        className="w-4 h-4 text-primary rounded border-gray-300 dark:border-gray-600 focus:ring-primary cursor-pointer"
                                        title={allTasksCompleted ? "Décocher toutes les tâches" : "Cocher toutes les tâches"}
                                    />
                                )}
                                <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    Tâches ({step.tasks?.length || 0})
                                </h3>
                            </div>
                            <button
                                onClick={() => {
                                    setEditingTask(null);
                                    resetTask();
                                    setShowTaskForm(true);
                                }}
                                className="px-3 py-1 text-xs font-medium text-primary dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md flex items-center gap-1"
                            >
                                <PlusIcon className="w-4 h-4" />
                                Ajouter
                            </button>
                        </div>

                        {step.tasks && step.tasks.length > 0 ? (
                            <div className="space-y-2">
                                {step.tasks.map((task: any) => (
                                    <div key={task.id} className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <input
                                            type="checkbox"
                                            checked={task.status?.name === 'completed'}
                                            onChange={() => handleTaskToggle(task)}
                                            className="mt-0.5 w-4 h-4 text-primary rounded border-gray-300 dark:border-gray-600 focus:ring-primary cursor-pointer"
                                        />
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-1">
                                                <p className={`text-sm font-medium ${task.status?.name === 'completed' ? 'line-through text-gray-500 dark:text-gray-400' : 'text-gray-900 dark:text-gray-100'}`}>
                                                    {task.title}
                                                </p>
                                                <Badge className={`${getPriorityBadgeClass(task.priority)} text-xs px-2 py-0.5`}>
                                                    {task.priority}
                                                </Badge>
                                                <Badge className={`${getTaskStatusBadgeClass(task.status?.name || 'todo')} text-xs px-2 py-0.5`}>
                                                    {task.status?.name || 'todo'}
                                                </Badge>
                                            </div>
                                            {task.description && (
                                                <p className="text-xs text-gray-600 dark:text-gray-400 mb-1">{task.description}</p>
                                            )}
                                            {task.assigned_user && (
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    Assigné à: {task.assigned_user.full_name}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={() => handleTaskEdit(task)}
                                                className="text-primary hover:text-primary dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                <PencilIcon className="w-4 h-4" />
                                            </button>
                                            <button
                                                onClick={() => handleTaskDelete(task.uuid)}
                                                className="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                <TrashIcon className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500 dark:text-gray-400">Aucune tâche définie</p>
                        )}

                        {/* Task Form */}
                        {showTaskForm && (
                            <form onSubmit={handleTaskSubmit} className="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg space-y-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Titre <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={taskData.title}
                                        onChange={(e) => setTaskData('title', e.target.value)}
                                        className="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Description
                                    </label>
                                    <textarea
                                        value={taskData.description}
                                        onChange={(e) => setTaskData('description', e.target.value)}
                                        rows={2}
                                        className="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Priorité
                                        </label>
                                        <select
                                            value={taskData.priority}
                                            onChange={(e) => setTaskData('priority', e.target.value)}
                                            className="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        >
                                            <option value="low">Basse</option>
                                            <option value="medium">Moyenne</option>
                                            <option value="high">Haute</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Date d'échéance
                                        </label>
                                        <input
                                            type="datetime-local"
                                            value={taskData.due_date}
                                            onChange={(e) => setTaskData('due_date', e.target.value)}
                                            className="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Heures estimées
                                        </label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            value={taskData.estimated_hours}
                                            onChange={(e) => setTaskData('estimated_hours', e.target.value)}
                                            className="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Assigner à
                                        </label>
                                        <SearchableSelect
                                            options={[
                                                { value: '', label: 'Non assigné' },
                                                ...userOptions
                                            ]}
                                            value={taskData.assigned_to}
                                            onChange={(value) => setTaskData('assigned_to', String(value))}
                                            placeholder="Sélectionner un utilisateur..."
                                        />
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="submit"
                                        disabled={taskProcessing}
                                        className="px-3 py-1.5 text-xs font-medium text-white bg-primary rounded-md hover:bg-primary disabled:opacity-50"
                                    >
                                        {editingTask ? 'Modifier' : 'Ajouter'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowTaskForm(false);
                                            setEditingTask(null);
                                            resetTask();
                                        }}
                                        className="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                                    >
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
