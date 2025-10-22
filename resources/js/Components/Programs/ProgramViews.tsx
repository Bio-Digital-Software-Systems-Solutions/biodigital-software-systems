import { CheckIcon, CalendarIcon, ClockIcon, UserIcon, PlusIcon, EllipsisVerticalIcon, ArrowLeftIcon, ArrowRightIcon } from '@heroicons/react/24/outline';
import { Badge } from '@/Components/ui/badge';
import { router } from '@inertiajs/react';
import { DndContext, DragEndEvent, DragOverlay, closestCorners, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { useDraggable, useDroppable } from '@dnd-kit/core';
import { useState } from 'react';

interface ViewProps {
    steps: any[];
    getStatusBadgeClass: (status: string) => string;
    getPriorityBadgeClass: (priority: string) => string;
    getTaskStatusBadgeClass: (status: string) => string;
    formatDateTime: (date: string) => string;
    onStepClick?: (step: any) => void;
    statuses?: any[];
    programId?: string | number;
}

export const TimelineView = ({ steps, getStatusBadgeClass, getPriorityBadgeClass, getTaskStatusBadgeClass, formatDateTime, onStepClick, statuses = [], programId }: ViewProps) => {
    const handleTaskToggle = (e: React.MouseEvent | React.ChangeEvent, task: any, stepId: number) => {
        e.stopPropagation(); // Empêche l'ouverture du modal

        const completedStatus = statuses.find((s: any) => s.name === 'completed');
        const todoStatus = statuses.find((s: any) => s.name === 'todo');

        const isCompleted = task.status?.name === 'completed';
        const newStatusId = isCompleted ? todoStatus?.id : completedStatus?.id;

        if (newStatusId && programId) {
            router.patch(route('programs.steps.tasks.update-status', {
                program: programId,
                step: stepId,
                task: task.uuid
            }), {
                status_id: newStatusId
            }, {
                preserveScroll: true
            });
        }
    };

    return (
    <div className="space-y-6">
        {steps
            .sort((a: any, b: any) => a.order_index - b.order_index)
            .map((step: any, index: number) => (
                <div key={step.id} className="relative">
                    {/* Timeline connector */}
                    {index < steps.length - 1 && (
                        <div className="absolute left-3 top-8 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700 -mb-6"></div>
                    )}

                    <div className="flex gap-4">
                        {/* Timeline dot */}
                        <div className="relative flex-shrink-0">
                            <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center ${
                                step.status === 'completed'
                                    ? 'bg-green-500 border-green-500'
                                    : step.status === 'in_progress'
                                    ? 'bg-primary border-primary'
                                    : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600'
                            }`}>
                                {step.status === 'completed' && (
                                    <CheckIcon className="w-4 h-4 text-white" />
                                )}
                                {step.status === 'in_progress' && (
                                    <div className="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                                )}
                            </div>
                        </div>

                        {/* Step Content */}
                        <div className="flex-1 pb-6">
                            <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                {/* Step Header */}
                                <div className="flex items-start justify-between mb-3">
                                    <div
                                        onClick={() => onStepClick?.(step)}
                                        className="flex items-center gap-2 cursor-pointer flex-1"
                                    >
                                        <span className="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 text-primary dark:text-blue-400 text-xs font-bold">
                                            {step.order_index}
                                        </span>
                                        <h4 className="font-semibold text-gray-900 dark:text-gray-100">
                                            {step.name}
                                        </h4>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge className={`text-xs px-2 py-1 ${getStatusBadgeClass(step.status)}`}>
                                            {step.status}
                                        </Badge>
                                        <button
                                            onClick={() => onStepClick?.(step)}
                                            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        >
                                            <EllipsisVerticalIcon className="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>

                                {/* Step Description */}
                                {step.description && (
                                    <p
                                        onClick={() => onStepClick?.(step)}
                                        className="text-sm text-gray-600 dark:text-gray-400 mb-3 ml-8 cursor-pointer"
                                    >
                                        {step.description}
                                    </p>
                                )}

                                {/* Step Meta Info */}
                                <div
                                    onClick={() => onStepClick?.(step)}
                                    className="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 ml-8 mb-3 cursor-pointer"
                                >
                                    <div className="flex items-center gap-1">
                                        <CalendarIcon className="w-4 h-4" />
                                        <span>{formatDateTime(step.start_datetime)}</span>
                                        <span>→</span>
                                        <span>{formatDateTime(step.end_datetime)}</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <ClockIcon className="w-4 h-4" />
                                        <span>{Math.floor(step.duration_minutes / 60)}h {step.duration_minutes % 60}min</span>
                                    </div>
                                </div>

                                {/* Participants */}
                                {step.users && step.users.length > 0 && (
                                    <div
                                        onClick={() => onStepClick?.(step)}
                                        className="ml-8 mb-3 cursor-pointer"
                                    >
                                        <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mb-2">
                                            <UserIcon className="w-4 h-4" />
                                            <span>Participants: ({step.users.length})</span>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {step.users.map((user: any) => (
                                                <div
                                                    key={user.id}
                                                    className="inline-flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-1.5 text-xs"
                                                >
                                                    <div className="w-6 h-6 bg-primary rounded-full flex items-center justify-center text-white font-semibold text-xs">
                                                        {user.first_name?.charAt(0)?.toUpperCase()}{user.last_name?.charAt(0)?.toUpperCase()}
                                                    </div>
                                                    <div className="text-left">
                                                        <div className="font-medium text-gray-900 dark:text-gray-100">
                                                            {user.full_name || `${user.first_name} ${user.last_name}`}
                                                        </div>
                                                        <div className="text-gray-500 dark:text-gray-400">
                                                            {user.pivot?.role_in_step}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Tasks */}
                                {step.tasks && step.tasks.length > 0 && (
                                    <div className="ml-8">
                                        <div className="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-2">
                                            <span>Tâches ({step.tasks.length})</span>
                                        </div>
                                        <div className="space-y-2">
                                            {step.tasks.map((task: any) => (
                                                <div
                                                    key={task.id}
                                                    className="flex items-center gap-2"
                                                >
                                                    {/* Checkbox - Non cliquable pour le modal */}
                                                    <input
                                                        type="checkbox"
                                                        checked={task.status?.name === 'completed'}
                                                        onChange={(e) => handleTaskToggle(e, task, step.id)}
                                                        className="w-4 h-4 text-primary rounded border-gray-300 cursor-pointer flex-shrink-0"
                                                    />

                                                    {/* Zone cliquable pour ouvrir le modal */}
                                                    <div
                                                        onClick={() => onStepClick?.(step)}
                                                        className="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2 flex-1 cursor-pointer"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <div className="text-sm">
                                                                <div className={`font-medium ${task.status?.name === 'completed' ? 'line-through text-gray-500' : 'text-gray-900 dark:text-gray-100'}`}>
                                                                    {task.name || task.title}
                                                                </div>
                                                                {task.assigned_user && (
                                                                    <div className="text-xs text-gray-500 dark:text-gray-400">
                                                                        Assigné à {task.assigned_user.full_name}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            {task.priority && (
                                                                <Badge className={`text-xs px-2 py-0.5 ${getPriorityBadgeClass(task.priority)}`}>
                                                                    {task.priority}
                                                                </Badge>
                                                            )}
                                                            <Badge className={`text-xs px-2 py-0.5 ${getTaskStatusBadgeClass(task.status?.name || 'todo')}`}>
                                                                {task.status?.name || 'todo'}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                        <button
                                            onClick={() => onStepClick?.(step)}
                                            className="mt-2 text-xs text-primary hover:text-primary dark:text-blue-400 flex items-center gap-1"
                                        >
                                            <PlusIcon className="w-3 h-3" />
                                            Ajouter une tâche
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            ))}
    </div>
);
};

export const ListView = ({ steps, getStatusBadgeClass, formatDateTime, onStepClick }: ViewProps) => (
    <div className="space-y-3">
        {steps
            .sort((a: any, b: any) => a.order_index - b.order_index)
            .map((step: any) => (
                <div
                    key={step.id}
                    onClick={() => onStepClick?.(step)}
                    className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3 flex-1">
                            <span className="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-primary dark:text-blue-400 text-sm font-bold">
                                {step.order_index}
                            </span>
                            <div className="flex-1">
                                <h4 className="font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                    {step.name}
                                </h4>
                                {step.description && (
                                    <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-1">
                                        {step.description}
                                    </p>
                                )}
                                <div className="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <div className="flex items-center gap-1">
                                        <CalendarIcon className="w-3 h-3" />
                                        {formatDateTime(step.start_datetime)} → {formatDateTime(step.end_datetime)}
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <ClockIcon className="w-3 h-3" />
                                        {Math.floor(step.duration_minutes / 60)}h {step.duration_minutes % 60}min
                                    </div>
                                    {step.tasks && step.tasks.length > 0 && (
                                        <span>{step.tasks.length} tâches</span>
                                    )}
                                    {step.users && step.users.length > 0 && (
                                        <span>{step.users.length} participants</span>
                                    )}
                                </div>
                            </div>
                        </div>
                        <Badge className={`text-xs px-3 py-1 ${getStatusBadgeClass(step.status)}`}>
                            {step.status}
                        </Badge>
                    </div>
                </div>
            ))}
    </div>
);

// Draggable Task Card Component
const DraggableTaskCard = ({ task, getPriorityBadgeClass, getTaskStatusBadgeClass, getStatusBadgeClass }: any) => {
    const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
        id: `task-${task.stepId}-${task.id}`,
        data: { task }
    });

    const style = transform ? {
        transform: `translate3d(${transform.x}px, ${transform.y}px, 0)`,
        opacity: isDragging ? 0.5 : 1,
    } : undefined;

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...listeners}
            {...attributes}
            className="bg-white dark:bg-gray-800 border-l-4 border-l-blue-500 rounded-lg p-4 hover:shadow-lg transition-all cursor-move"
        >
            <h4 className="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-3">
                {task.name || task.title}
            </h4>

            <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3">
                <div className="w-2 h-2 bg-primary rounded-full"></div>
                <span>{task.stepName}</span>
            </div>

            {task.assigned_user && (
                <div className="flex items-center gap-2 mb-3">
                    <div className="w-7 h-7 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                        {task.assigned_user.full_name ? task.assigned_user.full_name.split(' ').map((n: string) => n[0]).join('') : '?'}
                    </div>
                    <span className="text-xs text-gray-600 dark:text-gray-400 font-medium">
                        {task.assigned_user.full_name || `${task.assigned_user.first_name} ${task.assigned_user.last_name}`}
                    </span>
                </div>
            )}

            <div className="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div className="flex items-center gap-2">
                    {task.priority && (
                        <Badge className={`text-xs px-2.5 py-0.5 ${getPriorityBadgeClass(task.priority)}`}>
                            {task.priority}
                        </Badge>
                    )}
                    <Badge className={`text-xs px-2.5 py-0.5 ${getTaskStatusBadgeClass(task.status?.name || 'todo')}`}>
                        {task.status?.name || 'todo'}
                    </Badge>
                </div>
                <Badge className={`text-xs px-2.5 py-0.5 ${getStatusBadgeClass(task.stepStatus)}`}>
                    {task.stepStatus}
                </Badge>
            </div>
        </div>
    );
};

// Droppable Column Component
const DroppableColumn = ({ column, children }: any) => {
    const { setNodeRef, isOver } = useDroppable({
        id: column.id,
        data: { column }
    });

    return (
        <div
            ref={setNodeRef}
            className={`bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 transition-all ${
                isOver ? 'ring-2 ring-primary bg-blue-50 dark:bg-blue-900/20' : ''
            }`}
        >
            <div className="flex items-center justify-between mb-4">
                <h3 className="font-bold text-base text-gray-900 dark:text-gray-100">{column.title}</h3>
                <Badge className="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold">
                    {column.count}
                </Badge>
            </div>
            <div className="space-y-3 min-h-[200px]">
                {children}
            </div>
        </div>
    );
};

export const KanbanView = ({ steps, getStatusBadgeClass, getTaskStatusBadgeClass, getPriorityBadgeClass, statuses = [], programId }: ViewProps) => {
    const [activeId, setActiveId] = useState<string | null>(null);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        })
    );

    // Collect all tasks from all steps
    const allTasks = steps.flatMap(step =>
        (step.tasks || []).map((task: any) => ({
            ...task,
            stepName: step.name,
            stepId: step.id,
            stepStatus: step.status
        }))
    );

    const columns = [
        {
            id: 'todo',
            title: 'À faire',
            tasks: allTasks.filter((t: any) => !t.status?.name || t.status?.name === 'pending' || t.status?.name === 'todo'),
            count: allTasks.filter((t: any) => !t.status?.name || t.status?.name === 'pending' || t.status?.name === 'todo').length
        },
        {
            id: 'in_progress',
            title: 'En cours',
            tasks: allTasks.filter((t: any) => t.status?.name === 'in_progress' || t.status?.name === 'en_cours'),
            count: allTasks.filter((t: any) => t.status?.name === 'in_progress' || t.status?.name === 'en_cours').length
        },
        {
            id: 'completed',
            title: 'Terminé',
            tasks: allTasks.filter((t: any) => t.status?.name === 'completed' || t.status?.name === 'terminé'),
            count: allTasks.filter((t: any) => t.status?.name === 'completed' || t.status?.name === 'terminé').length
        }
    ];

    const handleDragStart = (event: DragEndEvent) => {
        setActiveId(event.active.id as string);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        setActiveId(null);

        if (!over) return;

        const taskData = active.data.current?.task;
        const targetColumnId = over.id as string;

        if (!taskData || !programId) return;

        // Map column ID to status name
        const statusMap: { [key: string]: string } = {
            'todo': 'todo',
            'in_progress': 'in_progress',
            'completed': 'completed'
        };

        const newStatusName = statusMap[targetColumnId];
        const currentStatusName = taskData.status?.name;

        if (newStatusName && newStatusName !== currentStatusName) {
            const newStatus = statuses.find((s: any) => s.name === newStatusName);

            if (newStatus && programId) {
                router.patch(route('programs.steps.tasks.update-status', {
                    program: programId,
                    step: taskData.stepId,
                    task: taskData.uuid
                }), {
                    status_id: newStatus.id
                }, {
                    preserveScroll: true
                });
            }
        }
    };

    const activeTask = activeId
        ? allTasks.find(t => `task-${t.stepId}-${t.id}` === activeId)
        : null;

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCorners}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
        >
            <div className="grid grid-cols-3 gap-5">
                {columns.map(column => (
                    <DroppableColumn key={column.id} column={column}>
                        {column.tasks.map((task: any) => (
                            <DraggableTaskCard
                                key={`${task.stepId}-${task.id}`}
                                task={task}
                                getPriorityBadgeClass={getPriorityBadgeClass}
                                getTaskStatusBadgeClass={getTaskStatusBadgeClass}
                                getStatusBadgeClass={getStatusBadgeClass}
                            />
                        ))}
                        {column.tasks.length === 0 && (
                            <div className="flex flex-col items-center justify-center py-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                                <PlusIcon className="w-8 h-8 text-gray-400 dark:text-gray-500 mb-2" />
                                <p className="text-sm text-gray-500 dark:text-gray-400">Ajouter une tâche</p>
                            </div>
                        )}
                    </DroppableColumn>
                ))}
            </div>

            <DragOverlay>
                {activeTask ? (
                    <div className="bg-white dark:bg-gray-800 border-l-4 border-l-blue-500 rounded-lg p-4 shadow-xl rotate-3">
                        <h4 className="font-semibold text-sm text-gray-900 dark:text-gray-100">
                            {activeTask.name || activeTask.title}
                        </h4>
                    </div>
                ) : null}
            </DragOverlay>
        </DndContext>
    );
};
