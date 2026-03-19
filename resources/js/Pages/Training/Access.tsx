import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { useState } from 'react';
import { LockClosedIcon, TrashIcon, UserPlusIcon, ShieldCheckIcon, LinkIcon, ClipboardDocumentIcon, QrCodeIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import axios from 'axios';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    pivot?: {
        granted_by: number | null;
        created_at: string;
    };
}

interface Role {
    id: number;
    name: string;
    pivot?: {
        granted_by: number | null;
        created_at: string;
    };
}

interface Training {
    id: number;
    uuid: string;
    title: string;
    visibility: 'public' | 'private';
    is_active: boolean;
    access_users: User[];
    access_roles: Role[];
}

interface ShareData {
    url: string;
    token: string;
    expires_at: string;
    qr_code: string | null;
}

interface Props {
    training: Training;
    allUsers: User[];
    allRoles: Role[];
    shareData: ShareData | null;
}

export default function Access({ training, allUsers, allRoles, shareData: initialShareData }: Props) {
    const [selectedUserIds, setSelectedUserIds] = useState<number[]>([]);
    const [selectedRoleIds, setSelectedRoleIds] = useState<number[]>([]);
    const [userSearch, setUserSearch] = useState('');
    const [revokeTarget, setRevokeTarget] = useState<{ type: 'user' | 'role'; id: number; name: string } | null>(null);
    const [processing, setProcessing] = useState(false);
    const [shareData, setShareData] = useState<ShareData | null>(initialShareData);
    const [shareProcessing, setShareProcessing] = useState(false);

    const accessUserIds = new Set(training.access_users.map((u) => u.id));
    const accessRoleIds = new Set(training.access_roles.map((r) => r.id));

    const filteredUsers = allUsers.filter((user) => {
        if (accessUserIds.has(user.id)) return false;
        if (!userSearch) return true;
        const search = userSearch.toLowerCase();
        return (
            user.first_name.toLowerCase().includes(search) ||
            user.last_name.toLowerCase().includes(search) ||
            user.email.toLowerCase().includes(search)
        );
    });

    const availableRoles = allRoles.filter((role) => !accessRoleIds.has(role.id));

    const handleGrantUsers = () => {
        if (selectedUserIds.length === 0) return;
        setProcessing(true);
        router.post(route('trainings.access.grant-users', training.uuid), {
            user_ids: selectedUserIds,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Acc\u00e8s utilisateurs accord\u00e9s');
                setSelectedUserIds([]);
                setUserSearch('');
                setProcessing(false);
            },
            onError: () => {
                toast.error('Erreur lors de l\'ajout des acc\u00e8s');
                setProcessing(false);
            },
        });
    };

    const handleGrantRoles = () => {
        if (selectedRoleIds.length === 0) return;
        setProcessing(true);
        router.post(route('trainings.access.grant-roles', training.uuid), {
            role_ids: selectedRoleIds,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Acc\u00e8s r\u00f4les accord\u00e9s');
                setSelectedRoleIds([]);
                setProcessing(false);
            },
            onError: () => {
                toast.error('Erreur lors de l\'ajout des acc\u00e8s');
                setProcessing(false);
            },
        });
    };

    const confirmRevoke = () => {
        if (!revokeTarget) return;
        setProcessing(true);

        const routeName = revokeTarget.type === 'user' ? 'trainings.access.revoke-users' : 'trainings.access.revoke-roles';
        const data = revokeTarget.type === 'user' ? { user_ids: [revokeTarget.id] } : { role_ids: [revokeTarget.id] };

        router.delete(route(routeName, training.uuid), {
            data,
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Acc\u00e8s r\u00e9voqu\u00e9');
                setRevokeTarget(null);
                setProcessing(false);
            },
            onError: () => {
                toast.error('Erreur lors de la r\u00e9vocation');
                setRevokeTarget(null);
                setProcessing(false);
            },
        });
    };

    const toggleUserSelection = (userId: number) => {
        setSelectedUserIds((prev) =>
            prev.includes(userId) ? prev.filter((id) => id !== userId) : [...prev, userId]
        );
    };

    const toggleRoleSelection = (roleId: number) => {
        setSelectedRoleIds((prev) =>
            prev.includes(roleId) ? prev.filter((id) => id !== roleId) : [...prev, roleId]
        );
    };

    const handleGenerateShareLink = async () => {
        setShareProcessing(true);
        try {
            const response = await axios.post(route('trainings.generate-share-link', training.uuid));
            setShareData(response.data);
            toast.success('Lien de partage g\u00e9n\u00e9r\u00e9 (valide 24h)');
        } catch {
            toast.error('Erreur lors de la g\u00e9n\u00e9ration du lien');
        } finally {
            setShareProcessing(false);
        }
    };

    const handleRevokeShareLink = async () => {
        setShareProcessing(true);
        try {
            await axios.post(route('trainings.revoke-share-link', training.uuid));
            setShareData(null);
            toast.success('Lien de partage r\u00e9voqu\u00e9');
        } catch {
            toast.error('Erreur lors de la r\u00e9vocation du lien');
        } finally {
            setShareProcessing(false);
        }
    };

    const copyToClipboard = async (text: string) => {
        try {
            await navigator.clipboard.writeText(text);
            toast.success('Lien copi\u00e9 dans le presse-papiers');
        } catch {
            toast.error('Impossible de copier le lien');
        }
    };

    const formatExpiresAt = (dateStr: string) => {
        const date = new Date(dateStr);
        return date.toLocaleString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Acc\u00e8s - ${training.title}`} />

            <div className="py-4 sm:py-12">
                <div className="mx-auto px-3 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <LockClosedIcon className="h-6 w-6 text-amber-500" />
                                Gestion des acc&egrave;s
                            </h2>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {training.title}
                                <span className="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                    <LockClosedIcon className="h-3 w-3" />
                                    Priv&eacute;
                                </span>
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('trainings.edit', training.uuid)}>
                                    Modifier la formation
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('trainings.index')}>
                                    Retour
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Share Link Section */}
                    <div className="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <LinkIcon className="h-5 w-5" />
                                Lien de partage priv&eacute;
                            </h3>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                G&eacute;n&eacute;rez un lien d'inscription valide 24h ou un QR code pour permettre l'acc&egrave;s &agrave; cette formation.
                            </p>
                        </div>
                        <div className="p-4 sm:p-6">
                            {shareData ? (
                                <div className="space-y-4">
                                    {/* Active Link */}
                                    <div className="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-green-800 dark:text-green-200">
                                                Lien actif
                                            </p>
                                            <p className="text-xs text-green-600 dark:text-green-400 truncate">
                                                {shareData.url}
                                            </p>
                                            <p className="text-xs text-green-600 dark:text-green-400 mt-1">
                                                Expire le {formatExpiresAt(shareData.expires_at)}
                                            </p>
                                        </div>
                                        <button
                                            onClick={() => copyToClipboard(shareData.url)}
                                            className="p-2 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 transition-colors"
                                            title="Copier le lien"
                                        >
                                            <ClipboardDocumentIcon className="h-5 w-5" />
                                        </button>
                                    </div>

                                    {/* QR Code */}
                                    {shareData.qr_code && (
                                        <div className="flex flex-col items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                            <p className="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                                                <QrCodeIcon className="h-4 w-4" />
                                                QR Code d'inscription
                                            </p>
                                            <img
                                                src={shareData.qr_code}
                                                alt="QR Code d'inscription"
                                                className="w-48 h-48 bg-white p-2 rounded-lg"
                                            />
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                Scannez ce QR code pour acc&eacute;der &agrave; la page d'inscription
                                            </p>
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="flex gap-2">
                                        <Button
                                            onClick={handleGenerateShareLink}
                                            disabled={shareProcessing}
                                            size="sm"
                                            variant="outline"
                                        >
                                            {shareProcessing ? 'G\u00e9n\u00e9ration...' : 'Renouveler le lien (24h)'}
                                        </Button>
                                        <Button
                                            onClick={handleRevokeShareLink}
                                            disabled={shareProcessing}
                                            size="sm"
                                            variant="destructive"
                                        >
                                            R&eacute;voquer le lien
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-center py-4">
                                    <QrCodeIcon className="h-10 w-10 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Aucun lien de partage actif. G&eacute;n&eacute;rez un lien pour permettre l'inscription via un lien ou un QR code.
                                    </p>
                                    <Button
                                        onClick={handleGenerateShareLink}
                                        disabled={shareProcessing}
                                        size="sm"
                                    >
                                        {shareProcessing ? 'G\u00e9n\u00e9ration...' : 'G\u00e9n\u00e9rer un lien de partage'}
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Users Section */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <div className="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <UserPlusIcon className="h-5 w-5" />
                                    Utilisateurs ({training.access_users.length})
                                </h3>
                            </div>

                            {/* Add Users */}
                            <div className="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                                <input
                                    type="text"
                                    placeholder="Rechercher un utilisateur..."
                                    value={userSearch}
                                    onChange={(e) => setUserSearch(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm mb-3"
                                />
                                <div className="max-h-48 overflow-y-auto space-y-1 mb-3">
                                    {filteredUsers.slice(0, 20).map((user) => (
                                        <label
                                            key={user.id}
                                            className="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={selectedUserIds.includes(user.id)}
                                                onChange={() => toggleUserSelection(user.id)}
                                                className="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                            />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">
                                                {user.first_name} {user.last_name}
                                            </span>
                                            <span className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {user.email}
                                            </span>
                                        </label>
                                    ))}
                                    {filteredUsers.length === 0 && (
                                        <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-2">
                                            Aucun utilisateur trouv&eacute;
                                        </p>
                                    )}
                                    {filteredUsers.length > 20 && (
                                        <p className="text-xs text-gray-500 dark:text-gray-400 text-center py-1">
                                            {filteredUsers.length - 20} autres r&eacute;sultats...
                                        </p>
                                    )}
                                </div>
                                <Button
                                    onClick={handleGrantUsers}
                                    disabled={selectedUserIds.length === 0 || processing}
                                    size="sm"
                                    className="w-full"
                                >
                                    {processing ? 'Ajout...' : `Accorder l'acc\u00e8s (${selectedUserIds.length})`}
                                </Button>
                            </div>

                            {/* Current Users */}
                            <div className="p-4 sm:p-6">
                                {training.access_users.length === 0 ? (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                        Aucun utilisateur n'a encore acc&egrave;s.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {training.access_users.map((user) => (
                                            <div key={user.id} className="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                                <div className="min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {user.first_name} {user.last_name}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                        {user.email}
                                                    </p>
                                                </div>
                                                <button
                                                    onClick={() => setRevokeTarget({ type: 'user', id: user.id, name: `${user.first_name} ${user.last_name}` })}
                                                    className="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 ml-2 flex-shrink-0"
                                                    title="R&eacute;voquer l'acc&egrave;s"
                                                >
                                                    <TrashIcon className="h-4 w-4" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Roles Section */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <div className="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <ShieldCheckIcon className="h-5 w-5" />
                                    R&ocirc;les ({training.access_roles.length})
                                </h3>
                            </div>

                            {/* Add Roles */}
                            <div className="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                                <div className="space-y-1 mb-3">
                                    {availableRoles.map((role) => (
                                        <label
                                            key={role.id}
                                            className="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={selectedRoleIds.includes(role.id)}
                                                onChange={() => toggleRoleSelection(role.id)}
                                                className="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                            />
                                            <span className="text-sm text-gray-700 dark:text-gray-300 capitalize">
                                                {role.name}
                                            </span>
                                        </label>
                                    ))}
                                    {availableRoles.length === 0 && (
                                        <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-2">
                                            Tous les r&ocirc;les ont d&eacute;j&agrave; acc&egrave;s.
                                        </p>
                                    )}
                                </div>
                                <Button
                                    onClick={handleGrantRoles}
                                    disabled={selectedRoleIds.length === 0 || processing}
                                    size="sm"
                                    className="w-full"
                                >
                                    {processing ? 'Ajout...' : `Accorder l'acc\u00e8s (${selectedRoleIds.length})`}
                                </Button>
                            </div>

                            {/* Current Roles */}
                            <div className="p-4 sm:p-6">
                                {training.access_roles.length === 0 ? (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                        Aucun r&ocirc;le n'a encore acc&egrave;s.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {training.access_roles.map((role) => (
                                            <div key={role.id} className="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                                <p className="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                                    {role.name}
                                                </p>
                                                <button
                                                    onClick={() => setRevokeTarget({ type: 'role', id: role.id, name: role.name })}
                                                    className="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 ml-2 flex-shrink-0"
                                                    title="R&eacute;voquer l'acc&egrave;s"
                                                >
                                                    <TrashIcon className="h-4 w-4" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Revoke Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={revokeTarget !== null}
                onOpenChange={(open) => !open && setRevokeTarget(null)}
                onConfirm={confirmRevoke}
                title="R\u00e9voquer l'acc\u00e8s"
                description={`\u00cates-vous s\u00fbr de vouloir r\u00e9voquer l'acc\u00e8s de "${revokeTarget?.name}" \u00e0 cette formation ?`}
            />
        </DashboardLayout>
    );
}
