import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import ViewSwitcher from '@/Components/ViewSwitcher';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import {
    PlusIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    FunnelIcon,
    BuildingOfficeIcon,
    CheckCircleIcon,
    XCircleIcon,
    UserIcon,
    BanknotesIcon
} from '@heroicons/react/24/outline';
import { userHasPermission } from '@/Enums/Permission';

type ViewMode = 'grid' | 'list' | 'calendar';

interface User {
    id: number;
    first_name: string;
    last_name: string;
}

interface Department {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description?: string;
    budget?: number;
    is_active: boolean;
    head_of_department?: number;
    head_of_department_user?: User;
    users: User[];
    users_count: number;
    created_at: string;
    updated_at: string;
}

interface IndexProps extends PageProps {
    departments: {
        data: Department[];
        links: any[];
        meta: any;
    };
    filters: {
        status?: string;
    };
}

const Index: React.FC<IndexProps> = ({ departments, filters }) => {
    const { auth } = usePage<PageProps>().props;
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [viewMode, setViewMode] = useState<ViewMode>('grid');
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [departmentToDelete, setDepartmentToDelete] = useState<Department | null>(null);

    const canCreateDepartments = userHasPermission(auth.user, 'create departments');
    const canEditDepartments = userHasPermission(auth.user, 'edit departments');
    const canDeleteDepartments = userHasPermission(auth.user, 'delete departments');

    const handleFilter = (status: string) => {
        setSelectedStatus(status);
        router.get('/departments', { status: status || undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (department: Department) => {
        setDepartmentToDelete(department);
        setDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        if (departmentToDelete) {
            router.delete(route('departments.destroy', departmentToDelete.uuid), {
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setDepartmentToDelete(null);
                },
            });
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatBudget = (budget?: number) => {
        if (!budget) return 'N/A';
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(budget);
    };

    return (
        <DashboardLayout
            title="Départements"
            description="Gérez les départements et leurs membres"
            actions={
                <div className="flex flex-wrap items-center gap-2">
                    <ViewSwitcher currentView={viewMode} onViewChange={(view) => setViewMode(view)} />
                    {canCreateDepartments && (
                        <Link
                            href={route('departments.create')}
                            className="inline-flex items-center px-2 sm:px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200 text-sm"
                        >
                            <PlusIcon className="h-4 w-4 sm:h-5 sm:w-5 sm:mr-2" />
                            <span className="hidden sm:inline">Nouveau département</span>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Départements - AIG-App" />

            {/* Filters */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-3 sm:p-6 mb-4 sm:mb-6">
                        <div className="flex flex-wrap items-center gap-2 sm:gap-4">
                            <FunnelIcon className="h-5 w-5 text-gray-400" />
                            <div className="flex gap-2 sm:gap-4">
                                <button
                                    onClick={() => handleFilter('')}
                                    className={`px-3 py-1 rounded-md text-sm font-medium ${
                                        selectedStatus === '' 
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' 
                                            : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'
                                    }`}
                                >
                                    Tous
                                </button>
                                <button
                                    onClick={() => handleFilter('active')}
                                    className={`px-3 py-1 rounded-md text-sm font-medium ${
                                        selectedStatus === 'active' 
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' 
                                            : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'
                                    }`}
                                >
                                    Actifs
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* List View */}
                    {viewMode === 'list' && (
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Département
                                            </th>
                                            <th scope="col" className="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Code
                                            </th>
                                            <th scope="col" className="hidden md:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Chef
                                            </th>
                                            <th scope="col" className="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Membres
                                            </th>
                                            <th scope="col" className="hidden lg:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Budget
                                            </th>
                                            <th scope="col" className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th scope="col" className="px-3 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {departments.data.map((department) => (
                                            <tr key={department.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                <td className="px-3 sm:px-6 py-4">
                                                    <div className="flex items-center">
                                                        <BuildingOfficeIcon className="h-5 w-5 text-gray-400 mr-2 flex-shrink-0" />
                                                        <Link
                                                            href={route('departments.show', department.uuid)}
                                                            className="text-sm font-medium text-primary dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 hover:underline truncate max-w-[120px] sm:max-w-none"
                                                        >
                                                            {department.name}
                                                        </Link>
                                                    </div>
                                                </td>
                                                <td className="hidden sm:table-cell px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {department.code}
                                                </td>
                                                <td className="hidden md:table-cell px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {department.head_of_department_user
                                                        ? `${department.head_of_department_user.first_name} ${department.head_of_department_user.last_name}`
                                                        : 'N/A'}
                                                </td>
                                                <td className="hidden sm:table-cell px-3 sm:px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center text-sm text-gray-900 dark:text-white">
                                                        <UserIcon className="h-4 w-4 mr-2 text-gray-400" />
                                                        {department.users_count || 0}
                                                    </div>
                                                </td>
                                                <td className="hidden lg:table-cell px-3 sm:px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center text-sm text-gray-900 dark:text-white">
                                                        <BanknotesIcon className="h-4 w-4 mr-2 text-gray-400" />
                                                        {formatBudget(department.budget)}
                                                    </div>
                                                </td>
                                                <td className="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                    {department.is_active ? (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                            <CheckCircleIcon className="h-4 w-4 mr-1" />
                                                            Actif
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                            <XCircleIcon className="h-4 w-4 mr-1" />
                                                            Inactif
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center justify-end gap-1 sm:gap-2">
                                                        <Link
                                                            href={route('departments.show', department.uuid)}
                                                            className="text-primary dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                            title="Voir détails"
                                                        >
                                                            <EyeIcon className="h-5 w-5" />
                                                        </Link>
                                                        {canEditDepartments && (
                                                            <Link
                                                                href={route('departments.edit', department.uuid)}
                                                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                                title="Modifier"
                                                            >
                                                                <PencilIcon className="h-5 w-5" />
                                                            </Link>
                                                        )}
                                                        {canDeleteDepartments && (
                                                            <button
                                                                onClick={() => handleDelete(department)}
                                                                className="text-gray-400 hover:text-red-600"
                                                                title="Supprimer"
                                                            >
                                                                <TrashIcon className="h-5 w-5" />
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* Grid View */}
                    {viewMode === 'grid' && (
                        <div className="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                            {departments.data.map((department) => (
                                <div
                                    key={department.id}
                                    className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden"
                                >
                                    <div className="p-4 sm:p-6">
                                        <div className="flex items-start justify-between mb-3 sm:mb-4">
                                            <div className="flex-1">
                                                <div className="flex items-center mb-2">
                                                    <BuildingOfficeIcon className="h-5 w-5 text-gray-400 mr-2" />
                                                    <Link
                                                        href={route('departments.show', department.uuid)}
                                                        className="text-lg font-semibold text-primary dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 hover:underline"
                                                    >
                                                        {department.name}
                                                    </Link>
                                                </div>
                                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                    Code: {department.code}
                                                </p>
                                            </div>

                                            <div className="flex items-center">
                                                {department.is_active ? (
                                                    <CheckCircleIcon className="h-5 w-5 text-green-500" title="Actif" />
                                                ) : (
                                                    <XCircleIcon className="h-5 w-5 text-red-500" title="Inactif" />
                                                )}
                                            </div>
                                        </div>

                                        {department.description && (
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                                {department.description}
                                            </p>
                                        )}

                                        {/* Department Stats */}
                                        <div className="space-y-2 mb-4">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-gray-500 dark:text-gray-400 flex items-center">
                                                    <UserIcon className="h-4 w-4 mr-1" />
                                                    Membres:
                                                </span>
                                                <span className="font-medium text-gray-900 dark:text-white">
                                                    {department.users_count || 0}
                                                </span>
                                            </div>

                                            {department.head_of_department_user && (
                                                <div className="flex items-center justify-between text-sm">
                                                    <span className="text-gray-500 dark:text-gray-400">Chef:</span>
                                                    <span className="font-medium text-gray-900 dark:text-white">
                                                        {department.head_of_department_user.first_name} {department.head_of_department_user.last_name}
                                                    </span>
                                                </div>
                                            )}

                                            {department.budget && (
                                                <div className="flex items-center justify-between text-sm">
                                                    <span className="text-gray-500 dark:text-gray-400 flex items-center">
                                                        <BanknotesIcon className="h-4 w-4 mr-1" />
                                                        Budget:
                                                    </span>
                                                    <span className="font-medium text-gray-900 dark:text-white">
                                                        {formatBudget(department.budget)}
                                                    </span>
                                                </div>
                                            )}

                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-gray-500 dark:text-gray-400">Créé le:</span>
                                                <span className="text-gray-900 dark:text-white">
                                                    {formatDate(department.created_at)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Actions */}
                                    <div className="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                                        <div className="flex items-center justify-between">
                                            <Link
                                                href={route('departments.show', department.uuid)}
                                                className="inline-flex items-center text-sm text-primary dark:text-blue-400 hover:text-primary dark:hover:text-blue-300"
                                            >
                                                <EyeIcon className="h-4 w-4 mr-1" />
                                                Voir détails
                                            </Link>

                                            {(canEditDepartments || canDeleteDepartments) && (
                                                <div className="flex space-x-2">
                                                    {canEditDepartments && (
                                                        <Link
                                                            href={route('departments.edit', department.uuid)}
                                                            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                            title="Modifier"
                                                        >
                                                            <PencilIcon className="h-4 w-4" />
                                                        </Link>
                                                    )}
                                                    {canDeleteDepartments && (
                                                        <button
                                                            onClick={() => handleDelete(department)}
                                                            className="text-gray-400 hover:text-red-600"
                                                            title="Supprimer"
                                                        >
                                                            <TrashIcon className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Empty State */}
                    {departments.data.length === 0 && (
                        <div className="text-center py-12">
                            <BuildingOfficeIcon className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                Aucun département trouvé
                            </h3>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {selectedStatus 
                                    ? 'Aucun département ne correspond aux critères sélectionnés.'
                                    : 'Commencez par créer votre premier département.'
                                }
                            </p>
                            {canCreateDepartments && !selectedStatus && (
                                <div className="mt-6">
                                    <Link
                                        href={route('departments.create')}
                                        className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                                    >
                                        <PlusIcon className="h-5 w-5 mr-2" />
                                        Nouveau département
                                    </Link>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Pagination */}
                    {departments.data.length > 0 && departments.meta?.last_page > 1 && (
                        <div className="mt-8 flex justify-center">
                            <nav className="flex space-x-2">
                                {departments.links.map((link, index) => {
                                    if (!link.url) {
                                        return (
                                            <span
                                                key={index}
                                                className="px-3 py-2 text-sm font-medium rounded-lg cursor-not-allowed opacity-50 text-gray-500 dark:text-gray-400"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        );
                                    }

                                    return (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            preserveState
                                            className={`px-3 py-2 text-sm font-medium rounded-lg ${
                                                link.active
                                                    ? 'bg-primary text-white'
                                                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                })}
                            </nav>
                        </div>
                    )}

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={confirmDelete}
                title="Supprimer le département"
                description={`Êtes-vous sûr de vouloir supprimer le département "${departmentToDelete?.name}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
};

export default Index;