import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowLeftIcon,
    CalendarDaysIcon,
    UserIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
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
    full_name: string;
    email: string;
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
    is_half_day_start: boolean;
    is_half_day_end: boolean;
    document_path: string | null;
    approved_by_user: User | null;
    approved_at: string | null;
    rejection_reason: string | null;
    rejected_at: string | null;
    created_at: string;
}

interface Props {
    department: Department;
    absence: Absence;
}

export default function AbsenceShow({ department, absence }: Props) {
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

    const getStatusBadge = (status: string) => {
        const config: Record<string, { color: string; label: string; icon: React.ReactNode }> = {
            pending: {
                color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                label: 'En attente',
                icon: <ClockIcon className="h-4 w-4" />,
            },
            approved: {
                color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                label: 'Approuvee',
                icon: <CheckCircleIcon className="h-4 w-4" />,
            },
            rejected: {
                color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                label: 'Refusee',
                icon: <XCircleIcon className="h-4 w-4" />,
            },
            cancelled: {
                color: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                label: 'Annulee',
                icon: <XCircleIcon className="h-4 w-4" />,
            },
        };
        const { color, label, icon } = config[status] || config.pending;
        return (
            <Badge className={`${color} flex items-center gap-1`}>
                {icon}
                {label}
            </Badge>
        );
    };

    const getTypeLabel = (type: string) => {
        const labels: Record<string, string> = {
            vacation: 'Conges payes',
            sick_leave: 'Maladie',
            family_leave: 'Conge familial',
            maternity_leave: 'Conge maternite',
            paternity_leave: 'Conge paternite',
            training: 'Formation',
            unpaid_leave: 'Conge sans solde',
            compensatory: 'Recuperation',
            public_holiday: 'Jour ferie',
            other: 'Autre',
        };
        return labels[type] || type;
    };

    const handleApprove = () => {
        router.post(`/departments/${department.uuid}/absences/${absence.uuid}/approve`, {}, {
            onSuccess: () => toast.success('Demande approuvee'),
            onError: () => toast.error('Erreur lors de l\'approbation'),
        });
    };

    const handleReject = () => {
        const reason = prompt('Motif du refus:');
        if (!reason) return;
        router.post(`/departments/${department.uuid}/absences/${absence.uuid}/reject`, {
            rejection_reason: reason,
        }, {
            onSuccess: () => toast.success('Demande refusee'),
            onError: () => toast.error('Erreur lors du refus'),
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Absence - ${absence.user.full_name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8 max-w-3xl">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href={`/departments/${department.uuid}/absences`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour aux absences
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Demande d'absence
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {department.name}
                            </p>
                        </div>
                        {getStatusBadge(absence.status)}
                    </div>
                </div>

                {/* User Info */}
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
                                    {absence.user.full_name}
                                </p>
                                <p className="text-sm text-gray-500">{absence.user.email}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Absence Details */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-lg flex items-center gap-2">
                            <CalendarDaysIcon className="h-5 w-5" />
                            Details de l'absence
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Type</p>
                                <p className="font-medium">{getTypeLabel(absence.type)}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Duree</p>
                                <p className="font-medium">{absence.days_count || 1} jour(s)</p>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Date de debut</p>
                                <p className="font-medium">{formatDate(absence.start_date)}</p>
                                {absence.is_half_day_start && (
                                    <Badge variant="outline" className="mt-1">Demi-journee</Badge>
                                )}
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Date de fin</p>
                                <p className="font-medium">{formatDate(absence.end_date)}</p>
                                {absence.is_half_day_end && (
                                    <Badge variant="outline" className="mt-1">Demi-journee</Badge>
                                )}
                            </div>
                        </div>

                        {absence.reason && (
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Motif</p>
                                <p className="mt-1">{absence.reason}</p>
                            </div>
                        )}

                        {absence.document_path && (
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Justificatif</p>
                                <a
                                    href={`/storage/${absence.document_path}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 mt-1"
                                >
                                    <DocumentTextIcon className="h-4 w-4" />
                                    Voir le document
                                </a>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Approval Info */}
                {(absence.status === 'approved' || absence.status === 'rejected') && (
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="text-lg">
                                {absence.status === 'approved' ? 'Approbation' : 'Refus'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {absence.approved_by_user && (
                                <p>
                                    <span className="text-gray-500">Par:</span>{' '}
                                    <span className="font-medium">{absence.approved_by_user.full_name}</span>
                                </p>
                            )}
                            {absence.approved_at && (
                                <p>
                                    <span className="text-gray-500">Le:</span>{' '}
                                    {formatDateTime(absence.approved_at)}
                                </p>
                            )}
                            {absence.rejection_reason && (
                                <div className="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                    <p className="text-sm text-red-800 dark:text-red-200">
                                        <strong>Motif du refus:</strong> {absence.rejection_reason}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Meta */}
                <Card className="mb-6">
                    <CardContent className="pt-4">
                        <p className="text-sm text-gray-500">
                            Demande creee le {formatDateTime(absence.created_at)}
                        </p>
                    </CardContent>
                </Card>

                {/* Actions */}
                {absence.status === 'pending' && (
                    <div className="flex justify-end gap-4">
                        <Button variant="destructive" onClick={handleReject}>
                            <XCircleIcon className="h-4 w-4 mr-2" />
                            Refuser
                        </Button>
                        <Button className="bg-green-600 hover:bg-green-700" onClick={handleApprove}>
                            <CheckCircleIcon className="h-4 w-4 mr-2" />
                            Approuver
                        </Button>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
