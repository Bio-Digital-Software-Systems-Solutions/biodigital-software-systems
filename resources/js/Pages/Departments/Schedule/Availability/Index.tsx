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
    CheckCircleIcon,
    XCircleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/react/24/outline';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
}

interface DateAvailability {
    status: string;
    slots?: { start: string; end: string }[];
    is_absent?: boolean;
    absence_type?: string;
}

interface AvailabilityEntry {
    employee: Employee;
    dates: Record<string, DateAvailability>;
}

interface AvailabilityStatus {
    value: string;
    label: string;
    color: string;
}

interface Props {
    department: Department;
    availabilityMatrix?: AvailabilityEntry[];
    weekStart: string;
    weekEnd: string;
    prevWeek?: string;
    nextWeek?: string;
    availabilityStatuses?: AvailabilityStatus[];
}

export default function AvailabilityIndex({
    department,
    availabilityMatrix = [],
    weekStart,
    weekEnd,
    prevWeek,
    nextWeek,
}: Props) {
    const formatWeekDisplay = () => {
        const start = new Date(weekStart);
        const end = new Date(weekEnd);
        return `${start.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })} - ${end.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}`;
    };

    const getDatesOfWeek = () => {
        const dates: string[] = [];
        const current = new Date(weekStart);
        const end = new Date(weekEnd);
        while (current <= end) {
            dates.push(current.toISOString().split('T')[0]);
            current.setDate(current.getDate() + 1);
        }
        return dates;
    };

    const weekDates = getDatesOfWeek();

    const formatDayLabel = (dateStr: string) => {
        const date = new Date(dateStr);
        return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
    };

    const getAvailabilityCount = (dateStr: string) => {
        return availabilityMatrix.filter(entry => {
            const dateAvail = entry.dates?.[dateStr];
            return dateAvail && dateAvail.status === 'available' && !dateAvail.is_absent;
        }).length;
    };

    const navigateToWeek = (weekDate: string) => {
        router.get(`/departments/${department.uuid}/availability`, { week: weekDate }, {
            preserveState: true,
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Disponibilites - ${department.name}`} />

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
                                Disponibilites de l'equipe
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                <CalendarDaysIcon className="h-4 w-4 inline mr-1" />
                                Semaine du {formatWeekDisplay()}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            {prevWeek && (
                                <Button variant="outline" size="sm" onClick={() => navigateToWeek(prevWeek)}>
                                    <ChevronLeftIcon className="h-4 w-4" />
                                </Button>
                            )}
                            {nextWeek && (
                                <Button variant="outline" size="sm" onClick={() => navigateToWeek(nextWeek)}>
                                    <ChevronRightIcon className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Summary */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-lg">Resume par jour</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-7 gap-2">
                            {weekDates.map((dateStr) => {
                                const count = getAvailabilityCount(dateStr);
                                const total = availabilityMatrix.length;
                                const percentage = total > 0 ? (count / total) * 100 : 0;
                                return (
                                    <div key={dateStr} className="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            {formatDayLabel(dateStr)}
                                        </div>
                                        <div className={`text-2xl font-bold ${percentage >= 50 ? 'text-green-600' : 'text-orange-600'}`}>
                                            {count}
                                        </div>
                                        <div className="text-xs text-gray-400">
                                            / {total}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                {/* Users Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Disponibilites par membre</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b dark:border-gray-700">
                                        <th className="text-left p-4 font-medium">Membre</th>
                                        {weekDates.map((dateStr) => (
                                            <th key={dateStr} className="text-center p-4 font-medium w-24">
                                                {formatDayLabel(dateStr)}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {availabilityMatrix.map((entry) => (
                                        <tr key={entry.employee.id} className="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td className="p-4">
                                                <div className="flex items-center gap-2">
                                                    <UserIcon className="h-5 w-5 text-gray-400" />
                                                    <span className="font-medium">{entry.employee.full_name}</span>
                                                </div>
                                            </td>
                                            {weekDates.map((dateStr) => {
                                                const dateAvail = entry.dates?.[dateStr];
                                                const isAvailable = dateAvail?.status === 'available' && !dateAvail?.is_absent;
                                                const isAbsent = dateAvail?.is_absent;
                                                return (
                                                    <td key={dateStr} className="text-center p-4">
                                                        {isAbsent ? (
                                                            <div className="flex flex-col items-center">
                                                                <Badge variant="outline" className="bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                                    Absent
                                                                </Badge>
                                                                {dateAvail?.absence_type && (
                                                                    <span className="text-xs text-gray-500 mt-1">
                                                                        {dateAvail.absence_type}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ) : isAvailable ? (
                                                            <div className="flex flex-col items-center">
                                                                <CheckCircleIcon className="h-6 w-6 text-green-500" />
                                                                {dateAvail?.slots && dateAvail.slots.length > 0 && (
                                                                    <div className="text-xs text-gray-500 mt-1">
                                                                        {dateAvail.slots.map((slot, i) => (
                                                                            <div key={i}>{slot.start}-{slot.end}</div>
                                                                        ))}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <XCircleIcon className="h-6 w-6 text-red-400 mx-auto" />
                                                        )}
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {availabilityMatrix.length === 0 && (
                            <div className="text-center py-12 text-gray-500">
                                <UserIcon className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                                <p>Aucun membre n'a renseigne ses disponibilites</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
