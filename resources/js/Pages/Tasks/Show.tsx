import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeftIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import { Task, Program, Status, User, PageProps } from '@/Types';

interface Project {
    id: number;
    uuid: string;
    name: string;
}

interface Props extends PageProps {
    task: Task & {
        program?: Program;
        project?: Project;
        status: Status;
        assigned_user: User;
    };
}

export default function Show({ task }: Props) {
    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this task?')) {
            router.delete(route('tasks.destroy', task.uuid));
        }
    };

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'high': return 'text-red-600 bg-red-50';
            case 'medium': return 'text-yellow-600 bg-yellow-50';
            case 'low': return 'text-green-600 bg-green-50';
            default: return 'text-gray-600 bg-gray-50';
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed': return 'text-green-600 bg-green-50';
            case 'in_progress': return 'text-primary bg-blue-50';
            case 'pending': return 'text-yellow-600 bg-yellow-50';
            default: return 'text-gray-600 bg-gray-50';
        }
    };

    return (
        <DashboardLayout>
            <Head title={`Task - ${task.title}`} />

            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6 text-gray-900 dark:text-gray-100">
                    <div className="flex justify-between items-start mb-6">
                        <div className="flex items-center">
                            <Link
                                href={route('tasks.index')}
                                className="flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mr-4"
                            >
                                <ArrowLeftIcon className="w-4 h-4 mr-1" />
                                Back to Tasks
                            </Link>
                            <h1 className="text-2xl font-semibold">{task.title}</h1>
                        </div>
                        
                        <div className="flex space-x-2">
                            <Link
                                href={route('tasks.edit', task.uuid)}
                                className="flex items-center px-3 py-2 bg-primary hover:bg-primary text-white text-sm font-medium rounded-md"
                            >
                                <PencilIcon className="w-4 h-4 mr-1" />
                                Edit
                            </Link>
                            <button
                                onClick={handleDelete}
                                className="flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md"
                            >
                                <TrashIcon className="w-4 h-4 mr-1" />
                                Delete
                            </button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div className="space-y-6">
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Task Details</h3>
                                <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-4">
                                    {task.project && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Project</dt>
                                            <dd className="mt-1">
                                                <Link
                                                    href={route('projects.show', task.project.uuid)}
                                                    className="text-sm text-primary hover:text-primary-dark hover:underline"
                                                >
                                                    {task.project.name}
                                                </Link>
                                            </dd>
                                        </div>
                                    )}

                                    {task.program && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Program</dt>
                                            <dd className="mt-1">
                                                <Link
                                                    href={route('programs.show', task.program.uuid)}
                                                    className="text-sm text-primary hover:text-primary-dark hover:underline"
                                                >
                                                    {task.program.name}
                                                </Link>
                                            </dd>
                                        </div>
                                    )}
                                    
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                            <dd className="mt-1">
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(task.status?.name)}`}>
                                                    {task.status?.name}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Priority</dt>
                                            <dd className="mt-1">
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColor(task.priority)}`}>
                                                    {task.priority}
                                                </span>
                                            </dd>
                                        </div>
                                    </div>

                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Assigned To</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {task.assigned_user 
                                                ? `${task.assigned_user.first_name} ${task.assigned_user.last_name}` 
                                                : 'Unassigned'}
                                        </dd>
                                    </div>

                                    {task.due_date && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Due Date</dt>
                                            <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                                {new Date(task.due_date).toLocaleDateString()}
                                            </dd>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {(task.estimated_hours || task.actual_hours) && (
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Time Tracking</h3>
                                    <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            {task.estimated_hours && (
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Estimated Hours</dt>
                                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{task.estimated_hours}</dd>
                                                </div>
                                            )}
                                            {task.actual_hours && (
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Actual Hours</dt>
                                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{task.actual_hours}</dd>
                                                </div>
                                            )}
                                        </div>
                                        
                                        {task.estimated_hours && task.actual_hours && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Variance</dt>
                                                <dd className={`mt-1 text-sm ${
                                                    (task.actual_hours - task.estimated_hours) > 0 
                                                        ? 'text-red-600' 
                                                        : 'text-green-600'
                                                }`}>
                                                    {task.actual_hours - task.estimated_hours > 0 ? '+' : ''}
                                                    {(task.actual_hours - task.estimated_hours).toFixed(1)} hours
                                                </dd>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="space-y-6">
                            {task.description && (
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Description</h3>
                                    <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <p className="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                                            {task.description}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {task.notes && (
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Notes</h3>
                                    <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <p className="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                                            {task.notes}
                                        </p>
                                    </div>
                                </div>
                            )}

                            <div>
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Timestamps</h3>
                                <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-4">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {new Date(task.created_at).toLocaleString()}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {new Date(task.updated_at).toLocaleString()}
                                        </dd>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
        </DashboardLayout>
    );
}