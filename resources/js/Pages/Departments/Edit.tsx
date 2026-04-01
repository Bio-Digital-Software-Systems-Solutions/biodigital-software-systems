import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import { SearchableSelect } from '@/Components/ui/searchable-select';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface DepartmentOption {
    id: number;
    uuid: string;
    name: string;
    code: string;
    parent_id: number | null;
}

interface Department {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description?: string;
    budget?: number;
    head_of_department?: number;
    first_deputy_id?: number;
    second_deputy_id?: number;
    parent_id?: number | null;
    is_active: boolean;
}

interface Props extends PageProps {
    department: Department;
    users: User[];
    departments: DepartmentOption[];
}

export default function Edit({ department, users, departments }: Props) {
    const [departmentType, setDepartmentType] = useState<'department' | 'sub-department'>(
        department.parent_id ? 'sub-department' : 'department'
    );

    const { data, setData, put, processing, errors } = useForm({
        name: department.name || '',
        code: department.code || '',
        description: department.description || '',
        head_of_department: department.head_of_department || '',
        first_deputy_id: department.first_deputy_id || '',
        second_deputy_id: department.second_deputy_id || '',
        budget: department.budget || '',
        is_active: department.is_active ?? true,
        parent_id: (department.parent_id || '') as string | number,
    });

    // Reset parent_id when switching to department type
    useEffect(() => {
        if (departmentType === 'department') {
            setData('parent_id', '');
        }
    }, [departmentType]);

    // Convert users to options for SearchableSelect
    const userOptions = useMemo(() =>
        users.map(user => ({
            value: user.id,
            label: `${user.first_name} ${user.last_name} (${user.email})`,
        })),
        [users]
    );

    // Convert departments to options for SearchableSelect
    const departmentOptions = useMemo(() =>
        departments.map(dept => ({
            value: dept.id,
            label: `${dept.name} (${dept.code})`,
        })),
        [departments]
    );

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
                            {/* Department Type Selection */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="department_type" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Type *
                                    </label>
                                    <select
                                        id="department_type"
                                        value={departmentType}
                                        onChange={(e) => setDepartmentType(e.target.value as 'department' | 'sub-department')}
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    >
                                        <option value="department">Département</option>
                                        <option value="sub-department">Sous-département</option>
                                    </select>
                                </div>

                                {departmentType === 'sub-department' && (
                                    <div>
                                        <label htmlFor="parent_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Département parent *
                                        </label>
                                        <div className="mt-1">
                                            <SearchableSelect
                                                id="parent_id"
                                                options={departmentOptions}
                                                value={data.parent_id ? Number(data.parent_id) : null}
                                                onChange={(value) => setData('parent_id', value ? Number(value) : '')}
                                                placeholder="Rechercher un département parent..."
                                                isClearable={true}
                                                noOptionsMessage="Aucun département trouvé"
                                            />
                                        </div>
                                        {errors.parent_id && <p className="mt-1 text-sm text-red-600">{errors.parent_id}</p>}
                                    </div>
                                )}
                            </div>

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
                                        placeholder="CODE"
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
                                    <div className="mt-1">
                                        <SearchableSelect
                                            id="head_of_department"
                                            options={userOptions}
                                            value={data.head_of_department ? Number(data.head_of_department) : null}
                                            onChange={(value) => setData('head_of_department', value ? String(value) : '')}
                                            placeholder="Rechercher un chef..."
                                            isClearable={true}
                                            noOptionsMessage="Aucun utilisateur trouvé"
                                        />
                                    </div>
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

                            {/* Deputies */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="first_deputy_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        1er Adjoint
                                    </label>
                                    <div className="mt-1">
                                        <SearchableSelect
                                            id="first_deputy_id"
                                            options={userOptions}
                                            value={data.first_deputy_id ? Number(data.first_deputy_id) : null}
                                            onChange={(value) => setData('first_deputy_id', value ? String(value) : '')}
                                            placeholder="Rechercher un adjoint..."
                                            isClearable={true}
                                            noOptionsMessage="Aucun utilisateur trouvé"
                                        />
                                    </div>
                                    {errors.first_deputy_id && <p className="mt-1 text-sm text-red-600">{errors.first_deputy_id}</p>}
                                </div>

                                <div>
                                    <label htmlFor="second_deputy_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        2ème Adjoint
                                    </label>
                                    <div className="mt-1">
                                        <SearchableSelect
                                            id="second_deputy_id"
                                            options={userOptions}
                                            value={data.second_deputy_id ? Number(data.second_deputy_id) : null}
                                            onChange={(value) => setData('second_deputy_id', value ? String(value) : '')}
                                            placeholder="Rechercher un adjoint..."
                                            isClearable={true}
                                            noOptionsMessage="Aucun utilisateur trouvé"
                                        />
                                    </div>
                                    {errors.second_deputy_id && <p className="mt-1 text-sm text-red-600">{errors.second_deputy_id}</p>}
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
