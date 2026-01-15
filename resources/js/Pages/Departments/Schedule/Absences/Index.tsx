import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import {
    ArrowLeftIcon,
    CalendarDaysIcon,
    FunnelIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    UserIcon,
    DocumentTextIcon,
    PlusIcon,
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
    full_name: string;
}

interface Absence {
    id: number;
    uuid: string;
    user: User;
    type: string;
    start_date: string;
    end_date: string;
    status: string;
    reason: string | null;
    days_count: number | null;
    approved_by_user: User | null;
    approved_at: string | null;
    rejection_reason: string | null;
    created_at: string;
}

interface AbsenceType {
    value: string;
    label: string;
    color: string;
    requiresApproval: boolean;
    deductsFromBalance: boolean;
}

interface AbsenceStatus {
    value: string;
    label: string;
    color: string;
}

interface Filters {
    status?: string;
    type?: string;
    from?: string;
    to?: string;
}

interface PaginatedAbsences {
    data: Absence[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    department: Department;
    absences: PaginatedAbsences;
    pendingCount: number;
    absenceTypes: AbsenceType[];
    absenceStatuses: AbsenceStatus[];
    filters: Filters;
}

export default function AbsencesIndex({
    department,
    absences,
    pendingCount,
    absenceTypes,
    absenceStatuses,
    filters,
}: Props) {
    const [localFilters, setLocalFilters] = useState<Filters>(filters);
    const [selectedAbsence, setSelectedAbsence] = useState<Absence | null>(null);
    const [showRejectDialog, setShowRejectDialog] = useState(false);
    const [rejectionReason, setRejectionReason] = useState('');

    const applyFilters = () => {
        router.get(`/departments/${department.uuid}/absences`, localFilters as Record<string, string>, {
            preserveState: true,
        });
    };

    const clearFilters = () => {
        setLocalFilters({});
        router.get(`/departments/${department.uuid}/absences`);
    };

    const handleApprove = (absence: Absence) => {
        router.post(`/departments/${department.uuid}/absences/${absence.uuid}/approve`, {}, {
            onSuccess: () => toast.success('Demande approuvée'),
            onError: () => toast.error('Erreur lors de l\'approbation'),
        });
    };

    const handleReject = () => {
        if (!selectedAbsence || !rejectionReason.trim()) {
            toast.error('Veuillez indiquer un motif de refus');
            return;
        }
        router.post(`/departments/${department.uuid}/absences/${selectedAbsence.uuid}/reject`, {
            rejection_reason: rejectionReason,
        }, {
            onSuccess: () => {
                toast.success('Demande refusée');
                setShowRejectDialog(false);
                setSelectedAbsence(null);
                setRejectionReason('');
            },
            onError: () => toast.error('Erreur lors du refus'),
        });
    };

    const getStatusBadge = (status: string) => {
        const statusInfo = absenceStatuses.find(s => s.value === status);
        const colors: Record<string, string> = {
            pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        };
        return (
            <Badge className={colors[status] || 'bg-gray-100 text-gray-800'}>
                {statusInfo?.label || status}
            </Badge>
        );
    };

    const getTypeBadge = (type: string) => {
        const typeInfo = absenceTypes.find(t => t.value === type);
        return (
            <Badge variant="outline">
                {typeInfo?.label || type}
            </Badge>
        );
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Absences - ${department.name}`} />

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
                                Gestion des Absences
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {department.name}
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            {pendingCount > 0 && (
                                <Badge className="bg-orange-500 text-white">
                                    {pendingCount} demande(s) en attente
                                </Badge>
                            )}
                            <Button asChild>
                                <Link href={`/departments/${department.uuid}/absences/create`}>
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Nouvelle demande
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-lg flex items-center gap-2">
                            <FunnelIcon className="h-5 w-5" />
                            Filtres
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <Select
                                value={localFilters.status || ''}
                                onValueChange={(value) => setLocalFilters(prev => ({ ...prev, status: value || undefined }))}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Tous les statuts" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Tous les statuts</SelectItem>
                                    {absenceStatuses.map((status) => (
                                        <SelectItem key={status.value} value={status.value}>
                                            {status.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={localFilters.type || ''}
                                onValueChange={(value) => setLocalFilters(prev => ({ ...prev, type: value || undefined }))}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Tous les types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Tous les types</SelectItem>
                                    {absenceTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Input
                                type="date"
                                value={localFilters.from || ''}
                                onChange={(e) => setLocalFilters(prev => ({ ...prev, from: e.target.value || undefined }))}
                                placeholder="Du"
                            />

                            <Input
                                type="date"
                                value={localFilters.to || ''}
                                onChange={(e) => setLocalFilters(prev => ({ ...prev, to: e.target.value || undefined }))}
                                placeholder="Au"
                            />
                        </div>
                        <div className="flex gap-2 mt-4">
                            <Button onClick={applyFilters}>Appliquer</Button>
                            <Button variant="outline" onClick={clearFilters}>Effacer</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Absences List */}
                <Card>
                    <CardContent className="p-0">
                        {absences.data.length === 0 ? (
                            <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                <CalendarDaysIcon className="h-12 w-12 mx-auto mb-4" />
                                <p>Aucune absence trouvée</p>
                            </div>
                        ) : (
                            <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                {absences.data.map((absence) => (
                                    <div key={absence.uuid} className="p-4 hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <UserIcon className="h-5 w-5 text-gray-400" />
                                                    <span className="font-medium text-gray-900 dark:text-white">
                                                        {absence.user.full_name}
                                                    </span>
                                                    {getTypeBadge(absence.type)}
                                                    {getStatusBadge(absence.status)}
                                                </div>
                                                <div className="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                                    <span className="flex items-center gap-1">
                                                        <CalendarDaysIcon className="h-4 w-4" />
                                                        {formatDate(absence.start_date)} - {formatDate(absence.end_date)}
                                                    </span>
                                                    {absence.days_count && (
                                                        <span>({absence.days_count} jour(s))</span>
                                                    )}
                                                </div>
                                                {absence.reason && (
                                                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                                        <DocumentTextIcon className="h-4 w-4 inline mr-1" />
                                                        {absence.reason}
                                                    </p>
                                                )}
                                                {absence.rejection_reason && (
                                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                                        Motif du refus: {absence.rejection_reason}
                                                    </p>
                                                )}
                                            </div>
                                            {absence.status === 'pending' && (
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        onClick={() => handleApprove(absence)}
                                                        className="bg-green-600 hover:bg-green-700"
                                                    >
                                                        <CheckCircleIcon className="h-4 w-4 mr-1" />
                                                        Approuver
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            setSelectedAbsence(absence);
                                                            setShowRejectDialog(true);
                                                        }}
                                                    >
                                                        <XCircleIcon className="h-4 w-4 mr-1" />
                                                        Refuser
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {absences.last_page > 1 && (
                    <div className="mt-4 flex justify-center gap-2">
                        {Array.from({ length: absences.last_page }, (_, i) => i + 1).map((page) => (
                            <Button
                                key={page}
                                variant={page === absences.current_page ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => router.get(`/departments/${department.uuid}/absences`, { ...filters, page })}
                            >
                                {page}
                            </Button>
                        ))}
                    </div>
                )}
            </div>

            {/* Reject Dialog */}
            <DeleteConfirmationDialog
                open={showRejectDialog}
                onOpenChange={setShowRejectDialog}
                title="Refuser la demande"
                description="Veuillez indiquer le motif du refus."
                confirmText="Refuser"
                cancelText="Annuler"
                onConfirm={handleReject}
            >
                <div className="mt-4">
                    <Input
                        placeholder="Motif du refus..."
                        value={rejectionReason}
                        onChange={(e) => setRejectionReason(e.target.value)}
                    />
                </div>
            </DeleteConfirmationDialog>
        </DashboardLayout>
    );
}
