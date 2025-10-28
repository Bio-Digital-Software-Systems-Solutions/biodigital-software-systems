import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import { Task, Program, Project, Status, User, PageProps } from '@/Types';
import { useState } from 'react';

interface Props extends PageProps {
    task: Task & {
        program?: Program;
        project?: Project;
        status: Status;
        assigned_user: User;
        taskable_type?: string;
        taskable_id?: number;
    };
    programs: Program[];
    projects: Project[];
    statuses: Status[];
    users: User[];
}

export default function Edit({ task, programs, projects, statuses, users }: Props) {
    // Determine initial taskable type
    const getInitialTaskableType = () => {
        if (task.taskable_type === 'App\\Models\\Project') return 'project';
        if (task.taskable_type === 'App\\Models\\Program') return 'program';
        if (task.project_id) return 'project';
        if (task.program_id) return 'program';
        return 'none';
    };

    const getInitialTaskableId = () => {
        if (task.taskable_id) return task.taskable_id;
        if (task.project_id) return task.project_id;
        if (task.program_id) return task.program_id;
        return '';
    };

    const [taskableType, setTaskableType] = useState<'none' | 'project' | 'program'>(getInitialTaskableType());

    const { data, setData, put, processing, errors } = useForm({
        title: task.title || '',
        description: task.description || '',
        due_date: task.due_date ? task.due_date.split('T')[0] : '',
        priority: task.priority || 'medium',
        estimated_hours: task.estimated_hours || '',
        actual_hours: task.actual_hours || '',
        notes: task.notes || '',
        status_id: task.status_id || '',
        taskable_type: task.taskable_type || '',
        taskable_id: getInitialTaskableId(),
        program_id: task.program_id || '',
        project_id: task.project_id || '',
        assigned_to: task.assigned_to || '',
    });

    const handleTaskableTypeChange = (type: 'none' | 'project' | 'program') => {
        setTaskableType(type);
        if (type === 'none') {
            setData({
                ...data,
                taskable_type: '',
                taskable_id: '',
                project_id: '',
                program_id: '',
            });
        } else {
            setData({
                ...data,
                taskable_id: '',
            });
        }
    };

    const handleTaskableIdChange = (id: string) => {
        setData({
            ...data,
            taskable_id: Number(id),
            ...(taskableType === 'project' ? { project_id: id } : {}),
            ...(taskableType === 'program' ? { program_id: id } : {}),
            taskable_type: taskableType === 'project' ? 'App\\Models\\Project' : (taskableType === 'program' ? 'App\\Models\\Program' : ''),
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('tasks.update', task.uuid));
    };

    return (
        <DashboardLayout>
            <Head title={`Edit Task - ${task.title}`} />

            <div className="p-4">
                <div className="mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <div className="flex items-center mb-6">
                                <Link
                                    href={route('project-tasks.index')}
                                    className="flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mr-4"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-1" />
                                    Back to Tasks
                                </Link>
                                <h1 className="text-2xl font-semibold">Edit Task</h1>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label htmlFor="title" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Title *
                                        </label>
                                        <input
                                            type="text"
                                            id="title"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            required
                                        />
                                        {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="taskable_type" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Associate with
                                        </label>
                                        <select
                                            id="taskable_type"
                                            value={taskableType}
                                            onChange={(e) => handleTaskableTypeChange(e.target.value as 'none' | 'project' | 'program')}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        >
                                            <option value="none">None (standalone task)</option>
                                            <option value="project">Project</option>
                                            <option value="program">Program</option>
                                        </select>
                                        {errors.taskable_type && <p className="mt-1 text-sm text-red-600">{errors.taskable_type}</p>}
                                    </div>

                                    {taskableType === 'project' && (
                                        <div>
                                            <label htmlFor="taskable_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Project *
                                            </label>
                                            <select
                                                id="taskable_id"
                                                value={data.taskable_id || ''}
                                                onChange={(e) => handleTaskableIdChange(e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                                required
                                            >
                                                <option value="">Select a project</option>
                                                {projects.map(project => (
                                                    <option key={project.id} value={project.id}>
                                                        {project.name}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.taskable_id && <p className="mt-1 text-sm text-red-600">{errors.taskable_id}</p>}
                                        </div>
                                    )}

                                    {taskableType === 'program' && (
                                        <div>
                                            <label htmlFor="taskable_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Program *
                                            </label>
                                            <select
                                                id="taskable_id"
                                                value={data.taskable_id || ''}
                                                onChange={(e) => handleTaskableIdChange(e.target.value)}
                                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                                required
                                            >
                                                <option value="">Select a program</option>
                                                {programs.map(program => (
                                                    <option key={program.id} value={program.id}>
                                                        {program.name}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.taskable_id && <p className="mt-1 text-sm text-red-600">{errors.taskable_id}</p>}
                                        </div>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Description
                                    </label>
                                    <textarea
                                        id="description"
                                        rows={4}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label htmlFor="status_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Status *
                                        </label>
                                        <select
                                            id="status_id"
                                            value={data.status_id}
                                            onChange={(e) => setData('status_id', e.target.value)}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            required
                                        >
                                            <option value="">Select status</option>
                                            {statuses.map(status => (
                                                <option key={status.id} value={status.id}>
                                                    {status.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.status_id && <p className="mt-1 text-sm text-red-600">{errors.status_id}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="priority" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Priority *
                                        </label>
                                        <select
                                            id="priority"
                                            value={data.priority}
                                            onChange={(e) => setData('priority', e.target.value as 'low' | 'medium' | 'high')}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                            required
                                        >
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                        {errors.priority && <p className="mt-1 text-sm text-red-600">{errors.priority}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="assigned_to" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Assigned To
                                        </label>
                                        <select
                                            id="assigned_to"
                                            value={data.assigned_to}
                                            onChange={(e) => setData('assigned_to', e.target.value)}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        >
                                            <option value="">Unassigned</option>
                                            {users.map(user => (
                                                <option key={user.id} value={user.id}>
                                                    {user.first_name} {user.last_name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.assigned_to && <p className="mt-1 text-sm text-red-600">{errors.assigned_to}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label htmlFor="due_date" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Due Date
                                        </label>
                                        <input
                                            type="date"
                                            id="due_date"
                                            value={data.due_date}
                                            onChange={(e) => setData('due_date', e.target.value)}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        />
                                        {errors.due_date && <p className="mt-1 text-sm text-red-600">{errors.due_date}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="estimated_hours" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Estimated Hours
                                        </label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            id="estimated_hours"
                                            value={data.estimated_hours}
                                            onChange={(e) => setData('estimated_hours', e.target.value)}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        />
                                        {errors.estimated_hours && <p className="mt-1 text-sm text-red-600">{errors.estimated_hours}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="actual_hours" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Actual Hours
                                        </label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            id="actual_hours"
                                            value={data.actual_hours}
                                            onChange={(e) => setData('actual_hours', e.target.value)}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        />
                                        {errors.actual_hours && <p className="mt-1 text-sm text-red-600">{errors.actual_hours}</p>}
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="notes" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Notes
                                    </label>
                                    <textarea
                                        id="notes"
                                        rows={3}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    {errors.notes && <p className="mt-1 text-sm text-red-600">{errors.notes}</p>}
                                </div>

                                <div className="flex justify-end space-x-3">
                                    <Link
                                        href={route('project-tasks.index')}
                                        className="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-primary hover:bg-primary disabled:bg-blue-300 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                    >
                                        {processing ? 'Updating...' : 'Update Task'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}