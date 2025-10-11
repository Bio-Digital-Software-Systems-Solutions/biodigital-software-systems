import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { ArrowLeftIcon, BookOpenIcon, PhotoIcon, XMarkIcon } from '@heroicons/react/24/outline';

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
}

interface Category {
    id: number;
    name: string;
}

interface EditBookPageProps extends PageProps {
    book: Book;
    categories: Category[];
}

export default function Edit() {
    const { book, categories } = usePage<EditBookPageProps>().props;
    const [coverImagePreview, setCoverImagePreview] = useState<string | null>(
        book.cover_image ? `/storage/${book.cover_image}` : null
    );
    const [imageInputMode, setImageInputMode] = useState<'file' | 'url'>('file');
    const [imageUrl, setImageUrl] = useState<string>('');

    const { data, setData, post, processing, errors } = useForm({
        title: book.title || '',
        author: book.author || '',
        isbn: book.isbn || '',
        description: book.description || '',
        cover_image: null as File | string | null,
        rental_price: book.rental_price?.toString() || '',
        max_rental_days: book.max_rental_days || 14,
        stock_quantity: book.stock_quantity || 1,
        category_id: book.category?.id?.toString() || '',
        _method: 'put',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('books.update', book.uuid), {
            forceFormData: true,
        });
    };

    const handleCoverImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('cover_image', file);
            const reader = new FileReader();
            reader.onload = (e) => {
                setCoverImagePreview(e.target?.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleImageUrlChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const url = e.target.value;
        setImageUrl(url);
        if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
            setData('cover_image', url);
            setCoverImagePreview(url);
        }
    };

    const removeCoverImage = () => {
        setData('cover_image', null);
        setImageUrl('');
        setCoverImagePreview(book.cover_image ? `/storage/${book.cover_image}` : null);
        const input = document.getElementById('cover_image') as HTMLInputElement;
        if (input) {
            input.value = '';
        }
    };

    return (
        <DashboardLayout>
            <Head title={`Modifier ${book.title} - AIG-App`} />

            <div className="p-4">
                {/* Back Button */}
                <div className="mb-6">
                    <Link
                        href={route('books.show', book.uuid)}
                        className="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                        Retour au livre
                    </Link>
                </div>

                <div className="mx-auto">
                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center space-x-3 mb-2">
                            <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                <BookOpenIcon className="h-6 w-6 text-primary dark:text-blue-400" />
                            </div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Modifier le livre
                            </h1>
                        </div>
                        <p className="text-gray-600 dark:text-gray-400">
                            Modifiez les informations de ce livre dans votre bibliothèque.
                        </p>
                    </div>

                    {/* Form */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Basic Information */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="title" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Titre *
                                    </label>
                                    <input
                                        type="text"
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        required
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                        placeholder="Le titre du livre"
                                    />
                                    {errors.title && (
                                        <p className="text-red-600 text-sm mt-1">{errors.title}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="author" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Auteur *
                                    </label>
                                    <input
                                        type="text"
                                        id="author"
                                        value={data.author}
                                        onChange={(e) => setData('author', e.target.value)}
                                        required
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                        placeholder="L'auteur du livre"
                                    />
                                    {errors.author && (
                                        <p className="text-red-600 text-sm mt-1">{errors.author}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="isbn" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        ISBN
                                    </label>
                                    <input
                                        type="text"
                                        id="isbn"
                                        value={data.isbn}
                                        onChange={(e) => setData('isbn', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                        placeholder="978-3-16-148410-0"
                                    />
                                    {errors.isbn && (
                                        <p className="text-red-600 text-sm mt-1">{errors.isbn}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="category_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Catégorie
                                    </label>
                                    <select
                                        id="category_id"
                                        value={data.category_id}
                                        onChange={(e) => setData('category_id', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                    >
                                        <option value="">Aucune catégorie</option>
                                        {categories.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.category_id && (
                                        <p className="text-red-600 text-sm mt-1">{errors.category_id}</p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    rows={4}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                    placeholder="Une brève description du livre..."
                                />
                                {errors.description && (
                                    <p className="text-red-600 text-sm mt-1">{errors.description}</p>
                                )}
                            </div>

                            {/* Cover Image */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Image de couverture
                                </label>

                                {/* Current Image Display */}
                                {book.cover_image && !coverImagePreview?.includes('blob:') && !data.cover_image && (
                                    <div className="mb-4">
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">Image actuelle :</p>
                                        <div className="relative inline-block">
                                            <img
                                                src={`/storage/${book.cover_image}`}
                                                alt="Current cover"
                                                className="max-h-64 rounded-lg object-cover border-2 border-gray-300 dark:border-gray-600"
                                            />
                                        </div>
                                    </div>
                                )}

                                {/* Mode Selection */}
                                <div className="flex items-center justify-between mb-4">
                                    <div className="flex space-x-4">
                                        <label className="flex items-center cursor-pointer">
                                            <input
                                                type="radio"
                                                value="file"
                                                checked={imageInputMode === 'file'}
                                                onChange={(e) => setImageInputMode(e.target.value as 'file' | 'url')}
                                                className="mr-2 text-primary focus:ring-primary"
                                            />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">Fichier local</span>
                                        </label>
                                        <label className="flex items-center cursor-pointer">
                                            <input
                                                type="radio"
                                                value="url"
                                                checked={imageInputMode === 'url'}
                                                onChange={(e) => setImageInputMode(e.target.value as 'file' | 'url')}
                                                className="mr-2 text-primary focus:ring-primary"
                                            />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">Lien HTTPS</span>
                                        </label>
                                    </div>
                                    {data.cover_image && (
                                        <button
                                            type="button"
                                            onClick={removeCoverImage}
                                            className="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium"
                                        >
                                            Annuler le changement
                                        </button>
                                    )}
                                </div>

                                {imageInputMode === 'url' ? (
                                    <div className="space-y-3">
                                        <input
                                            type="url"
                                            value={imageUrl}
                                            onChange={handleImageUrlChange}
                                            placeholder="https://exemple.com/image.jpg"
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                        />
                                        {coverImagePreview && imageUrl && (
                                            <div className="relative inline-block">
                                                <p className="text-sm text-green-600 dark:text-green-400 mb-2">Nouvelle image (aperçu) :</p>
                                                <img
                                                    src={coverImagePreview}
                                                    alt="Cover preview"
                                                    className="max-h-64 rounded-lg object-cover border-2 border-green-500"
                                                />
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div>
                                        {coverImagePreview && !imageUrl && data.cover_image ? (
                                            <div className="relative inline-block">
                                                <p className="text-sm text-green-600 dark:text-green-400 mb-2">Nouvelle image (aperçu) :</p>
                                                <img
                                                    src={coverImagePreview}
                                                    alt="Cover preview"
                                                    className="max-h-64 rounded-lg object-cover border-2 border-green-500"
                                                />
                                            </div>
                                        ) : (
                                            <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:border-primary dark:hover:border-primary transition-colors">
                                                <div className="space-y-1 text-center">
                                                    <PhotoIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                    <div className="flex text-sm text-gray-600 dark:text-gray-400">
                                                        <label
                                                            htmlFor="cover_image"
                                                            className="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-primary hover:text-blue-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary"
                                                        >
                                                            <span>{book.cover_image ? 'Changer l\'image' : 'Télécharger une image'}</span>
                                                            <input
                                                                id="cover_image"
                                                                name="cover_image"
                                                                type="file"
                                                                className="sr-only"
                                                                accept="image/*"
                                                                onChange={handleCoverImageChange}
                                                            />
                                                        </label>
                                                        <p className="pl-1">ou glisser-déposer</p>
                                                    </div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        PNG, JPG, GIF jusqu'à 10MB
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}
                                {errors.cover_image && (
                                    <p className="text-red-600 text-sm mt-1">{errors.cover_image}</p>
                                )}
                            </div>

                            {/* Rental Information */}
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Informations de location
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label htmlFor="rental_price" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Prix de location (€/jour)
                                        </label>
                                        <input
                                            type="number"
                                            id="rental_price"
                                            min="0"
                                            step="0.01"
                                            value={data.rental_price}
                                            onChange={(e) => setData('rental_price', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                            placeholder="2.50"
                                        />
                                        {errors.rental_price && (
                                            <p className="text-red-600 text-sm mt-1">{errors.rental_price}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="max_rental_days" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Durée max (jours) *
                                        </label>
                                        <input
                                            type="number"
                                            id="max_rental_days"
                                            min="1"
                                            value={data.max_rental_days}
                                            onChange={(e) => setData('max_rental_days', parseInt(e.target.value) || 14)}
                                            required
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                        />
                                        {errors.max_rental_days && (
                                            <p className="text-red-600 text-sm mt-1">{errors.max_rental_days}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="stock_quantity" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Nombre d'exemplaires *
                                        </label>
                                        <input
                                            type="number"
                                            id="stock_quantity"
                                            min="0"
                                            value={data.stock_quantity}
                                            onChange={(e) => setData('stock_quantity', parseInt(e.target.value) || 1)}
                                            required
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-primary"
                                        />
                                        {errors.stock_quantity && (
                                            <p className="text-red-600 text-sm mt-1">{errors.stock_quantity}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-6">
                                <div className="flex justify-end space-x-3">
                                    <Link
                                        href={route('books.show', book.uuid)}
                                        className="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200"
                                    >
                                        Annuler
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-primary hover:bg-primary disabled:bg-blue-400 text-white font-medium rounded-lg transition duration-200"
                                    >
                                        {processing ? 'Mise à jour...' : 'Mettre à jour'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}