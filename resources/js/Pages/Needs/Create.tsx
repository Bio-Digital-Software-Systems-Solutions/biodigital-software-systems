import React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeftIcon, CalendarIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import DatePicker, { registerLocale } from 'react-datepicker';
import { fr } from 'date-fns/locale';
import { format, parse } from 'date-fns';
import 'react-datepicker/dist/react-datepicker.css';
import type { Department, User } from '@/Types';
import type { NeedCategory, NeedPriority } from '@/Types/need';
import { SearchableSelect, SelectOption } from '@/Components/ui/searchable-select';

// Register French locale for DatePicker
registerLocale('fr', fr);

interface Props {
    departments: Department[];
    users?: User[];
    departmentId?: string | number;
}

const categoryOptions: { value: NeedCategory; label: string }[] = [
    { value: 'equipment', label: 'Équipement' },
    { value: 'software', label: 'Logiciel' },
    { value: 'furniture', label: 'Mobilier' },
    { value: 'supplies', label: 'Fournitures' },
    { value: 'services', label: 'Services' },
    { value: 'training', label: 'Formation' },
    { value: 'recruitment', label: 'Recrutement' },
    { value: 'other', label: 'Autre' },
];

const priorityOptions: { value: NeedPriority; label: string }[] = [
    { value: 'critical', label: 'Critique' },
    { value: 'high', label: 'Haute' },
    { value: 'medium', label: 'Moyenne' },
    { value: 'low', label: 'Basse' },
];

export default function NeedCreate({ departments = [], users = [], departmentId }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        justification: '',
        category: 'equipment' as NeedCategory,
        priority: 'medium' as NeedPriority,
        department_id: departmentId?.toString() || '',
        assigned_to_id: '',
        estimated_cost: '',
        needed_by: '',
    });

    const handleSubmit = (e: React.FormEvent, asDraft = false) => {
        e.preventDefault();

        post(route('needs.store', { as_draft: asDraft }), {
            onSuccess: () => {
                toast.success(asDraft ? 'Brouillon enregistré' : 'Besoin créé avec succès');
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
            <Head title="Nouveau besoin" />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-center gap-4 mb-6">
                        <button
                            type="button"
                            onClick={() => router.get(route('needs.index'))}
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
                                Nouveau besoin
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Créez une nouvelle demande de besoin
                            </p>
                        </div>
                    </div>

                    {/* Form */}
                    <form onSubmit={(e) => handleSubmit(e, false)}>
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                            {/* Title */}
                            <div>
                                <label className={labelClasses}>
                                    Titre <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className={inputClasses}
                                    placeholder="Décrivez brièvement votre besoin"
                                />
                                {errors.title && <p className={errorClasses}>{errors.title}</p>}
                            </div>

                            {/* Category & Priority */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className={labelClasses}>
                                        Catégorie <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={data.category}
                                        onChange={(e) => setData('category', e.target.value as NeedCategory)}
                                        className={inputClasses}
                                    >
                                        {categoryOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.category && <p className={errorClasses}>{errors.category}</p>}
                                </div>

                                <div>
                                    <label className={labelClasses}>
                                        Priorité <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={data.priority}
                                        onChange={(e) => setData('priority', e.target.value as NeedPriority)}
                                        className={inputClasses}
                                    >
                                        {priorityOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.priority && <p className={errorClasses}>{errors.priority}</p>}
                                </div>
                            </div>

                            {/* Description */}
                            <div>
                                <label className={labelClasses}>Description</label>
                                <textarea
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={4}
                                    className={inputClasses}
                                    placeholder="Décrivez en détail votre besoin..."
                                />
                                {errors.description && <p className={errorClasses}>{errors.description}</p>}
                            </div>

                            {/* Justification */}
                            <div>
                                <label className={labelClasses}>Justification</label>
                                <textarea
                                    value={data.justification}
                                    onChange={(e) => setData('justification', e.target.value)}
                                    rows={3}
                                    className={inputClasses}
                                    placeholder="Pourquoi ce besoin est-il nécessaire?"
                                />
                                {errors.justification && <p className={errorClasses}>{errors.justification}</p>}
                            </div>

                            {/* Department & Assignee */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className={labelClasses} htmlFor="department_id">Département</label>
                                    <SearchableSelect
                                        id="department_id"
                                        options={departments.map((dept): SelectOption => ({
                                            value: dept.id,
                                            label: dept.name,
                                        }))}
                                        value={data.department_id ? Number(data.department_id) : null}
                                        onChange={(value) => setData('department_id', value?.toString() || '')}
                                        placeholder="Rechercher un département..."
                                        noOptionsMessage="Aucun département trouvé"
                                    />
                                    {errors.department_id && <p className={errorClasses}>{errors.department_id}</p>}
                                </div>

                                <div>
                                    <label className={labelClasses} htmlFor="assigned_to_id">Assigné à</label>
                                    <SearchableSelect
                                        id="assigned_to_id"
                                        options={users.map((user): SelectOption => ({
                                            value: user.id,
                                            label: user.name,
                                        }))}
                                        value={data.assigned_to_id ? Number(data.assigned_to_id) : null}
                                        onChange={(value) => setData('assigned_to_id', value?.toString() || '')}
                                        placeholder="Rechercher un utilisateur..."
                                        noOptionsMessage="Aucun utilisateur trouvé"
                                    />
                                    {errors.assigned_to_id && <p className={errorClasses}>{errors.assigned_to_id}</p>}
                                </div>
                            </div>

                            {/* Amount & Date */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className={labelClasses}>Montant estimé (€)</label>
                                    <input
                                        type="number"
                                        value={data.estimated_cost}
                                        onChange={(e) => setData('estimated_cost', e.target.value)}
                                        className={inputClasses}
                                        min="0"
                                        step="0.01"
                                    />
                                    {errors.estimated_cost && <p className={errorClasses}>{errors.estimated_cost}</p>}
                                </div>

                                <div>
                                    <label className={labelClasses}>Date souhaitée</label>
                                    <div className="relative">
                                        <DatePicker
                                            selected={data.needed_by ? parse(data.needed_by, 'yyyy-MM-dd', new Date()) : null}
                                            onChange={(date: Date | null) => {
                                                setData('needed_by', date ? format(date, 'yyyy-MM-dd') : '');
                                            }}
                                            locale="fr"
                                            dateFormat="dd/MM/yyyy"
                                            minDate={new Date()}
                                            placeholderText="Sélectionner une date"
                                            className={inputClasses}
                                            calendarClassName="shadow-lg"
                                            showPopperArrow={false}
                                            isClearable
                                        />
                                        <CalendarIcon className="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                                    </div>
                                    {errors.needed_by && <p className={errorClasses}>{errors.needed_by}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3 mt-6">
                            <button
                                type="button"
                                onClick={() => router.get(route('needs.index'))}
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
                                type="button"
                                onClick={(e) => handleSubmit(e, true)}
                                disabled={processing}
                                className="
                                    px-4 py-2 rounded-md
                                    border border-gray-300 dark:border-gray-600
                                    text-gray-700 dark:text-gray-300
                                    hover:bg-gray-50 dark:hover:bg-gray-700
                                    disabled:opacity-50
                                "
                            >
                                Enregistrer comme brouillon
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
                                Soumettre
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
