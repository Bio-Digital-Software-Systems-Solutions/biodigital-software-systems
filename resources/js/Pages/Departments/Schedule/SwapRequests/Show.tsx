import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowLeftIcon,
    ArrowsRightLeftIcon,
    CalendarDaysIcon,
    UserIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface User {
    id: number;
    first_name: string;
    last_name: string;
    full_name?: string;
    name?: string;
    email?: string;
}

interface WeeklySchedule {
    id: number;
    uuid: string;
    week_start: string;
    week_end: string;
}

interface Shift {
    id: number;
    uuid: string;
    date: string;
    start_time: string;
    end_time: string;
    type: string;
    title: string | null;
    weekly_schedule?: WeeklySchedule;
}

interface Conflict {
    type: string;
    severity: 'blocking' | 'warning';
    message: string;
}

interface ConflictResult {
    has_blocking_conflicts: boolean;
    has_warnings: boolean;
    conflicts: Conflict[];
    warnings: Conflict[];
}

interface SwapRequest {
    id: number;
    uuid: string;
    requester: User;
    target_user: User | null;
    requested_shift: Shift;
    offered_shift: Shift | null;
    approved_by_user: User | null;
    status: string;
    reason: string | null;
    rejection_reason: string | null;
    approved_at: string | null;
    expires_at: string | null;
    created_at: string;
}

interface Props {
    department: Department;
    swapRequest: SwapRequest;
    requesterConflicts: ConflictResult | null;
    targetConflicts: ConflictResult | null;
}

export default function SwapRequestShow({ department, swapRequest, requesterConflicts, targetConflicts }: Props) {
    const getUserName = (user: User | null) => {
        if (!user) return 'N/A';
        if (user.full_name) return user.full_name;
        if (user.first_name && user.last_name) return `${user.first_name} ${user.last_name}`;
        return user.name || 'Utilisateur';
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const formatDateTime = (dateString: string) => {
        return new Date(dateString).toLocaleString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatTime = (time: string) => {
        return time.slice(0, 5);
    };

    const getStatusBadge = (status: string) => {
        const config: Record<string, { color: string; label: string; icon: React.ReactNode }> = {
            pending_colleague: {
                color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                label: 'En attente (collègue)',
                icon: <ClockIcon className="h-4 w-4" />,
            },
            pending_manager: {
                color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                label: 'En attente (manager)',
                icon: <ClockIcon className="h-4 w-4" />,
            },
            approved: {
                color: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                label: 'Approuvé',
                icon: <CheckCircleIcon className="h-4 w-4" />,
            },
            rejected_colleague: {
                color: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                label: 'Refusé (collègue)',
                icon: <XCircleIcon className="h-4 w-4" />,
            },
            rejected_manager: {
                color: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                label: 'Refusé (manager)',
                icon: <XCircleIcon className="h-4 w-4" />,
            },
            cancelled: {
                color: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                label: 'Annulé',
                icon: <XCircleIcon className="h-4 w-4" />,
            },
            expired: {
                color: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                label: 'Expiré',
                icon: <ClockIcon className="h-4 w-4" />,
            },
        };
        const { color, label, icon } = config[status] || { color: 'bg-gray-100 text-gray-800', label: status, icon: null };
        return (
            <Badge className={`${color} flex items-center gap-1 text-base px-3 py-1`}>
                {icon}
                {label}
            </Badge>
        );
    };

    const handleApproveManager = () => {
        router.post(`/departments/${department.uuid}/swap-requests/${swapRequest.uuid}/approve-manager`, {}, {
            onSuccess: () => toast.success('Échange approuvé'),
            onError: () => toast.error('Erreur lors de l\'approbation'),
        });
    };

    const handleRejectManager = () => {
        const reason = prompt('Motif du refus:');
        if (!reason) return;
        router.post(`/departments/${department.uuid}/swap-requests/${swapRequest.uuid}/reject-manager`, {
            rejection_reason: reason,
        }, {
            onSuccess: () => toast.success('Échange refusé'),
            onError: () => toast.error('Erreur lors du refus'),
        });
    };

    const handleAcceptColleague = () => {
        router.post(`/departments/${department.uuid}/swap-requests/${swapRequest.uuid}/accept-colleague`, {}, {
            onSuccess: () => toast.success('Échange accepté'),
            onError: () => toast.error('Erreur lors de l\'acceptation'),
        });
    };

    const handleRejectColleague = () => {
        router.post(`/departments/${department.uuid}/swap-requests/${swapRequest.uuid}/reject-colleague`, {}, {
            onSuccess: () => toast.success('Échange refusé'),
            onError: () => toast.error('Erreur lors du refus'),
        });
    };

    const handleCancel = () => {
        if (!confirm('Voulez-vous vraiment annuler cette demande?')) return;
        router.post(`/departments/${department.uuid}/swap-requests/${swapRequest.uuid}/cancel`, {}, {
            onSuccess: () => toast.success('Demande annulée'),
            onError: () => toast.error('Erreur lors de l\'annulation'),
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Échange de shift - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8 max-w-3xl">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href={`/departments/${department.uuid}/swap-requests`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour aux échanges
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Demande d'échange
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {department.name}
                            </p>
                        </div>
                        {getStatusBadge(swapRequest.status)}
                    </div>
                </div>

                {/* Requester */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-lg flex items-center gap-2">
                            <UserIcon className="h-5 w-5" />
                            Demandeur
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-4">
                            <div className="h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <UserIcon className="h-6 w-6 text-gray-500" />
                            </div>
                            <div>
                                <p className="font-medium text-gray-900 dark:text-white">
                                    {getUserName(swapRequest.requester)}
                                </p>
                                {swapRequest.requester?.email && (
                                    <p className="text-sm text-gray-500">{swapRequest.requester.email}</p>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Shifts */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-lg flex items-center gap-2">
                            <ArrowsRightLeftIcon className="h-5 w-5" />
                            Détails de l'échange
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-around">
                            {/* Requested Shift */}
                            <div className="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg flex-1">
                                <p className="text-xs text-red-600 dark:text-red-400 font-medium mb-2">
                                    Shift demandé
                                </p>
                                <CalendarDaysIcon className="h-8 w-8 mx-auto text-red-600 dark:text-red-400 mb-2" />
                                <p className="font-medium">{formatDate(swapRequest.requested_shift.date)}</p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {formatTime(swapRequest.requested_shift.start_time)} - {formatTime(swapRequest.requested_shift.end_time)}
                                </p>
                                {swapRequest.requested_shift.title && (
                                    <Badge variant="outline" className="mt-2">
                                        {swapRequest.requested_shift.title}
                                    </Badge>
                                )}
                            </div>

                            <ArrowsRightLeftIcon className="h-8 w-8 mx-4 text-gray-400" />

                            {/* Offered Shift */}
                            <div className="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg flex-1">
                                <p className="text-xs text-green-600 dark:text-green-400 font-medium mb-2">
                                    {swapRequest.offered_shift ? 'Shift offert' : 'Échange simple'}
                                </p>
                                <CalendarDaysIcon className="h-8 w-8 mx-auto text-green-600 dark:text-green-400 mb-2" />
                                {swapRequest.offered_shift ? (
                                    <>
                                        <p className="font-medium">{formatDate(swapRequest.offered_shift.date)}</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {formatTime(swapRequest.offered_shift.start_time)} - {formatTime(swapRequest.offered_shift.end_time)}
                                        </p>
                                        {swapRequest.offered_shift.title && (
                                            <Badge variant="outline" className="mt-2">
                                                {swapRequest.offered_shift.title}
                                            </Badge>
                                        )}
                                    </>
                                ) : (
                                    <p className="text-gray-500">
                                        Prise du shift sans échange
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Target User */}
                        {swapRequest.target_user && (
                            <div className="mt-4 pt-4 border-t dark:border-gray-700">
                                <p className="text-sm text-gray-500 mb-2">Échange avec:</p>
                                <div className="flex items-center gap-2">
                                    <UserIcon className="h-5 w-5 text-gray-400" />
                                    <span className="font-medium text-gray-900 dark:text-white">
                                        {getUserName(swapRequest.target_user)}
                                    </span>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Conflicts Warning */}
                {(requesterConflicts?.has_blocking_conflicts || requesterConflicts?.has_warnings ||
                  targetConflicts?.has_blocking_conflicts || targetConflicts?.has_warnings) && (
                    <Card className="mb-6 border-orange-300 dark:border-orange-700">
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2 text-orange-600 dark:text-orange-400">
                                <ExclamationTriangleIcon className="h-5 w-5" />
                                Conflits détectés
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {requesterConflicts && (requesterConflicts.conflicts.length > 0 || requesterConflicts.warnings.length > 0) && (
                                <div>
                                    <p className="font-medium text-sm mb-2">Pour le demandeur:</p>
                                    <ul className="space-y-1">
                                        {requesterConflicts.conflicts.map((c, i) => (
                                            <li key={i} className="text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                                <XCircleIcon className="h-4 w-4" />
                                                {c.message}
                                            </li>
                                        ))}
                                        {requesterConflicts.warnings.map((w, i) => (
                                            <li key={i} className="text-sm text-orange-600 dark:text-orange-400 flex items-center gap-1">
                                                <ExclamationTriangleIcon className="h-4 w-4" />
                                                {w.message}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            {targetConflicts && (targetConflicts.conflicts.length > 0 || targetConflicts.warnings.length > 0) && (
                                <div>
                                    <p className="font-medium text-sm mb-2">Pour le collègue:</p>
                                    <ul className="space-y-1">
                                        {targetConflicts.conflicts.map((c, i) => (
                                            <li key={i} className="text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                                <XCircleIcon className="h-4 w-4" />
                                                {c.message}
                                            </li>
                                        ))}
                                        {targetConflicts.warnings.map((w, i) => (
                                            <li key={i} className="text-sm text-orange-600 dark:text-orange-400 flex items-center gap-1">
                                                <ExclamationTriangleIcon className="h-4 w-4" />
                                                {w.message}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Reason */}
                {swapRequest.reason && (
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="text-lg">Motif de la demande</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-gray-600 dark:text-gray-300">{swapRequest.reason}</p>
                        </CardContent>
                    </Card>
                )}

                {/* Rejection Reason */}
                {swapRequest.rejection_reason && (
                    <Card className="mb-6 border-red-300 dark:border-red-700">
                        <CardHeader>
                            <CardTitle className="text-lg text-red-600 dark:text-red-400">Motif du refus</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-red-800 dark:text-red-200">{swapRequest.rejection_reason}</p>
                            {swapRequest.approved_at && (
                                <p className="text-xs text-gray-500 mt-2">
                                    Refusé le {formatDateTime(swapRequest.approved_at)}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Meta */}
                <Card className="mb-6">
                    <CardContent className="pt-4 space-y-2">
                        <p className="text-sm text-gray-500">
                            Demande créée le {formatDateTime(swapRequest.created_at)}
                        </p>
                        {swapRequest.expires_at && (
                            <p className="text-sm text-gray-500">
                                Expire le {formatDateTime(swapRequest.expires_at)}
                            </p>
                        )}
                        {swapRequest.approved_by_user && (
                            <p className="text-sm text-gray-500">
                                Approuvé par {getUserName(swapRequest.approved_by_user)}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="flex justify-end gap-4">
                    {swapRequest.status === 'pending_colleague' && (
                        <>
                            <Button variant="outline" onClick={handleCancel}>
                                Annuler la demande
                            </Button>
                            <Button variant="destructive" onClick={handleRejectColleague}>
                                <XCircleIcon className="h-4 w-4 mr-2" />
                                Refuser (collègue)
                            </Button>
                            <Button className="bg-green-600 hover:bg-green-700" onClick={handleAcceptColleague}>
                                <CheckCircleIcon className="h-4 w-4 mr-2" />
                                Accepter (collègue)
                            </Button>
                        </>
                    )}
                    {swapRequest.status === 'pending_manager' && (
                        <>
                            <Button variant="destructive" onClick={handleRejectManager}>
                                <XCircleIcon className="h-4 w-4 mr-2" />
                                Refuser
                            </Button>
                            <Button className="bg-green-600 hover:bg-green-700" onClick={handleApproveManager}>
                                <CheckCircleIcon className="h-4 w-4 mr-2" />
                                Approuver
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
