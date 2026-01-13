import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    DocumentDuplicateIcon,
    DocumentArrowDownIcon,
    ClockIcon,
    CheckCircleIcon,
    ArchiveBoxIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import type { DepartmentForm, FormStatus, FormField } from '@/Types/form';

interface FormStats {
    total_submissions: number;
    completed_submissions: number;
    pending_submissions: number;
    avg_completion_time?: string;
}

interface Props {
    form: DepartmentForm;
    fields?: FormField[];
    submissionCount?: number;
    stats?: FormStats;
}

const statusConfig: Record<FormStatus, { label: string; color: string; icon: React.ElementType }> = {
    draft: {
        label: 'Brouillon',
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        icon: ClockIcon,
    },
    published: {
        label: 'Publié',
        color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        icon: CheckCircleIcon,
    },
    archived: {
        label: 'Archivé',
        color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        icon: ArchiveBoxIcon,
    },
};

export default function FormShow({ form, fields: propFields, submissionCount, stats }: Props) {
    const status = statusConfig[form.status];
    const StatusIcon = status.icon;
    const fields = propFields || form.fields || [];

    const handlePublish = () => {
        router.post(route('forms.publish', form.uuid), {}, {
            onSuccess: () => {
                toast.success('Formulaire publié avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la publication');
            },
        });
    };

    const handleDuplicate = () => {
        router.post(route('forms.duplicate', form.uuid), {}, {
            onSuccess: () => {
                toast.success('Formulaire dupliqué avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la duplication');
            },
        });
    };

    const handleArchive = () => {
        router.post(route('forms.archive', form.uuid), {}, {
            onSuccess: () => {
                toast.success('Formulaire archivé');
            },
            onError: () => {
                toast.error('Erreur lors de l\'archivage');
            },
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Formulaire: ${form.name}`} />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-start justify-between mb-6">
                        <div className="flex items-start gap-4">
                            <Link
                                href={route('forms.index')}
                                className="
                                    p-2 rounded-md mt-1
                                    text-gray-600 hover:bg-gray-100
                                    dark:text-gray-400 dark:hover:bg-gray-700
                                "
                            >
                                <ArrowLeftIcon className="h-5 w-5" />
                            </Link>
                            <div>
                                <div className="flex items-center gap-3">
                                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                        {form.name}
                                    </h1>
                                    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium ${status.color}`}>
                                        <StatusIcon className="h-3.5 w-3.5" />
                                        {status.label}
                                    </span>
                                </div>
                                {form.description && (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-2xl">
                                        {form.description}
                                    </p>
                                )}
                                <div className="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                    <span>Version {form.version}</span>
                                    {form.department && (
                                        <span>{form.department.name}</span>
                                    )}
                                    <span>{fields.length} champs</span>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-2">
                            {form.status === 'draft' && (
                                <button
                                    type="button"
                                    onClick={handlePublish}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        bg-green-600 text-white font-medium text-sm
                                        hover:bg-green-700
                                    "
                                >
                                    <DocumentArrowDownIcon className="h-4 w-4" />
                                    Publier
                                </button>
                            )}
                            {form.status === 'published' && (
                                <button
                                    type="button"
                                    onClick={handleArchive}
                                    className="
                                        inline-flex items-center gap-2 px-3 py-2 rounded-md
                                        border border-gray-300 dark:border-gray-600
                                        text-gray-700 dark:text-gray-300
                                        hover:bg-gray-50 dark:hover:bg-gray-700
                                        text-sm
                                    "
                                >
                                    <ArchiveBoxIcon className="h-4 w-4" />
                                    Archiver
                                </button>
                            )}
                            <Link
                                href={route('forms.edit', form.uuid)}
                                className="
                                    inline-flex items-center gap-2 px-3 py-2 rounded-md
                                    border border-gray-300 dark:border-gray-600
                                    text-gray-700 dark:text-gray-300
                                    hover:bg-gray-50 dark:hover:bg-gray-700
                                    text-sm
                                "
                            >
                                <PencilIcon className="h-4 w-4" />
                                Modifier
                            </Link>
                            <button
                                type="button"
                                onClick={handleDuplicate}
                                className="
                                    inline-flex items-center gap-2 px-3 py-2 rounded-md
                                    border border-gray-300 dark:border-gray-600
                                    text-gray-700 dark:text-gray-300
                                    hover:bg-gray-50 dark:hover:bg-gray-700
                                    text-sm
                                "
                            >
                                <DocumentDuplicateIcon className="h-4 w-4" />
                                Dupliquer
                            </button>
                        </div>
                    </div>

                    {/* Stats */}
                    {stats && (
                        <div className="grid grid-cols-3 gap-4 mb-6">
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <p className="text-sm text-gray-500 dark:text-gray-400">Total soumissions</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    {stats.total_submissions}
                                </p>
                            </div>
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <p className="text-sm text-gray-500 dark:text-gray-400">Complétées</p>
                                <p className="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                                    {stats.completed_submissions}
                                </p>
                            </div>
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <p className="text-sm text-gray-500 dark:text-gray-400">En attente</p>
                                <p className="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                                    {stats.pending_submissions}
                                </p>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Form Fields */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                <h2 className="font-medium text-gray-900 dark:text-white">
                                    Champs du formulaire
                                </h2>
                            </div>
                            <div className="p-4">
                                {fields.length === 0 ? (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                                        Aucun champ configuré
                                    </p>
                                ) : (
                                    <div className="space-y-3">
                                        {fields.map((field, index) => (
                                            <div
                                                key={field.uuid}
                                                className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                            >
                                                <span className="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded bg-gray-200 dark:bg-gray-600 text-xs font-medium text-gray-600 dark:text-gray-300">
                                                    {index + 1}
                                                </span>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {field.label}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        {field.type}
                                                        {field.is_required && (
                                                            <span className="ml-2 text-red-500">*</span>
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Form Settings */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                <h2 className="font-medium text-gray-900 dark:text-white">
                                    Paramètres
                                </h2>
                            </div>
                            <div className="p-4 space-y-4">
                                <div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                        Formulaire multi-étapes
                                    </p>
                                    <p className="text-sm text-gray-900 dark:text-white mt-1">
                                        {form.is_multi_step ? 'Oui' : 'Non'}
                                    </p>
                                </div>

                                {form.success_message && (
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                            Message de succès
                                        </p>
                                        <p className="text-sm text-gray-900 dark:text-white mt-1">
                                            {form.success_message}
                                        </p>
                                    </div>
                                )}

                                {form.redirect_url && (
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                            URL de redirection
                                        </p>
                                        <p className="text-sm text-gray-900 dark:text-white mt-1">
                                            {form.redirect_url}
                                        </p>
                                    </div>
                                )}

                                {form.published_at && (
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                            Publié le
                                        </p>
                                        <p className="text-sm text-gray-900 dark:text-white mt-1">
                                            {new Date(form.published_at).toLocaleDateString('fr-FR', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Recent Submissions */}
                    {form.status === 'published' && (
                        <div className="mt-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                                <h2 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                    <ClipboardDocumentListIcon className="h-5 w-5" />
                                    Soumissions récentes
                                </h2>
                                <Link
                                    href={route('forms.submissions', form.uuid)}
                                    className="text-sm text-primary hover:underline"
                                >
                                    Voir tout
                                </Link>
                            </div>
                            <div className="p-4">
                                <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                                    Aucune soumission récente
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
