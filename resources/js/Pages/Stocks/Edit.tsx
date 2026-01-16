import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import { PageProps } from '@/Types';
import { DatePicker } from '@/Components/ui/date-picker';
import { format } from 'date-fns';

interface Category {
    id: number;
    name: string;
}

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Stock {
    id: number;
    uuid: string;
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
    status?: string;
    category?: Category;
    department?: Department;
}

interface Props extends PageProps {
    stock: Stock;
    categories: Category[];
    departments: Department[];
    statuses: Record<string, string>;
}

export default function Edit({ stock, categories, departments, statuses }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: stock.name || '',
        sku: stock.sku || '',
        description: stock.description || '',
        quantity: stock.quantity || 0,
        minimum_quantity: stock.minimum_quantity || 0,
        unit_price: stock.unit_price || 0,
        supplier: stock.supplier || '',
        supplier_contact: stock.supplier_contact || '',
        expiry_date: stock.expiry_date ? stock.expiry_date.substring(0, 10) : '',
        location: stock.location || '',
        is_active: stock.is_active ?? true,
        status: stock.status || 'active',
        category_id: stock.category?.id || '',
        department_id: stock.department?.id || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('stocks.update', stock.uuid));
    };

    return (
        <DashboardLayout>
            <Head title={`Edit Stock: ${stock.name}`} />

            <div className="p-4">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
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
                            <h1 className="text-lg font-medium text-gray-900 dark:text-white">
                                Modifier le stock : {stock.name}
                            </h1>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Basic Information */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Nom *
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        required
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                                </div>

                                <div>
                                    <label htmlFor="sku" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        SKU *
                                    </label>
                                    <input
                                        type="text"
                                        id="sku"
                                        value={data.sku}
                                        onChange={(e) => setData('sku', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="ex: STK-001"
                                        required
                                    />
                                    {errors.sku && <p className="mt-1 text-sm text-red-600">{errors.sku}</p>}
                                </div>
                            </div>

                            <div>
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    rows={3}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    placeholder="Description de l'article..."
                                />
                                {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                            </div>

                            {/* Inventory Information */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label htmlFor="quantity" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Quantité *
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        id="quantity"
                                        value={data.quantity}
                                        onChange={(e) => setData('quantity', parseInt(e.target.value) || 0)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        required
                                    />
                                    {errors.quantity && <p className="mt-1 text-sm text-red-600">{errors.quantity}</p>}
                                </div>

                                <div>
                                    <label htmlFor="minimum_quantity" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Quantité minimum *
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        id="minimum_quantity"
                                        value={data.minimum_quantity}
                                        onChange={(e) => setData('minimum_quantity', parseInt(e.target.value) || 0)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        required
                                    />
                                    {errors.minimum_quantity && <p className="mt-1 text-sm text-red-600">{errors.minimum_quantity}</p>}
                                </div>

                                <div>
                                    <label htmlFor="unit_price" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Prix unitaire *
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="unit_price"
                                        value={data.unit_price}
                                        onChange={(e) => setData('unit_price', parseFloat(e.target.value) || 0)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="0.00"
                                        required
                                    />
                                    {errors.unit_price && <p className="mt-1 text-sm text-red-600">{errors.unit_price}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label htmlFor="category_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Catégorie *
                                    </label>
                                    <select
                                        id="category_id"
                                        value={data.category_id}
                                        onChange={(e) => setData('category_id', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        required
                                    >
                                        <option value="">Sélectionner une catégorie</option>
                                        {categories.map(category => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.category_id && <p className="mt-1 text-sm text-red-600">{errors.category_id}</p>}
                                </div>

                                <div>
                                    <label htmlFor="department_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Département
                                    </label>
                                    <select
                                        id="department_id"
                                        value={data.department_id}
                                        onChange={(e) => setData('department_id', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    >
                                        <option value="">Aucun département</option>
                                        {departments.map(department => (
                                            <option key={department.id} value={department.id}>
                                                {department.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.department_id && <p className="mt-1 text-sm text-red-600">{errors.department_id}</p>}
                                </div>

                                <div>
                                    <label htmlFor="location" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Emplacement
                                    </label>
                                    <input
                                        type="text"
                                        id="location"
                                        value={data.location}
                                        onChange={(e) => setData('location', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="ex: Entrepôt A, Étagère B-3"
                                    />
                                    {errors.location && <p className="mt-1 text-sm text-red-600">{errors.location}</p>}
                                </div>
                            </div>

                            {/* Supplier Information */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="supplier" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Fournisseur
                                    </label>
                                    <input
                                        type="text"
                                        id="supplier"
                                        value={data.supplier}
                                        onChange={(e) => setData('supplier', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="Nom du fournisseur"
                                    />
                                    {errors.supplier && <p className="mt-1 text-sm text-red-600">{errors.supplier}</p>}
                                </div>

                                <div>
                                    <label htmlFor="supplier_contact" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Contact fournisseur
                                    </label>
                                    <input
                                        type="text"
                                        id="supplier_contact"
                                        value={data.supplier_contact}
                                        onChange={(e) => setData('supplier_contact', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="Email ou téléphone"
                                    />
                                    {errors.supplier_contact && <p className="mt-1 text-sm text-red-600">{errors.supplier_contact}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="expiry_date" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Date d'expiration
                                    </label>
                                    <DatePicker
                                        value={data.expiry_date}
                                        onChange={(date) => setData('expiry_date', date ? format(date, 'yyyy-MM-dd') : '')}
                                        placeholder="Sélectionner une date d'expiration"
                                    />
                                    {errors.expiry_date && <p className="mt-1 text-sm text-red-600">{errors.expiry_date}</p>}
                                </div>

                                <div>
                                    <label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Statut
                                    </label>
                                    <select
                                        id="status"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    >
                                        {Object.entries(statuses).map(([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.status && <p className="mt-1 text-sm text-red-600">{errors.status}</p>}
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <Link
                                    href={route('stocks.index')}
                                    className="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    Annuler
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-primary hover:bg-primary disabled:bg-blue-300 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    {processing ? 'Mise à jour...' : 'Mettre à jour'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}