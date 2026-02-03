import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { FormEventHandler, useState } from 'react';
import { PhotoIcon } from '@heroicons/react/24/outline';
import TopicsManager, { Topic } from '@/Components/Training/TopicsManager';

interface Teacher {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface Props {
    teachers: Teacher[];
}

export default function Create({ teachers = [] }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        title: string;
        description: string;
        duration: string;
        level: string;
        price: string;
        category: string;
        image: File | null;
        is_active: boolean;
        teacher_id: number | null;
        topics: Topic[];
    }>({
        title: '',
        description: '',
        duration: '',
        level: 'beginner',
        price: '',
        category: '',
        image: null,
        is_active: true,
        teacher_id: null,
        topics: [],
    });

    const [imagePreview, setImagePreview] = useState<string | null>(null);

    const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('image', file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setImagePreview(reader.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('trainings.store'));
    };

    return (
        <DashboardLayout>
            <Head title="Nouvelle Formation" />

            <div className="py-4 sm:py-12">
                <div className="mx-auto px-3 sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-4 sm:p-6">
                            <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 sm:gap-0 mb-4 sm:mb-6">
                                <h2 className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                                    Nouvelle Formation
                                </h2>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('trainings.index')}>
                                        Retour
                                    </Link>
                                </Button>
                            </div>

                            <form onSubmit={submit} className="space-y-4 sm:space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                    {/* Title */}
                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Titre *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            required
                                        />
                                        {errors.title && <p className="text-red-500 text-sm mt-1">{errors.title}</p>}
                                    </div>

                                    {/* Description */}
                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Description *
                                        </label>
                                        <textarea
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            rows={4}
                                            className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            required
                                        />
                                        {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
                                    </div>

                                    {/* Topics Section */}
                                    <div className="md:col-span-2">
                                        <TopicsManager
                                            topics={data.topics}
                                            onChange={(topics) => setData('topics', topics)}
                                            error={errors.topics}
                                        />
                                    </div>

                                    {/* Duration */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Durée *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.duration}
                                            onChange={(e) => setData('duration', e.target.value)}
                                            placeholder="Ex: 3 mois"
                                            className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            required
                                        />
                                        {errors.duration && <p className="text-red-500 text-sm mt-1">{errors.duration}</p>}
                                    </div>

                                    {/* Level */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Niveau *
                                        </label>
                                        <select
                                            value={data.level}
                                            onChange={(e) => setData('level', e.target.value)}
                                            className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            required
                                        >
                                            <option value="beginner">Débutant</option>
                                            <option value="intermediate">Intermédiaire</option>
                                            <option value="advanced">Avancé</option>
                                        </select>
                                        {errors.level && <p className="text-red-500 text-sm mt-1">{errors.level}</p>}
                                    </div>

                                    {/* Price */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Prix (€) *
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={data.price}
                                            onChange={(e) => setData('price', e.target.value)}
                                            className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            required
                                        />
                                        {errors.price && <p className="text-red-500 text-sm mt-1">{errors.price}</p>}
                                    </div>

                                    {/* Category */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Catégorie *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.category}
                                            onChange={(e) => setData('category', e.target.value)}
                                            className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            required
                                        />
                                        {errors.category && <p className="text-red-500 text-sm mt-1">{errors.category}</p>}
                                    </div>

                                    {/* Teacher */}
                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Enseignant
                                        </label>
                                        <select
                                            value={data.teacher_id || ''}
                                            onChange={(e) => setData('teacher_id', e.target.value ? parseInt(e.target.value) : null)}
                                            className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        >
                                            <option value="">Aucun enseignant assigné</option>
                                            {teachers.map((teacher) => (
                                                <option key={teacher.id} value={teacher.id}>
                                                    {teacher.first_name} {teacher.last_name} ({teacher.email})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.teacher_id && <p className="text-red-500 text-sm mt-1">{errors.teacher_id}</p>}
                                    </div>

                                    {/* Image Upload */}
                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Image de la formation
                                        </label>
                                        <div className="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 dark:border-gray-700 px-6 py-10">
                                            <div className="text-center">
                                                {imagePreview ? (
                                                    <div className="mb-4">
                                                        <img
                                                            src={imagePreview}
                                                            alt="Preview"
                                                            className="mx-auto h-32 w-auto rounded-lg object-cover"
                                                        />
                                                    </div>
                                                ) : (
                                                    <PhotoIcon className="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                                                )}
                                                <div className="mt-4 flex text-sm leading-6 text-gray-600 dark:text-gray-400">
                                                    <label
                                                        htmlFor="file-upload"
                                                        className="relative cursor-pointer rounded-md bg-white dark:bg-gray-800 font-semibold text-violet-600 dark:text-violet-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-violet-600 focus-within:ring-offset-2 hover:text-violet-500"
                                                    >
                                                        <span className="px-2">Télécharger un fichier</span>
                                                        <input
                                                            id="file-upload"
                                                            name="file-upload"
                                                            type="file"
                                                            className="sr-only"
                                                            accept="image/*"
                                                            onChange={handleImageChange}
                                                        />
                                                    </label>
                                                    <p className="pl-1">ou glisser-déposer</p>
                                                </div>
                                                <p className="text-xs leading-5 text-gray-600 dark:text-gray-400">PNG, JPG, GIF jusqu'à 10MB</p>
                                                {data.image && typeof data.image !== 'string' && (
                                                    <p className="mt-2 text-sm text-gray-900 dark:text-white">
                                                        Fichier sélectionné: <span className="font-medium">{data.image.name}</span>
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        {errors.image && <p className="text-red-500 text-sm mt-1">{errors.image}</p>}
                                    </div>

                                    {/* Active Status */}
                                    <div className="md:col-span-2">
                                        <label className="flex items-center space-x-3 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.is_active}
                                                onChange={(e) => setData('is_active', e.target.checked)}
                                                className="w-5 h-5 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                            />
                                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Formation active
                                            </span>
                                        </label>
                                        {errors.is_active && <p className="text-red-500 text-sm mt-1">{errors.is_active}</p>}
                                    </div>
                                </div>

                                <div className="flex justify-end gap-4 pt-6 border-t dark:border-gray-700">
                                    <Button type="button" variant="outline" asChild>
                                        <Link href={route('trainings.index')}>
                                            Annuler
                                        </Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Création...' : 'Créer la formation'}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
