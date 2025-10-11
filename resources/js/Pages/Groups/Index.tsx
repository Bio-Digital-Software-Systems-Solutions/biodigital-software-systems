import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import ViewSwitcher from '@/Components/ViewSwitcher';
import {
    PlusIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    UsersIcon,
    FunnelIcon,
    UserGroupIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/react/24/outline';

type ViewMode = 'grid' | 'list';

interface User {
    id: number;
    first_name: string;
    last_name: string;
}

interface Group {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    code: string;
    max_members?: number;
    is_active: boolean;
    leader?: User;
    users: User[];
    members_count: number;
    created_at: string;
    updated_at: string;
}

interface IndexProps extends PageProps {
    groups: {
        data: Group[];
        links: any[];
        meta: any;
    };
    filters: {
        status?: string;
    };
}

const Index: React.FC<IndexProps> = ({ groups, filters }) => {
    const { auth } = usePage<PageProps>().props;
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [viewMode, setViewMode] = useState<ViewMode>('grid');

    const canCreateGroups = auth.user?.permissions?.includes('create groups') || 
                           auth.user?.roles?.includes('admin');
    const canEditGroups = auth.user?.permissions?.includes('edit groups') || 
                         auth.user?.roles?.includes('admin');
    const canDeleteGroups = auth.user?.permissions?.includes('delete groups') || 
                           auth.user?.roles?.includes('admin');

    const handleFilter = (status: string) => {
        setSelectedStatus(status);
        router.get('/groups', { status: status || undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (group: Group) => {
        if (confirm(`Êtes-vous sûr de vouloir supprimer le groupe "${group.name}" ?`)) {
            router.delete(route('groups.destroy', group.uuid));
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    return (
        <DashboardLayout
            title="Groupes"
            description="Gérez les groupes et leurs membres"
            actions={
                <>
                    <ViewSwitcher currentView={viewMode} onViewChange={setViewMode} />
                    {canCreateGroups && (
                        <Link
                            href={route('groups.create')}
                            className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                        >
                            <PlusIcon className="h-5 w-5 mr-2" />
                            Nouveau groupe
                        </Link>
                    )}
                </>
            }
        >
            <Head title="Groupes - AIG-App" />

            {/* Filters */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 mb-6">
                        <div className="flex items-center space-x-4">
                            <FunnelIcon className="h-5 w-5 text-gray-400" />
                            <div className="flex space-x-4">
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
                                <button
                                    onClick={() => handleFilter('with_space')}
                                    className={`px-3 py-1 rounded-md text-sm font-medium ${
                                        selectedStatus === 'with_space' 
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' 
                                            : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'
                                    }`}
                                >
                                    Avec places libres
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
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Groupe
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Code
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Leader
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Membres
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Capacité
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {groups.data.map((group) => (
                                            <tr key={group.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center">
                                                        <UserGroupIcon className="h-5 w-5 text-gray-400 mr-2 flex-shrink-0" />
                                                        <Link
                                                            href={route('groups.show', group.uuid)}
                                                            className="text-sm font-medium text-primary dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 hover:underline"
                                                        >
                                                            {group.name}
                                                        </Link>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {group.code}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {group.leader
                                                        ? `${group.leader.first_name} ${group.leader.last_name}`
                                                        : 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center text-sm text-gray-900 dark:text-white">
                                                        <UsersIcon className="h-4 w-4 mr-2 text-gray-400" />
                                                        {group.members_count}
                                                        {group.max_members && ` / ${group.max_members}`}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {group.max_members ? (
                                                        <div className="w-24">
                                                            <div className="flex justify-between items-center mb-1">
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                                    {Math.round((group.members_count / group.max_members) * 100)}%
                                                                </span>
                                                            </div>
                                                            <div className="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                                                <div
                                                                    className="bg-primary h-2 rounded-full"
                                                                    style={{ width: `${Math.min((group.members_count / group.max_members) * 100, 100)}%` }}
                                                                ></div>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm text-gray-500 dark:text-gray-400">Illimitée</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {group.is_active ? (
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
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Link
                                                            href={route('groups.show', group.uuid)}
                                                            className="text-primary dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                            title="Voir détails"
                                                        >
                                                            <EyeIcon className="h-5 w-5" />
                                                        </Link>
                                                        {canEditGroups && (
                                                            <Link
                                                                href={route('groups.edit', group.uuid)}
                                                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                                title="Modifier"
                                                            >
                                                                <PencilIcon className="h-5 w-5" />
                                                            </Link>
                                                        )}
                                                        {canDeleteGroups && (
                                                            <button
                                                                onClick={() => handleDelete(group)}
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
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {groups.data.map((group) => (
                                <div
                                    key={group.id}
                                    className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden"
                                >
                                    <div className="p-6">
                                        <div className="flex items-start justify-between mb-4">
                                            <div className="flex-1">
                                                <div className="flex items-center mb-2">
                                                    <UserGroupIcon className="h-5 w-5 text-gray-400 mr-2" />
                                                    <Link
                                                        href={route('groups.show', group.uuid)}
                                                        className="text-lg font-semibold text-primary dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 hover:underline"
                                                    >
                                                        {group.name}
                                                    </Link>
                                                </div>
                                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                    Code: {group.code}
                                                </p>
                                            </div>

                                            <div className="flex items-center">
                                                {group.is_active ? (
                                                    <CheckCircleIcon className="h-5 w-5 text-green-500" title="Actif" />
                                                ) : (
                                                    <XCircleIcon className="h-5 w-5 text-red-500" title="Inactif" />
                                                )}
                                            </div>
                                        </div>

                                        {group.description && (
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                                {group.description}
                                            </p>
                                        )}

                                        {/* Group Stats */}
                                        <div className="space-y-2 mb-4">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-gray-500 dark:text-gray-400">Membres:</span>
                                                <span className="font-medium text-gray-900 dark:text-white">
                                                    {group.members_count}
                                                    {group.max_members && ` / ${group.max_members}`}
                                                </span>
                                            </div>

                                            {group.leader && (
                                                <div className="flex items-center justify-between text-sm">
                                                    <span className="text-gray-500 dark:text-gray-400">Leader:</span>
                                                    <span className="font-medium text-gray-900 dark:text-white">
                                                        {group.leader.first_name} {group.leader.last_name}
                                                    </span>
                                                </div>
                                            )}

                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-gray-500 dark:text-gray-400">Créé le:</span>
                                                <span className="text-gray-900 dark:text-white">
                                                    {formatDate(group.created_at)}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Capacity Bar */}
                                        {group.max_members && (
                                            <div className="mb-4">
                                                <div className="flex justify-between items-center mb-1">
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">Capacité</span>
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        {Math.round((group.members_count / group.max_members) * 100)}%
                                                    </span>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                                    <div
                                                        className="bg-primary h-2 rounded-full"
                                                        style={{ width: `${Math.min((group.members_count / group.max_members) * 100, 100)}%` }}
                                                    ></div>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Actions */}
                                    <div className="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                                        <div className="flex items-center justify-between">
                                            <Link
                                                href={route('groups.show', group.uuid)}
                                                className="inline-flex items-center text-sm text-primary dark:text-blue-400 hover:text-primary dark:hover:text-blue-300"
                                            >
                                                <EyeIcon className="h-4 w-4 mr-1" />
                                                Voir détails
                                            </Link>

                                            {(canEditGroups || canDeleteGroups) && (
                                                <div className="flex space-x-2">
                                                    {canEditGroups && (
                                                        <Link
                                                            href={route('groups.edit', group.uuid)}
                                                            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                            title="Modifier"
                                                        >
                                                            <PencilIcon className="h-4 w-4" />
                                                        </Link>
                                                    )}
                                                    {canDeleteGroups && (
                                                        <button
                                                            onClick={() => handleDelete(group)}
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
                    {groups.data.length === 0 && (
                        <div className="text-center py-12">
                            <UserGroupIcon className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                Aucun groupe trouvé
                            </h3>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {selectedStatus 
                                    ? 'Aucun groupe ne correspond aux critères sélectionnés.'
                                    : 'Commencez par créer votre premier groupe.'
                                }
                            </p>
                            {canCreateGroups && !selectedStatus && (
                                <div className="mt-6">
                                    <Link
                                        href={route('groups.create')}
                                        className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                                    >
                                        <PlusIcon className="h-5 w-5 mr-2" />
                                        Nouveau groupe
                                    </Link>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Pagination */}
                    {groups.data.length > 0 && groups.meta?.last_page > 1 && (
                        <div className="mt-8 flex justify-center">
                            <nav className="flex space-x-2">
                                {groups.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-3 py-2 text-sm font-medium rounded-lg ${
                                            link.active
                                                ? 'bg-primary text-white'
                                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700'
                                        } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </nav>
                        </div>
                    )}
        </DashboardLayout>
    );
};

export default Index;