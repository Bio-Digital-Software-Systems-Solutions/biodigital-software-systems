import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    ArrowLeftIcon,
    EyeIcon,
    UserIcon,
    CalendarIcon,
    ClockIcon,
    TrashIcon,
} from '@heroicons/react/24/outline';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { toast } from 'sonner';
import type { DepartmentForm, DepartmentFormSubmission, SubmissionStatus } from '@/Types/form';
import type { User } from '@/Types';

interface LaravelPagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Props {
    form: DepartmentForm;
    submissions: LaravelPagination<DepartmentFormSubmission & { user?: User }>;
    canManageSubmissions: boolean;
    currentUserId: number;
}

const statusConfig: Record<SubmissionStatus, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
    submitted: { label: 'Soumis', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
    processing: { label: 'En cours', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
    completed: { label: 'Terminé', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
    rejected: { label: 'Rejeté', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
};

export default function FormSubmissions({ form, submissions, canManageSubmissions, currentUserId }: Props) {
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [submissionToDelete, setSubmissionToDelete] = useState<(DepartmentFormSubmission & { user?: User }) | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    const formatTime = (dateString: string) => {
        return new Date(dateString).toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    // Check if user can delete a specific submission
    const canDeleteSubmission = (submission: DepartmentFormSubmission & { user?: User }) => {
        // Admin or users with manage forms permission can delete any submission
        if (canManageSubmissions) return true;
        // Users can delete their own draft submissions
        return submission.status === 'draft' && submission.user_id === currentUserId;
    };

    const handleDeleteClick = (submission: DepartmentFormSubmission & { user?: User }) => {
        setSubmissionToDelete(submission);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (!submissionToDelete) return;

        setIsDeleting(true);
        router.delete(route('form-submissions.destroy', submissionToDelete.uuid), {
            onSuccess: () => {
                toast.success('Soumission supprimée avec succès');
                setDeleteDialogOpen(false);
                setSubmissionToDelete(null);
            },
            onError: () => {
                toast.error('Erreur lors de la suppression de la soumission');
            },
            onFinish: () => {
                setIsDeleting(false);
            },
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Soumissions - ${form.name}`} />

            <div className="py-6">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href={route('forms.show', form.uuid)}
                            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                        >
                            <ArrowLeftIcon className="h-4 w-4 mr-1" />
                            Retour au formulaire
                        </Link>
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    Soumissions
                                </h1>
                                <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {form.name} - {submissions.total} soumission(s)
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Submissions List */}
                    {submissions.data.length === 0 ? (
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
                            <p className="text-gray-500 dark:text-gray-400">
                                Aucune soumission pour ce formulaire
                            </p>
                        </div>
                    ) : (
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Utilisateur
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Statut
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Date de création
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Date de soumission
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {submissions.data.map((submission) => {
                                        const status = statusConfig[submission.status];
                                        return (
                                            <tr key={submission.uuid} className="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <UserIcon className="h-5 w-5 text-gray-400 mr-2" />
                                                        <span className="text-sm text-gray-900 dark:text-white">
                                                            {submission.user?.full_name || 'Utilisateur inconnu'}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <Badge className={status.color}>
                                                        {status.label}
                                                    </Badge>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                        <CalendarIcon className="h-4 w-4 mr-1" />
                                                        {formatDate(submission.created_at)}
                                                        <ClockIcon className="h-4 w-4 ml-2 mr-1" />
                                                        {formatTime(submission.created_at)}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {submission.submitted_at ? (
                                                        <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                            <CalendarIcon className="h-4 w-4 mr-1" />
                                                            {formatDate(submission.submitted_at)}
                                                            <ClockIcon className="h-4 w-4 ml-2 mr-1" />
                                                            {formatTime(submission.submitted_at)}
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm text-gray-400 dark:text-gray-500">-</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link href={route('form-submissions.show', submission.uuid)}>
                                                                <EyeIcon className="h-4 w-4 mr-1" />
                                                                Voir
                                                            </Link>
                                                        </Button>
                                                        {canDeleteSubmission(submission) && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                                                                onClick={() => handleDeleteClick(submission)}
                                                            >
                                                                <TrashIcon className="h-4 w-4 mr-1" />
                                                                Supprimer
                                                            </Button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>

                            {/* Pagination */}
                            {submissions.last_page > 1 && (
                                <div className="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-2">
                                    {Array.from({ length: submissions.last_page }, (_, i) => i + 1).map((page) => (
                                        <Link
                                            key={page}
                                            href={route('forms.submissions', { form: form.uuid, page })}
                                            className={`
                                                px-3 py-1 rounded text-sm
                                                ${page === submissions.current_page
                                                    ? 'bg-primary text-white'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                                }
                                            `}
                                        >
                                            {page}
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={handleDeleteConfirm}
                title="Supprimer la soumission"
                description={`Êtes-vous sûr de vouloir supprimer la soumission de ${submissionToDelete?.user?.full_name || 'cet utilisateur'} ? Cette action est irréversible.`}
                isDeleting={isDeleting}
            />
        </DashboardLayout>
    );
}
