import React, { useState, useRef, useEffect, useCallback } from 'react';
import { format, startOfWeek, addDays, isSameDay, startOfDay, addWeeks, subWeeks } from 'date-fns';
import { fr } from 'date-fns/locale';
import { router } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon, XMarkIcon } from '@heroicons/react/24/outline';
import type { Shift, DepartmentMember } from '@/Types/scheduling';

interface WeekAssignment {
    date: string;
    start_time: string;
    shift_id: number;
    shift_uuid: string;
    users: Array<{ id: number; name: string }>;
}

interface Props {
    shift: Shift;
    members?: DepartmentMember[];
    weekAssignments?: WeekAssignment[];
    departmentUuid: string;
    scheduleUuid: string;
    isEditable?: boolean;
}

const HOUR_HEIGHT = 64;

const USER_COLORS = [
    '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4',
    '#ec4899', '#f97316', '#14b8a6', '#a855f7', '#84cc16', '#e11d48',
];

function timeToMinutes(time: string): number {
    const [h, m] = time.split(':').map(Number);
    return h * 60 + (m || 0);
}

function cellKey(dateStr: string, hour: number): string {
    return `${dateStr}|${String(hour).padStart(2, '0')}`;
}

export default function ShiftWeekCalendar({
    shift,
    members = [],
    weekAssignments = [],
    departmentUuid,
    scheduleUuid,
    isEditable = false,
}: Props) {
    const shiftDate = new Date(shift.date);
    const today = startOfDay(new Date());
    const scrollRef = useRef<HTMLDivElement>(null);

    const [currentDate, setCurrentDate] = useState<Date>(shiftDate);
    const [openCellKey, setOpenCellKey] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const weekStart = startOfWeek(currentDate, { locale: fr, weekStartsOn: 1 });
    const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));

    const visibleHours = Array.from({ length: 24 }, (_, i) => i);

    const startMinutes = timeToMinutes(shift.start_time);
    const slotTopPx = (startMinutes / 60) * HOUR_HEIGHT;

    // Color map
    const userColorMap = new Map<number, string>();
    let colorIdx = 0;
    for (const assignment of weekAssignments) {
        for (const user of assignment.users) {
            if (!userColorMap.has(user.id)) {
                userColorMap.set(user.id, USER_COLORS[colorIdx % USER_COLORS.length]);
                colorIdx++;
            }
        }
    }

    // Index assignments by cell
    const assignmentsByCell = new Map<string, WeekAssignment>();
    for (const a of weekAssignments) {
        const hour = Math.floor(timeToMinutes(a.start_time) / 60);
        assignmentsByCell.set(cellKey(a.date, hour), a);
    }

    const getAvailableMembers = (dateStr: string, hour: number): DepartmentMember[] => {
        const assignment = assignmentsByCell.get(cellKey(dateStr, hour));
        const assignedIds = new Set(assignment?.users.map((u) => u.id) || []);
        return members.filter((m) => m.id !== undefined && !assignedIds.has(m.id!));
    };

    useEffect(() => {
        if (scrollRef.current) {
            const scrollTo = Math.max(0, slotTopPx - HOUR_HEIGHT);
            scrollRef.current.scrollTop = scrollTo;
        }
    }, []);

    const assignUser = useCallback((userId: number, date: Date, hour: number) => {
        if (isSubmitting) return;
        setIsSubmitting(true);

        const dateStr = format(date, 'yyyy-MM-dd');
        const startTime = `${String(hour).padStart(2, '0')}:00`;
        // For hour 23, end time wraps to 00:00
        const endHour = (hour + 1) % 24;
        const endTime = `${String(endHour).padStart(2, '0')}:00`;

        router.post(
            `/departments/${departmentUuid}/schedule/${scheduleUuid}/shifts`,
            {
                creation_mode: 'single',
                date: dateStr,
                start_time: startTime,
                end_time: endTime,
                type: shift.type,
                title: '',
                user_ids: [userId],
                break_duration: 0,
                is_overtime: false,
                requires_approval: false,
                _from_calendar: true,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setOpenCellKey(null);
                    setIsSubmitting(false);
                },
            },
        );
    }, [isSubmitting, departmentUuid, scheduleUuid, shift.type]);

    // Unique users for legend
    const uniqueUsers = weekAssignments
        .flatMap((a) => a.users)
        .reduce<Array<{ id: number; name: string }>>((acc, u) => {
            if (!acc.find((x) => x.id === u.id)) acc.push(u);
            return acc;
        }, []);

    return (
        <div className="flex flex-col overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            {/* Header */}
            <div className="p-4 border-b border-gray-200 dark:border-gray-700">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <button onClick={() => setCurrentDate(subWeeks(currentDate, 1))} className="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                            <ChevronLeftIcon className="h-5 w-5" />
                        </button>
                        <button onClick={() => setCurrentDate(addWeeks(currentDate, 1))} className="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                            <ChevronRightIcon className="h-5 w-5" />
                        </button>
                    </div>
                    <h2 className="text-sm font-normal text-gray-900 dark:text-white capitalize">
                        {format(currentDate, 'MMMM yyyy', { locale: fr })}
                    </h2>
                </div>
            </div>

            {/* Legend */}
            {uniqueUsers.length > 0 && (
                <div className="px-4 py-2 border-b border-gray-200 dark:border-gray-700 flex flex-wrap gap-3">
                    {uniqueUsers.map((user) => (
                        <div key={user.id} className="flex items-center gap-1.5">
                            <div className="w-3 h-3 rounded-full flex-shrink-0" style={{ backgroundColor: userColorMap.get(user.id) }} />
                            <span className="text-xs text-gray-600 dark:text-gray-400">{user.name}</span>
                        </div>
                    ))}
                </div>
            )}

            {/* Day headers (fixed) */}
            <div className="flex border-b border-gray-200 dark:border-gray-700">
                <div className="w-20 flex-shrink-0 h-12 border-r border-gray-200 dark:border-gray-700" />
                {weekDays.map((day, dayIndex) => {
                    const isToday = isSameDay(day, today);
                    return (
                        <div
                            key={dayIndex}
                            className={`flex-1 h-12 border-r border-gray-200 dark:border-gray-700 last:border-r-0 flex flex-col items-center justify-center ${isToday ? 'bg-icc-blue/10 dark:bg-icc-blue/20' : ''}`}
                        >
                            <div className="text-xs text-gray-600 dark:text-gray-400 uppercase">
                                {format(day, 'EEE', { locale: fr })}
                            </div>
                            <div className={`text-lg font-semibold ${isToday ? 'text-white w-8 h-8 rounded-full bg-icc-blue flex items-center justify-center' : 'text-gray-900 dark:text-white'}`}>
                                {format(day, 'd')}
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Grid (scrollable) */}
            <div className="flex-1 overflow-auto" ref={scrollRef} style={{ maxHeight: '432px' }}>
                <div className="flex">
                    {/* Time column */}
                    <div className="w-20 flex-shrink-0 border-r border-gray-200 dark:border-gray-700">
                        {visibleHours.map((hour) => (
                            <div
                                key={hour}
                                className="border-b border-gray-200 dark:border-gray-700 pr-2 text-right text-xs text-gray-500 dark:text-gray-400 flex items-start pt-1"
                                style={{ height: `${HOUR_HEIGHT}px` }}
                            >
                                <span className="ml-auto">{format(new Date(2026, 0, 1, hour, 0, 0), 'HH:mm')}</span>
                            </div>
                        ))}
                    </div>

                    {/* Day columns */}
                    {weekDays.map((day, dayIndex) => {
                        const isToday = isSameDay(day, today);
                        const dateStr = format(day, 'yyyy-MM-dd');

                        return (
                            <div key={dayIndex} className="flex-1 border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                                {/* Cells */}
                                {visibleHours.map((hour) => {
                                    const ck = cellKey(dateStr, hour);
                                    const cellUsers = assignmentsByCell.get(ck)?.users || [];
                                    const isOpen = openCellKey === ck;
                                    const available = getAvailableMembers(dateStr, hour);

                                    return (
                                        <div
                                            key={hour}
                                            className={`border-b border-gray-100 dark:border-gray-700/50 relative ${isEditable ? 'cursor-pointer hover:bg-icc-blue/5 dark:hover:bg-icc-blue/10 transition-colors' : ''}`}
                                            style={{ height: `${HOUR_HEIGHT}px` }}
                                            onDoubleClick={(e) => {
                                                e.preventDefault();
                                                if (isEditable) setOpenCellKey(isOpen ? null : ck);
                                            }}
                                        >
                                            {/* Assigned users */}
                                            {cellUsers.length > 0 && (
                                                <div className="flex flex-wrap gap-0.5 p-1">
                                                    {cellUsers.map((user) => {
                                                        const color = userColorMap.get(user.id) || '#6b7280';
                                                        return (
                                                            <div
                                                                key={user.id}
                                                                className="flex items-center gap-1 rounded-full px-1.5 py-0.5 max-w-full"
                                                                style={{ backgroundColor: `${color}18`, border: `1px solid ${color}40` }}
                                                                title={user.name}
                                                            >
                                                                <div className="w-2 h-2 rounded-full flex-shrink-0" style={{ backgroundColor: color }} />
                                                                <span className="text-[10px] font-medium truncate leading-none" style={{ color }}>{user.name}</span>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            )}

                                            {/* Popover */}
                                            {isOpen && (
                                                <div
                                                    className="absolute left-0 top-0 z-30 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700"
                                                    onMouseDown={(e) => e.stopPropagation()}
                                                >
                                                    <div className="flex items-center justify-between px-2.5 pt-2 pb-1">
                                                        <span className="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                            {format(day, 'EEE d', { locale: fr })} — {String(hour).padStart(2, '0')}:00
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onMouseDown={(e) => {
                                                                e.stopPropagation();
                                                                e.preventDefault();
                                                                setOpenCellKey(null);
                                                            }}
                                                            className="p-0.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                                                        >
                                                            <XMarkIcon className="h-3.5 w-3.5 text-gray-400" />
                                                        </button>
                                                    </div>
                                                    {available.length > 0 ? (
                                                        <ul className="max-h-40 overflow-y-auto py-1">
                                                            {available.map((m) => (
                                                                <li key={m.id}>
                                                                    <button
                                                                        type="button"
                                                                        disabled={isSubmitting}
                                                                        className="w-full text-left px-2.5 py-1.5 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 cursor-pointer select-none"
                                                                        onMouseDown={(e) => {
                                                                            e.stopPropagation();
                                                                            e.preventDefault();
                                                                            if (m.id !== undefined) {
                                                                                assignUser(m.id, day, hour);
                                                                            }
                                                                        }}
                                                                    >
                                                                        {m.name || m.email}
                                                                    </button>
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    ) : (
                                                        <p className="text-xs text-gray-400 italic px-2.5 py-2">Tous les membres sont assignés</p>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
