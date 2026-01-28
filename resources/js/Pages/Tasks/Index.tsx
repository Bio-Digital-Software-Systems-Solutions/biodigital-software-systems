import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PlusIcon, FunnelIcon, EyeIcon, PencilIcon, TrashIcon, Squares2X2Icon, ListBulletIcon, TableCellsIcon, ArrowLeftIcon, ChevronUpIcon, ChevronDownIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { Task, Program, Status, User, PageProps } from '@/Types';
import { Button } from '@/Components/ui/button';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import ProjectStatisticsAnalytical, { ProjectAnalyticsData } from '@/Components/Project/ProjectStatisticsAnalytical';

interface Props extends PageProps {
    tasks: {
        data: Task[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    programs: Program[];
    statuses: Status[];
    users: User[];
    filters: {
        [key: string]: string | undefined;
        status?: string;
        program?: string;
        priority?: string;
        assigned_to?: string;
        sort_by?: string;
        sort_direction?: string;
    };
    taskStatistics?: ProjectAnalyticsData;
}

type ViewMode = 'table' | 'list' | 'grid';

export default function Index({ tasks, programs, statuses, users, filters, taskStatistics }: Props) {
    const [showFilters, setShowFilters] = useState(false);
    const [viewMode, setViewMode] = useState<ViewMode>('table');
    const [activeTab, setActiveTab] = useState<'tasks' | 'statistics'>('tasks');
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [taskToDelete, setTaskToDelete] = useState<Task | null>(null);

    const handleFilter = (key: string, value: string) => {
        const newFilters = { ...filters, [key]: value };
        if (!value) delete newFilters[key];

        router.get(route('tasks.index'), newFilters, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSort = (column: string) => {
        const currentDirection = filters.sort_by === column ? filters.sort_direction : null;
        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

        router.get(route('tasks.index'), {
            ...filters,
            sort_by: column,
            sort_direction: newDirection,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const getSortIcon = (column: string) => {
        if (filters.sort_by !== column) {
            return null;
        }
        return filters.sort_direction === 'asc' ? (
            <ChevronUpIcon className="w-4 h-4 inline ml-1" />
        ) : (
            <ChevronDownIcon className="w-4 h-4 inline ml-1" />
        );
    };

    const handleDelete = (task: Task) => {
        setTaskToDelete(task);
        setDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        if (taskToDelete) {
            router.delete(route('tasks.destroy', taskToDelete.uuid), {
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setTaskToDelete(null);
                },
            });
        }
    };

    const handleTaskToggle = (taskUuid: string, event: React.ChangeEvent<HTMLInputElement>) => {
        event.stopPropagation();
        router.patch(route('tasks.toggle-complete', taskUuid), {}, {
            preserveScroll: true,
        });
    };

    const completedStatus = statuses.find(s => s.name === 'completed');

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'high': return 'text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400';
            case 'medium': return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 dark:text-yellow-400';
            case 'low': return 'text-green-600 bg-green-50 dark:bg-green-900/20 dark:text-green-400';
            default: return 'text-gray-600 bg-gray-50 dark:bg-gray-700 dark:text-gray-400';
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed': return 'text-green-600 bg-green-50 dark:bg-green-900/20 dark:text-green-400';
            case 'in_progress': return 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400';
            case 'pending': return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 dark:text-yellow-400';
            case 'under_review': return 'text-purple-600 bg-purple-50 dark:bg-purple-900/20 dark:text-purple-400';
            default: return 'text-gray-600 bg-gray-50 dark:bg-gray-700 dark:text-gray-400';
        }
    };

    return (
        <DashboardLayout
            title="Tasks"
            description="Manage and track all your tasks"
            actions={
                <>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/projects">
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour aux Projets
                        </Link>
                    </Button>
                    <div className="flex bg-gray-100 dark:bg-gray-700 rounded-md p-1">
                        <button
                            onClick={() => setViewMode('table')}
                            className={`p-2 rounded ${viewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow' : 'hover:bg-gray-200 dark:hover:bg-gray-600'}`}
                            title="Table View"
                        >
                            <TableCellsIcon className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => setViewMode('list')}
                            className={`p-2 rounded ${viewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow' : 'hover:bg-gray-200 dark:hover:bg-gray-600'}`}
                            title="List View"
                        >
                            <ListBulletIcon className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => setViewMode('grid')}
                            className={`p-2 rounded ${viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow' : 'hover:bg-gray-200 dark:hover:bg-gray-600'}`}
                            title="Grid View"
                        >
                            <Squares2X2Icon className="w-4 h-4" />
                        </button>
                    </div>
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className="flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                    >
                        <FunnelIcon className="w-4 h-4 mr-2" />
                        Filters
                    </button>
                    <Link
                        href={route('tasks.create')}
                        className="flex items-center px-4 py-2 bg-primary hover:bg-primary text-white text-sm font-medium rounded-md"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        New Task
                    </Link>
                </>
            }
        >
            <Head title="Tasks" />

            <div className="mx-auto sm:px-6 lg:px-8">
                {/* Tab Switcher */}
                <div className="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1 w-fit mb-6">
                    <button
                        type="button"
                        onClick={() => setActiveTab('tasks')}
                        className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                            activeTab === 'tasks'
                                ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        }`}
                    >
                        Tâches
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('statistics')}
                        className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                            activeTab === 'statistics'
                                ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        }`}
                    >
                        Statistiques
                    </button>
                </div>

                {activeTab === 'statistics' ? (
                    taskStatistics ? (
                        <ProjectStatisticsAnalytical statistics={taskStatistics} context="tasks" />
                    ) : (
                        <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                            Aucune donnée statistique disponible
                        </div>
                    )
                ) : (
                <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-4 text-gray-900 dark:text-gray-100">
                        {showFilters && (
                                <div className="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Status
                                            </label>
                                            <select
                                                value={filters.status || ''}
                                                onChange={(e) => handleFilter('status', e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            >
                                                <option value="">All Statuses</option>
                                                {statuses.map(status => (
                                                    <option key={status.id} value={status.name}>
                                                        {status.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Program
                                            </label>
                                            <select
                                                value={filters.program || ''}
                                                onChange={(e) => handleFilter('program', e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            >
                                                <option value="">All Programs</option>
                                                {programs.map(program => (
                                                    <option key={program.id} value={program.id}>
                                                        {program.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Priority
                                            </label>
                                            <select
                                                value={filters.priority || ''}
                                                onChange={(e) => handleFilter('priority', e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            >
                                                <option value="">All Priorities</option>
                                                <option value="high">High</option>
                                                <option value="medium">Medium</option>
                                                <option value="low">Low</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Assigned To
                                            </label>
                                            <select
                                                value={filters.assigned_to || ''}
                                                onChange={(e) => handleFilter('assigned_to', e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            >
                                                <option value="">All Users</option>
                                                {users.map(user => (
                                                    <option key={user.id} value={user.id}>
                                                        {user.first_name} {user.last_name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Table View */}
                            {viewMode === 'table' && (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th className="px-3 py-3 text-left">
                                                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Complete
                                                    </span>
                                                </th>
                                                <th
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                                                    onClick={() => handleSort('title')}
                                                >
                                                    Task {getSortIcon('title')}
                                                </th>
                                                <th
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                                                    onClick={() => handleSort('program')}
                                                >
                                                    Program {getSortIcon('program')}
                                                </th>
                                                <th
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                                                    onClick={() => handleSort('status')}
                                                >
                                                    Status {getSortIcon('status')}
                                                </th>
                                                <th
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                                                    onClick={() => handleSort('priority')}
                                                >
                                                    Priority {getSortIcon('priority')}
                                                </th>
                                                <th
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                                                    onClick={() => handleSort('due_date')}
                                                >
                                                    Due Date {getSortIcon('due_date')}
                                                </th>
                                                <th
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                                                    onClick={() => handleSort('assigned_to')}
                                                >
                                                    Assigned To {getSortIcon('assigned_to')}
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            {tasks.data.map((task: any) => (
                                                <tr key={task.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <td className="px-3 py-4">
                                                        <input
                                                            type="checkbox"
                                                            checked={task.status?.id === completedStatus?.id}
                                                            onChange={(e) => handleTaskToggle(task.uuid, e)}
                                                            className="w-5 h-5 text-green-600 rounded border-gray-300 cursor-pointer"
                                                        />
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div>
                                                            <Link
                                                                href={route('tasks.show', task.uuid)}
                                                                className={`text-sm font-medium hover:text-primary hover:underline ${task.status?.id === completedStatus?.id ? 'line-through text-gray-500' : 'text-gray-900 dark:text-gray-100'}`}
                                                            >
                                                                {task.title}
                                                            </Link>
                                                            <div className="text-sm text-gray-500 dark:text-gray-400">
                                                                {task.description && task.description.substring(0, 100)}
                                                                {task.description && task.description.length > 100 && '...'}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {task.program?.name}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(task.status?.name)}`}>
                                                            {task.status?.name}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColor(task.priority)}`}>
                                                            {task.priority}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {task.due_date ? new Date(task.due_date).toLocaleDateString() : '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {task.assigned_user ? `${task.assigned_user.first_name} ${task.assigned_user.last_name}` : 'Unassigned'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex space-x-2">
                                                            <Link
                                                                href={route('tasks.show', task.uuid)}
                                                                className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                            >
                                                                <EyeIcon className="w-4 h-4" />
                                                            </Link>
                                                            <Link
                                                                href={route('tasks.edit', task.uuid)}
                                                                className="text-primary hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                            >
                                                                <PencilIcon className="w-4 h-4" />
                                                            </Link>
                                                            <button
                                                                onClick={() => handleDelete(task)}
                                                                className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                            >
                                                                <TrashIcon className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* List View */}
                            {viewMode === 'list' && (
                                <div className="space-y-3">
                                    {tasks.data.map((task: any) => (
                                        <div key={task.id} className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div className="flex items-start justify-between">
                                                <div className="flex items-start space-x-3 flex-1">
                                                    <input
                                                        type="checkbox"
                                                        checked={task.status?.id === completedStatus?.id}
                                                        onChange={(e) => handleTaskToggle(task.uuid, e)}
                                                        className="mt-1 w-5 h-5 text-green-600 rounded border-gray-300 cursor-pointer"
                                                    />
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2 mb-2">
                                                            <Link
                                                                href={route('tasks.show', task.uuid)}
                                                                className={`font-medium hover:text-primary hover:underline ${task.status?.id === completedStatus?.id ? 'line-through text-gray-500' : 'text-gray-900 dark:text-gray-100'}`}
                                                            >
                                                                {task.title}
                                                            </Link>
                                                            <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getPriorityColor(task.priority)}`}>
                                                                {task.priority}
                                                            </span>
                                                            <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getStatusColor(task.status?.name)}`}>
                                                                {task.status?.name}
                                                            </span>
                                                        </div>
                                                        {task.description && (
                                                            <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                                                {task.description.substring(0, 150)}
                                                                {task.description.length > 150 && '...'}
                                                            </p>
                                                        )}
                                                        <div className="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                            {task.program && (
                                                                <span>📁 {task.program.name}</span>
                                                            )}
                                                            {task.due_date && (
                                                                <span>📅 {new Date(task.due_date).toLocaleDateString()}</span>
                                                            )}
                                                            {task.assigned_user && (
                                                                <span>👤 {task.assigned_user.first_name} {task.assigned_user.last_name}</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex space-x-2 ml-4">
                                                    <Link
                                                        href={route('tasks.show', task.uuid)}
                                                        className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                    >
                                                        <EyeIcon className="w-4 h-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('tasks.edit', task.uuid)}
                                                        className="text-primary hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                    >
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(task)}
                                                        className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                    >
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Grid View */}
                            {viewMode === 'grid' && (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {tasks.data.map((task: any) => (
                                        <div key={task.id} className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-lg transition-shadow">
                                            <div className="flex items-start justify-between mb-3">
                                                <div className="flex items-center space-x-2">
                                                    <input
                                                        type="checkbox"
                                                        checked={task.status?.id === completedStatus?.id}
                                                        onChange={(e) => handleTaskToggle(task.uuid, e)}
                                                        className="w-5 h-5 text-green-600 rounded border-gray-300 cursor-pointer"
                                                    />
                                                </div>
                                                <div className="flex space-x-1">
                                                    <Link
                                                        href={route('tasks.show', task.uuid)}
                                                        className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                    >
                                                        <EyeIcon className="w-4 h-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('tasks.edit', task.uuid)}
                                                        className="text-primary hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                    >
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(task)}
                                                        className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                    >
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                            <Link
                                                href={route('tasks.show', task.uuid)}
                                                className={`block font-medium mb-2 hover:text-primary hover:underline ${task.status?.id === completedStatus?.id ? 'line-through text-gray-500' : 'text-gray-900 dark:text-gray-100'}`}
                                            >
                                                {task.title}
                                            </Link>
                                            {task.description && (
                                                <p className="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">
                                                    {task.description}
                                                </p>
                                            )}
                                            <div className="flex flex-wrap gap-2 mb-3">
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColor(task.priority)}`}>
                                                    {task.priority}
                                                </span>
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(task.status?.name)}`}>
                                                    {task.status?.name}
                                                </span>
                                            </div>
                                            <div className="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                                                {task.program && (
                                                    <div>📁 {task.program.name}</div>
                                                )}
                                                {task.due_date && (
                                                    <div>📅 {new Date(task.due_date).toLocaleDateString()}</div>
                                                )}
                                                {task.assigned_user && (
                                                    <div>👤 {task.assigned_user.first_name} {task.assigned_user.last_name}</div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                        {tasks.data.length === 0 && (
                            <div className="text-center py-8">
                                <p className="text-gray-500 dark:text-gray-400">No tasks found.</p>
                            </div>
                        )}
                    </div>
                </div>
                )}
            </div>

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={confirmDelete}
                title="Supprimer la tâche"
                description={`Êtes-vous sûr de vouloir supprimer la tâche "${taskToDelete?.title}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
}
