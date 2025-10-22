import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import {
    ArrowLeftIcon,
    BookOpenIcon,
    UserIcon,
    CalendarDaysIcon,
    ClockIcon,
    CurrencyEuroIcon,
    BuildingOffice2Icon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    TagIcon
} from '@heroicons/react/24/outline';
import { userHasPermission } from '@/Enums/Permission';

interface BookRental {
    id: number;
    rental_date: string;
    due_date: string;
    return_date?: string;
    rental_fee: number;
    late_fee: number;
    status: string;
    book: {
        id: number;
        title: string;
        author: string;
        isbn?: string;
        category?: {
            id: number;
            name: string;
        };
    };
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
    created_at: string;
    updated_at: string;
}

interface BookRentalShowPageProps extends PageProps {
    rental: BookRental;
}

export default function Show() {
    const { rental, auth } = usePage<BookRentalShowPageProps>().props;
    const [showExtendForm, setShowExtendForm] = useState(false);

    const isOwnRental = rental.user.id === auth.user?.id;
    const canManageLibrary = userHasPermission(auth.user, 'manage library');

    const { data: returnData, post: postReturn, processing: returningBook } = useForm({});
    const { data: extendData, setData: setExtendData, post: postExtend, processing: extendingRental, errors: extendErrors } = useForm({
        extension_days: 7,
    });

    const handleReturn = () => {
        postReturn(route('book-rentals.return', rental.id));
    };

    const handleExtend = (e: React.FormEvent) => {
        e.preventDefault();
        postExtend(route('book-rentals.extend', rental.id), {
            onSuccess: () => {
                setShowExtendForm(false);
                setExtendData('extension_days', 7);
            }
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatDateOnly = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const isOverdue = () => {
        return rental.status === 'active' && new Date(rental.due_date) < new Date();
    };

    const getStatusColor = () => {
        if (isOverdue()) {
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        }
        switch (rental.status) {
            case 'active':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'returned':
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        }
    };

    const getStatusLabel = () => {
        if (isOverdue()) {
            return 'En retard';
        }
        switch (rental.status) {
            case 'active':
                return 'En cours';
            case 'returned':
                return 'Retourné';
            default:
                return rental.status;
        }
    };

    const extensionCost = rental.book ? 2.5 * extendData.extension_days : 0; // Default rental price

    return (
        <DashboardLayout>
            <Head title={`Location de ${rental.book.title} - AIG-App`} />

            <div className="p-4">
                {/* Back Button */}
                <div className="mb-6">
                    <Link
                        href={isOwnRental ? route('book-rentals.index') : route('admin.book-rentals.index')}
                        className="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                        Retour aux locations
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Rental Details */}
                    <div className="lg:col-span-2">
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                            {/* Header */}
                            <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <div className="flex items-start justify-between">
                                    <div className="flex items-start space-x-3">
                                        <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                            <BookOpenIcon className="h-6 w-6 text-primary dark:text-blue-400" />
                                        </div>
                                        <div>
                                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                                {rental.book.title}
                                            </h1>
                                            <p className="text-lg text-gray-600 dark:text-gray-400 mt-1">
                                                par {rental.book.author}
                                            </p>
                                            {rental.book.category && (
                                                <div className="flex items-center mt-2">
                                                    <TagIcon className="h-4 w-4 text-gray-400 mr-1" />
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">
                                                        {rental.book.category.name}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusColor()}`}>
                                        {getStatusLabel()}
                                    </span>
                                </div>
                            </div>

                            {/* Rental Information */}
                            <div className="p-6">
                                {rental.book.isbn && (
                                    <div className="mb-6">
                                        <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            ISBN: {rental.book.isbn}
                                        </span>
                                    </div>
                                )}

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="flex items-center space-x-3">
                                        <UserIcon className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                Locataire
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {rental.user.full_name}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center space-x-3">
                                        <BuildingOffice2Icon className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                Bibliothèque
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {rental.library.name}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center space-x-3">
                                        <CalendarDaysIcon className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                Date de location
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {formatDateOnly(rental.rental_date)}
                                            </p>
                                        </div>
                                    </div>

                                    <div className={`flex items-center space-x-3 ${
                                        isOverdue() ? 'text-red-600 dark:text-red-400' : ''
                                    }`}>
                                        <ClockIcon className="h-5 w-5" />
                                        <div>
                                            <p className="text-sm font-medium">
                                                Date de retour prévue
                                            </p>
                                            <p className="text-sm">
                                                {formatDateOnly(rental.due_date)}
                                                {isOverdue() && (
                                                    <span className="ml-2 inline-flex items-center">
                                                        <ExclamationTriangleIcon className="h-4 w-4" />
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    {rental.return_date && (
                                        <div className="flex items-center space-x-3">
                                            <CheckCircleIcon className="h-5 w-5 text-green-500" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                    Date de retour
                                                </p>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    {formatDateOnly(rental.return_date)}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Financial Information */}
                                {(rental.rental_fee > 0 || rental.late_fee > 0) && (
                                    <div className="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                            Informations financières
                                        </h3>
                                        
                                        <div className="space-y-3">
                                            {rental.rental_fee > 0 && (
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-2">
                                                        <CurrencyEuroIcon className="h-4 w-4 text-gray-400" />
                                                        <span className="text-sm text-gray-600 dark:text-gray-400">
                                                            Frais de location
                                                        </span>
                                                    </div>
                                                    <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                        {rental.rental_fee}€
                                                    </span>
                                                </div>
                                            )}
                                            
                                            {rental.late_fee > 0 && (
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-2">
                                                        <ExclamationTriangleIcon className="h-4 w-4 text-red-400" />
                                                        <span className="text-sm text-red-600 dark:text-red-400">
                                                            Frais de retard
                                                        </span>
                                                    </div>
                                                    <span className="text-sm font-medium text-red-600 dark:text-red-400">
                                                        {rental.late_fee}€
                                                    </span>
                                                </div>
                                            )}
                                            
                                            <div className="border-t border-gray-200 dark:border-gray-700 pt-3">
                                                <div className="flex items-center justify-between">
                                                    <span className="text-base font-semibold text-gray-900 dark:text-white">
                                                        Total
                                                    </span>
                                                    <span className="text-base font-semibold text-gray-900 dark:text-white">
                                                        {(rental.rental_fee + rental.late_fee)}€
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Timeline */}
                                <div className="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                        Historique
                                    </h3>
                                    
                                    <div className="space-y-3">
                                        <div className="flex items-center space-x-3">
                                            <div className="w-2 h-2 bg-primary rounded-full"></div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                    Location créée
                                                </p>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    {formatDate(rental.created_at)}
                                                </p>
                                            </div>
                                        </div>
                                        
                                        {rental.return_date && (
                                            <div className="flex items-center space-x-3">
                                                <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                        Livre retourné
                                                    </p>
                                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                                        {formatDate(rental.return_date)}
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Actions Panel */}
                    <div className="space-y-6">
                        {/* Return Book */}
                        {rental.status === 'active' && (isOwnRental || canManageLibrary) && (
                            <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Retourner le livre
                                </h3>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Marquer ce livre comme retourné. Les frais de retard seront automatiquement calculés si applicable.
                                </p>
                                <button
                                    onClick={handleReturn}
                                    disabled={returningBook}
                                    className="w-full px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-medium rounded-lg transition duration-200"
                                >
                                    {returningBook ? 'Retour en cours...' : 'Confirmer le retour'}
                                </button>
                            </div>
                        )}

                        {/* Extend Rental */}
                        {rental.status === 'active' && isOwnRental && (
                            <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Prolonger la location
                                </h3>

                                {!showExtendForm ? (
                                    <>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                            Prolongez votre location si vous avez besoin de plus de temps avec ce livre.
                                        </p>
                                        <button
                                            onClick={() => setShowExtendForm(true)}
                                            className="w-full px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                                        >
                                            <ArrowPathIcon className="h-4 w-4 inline mr-2" />
                                            Prolonger
                                        </button>
                                    </>
                                ) : (
                                    <form onSubmit={handleExtend} className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Jours supplémentaires (1-14)
                                            </label>
                                            <input
                                                type="number"
                                                min="1"
                                                max="14"
                                                value={extendData.extension_days}
                                                onChange={(e) => setExtendData('extension_days', parseInt(e.target.value) || 7)}
                                                required
                                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                            />
                                            {extendErrors.extension_days && (
                                                <p className="text-red-600 text-sm mt-1">{extendErrors.extension_days}</p>
                                            )}
                                        </div>

                                        <div className="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                Coût estimé: <span className="font-semibold text-gray-900 dark:text-white">
                                                    {extensionCost}€
                                                </span>
                                            </p>
                                        </div>

                                        <div className="flex space-x-2">
                                            <button
                                                type="submit"
                                                disabled={extendingRental}
                                                className="flex-1 px-4 py-2 bg-primary hover:bg-primary disabled:bg-blue-400 text-white font-medium rounded-lg transition duration-200"
                                            >
                                                {extendingRental ? 'Prolongement...' : 'Confirmer'}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setShowExtendForm(false)}
                                                className="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition duration-200"
                                            >
                                                Annuler
                                            </button>
                                        </div>
                                    </form>
                                )}
                            </div>
                        )}

                        {/* Book Link */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Informations du livre
                            </h3>
                            <Link
                                href={route('books.show', rental.book.id)}
                                className="inline-flex items-center text-sm text-primary dark:text-blue-400 hover:text-primary dark:hover:text-blue-300"
                            >
                                <BookOpenIcon className="h-4 w-4 mr-2" />
                                Voir la page du livre
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}