import React, { useState, useCallback, useMemo } from 'react';
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
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/react/24/outline';
import { CheckCircleIcon as CheckCircleSolidIcon } from '@heroicons/react/24/solid';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { TodoEditableCell } from '@/Components/Scheduling/TodoEditableCell';
import type { DepartmentTodo, DepartmentMember } from '@/Types/scheduling';

export type TodoViewMode = 'list' | 'table' | 'grid';

type SortField = 'title' | 'priority' | 'status' | 'assignee' | 'due_date' | 'shift';
type SortDirection = 'asc' | 'desc';

interface SortConfig {
    field: SortField;
    direction: SortDirection;
}

const PRIORITY_ORDER: Record<string, number> = { urgent: 0, high: 1, medium: 2, low: 3 };
const STATUS_ORDER: Record<string, number> = { blocked: 0, in_progress: 1, todo: 2, completed: 3, cancelled: 4 };

interface TodoListProps {
    todos: DepartmentTodo[];
    departmentUuid: string;
    members?: DepartmentMember[];
    compact?: boolean;
    showShiftInfo?: boolean;
    onEdit?: (todo: DepartmentTodo) => void;
    viewMode?: TodoViewMode;
}

export default function TodoList({
    todos,
    departmentUuid,
    members,
    compact = false,
    showShiftInfo = true,
    onEdit,
    viewMode = 'list',
}: TodoListProps) {
    const [deletingTodo, setDeletingTodo] = useState<DepartmentTodo | null>(null);
    const [sort, setSort] = useState<SortConfig | null>(null);

    const toggleSort = (field: SortField) => {
        setSort((prev) => {
            if (prev?.field !== field) return { field, direction: 'asc' };
            if (prev.direction === 'asc') return { field, direction: 'desc' };
            return null;
        });
    };

    const sortedTodos = useMemo(() => {
        if (!sort) return todos;

        return [...todos].sort((a, b) => {
            const dir = sort.direction === 'asc' ? 1 : -1;

            switch (sort.field) {
                case 'title':
                    return dir * a.title.localeCompare(b.title);
                case 'priority':
                    return dir * ((PRIORITY_ORDER[a.priority] ?? 99) - (PRIORITY_ORDER[b.priority] ?? 99));
                case 'status':
                    return dir * ((STATUS_ORDER[a.status] ?? 99) - (STATUS_ORDER[b.status] ?? 99));
                case 'assignee': {
                    const nameA = a.assignee?.name ?? '';
                    const nameB = b.assignee?.name ?? '';
                    if (!nameA && !nameB) return 0;
                    if (!nameA) return 1;
                    if (!nameB) return -1;
                    return dir * nameA.localeCompare(nameB);
                }
                case 'due_date': {
                    const dateA = a.due_date ?? '';
                    const dateB = b.due_date ?? '';
                    if (!dateA && !dateB) return 0;
                    if (!dateA) return 1;
                    if (!dateB) return -1;
                    return dir * dateA.localeCompare(dateB);
                }
                case 'shift': {
                    const shiftA = a.shift?.time_range ?? '';
                    const shiftB = b.shift?.time_range ?? '';
                    if (!shiftA && !shiftB) return 0;
                    if (!shiftA) return 1;
                    if (!shiftB) return -1;
                    return dir * shiftA.localeCompare(shiftB);
                }
                default:
                    return 0;
            }
        });
    }, [todos, sort]);

    const renderSortIcon = (field: SortField) => {
        if (sort?.field !== field) {
            return <ChevronUpIcon className="h-3.5 w-3.5 opacity-0 group-hover:opacity-40 transition-opacity" />;
        }
        return sort.direction === 'asc'
            ? <ChevronUpIcon className="h-3.5 w-3.5 text-icc-blue dark:text-blue-400" />
            : <ChevronDownIcon className="h-3.5 w-3.5 text-icc-blue dark:text-blue-400" />;
    };

    const SortableHeader = ({ field, children, className }: { field: SortField; children: React.ReactNode; className?: string }) => (
        <TableHead
            className={cn('cursor-pointer select-none group', className)}
            onClick={() => toggleSort(field)}
        >
            <span className="inline-flex items-center gap-1">
                {children}
                {renderSortIcon(field)}
            </span>
        </TableHead>
    );

    const handleTodoInlineUpdate = useCallback((_updatedTodo: DepartmentTodo) => {
        router.reload({ only: ['todos', 'todoStats', 'shiftTodos'] });
    }, []);

    const priorityOptions = [
        { value: 'low', label: 'Basse' },
        { value: 'medium', label: 'Moyenne' },
        { value: 'high', label: 'Haute' },
        { value: 'urgent', label: 'Urgente' },
    ];

    const statusOptions = [
        { value: 'todo', label: 'À faire' },
        { value: 'in_progress', label: 'En cours' },
        { value: 'completed', label: 'Terminée' },
        { value: 'blocked', label: 'Bloquée' },
        { value: 'cancelled', label: 'Annulée' },
    ];

    const memberOptions = (members ?? []).map((m) => ({ value: m.uuid, label: m.name }));

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

    const formatDueDate = (date: string) =>
        new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });

    const renderCheckbox = (todo: DepartmentTodo) => (
        <button
            onClick={() => handleToggleComplete(todo)}
            className={cn(
                'flex-shrink-0 transition-colors',
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
    );

    const renderActions = (todo: DepartmentTodo) => (
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
    );

    if (todos.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <CheckCircleIcon className="h-12 w-12 mx-auto mb-3 opacity-50" />
                <p>Aucune tache</p>
            </div>
        );
    }

    const renderListView = () => (
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
                    {renderCheckbox(todo)}
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                            <span className={cn('font-medium', todo.status === 'completed' && 'line-through text-gray-500')}>
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
                                <span className={cn('flex items-center gap-1', todo.is_overdue && 'text-red-600 dark:text-red-400 font-medium')}>
                                    <CalendarIcon className="h-3.5 w-3.5" />
                                    {formatDueDate(todo.due_date)}
                                    {todo.is_overdue && <ExclamationTriangleIcon className="h-3.5 w-3.5" />}
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
                    {renderActions(todo)}
                </div>
            ))}
        </div>
    );

    const renderTableView = () => (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead className="w-10"></TableHead>
                    <SortableHeader field="title">Tache</SortableHeader>
                    <SortableHeader field="priority">Priorite</SortableHeader>
                    <SortableHeader field="status">Statut</SortableHeader>
                    <SortableHeader field="assignee">Assignee</SortableHeader>
                    <TableHead>Backup</TableHead>
                    <SortableHeader field="due_date">Echeance</SortableHeader>
                    {showShiftInfo && <SortableHeader field="shift">Shift</SortableHeader>}
                    <TableHead className="w-10"></TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {sortedTodos.map((todo) => (
                    <TableRow
                        key={todo.uuid}
                        className={cn(
                            todo.status === 'completed' && 'opacity-60',
                            todo.is_overdue && todo.status !== 'completed' && 'bg-red-50 dark:bg-red-900/10'
                        )}
                    >
                        <TableCell>{renderCheckbox(todo)}</TableCell>
                        <TableCell>
                            <TodoEditableCell
                                value={todo.title}
                                field="title"
                                type="text"
                                todo={todo}
                                departmentUuid={departmentUuid}
                                onUpdate={handleTodoInlineUpdate}
                                renderDisplay={(val) => (
                                    <div>
                                        <span className={cn('font-medium', todo.status === 'completed' && 'line-through text-gray-500')}>
                                            {val}
                                        </span>
                                        {!compact && todo.description && (
                                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">
                                                {todo.description}
                                            </p>
                                        )}
                                    </div>
                                )}
                            />
                        </TableCell>
                        <TableCell>
                            <TodoEditableCell
                                value={todo.priority}
                                field="priority"
                                todo={todo}
                                departmentUuid={departmentUuid}
                                onUpdate={handleTodoInlineUpdate}
                                type="select"
                                options={priorityOptions}
                                renderDisplay={(val) => getPriorityBadge(val, priorityOptions.find((o) => o.value === val)?.label ?? val)}
                            />
                        </TableCell>
                        <TableCell>
                            <TodoEditableCell
                                value={todo.status}
                                field="status"
                                todo={todo}
                                departmentUuid={departmentUuid}
                                onUpdate={handleTodoInlineUpdate}
                                type="select"
                                options={statusOptions}
                                renderDisplay={(val) => getStatusBadge(val, statusOptions.find((o) => o.value === val)?.label ?? val)}
                            />
                        </TableCell>
                        <TableCell>
                            <TodoEditableCell
                                value={todo.assignee?.uuid ?? ''}
                                field="assigned_to"
                                todo={todo}
                                departmentUuid={departmentUuid}
                                onUpdate={handleTodoInlineUpdate}
                                type="searchable-select"
                                options={memberOptions}
                                renderDisplay={() =>
                                    todo.assignee ? (
                                        <span className="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                                            <UserIcon className="h-3.5 w-3.5" />
                                            {todo.assignee.name}
                                        </span>
                                    ) : (
                                        <span className="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    )
                                }
                            />
                        </TableCell>
                        <TableCell>
                            <TodoEditableCell
                                field="backup_assignees"
                                todo={todo}
                                departmentUuid={departmentUuid}
                                onUpdate={handleTodoInlineUpdate}
                                type="searchable-multi-select"
                                multiValue={todo.backup_assignees.map((b) => b.uuid)}
                                options={memberOptions}
                                renderDisplay={() =>
                                    todo.backup_assignees.length > 0 ? (
                                        <div className="flex flex-col gap-0.5">
                                            {todo.backup_assignees.map((backup) => (
                                                <span key={backup.uuid} className="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                                                    <UserIcon className="h-3.5 w-3.5" />
                                                    {backup.name}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        <span className="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    )
                                }
                            />
                        </TableCell>
                        <TableCell>
                            <TodoEditableCell
                                value={todo.due_date ?? ''}
                                field="due_date"
                                todo={todo}
                                departmentUuid={departmentUuid}
                                onUpdate={handleTodoInlineUpdate}
                                type="date"
                                renderDisplay={(val) =>
                                    val ? (
                                        <span className={cn(
                                            'flex items-center gap-1 text-sm',
                                            todo.is_overdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-400'
                                        )}>
                                            {formatDueDate(val)}
                                            {todo.is_overdue && <ExclamationTriangleIcon className="h-3.5 w-3.5" />}
                                        </span>
                                    ) : (
                                        <span className="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    )
                                }
                            />
                        </TableCell>
                        {showShiftInfo && (
                            <TableCell>
                                {todo.shift && (
                                    <span className="text-sm text-blue-600 dark:text-blue-400">
                                        {todo.shift.time_range}
                                    </span>
                                )}
                            </TableCell>
                        )}
                        <TableCell>{renderActions(todo)}</TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );

    const renderGridView = () => (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {todos.map((todo) => (
                <div
                    key={todo.uuid}
                    className={cn(
                        'flex flex-col p-4 rounded-lg border transition-colors',
                        todo.status === 'completed'
                            ? 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700'
                            : todo.is_overdue
                                ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                    )}
                >
                    <div className="flex items-start justify-between gap-2">
                        <div className="flex items-start gap-2 min-w-0">
                            {renderCheckbox(todo)}
                            <span className={cn('font-medium text-sm', todo.status === 'completed' && 'line-through text-gray-500')}>
                                {todo.title}
                            </span>
                        </div>
                        {renderActions(todo)}
                    </div>

                    {!compact && todo.description && (
                        <p className="text-xs text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                            {todo.description}
                        </p>
                    )}

                    <div className="flex items-center gap-2 mt-3 flex-wrap">
                        {getPriorityBadge(todo.priority, todo.priority_label)}
                        {todo.status !== 'todo' && getStatusBadge(todo.status, todo.status_label)}
                    </div>

                    <div className="flex items-center gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                        {todo.assignee && (
                            <span className="flex items-center gap-1">
                                <UserIcon className="h-3.5 w-3.5" />
                                {todo.assignee.name}
                            </span>
                        )}
                        {todo.due_date && (
                            <span className={cn('flex items-center gap-1', todo.is_overdue && 'text-red-600 dark:text-red-400 font-medium')}>
                                <CalendarIcon className="h-3.5 w-3.5" />
                                {formatDueDate(todo.due_date)}
                            </span>
                        )}
                        {todo.estimated_minutes && (
                            <span className="flex items-center gap-1">
                                <ClockIcon className="h-3.5 w-3.5" />
                                {todo.estimated_minutes}min
                            </span>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );

    return (
        <>
            {viewMode === 'table' && renderTableView()}
            {viewMode === 'grid' && renderGridView()}
            {viewMode === 'list' && renderListView()}

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
