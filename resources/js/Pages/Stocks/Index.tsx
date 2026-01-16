import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PlusIcon, FunnelIcon, EyeIcon, PencilIcon, TrashIcon, ExclamationTriangleIcon, Squares2X2Icon, TableCellsIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { useState, useEffect, useRef } from 'react';
import { Stock, Category, PageProps } from '@/Types';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Props extends PageProps {
    stocks: {
        data: Stock[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    categories: Category[];
    departments: Department[];
    filters: {
        [key: string]: string | undefined;
        search?: string;
        category?: string;
        department?: string;
        status?: string;
        supplier?: string;
    };
}

export default function Index({ stocks, categories, departments, filters }: Props) {
    const [showFilters, setShowFilters] = useState(false);
    const [viewMode, setViewMode] = useState<'table' | 'grid'>('table');
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [stockToDelete, setStockToDelete] = useState<Stock | null>(null);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const debounceRef = useRef<NodeJS.Timeout | null>(null);

    // Debounced search
    useEffect(() => {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }
        debounceRef.current = setTimeout(() => {
            if (searchQuery !== (filters.search || '')) {
                handleFilter('search', searchQuery);
            }
        }, 300);
        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, [searchQuery]);

    const handleFilter = (key: string, value: string) => {
        const newFilters = { ...filters, [key]: value };
        if (!value) delete newFilters[key];

        router.get(route('stocks.index'), newFilters, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (stock: Stock) => {
        setStockToDelete(stock);
        setDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        if (stockToDelete) {
            router.delete(route('stocks.destroy', stockToDelete.uuid), {
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setStockToDelete(null);
                },
            });
        }
    };

    const getStockStatus = (stock: Stock) => {
        if (stock.expiry_date && new Date(stock.expiry_date) < new Date()) {
            return { label: 'Expired', color: 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20' };
        }
        if (stock.expiry_date && new Date(stock.expiry_date) <= new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)) {
            return { label: 'Near Expiry', color: 'text-yellow-600 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20' };
        }
        if (stock.quantity === 0) {
            return { label: 'Out of Stock', color: 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20' };
        }
        if (stock.quantity <= stock.minimum_quantity) {
            return { label: 'Low Stock', color: 'text-yellow-600 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20' };
        }
        if (stock.is_active) {
            return { label: 'In Stock', color: 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20' };
        }
        return { label: 'Inactive', color: 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20' };
    };

    return (
        <DashboardLayout
            title="Gestion des Stocks"
            description="Gérez l'inventaire et les approvisionnements"
            actions={
                <>
                    <div className="flex border border-gray-300 dark:border-gray-600 rounded-md">
                        <button
                            onClick={() => setViewMode('table')}
                            className={`flex items-center px-3 py-2 text-sm font-medium rounded-l-md ${viewMode === 'table'
                                    ? 'bg-primary text-white'
                                    : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'
                                }`}
                            title="Vue tableau"
                        >
                            <TableCellsIcon className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => setViewMode('grid')}
                            className={`flex items-center px-3 py-2 text-sm font-medium rounded-r-md ${viewMode === 'grid'
                                    ? 'bg-primary text-white'
                                    : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'
                                }`}
                            title="Vue grille"
                        >
                            <Squares2X2Icon className="w-4 h-4" />
                        </button>
                    </div>
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition duration-200"
                    >
                        <FunnelIcon className="w-4 h-4 mr-2" />
                        Filtres
                    </button>
                    <Link
                        href={route('stocks.create')}
                        className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white text-sm font-medium rounded-lg transition duration-200"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Nouvel article
                    </Link>
                </>
            }
        >
            <Head title="Gestion des Stocks" />

            {showFilters && (
                <div className="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Rechercher
                            </label>
                            <div className="relative">
                                <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder="Rechercher par nom..."
                                    className="block w-full pl-10 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Catégorie
                            </label>
                            <select
                                value={filters.category || ''}
                                onChange={(e) => handleFilter('category', e.target.value)}
                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                            >
                                <option value="">Toutes les catégories</option>
                                {categories.map(category => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Département
                            </label>
                            <select
                                value={filters.department || ''}
                                onChange={(e) => handleFilter('department', e.target.value)}
                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                            >
                                <option value="">Tous les départements</option>
                                {departments.map(department => (
                                    <option key={department.id} value={department.id}>
                                        {department.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Statut Stock
                            </label>
                            <select
                                value={filters.status || ''}
                                onChange={(e) => handleFilter('status', e.target.value)}
                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="low_stock">Stock faible</option>
                                <option value="out_of_stock">Rupture de stock</option>
                                <option value="expired">Expiré</option>
                                <option value="near_expiry">Expire bientôt</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Fournisseur
                            </label>
                            <input
                                type="text"
                                value={filters.supplier || ''}
                                onChange={(e) => handleFilter('supplier', e.target.value)}
                                placeholder="Rechercher fournisseur..."
                                className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                            />
                        </div>
                    </div>
                </div>
            )}

            {/* Table View */}
            {viewMode === 'table' && (
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Item
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    SKU
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Category
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Unit Price
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Supplier
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            {stocks.data.map((stock) => {
                                const status = getStockStatus(stock);
                                const needsAttention = stock.quantity <= stock.minimum_quantity ||
                                    (stock.expiry_date && new Date(stock.expiry_date) <= new Date(Date.now() + 30 * 24 * 60 * 60 * 1000));

                                return (
                                    <tr key={stock.uuid}>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center">
                                                {needsAttention && (
                                                    <ExclamationTriangleIcon className="w-4 h-4 text-yellow-500 mr-2" />
                                                )}
                                                <div>
                                                    <Link
                                                        href={route('stocks.show', stock.uuid)}
                                                        className="text-sm font-medium text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                    >
                                                        {stock.name}
                                                    </Link>
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">
                                                        {stock.description && stock.description.substring(0, 50)}
                                                        {stock.description && stock.description.length > 50 && '...'}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {stock.sku}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {stock.category?.name || '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <div className="text-gray-900 dark:text-gray-100">
                                                {stock.quantity}
                                            </div>
                                            <div className="text-gray-500 dark:text-gray-400 text-xs">
                                                Min: {stock.minimum_quantity}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            ${stock.unit_price}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${status.color}`}>
                                                {status.label}
                                            </span>
                                            {stock.expiry_date && (
                                                <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Exp: {new Date(stock.expiry_date).toLocaleDateString()}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            <div>
                                                {stock.supplier || '-'}
                                            </div>
                                            {stock.supplier_contact && (
                                                <div className="text-xs text-gray-500 dark:text-gray-400">
                                                    {stock.supplier_contact}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div className="flex space-x-2">
                                                <Link
                                                    href={route('stocks.show', stock.uuid)}
                                                    className="text-primary hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    <EyeIcon className="w-4 h-4" />
                                                </Link>
                                                <Link
                                                    href={route('stocks.edit', stock.uuid)}
                                                    className="text-primary hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                >
                                                    <PencilIcon className="w-4 h-4" />
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(stock)}
                                                    className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                >
                                                    <TrashIcon className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Grid View */}
            {viewMode === 'grid' && (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    {stocks.data.map((stock) => {
                        const status = getStockStatus(stock);
                        const needsAttention = stock.quantity <= stock.minimum_quantity ||
                            (stock.expiry_date && new Date(stock.expiry_date) <= new Date(Date.now() + 30 * 24 * 60 * 60 * 1000));

                        return (
                            <Link
                                key={stock.uuid}
                                href={route('stocks.show', stock.uuid)}
                                className="block bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-200 dark:border-gray-600 overflow-hidden"
                            >
                                <div className="p-6">
                                    {/* Header with Warning */}
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex-1">
                                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                                {stock.name}
                                            </h3>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                SKU: {stock.sku}
                                            </p>
                                        </div>
                                        {needsAttention && (
                                            <ExclamationTriangleIcon className="w-6 h-6 text-yellow-500 flex-shrink-0" />
                                        )}
                                    </div>

                                    {/* Description */}
                                    {stock.description && (
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                            {stock.description}
                                        </p>
                                    )}

                                    {/* Category */}
                                    {stock.category && (
                                        <div className="mb-3">
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200">
                                                {stock.category.name}
                                            </span>
                                        </div>
                                    )}

                                    {/* Quantity and Price */}
                                    <div className="grid grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">Quantity</p>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-white">
                                                {stock.quantity}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                Min: {stock.minimum_quantity}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">Unit Price</p>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-white">
                                                ${stock.unit_price}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Status */}
                                    <div className="mb-3">
                                        <span className={`inline-flex px-2.5 py-1 text-xs font-semibold rounded-full ${status.color}`}>
                                            {status.label}
                                        </span>
                                    </div>

                                    {/* Expiry Date */}
                                    {stock.expiry_date && (
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                            Expires: {new Date(stock.expiry_date).toLocaleDateString()}
                                        </p>
                                    )}

                                    {/* Supplier */}
                                    {stock.supplier && (
                                        <div className="pt-3 border-t border-gray-200 dark:border-gray-600">
                                            <p className="text-xs text-gray-500 dark:text-gray-400">Supplier</p>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                {stock.supplier}
                                            </p>
                                            {stock.supplier_contact && (
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {stock.supplier_contact}
                                                </p>
                                            )}
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="mt-4 flex justify-end space-x-2">
                                        <button
                                            onClick={(e) => {
                                                e.preventDefault();
                                                router.visit(route('stocks.edit', stock.uuid));
                                            }}
                                            className="p-2 text-primary hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            <PencilIcon className="w-4 h-4" />
                                        </button>
                                        <button
                                            onClick={(e) => {
                                                e.preventDefault();
                                                handleDelete(stock);
                                            }}
                                            className="p-2 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <TrashIcon className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </Link>
                        );
                    })}
                </div>
            )}

            {stocks.data.length === 0 && (
                <div className="text-center py-8">
                    <p className="text-gray-500 dark:text-gray-400">No stock items found.</p>
                </div>
            )}

            {/* Pagination */}
            {stocks.last_page > 1 && (
                <div className="flex justify-between items-center mt-6">
                    <div className="text-sm text-gray-700 dark:text-gray-300">
                        Showing {((stocks.current_page - 1) * stocks.per_page) + 1} to{' '}
                        {Math.min(stocks.current_page * stocks.per_page, stocks.total)} of{' '}
                        {stocks.total} results
                    </div>
                    <div className="flex space-x-2">
                        {stocks.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url || '#'}
                                className={`px-3 py-2 text-sm font-medium rounded-md ${link.active
                                        ? 'bg-primary text-white'
                                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={confirmDelete}
                title="Supprimer l'article"
                description={`Êtes-vous sûr de vouloir supprimer l'article "${stockToDelete?.name}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
}