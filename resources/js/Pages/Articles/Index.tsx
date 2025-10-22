import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import ViewSwitcher from '@/Components/ViewSwitcher';
import { PageProps } from '@/Types';
import {
    PlusIcon,
    DocumentTextIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    TagIcon,
    UserIcon,
    CalendarDaysIcon,
    HeartIcon
} from '@heroicons/react/24/outline';
import { debounce } from 'lodash';
import { userHasPermission } from '@/Enums/Permission';

type ViewMode = 'grid' | 'list' | 'calendar';

interface Article {
    id: number;
    uuid: string;
    slug: string;
    title: string;
    content: string;
    cover_image?: string;
    video_file?: string;
    published_at?: string;
    views_count?: number;
    likes_count?: number;
    category: {
        id: number;
        name: string;
    };
    user: {
        id: number;
        first_name: string;
        last_name: string;
    };
    tags: {
        id: number;
        name: string;
        color: string;
    }[];
    created_at: string;
    updated_at: string;
}

interface Category {
    id: number;
    name: string;
}

interface ArticlesPageProps extends PageProps {
    articles: {
        data: Article[];
        links: any[];
        meta: any;
    };
    categories: Category[];
    filters: {
        search?: string;
        category?: string;
        status?: string;
    };
}

export default function Index() {
    const { articles, categories, filters, auth } = usePage<ArticlesPageProps>().props;
    const [search, setSearch] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [viewMode, setViewMode] = useState<ViewMode>('grid');

    // Debounced search function
    const debouncedSearch = React.useMemo(
        () =>
            debounce((searchValue: string, category: string, status: string) => {
                router.get('/articles',
                    {
                        search: searchValue || undefined,
                        category: category || undefined,
                        status: status || undefined
                    },
                    {
                        preserveState: true,
                        replace: true,
                    }
                );
            }, 500),
        []
    );

    // Apply filters dynamically when they change
    useEffect(() => {
        debouncedSearch(search, selectedCategory, selectedStatus);

        return () => {
            debouncedSearch.cancel();
        };
    }, [search, selectedCategory, selectedStatus, debouncedSearch]);

    const clearFilters = () => {
        setSearch('');
        setSelectedCategory('');
        setSelectedStatus('');
    };

    const canCreateArticles = userHasPermission(auth.user, 'create articles');

    const canEditArticles = userHasPermission(auth.user, 'edit articles');

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'published':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'draft':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'published':
                return 'Publié';
            case 'draft':
                return 'Brouillon';
            default:
                return status;
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const truncateText = (text: string, maxLength: number) => {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    };

    return (
        <DashboardLayout
            title="Articles"
            description="Découvrez et partagez des articles avec votre organisation"
            actions={
                <>
                    <ViewSwitcher currentView={viewMode} onViewChange={(view) => setViewMode(view)} />
                    {canCreateArticles && (
                        <Link
                            href={route('articles.create')}
                            className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                        >
                            <PlusIcon className="h-5 w-5 mr-2" />
                            Nouvel article
                        </Link>
                    )}
                </>
            }
        >
            <Head title="Articles - AIG-App" />

            {/* Filters */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <div className="space-y-4 sm:space-y-0 sm:flex sm:items-end sm:space-x-4">
                        <div className="flex-1">
                            <label htmlFor="search" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Rechercher
                            </label>
                            <div className="relative">
                                <input
                                    type="text"
                                    id="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Titre, contenu ou extrait..."
                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                />
                                <MagnifyingGlassIcon className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                            </div>
                        </div>

                        <div className="sm:w-48">
                            <label htmlFor="category" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Catégorie
                            </label>
                            <select
                                id="category"
                                value={selectedCategory}
                                onChange={(e) => setSelectedCategory(e.target.value)}
                                className="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                            >
                                <option value="">Toutes les catégories</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {canEditArticles && (
                            <div className="sm:w-40">
                                <label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Statut
                                </label>
                                <select
                                    id="status"
                                    value={selectedStatus}
                                    onChange={(e) => setSelectedStatus(e.target.value)}
                                    className="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                >
                                    <option value="">Tous les statuts</option>
                                    <option value="published">Publié</option>
                                    <option value="draft">Brouillon</option>
                                </select>
                            </div>
                        )}

                        {(search || selectedCategory || selectedStatus) && (
                            <div className="flex items-end">
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition duration-200"
                                >
                                    Effacer
                                </button>
                            </div>
                        )}
                    </div>
                </div>

                {/* Articles List View */}
                {viewMode === 'list' && (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Article
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Auteur
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Catégorie
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Statistiques
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                {articles.data.map((article) => {
                                    const status = article.published_at ? 'published' : 'draft';
                                    return (
                                        <tr key={article.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    {article.cover_image && (
                                                        <img
                                                            src={`/storage/${article.cover_image}`}
                                                            alt={article.title}
                                                            className="h-10 w-10 rounded object-cover mr-3"
                                                        />
                                                    )}
                                                    <div className="max-w-md">
                                                        <Link
                                                            href={route('articles.show', article.slug)}
                                                            className="text-sm font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 truncate block"
                                                        >
                                                            {article.title}
                                                        </Link>
                                                        <div className="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">
                                                            {truncateText(article.content.replace(/<[^>]*>/g, ''), 60)}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center text-sm text-gray-900 dark:text-gray-300">
                                                    <UserIcon className="h-4 w-4 mr-1 text-gray-400" />
                                                    {article.user.first_name} {article.user.last_name}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {article.category.name}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(status)}`}>
                                                    {getStatusLabel(status)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                                    <div className="flex items-center">
                                                        <EyeIcon className="h-4 w-4 mr-1" />
                                                        {article.views_count || 0}
                                                    </div>
                                                    <div className="flex items-center">
                                                        <HeartIcon className="h-4 w-4 mr-1" />
                                                        {article.likes_count || 0}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <div className="flex items-center">
                                                    <CalendarDaysIcon className="h-4 w-4 mr-1" />
                                                    {formatDate(article.published_at || article.created_at)}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link
                                                        href={route('articles.show', article.slug)}
                                                        className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                        title="Voir"
                                                    >
                                                        <EyeIcon className="h-5 w-5" />
                                                    </Link>
                                                    {(auth.user?.id === article.user.id || canEditArticles) && (
                                                        <>
                                                            <Link
                                                                href={route('articles.edit', article.slug)}
                                                                className="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                                                title="Modifier"
                                                            >
                                                                <PencilIcon className="h-5 w-5" />
                                                            </Link>
                                                            <Link
                                                                href={route('articles.destroy', article.slug)}
                                                                method="delete"
                                                                as="button"
                                                                className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                                title="Supprimer"
                                                            >
                                                                <TrashIcon className="h-5 w-5" />
                                                            </Link>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>

                        {articles.data.length === 0 && (
                            <div className="text-center py-12">
                                <DocumentTextIcon className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun article</h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Aucun article ne correspond à vos critères de recherche.
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Articles Grid View */}
                {viewMode === 'grid' && (
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {articles.data.map((article) => (
                        <div
                            key={article.id}
                            className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden"
                        >
                            {/* Cover Image */}
                            {article.cover_image && (
                                <div className="aspect-video bg-gray-200 dark:bg-gray-700">
                                    <img
                                        src={`/storage/${article.cover_image}`}
                                        alt={article.title}
                                        className="w-full h-full object-cover"
                                    />
                                </div>
                            )}

                            {/* Article Content */}
                            <div className="p-6">
                                <div className="flex items-start justify-between mb-3">
                                    <div className="flex-1">
                                        <Link
                                            href={route('articles.show', article.slug)}
                                            className="text-lg font-semibold text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 line-clamp-2 block"
                                        >
                                            {article.title}
                                        </Link>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            par {article.user.first_name} {article.user.last_name}
                                        </p>
                                    </div>
                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${article.published_at ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'}`}>
                                        {article.published_at ? 'Publié' : 'Brouillon'}
                                    </span>
                                </div>

                                {article.category && (
                                    <div className="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-3">
                                        <TagIcon className="h-4 w-4 mr-1" />
                                        <span>{article.category.name}</span>
                                    </div>
                                )}

                                <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-3 mb-3">
                                    {truncateText(article.content, 150)}
                                </p>

                                {/* Article Metadata */}
                                <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
                                    <div className="flex items-center">
                                        <CalendarDaysIcon className="h-4 w-4 mr-1" />
                                        <span>
                                            {article.published_at
                                                ? `Publié le ${formatDate(article.published_at)}`
                                                : `Créé le ${formatDate(article.created_at)}`
                                            }
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <div className="flex items-center">
                                            <EyeIcon className="h-4 w-4 mr-1" />
                                            <span>{article.views_count || 0}</span>
                                        </div>
                                        <div className="flex items-center">
                                            <HeartIcon className="h-4 w-4 mr-1" />
                                            <span>{article.likes_count || 0}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Tags */}
                                {article.tags && article.tags.length > 0 && (
                                    <div className="flex flex-wrap gap-1 mb-4">
                                        {article.tags.map((tag) => (
                                            <span
                                                key={tag.id}
                                                className="inline-flex items-center px-2 py-0.5 rounded text-xs"
                                                style={{ backgroundColor: tag.color + '20', color: tag.color }}
                                            >
                                                {tag.name}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Article Actions */}
                            <div className="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                                <div className="flex items-center justify-between">
                                    <Link
                                        href={route('articles.show', article.slug)}
                                        className="inline-flex items-center text-sm text-primary dark:text-blue-400 hover:text-primary dark:hover:text-blue-300"
                                    >
                                        <EyeIcon className="h-4 w-4 mr-1" />
                                        Lire l'article
                                    </Link>

                                    {(auth.user?.id === article.user.id || canEditArticles) && (
                                        <div className="flex space-x-2">
                                            <Link
                                                href={route('articles.edit', article.slug)}
                                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            >
                                                <PencilIcon className="h-4 w-4" />
                                            </Link>
                                            <Link
                                                href={route('articles.destroy', article.slug)}
                                                method="delete"
                                                as="button"
                                                className="text-gray-400 hover:text-red-600"
                                                data-confirm="Êtes-vous sûr de vouloir supprimer cet article ?"
                                            >
                                                <TrashIcon className="h-4 w-4" />
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                        ))}
                    </div>
                )}

                {/* Empty State */}
                {viewMode === 'grid' && articles.data.length === 0 && (
                    <div className="text-center py-12">
                        <DocumentTextIcon className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            Aucun article trouvé
                        </h3>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {search || selectedCategory || selectedStatus
                                ? 'Aucun article ne correspond à vos critères de recherche.'
                                : 'Commencez par créer votre premier article.'
                            }
                        </p>
                        {canCreateArticles && !search && !selectedCategory && !selectedStatus && (
                            <div className="mt-6">
                                <Link
                                    href={route('articles.create')}
                                    className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                                >
                                    <PlusIcon className="h-5 w-5 mr-2" />
                                    Nouvel article
                                </Link>
                            </div>
                        )}
                    </div>
                )}

                {/* Pagination */}
                {articles.data.length > 0 && articles.meta?.last_page > 1 && (
                    <div className="mt-8 flex justify-center">
                        <nav className="flex space-x-2">
                            {articles.links.map((link, index) => (
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
}