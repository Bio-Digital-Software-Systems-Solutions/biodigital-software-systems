import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import {
    ArrowLeftIcon,
    BookOpenIcon,
    TagIcon,
    CalendarDaysIcon,
    CurrencyEuroIcon,
    BuildingOffice2Icon,
    UserIcon,
    ClockIcon,
    PencilIcon,
    TrashIcon
} from '@heroicons/react/24/outline';
import { userHasPermission } from '@/Enums/Permission';

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

interface ActiveRental {
    id: number;
    rental_date: string;
    due_date: string;
    rental_fee: number;
    user: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
    };
    library: {
        id: number;
        name: string;
    };
}

interface BookShowPageProps extends PageProps {
    book: Book;
    activeRentals: ActiveRental[];
    canRent: boolean;
}

export default function Show() {
    const { book, activeRentals, canRent, auth, flash } = usePage<BookShowPageProps>().props;
    const [showRentalForm, setShowRentalForm] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        library_id: '',
        rental_days: 1,
    });

    const canManageLibrary = userHasPermission(auth.user, 'manage library');

    const availableQuantity = book.stock_quantity - activeRentals.length;
    const canRentThisBook = canRent && availableQuantity > 0;

    const handleRent = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('books.rent', book.uuid), {
            onSuccess: () => {
                setShowRentalForm(false);
                setData({ library_id: '', rental_days: 1 });
            }
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const totalRentalCost = book.rental_price ? book.rental_price * data.rental_days : 0;

    return (
        <DashboardLayout>
            <Head title={`${book.title} - Livres - AIG-App`} />

            <div className="p-4">
                {/* Back Button */}
                <div className="mb-6">
                    <Link
                        href={route('books.index')}
                        className="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                        Retour à la bibliothèque
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Book Information */}
                    <div className="lg:col-span-2">
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                            {/* Cover Image */}
                            {book.cover_image && (
                                <div className="px-6 pt-6">
                                    <img
                                        src={book.cover_image.startsWith('http://') || book.cover_image.startsWith('https://')
                                            ? book.cover_image
                                            : `/storage/${book.cover_image}`
                                        }
                                        alt={`Couverture de ${book.title}`}
                                        className="w-full max-h-96 object-contain rounded-lg"
                                    />
                                </div>
                            )}

                            {/* Header */}
                            <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <div className="flex items-start justify-between">
                                    <div className="flex items-start space-x-3">
                                        <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                            <BookOpenIcon className="h-6 w-6 text-primary dark:text-blue-400" />
                                        </div>
                                        <div>
                                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                                {book.title}
                                            </h1>
                                            <p className="text-lg text-gray-600 dark:text-gray-400 mt-1">
                                                par {book.author}
                                            </p>
                                            {book.category && (
                                                <div className="flex items-center mt-2">
                                                    <TagIcon className="h-4 w-4 text-gray-400 mr-1" />
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">
                                                        {book.category.name}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Status Badge */}
                                    <div className="flex flex-col items-end space-y-2">
                                        {availableQuantity > 0 ? (
                                            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Disponible ({availableQuantity})
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                Indisponible
                                            </span>
                                        )}

                                        {canManageLibrary && (
                                            <div className="flex space-x-2">
                                                <Link
                                                    href={route('books.edit', book.uuid)}
                                                    className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-2"
                                                >
                                                    <PencilIcon className="h-5 w-5" />
                                                </Link>
                                                <Link
                                                    href={route('books.destroy', book.uuid)}
                                                    method="delete"
                                                    as="button"
                                                    className="text-gray-400 hover:text-red-600 p-2"
                                                    data-confirm="Êtes-vous sûr de vouloir supprimer ce livre ?"
                                                >
                                                    <TrashIcon className="h-5 w-5" />
                                                </Link>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Book Details */}
                            <div className="p-6">
                                {book.isbn && (
                                    <div className="mb-4">
                                        <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            ISBN: {book.isbn}
                                        </span>
                                    </div>
                                )}

                                {book.description && (
                                    <div className="mb-6">
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                            Description
                                        </h3>
                                        <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                                            {book.description}
                                        </p>
                                    </div>
                                )}

                                {/* Rental Information */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {book.rental_price && (
                                        <div className="flex items-center space-x-3">
                                            <CurrencyEuroIcon className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                    Prix de location
                                                </p>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    {book.rental_price}€ par jour
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex items-center space-x-3">
                                        <ClockIcon className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                Durée maximale
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {book.max_rental_days} jours
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center space-x-3">
                                        <BookOpenIcon className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                Stock total
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {book.stock_quantity} exemplaires
                                            </p>
                                        </div>
                                    </div>

                                    {book.libraries.length > 0 && (
                                        <div className="flex items-start space-x-3">
                                            <BuildingOffice2Icon className="h-5 w-5 text-gray-400 mt-0.5" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                    Disponible dans
                                                </p>
                                                <div className="text-sm text-gray-600 dark:text-gray-400">
                                                    {book.libraries.map((library, index) => (
                                                        <span key={library.id}>
                                                            {library.name}
                                                            {index < book.libraries.length - 1 && ', '}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Rental Section */}
                    <div className="space-y-6">
                        {/* Flash Messages */}
                        {flash?.success && (
                            <div className="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4">
                                <p className="text-green-800 dark:text-green-200 text-sm">{flash.success}</p>
                            </div>
                        )}
                        
                        {flash?.error && (
                            <div className="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4">
                                <p className="text-red-800 dark:text-red-200 text-sm">{flash.error}</p>
                            </div>
                        )}

                        {/* Rental Form */}
                        {canRentThisBook && (
                            <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Louer ce livre
                                </h3>

                                {!showRentalForm ? (
                                    <button
                                        onClick={() => setShowRentalForm(true)}
                                        className="w-full px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                                    >
                                        Commencer la location
                                    </button>
                                ) : (
                                    <form onSubmit={handleRent} className="space-y-4">
                                        {flash?.error && (
                                            <div className="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-3">
                                                <p className="text-red-800 dark:text-red-200 text-sm">{flash.error}</p>
                                            </div>
                                        )}
                                        
                                        {flash?.success && (
                                            <div className="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-3">
                                                <p className="text-green-800 dark:text-green-200 text-sm">{flash.success}</p>
                                            </div>
                                        )}
                                        
                                        {book.libraries.length > 0 && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Bibliothèque
                                                </label>
                                                <select
                                                    value={data.library_id}
                                                    onChange={(e) => setData('library_id', e.target.value)}
                                                    required
                                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                >
                                                    <option value="">Choisir une bibliothèque</option>
                                                    {book.libraries.map((library) => (
                                                        <option key={library.id} value={library.id}>
                                                            {library.name}
                                                        </option>
                                                    ))}
                                                </select>
                                                {errors.library_id && (
                                                    <p className="text-red-600 text-sm mt-1">{errors.library_id}</p>
                                                )}
                                            </div>
                                        )}

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Durée de location (jours)
                                            </label>
                                            <input
                                                type="number"
                                                min="1"
                                                max={book.max_rental_days}
                                                value={data.rental_days}
                                                onChange={(e) => setData('rental_days', parseInt(e.target.value) || 1)}
                                                required
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                            />
                                            {errors.rental_days && (
                                                <p className="text-red-600 text-sm mt-1">{errors.rental_days}</p>
                                            )}
                                        </div>

                                        {book.rental_price && (
                                            <div className="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    Coût total: <span className="font-semibold text-gray-900 dark:text-white">
                                                        {totalRentalCost}€
                                                    </span>
                                                </p>
                                            </div>
                                        )}

                                        <div className="flex space-x-2">
                                            <button
                                                type="submit"
                                                disabled={processing}
                                                className="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium rounded-lg transition duration-200"
                                            >
                                                {processing ? 'Location...' : 'Confirmer la location'}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setShowRentalForm(false)}
                                                className="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition duration-200"
                                            >
                                                Annuler
                                            </button>
                                        </div>
                                    </form>
                                )}
                            </div>
                        )}

                        {!canRent && (
                            <div className="bg-amber-50 dark:bg-amber-900 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                                <p className="text-amber-800 dark:text-amber-200 text-sm">
                                    Vous n'avez pas les permissions nécessaires pour louer des livres.
                                </p>
                            </div>
                        )}

                        {/* Active Rentals */}
                        {activeRentals.length > 0 && (
                            <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Locations en cours ({activeRentals.length})
                                </h3>

                                <div className="space-y-3">
                                    {activeRentals.map((rental) => (
                                        <div key={rental.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <div className="flex items-center space-x-2 mb-2">
                                                        <UserIcon className="h-4 w-4 text-gray-400" />
                                                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {rental.user.full_name}
                                                        </span>
                                                    </div>
                                                    <div className="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                                        <p>Loué le: {formatDate(rental.rental_date)}</p>
                                                        <p>À retourner le: {formatDate(rental.due_date)}</p>
                                                        <p>Bibliothèque: {rental.library.name}</p>
                                                        {rental.rental_fee > 0 && (
                                                            <p>Coût: {rental.rental_fee}€</p>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}