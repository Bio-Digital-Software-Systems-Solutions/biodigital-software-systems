import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowLeftIcon,
    ArrowsRightLeftIcon,
    PlusIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    UserIcon,
    InboxArrowDownIcon,
    PaperAirplaneIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

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
}

interface Shift {
    id: number;
    uuid: string;
    date: string;
    start_time: string;
    end_time: string;
    type: string;
    title?: string | null;
}

interface SwapRequest {
    id: number;
    uuid: string;
    requester: User;
    target_user: User | null;
    requested_shift: Shift;
    offered_shift: Shift | null;
    status: string;
    reason: string | null;
    rejection_reason: string | null;
    created_at: string;
    expires_at: string | null;
}

interface SwapStatus {
    value: string;
    label: string;
    color: string;
}

interface Props {
    department: Department;
    outgoing: SwapRequest[];
    incoming: SwapRequest[];
    myShifts: Shift[];
    swapStatuses: SwapStatus[];
}

export default function MySwapRequests({ department, outgoing, incoming, myShifts, swapStatuses }: Props) {
    const [cancelDialogOpen, setCancelDialogOpen] = useState(false);
    const [selectedSwap, setSelectedSwap] = useState<SwapRequest | null>(null);
    const [processingId, setProcessingId] = useState<number | null>(null);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
        });
    };

    const formatTime = (time: string) => {
        return time.slice(0, 5);
    };

    const getUserName = (user: User | null) => {
        if (!user) return 'N/A';
        if (user.full_name) return user.full_name;
        if (user.first_name && user.last_name) return `${user.first_name} ${user.last_name}`;
        return user.name || 'Utilisateur';
    };

    const getStatusBadge = (status: string) => {
        const config: Record<string, { color: string; label: string; icon: React.ReactNode }> = {
            pending_colleague: {
                color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                label: 'En attente (collègue)',
                icon: <ClockIcon className="h-3 w-3" />,
            },
            pending_manager: {
                color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                label: 'En attente (manager)',
                icon: <ClockIcon className="h-3 w-3" />,
            },
            approved: {
                color: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                label: 'Approuvé',
                icon: <CheckCircleIcon className="h-3 w-3" />,
            },
            rejected_colleague: {
                color: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                label: 'Refusé (collègue)',
                icon: <XCircleIcon className="h-3 w-3" />,
            },
            rejected_manager: {
                color: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                label: 'Refusé (manager)',
                icon: <XCircleIcon className="h-3 w-3" />,
            },
            cancelled: {
                color: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                label: 'Annulé',
                icon: <XCircleIcon className="h-3 w-3" />,
            },
            expired: {
                color: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                label: 'Expiré',
                icon: <ClockIcon className="h-3 w-3" />,
            },
        };
        const { color, label, icon } = config[status] || { color: 'bg-gray-100 text-gray-800', label: status, icon: null };
        return (
            <Badge className={`${color} flex items-center gap-1`}>
                {icon}
                {label}
            </Badge>
        );
    };

    const handleCancel = () => {
        if (!selectedSwap) return;
        setProcessingId(selectedSwap.id);
        router.post(`/departments/${department.uuid}/swap-requests/${selectedSwap.uuid}/cancel`, {}, {
            onSuccess: () => {
                toast.success('Demande annulée');
                setCancelDialogOpen(false);
                setSelectedSwap(null);
            },
            onError: () => toast.error('Erreur lors de l\'annulation'),
            onFinish: () => setProcessingId(null),
        });
    };

    const handleAccept = (swap: SwapRequest) => {
        setProcessingId(swap.id);
        router.post(`/departments/${department.uuid}/swap-requests/${swap.uuid}/accept-colleague`, {}, {
            onSuccess: () => toast.success('Échange accepté'),
            onError: () => toast.error('Erreur lors de l\'acceptation'),
            onFinish: () => setProcessingId(null),
        });
    };

    const handleReject = (swap: SwapRequest) => {
        setProcessingId(swap.id);
        router.post(`/departments/${department.uuid}/swap-requests/${swap.uuid}/reject-colleague`, {}, {
            onSuccess: () => toast.success('Échange refusé'),
            onError: () => toast.error('Erreur lors du refus'),
            onFinish: () => setProcessingId(null),
        });
    };

    const isPending = (status: string) => status === 'pending_colleague' || status === 'pending_manager';
    const pendingOutgoing = outgoing.filter(s => isPending(s.status));
    const processedOutgoing = outgoing.filter(s => !isPending(s.status));

    return (
        <DashboardLayout>
            <Head title={`Mes échanges - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href={`/departments/${department.uuid}/schedule`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour au planning
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Mes demandes d'échange
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {department.name}
                            </p>
                        </div>
                        <Link href={`/departments/${department.uuid}/swap-requests/create`}>
                            <Button className="bg-blue-600 hover:bg-blue-700">
                                <PlusIcon className="h-4 w-4 mr-2" />
                                Nouvelle demande
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Incoming Requests */}
                {incoming.length > 0 && (
                    <Card className="mb-6 border-blue-200 dark:border-blue-800">
                        <CardHeader className="bg-blue-50 dark:bg-blue-900/20">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <InboxArrowDownIcon className="h-5 w-5 text-blue-600" />
                                Demandes reçues ({incoming.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y divide-gray-200 dark:divide-gray-700 p-0">
                            {incoming.map((swap) => (
                                <div key={swap.uuid} className="p-4">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-2">
                                                <UserIcon className="h-5 w-5 text-gray-400" />
                                                <span className="font-medium text-gray-900 dark:text-white">
                                                    {getUserName(swap.requester)}
                                                </span>
                                                <span className="text-gray-500">souhaite échanger avec vous</span>
                                            </div>

                                            <div className="flex items-center gap-4 text-sm">
                                                {/* Requested Shift (yours) */}
                                                <div className="flex items-center gap-2 p-2 bg-red-50 dark:bg-red-900/20 rounded">
                                                    <CalendarDaysIcon className="h-4 w-4 text-red-600 dark:text-red-400" />
                                                    <div>
                                                        <p className="text-xs text-red-600 dark:text-red-400">Votre shift</p>
                                                        <p className="font-medium">{formatDate(swap.requested_shift.date)}</p>
                                                        <p className="text-xs text-gray-500">
                                                            {formatTime(swap.requested_shift.start_time)} - {formatTime(swap.requested_shift.end_time)}
                                                        </p>
                                                    </div>
                                                </div>

                                                <ArrowsRightLeftIcon className="h-5 w-5 text-gray-400" />

                                                {/* Offered Shift */}
                                                {swap.offered_shift ? (
                                                    <div className="flex items-center gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                                        <CalendarDaysIcon className="h-4 w-4 text-green-600 dark:text-green-400" />
                                                        <div>
                                                            <p className="text-xs text-green-600 dark:text-green-400">En échange</p>
                                                            <p className="font-medium">{formatDate(swap.offered_shift.date)}</p>
                                                            <p className="text-xs text-gray-500">
                                                                {formatTime(swap.offered_shift.start_time)} - {formatTime(swap.offered_shift.end_time)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-400 italic p-2">Échange simple</span>
                                                )}
                                            </div>

                                            {swap.reason && (
                                                <p className="mt-2 text-sm text-gray-500 italic">
                                                    "{swap.reason}"
                                                </p>
                                            )}
                                        </div>

                                        <div className="flex gap-2 ml-4">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => handleReject(swap)}
                                                disabled={processingId === swap.id}
                                            >
                                                <XCircleIcon className="h-4 w-4 mr-1" />
                                                Refuser
                                            </Button>
                                            <Button
                                                size="sm"
                                                className="bg-green-600 hover:bg-green-700"
                                                onClick={() => handleAccept(swap)}
                                                disabled={processingId === swap.id}
                                            >
                                                <CheckCircleIcon className="h-4 w-4 mr-1" />
                                                Accepter
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {/* Pending Outgoing Requests */}
                {pendingOutgoing.length > 0 && (
                    <Card className="mb-6 border-yellow-200 dark:border-yellow-800">
                        <CardHeader className="bg-yellow-50 dark:bg-yellow-900/20">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <PaperAirplaneIcon className="h-5 w-5 text-yellow-600" />
                                Demandes envoyées en attente ({pendingOutgoing.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y divide-gray-200 dark:divide-gray-700 p-0">
                            {pendingOutgoing.map((swap) => (
                                <div key={swap.uuid} className="p-4">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-2">
                                                {getStatusBadge(swap.status)}
                                                <span className="text-sm text-gray-500">
                                                    vers {getUserName(swap.target_user)}
                                                </span>
                                            </div>

                                            <div className="flex items-center gap-4 text-sm">
                                                {/* Requested Shift */}
                                                <div className="flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                                                    <CalendarDaysIcon className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                    <div>
                                                        <p className="text-xs text-blue-600 dark:text-blue-400">Demandé</p>
                                                        <p className="font-medium">{formatDate(swap.requested_shift.date)}</p>
                                                        <p className="text-xs text-gray-500">
                                                            {formatTime(swap.requested_shift.start_time)} - {formatTime(swap.requested_shift.end_time)}
                                                        </p>
                                                    </div>
                                                </div>

                                                <ArrowsRightLeftIcon className="h-5 w-5 text-gray-400" />

                                                {/* Offered Shift */}
                                                {swap.offered_shift ? (
                                                    <div className="flex items-center gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                                        <CalendarDaysIcon className="h-4 w-4 text-green-600 dark:text-green-400" />
                                                        <div>
                                                            <p className="text-xs text-green-600 dark:text-green-400">Offert</p>
                                                            <p className="font-medium">{formatDate(swap.offered_shift.date)}</p>
                                                            <p className="text-xs text-gray-500">
                                                                {formatTime(swap.offered_shift.start_time)} - {formatTime(swap.offered_shift.end_time)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-400 italic p-2">Échange simple</span>
                                                )}
                                            </div>

                                            {swap.reason && (
                                                <p className="mt-2 text-sm text-gray-500 italic">
                                                    "{swap.reason}"
                                                </p>
                                            )}
                                        </div>

                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => {
                                                setSelectedSwap(swap);
                                                setCancelDialogOpen(true);
                                            }}
                                            disabled={processingId === swap.id}
                                        >
                                            Annuler
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {/* Processed/History */}
                {processedOutgoing.length > 0 && (
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="text-lg">Historique</CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y divide-gray-200 dark:divide-gray-700 p-0">
                            {processedOutgoing.map((swap) => (
                                <Link
                                    key={swap.uuid}
                                    href={`/departments/${department.uuid}/swap-requests/${swap.uuid}`}
                                    className="block p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors opacity-70"
                                >
                                    <div className="flex items-center gap-4">
                                        {getStatusBadge(swap.status)}
                                        <span className="text-sm">
                                            {formatDate(swap.requested_shift.date)} {formatTime(swap.requested_shift.start_time)}
                                        </span>
                                        {swap.offered_shift && (
                                            <>
                                                <ArrowsRightLeftIcon className="h-4 w-4 text-gray-400" />
                                                <span className="text-sm">
                                                    {formatDate(swap.offered_shift.date)} {formatTime(swap.offered_shift.start_time)}
                                                </span>
                                            </>
                                        )}
                                        <span className="text-sm text-gray-500">
                                            avec {getUserName(swap.target_user)}
                                        </span>
                                    </div>
                                    {swap.rejection_reason && (
                                        <p className="text-sm text-red-600 dark:text-red-400 mt-1">
                                            Motif: {swap.rejection_reason}
                                        </p>
                                    )}
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {/* Empty State */}
                {outgoing.length === 0 && incoming.length === 0 && (
                    <Card>
                        <CardContent className="text-center py-12">
                            <ArrowsRightLeftIcon className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Aucune demande d'échange
                            </h3>
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Vous n'avez pas encore fait de demande d'échange de shift.
                            </p>
                            <Link href={`/departments/${department.uuid}/swap-requests/create`}>
                                <Button className="bg-blue-600 hover:bg-blue-700">
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Créer une demande
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Cancel Dialog */}
            <DeleteConfirmationDialog
                open={cancelDialogOpen}
                onOpenChange={setCancelDialogOpen}
                title="Annuler la demande"
                description="Êtes-vous sûr de vouloir annuler cette demande d'échange ?"
                confirmText="Annuler la demande"
                cancelText="Retour"
                onConfirm={handleCancel}
            />
        </DashboardLayout>
    );
}
