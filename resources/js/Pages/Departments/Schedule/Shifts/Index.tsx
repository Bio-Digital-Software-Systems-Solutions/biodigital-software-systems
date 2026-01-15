import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowLeftIcon,
    PlusIcon,
    CalendarDaysIcon,
    ClockIcon,
    UserIcon,
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
}

interface User {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
}

interface Shift {
    id: number;
    uuid: string;
    date: string;
    type: string;
    start_time: string;
    end_time: string;
    title: string | null;
    user: User | null;
    status: string;
}

interface Props {
    department: Department;
    schedule: Schedule;
    shifts: Shift[];
}

export default function ShiftsIndex({ department, schedule, shifts }: Props) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
        });
    };

    const getStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            draft: 'bg-gray-100 text-gray-800',
            published: 'bg-blue-100 text-blue-800',
            confirmed: 'bg-green-100 text-green-800',
            in_progress: 'bg-yellow-100 text-yellow-800',
            completed: 'bg-purple-100 text-purple-800',
            cancelled: 'bg-red-100 text-red-800',
        };
        return <Badge className={colors[status] || 'bg-gray-100'}>{status}</Badge>;
    };

    const getTypeColor = (type: string) => {
        const colors: Record<string, string> = {
            morning: 'border-l-yellow-500',
            afternoon: 'border-l-orange-500',
            evening: 'border-l-purple-500',
            night: 'border-l-indigo-500',
            full_day: 'border-l-blue-500',
        };
        return colors[type] || 'border-l-gray-500';
    };

    // Group shifts by date
    const shiftsByDate = shifts.reduce((acc, shift) => {
        if (!acc[shift.date]) acc[shift.date] = [];
        acc[shift.date].push(shift);
        return acc;
    }, {} as Record<string, Shift[]>);

    return (
        <DashboardLayout>
            <Head title={`Shifts - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href={`/departments/${department.uuid}/schedule?week=${schedule.week_start}`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour au planning
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Shifts de la semaine
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {department.name}
                            </p>
                        </div>
                        <Link href={`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/create`}>
                            <Button>
                                <PlusIcon className="h-4 w-4 mr-2" />
                                Nouveau shift
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Shifts by Date */}
                {Object.entries(shiftsByDate).map(([date, dateShifts]) => (
                    <Card key={date} className="mb-4">
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <CalendarDaysIcon className="h-5 w-5" />
                                {formatDate(date)}
                                <Badge variant="outline">{dateShifts.length} shift(s)</Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {dateShifts.map((shift) => (
                                <Link
                                    key={shift.uuid}
                                    href={`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}`}
                                >
                                    <div className={`p-4 border-l-4 ${getTypeColor(shift.type)} bg-gray-50 dark:bg-gray-800 rounded-r hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors`}>
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-4">
                                                <div className="flex items-center gap-1 text-gray-600 dark:text-gray-300">
                                                    <ClockIcon className="h-4 w-4" />
                                                    {shift.start_time} - {shift.end_time}
                                                </div>
                                                {shift.title && (
                                                    <span className="font-medium">{shift.title}</span>
                                                )}
                                                {getStatusBadge(shift.status)}
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {shift.user ? (
                                                    <span className="flex items-center gap-1 text-sm">
                                                        <UserIcon className="h-4 w-4" />
                                                        {shift.user.full_name}
                                                    </span>
                                                ) : (
                                                    <Badge variant="outline" className="text-orange-600">
                                                        Non assigne
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                ))}

                {shifts.length === 0 && (
                    <Card>
                        <CardContent className="text-center py-12">
                            <CalendarDaysIcon className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Aucun shift
                            </h3>
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Commencez par creer des shifts pour cette semaine.
                            </p>
                            <Link href={`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/create`}>
                                <Button>
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Creer un shift
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                )}
            </div>
        </DashboardLayout>
    );
}
