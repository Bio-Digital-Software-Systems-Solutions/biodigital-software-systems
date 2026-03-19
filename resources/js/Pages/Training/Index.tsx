import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { PlusIcon, EyeIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon, XMarkIcon, Squares2X2Icon, ListBulletIcon, TableCellsIcon, LockClosedIcon } from '@heroicons/react/24/outline';
import { useState, useEffect } from 'react';
import { useDebouncedCallback } from 'use-debounce';
import { PageProps } from '@/Types';
import { isAdmin } from '@/Enums/Role';
import { userHasPermission } from '@/Enums/Permission';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { toast } from 'sonner';

interface Training {
    id: number;
    uuid: string;
    title: string;
    description: string;
    duration: string;
    level: string;
    price: string;
    category: string;
    rating: string;
    students_count: number;
    is_active: boolean;
    visibility: 'public' | 'private';
    created_at: string;
}

interface Props extends PageProps {
    trainings: {
        data: Training[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        level?: string;
        category?: string;
        visibility?: string;
    };
}

export default function Index({ trainings, filters }: Props) {
    const { auth } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search || '');
    const [level, setLevel] = useState(filters.level || '');
    const [category, setCategory] = useState(filters.category || '');
    const [visibility, setVisibility] = useState(filters.visibility || '');
    const [viewMode, setViewMode] = useState<'table' | 'list' | 'grid'>('table');
    const [trainingToDelete, setTrainingToDelete] = useState<Training | null>(null);

    // Permission checks
    const canCreateTrainings = userHasPermission(auth.user, 'create trainings');
    const canEditTrainings = userHasPermission(auth.user, 'edit trainings');
    const canDeleteTrainings = userHasPermission(auth.user, 'delete trainings');

    const debouncedSearch = useDebouncedCallback((value: string) => {
        router.get('/trainings',
            { search: value, level, category, visibility },
            { preserveState: true, replace: true }
        );
    }, 300);

    const handleSearchChange = (value: string) => {
        setSearch(value);
        debouncedSearch(value);
    };

    const handleLevelChange = (value: string) => {
        setLevel(value);
        router.get('/trainings',
            { search, level: value, category, visibility },
            { preserveState: true, replace: true }
        );
    };

    const handleCategoryChange = (value: string) => {
        setCategory(value);
        router.get('/trainings',
            { search, level, category: value, visibility },
            { preserveState: true, replace: true }
        );
    };

    const handleVisibilityChange = (value: string) => {
        setVisibility(value);
        router.get('/trainings',
            { search, level, category, visibility: value },
            { preserveState: true, replace: true }
        );
    };

    const clearFilters = () => {
        setSearch('');
        setLevel('');
        setCategory('');
        setVisibility('');
        router.get('/trainings', {}, { preserveState: true, replace: true });
    };

    const hasActiveFilters = search || level || category || visibility;

    const handleDelete = (training: Training) => {
        setTrainingToDelete(training);
    };

    const confirmDelete = () => {
        if (!trainingToDelete) return;

        router.delete(`/trainings/${trainingToDelete.uuid}`, {
            onSuccess: () => {
                toast.success('Formation supprimée avec succès');
                setTrainingToDelete(null);
            },
            onError: (errors) => {
                toast.error('Erreur lors de la suppression');
                console.error(errors);
            },
        });
    };

    return (
        <DashboardLayout
            title="Gestion des Formations"
            description="Gérez et consultez toutes les formations disponibles"
            actions={
                canCreateTrainings ? (
                    <Button asChild className="px-2 sm:px-4 text-sm">
                        <Link href="/trainings/create">
                            <PlusIcon className="h-4 w-4 sm:mr-2" />
                            <span className="hidden sm:inline">Nouvelle Formation</span>
                        </Link>
                    </Button>
                ) : undefined
            }
        >
            <Head title="Formations - Administration" />

            {/* Filters */}
                            <div className="mb-4 sm:mb-6 space-y-3 sm:space-y-4">
                                <div className="flex flex-col sm:flex-row gap-2 sm:gap-4">
                                    {/* Search */}
                                    <div className="flex-1 relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            type="text"
                                            placeholder="Rechercher..."
                                            value={search}
                                            onChange={(e) => handleSearchChange(e.target.value)}
                                            className="block w-full pl-10 pr-10 py-2 sm:py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm sm:text-base"
                                        />
                                        {search && (
                                            <button
                                                onClick={() => handleSearchChange('')}
                                                className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                            >
                                                <XMarkIcon className="h-5 w-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
                                            </button>
                                        )}
                                    </div>

                                    {/* Level Filter */}
                                    <select
                                        value={level}
                                        onChange={(e) => handleLevelChange(e.target.value)}
                                        className="w-full sm:w-[180px] flex-shrink-0 px-3 sm:px-4 py-2 sm:py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm sm:text-base"
                                    >
                                        <option value="">Tous niveaux</option>
                                        <option value="beginner">Débutant</option>
                                        <option value="intermediate">Intermédiaire</option>
                                        <option value="advanced">Avancé</option>
                                    </select>

                                    {/* Category Filter */}
                                    <input
                                        type="text"
                                        placeholder="Catégorie..."
                                        value={category}
                                        onChange={(e) => handleCategoryChange(e.target.value)}
                                        className="w-full sm:w-[180px] flex-shrink-0 px-3 sm:px-4 py-2 sm:py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm sm:text-base"
                                    />

                                    {/* Visibility Filter */}
                                    <select
                                        value={visibility}
                                        onChange={(e) => handleVisibilityChange(e.target.value)}
                                        title="Filtrer par visibilit&eacute;"
                                        className="w-full sm:w-[160px] flex-shrink-0 px-3 sm:px-4 py-2 sm:py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm sm:text-base"
                                    >
                                        <option value="">Toutes</option>
                                        <option value="public">Public</option>
                                        <option value="private">Priv&eacute;</option>
                                    </select>

                                    <div className="flex items-center gap-2">
                                        {/* Clear Filters */}
                                        {hasActiveFilters && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={clearFilters}
                                                className="whitespace-nowrap"
                                            >
                                                <XMarkIcon className="h-4 w-4 sm:mr-2" />
                                                <span className="hidden sm:inline">Réinitialiser</span>
                                            </Button>
                                        )}

                                        {/* View Mode Selector */}
                                        <div className="flex items-center gap-0.5 border border-gray-300 dark:border-gray-600 rounded-lg p-0.5">
                                            <Button
                                                variant={viewMode === 'table' ? 'default' : 'ghost'}
                                                size="sm"
                                                onClick={() => setViewMode('table')}
                                                className="h-8 w-8 sm:h-9 sm:w-9 p-0"
                                                title="Vue tableau"
                                            >
                                                <TableCellsIcon className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant={viewMode === 'list' ? 'default' : 'ghost'}
                                                size="sm"
                                                onClick={() => setViewMode('list')}
                                                className="h-8 w-8 sm:h-9 sm:w-9 p-0"
                                                title="Vue liste"
                                            >
                                                <ListBulletIcon className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant={viewMode === 'grid' ? 'default' : 'ghost'}
                                                size="sm"
                                                onClick={() => setViewMode('grid')}
                                                className="h-8 w-8 sm:h-9 sm:w-9 p-0"
                                                title="Vue grille"
                                            >
                                                <Squares2X2Icon className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                {/* Active Filters Display */}
                                {hasActiveFilters && (
                                    <div className="flex flex-wrap gap-2 items-center text-sm">
                                        <span className="text-gray-600 dark:text-gray-400">Filtres actifs:</span>
                                        {search && (
                                            <span className="inline-flex items-center gap-1 px-3 py-1 bg-violet-100 dark:bg-violet-900/30 text-violet-800 dark:text-violet-300 rounded-full">
                                                Recherche: "{search}"
                                                <button onClick={() => handleSearchChange('')} className="hover:bg-violet-200 dark:hover:bg-violet-800/50 rounded-full p-0.5">
                                                    <XMarkIcon className="h-3 w-3" />
                                                </button>
                                            </span>
                                        )}
                                        {level && (
                                            <span className="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full">
                                                Niveau: {level === 'beginner' ? 'Débutant' : level === 'intermediate' ? 'Intermédiaire' : 'Avancé'}
                                                <button onClick={() => handleLevelChange('')} className="hover:bg-blue-200 dark:hover:bg-blue-800/50 rounded-full p-0.5">
                                                    <XMarkIcon className="h-3 w-3" />
                                                </button>
                                            </span>
                                        )}
                                        {category && (
                                            <span className="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full">
                                                Cat&eacute;gorie: {category}
                                                <button onClick={() => handleCategoryChange('')} className="hover:bg-green-200 dark:hover:bg-green-800/50 rounded-full p-0.5">
                                                    <XMarkIcon className="h-3 w-3" />
                                                </button>
                                            </span>
                                        )}
                                        {visibility && (
                                            <span className="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded-full">
                                                Visibilit&eacute;: {visibility === 'public' ? 'Public' : 'Priv\u00e9'}
                                                <button onClick={() => handleVisibilityChange('')} className="hover:bg-amber-200 dark:hover:bg-amber-800/50 rounded-full p-0.5">
                                                    <XMarkIcon className="h-3 w-3" />
                                                </button>
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Trainings Display */}
                            {viewMode === 'table' && (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-900">
                                            <tr>
                                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Formation
                                                </th>
                                                <th className="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Niveau
                                                </th>
                                                <th className="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Prix
                                                </th>
                                                <th className="hidden md:table-cell px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Étudiants
                                                </th>
                                                <th className="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-3 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            {trainings.data.map((training) => (
                                                <tr key={training.id}>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                        <Link
                                                            href={route('trainings.show', training.uuid)}
                                                            className="text-sm font-medium text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300 truncate max-w-[150px] sm:max-w-none block"
                                                        >
                                                            {training.title}
                                                        </Link>
                                                        <div className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                                            {training.category}
                                                        </div>
                                                    </td>
                                                    <td className="hidden sm:table-cell px-3 sm:px-6 py-4 whitespace-nowrap">
                                                        <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                            {training.level}
                                                        </span>
                                                    </td>
                                                    <td className="hidden sm:table-cell px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        {training.price} €
                                                    </td>
                                                    <td className="hidden md:table-cell px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        {training.students_count}
                                                    </td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                        <div className="flex flex-col gap-1">
                                                            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full w-fit ${
                                                                training.is_active
                                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                                            }`}>
                                                                {training.is_active ? 'Actif' : 'Inactif'}
                                                            </span>
                                                            <span className={`px-2 inline-flex items-center gap-1 text-xs leading-5 font-semibold rounded-full w-fit ${
                                                                training.visibility === 'private'
                                                                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200'
                                                                    : 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200'
                                                            }`}>
                                                                {training.visibility === 'private' && <LockClosedIcon className="h-3 w-3" />}
                                                                {training.visibility === 'private' ? 'Priv\u00e9' : 'Public'}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td className="px-3 sm:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex justify-end items-center gap-2 sm:gap-3">
                                                            <Link
                                                                href={route('trainings.show', training.uuid)}
                                                                className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                                title="Voir"
                                                            >
                                                                <EyeIcon className="h-5 w-5" />
                                                            </Link>
                                                            {canEditTrainings && training.visibility === 'private' && (
                                                                <Link
                                                                    href={route('trainings.access', training.uuid)}
                                                                    className="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300"
                                                                    title="G&eacute;rer les acc&egrave;s"
                                                                >
                                                                    <LockClosedIcon className="h-5 w-5" />
                                                                </Link>
                                                            )}
                                                            {canEditTrainings && (
                                                                <Link
                                                                    href={route('trainings.edit', training.uuid)}
                                                                    className="text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300"
                                                                    title="Modifier"
                                                                >
                                                                    <PencilIcon className="h-5 w-5" />
                                                                </Link>
                                                            )}
                                                            {canDeleteTrainings && (
                                                                <button
                                                                    onClick={() => handleDelete(training)}
                                                                    className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
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
                            )}

                            {/* List View */}
                            {viewMode === 'list' && (
                                <div className="space-y-3">
                                    {trainings.data.map((training) => (
                                        <div key={training.id} className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 sm:p-4 hover:shadow-md transition-shadow">
                                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                                                <div className="flex-1 min-w-0">
                                                    <Link
                                                        href={route('trainings.show', training.uuid)}
                                                        className="text-base sm:text-lg font-semibold text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300 truncate block"
                                                    >
                                                        {training.title}
                                                    </Link>
                                                    <div className="mt-1 flex flex-wrap items-center gap-2 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                                        <span className="truncate max-w-[100px] sm:max-w-none">{training.category}</span>
                                                        <span className="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-medium">
                                                            {training.level}
                                                        </span>
                                                        <span className="font-medium">{training.price} €</span>
                                                        <span className="hidden sm:inline">{training.students_count} étudiants</span>
                                                        <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                                                            training.is_active
                                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                                        }`}>
                                                            {training.is_active ? 'Actif' : 'Inactif'}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2 sm:gap-3 sm:ml-4">
                                                    <Link
                                                        href={route('trainings.show', training.uuid)}
                                                        className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                        title="Voir"
                                                    >
                                                        <EyeIcon className="h-5 w-5" />
                                                    </Link>
                                                    {canEditTrainings && (
                                                        <Link
                                                            href={route('trainings.edit', training.uuid)}
                                                            className="text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300"
                                                            title="Modifier"
                                                        >
                                                            <PencilIcon className="h-5 w-5" />
                                                        </Link>
                                                    )}
                                                    {canDeleteTrainings && (
                                                        <button
                                                            onClick={() => handleDelete(training)}
                                                            className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                            title="Supprimer"
                                                        >
                                                            <TrashIcon className="h-5 w-5" />
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Grid View */}
                            {viewMode === 'grid' && (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                                    {trainings.data.map((training) => (
                                        <div key={training.id} className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:shadow-lg transition-all">
                                            <div className="flex items-start justify-between mb-3">
                                                <Link
                                                    href={route('trainings.show', training.uuid)}
                                                    className="text-lg font-semibold text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300 line-clamp-2 flex-1"
                                                >
                                                    {training.title}
                                                </Link>
                                                <span className={`px-2 py-1 rounded-full text-xs font-medium ml-2 ${
                                                    training.is_active
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                                }`}>
                                                    {training.is_active ? 'Actif' : 'Inactif'}
                                                </span>
                                            </div>
                                            <div className="space-y-2 mb-4">
                                                <div className="text-sm text-gray-600 dark:text-gray-400">{training.category}</div>
                                                <div className="flex items-center justify-between">
                                                    <span className="px-2.5 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-medium">
                                                        {training.level}
                                                    </span>
                                                    <span className="text-lg font-bold text-gray-900 dark:text-white">{training.price} €</span>
                                                </div>
                                                <div className="text-sm text-gray-600 dark:text-gray-400">{training.students_count} étudiants</div>
                                            </div>
                                            <div className="flex justify-end items-center gap-3 pt-3 border-t dark:border-gray-700">
                                                <Link
                                                    href={route('trainings.show', training.uuid)}
                                                    className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                    title="Voir"
                                                >
                                                    <EyeIcon className="h-5 w-5" />
                                                </Link>
                                                {canEditTrainings && (
                                                    <Link
                                                        href={route('trainings.edit', training.uuid)}
                                                        className="text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300"
                                                        title="Modifier"
                                                    >
                                                        <PencilIcon className="h-5 w-5" />
                                                    </Link>
                                                )}
                                                {canDeleteTrainings && (
                                                    <button
                                                        onClick={() => handleDelete(training)}
                                                        className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                        title="Supprimer"
                                                    >
                                                        <TrashIcon className="h-5 w-5" />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                {/* Pagination */}
                {trainings.last_page > 1 && (
                    <div className="mt-6 flex justify-center gap-2">
                        {Array.from({ length: trainings.last_page }, (_, i) => i + 1).map((page) => (
                            <Link
                                key={page}
                                href={`/trainings?page=${page}`}
                                className={`px-4 py-2 rounded-lg ${
                                    page === trainings.current_page
                                        ? 'bg-violet-600 text-white'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                                }`}
                            >
                                {page}
                            </Link>
                        ))}
                    </div>
                )}

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={trainingToDelete !== null}
                onOpenChange={(open) => !open && setTrainingToDelete(null)}
                onConfirm={confirmDelete}
                title="Supprimer la formation"
                description={`Êtes-vous sûr de vouloir supprimer la formation "${trainingToDelete?.title}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
}
