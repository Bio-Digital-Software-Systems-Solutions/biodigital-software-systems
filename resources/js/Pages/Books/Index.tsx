import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import ViewSwitcher from '@/Components/ViewSwitcher';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { toast } from 'sonner';
import axios from 'axios';
import { apiLogger } from '@/utils/logger';
import {
    PlusIcon,
    BookOpenIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    TagIcon
} from '@heroicons/react/24/outline';

type ViewMode = 'grid' | 'list';

interface Book {
    id: number;
    uuid: string;
    title: string;
    author: string;
    isbn?: string;
    description?: string;
    cover_image?: string;
    rental_price?: number;
    max_rental_days: number;
    stock_quantity: number;
    category: {
        id: number;
        name: string;
    } | null;
    libraries: Array<{
        id: number;
        name: string;
    }>;
    created_at: string;
    updated_at: string;
}

interface Category {
    id: number;
    name: string;
}

interface BooksPageProps extends PageProps {
    books: {
        data: Book[];
        links: any[];
        meta: any;
    };
    categories: Category[];
    filters: {
        search?: string;
        category?: string;
    };
}

export default function Index() {
    const { books, categories, filters, auth } = usePage<BooksPageProps>().props;
    const [search, setSearch] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || '');
    const [viewMode, setViewMode] = useState<ViewMode>('grid');
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [bookToDelete, setBookToDelete] = useState<string | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/books', { search, category: selectedCategory }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedCategory('');
        router.get('/books', {}, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDeleteClick = (bookUuid: string) => {
        setBookToDelete(bookUuid);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = async () => {
        if (!bookToDelete) return;

        setIsDeleting(true);
        try {
            await axios.delete(`/books/${bookToDelete}`);

            // Refresh the page to show updated list
            router.reload({ only: ['books'] });

            setDeleteDialogOpen(false);
            setBookToDelete(null);

            toast.success('Livre supprimé avec succès', {
                description: 'Le livre a été supprimé de la bibliothèque.',
            });
        } catch (error) {
            apiLogger.error('Error deleting book', error);
            toast.error('Erreur lors de la suppression', {
                description: 'Une erreur est survenue lors de la suppression du livre.',
            });
        } finally {
            setIsDeleting(false);
        }
    };

    const canManageLibrary = auth.user?.permissions?.includes('manage library') ||
                            auth.user?.roles?.includes('admin');

    const canRentBooks = auth.user?.permissions?.includes('rent books');

    return (
        <DashboardLayout
            title="Bibliothèque"
            description="Découvrez et louez des livres de votre organisation"
            actions={
                <>
                    <ViewSwitcher currentView={viewMode} onViewChange={setViewMode} />
                    {canManageLibrary && (
                        <Link
                            href={route('books.create')}
                            className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                        >
                            <PlusIcon className="h-5 w-5 mr-2" />
                            Ajouter un livre
                        </Link>
                    )}
                </>
            }
        >
            <Head title="Livres - AIG-App" />

            {/* Filters */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <form onSubmit={handleSearch} className="space-y-4 sm:space-y-0 sm:flex sm:items-end sm:space-x-4">
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
                                    placeholder="Titre, auteur ou ISBN..."
                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                />
                                <MagnifyingGlassIcon className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                            </div>
                        </div>

                        <div className="sm:w-64">
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

                        <div className="flex space-x-2">
                            <button
                                type="submit"
                                className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                            >
                                <FunnelIcon className="h-5 w-5 mr-2" />
                                Filtrer
                            </button>
                            {(search || selectedCategory) && (
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition duration-200"
                                >
                                    Effacer
                                </button>
                            )}
                        </div>
                    </form>
                </div>

                {/* List View */}
                {viewMode === 'list' && (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Titre
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Auteur
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Catégorie
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            ISBN
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Stock
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Prix/Durée
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {books.data.map((book) => (
                                        <tr key={book.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    <BookOpenIcon className="h-5 w-5 text-gray-400 mr-2 flex-shrink-0" />
                                                    <Link
                                                        href={route('books.show', book.uuid)}
                                                        className="text-sm font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 transition-colors"
                                                    >
                                                        {book.title}
                                                    </Link>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {book.author}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {book.category ? (
                                                    <div className="flex items-center text-sm text-gray-900 dark:text-white">
                                                        <TagIcon className="h-4 w-4 mr-1 text-gray-400" />
                                                        {book.category.name}
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-gray-500 dark:text-gray-400">N/A</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {book.isbn || 'N/A'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {book.stock_quantity > 0 ? (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        Disponible ({book.stock_quantity})
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                        Indisponible
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {book.rental_price ? `${book.rental_price}€/jour` : 'Gratuit'}
                                                <br />
                                                <span className="text-xs text-gray-500 dark:text-gray-400">Max: {book.max_rental_days}j</span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link
                                                        href={route('books.show', book.uuid)}
                                                        className="text-primary dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                        title="Voir détails"
                                                    >
                                                        <EyeIcon className="h-5 w-5" />
                                                    </Link>
                                                    {canManageLibrary && (
                                                        <>
                                                            <Link
                                                                href={route('books.edit', book.uuid)}
                                                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                                title="Modifier"
                                                            >
                                                                <PencilIcon className="h-5 w-5" />
                                                            </Link>
                                                            <button
                                                                onClick={() => handleDeleteClick(book.uuid)}
                                                                className="text-gray-400 hover:text-red-600"
                                                                title="Supprimer"
                                                            >
                                                                <TrashIcon className="h-5 w-5" />
                                                            </button>
                                                        </>
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
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {books.data.map((book) => (
                            <div
                                key={book.id}
                                className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden"
                            >
                                {/* Cover Image */}
                                {book.cover_image && (
                                    <div className="relative h-48 bg-gray-100 dark:bg-gray-700">
                                        <img
                                            src={book.cover_image.startsWith('http://') || book.cover_image.startsWith('https://')
                                                ? book.cover_image
                                                : `/storage/${book.cover_image}`
                                            }
                                            alt={`Couverture de ${book.title}`}
                                            className="w-full h-full object-cover"
                                        />
                                    </div>
                                )}

                                {/* Book Header */}
                                <div className="p-6">
                                    <div className="flex items-start justify-between mb-3">
                                        <div className="flex-1">
                                            <Link
                                                href={route('books.show', book.uuid)}
                                                className="text-lg font-semibold text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 transition-colors line-clamp-2 block"
                                            >
                                                {book.title}
                                            </Link>
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                par {book.author}
                                            </p>
                                        </div>
                                        {book.stock_quantity > 0 ? (
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Disponible ({book.stock_quantity})
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                Indisponible
                                            </span>
                                        )}
                                    </div>

                                    {book.category && (
                                        <div className="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-2">
                                            <TagIcon className="h-4 w-4 mr-1" />
                                            <span>{book.category.name}</span>
                                        </div>
                                    )}

                                    {book.isbn && (
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                            ISBN: {book.isbn}
                                        </p>
                                    )}

                                    {book.description && (
                                        <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-3 mb-3">
                                            {book.description}
                                        </p>
                                    )}

                                    {/* Book Details */}
                                    <div className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                        {book.rental_price && (
                                            <div>
                                                <span className="font-medium">Prix de location:</span> {book.rental_price}€/jour
                                            </div>
                                        )}
                                        <div>
                                            <span className="font-medium">Durée max:</span> {book.max_rental_days} jours
                                        </div>
                                    </div>
                                </div>

                                {/* Book Actions */}
                                <div className="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                                    <div className="flex items-center justify-between">
                                        <Link
                                            href={route('books.show', book.uuid)}
                                            className="inline-flex items-center text-sm text-primary dark:text-blue-400 hover:text-primary dark:hover:text-blue-300"
                                        >
                                            <EyeIcon className="h-4 w-4 mr-1" />
                                            Voir détails
                                        </Link>

                                        <div className="flex space-x-2">
                                            {canRentBooks && book.stock_quantity > 0 && (
                                                <Link
                                                    href={route('books.show', book.uuid)}
                                                    className="inline-flex items-center px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded transition duration-200"
                                                >
                                                    Louer
                                                </Link>
                                            )}

                                            {canManageLibrary && (
                                                <>
                                                    <Link
                                                        href={route('books.edit', book.uuid)}
                                                        className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                    >
                                                        <PencilIcon className="h-4 w-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDeleteClick(book.uuid)}
                                                        className="text-gray-400 hover:text-red-600"
                                                        title="Supprimer"
                                                    >
                                                        <TrashIcon className="h-4 w-4" />
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Empty State */}
                {books.data.length === 0 && (
                    <div className="text-center py-12">
                        <BookOpenIcon className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            Aucun livre trouvé
                        </h3>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {search || selectedCategory 
                                ? 'Aucun livre ne correspond à vos critères de recherche.'
                                : 'Commencez par ajouter votre premier livre.'
                            }
                        </p>
                        {canManageLibrary && !search && !selectedCategory && (
                            <div className="mt-6">
                                <Link
                                    href={route('books.create')}
                                    className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                                >
                                    <PlusIcon className="h-5 w-5 mr-2" />
                                    Ajouter un livre
                                </Link>
                            </div>
                        )}
                    </div>
                )}

                {/* Pagination */}
                {books.data.length > 0 && books.meta?.last_page > 1 && (
                    <div className="mt-8 flex justify-center">
                        <nav className="flex space-x-2">
                            {books.links.map((link, index) => (
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

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={handleDeleteConfirm}
                title="Êtes-vous sûr de vouloir supprimer ce livre ?"
                description="Cette action est irréversible. Le livre sera définitivement supprimé de la bibliothèque."
                isDeleting={isDeleting}
            />
        </DashboardLayout>
    );
}