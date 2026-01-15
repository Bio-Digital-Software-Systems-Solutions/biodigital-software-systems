import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    ArrowLeftIcon,
    ArrowsRightLeftIcon,
    CalendarDaysIcon,
    UserIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    FunnelIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import type { PaginatedData } from '@/Types/scheduling';

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
    title?: string;
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
    target_response: string | null;
    manager_notes: string | null;
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
    swapRequests: PaginatedData<SwapRequest>;
    pendingColleague: number;
    pendingManager: number;
    swapStatuses: SwapStatus[];
    filters: {
        status?: string;
    };
}

export default function SwapRequestsIndex({
    department,
    swapRequests,
    pendingColleague,
    pendingManager,
    swapStatuses,
    filters,
}: Props) {
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

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

    const handleApproveManager = (swap: SwapRequest) => {
        router.post(`/departments/${department.uuid}/swap-requests/${swap.uuid}/approve-manager`, {}, {
            onSuccess: () => toast.success('Échange approuvé'),
            onError: () => toast.error('Erreur lors de l\'approbation'),
        });
    };

    const handleRejectManager = (swap: SwapRequest) => {
        const reason = prompt('Motif du refus:');
        if (!reason) return;
        router.post(`/departments/${department.uuid}/swap-requests/${swap.uuid}/reject-manager`, {
            rejection_reason: reason,
        }, {
            onSuccess: () => toast.success('Échange refusé'),
            onError: () => toast.error('Erreur lors du refus'),
        });
    };

    const handleFilterChange = (value: string) => {
        setStatusFilter(value);
        router.get(`/departments/${department.uuid}/swap-requests`, {
            status: value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const totalPending = pendingColleague + pendingManager;

    return (
        <DashboardLayout>
            <Head title={`Échanges de shifts - ${department.name}`} />

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
                                Demandes d'échange
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {department.name}
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            {pendingColleague > 0 && (
                                <Badge className="bg-yellow-500 text-white">
                                    {pendingColleague} collègue
                                </Badge>
                            )}
                            {pendingManager > 0 && (
                                <Badge className="bg-blue-500 text-white">
                                    {pendingManager} manager
                                </Badge>
                            )}
                            <Link href={`/departments/${department.uuid}/swap-requests/my`}>
                                <Button variant="outline">
                                    Mes demandes
                                </Button>
                            </Link>
                            <Link href={`/departments/${department.uuid}/swap-requests/create`}>
                                <Button>
                                    <ArrowsRightLeftIcon className="h-4 w-4 mr-2" />
                                    Nouvelle demande
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="mb-4 flex items-center gap-4">
                    <div className="flex items-center gap-2">
                        <FunnelIcon className="h-5 w-5 text-gray-400" />
                        <Select value={statusFilter} onValueChange={handleFilterChange}>
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder="Tous les statuts" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Tous les statuts</SelectItem>
                                {swapStatuses.map((status) => (
                                    <SelectItem key={status.value} value={status.value}>
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <span className="text-sm text-gray-500">
                        {swapRequests.total} demande{swapRequests.total !== 1 ? 's' : ''}
                    </span>
                </div>

                {/* Swap Requests List */}
                <Card>
                    <CardContent className="p-0">
                        {swapRequests.data.length === 0 ? (
                            <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                <ArrowsRightLeftIcon className="h-12 w-12 mx-auto mb-4" />
                                <p>Aucune demande d'échange</p>
                            </div>
                        ) : (
                            <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                {swapRequests.data.map((swap) => (
                                    <Link
                                        key={swap.uuid}
                                        href={`/departments/${department.uuid}/swap-requests/${swap.uuid}`}
                                        className="block p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <UserIcon className="h-5 w-5 text-gray-400" />
                                                    <span className="font-medium text-gray-900 dark:text-white">
                                                        {getUserName(swap.requester)}
                                                    </span>
                                                    {getStatusBadge(swap.status)}
                                                </div>

                                                <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
                                                    {/* Requested Shift */}
                                                    <div className="flex items-center gap-2">
                                                        <div className="p-2 bg-red-50 dark:bg-red-900/20 rounded">
                                                            <CalendarDaysIcon className="h-4 w-4 text-red-600 dark:text-red-400" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium">{formatDate(swap.requested_shift.date)}</p>
                                                            <p className="text-xs">
                                                                {formatTime(swap.requested_shift.start_time)} - {formatTime(swap.requested_shift.end_time)}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <ArrowsRightLeftIcon className="h-5 w-5 text-gray-400" />

                                                    {/* Offered Shift */}
                                                    {swap.offered_shift ? (
                                                        <div className="flex items-center gap-2">
                                                            <div className="p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                                                <CalendarDaysIcon className="h-4 w-4 text-green-600 dark:text-green-400" />
                                                            </div>
                                                            <div>
                                                                <p className="font-medium">{formatDate(swap.offered_shift.date)}</p>
                                                                <p className="text-xs">
                                                                    {formatTime(swap.offered_shift.start_time)} - {formatTime(swap.offered_shift.end_time)}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-gray-400 italic">Échange simple</span>
                                                    )}

                                                    {swap.target_user && (
                                                        <span className="text-sm">
                                                            avec <span className="font-medium">{getUserName(swap.target_user)}</span>
                                                        </span>
                                                    )}
                                                </div>

                                                {swap.reason && (
                                                    <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                                        "{swap.reason}"
                                                    </p>
                                                )}

                                                {swap.target_response && (
                                                    <p className="mt-2 text-sm text-blue-600 dark:text-blue-400">
                                                        Réponse collègue: {swap.target_response}
                                                    </p>
                                                )}

                                                {swap.manager_notes && (
                                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                                        Notes manager: {swap.manager_notes}
                                                    </p>
                                                )}
                                            </div>

                                            {swap.status === 'pending_manager' && (
                                                <div className="flex gap-2" onClick={(e) => e.preventDefault()}>
                                                    <Button
                                                        size="sm"
                                                        className="bg-green-600 hover:bg-green-700"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            handleApproveManager(swap);
                                                        }}
                                                    >
                                                        <CheckCircleIcon className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            handleRejectManager(swap);
                                                        }}
                                                    >
                                                        <XCircleIcon className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {swapRequests.last_page > 1 && (
                    <div className="mt-4 flex justify-center gap-2">
                        {swapRequests.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
