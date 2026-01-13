import React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import type { Department } from '@/Types';

interface Props {
    departments: Department[];
    departmentId?: string | number;
}

export default function FormCreate({ departments = [], departmentId }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        department_id: departmentId?.toString() || '',
        is_multi_step: false,
        success_message: 'Merci pour votre soumission!',
        redirect_url: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('forms.store'), {
            onSuccess: () => {
                toast.success('Formulaire créé avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la création');
            },
        });
    };

    const inputClasses = `
        w-full px-3 py-2 rounded-md border text-sm
        bg-white dark:bg-gray-900
        border-gray-300 dark:border-gray-600
        text-gray-900 dark:text-white
        focus:ring-2 focus:ring-primary focus:border-primary
        disabled:bg-gray-100 dark:disabled:bg-gray-800
    `;

    const labelClasses = `
        block text-sm font-medium mb-1
        text-gray-700 dark:text-gray-300
    `;

    const errorClasses = `
        mt-1 text-sm text-red-500
    `;

    return (
        <DashboardLayout>
            <Head title="Nouveau formulaire" />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-center gap-4 mb-6">
                        <button
                            type="button"
                            onClick={() => router.get(route('forms.index'))}
                            className="
                                p-2 rounded-md
                                text-gray-600 hover:bg-gray-100
                                dark:text-gray-400 dark:hover:bg-gray-700
                            "
                        >
                            <ArrowLeftIcon className="h-5 w-5" />
                        </button>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Nouveau formulaire
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Créez un nouveau formulaire personnalisé
                            </p>
                        </div>
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit}>
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                            {/* Name */}
                            <div>
                                <label className={labelClasses}>
                                    Nom du formulaire <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className={inputClasses}
                                    placeholder="Ex: Formulaire d'inscription"
                                />
                                {errors.name && <p className={errorClasses}>{errors.name}</p>}
                            </div>

                            {/* Department */}
                            <div>
                                <label className={labelClasses}>
                                    Département <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.department_id}
                                    onChange={(e) => setData('department_id', e.target.value)}
                                    className={inputClasses}
                                >
                                    <option value="">Sélectionner un département</option>
                                    {departments.map((dept) => (
                                        <option key={dept.id} value={dept.id}>
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.department_id && <p className={errorClasses}>{errors.department_id}</p>}
                            </div>

                            {/* Description */}
                            <div>
                                <label className={labelClasses}>Description</label>
                                <textarea
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={3}
                                    className={inputClasses}
                                    placeholder="Décrivez l'objectif de ce formulaire..."
                                />
                                {errors.description && <p className={errorClasses}>{errors.description}</p>}
                            </div>

                            {/* Multi-step option */}
                            <div className="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    id="is_multi_step"
                                    checked={data.is_multi_step}
                                    onChange={(e) => setData('is_multi_step', e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                />
                                <label htmlFor="is_multi_step" className="text-sm text-gray-700 dark:text-gray-300">
                                    Formulaire multi-étapes
                                </label>
                            </div>

                            {/* Success message */}
                            <div>
                                <label className={labelClasses}>Message de succès</label>
                                <textarea
                                    value={data.success_message}
                                    onChange={(e) => setData('success_message', e.target.value)}
                                    rows={2}
                                    className={inputClasses}
                                    placeholder="Message affiché après soumission..."
                                />
                                {errors.success_message && <p className={errorClasses}>{errors.success_message}</p>}
                            </div>

                            {/* Redirect URL */}
                            <div>
                                <label className={labelClasses}>URL de redirection (optionnel)</label>
                                <input
                                    type="url"
                                    value={data.redirect_url}
                                    onChange={(e) => setData('redirect_url', e.target.value)}
                                    className={inputClasses}
                                    placeholder="https://..."
                                />
                                {errors.redirect_url && <p className={errorClasses}>{errors.redirect_url}</p>}
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3 mt-6">
                            <button
                                type="button"
                                onClick={() => router.get(route('forms.index'))}
                                className="
                                    px-4 py-2 rounded-md
                                    border border-gray-300 dark:border-gray-600
                                    text-gray-700 dark:text-gray-300
                                    hover:bg-gray-50 dark:hover:bg-gray-700
                                "
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="
                                    px-4 py-2 rounded-md
                                    bg-primary text-white font-medium
                                    hover:bg-primary/90
                                    disabled:opacity-50
                                "
                            >
                                Créer et configurer les champs
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
