import React from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import { User, Role, Permission } from '@/Types';
import { ArrowLeftIcon, CheckCircleIcon, XCircleIcon, ExclamationTriangleIcon, CheckIcon } from '@heroicons/react/24/outline';
import axios from 'axios';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';

interface LoginInfo {
    last_login_at: string;
    last_login_ip: string;
    browser: string;
    platform: string;
}

interface BlockedLoginAttempt {
    id: number;
    email: string;
    ip_address: string | null;
    user_agent: string | null;
    acknowledged: boolean;
    acknowledged_at: string | null;
    created_at: string;
    acknowledged_by_user?: {
        full_name: string;
    };
}

interface Props {
    user: User & {
        roles: (Role & { permissions: Permission[] })[];
        permissions: Permission[];
        is_active?: boolean;
        is_blocked?: boolean;
        status_reason?: string;
        status_changed_at?: string;
        status_changed_by?: number;
    };
    loginInfo?: LoginInfo | null;
    blockedAttempts?: BlockedLoginAttempt[];
    blockedAttemptsCount?: number;
}

export default function Show({ user, loginInfo, blockedAttempts = [], blockedAttemptsCount = 0 }: Props) {
    const [attempts, setAttempts] = React.useState(blockedAttempts);
    const [acknowledging, setAcknowledging] = React.useState<number | null>(null);

    const handleAcknowledge = async (attemptId: number) => {
        setAcknowledging(attemptId);
        try {
            await axios.post(route('user-management.acknowledge-blocked-attempt', { attempt: attemptId }));
            toast.success('Tentative marquée comme vue');
            setAttempts(prev => prev.map(a =>
                a.id === attemptId ? { ...a, acknowledged: true, acknowledged_at: new Date().toISOString() } : a
            ));
        } catch (error) {
            toast.error('Erreur lors du marquage de la tentative');
        } finally {
            setAcknowledging(null);
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };
    const allPermissions = new Set<string>();

    // Collect all permissions from roles
    user.roles.forEach(role => {
        role.permissions.forEach(permission => {
            allPermissions.add(permission.name);
        });
    });

    // Add direct permissions
    user.permissions.forEach(permission => {
        allPermissions.add(permission.name);
    });

    const permissionsArray = Array.from(allPermissions).sort();

    return (
        <DashboardLayout>
            <Head title={`${user.first_name} ${user.last_name}`} />

            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6">
                    {/* Header */}
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-4">
                            <Link
                                href={route('user-management.index')}
                                className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
                            >
                                <ArrowLeftIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            </Link>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Détails de l'utilisateur
                            </h1>
                        </div>

                        {/* Status Badge */}
                        {user.is_blocked ? (
                            <span className="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                Bloqué
                            </span>
                        ) : user.is_active === false ? (
                            <span className="px-3 py-1 text-sm font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                Inactif
                            </span>
                        ) : (
                            <span className="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Actif
                            </span>
                        )}
                    </div>

                    {/* User Info */}
                    <div className="grid md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h2 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                                Informations personnelles
                            </h2>
                            <dl className="space-y-3">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Nom complet</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                        {user.first_name} {user.last_name}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-white">{user.email}</dd>
                                </div>
                                {user.birth_date && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Date de naissance</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {new Date(user.birth_date).toLocaleDateString('fr-FR')}
                                        </dd>
                                    </div>
                                )}
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Email vérifié</dt>
                                    <dd className="mt-1 text-sm">
                                        {user.email_verified_at ? (
                                            <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                <CheckCircleIcon className="h-4 w-4" />
                                                Vérifié le {new Date(user.email_verified_at).toLocaleDateString('fr-FR')}
                                            </span>
                                        ) : (
                                            <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                                <XCircleIcon className="h-4 w-4" />
                                                Non vérifié
                                            </span>
                                        )}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h2 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                                Statut et historique
                            </h2>
                            <dl className="space-y-3">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Statut actuel</dt>
                                    <dd className="mt-1">
                                        {user.is_blocked ? (
                                            <span className="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                Bloqué
                                            </span>
                                        ) : user.is_active === false ? (
                                            <span className="px-3 py-1 text-sm font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Inactif
                                            </span>
                                        ) : (
                                            <span className="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Actif
                                            </span>
                                        )}
                                    </dd>
                                </div>
                                {user.status_reason && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Raison du changement</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900 p-3 rounded border border-gray-200 dark:border-gray-700">
                                            {user.status_reason}
                                        </dd>
                                    </div>
                                )}
                                {user.status_changed_at && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Date de modification</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {new Date(user.status_changed_at).toLocaleString('fr-FR')}
                                        </dd>
                                    </div>
                                )}
                                {loginInfo && (
                                    <>
                                        <div className="pt-3 mt-3 border-t dark:border-gray-700">
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Dernière connexion</dt>
                                            <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                                {new Date(loginInfo.last_login_at).toLocaleString('fr-FR', {
                                                    dateStyle: 'long',
                                                    timeStyle: 'medium'
                                                })}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Navigateur</dt>
                                            <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                                {loginInfo.browser}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Système</dt>
                                            <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                                {loginInfo.platform}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Adresse IP</dt>
                                            <dd className="mt-1 text-sm font-mono text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900 px-2 py-1 rounded border border-gray-200 dark:border-gray-700 inline-block">
                                                {loginInfo.last_login_ip}
                                            </dd>
                                        </div>
                                    </>
                                )}
                            </dl>
                        </div>
                    </div>

                    {/* Roles Section */}
                    <div className="mb-8">
                        <h2 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                            Rôles ({user.roles.length})
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            {user.roles.map(role => (
                                <span
                                    key={role.id}
                                    className="px-3 py-1 text-sm font-medium rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200"
                                >
                                    {role.name}
                                </span>
                            ))}
                        </div>
                    </div>

                    {/* Permissions */}
                    <div>
                        <h2 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                            Toutes les permissions ({permissionsArray.length})
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Inclut les permissions des rôles et les permissions directes
                        </p>
                        <div className="grid sm:grid-cols-2 md:grid-cols-3 gap-2">
                            {permissionsArray.map(permissionName => {
                                const isDirect = user.permissions.some(p => p.name === permissionName);
                                return (
                                    <div
                                        key={permissionName}
                                        className={`px-3 py-2 text-sm rounded border ${
                                            isDirect
                                                ? 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300'
                                                : 'bg-gray-50 border-gray-200 text-gray-700 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'
                                        }`}
                                    >
                                        {permissionName}
                                        {isDirect && (
                                            <span className="ml-2 text-xs text-primary dark:text-blue-400">(directe)</span>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Role Details */}
                    {user.roles.length > 0 && (
                        <div className="mt-8">
                            <h2 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                                Détails des rôles
                            </h2>
                            <div className="space-y-4">
                                {user.roles.map(role => (
                                    <div
                                        key={role.id}
                                        className="p-4 border rounded-lg dark:border-gray-700"
                                    >
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-2">
                                            {role.name} ({role.permissions.length} permissions)
                                        </h3>
                                        <div className="flex flex-wrap gap-1">
                                            {role.permissions.map(permission => (
                                                <span
                                                    key={permission.id}
                                                    className="px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                                >
                                                    {permission.name}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Blocked Login Attempts */}
                    {blockedAttemptsCount > 0 && (
                        <div className="mt-8">
                            <h2 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white flex items-center gap-2">
                                <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />
                                Tentatives de connexion bloquées ({blockedAttemptsCount})
                            </h2>
                            <div className="overflow-x-auto border rounded-lg dark:border-gray-700">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Date
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                IP
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Statut
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {attempts.map((attempt) => (
                                            <tr key={attempt.id} className={attempt.acknowledged ? 'opacity-60' : ''}>
                                                <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                    {formatDate(attempt.created_at)}
                                                </td>
                                                <td className="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">
                                                    {attempt.ip_address || '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {attempt.acknowledged ? (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                            <CheckIcon className="w-3 h-3 mr-1" />
                                                            Vue
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                            <ExclamationTriangleIcon className="w-3 h-3 mr-1" />
                                                            Non vue
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {!attempt.acknowledged && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => handleAcknowledge(attempt.id)}
                                                            disabled={acknowledging === attempt.id}
                                                        >
                                                            {acknowledging === attempt.id ? (
                                                                <div className="animate-spin h-4 w-4 border-2 border-green-500 border-t-transparent rounded-full"></div>
                                                            ) : (
                                                                <>
                                                                    <CheckIcon className="w-4 h-4 mr-1" />
                                                                    Marquer vue
                                                                </>
                                                            )}
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {blockedAttemptsCount > 10 && (
                                <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Affichage des 10 dernières tentatives. Total: {blockedAttemptsCount}
                                </p>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
