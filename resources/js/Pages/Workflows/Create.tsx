import React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import type { Department } from '@/Types';

interface SelectOption {
    value: string;
    label: string;
}

interface Props {
    departments: Department[];
    triggerTypes: SelectOption[];
    scopes: SelectOption[];
    departmentId?: string | number;
}

export default function WorkflowCreate({ departments = [], triggerTypes = [], scopes = [], departmentId }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        department_id: departmentId?.toString() || '',
        trigger_type: 'manual',
        scope: 'department',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('workflows.store'), {
            onSuccess: () => {
                toast.success('Workflow créé avec succès');
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
            <Head title="Nouveau workflow" />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-center gap-4 mb-6">
                        <button
                            type="button"
                            onClick={() => router.get(route('workflows.index'))}
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
                                Nouveau workflow
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Créez un nouveau workflow d'automatisation
                            </p>
                        </div>
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit}>
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                            {/* Name */}
                            <div>
                                <label className={labelClasses}>
                                    Nom du workflow <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className={inputClasses}
                                    placeholder="Ex: Processus d'approbation des achats"
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
                                    placeholder="Décrivez l'objectif de ce workflow..."
                                />
                                {errors.description && <p className={errorClasses}>{errors.description}</p>}
                            </div>

                            {/* Trigger Type */}
                            <div>
                                <label className={labelClasses}>
                                    Type de déclenchement <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.trigger_type}
                                    onChange={(e) => setData('trigger_type', e.target.value)}
                                    className={inputClasses}
                                >
                                    {triggerTypes.length > 0 ? (
                                        triggerTypes.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))
                                    ) : (
                                        <>
                                            <option value="manual">Manuel</option>
                                            <option value="form_submission">Soumission de formulaire</option>
                                            <option value="schedule">Planifié</option>
                                            <option value="event">Événement</option>
                                        </>
                                    )}
                                </select>
                                {errors.trigger_type && <p className={errorClasses}>{errors.trigger_type}</p>}
                            </div>

                            {/* Scope */}
                            <div>
                                <label className={labelClasses}>
                                    Portée <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.scope}
                                    onChange={(e) => setData('scope', e.target.value)}
                                    className={inputClasses}
                                >
                                    {scopes.length > 0 ? (
                                        scopes.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))
                                    ) : (
                                        <>
                                            <option value="department">Département</option>
                                            <option value="organization">Organisation</option>
                                            <option value="personal">Personnel</option>
                                        </>
                                    )}
                                </select>
                                {errors.scope && <p className={errorClasses}>{errors.scope}</p>}
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3 mt-6">
                            <button
                                type="button"
                                onClick={() => router.get(route('workflows.index'))}
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
                                Créer et configurer les étapes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
