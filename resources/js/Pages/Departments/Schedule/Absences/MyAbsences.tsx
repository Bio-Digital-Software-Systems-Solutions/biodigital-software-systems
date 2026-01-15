import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowLeftIcon,
    CalendarDaysIcon,
    PlusIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    PencilSquareIcon,
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

interface LeaveBalance {
    leave_type: string;
    entitled_days: number;
    taken_days: number;
    pending_days: number;
    carried_over: number;
}

interface Props {
    department: Department;
    absences: Absence[];
    balances: Record<string, LeaveBalance>;
    absenceTypes: AbsenceType[];
}

export default function MyAbsences({
    department,
    absences,
    balances,
    absenceTypes,
}: Props) {
    const [cancelDialogOpen, setCancelDialogOpen] = React.useState(false);
    const [selectedAbsence, setSelectedAbsence] = React.useState<Absence | null>(null);

    const getStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        };
        const labels: Record<string, string> = {
            pending: 'En attente',
            approved: 'Approuvée',
            rejected: 'Refusée',
            cancelled: 'Annulée',
        };
        return (
            <Badge className={colors[status] || 'bg-gray-100 text-gray-800'}>
                {status === 'pending' && <ClockIcon className="h-3 w-3 mr-1" />}
                {status === 'approved' && <CheckCircleIcon className="h-3 w-3 mr-1" />}
                {status === 'rejected' && <XCircleIcon className="h-3 w-3 mr-1" />}
                {labels[status] || status}
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
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    const handleCancel = () => {
        if (!selectedAbsence) return;
        router.post(`/departments/${department.uuid}/absences/${selectedAbsence.uuid}/cancel`, {}, {
            onSuccess: () => {
                toast.success('Demande annulée');
                setCancelDialogOpen(false);
                setSelectedAbsence(null);
            },
            onError: () => toast.error('Erreur lors de l\'annulation'),
        });
    };

    const canCancel = (absence: Absence) => {
        return ['pending', 'approved'].includes(absence.status);
    };

    const getRemainingDays = (leaveType: string) => {
        const balance = balances[leaveType];
        if (!balance) return null;
        return balance.entitled_days + balance.carried_over - balance.taken_days - balance.pending_days;
    };

    // Group absences by status
    const pendingAbsences = absences.filter(a => a.status === 'pending');
    const approvedAbsences = absences.filter(a => a.status === 'approved');
    const otherAbsences = absences.filter(a => !['pending', 'approved'].includes(a.status));

    return (
        <DashboardLayout>
            <Head title={`Mes Absences - ${department.name}`} />

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
                                Mes Absences
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {department.name}
                            </p>
                        </div>
                        <Link href={`/departments/${department.uuid}/absences/create`}>
                            <Button>
                                <PlusIcon className="h-4 w-4 mr-2" />
                                Nouvelle demande
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Leave Balances */}
                {Object.keys(balances).length > 0 && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        {Object.entries(balances).map(([type, balance]) => {
                            const typeInfo = absenceTypes.find(t => t.value === type);
                            const remaining = getRemainingDays(type);
                            return (
                                <Card key={type}>
                                    <CardContent className="pt-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                                {typeInfo?.label || type}
                                            </span>
                                            <Badge variant={remaining && remaining > 5 ? 'default' : 'destructive'}>
                                                {remaining?.toFixed(1)} restant(s)
                                            </Badge>
                                        </div>
                                        <div className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span>Droits: {balance.entitled_days}j</span>
                                            {balance.carried_over > 0 && <span> + {balance.carried_over}j reportés</span>}
                                            <span> | Pris: {balance.taken_days}j</span>
                                            {balance.pending_days > 0 && <span> | En attente: {balance.pending_days}j</span>}
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}

                {/* Pending Requests */}
                {pendingAbsences.length > 0 && (
                    <Card className="mb-6 border-yellow-200 dark:border-yellow-800">
                        <CardHeader className="bg-yellow-50 dark:bg-yellow-900/20">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <ClockIcon className="h-5 w-5 text-yellow-600" />
                                Demandes en attente ({pendingAbsences.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y divide-gray-200 dark:divide-gray-700">
                            {pendingAbsences.map((absence) => (
                                <div key={absence.uuid} className="py-4 first:pt-4">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <div className="flex items-center gap-2 mb-1">
                                                {getTypeBadge(absence.type)}
                                                {getStatusBadge(absence.status)}
                                            </div>
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                                <CalendarDaysIcon className="h-4 w-4" />
                                                {formatDate(absence.start_date)} - {formatDate(absence.end_date)}
                                                {absence.days_count && <span>({absence.days_count} jour(s))</span>}
                                            </div>
                                            {absence.reason && (
                                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    {absence.reason}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex gap-2">
                                            <Link href={`/departments/${department.uuid}/absences/${absence.uuid}/edit`}>
                                                <Button size="sm" variant="outline">
                                                    <PencilSquareIcon className="h-4 w-4 mr-1" />
                                                    Modifier
                                                </Button>
                                            </Link>
                                            {canCancel(absence) && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        setSelectedAbsence(absence);
                                                        setCancelDialogOpen(true);
                                                    }}
                                                >
                                                    Annuler
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {/* Approved Absences */}
                {approvedAbsences.length > 0 && (
                    <Card className="mb-6 border-green-200 dark:border-green-800">
                        <CardHeader className="bg-green-50 dark:bg-green-900/20">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <CheckCircleIcon className="h-5 w-5 text-green-600" />
                                Absences approuvées ({approvedAbsences.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y divide-gray-200 dark:divide-gray-700">
                            {approvedAbsences.map((absence) => (
                                <div key={absence.uuid} className="py-4 first:pt-4">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <div className="flex items-center gap-2 mb-1">
                                                {getTypeBadge(absence.type)}
                                                {getStatusBadge(absence.status)}
                                            </div>
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                                <CalendarDaysIcon className="h-4 w-4" />
                                                {formatDate(absence.start_date)} - {formatDate(absence.end_date)}
                                                {absence.days_count && <span>({absence.days_count} jour(s))</span>}
                                            </div>
                                            {absence.approved_by_user && (
                                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    Approuvée par {absence.approved_by_user.full_name}
                                                </p>
                                            )}
                                        </div>
                                        {canCancel(absence) && new Date(absence.start_date) > new Date() && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => {
                                                    setSelectedAbsence(absence);
                                                    setCancelDialogOpen(true);
                                                }}
                                            >
                                                Annuler
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {/* Other Absences (rejected, cancelled) */}
                {otherAbsences.length > 0 && (
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="text-lg">Historique</CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y divide-gray-200 dark:divide-gray-700">
                            {otherAbsences.map((absence) => (
                                <div key={absence.uuid} className="py-4 first:pt-4 opacity-60">
                                    <div className="flex items-center gap-2 mb-1">
                                        {getTypeBadge(absence.type)}
                                        {getStatusBadge(absence.status)}
                                    </div>
                                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <CalendarDaysIcon className="h-4 w-4" />
                                        {formatDate(absence.start_date)} - {formatDate(absence.end_date)}
                                    </div>
                                    {absence.rejection_reason && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                            <ExclamationTriangleIcon className="h-4 w-4 inline mr-1" />
                                            Motif: {absence.rejection_reason}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {/* Empty State */}
                {absences.length === 0 && (
                    <Card>
                        <CardContent className="text-center py-12">
                            <CalendarDaysIcon className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Aucune absence
                            </h3>
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Vous n'avez pas encore de demande d'absence.
                            </p>
                            <Link href={`/departments/${department.uuid}/absences/create`}>
                                <Button>
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Faire une demande
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
                description="Etes-vous sur de vouloir annuler cette demande d'absence ?"
                confirmText="Annuler la demande"
                cancelText="Retour"
                onConfirm={handleCancel}
            />
        </DashboardLayout>
    );
}
