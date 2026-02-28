import React, { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import {
    DocumentTextIcon,
    ArrowUpTrayIcon,
    TrashIcon,
    LinkIcon,
    QrCodeIcon,
    ArrowPathIcon,
    ClipboardIcon,
    ArrowDownTrayIcon,
    XCircleIcon,
    ClockIcon,
} from '@heroicons/react/24/outline';
import { EventProgramme } from '@/Types/event.d';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface EventProgrammeTabProps {
    eventId: string;
    programme: EventProgramme | null;
    canEdit: boolean;
}

interface ShareLinkData {
    url: string;
    token: string;
    expires_at: string;
    qr_code?: string | null;
}

export const EventProgrammeTab: React.FC<EventProgrammeTabProps> = ({ eventId, programme, canEdit }) => {
    const [uploading, setUploading] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [shareLink, setShareLink] = useState<ShareLinkData | null>(
        programme?.share_token && programme?.share_token_expires_at
            ? {
                url: programme.share_url || '',
                token: programme.share_token,
                expires_at: programme.share_token_expires_at,
                qr_code: null,
            }
            : null
    );
    const [generatingLink, setGeneratingLink] = useState(false);
    const [renewingLink, setRenewingLink] = useState(false);

    const handleUpload = useCallback(async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            toast.error('Type de fichier non supporté. Utilisez PDF, JPEG, PNG, GIF ou WebP.');
            return;
        }

        if (file.size > 50 * 1024 * 1024) {
            toast.error('Le fichier ne doit pas dépasser 50 Mo.');
            return;
        }

        setUploading(true);
        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch(route('events.programme.store', eventId), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Erreur lors de l\'upload.');
            }

            toast.success('Programme uploadé avec succès.');
            router.reload({ only: ['programme', 'tabPermissions'] });
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Erreur lors de l\'upload du programme.');
        } finally {
            setUploading(false);
            e.target.value = '';
        }
    }, [eventId]);

    const handleDelete = useCallback(async () => {
        try {
            const response = await fetch(route('events.programme.destroy', eventId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la suppression.');
            }

            toast.success('Programme supprimé.');
            setShareLink(null);
            router.reload({ only: ['programme', 'tabPermissions'] });
        } catch (error) {
            toast.error('Erreur lors de la suppression du programme.');
        }
    }, [eventId]);

    const handleGenerateLink = useCallback(async () => {
        setGeneratingLink(true);
        try {
            const response = await fetch(route('events.programme.generate-share-link', eventId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la génération du lien.');
            }

            const data: ShareLinkData = await response.json();
            setShareLink(data);
            toast.success('Lien de partage généré.');
        } catch (error) {
            toast.error('Erreur lors de la génération du lien de partage.');
        } finally {
            setGeneratingLink(false);
        }
    }, [eventId]);

    const handleRenewLink = useCallback(async () => {
        setRenewingLink(true);
        try {
            const response = await fetch(route('events.programme.renew-share-link', eventId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Erreur lors du renouvellement.');
            }

            const data = await response.json();
            setShareLink(prev => prev ? { ...prev, ...data } : data);
            toast.success('Lien renouvelé pour 24h.');
        } catch (error) {
            toast.error('Erreur lors du renouvellement du lien.');
        } finally {
            setRenewingLink(false);
        }
    }, [eventId]);

    const handleRevokeLink = useCallback(async () => {
        try {
            const response = await fetch(route('events.programme.revoke-share-link', eventId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la révocation.');
            }

            setShareLink(null);
            toast.success('Lien de partage révoqué.');
        } catch (error) {
            toast.error('Erreur lors de la révocation du lien.');
        }
    }, [eventId]);

    const copyToClipboard = useCallback((text: string) => {
        navigator.clipboard.writeText(text).then(() => {
            toast.success('Lien copié dans le presse-papiers.');
        });
    }, []);

    const getExpirationLabel = (expiresAt: string): string => {
        const expires = new Date(expiresAt);
        const now = new Date();
        const diffMs = expires.getTime() - now.getTime();

        if (diffMs <= 0) return 'Expiré';

        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

        if (hours > 0) return `Expire dans ${hours}h ${minutes}min`;
        return `Expire dans ${minutes}min`;
    };

    const isTokenExpired = shareLink?.expires_at
        ? new Date(shareLink.expires_at).getTime() <= Date.now()
        : false;

    // No programme uploaded yet
    if (!programme) {
        return (
            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                <div className="text-center py-12">
                    <DocumentTextIcon className="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Aucun programme
                    </h3>
                    <p className="text-gray-500 dark:text-gray-400 mb-6">
                        {canEdit
                            ? 'Uploadez le programme de l\'événement (PDF, image).'
                            : 'Aucun programme n\'a été ajouté à cet événement.'}
                    </p>
                    {canEdit && (
                        <label className="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors cursor-pointer">
                            <ArrowUpTrayIcon className="h-5 w-5" />
                            {uploading ? 'Upload en cours...' : 'Uploader un programme'}
                            <input
                                type="file"
                                className="hidden"
                                accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                                onChange={handleUpload}
                                disabled={uploading}
                            />
                        </label>
                    )}
                </div>
            </div>
        );
    }

    // Programme exists
    return (
        <div className="space-y-6">
            {/* Programme Preview */}
            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <DocumentTextIcon className="h-5 w-5" />
                        Programme
                    </h3>
                    <div className="flex items-center gap-2">
                        {canEdit && (
                            <>
                                <label className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors cursor-pointer">
                                    <ArrowUpTrayIcon className="h-4 w-4" />
                                    Remplacer
                                    <input
                                        type="file"
                                        className="hidden"
                                        accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                                        onChange={handleUpload}
                                        disabled={uploading}
                                    />
                                </label>
                                <button
                                    onClick={() => setDeleteDialogOpen(true)}
                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                                >
                                    <TrashIcon className="h-4 w-4" />
                                    Supprimer
                                </button>
                            </>
                        )}
                    </div>
                </div>

                {/* File Info */}
                <div className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg mb-4">
                    <div className="p-2 bg-white dark:bg-gray-700 rounded-lg">
                        <DocumentTextIcon className="h-6 w-6 text-indigo-500" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {programme.file_name}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {programme.file_size_for_humans} &middot; {programme.is_pdf ? 'PDF' : 'Image'}
                        </p>
                    </div>
                    {programme.file_url && (
                        <a
                            href={programme.file_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                        >
                            <ArrowDownTrayIcon className="h-4 w-4" />
                            Télécharger
                        </a>
                    )}
                </div>

                {/* Preview */}
                {programme.can_preview && programme.file_url && (
                    <div className="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                        {programme.is_pdf ? (
                            <object
                                data={programme.file_url}
                                type="application/pdf"
                                className="w-full h-[600px]"
                            >
                                <div className="p-8 text-center text-gray-500 dark:text-gray-400">
                                    <p>Impossible d'afficher le PDF dans le navigateur.</p>
                                    <a
                                        href={programme.file_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block"
                                    >
                                        Ouvrir dans un nouvel onglet
                                    </a>
                                </div>
                            </object>
                        ) : (
                            <img
                                src={programme.file_url}
                                alt="Programme"
                                className="w-full h-auto max-h-[600px] object-contain bg-gray-100 dark:bg-gray-900"
                            />
                        )}
                    </div>
                )}
            </div>

            {/* Share Link Section */}
            {canEdit && (
                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <LinkIcon className="h-5 w-5" />
                        Partager le programme
                    </h3>

                    {!shareLink || isTokenExpired ? (
                        <div className="text-center py-6">
                            <QrCodeIcon className="h-12 w-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                            <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                {isTokenExpired
                                    ? 'Le lien de partage a expiré. Générez-en un nouveau.'
                                    : 'Générez un lien de partage public valable 24h avec un code QR.'}
                            </p>
                            <button
                                onClick={handleGenerateLink}
                                disabled={generatingLink}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
                            >
                                <LinkIcon className="h-4 w-4" />
                                {generatingLink ? 'Génération...' : 'Générer un lien de partage'}
                            </button>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {/* Expiration Info */}
                            <div className="flex items-center gap-2 text-sm">
                                <ClockIcon className="h-4 w-4 text-green-500" />
                                <span className="text-green-600 dark:text-green-400 font-medium">
                                    {getExpirationLabel(shareLink.expires_at)}
                                </span>
                            </div>

                            {/* Share URL */}
                            <div className="flex items-center gap-2">
                                <input
                                    type="text"
                                    readOnly
                                    value={shareLink.url}
                                    className="flex-1 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-700 dark:text-gray-300"
                                />
                                <button
                                    onClick={() => copyToClipboard(shareLink.url)}
                                    className="inline-flex items-center gap-1.5 px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                    title="Copier le lien"
                                >
                                    <ClipboardIcon className="h-4 w-4" />
                                </button>
                            </div>

                            {/* QR Code */}
                            {shareLink.qr_code && (
                                <div className="flex justify-center p-4 bg-white rounded-lg border border-gray-200 dark:border-gray-700">
                                    <img
                                        src={shareLink.qr_code}
                                        alt="QR Code du programme"
                                        className="w-48 h-48"
                                    />
                                </div>
                            )}

                            {/* Actions */}
                            <div className="flex items-center gap-2 pt-2">
                                <button
                                    onClick={handleRenewLink}
                                    disabled={renewingLink}
                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors disabled:opacity-50"
                                >
                                    <ArrowPathIcon className="h-4 w-4" />
                                    {renewingLink ? 'Renouvellement...' : 'Renouveler 24h'}
                                </button>
                                <button
                                    onClick={handleGenerateLink}
                                    disabled={generatingLink}
                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors disabled:opacity-50"
                                >
                                    <QrCodeIcon className="h-4 w-4" />
                                    {generatingLink ? 'Génération...' : 'Nouveau lien + QR'}
                                </button>
                                <button
                                    onClick={handleRevokeLink}
                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                                >
                                    <XCircleIcon className="h-4 w-4" />
                                    Révoquer
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Delete Confirmation */}
            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={handleDelete}
                title="Supprimer le programme"
                description="Êtes-vous sûr de vouloir supprimer le programme de cet événement ? Cette action est irréversible."
            />
        </div>
    );
};
