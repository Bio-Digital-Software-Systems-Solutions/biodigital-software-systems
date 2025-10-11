import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeftIcon, PencilIcon, TrashIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import { PageProps } from '@/Types';

interface Category {
    id: number;
    name: string;
}

interface Stock {
    id: number;
    name: string;
    sku: string;
    description?: string;
    quantity: number;
    minimum_quantity: number;
    unit_price: number;
    supplier?: string;
    supplier_contact?: string;
    expiry_date?: string;
    location?: string;
    is_active: boolean;
    category?: Category;
    created_at: string;
    updated_at: string;
}

interface Props extends PageProps {
    stock: Stock;
}

export default function Show({ stock, auth }: Props) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(price);
    };

    const getStockStatus = () => {
        if (stock.expiry_date && new Date(stock.expiry_date) < new Date()) {
            return { label: 'Expiré', color: 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20' };
        }
        if (stock.expiry_date && new Date(stock.expiry_date) <= new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)) {
            return { label: 'Expire bientôt', color: 'text-yellow-600 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20' };
        }
        if (stock.quantity === 0) {
            return { label: 'En rupture', color: 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20' };
        }
        if (stock.quantity <= stock.minimum_quantity) {
            return { label: 'Stock faible', color: 'text-yellow-600 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20' };
        }
        return { label: 'En stock', color: 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20' };
    };

    const status = getStockStatus();
    const canEdit = auth.user?.permissions?.includes('manage stocks') || auth.user?.roles?.includes('admin');

    return (
        <DashboardLayout>
            <Head title={`${stock.name} - Stock`} />

            <div className="mb-6">
                <Link
                    href={route('stocks.index')}
                    className="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <ArrowLeftIcon className="w-4 h-4 mr-2" />
                    Retour aux stocks
                </Link>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div className="flex justify-between items-start">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                {stock.name}
                            </h1>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                SKU: {stock.sku}
                            </p>
                        </div>
                        
                        <div className="flex items-center space-x-3">
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${status.color}`}>
                                {status.label}
                            </span>
                            
                            {canEdit && (
                                <div className="flex space-x-2">
                                    <Link
                                        href={route('stocks.edit', stock.uuid)}
                                        className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                    >
                                        <PencilIcon className="h-4 w-4 mr-2" />
                                        Modifier
                                    </Link>
                                    <Link
                                        href={route('stocks.destroy', stock.uuid)}
                                        method="delete"
                                        as="button"
                                        className="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 rounded-md text-sm font-medium text-red-700 dark:text-red-300 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900"
                                        data-confirm="Êtes-vous sûr de vouloir supprimer cet article ?"
                                    >
                                        <TrashIcon className="h-4 w-4 mr-2" />
                                        Supprimer
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <div className="p-6">
                    {/* Alerts */}
                    {(stock.quantity <= stock.minimum_quantity || (stock.expiry_date && new Date(stock.expiry_date) <= new Date(Date.now() + 30 * 24 * 60 * 60 * 1000))) && (
                        <div className="mb-6">
                            {stock.quantity === 0 && (
                                <div className="flex items-center p-4 mb-4 text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800">
                                    <ExclamationTriangleIcon className="h-5 w-5 mr-3" />
                                    <span className="text-sm font-medium">Cet article est en rupture de stock</span>
                                </div>
                            )}
                            
                            {stock.quantity > 0 && stock.quantity <= stock.minimum_quantity && (
                                <div className="flex items-center p-4 mb-4 text-yellow-800 border border-yellow-300 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300 dark:border-yellow-800">
                                    <ExclamationTriangleIcon className="h-5 w-5 mr-3" />
                                    <span className="text-sm font-medium">Stock faible - Réapprovisionnement recommandé</span>
                                </div>
                            )}
                            
                            {stock.expiry_date && new Date(stock.expiry_date) <= new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) && (
                                <div className="flex items-center p-4 mb-4 text-orange-800 border border-orange-300 rounded-lg bg-orange-50 dark:bg-gray-800 dark:text-orange-300 dark:border-orange-800">
                                    <ExclamationTriangleIcon className="h-5 w-5 mr-3" />
                                    <span className="text-sm font-medium">
                                        {new Date(stock.expiry_date) < new Date() ? 'Cet article a expiré' : 'Cet article expire bientôt'}
                                    </span>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Basic Information */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">Informations générales</h3>
                            <dl className="space-y-3">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Nom</dt>
                                    <dd className="text-sm text-gray-900 dark:text-white">{stock.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">SKU</dt>
                                    <dd className="text-sm text-gray-900 dark:text-white">{stock.sku}</dd>
                                </div>
                                {stock.category && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Catégorie</dt>
                                        <dd className="text-sm text-gray-900 dark:text-white">{stock.category.name}</dd>
                                    </div>
                                )}
                                {stock.description && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                        <dd className="text-sm text-gray-900 dark:text-white">{stock.description}</dd>
                                    </div>
                                )}
                            </dl>
                        </div>

                        <div>
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">Inventaire</h3>
                            <dl className="space-y-3">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Quantité actuelle</dt>
                                    <dd className={`text-sm font-semibold ${stock.quantity <= stock.minimum_quantity ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'}`}>
                                        {stock.quantity}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Quantité minimum</dt>
                                    <dd className="text-sm text-gray-900 dark:text-white">{stock.minimum_quantity}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Prix unitaire</dt>
                                    <dd className="text-sm text-gray-900 dark:text-white">{formatPrice(stock.unit_price)}</dd>
                                </div>
                                {stock.location && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Emplacement</dt>
                                        <dd className="text-sm text-gray-900 dark:text-white">{stock.location}</dd>
                                    </div>
                                )}
                                {stock.expiry_date && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Date d'expiration</dt>
                                        <dd className={`text-sm ${new Date(stock.expiry_date) <= new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-900 dark:text-white'}`}>
                                            {formatDate(stock.expiry_date)}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </div>
                    </div>

                    {/* Supplier Information */}
                    {(stock.supplier || stock.supplier_contact) && (
                        <div className="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">Informations fournisseur</h3>
                            <dl className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {stock.supplier && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Fournisseur</dt>
                                        <dd className="text-sm text-gray-900 dark:text-white">{stock.supplier}</dd>
                                    </div>
                                )}
                                {stock.supplier_contact && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Contact fournisseur</dt>
                                        <dd className="text-sm text-gray-900 dark:text-white">{stock.supplier_contact}</dd>
                                    </div>
                                )}
                            </dl>
                        </div>
                    )}

                    {/* Timestamps */}
                    <div className="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                        <dl className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Créé le</dt>
                                <dd className="text-sm text-gray-900 dark:text-white">{formatDate(stock.created_at)}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Dernière modification</dt>
                                <dd className="text-sm text-gray-900 dark:text-white">{formatDate(stock.updated_at)}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                    </div>
        </DashboardLayout>
    );
}