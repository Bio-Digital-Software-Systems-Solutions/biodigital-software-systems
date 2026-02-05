import React, { useState, useEffect, useCallback } from 'react';
import { Link } from '@inertiajs/react';
import { toast } from 'sonner';
import axios from 'axios';
import { CheckIcon, ExclamationTriangleIcon, EyeIcon } from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';

interface User {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
    email: string;
    full_name: string;
}

interface BlockedLoginAttempt {
    id: number;
    user_id: number;
    email: string;
    ip_address: string | null;
    user_agent: string | null;
    acknowledged: boolean;
    acknowledged_by: number | null;
    acknowledged_at: string | null;
    created_at: string;
    user: User;
    acknowledged_by_user?: User;
}

interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: BlockedLoginAttempt[];
}

interface Props {
    initialUnacknowledgedCount?: number;
}

export default function BlockedLoginAttemptsTab({ initialUnacknowledgedCount = 0 }: Props) {
    const [attempts, setAttempts] = useState<BlockedLoginAttempt[]>([]);
    const [pagination, setPagination] = useState<Omit<PaginationData, 'data'> | null>(null);
    const [loading, setLoading] = useState(true);
    const [acknowledging, setAcknowledging] = useState<number | null>(null);
    const [showAcknowledged, setShowAcknowledged] = useState(false);
    const [selectedAttempts, setSelectedAttempts] = useState<number[]>([]);
    const [unacknowledgedCount, setUnacknowledgedCount] = useState(initialUnacknowledgedCount);

    const fetchAttempts = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const response = await axios.get(route('user-management.blocked-login-attempts'), {
                params: {
                    page,
                    per_page: 20,
                    acknowledged: showAcknowledged ? undefined : 'false',
                },
            });
            setAttempts(response.data.attempts.data);
            setPagination({
                current_page: response.data.attempts.current_page,
                last_page: response.data.attempts.last_page,
                per_page: response.data.attempts.per_page,
                total: response.data.attempts.total,
            });
            setUnacknowledgedCount(response.data.unacknowledged_count);
        } catch (error) {
            toast.error('Erreur lors du chargement des tentatives de connexion bloquées');
        } finally {
            setLoading(false);
        }
    }, [showAcknowledged]);

    useEffect(() => {
        fetchAttempts();
    }, [fetchAttempts]);

    const handleAcknowledge = async (attemptId: number) => {
        setAcknowledging(attemptId);
        try {
            await axios.post(route('user-management.acknowledge-blocked-attempt', { attempt: attemptId }));
            toast.success('Tentative marquée comme vue');
            fetchAttempts(pagination?.current_page || 1);
        } catch (error) {
            toast.error('Erreur lors du marquage de la tentative');
        } finally {
            setAcknowledging(null);
        }
    };

    const handleAcknowledgeSelected = async () => {
        if (selectedAttempts.length === 0) return;

        try {
            await axios.post(route('user-management.acknowledge-multiple-blocked-attempts'), {
                attempt_ids: selectedAttempts,
            });
            toast.success(`${selectedAttempts.length} tentative(s) marquée(s) comme vue(s)`);
            setSelectedAttempts([]);
            fetchAttempts(pagination?.current_page || 1);
        } catch (error) {
            toast.error('Erreur lors du marquage des tentatives');
        }
    };

    const toggleSelectAll = () => {
        const unacknowledgedAttempts = attempts.filter(a => !a.acknowledged);
        if (selectedAttempts.length === unacknowledgedAttempts.length) {
            setSelectedAttempts([]);
        } else {
            setSelectedAttempts(unacknowledgedAttempts.map(a => a.id));
        }
    };

    const toggleSelect = (attemptId: number) => {
        setSelectedAttempts(prev =>
            prev.includes(attemptId)
                ? prev.filter(id => id !== attemptId)
                : [...prev, attemptId]
        );
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

    const parseUserAgent = (ua: string | null) => {
        if (!ua) return { browser: 'Inconnu', os: 'Inconnu' };

        let browser = 'Inconnu';
        let os = 'Inconnu';

        // Browser detection
        if (ua.includes('Chrome')) browser = 'Chrome';
        else if (ua.includes('Firefox')) browser = 'Firefox';
        else if (ua.includes('Safari')) browser = 'Safari';
        else if (ua.includes('Edge')) browser = 'Edge';

        // OS detection
        if (ua.includes('Windows')) os = 'Windows';
        else if (ua.includes('Mac')) os = 'macOS';
        else if (ua.includes('Linux')) os = 'Linux';
        else if (ua.includes('iPhone') || ua.includes('iPad')) os = 'iOS';
        else if (ua.includes('Android')) os = 'Android';

        return { browser, os };
    };

    const unacknowledgedAttempts = attempts.filter(a => !a.acknowledged);

    return (
        <div>
            {/* Header with stats */}
            <div className="mb-6 flex items-center justify-between flex-wrap gap-4">
                <div className="flex items-center gap-4">
                    <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                        Tentatives de connexion bloquées
                    </h3>
                    {unacknowledgedCount > 0 && (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <ExclamationTriangleIcon className="w-4 h-4 mr-1" />
                            {unacknowledgedCount} non vue(s)
                        </span>
                    )}
                </div>

                <div className="flex items-center gap-3">
                    <label className="inline-flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <input
                            type="checkbox"
                            checked={showAcknowledged}
                            onChange={(e) => setShowAcknowledged(e.target.checked)}
                            className="mr-2 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                        />
                        Afficher les vues
                    </label>

                    {selectedAttempts.length > 0 && (
                        <Button
                            onClick={handleAcknowledgeSelected}
                            size="sm"
                            className="bg-green-600 hover:bg-green-700"
                        >
                            <CheckIcon className="w-4 h-4 mr-1" />
                            Marquer {selectedAttempts.length} comme vue(s)
                        </Button>
                    )}
                </div>
            </div>

            {/* Loading state */}
            {loading ? (
                <div className="flex justify-center py-8">
                    <div className="animate-spin h-8 w-8 border-2 border-violet-500 border-t-transparent rounded-full"></div>
                </div>
            ) : attempts.length === 0 ? (
                <div className="text-center py-12">
                    <ExclamationTriangleIcon className="mx-auto h-12 w-12 text-gray-400" />
                    <h3 className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                        Aucune tentative
                    </h3>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {showAcknowledged
                            ? "Aucune tentative de connexion bloquée n'a été enregistrée."
                            : "Aucune tentative de connexion bloquée non vue."}
                    </p>
                </div>
            ) : (
                <>
                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    {unacknowledgedAttempts.length > 0 && (
                                        <th className="px-4 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                checked={selectedAttempts.length === unacknowledgedAttempts.length && unacknowledgedAttempts.length > 0}
                                                onChange={toggleSelectAll}
                                                className="rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                            />
                                        </th>
                                    )}
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Utilisateur
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Date/Heure
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        IP
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Appareil
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                {attempts.map((attempt) => {
                                    const { browser, os } = parseUserAgent(attempt.user_agent);
                                    return (
                                        <tr key={attempt.id} className={attempt.acknowledged ? 'opacity-60' : ''}>
                                            {unacknowledgedAttempts.length > 0 && (
                                                <td className="px-4 py-4">
                                                    {!attempt.acknowledged && (
                                                        <input
                                                            type="checkbox"
                                                            checked={selectedAttempts.includes(attempt.id)}
                                                            onChange={() => toggleSelect(attempt.id)}
                                                            className="rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                                        />
                                                    )}
                                                </td>
                                            )}
                                            <td className="px-4 py-4">
                                                <div className="flex items-center">
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {attempt.user.full_name}
                                                        </div>
                                                        <div className="text-sm text-gray-500 dark:text-gray-400">
                                                            {attempt.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {formatDate(attempt.created_at)}
                                            </td>
                                            <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                                {attempt.ip_address || '-'}
                                            </td>
                                            <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <div>{browser}</div>
                                                <div className="text-xs">{os}</div>
                                            </td>
                                            <td className="px-4 py-4 whitespace-nowrap">
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
                                            <td className="px-4 py-4 whitespace-nowrap text-sm">
                                                <div className="flex items-center gap-2">
                                                    <Link
                                                        href={route('user-management.show', { user: attempt.user.id })}
                                                        className="text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300"
                                                    >
                                                        <EyeIcon className="w-5 h-5" />
                                                    </Link>
                                                    {!attempt.acknowledged && (
                                                        <button
                                                            onClick={() => handleAcknowledge(attempt.id)}
                                                            disabled={acknowledging === attempt.id}
                                                            className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 disabled:opacity-50"
                                                            title="Marquer comme vue"
                                                        >
                                                            {acknowledging === attempt.id ? (
                                                                <div className="animate-spin h-5 w-5 border-2 border-green-500 border-t-transparent rounded-full"></div>
                                                            ) : (
                                                                <CheckIcon className="w-5 h-5" />
                                                            )}
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {pagination && pagination.last_page > 1 && (
                        <div className="mt-4 flex justify-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={pagination.current_page === 1}
                                onClick={() => fetchAttempts(pagination.current_page - 1)}
                            >
                                Précédent
                            </Button>
                            <span className="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                Page {pagination.current_page} sur {pagination.last_page}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={pagination.current_page === pagination.last_page}
                                onClick={() => fetchAttempts(pagination.current_page + 1)}
                            >
                                Suivant
                            </Button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
