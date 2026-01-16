import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import SafeHTML from '@/Components/SafeHTML';
import { toast } from 'sonner';
import {
    ArrowLeftIcon,
    UserIcon,
    CalendarIcon,
    DocumentTextIcon,
    GlobeAltIcon,
    ComputerDesktopIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    PaperAirplaneIcon,
    PencilIcon,
} from '@heroicons/react/24/outline';
import type { DepartmentForm, FormField, DepartmentFormSubmission, SubmissionStatus } from '@/Types/form';

interface StatusOption {
    value: string;
    label: string;
    color: string;
}

interface Props {
    form: DepartmentForm;
    fields: FormField[];
    submission: DepartmentFormSubmission & {
        user?: {
            id: number;
            full_name: string;
            email: string;
        };
        processor?: {
            id: number;
            full_name: string;
        };
        notes?: string;
    };
    canProcess?: boolean;
    statuses?: StatusOption[];
}

const statusConfig: Record<SubmissionStatus, { label: string; color: string; icon: React.ElementType }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', icon: PencilIcon },
    submitted: { label: 'Soumis', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', icon: PaperAirplaneIcon },
    processing: { label: 'En cours', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', icon: ArrowPathIcon },
    completed: { label: 'Terminé', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', icon: CheckCircleIcon },
    rejected: { label: 'Rejeté', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', icon: XCircleIcon },
};

export default function FormSubmissionShow({ form, fields, submission, canProcess = false, statuses = [] }: Props) {
    const status = statusConfig[submission.status] || statusConfig.draft;
    const [selectedStatus, setSelectedStatus] = useState(submission.status);
    const [notes, setNotes] = useState(submission.notes || '');
    const [isUpdating, setIsUpdating] = useState(false);

    const formatDateTime = (dateString: string | null | undefined) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getFieldValue = (field: FormField): string => {
        const value = submission.data?.[field.name];
        if (value === undefined || value === null || value === '') {
            return '-';
        }
        if (typeof value === 'boolean') {
            return value ? 'Oui' : 'Non';
        }
        if (Array.isArray(value)) {
            return value.join(', ');
        }
        if (typeof value === 'object') {
            return JSON.stringify(value);
        }
        return String(value);
    };

    const isRichTextField = (field: FormField): boolean => {
        return field.type === 'rich_text';
    };

    const renderFieldValue = (field: FormField) => {
        const value = getFieldValue(field);

        if (value === '-') {
            return <span className="text-gray-400">-</span>;
        }

        // Render rich text content safely with proper HTML rendering
        if (isRichTextField(field)) {
            return (
                <SafeHTML
                    html={value}
                    className="prose prose-sm dark:prose-invert max-w-none"
                    data-testid={`rich-text-${field.name}`}
                />
            );
        }

        // Regular text content
        return value;
    };

    // Flatten fields for display (including nested children)
    const flattenFields = (fieldsToFlatten: FormField[]): FormField[] => {
        const result: FormField[] = [];
        const processField = (field: FormField) => {
            // Skip layout-only fields (sections, rows, columns)
            if (!['section', 'row', 'column'].includes(field.type)) {
                result.push(field);
            }
            if (field.children && field.children.length > 0) {
                field.children.forEach(processField);
            }
        };
        fieldsToFlatten.forEach(processField);
        return result;
    };

    const displayFields = flattenFields(fields);

    const handleUpdateStatus = () => {
        setIsUpdating(true);
        router.post(
            route('form-submissions.update-status', submission.uuid),
            {
                status: selectedStatus,
                notes: notes,
            },
            {
                onSuccess: () => {
                    toast.success('Statut mis à jour avec succès');
                },
                onError: () => {
                    toast.error('Erreur lors de la mise à jour');
                },
                onFinish: () => {
                    setIsUpdating(false);
                },
            }
        );
    };

    const StatusIcon = status.icon;

    return (
        <DashboardLayout>
            <Head title={`Soumission - ${form.name}`} />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href={route('forms.submissions', form.uuid)}
                            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                        >
                            <ArrowLeftIcon className="h-4 w-4 mr-1" />
                            Retour aux soumissions
                        </Link>
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    Soumission
                                </h1>
                                <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {form.name}
                                </p>
                            </div>
                            <Badge className={`${status.color} flex items-center gap-1`}>
                                <StatusIcon className="h-4 w-4" />
                                {status.label}
                            </Badge>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content - Submission Data */}
                        <div className="lg:col-span-2 space-y-6">
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                    <h2 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        <DocumentTextIcon className="h-5 w-5" />
                                        Données soumises
                                    </h2>
                                </div>
                                <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {displayFields.length === 0 ? (
                                        <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                                            Aucun champ à afficher
                                        </div>
                                    ) : (
                                        displayFields.map((field) => (
                                            <div key={field.uuid} className="p-4">
                                                <div className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                    {field.label}
                                                </div>
                                                <div className="text-gray-900 dark:text-white">
                                                    {renderFieldValue(field)}
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>

                            {/* Processing Section - Only visible if canProcess */}
                            {canProcess && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                        <h2 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                            <ChatBubbleLeftRightIcon className="h-5 w-5" />
                                            Traitement de la soumission
                                        </h2>
                                    </div>
                                    <div className="p-4 space-y-4">
                                        {/* Status Selection */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Changer le statut
                                            </label>
                                            <div className="flex flex-wrap gap-2">
                                                {statuses.map((statusOption) => {
                                                    const config = statusConfig[statusOption.value as SubmissionStatus];
                                                    const Icon = config?.icon || PencilIcon;
                                                    const isSelected = selectedStatus === statusOption.value;
                                                    return (
                                                        <button
                                                            key={statusOption.value}
                                                            type="button"
                                                            onClick={() => setSelectedStatus(statusOption.value as SubmissionStatus)}
                                                            className={`
                                                                inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-all
                                                                ${isSelected
                                                                    ? config?.color || 'bg-gray-100 text-gray-700'
                                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                                                }
                                                                ${isSelected ? 'ring-2 ring-offset-2 ring-primary dark:ring-offset-gray-800' : ''}
                                                            `}
                                                        >
                                                            <Icon className="h-4 w-4" />
                                                            {statusOption.label}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>

                                        {/* Notes */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Notes de traitement
                                            </label>
                                            <textarea
                                                value={notes}
                                                onChange={(e) => setNotes(e.target.value)}
                                                rows={4}
                                                placeholder="Ajoutez des notes sur le traitement de cette soumission..."
                                                className="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-transparent"
                                            />
                                        </div>

                                        {/* Submit Button */}
                                        <div className="flex justify-end">
                                            <Button
                                                onClick={handleUpdateStatus}
                                                disabled={isUpdating}
                                            >
                                                {isUpdating ? (
                                                    <>
                                                        <ArrowPathIcon className="h-4 w-4 mr-2 animate-spin" />
                                                        Mise à jour...
                                                    </>
                                                ) : (
                                                    <>
                                                        <CheckCircleIcon className="h-4 w-4 mr-2" />
                                                        Enregistrer les modifications
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Display existing notes if not canProcess but notes exist */}
                            {!canProcess && submission.notes && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                        <h2 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                            <ChatBubbleLeftRightIcon className="h-5 w-5" />
                                            Notes de traitement
                                        </h2>
                                    </div>
                                    <div className="p-4">
                                        <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                            {submission.notes}
                                        </p>
                                        {submission.processor && (
                                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                Par {submission.processor.full_name}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Sidebar - Metadata */}
                        <div className="space-y-6">
                            {/* User Info */}
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                    <h3 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        <UserIcon className="h-5 w-5" />
                                        Utilisateur
                                    </h3>
                                </div>
                                <div className="p-4 space-y-3">
                                    <div>
                                        <span className="text-sm text-gray-500 dark:text-gray-400">Nom</span>
                                        <p className="text-gray-900 dark:text-white">
                                            {submission.user?.full_name || 'Utilisateur inconnu'}
                                        </p>
                                    </div>
                                    {submission.user?.email && (
                                        <div>
                                            <span className="text-sm text-gray-500 dark:text-gray-400">Email</span>
                                            <p className="text-gray-900 dark:text-white">
                                                {submission.user.email}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Dates */}
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                    <h3 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        <CalendarIcon className="h-5 w-5" />
                                        Dates
                                    </h3>
                                </div>
                                <div className="p-4 space-y-3">
                                    <div>
                                        <span className="text-sm text-gray-500 dark:text-gray-400">Créé le</span>
                                        <p className="text-gray-900 dark:text-white">
                                            {formatDateTime(submission.created_at)}
                                        </p>
                                    </div>
                                    {submission.submitted_at && (
                                        <div>
                                            <span className="text-sm text-gray-500 dark:text-gray-400">Soumis le</span>
                                            <p className="text-gray-900 dark:text-white">
                                                {formatDateTime(submission.submitted_at)}
                                            </p>
                                        </div>
                                    )}
                                    {submission.processed_at && (
                                        <div>
                                            <span className="text-sm text-gray-500 dark:text-gray-400">Traité le</span>
                                            <p className="text-gray-900 dark:text-white">
                                                {formatDateTime(submission.processed_at)}
                                            </p>
                                            {submission.processor && (
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    par {submission.processor.full_name}
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Technical Info */}
                            {(submission.ip_address || submission.user_agent) && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                                        <h3 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                            <ComputerDesktopIcon className="h-5 w-5" />
                                            Informations techniques
                                        </h3>
                                    </div>
                                    <div className="p-4 space-y-3">
                                        {submission.ip_address && (
                                            <div>
                                                <span className="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                    <GlobeAltIcon className="h-4 w-4" />
                                                    Adresse IP
                                                </span>
                                                <p className="text-gray-900 dark:text-white font-mono text-sm">
                                                    {submission.ip_address}
                                                </p>
                                            </div>
                                        )}
                                        {submission.user_agent && (
                                            <div>
                                                <span className="text-sm text-gray-500 dark:text-gray-400">User Agent</span>
                                                <p className="text-gray-900 dark:text-white text-xs font-mono break-all">
                                                    {submission.user_agent}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Actions */}
                            <div className="flex flex-col gap-2">
                                <Button variant="outline" asChild>
                                    <Link href={route('forms.submissions', form.uuid)}>
                                        Retour aux soumissions
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
