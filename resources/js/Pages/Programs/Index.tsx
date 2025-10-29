import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PlusIcon, FunnelIcon, EyeIcon, PencilIcon, TrashIcon, TableCellsIcon, Squares2X2Icon, ListBulletIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { Program, PageProps } from '@/Types';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface Props extends PageProps {
    programs: {
        data: Program[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        [key: string]: string | undefined;
        status?: string;
        priority?: string;
    };
}

export default function Index({ programs, filters }: Props) {
    const [showFilters, setShowFilters] = useState(false);
    const [viewMode, setViewMode] = useState<'table' | 'grid' | 'list'>('table');
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [programToDelete, setProgramToDelete] = useState<Program | null>(null);

    const handleFilter = (key: string, value: string) => {
        const newFilters = { ...filters, [key]: value };
        if (!value) delete newFilters[key];
        
        router.get(route('programs.index'), newFilters, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (program: Program) => {
        setProgramToDelete(program);
        setDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        if (programToDelete) {
            router.delete(route('programs.destroy', programToDelete.uuid), {
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setProgramToDelete(null);
                },
            });
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed': return 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20';
            case 'active': return 'text-primary bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20';
            case 'in_progress': return 'text-purple-600 bg-purple-50 dark:text-purple-400 dark:bg-purple-900/20';
            case 'planning': return 'text-yellow-600 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20';
            case 'cancelled': return 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20';
            default: return 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
        }
    };

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'high': return 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20';
            case 'medium': return 'text-yellow-600 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20';
            case 'low': return 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20';
            default: return 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
        }
    };

    return (
        <DashboardLayout
            title="Programs"
            description="Manage and monitor all your programs"
            actions={
                <>
                    <div className="flex border border-gray-300 dark:border-gray-600 rounded-md overflow-hidden">
                        <button
                            onClick={() => setViewMode('table')}
                            className={`p-2 ${viewMode === 'table' ? 'bg-primary text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'}`}
                            title="Table View"
                        >
                            <TableCellsIcon className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => setViewMode('grid')}
                            className={`p-2 border-l border-gray-300 dark:border-gray-600 ${viewMode === 'grid' ? 'bg-primary text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'}`}
                            title="Grid View"
                        >
                            <Squares2X2Icon className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => setViewMode('list')}
                            className={`p-2 border-l border-gray-300 dark:border-gray-600 ${viewMode === 'list' ? 'bg-primary text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'}`}
                            title="List View"
                        >
                            <ListBulletIcon className="w-4 h-4" />
                        </button>
                    </div>
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                    >
                        <FunnelIcon className="w-4 h-4 mr-2" />
                        Filters
                    </button>
                    <Link
                        href={route('programs.create')}
                        className="flex items-center justify-center px-4 py-2 bg-primary hover:bg-primary text-white text-sm font-medium rounded-md"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        <span className="hidden sm:inline">New Program</span>
                        <span className="sm:hidden">New</span>
                    </Link>
                </>
            }
        >
            <Head title="Programs" />

            {showFilters && (
                                <div className="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                                <option value="planning">Planning</option>
                                                <option value="active">Active</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
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
                                    </div>
                                </div>
                            )}

                            {/* Grid View */}
                            {viewMode === 'grid' && (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    {programs.data.map((program) => (
                                        <Link
                                            key={program.uuid}
                                            href={route('programs.show', program.uuid)}
                                            className="block bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 p-5 border border-gray-200 dark:border-gray-600"
                                        >
                                            <div className="space-y-3">
                                                <div>
                                                    <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">
                                                        {program.name}
                                                    </h3>
                                                    {program.description && (
                                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                                            {program.description}
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="flex gap-2">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(program.status)}`}>
                                                        {program.status.replace('_', ' ')}
                                                    </span>
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColor(program.priority)}`}>
                                                        {program.priority}
                                                    </span>
                                                </div>

                                                <div className="space-y-1">
                                                    <div className="flex justify-between text-xs">
                                                        <span className="text-gray-500 dark:text-gray-400">Progress</span>
                                                        <span className="text-gray-900 dark:text-gray-100 font-medium">{program.progress_percentage || 0}%</span>
                                                    </div>
                                                    <div className="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                        <div
                                                            className="bg-primary h-2 rounded-full transition-all duration-300"
                                                            style={{ width: `${program.progress_percentage || 0}%` }}
                                                        ></div>
                                                    </div>
                                                </div>

                                                <div className="pt-2 border-t border-gray-200 dark:border-gray-600 space-y-1 text-xs">
                                                    <div className="flex justify-between">
                                                        <span className="text-gray-500 dark:text-gray-400">Duration:</span>
                                                        <span className="text-gray-900 dark:text-gray-100">
                                                            {new Date(program.start_date).toLocaleDateString()}
                                                        </span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-gray-500 dark:text-gray-400">Budget:</span>
                                                        <span className="text-gray-900 dark:text-gray-100">
                                                            {program.budget ? `$${program.budget.toLocaleString()}` : '-'}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div className="flex gap-2 pt-2" onClick={(e) => e.preventDefault()}>
                                                    <Link
                                                        href={route('programs.edit', program.uuid)}
                                                        className="flex-1 text-center px-2 py-1 text-xs font-medium text-primary hover:text-primary dark:text-primary dark:hover:text-primary bg-indigo-50 dark:bg-indigo-900/20 rounded"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <button
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            handleDelete(program);
                                                        }}
                                                        className="flex-1 px-2 py-1 text-xs font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 bg-red-50 dark:bg-red-900/20 rounded"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}

                            {/* List View */}
                            {viewMode === 'list' && (
                                <div className="space-y-3">
                                    {programs.data.map((program) => (
                                        <Link
                                            key={program.uuid}
                                            href={route('programs.show', program.uuid)}
                                            className="block bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 p-4 border border-gray-200 dark:border-gray-600"
                                        >
                                            <div className="flex items-center justify-between gap-4">
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-3 mb-2">
                                                        <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                                            {program.name}
                                                        </h3>
                                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(program.status)}`}>
                                                            {program.status.replace('_', ' ')}
                                                        </span>
                                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColor(program.priority)}`}>
                                                            {program.priority}
                                                        </span>
                                                    </div>
                                                    {program.description && (
                                                        <p className="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">
                                                            {program.description}
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="flex items-center gap-6">
                                                    <div className="w-32">
                                                        <div className="flex justify-between text-xs mb-1">
                                                            <span className="text-gray-500 dark:text-gray-400">Progress</span>
                                                            <span className="text-gray-900 dark:text-gray-100">{program.progress_percentage || 0}%</span>
                                                        </div>
                                                        <div className="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                            <div
                                                                className="bg-primary h-2 rounded-full"
                                                                style={{ width: `${program.progress_percentage || 0}%` }}
                                                            ></div>
                                                        </div>
                                                    </div>

                                                    <div className="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                        {new Date(program.start_date).toLocaleDateString()}
                                                    </div>

                                                    <div className="text-xs font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                                        {program.budget ? `$${program.budget.toLocaleString()}` : '-'}
                                                    </div>

                                                    <div className="flex gap-2" onClick={(e) => e.preventDefault()}>
                                                        <Link
                                                            href={route('programs.edit', program.uuid)}
                                                            className="p-1 text-primary hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                        >
                                                            <PencilIcon className="w-4 h-4" />
                                                        </Link>
                                                        <button
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                handleDelete(program);
                                                            }}
                                                            className="p-1 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                        >
                                                            <TrashIcon className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}

                            {/* Table View */}
                            {viewMode === 'table' && (
                                <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Program
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Priority
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Duration
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Progress
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Budget
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {programs.data.map((program) => (
                                            <tr key={program.uuid}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div>
                                                        <Link
                                                            href={route('programs.show', program.uuid)}
                                                            className="text-sm font-medium text-primary dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                        >
                                                            {program.name}
                                                        </Link>
                                                        <div className="text-sm text-gray-500 dark:text-gray-400">
                                                            {program.description && program.description.substring(0, 100)}
                                                            {program.description && program.description.length > 100 && '...'}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(program.status)}`}>
                                                        {program.status.replace('_', ' ')}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColor(program.priority)}`}>
                                                        {program.priority}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    <div>
                                                        <div>{new Date(program.start_date).toLocaleDateString()}</div>
                                                        <div className="text-gray-500 dark:text-gray-400">to {new Date(program.end_date).toLocaleDateString()}</div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                        <div
                                                            className="bg-primary h-2 rounded-full"
                                                            style={{ width: `${program.progress_percentage || 0}%` }}
                                                        ></div>
                                                    </div>
                                                    <span className="text-xs">{program.progress_percentage || 0}%</span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {program.budget ? `$${program.budget.toLocaleString()}` : '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex space-x-2">
                                                        <Link
                                                            href={route('programs.show', program.uuid)}
                                                            className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                        >
                                                            <EyeIcon className="w-4 h-4" />
                                                        </Link>
                                                        <Link
                                                            href={route('programs.edit', program.uuid)}
                                                            className="text-primary hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                        >
                                                            <PencilIcon className="w-4 h-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => handleDelete(program)}
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

                            {programs.data.length === 0 && (
                                <div className="text-center py-8">
                                    <p className="text-gray-500 dark:text-gray-400">No programs found.</p>
                                </div>
                            )}

                            {/* Pagination */}
                            {programs.last_page > 1 && (
                                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-6 space-y-4 sm:space-y-0">
                                    <div className="text-xs sm:text-sm text-gray-700 dark:text-gray-300 text-center sm:text-left">
                                        Showing {((programs.current_page - 1) * programs.per_page) + 1} to{' '}
                                        {Math.min(programs.current_page * programs.per_page, programs.total)} of{' '}
                                        {programs.total} results
                                    </div>
                                    <div className="flex justify-center sm:justify-end">
                                        <div className="flex space-x-1 sm:space-x-2">
                                            {programs.links.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url || '#'}
                                                    className={`px-2 py-1 sm:px-3 sm:py-2 text-xs sm:text-sm font-medium rounded-md ${
                                                        link.active
                                                            ? 'bg-primary text-white'
                                                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={confirmDelete}
                title="Supprimer le programme"
                description={`Êtes-vous sûr de vouloir supprimer le programme "${programToDelete?.name}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
}