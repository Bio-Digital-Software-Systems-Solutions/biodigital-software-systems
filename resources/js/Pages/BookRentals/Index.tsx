import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { 
    BookOpenIcon,
    ClockIcon,
    CalendarDaysIcon,
    BuildingOffice2Icon,
    CurrencyEuroIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    EyeIcon
} from '@heroicons/react/24/outline';

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
        category?: {
            id: number;
            name: string;
        };
    };
    library: {
        id: number;
        name: string;
    };
    created_at: string;
}

interface BookRentalsPageProps extends PageProps {
    rentals: {
        data: BookRental[];
        links: any[];
        meta: any;
    };
    filters: {
        status?: string;
    };
}

export default function Index() {
    const { rentals, filters, auth } = usePage<BookRentalsPageProps>().props;
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    const handleStatusFilter = (status: string) => {
        setSelectedStatus(status);
        router.get(route('book-rentals.index'), { status }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSelectedStatus('');
        router.get(route('book-rentals.index'), {}, {
            preserveState: true,
            replace: true,
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'active':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'returned':
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
            case 'overdue':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'active':
                return 'En cours';
            case 'returned':
                return 'Retourné';
            case 'overdue':
                return 'En retard';
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

    const isOverdue = (dueDate: string, status: string) => {
        return status === 'active' && new Date(dueDate) < new Date();
    };

    return (
        <DashboardLayout
            title="Mes locations de livres"
            description="Gérez vos locations de livres en cours et passées"
        >
            <Head title="Mes locations - AIG-App" />

            {/* Filters */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <div className="flex flex-wrap items-center gap-4">
                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Filtrer par statut:
                        </span>
                        
                        <div className="flex flex-wrap gap-2">
                            <button
                                onClick={() => clearFilters()}
                                className={`px-3 py-1 rounded-full text-sm font-medium transition duration-200 ${
                                    !selectedStatus
                                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                }`}
                            >
                                Tous
                            </button>
                            
                            <button
                                onClick={() => handleStatusFilter('active')}
                                className={`px-3 py-1 rounded-full text-sm font-medium transition duration-200 ${
                                    selectedStatus === 'active'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                }`}
                            >
                                En cours
                            </button>
                            
                            <button
                                onClick={() => handleStatusFilter('returned')}
                                className={`px-3 py-1 rounded-full text-sm font-medium transition duration-200 ${
                                    selectedStatus === 'returned'
                                        ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                }`}
                            >
                                Retournés
                            </button>
                        </div>
                    </div>
                </div>

                {/* Rentals List */}
                <div className="space-y-4">
                    {rentals.data.map((rental) => (
                        <div
                            key={rental.id}
                            className={`bg-white dark:bg-gray-800 rounded-lg shadow border ${
                                isOverdue(rental.due_date, rental.status)
                                    ? 'border-red-200 dark:border-red-700'
                                    : 'border-gray-200 dark:border-gray-700'
                            } overflow-hidden`}
                        >
                            <div className="p-6">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-start space-x-4">
                                            <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                                <BookOpenIcon className="h-6 w-6 text-primary dark:text-primary" />
                                            </div>
                                            
                                            <div className="flex-1">
                                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                                    {rental.book.title}
                                                </h3>
                                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    par {rental.book.author}
                                                </p>
                                                
                                                {rental.book.category && (
                                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                        Catégorie: {rental.book.category.name}
                                                    </p>
                                                )}

                                                {/* Rental Details */}
                                                <div className="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                                                    <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                                        <CalendarDaysIcon className="h-4 w-4 mr-2" />
                                                        <div>
                                                            <p className="font-medium">Location</p>
                                                            <p>{formatDate(rental.rental_date)}</p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div className={`flex items-center text-sm ${
                                                        isOverdue(rental.due_date, rental.status)
                                                            ? 'text-red-600 dark:text-red-400'
                                                            : 'text-gray-600 dark:text-gray-400'
                                                    }`}>
                                                        <ClockIcon className="h-4 w-4 mr-2" />
                                                        <div>
                                                            <p className="font-medium">À retourner</p>
                                                            <p>{formatDate(rental.due_date)}</p>
                                                        </div>
                                                    </div>

                                                    {rental.return_date && (
                                                        <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                                            <CheckCircleIcon className="h-4 w-4 mr-2" />
                                                            <div>
                                                                <p className="font-medium">Retourné</p>
                                                                <p>{formatDate(rental.return_date)}</p>
                                                            </div>
                                                        </div>
                                                    )}
                                                    
                                                    <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                                        <BuildingOffice2Icon className="h-4 w-4 mr-2" />
                                                        <div>
                                                            <p className="font-medium">Bibliothèque</p>
                                                            <p>{rental.library.name}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Fees */}
                                                {(rental.rental_fee > 0 || rental.late_fee > 0) && (
                                                    <div className="mt-4 flex items-center space-x-4">
                                                        {rental.rental_fee > 0 && (
                                                            <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                                                <CurrencyEuroIcon className="h-4 w-4 mr-1" />
                                                                <span>Frais de location: {rental.rental_fee}€</span>
                                                            </div>
                                                        )}
                                                        
                                                        {rental.late_fee > 0 && (
                                                            <div className="flex items-center text-sm text-red-600 dark:text-red-400">
                                                                <ExclamationTriangleIcon className="h-4 w-4 mr-1" />
                                                                <span>Frais de retard: {rental.late_fee}€</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex flex-col items-end space-y-3">
                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                            isOverdue(rental.due_date, rental.status) 
                                                ? getStatusColor('overdue')
                                                : getStatusColor(rental.status)
                                        }`}>
                                            {isOverdue(rental.due_date, rental.status) 
                                                ? getStatusLabel('overdue')
                                                : getStatusLabel(rental.status)
                                            }
                                        </span>

                                        <div className="flex space-x-2">
                                            <Link
                                                href={route('book-rentals.show', rental.id)}
                                                className="inline-flex items-center text-sm text-primary dark:text-primary hover:text-primary dark:hover:text-primary"
                                            >
                                                <EyeIcon className="h-4 w-4 mr-1" />
                                                Détails
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Empty State */}
                {rentals.data.length === 0 && (
                    <div className="text-center py-12">
                        <BookOpenIcon className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            Aucune location trouvée
                        </h3>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {selectedStatus 
                                ? 'Aucune location ne correspond à vos critères de recherche.'
                                : 'Vous n\'avez pas encore loué de livres.'
                            }
                        </p>
                        <div className="mt-6">
                            <Link
                                href={route('books.index')}
                                className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg transition duration-200"
                            >
                                <BookOpenIcon className="h-5 w-5 mr-2" />
                                Parcourir la bibliothèque
                            </Link>
                        </div>
                    </div>
                )}

                {/* Pagination */}
                {rentals.data.length > 0 && rentals.meta?.last_page > 1 && (
                    <div className="mt-8 flex justify-center">
                        <nav className="flex space-x-2">
                            {rentals.links.map((link, index) => (
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