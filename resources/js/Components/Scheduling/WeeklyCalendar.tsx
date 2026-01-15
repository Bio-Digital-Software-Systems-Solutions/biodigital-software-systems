import React from 'react';
import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { PlusIcon } from '@heroicons/react/24/outline';
import ShiftCard from './ShiftCard';
import type { Shift } from '@/Types/scheduling';

interface Props {
    weekStart: string;
    shiftsByDay: Record<string, Shift[]>;
    departmentUuid: string;
    scheduleUuid: string;
    isEditable: boolean;
}

const DAYS_OF_WEEK = [
    { key: 'monday', label: 'Lundi', short: 'Lun' },
    { key: 'tuesday', label: 'Mardi', short: 'Mar' },
    { key: 'wednesday', label: 'Mercredi', short: 'Mer' },
    { key: 'thursday', label: 'Jeudi', short: 'Jeu' },
    { key: 'friday', label: 'Vendredi', short: 'Ven' },
    { key: 'saturday', label: 'Samedi', short: 'Sam' },
    { key: 'sunday', label: 'Dimanche', short: 'Dim' },
];

export default function WeeklyCalendar({
    weekStart,
    shiftsByDay,
    departmentUuid,
    scheduleUuid,
    isEditable,
}: Props) {
    const getDateForDay = (dayIndex: number) => {
        const date = new Date(weekStart);
        date.setDate(date.getDate() + dayIndex);
        return date;
    };

    const formatDateKey = (date: Date) => {
        return date.toISOString().split('T')[0];
    };

    const formatDayHeader = (date: Date) => {
        return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
    };

    const isToday = (date: Date) => {
        const today = new Date();
        return date.toDateString() === today.toDateString();
    };

    const getShiftsForDay = (dayIndex: number) => {
        const date = getDateForDay(dayIndex);
        const dateKey = formatDateKey(date);
        return shiftsByDay[dateKey] || [];
    };

    const calculateDayHours = (shifts: Shift[]) => {
        return shifts.reduce((total, shift) => total + (shift.duration_hours || 0), 0);
    };

    return (
        <div className="grid grid-cols-7 gap-2">
            {DAYS_OF_WEEK.map((day, index) => {
                const date = getDateForDay(index);
                const dateKey = formatDateKey(date);
                const shifts = getShiftsForDay(index);
                const totalHours = calculateDayHours(shifts);
                const dayIsToday = isToday(date);

                return (
                    <Card
                        key={day.key}
                        className={`min-h-[300px] ${dayIsToday ? 'ring-2 ring-blue-500 ring-offset-2' : ''}`}
                    >
                        <CardHeader className="py-3 px-3">
                            <div className="flex flex-col items-center">
                                <CardTitle className="text-sm font-medium text-gray-500">
                                    {day.label}
                                </CardTitle>
                                <span className={`text-lg font-bold ${dayIsToday ? 'text-blue-600' : ''}`}>
                                    {formatDayHeader(date)}
                                </span>
                                {shifts.length > 0 && (
                                    <div className="flex items-center gap-2 mt-1">
                                        <Badge variant="secondary" className="text-xs">
                                            {shifts.length} shift{shifts.length > 1 ? 's' : ''}
                                        </Badge>
                                        <span className="text-xs text-gray-400">{totalHours}h</span>
                                    </div>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="px-2 pb-2 space-y-2">
                            {shifts.length > 0 ? (
                                shifts.map((shift) => (
                                    <ShiftCard
                                        key={shift.uuid}
                                        shift={shift}
                                        departmentUuid={departmentUuid}
                                        scheduleUuid={scheduleUuid}
                                        compact
                                    />
                                ))
                            ) : (
                                <div className="flex flex-col items-center justify-center py-8 text-gray-400">
                                    <span className="text-sm">Aucun shift</span>
                                </div>
                            )}

                            {isEditable && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="w-full mt-2 border-dashed border-2 border-gray-200 dark:border-gray-700 hover:border-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                    asChild
                                >
                                    <Link
                                        href={`/departments/${departmentUuid}/schedule/${scheduleUuid}/shifts/create?date=${dateKey}`}
                                    >
                                        <PlusIcon className="h-4 w-4 mr-1" />
                                        Ajouter
                                    </Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
