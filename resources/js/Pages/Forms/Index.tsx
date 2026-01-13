import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    DocumentDuplicateIcon,
    DocumentArrowDownIcon,
    Squares2X2Icon,
    ListBulletIcon,
    TableCellsIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import type { DepartmentForm, FormStatus } from '@/Types/form';
import type { PaginatedData } from '@/Types';

type ViewMode = 'grid' | 'list' | 'table';

const viewModes: { value: ViewMode; label: string; icon: React.ElementType }[] = [
    { value: 'grid', label: 'Grille', icon: Squares2X2Icon },
    { value: 'list', label: 'Liste', icon: ListBulletIcon },
    { value: 'table', label: 'Tableau', icon: TableCellsIcon },
];

interface Props {
    forms: PaginatedData<DepartmentForm>;
}

const statusConfig: Record<FormStatus, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
    published: { label: 'Publié', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
    archived: { label: 'Archivé', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
};

export default function FormsIndex({ forms: paginatedForms }: Props) {
    const forms = paginatedForms?.data || [];
    const [deleteForm, setDeleteForm] = React.useState<DepartmentForm | null>(null);
    const [viewMode, setViewMode] = React.useState<ViewMode>('grid');

    const handleDelete = () => {
        if (!deleteForm) return;

        router.delete(route('forms.destroy', deleteForm.uuid), {
            onSuccess: () => {
                toast.success('Formulaire supprimé avec succès');
                setDeleteForm(null);
            },
            onError: () => {
                toast.error('Erreur lors de la suppression');
            },
        });
    };

    const handleDuplicate = (form: DepartmentForm) => {
        router.post(route('forms.duplicate', form.uuid), {}, {
            onSuccess: () => {
                toast.success('Formulaire dupliqué avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la duplication');
            },
        });
    };

    const handlePublish = (form: DepartmentForm) => {
        router.post(route('forms.publish', form.uuid), {}, {
            onSuccess: () => {
                toast.success('Formulaire publié avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la publication');
            },
        });
    };

    return (
        <DashboardLayout>
            <Head title="Formulaires" />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Formulaires
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Créez et gérez vos formulaires personnalisés
                            </p>
                        </div>
                        <div className="flex items-center gap-4">
                            {/* View Toggle */}
                            <div className="flex items-center border border-gray-300 dark:border-gray-600 rounded-md overflow-hidden">
                                {viewModes.map((mode, index) => {
                                    const Icon = mode.icon;
                                    return (
                                        <button
                                            key={mode.value}
                                            type="button"
                                            onClick={() => setViewMode(mode.value)}
                                            title={mode.label}
                                            className={`
                                                p-2
                                                ${index > 0 ? 'border-l border-gray-300 dark:border-gray-600' : ''}
                                                ${viewMode === mode.value
                                                    ? 'bg-primary text-white'
                                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                                }
                                            `}
                                        >
                                            <Icon className="h-5 w-5" />
                                        </button>
                                    );
                                })}
                            </div>
                            <Link
                                href={route('forms.create')}
                                className="
                                    inline-flex items-center gap-2 px-4 py-2 rounded-md
                                    bg-primary text-white font-medium
                                    hover:bg-primary/90 transition-colors
                                "
                            >
                                <PlusIcon className="h-5 w-5" />
                                Nouveau formulaire
                            </Link>
                        </div>
                    </div>

                    {/* Forms Content */}
                    {forms.length === 0 ? (
                        <div className="
                            bg-white dark:bg-gray-800 rounded-lg
                            border border-gray-200 dark:border-gray-700
                            p-12 text-center
                        ">
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Aucun formulaire créé
                            </p>
                            <Link
                                href={route('forms.create')}
                                className="
                                    inline-flex items-center gap-2 px-4 py-2 rounded-md
                                    bg-primary text-white font-medium
                                    hover:bg-primary/90 transition-colors
                                "
                            >
                                <PlusIcon className="h-5 w-5" />
                                Créer votre premier formulaire
                            </Link>
                        </div>
                    ) : (
                        <>
                            {/* Grid View */}
                            {viewMode === 'grid' && (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    {forms.map((form) => {
                                        const status = statusConfig[form.status];
                                        return (
                                            <div
                                                key={form.uuid}
                                                className="
                                                    bg-white dark:bg-gray-800 rounded-lg
                                                    border border-gray-200 dark:border-gray-700
                                                    p-4 hover:shadow-md transition-shadow
                                                "
                                            >
                                                <div className="flex items-start justify-between mb-3">
                                                    <div className="flex-1 min-w-0">
                                                        <Link
                                                            href={route('forms.show', form.uuid)}
                                                            className="text-lg font-medium text-gray-900 dark:text-white truncate block hover:text-primary dark:hover:text-primary transition-colors"
                                                        >
                                                            {form.name}
                                                        </Link>
                                                        <span className={`inline-block mt-1 px-2 py-0.5 rounded text-xs font-medium ${status.color}`}>
                                                            {status.label}
                                                        </span>
                                                    </div>
                                                </div>

                                                {form.description && (
                                                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">
                                                        {form.description}
                                                    </p>
                                                )}

                                                <div className="flex items-center gap-4 text-xs text-gray-400 mb-4">
                                                    <span>{form.fields_count || 0} champs</span>
                                                    <span>{form.submissions_count || 0} soumissions</span>
                                                </div>

                                                {/* Actions */}
                                                <div className="flex items-center justify-end gap-1 pt-3 border-t border-gray-100 dark:border-gray-700">
                                                    {form.status === 'draft' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handlePublish(form)}
                                                            className="p-2 rounded-md text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20"
                                                            title="Publier"
                                                        >
                                                            <DocumentArrowDownIcon className="h-5 w-5" />
                                                        </button>
                                                    )}
                                                    <Link
                                                        href={route('forms.show', form.uuid)}
                                                        className="p-2 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                                        title="Voir"
                                                    >
                                                        <EyeIcon className="h-5 w-5" />
                                                    </Link>
                                                    <Link
                                                        href={route('forms.edit', form.uuid)}
                                                        className="p-2 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                                        title="Modifier"
                                                    >
                                                        <PencilIcon className="h-5 w-5" />
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDuplicate(form)}
                                                        className="p-2 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                                        title="Dupliquer"
                                                    >
                                                        <DocumentDuplicateIcon className="h-5 w-5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleteForm(form)}
                                                        className="p-2 rounded-md text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                        title="Supprimer"
                                                    >
                                                        <TrashIcon className="h-5 w-5" />
                                                    </button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            {/* List View */}
                            {viewMode === 'list' && (
                                <div className="space-y-2">
                                    {forms.map((form) => {
                                        const status = statusConfig[form.status];
                                        return (
                                            <div
                                                key={form.uuid}
                                                className="
                                                    bg-white dark:bg-gray-800 rounded-lg
                                                    border border-gray-200 dark:border-gray-700
                                                    p-4 hover:shadow-md transition-shadow
                                                    flex items-center gap-4
                                                "
                                            >
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-3">
                                                        <Link
                                                            href={route('forms.show', form.uuid)}
                                                            className="text-base font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-primary transition-colors"
                                                        >
                                                            {form.name}
                                                        </Link>
                                                        <span className={`px-2 py-0.5 rounded text-xs font-medium ${status.color}`}>
                                                            {status.label}
                                                        </span>
                                                    </div>
                                                    {form.description && (
                                                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">
                                                            {form.description}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-6 text-sm text-gray-500 dark:text-gray-400">
                                                    <span>{form.fields_count || 0} champs</span>
                                                    <span>{form.submissions_count || 0} soumissions</span>
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    {form.status === 'draft' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handlePublish(form)}
                                                            className="p-2 rounded-md text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20"
                                                            title="Publier"
                                                        >
                                                            <DocumentArrowDownIcon className="h-5 w-5" />
                                                        </button>
                                                    )}
                                                    <Link
                                                        href={route('forms.show', form.uuid)}
                                                        className="p-2 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                                        title="Voir"
                                                    >
                                                        <EyeIcon className="h-5 w-5" />
                                                    </Link>
                                                    <Link
                                                        href={route('forms.edit', form.uuid)}
                                                        className="p-2 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                                        title="Modifier"
                                                    >
                                                        <PencilIcon className="h-5 w-5" />
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDuplicate(form)}
                                                        className="p-2 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                                        title="Dupliquer"
                                                    >
                                                        <DocumentDuplicateIcon className="h-5 w-5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleteForm(form)}
                                                        className="p-2 rounded-md text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                        title="Supprimer"
                                                    >
                                                        <TrashIcon className="h-5 w-5" />
                                                    </button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            {/* Table View */}
                            {viewMode === 'table' && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-900">
                                            <tr>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Nom
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Statut
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Champs
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Soumissions
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {forms.map((form) => {
                                                const status = statusConfig[form.status];
                                                return (
                                                    <tr key={form.uuid} className="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                        <td className="px-6 py-4">
                                                            <Link
                                                                href={route('forms.show', form.uuid)}
                                                                className="text-sm font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-primary transition-colors"
                                                            >
                                                                {form.name}
                                                            </Link>
                                                            {form.description && (
                                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate max-w-md">
                                                                    {form.description}
                                                                </p>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className={`px-2 py-1 rounded text-xs font-medium ${status.color}`}>
                                                                {status.label}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                            {form.fields_count || 0}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                            {form.submissions_count || 0}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right">
                                                            <div className="flex items-center justify-end gap-1">
                                                                {form.status === 'draft' && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handlePublish(form)}
                                                                        className="p-1.5 rounded-md text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20"
                                                                        title="Publier"
                                                                    >
                                                                        <DocumentArrowDownIcon className="h-4 w-4" />
                                                                    </button>
                                                                )}
                                                                <Link
                                                                    href={route('forms.show', form.uuid)}
                                                                    className="p-1.5 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                                                    title="Voir"
                                                                >
                                                                    <EyeIcon className="h-4 w-4" />
                                                                </Link>
                                                                <Link
                                                                    href={route('forms.edit', form.uuid)}
                                                                    className="p-1.5 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                                                    title="Modifier"
                                                                >
                                                                    <PencilIcon className="h-4 w-4" />
                                                                </Link>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDuplicate(form)}
                                                                    className="p-1.5 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                                                    title="Dupliquer"
                                                                >
                                                                    <DocumentDuplicateIcon className="h-4 w-4" />
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setDeleteForm(form)}
                                                                    className="p-1.5 rounded-md text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                                    title="Supprimer"
                                                                >
                                                                    <TrashIcon className="h-4 w-4" />
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
                        </>
                    )}
                </div>
            </div>

            {/* Delete Confirmation */}
            <DeleteConfirmationDialog
                open={!!deleteForm}
                onOpenChange={(open) => !open && setDeleteForm(null)}
                onConfirm={handleDelete}
                title="Supprimer le formulaire"
                description={`Êtes-vous sûr de vouloir supprimer le formulaire "${deleteForm?.name}" ? Cette action est irréversible et supprimera également toutes les soumissions associées.`}
            />
        </DashboardLayout>
    );
}
