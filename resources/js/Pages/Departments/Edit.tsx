import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface Department {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description?: string;
    budget?: number;
    head_of_department?: number;
    is_active: boolean;
}

interface Props extends PageProps {
    department: Department;
    users: User[];
}

export default function Edit({ department, users }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: department.name || '',
        code: department.code || '',
        description: department.description || '',
        head_of_department: department.head_of_department || '',
        budget: department.budget || '',
        is_active: department.is_active ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('departments.update', department.uuid));
    };

    return (
        <DashboardLayout>
            <Head title={`Modifier le Département: ${department.name}`} />

            <div className="p-4">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('departments.index')}
                            className="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Retour aux départements
                        </Link>
                    </div>

                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h1 className="text-lg font-medium text-gray-900 dark:text-white">
                                Modifier le département : {department.name}
                            </h1>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Basic Information */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Nom *
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="Nom du département"
                                        required
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                                </div>

                                <div>
                                    <label htmlFor="code" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Code *
                                    </label>
                                    <input
                                        type="text"
                                        id="code"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="DEPT-001"
                                        maxLength={50}
                                        required
                                    />
                                    {errors.code && <p className="mt-1 text-sm text-red-600">{errors.code}</p>}
                                </div>
                            </div>

                            <div>
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    rows={3}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    placeholder="Description du département..."
                                />
                                {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                            </div>

                            {/* Leadership and Budget */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="head_of_department" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Chef de département
                                    </label>
                                    <select
                                        id="head_of_department"
                                        value={data.head_of_department}
                                        onChange={(e) => setData('head_of_department', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    >
                                        <option value="">Sélectionner un chef</option>
                                        {users.map(user => (
                                            <option key={user.id} value={user.id}>
                                                {user.first_name} {user.last_name} ({user.email})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.head_of_department && <p className="mt-1 text-sm text-red-600">{errors.head_of_department}</p>}
                                </div>

                                <div>
                                    <label htmlFor="budget" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Budget (€)
                                    </label>
                                    <input
                                        type="number"
                                        id="budget"
                                        value={data.budget}
                                        onChange={(e) => setData('budget', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="0.00"
                                        min="0"
                                        step="0.01"
                                    />
                                    {errors.budget && <p className="mt-1 text-sm text-red-600">{errors.budget}</p>}
                                </div>
                            </div>

                            {/* Status */}
                            <div>
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="rounded border-gray-300 text-primary shadow-sm focus:ring-primary dark:border-gray-600 dark:bg-gray-700"
                                    />
                                    <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Département actif
                                    </span>
                                </label>
                                {errors.is_active && <p className="mt-1 text-sm text-red-600">{errors.is_active}</p>}
                            </div>

                            {/* Actions */}
                            <div className="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <Link
                                    href={route('departments.index')}
                                    className="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    Annuler
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-primary hover:bg-primary disabled:bg-blue-300 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    {processing ? 'Mise à jour...' : 'Mettre à jour'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}