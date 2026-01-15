import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowLeftIcon,
    CalendarDaysIcon,
    UserGroupIcon,
    ClockIcon,
} from '@heroicons/react/24/outline';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Schedule {
    id: number;
    uuid: string;
    week_start: string;
    week_end: string;
    status: string;
    notes: string | null;
    shifts_count: number;
    assigned_shifts_count: number;
}

interface Props {
    department: Department;
    schedule: Schedule;
}

export default function ScheduleShow({ department, schedule }: Props) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const getStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            draft: 'bg-gray-100 text-gray-800',
            published: 'bg-green-100 text-green-800',
            locked: 'bg-blue-100 text-blue-800',
        };
        const labels: Record<string, string> = {
            draft: 'Brouillon',
            published: 'Publie',
            locked: 'Verrouille',
        };
        return (
            <Badge className={colors[status] || 'bg-gray-100 text-gray-800'}>
                {labels[status] || status}
            </Badge>
        );
    };

    return (
        <DashboardLayout>
            <Head title={`Planning - ${department.name}`} />

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
                                Semaine du {formatDate(schedule.week_start)}
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {department.name}
                            </p>
                        </div>
                        {getStatusBadge(schedule.status)}
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <Card>
                        <CardContent className="pt-4">
                            <div className="flex items-center gap-3">
                                <CalendarDaysIcon className="h-8 w-8 text-blue-500" />
                                <div>
                                    <p className="text-2xl font-bold">{schedule.shifts_count}</p>
                                    <p className="text-sm text-gray-500">Shifts total</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-4">
                            <div className="flex items-center gap-3">
                                <UserGroupIcon className="h-8 w-8 text-green-500" />
                                <div>
                                    <p className="text-2xl font-bold">{schedule.assigned_shifts_count}</p>
                                    <p className="text-sm text-gray-500">Shifts assignes</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-4">
                            <div className="flex items-center gap-3">
                                <ClockIcon className="h-8 w-8 text-orange-500" />
                                <div>
                                    <p className="text-2xl font-bold">
                                        {schedule.shifts_count - schedule.assigned_shifts_count}
                                    </p>
                                    <p className="text-sm text-gray-500">Non assignes</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Notes */}
                {schedule.notes && (
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-gray-600 dark:text-gray-300">{schedule.notes}</p>
                        </CardContent>
                    </Card>
                )}

                {/* Actions */}
                <div className="flex gap-4">
                    <Link href={`/departments/${department.uuid}/schedule?week=${schedule.week_start}`}>
                        <Button>Voir le planning complet</Button>
                    </Link>
                </div>
            </div>
        </DashboardLayout>
    );
}
