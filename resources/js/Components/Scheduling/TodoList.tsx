import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import {
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    UserIcon,
    CalendarIcon,
    TrashIcon,
    PlayIcon,
    PauseIcon,
    PencilIcon,
} from '@heroicons/react/24/outline';
import { CheckCircleIcon as CheckCircleSolidIcon } from '@heroicons/react/24/solid';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import type { DepartmentTodo, DepartmentMember } from '@/Types/scheduling';

interface TodoListProps {
    todos: DepartmentTodo[];
    departmentUuid: string;
    members?: DepartmentMember[];
    compact?: boolean;
    showShiftInfo?: boolean;
    onEdit?: (todo: DepartmentTodo) => void;
}

export default function TodoList({
    todos,
    departmentUuid,
    members,
    compact = false,
    showShiftInfo = true,
    onEdit,
}: TodoListProps) {
    const [deletingTodo, setDeletingTodo] = useState<DepartmentTodo | null>(null);

    const handleToggleComplete = async (todo: DepartmentTodo) => {
        try {
            const response = await fetch(`/departments/${departmentUuid}/todos/${todo.uuid}/toggle-complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const result = await response.json();

            if (response.ok && result.success) {
                toast.success(result.message);
                router.reload({ only: ['todos', 'todoStats', 'shiftTodos'] });
            } else {
                toast.error(result.message || 'Erreur');
            }
        } catch (error) {
            console.error('Toggle complete error:', error);
            toast.error('Erreur lors de la mise a jour');
        }
    };

    const handleUpdateStatus = async (todo: DepartmentTodo, status: string) => {
        try {
            const response = await fetch(`/departments/${departmentUuid}/todos/${todo.uuid}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ status }),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                toast.success(result.message);
                router.reload({ only: ['todos', 'todoStats', 'shiftTodos'] });
            } else {
                toast.error(result.message || 'Erreur');
            }
        } catch (error) {
            console.error('Update status error:', error);
            toast.error('Erreur lors de la mise a jour');
        }
    };

    const handleDelete = async () => {
        if (!deletingTodo) return;

        try {
            const response = await fetch(`/departments/${departmentUuid}/todos/${deletingTodo.uuid}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const result = await response.json();

            if (response.ok && result.success) {
                toast.success(result.message);
                setDeletingTodo(null);
                router.reload({ only: ['todos', 'todoStats', 'shiftTodos'] });
            } else {
                toast.error(result.message || 'Erreur');
            }
        } catch (error) {
            console.error('Delete error:', error);
            toast.error('Erreur lors de la suppression');
        }
    };

    const getPriorityBadge = (priority: string, label: string) => {
        const colors: Record<string, string> = {
            urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
            medium: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            low: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
        return (
            <Badge className={cn('text-xs', colors[priority] || colors.medium)}>
                {label}
            </Badge>
        );
    };

    const getStatusBadge = (status: string, label: string) => {
        const colors: Record<string, string> = {
            todo: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            blocked: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            cancelled: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
        };
        return (
            <Badge className={cn('text-xs', colors[status] || colors.todo)}>
                {label}
            </Badge>
        );
    };

    if (todos.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <CheckCircleIcon className="h-12 w-12 mx-auto mb-3 opacity-50" />
                <p>Aucune tache</p>
            </div>
        );
    }

    return (
        <>
            <div className="space-y-2">
                {todos.map((todo) => (
                    <div
                        key={todo.uuid}
                        className={cn(
                            'flex items-start gap-3 p-3 rounded-lg border transition-colors',
                            todo.status === 'completed'
                                ? 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700'
                                : todo.is_overdue
                                    ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                        )}
                    >
                        {/* Checkbox */}
                        <button
                            onClick={() => handleToggleComplete(todo)}
                            className={cn(
                                'flex-shrink-0 mt-0.5 transition-colors',
                                todo.status === 'completed'
                                    ? 'text-green-500'
                                    : 'text-gray-400 hover:text-green-500'
                            )}
                        >
                            {todo.status === 'completed' ? (
                                <CheckCircleSolidIcon className="h-5 w-5" />
                            ) : (
                                <CheckCircleIcon className="h-5 w-5" />
                            )}
                        </button>

                        {/* Content */}
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 flex-wrap">
                                <span
                                    className={cn(
                                        'font-medium',
                                        todo.status === 'completed' && 'line-through text-gray-500'
                                    )}
                                >
                                    {todo.title}
                                </span>
                                {getPriorityBadge(todo.priority, todo.priority_label)}
                                {todo.status !== 'todo' && todo.status !== 'completed' && getStatusBadge(todo.status, todo.status_label)}
                            </div>

                            {!compact && todo.description && (
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                    {todo.description}
                                </p>
                            )}

                            <div className="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {todo.assignee && (
                                    <span className="flex items-center gap-1">
                                        <UserIcon className="h-3.5 w-3.5" />
                                        {todo.assignee.name}
                                    </span>
                                )}
                                {todo.due_date && (
                                    <span className={cn(
                                        'flex items-center gap-1',
                                        todo.is_overdue && 'text-red-600 dark:text-red-400 font-medium'
                                    )}>
                                        <CalendarIcon className="h-3.5 w-3.5" />
                                        {new Date(todo.due_date).toLocaleDateString('fr-FR', {
                                            day: 'numeric',
                                            month: 'short',
                                        })}
                                        {todo.is_overdue && (
                                            <ExclamationTriangleIcon className="h-3.5 w-3.5" />
                                        )}
                                    </span>
                                )}
                                {todo.estimated_minutes && (
                                    <span className="flex items-center gap-1">
                                        <ClockIcon className="h-3.5 w-3.5" />
                                        {todo.estimated_minutes}min
                                    </span>
                                )}
                                {showShiftInfo && todo.shift && (
                                    <span className="text-blue-600 dark:text-blue-400">
                                        Shift: {todo.shift.time_range}
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Actions */}
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                    <span className="sr-only">Actions</span>
                                    <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {onEdit && (
                                    <>
                                        <DropdownMenuItem onClick={() => onEdit(todo)}>
                                            <PencilIcon className="h-4 w-4 mr-2" />
                                            Modifier
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                    </>
                                )}
                                {todo.status !== 'completed' && (
                                    <>
                                        {todo.status === 'todo' && (
                                            <DropdownMenuItem onClick={() => handleUpdateStatus(todo, 'in_progress')}>
                                                <PlayIcon className="h-4 w-4 mr-2" />
                                                Demarrer
                                            </DropdownMenuItem>
                                        )}
                                        {todo.status === 'in_progress' && (
                                            <DropdownMenuItem onClick={() => handleUpdateStatus(todo, 'todo')}>
                                                <PauseIcon className="h-4 w-4 mr-2" />
                                                Mettre en pause
                                            </DropdownMenuItem>
                                        )}
                                        <DropdownMenuItem onClick={() => handleToggleComplete(todo)}>
                                            <CheckCircleIcon className="h-4 w-4 mr-2" />
                                            Marquer complete
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                    </>
                                )}
                                {todo.status === 'completed' && (
                                    <>
                                        <DropdownMenuItem onClick={() => handleToggleComplete(todo)}>
                                            <PlayIcon className="h-4 w-4 mr-2" />
                                            Reouvrir
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                    </>
                                )}
                                <DropdownMenuItem
                                    className="text-red-600 dark:text-red-400"
                                    onClick={() => setDeletingTodo(todo)}
                                >
                                    <TrashIcon className="h-4 w-4 mr-2" />
                                    Supprimer
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                ))}
            </div>

            <DeleteConfirmationDialog
                open={!!deletingTodo}
                onOpenChange={(open) => !open && setDeletingTodo(null)}
                onConfirm={handleDelete}
                title="Supprimer la tache"
                description={`Voulez-vous vraiment supprimer la tache "${deletingTodo?.title}" ?`}
                confirmText="Supprimer"
                cancelText="Annuler"
            />
        </>
    );
}
