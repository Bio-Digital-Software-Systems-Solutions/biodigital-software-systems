import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import {
    ArrowLeftIcon,
    PencilIcon,
    DocumentDuplicateIcon,
    DocumentArrowDownIcon,
    ClockIcon,
    CheckCircleIcon,
    ArchiveBoxIcon,
    ClipboardDocumentListIcon,
    UserIcon,
    EyeIcon,
    PlayIcon,
    LinkIcon,
    TrashIcon,
    ClipboardDocumentIcon,
    ArrowDownTrayIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import type { DepartmentForm, FormStatus, FormField, SubmissionStatus } from '@/Types/form';

interface ShareLinkData {
    url: string;
    token: string;
    expires_at: string;
    max_uses: number | null;
    qr_code: string | null;
}

interface FormStats {
    total_submissions: number;
    completed_submissions: number;
    pending_submissions: number;
    avg_completion_time?: string;
}

interface RecentSubmission {
    uuid: string;
    status: SubmissionStatus;
    created_at: string;
    submitted_at: string | null;
    user?: {
        id: number;
        full_name: string;
    };
}

interface Props {
    form: DepartmentForm;
    fields?: FormField[];
    submissionCount?: number;
    stats?: FormStats;
    recentSubmissions?: RecentSubmission[];
}

const submissionStatusConfig: Record<SubmissionStatus, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
    submitted: { label: 'Soumis', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
    processing: { label: 'En cours', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
    completed: { label: 'Termine', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
    rejected: { label: 'Rejete', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
};

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

export default function FormShow({ form, fields: propFields, submissionCount, stats, recentSubmissions = [] }: Props) {
    const status = statusConfig[form.status];
    const [isOpeningForm, setIsOpeningForm] = useState(false);
    const [shareModalOpen, setShareModalOpen] = useState(false);
    const [shareData, setShareData] = useState<ShareLinkData | null>(null);
    const [isGeneratingLink, setIsGeneratingLink] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };
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

    const handleOpenForm = async () => {
        setIsOpeningForm(true);
        try {
            const response = await fetch(route('forms.generate-share-link', form.uuid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    expires_in_hours: 1, // Short expiration for immediate use
                }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Erreur lors de la génération du lien');
            }

            const data = await response.json();
            // Open the form in a new tab
            window.open(data.url, '_blank');
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Erreur lors de l\'ouverture du formulaire');
        } finally {
            setIsOpeningForm(false);
        }
    };

    const handleGenerateShareLink = async () => {
        setIsGeneratingLink(true);
        try {
            const response = await fetch(route('forms.generate-share-link', form.uuid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    expires_in_hours: 24,
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || errorData.message || 'Erreur lors de la génération du lien');
            }

            const data: ShareLinkData = await response.json();
            setShareData(data);
            setShareModalOpen(true);
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Erreur lors de la génération du lien');
        } finally {
            setIsGeneratingLink(false);
        }
    };

    const handleCopyLink = async () => {
        if (!shareData) return;
        try {
            await navigator.clipboard.writeText(shareData.url);
            toast.success('Lien copié dans le presse-papiers');
        } catch {
            const textArea = document.createElement('textarea');
            textArea.value = shareData.url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            toast.success('Lien copié dans le presse-papiers');
        }
    };

    const handleDownloadQrCode = () => {
        if (!shareData || !shareData.qr_code) return;
        const link = document.createElement('a');
        link.href = shareData.qr_code;
        link.download = `qr-code-${form.name}.svg`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        toast.success('QR Code téléchargé');
    };

    const formatExpirationDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const handleDelete = () => {
        router.delete(route('forms.destroy', form.uuid), {
            onSuccess: () => {
                toast.success('Formulaire supprimé avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la suppression');
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
                        <div className="flex items-center gap-1">
                            {form.status === 'draft' && (
                                <button
                                    type="button"
                                    onClick={handlePublish}
                                    title="Publier"
                                    className="p-2 rounded-md bg-green-600 text-white hover:bg-green-700"
                                >
                                    <DocumentArrowDownIcon className="h-5 w-5" />
                                </button>
                            )}
                            {form.status === 'published' && (
                                <>
                                    <button
                                        type="button"
                                        onClick={handleOpenForm}
                                        disabled={isOpeningForm}
                                        title="Ouvrir"
                                        className="p-2 rounded-md bg-primary text-white hover:bg-primary/90 disabled:opacity-50"
                                    >
                                        <PlayIcon className="h-5 w-5" />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={handleArchive}
                                        title="Archiver"
                                        className="p-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        <ArchiveBoxIcon className="h-5 w-5" />
                                    </button>
                                </>
                            )}
                            <Link
                                href={route('forms.edit', form.uuid)}
                                title="Modifier"
                                className="p-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <PencilIcon className="h-5 w-5" />
                            </Link>
                            <button
                                type="button"
                                onClick={handleDuplicate}
                                title="Dupliquer"
                                className="p-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <DocumentDuplicateIcon className="h-5 w-5" />
                            </button>
                            {form.status === 'published' && (
                                <button
                                    type="button"
                                    onClick={handleGenerateShareLink}
                                    disabled={isGeneratingLink}
                                    title="Partager"
                                    className="p-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                                >
                                    <LinkIcon className="h-5 w-5" />
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={() => setDeleteDialogOpen(true)}
                                title="Supprimer"
                                className="p-2 rounded-md border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                            >
                                <TrashIcon className="h-5 w-5" />
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
                                    Soumissions recentes
                                    {stats && stats.total_submissions > 0 && (
                                        <Badge variant="secondary">{stats.total_submissions}</Badge>
                                    )}
                                </h2>
                                <Link
                                    href={route('forms.submissions', form.uuid)}
                                    className="text-sm text-primary hover:underline"
                                >
                                    Voir tout
                                </Link>
                            </div>
                            <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                {recentSubmissions.length === 0 ? (
                                    <div className="p-4">
                                        <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                                            Aucune soumission recente
                                        </p>
                                    </div>
                                ) : (
                                    recentSubmissions.map((submission) => {
                                        const subStatus = submissionStatusConfig[submission.status] || submissionStatusConfig.draft;
                                        return (
                                            <div key={submission.uuid} className="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-3">
                                                        <UserIcon className="h-5 w-5 text-gray-400" />
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                                {submission.user?.full_name || 'Utilisateur inconnu'}
                                                            </p>
                                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                                {formatDate(submission.submitted_at || submission.created_at)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Badge className={subStatus.color}>
                                                            {subStatus.label}
                                                        </Badge>
                                                        <Link
                                                            href={route('form-submissions.show', submission.uuid)}
                                                            className="p-1.5 rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                            title="Voir"
                                                        >
                                                            <EyeIcon className="h-4 w-4" />
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Share Link Modal */}
            <Dialog open={shareModalOpen} onOpenChange={setShareModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <LinkIcon className="h-5 w-5" />
                            Lien de partage
                        </DialogTitle>
                    </DialogHeader>

                    {shareData && (
                        <div className="px-3 pb-3 space-y-4">
                            {/* QR Code */}
                            {shareData.qr_code && (
                                <div className="flex justify-center">
                                    <div className="bg-white p-3 rounded-lg shadow-sm">
                                        <img
                                            src={shareData.qr_code}
                                            alt="QR Code du formulaire"
                                            className="w-48 h-48"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Link */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Lien de partage
                                </label>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="text"
                                        readOnly
                                        value={shareData.url}
                                        aria-label="Lien de partage du formulaire"
                                        className="flex-1 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-gray-600 dark:text-gray-400"
                                    />
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={handleCopyLink}
                                        title="Copier le lien"
                                    >
                                        <ClipboardDocumentIcon className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            {/* Expiration info */}
                            <div className="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                                <p>
                                    <span className="font-medium">Expire le:</span>{' '}
                                    {formatExpirationDate(shareData.expires_at)}
                                </p>
                                {shareData.max_uses && (
                                    <p>
                                        <span className="font-medium">Utilisations max:</span>{' '}
                                        {shareData.max_uses}
                                    </p>
                                )}
                            </div>

                            {/* Actions */}
                            <div className="flex gap-2">
                                {shareData.qr_code && (
                                    <Button
                                        variant="outline"
                                        className="flex-1"
                                        onClick={handleDownloadQrCode}
                                    >
                                        <ArrowDownTrayIcon className="h-4 w-4 mr-2" />
                                        Télécharger QR Code
                                    </Button>
                                )}
                                <Button
                                    className="flex-1"
                                    onClick={handleCopyLink}
                                >
                                    <ClipboardDocumentIcon className="h-4 w-4 mr-2" />
                                    Copier le lien
                                </Button>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={handleDelete}
                title="Supprimer le formulaire"
                description={`Êtes-vous sûr de vouloir supprimer le formulaire "${form.name}" ? Cette action est irréversible et supprimera également toutes les soumissions associées.`}
            />
        </DashboardLayout>
    );
}
