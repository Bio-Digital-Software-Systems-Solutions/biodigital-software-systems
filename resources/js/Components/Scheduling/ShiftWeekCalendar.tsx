import React, { useState, useRef, useEffect, useCallback } from 'react';
import { format, startOfWeek, addDays, isSameDay, startOfDay, addWeeks, subWeeks } from 'date-fns';
import { fr } from 'date-fns/locale';
import { router } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon, XMarkIcon, PencilIcon } from '@heroicons/react/24/outline';
import { Switch } from '@/Components/ui/switch';
import type { Shift, DepartmentMember } from '@/Types/scheduling';

interface WeekShift {
    id: number;
    uuid: string;
    date: string;
    start_time: string;
    end_time: string;
    type: string;
    users_by_slot: Record<string, Array<{ id: number; name: string }>>;
}

interface OtherWeekShift extends WeekShift {
    title?: string;
}

interface CellData {
    bgColor: string;
    shiftUuid: string;
    timeSlot: string;
    users: Array<{ id: number; name: string }>;
    isOther?: boolean;
}

interface Props {
    shift: Shift;
    members?: DepartmentMember[];
    weekShifts?: WeekShift[];
    otherWeekShifts?: OtherWeekShift[];
    departmentUuid: string;
    scheduleUuid: string;
    isEditable?: boolean;
}

const HOUR_HEIGHT = 64;

const USER_COLORS = [
    '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4',
    '#ec4899', '#f97316', '#14b8a6', '#a855f7', '#84cc16', '#e11d48',
];

const SHIFT_BG_COLORS = [
    '#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6',
    '#06b6d4', '#f97316', '#14b8a6', '#ec4899', '#84cc16',
];

function timeToMinutes(time: string): number {
    const [h, m] = time.split(':').map(Number);
    return h * 60 + (m || 0);
}

function cellKey(dateStr: string, hour: number): string {
    return `${dateStr}|${String(hour).padStart(2, '0')}`;
}

function hourToTimeSlot(hour: number): string {
    return `${String(hour).padStart(2, '0')}:00`;
}

export default function ShiftWeekCalendar({
    shift,
    members = [],
    weekShifts = [],
    otherWeekShifts = [],
    departmentUuid,
    scheduleUuid,
    isEditable = false,
}: Props) {
    const shiftDate = new Date(shift.date);
    const today = startOfDay(new Date());
    const scrollRef = useRef<HTMLDivElement>(null);

    const [currentDate, setCurrentDate] = useState<Date>(shiftDate);
    const [editMode, setEditMode] = useState(false);
    const [openCellKey, setOpenCellKey] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const weekStart = startOfWeek(currentDate, { locale: fr, weekStartsOn: 1 });
    const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));

    const visibleHours = Array.from({ length: 24 }, (_, i) => i);

    const startMinutes = timeToMinutes(shift.start_time);
    const slotTopPx = (startMinutes / 60) * HOUR_HEIGHT;

    // Build cell → CellData map from weekShifts (per-slot users)
    const cellDataMap = new Map<string, CellData>();

    const expandShiftCells = (ws: WeekShift, color: string, isOther = false): void => {
        const sMin = timeToMinutes(ws.start_time);
        const eMin = timeToMinutes(ws.end_time);
        const sHour = Math.floor(sMin / 60);
        const eHour = Math.floor(eMin / 60);
        const overnight = eMin <= sMin;

        const setCell = (dateStr: string, h: number) => {
            const ck = cellKey(dateStr, h);
            // Don't overwrite main shift cells with "other" shift data
            if (isOther && cellDataMap.has(ck)) return;
            const timeSlot = hourToTimeSlot(h);
            const slotUsers = ws.users_by_slot[timeSlot] || [];
            cellDataMap.set(ck, {
                bgColor: color,
                shiftUuid: ws.uuid,
                timeSlot,
                users: slotUsers,
                isOther,
            });
        };

        if (overnight) {
            for (let h = sHour; h < 24; h++) setCell(ws.date, h);
            const nextDay = addDays(new Date(ws.date), 1);
            const nextDayStr = format(nextDay, 'yyyy-MM-dd');
            for (let h = 0; h < eHour; h++) setCell(nextDayStr, h);
        } else {
            for (let h = sHour; h < eHour; h++) setCell(ws.date, h);
        }
    };

    // Build the list: main shift first, then series siblings
    const allShifts: WeekShift[] = [];
    const mainShiftDate = format(shiftDate, 'yyyy-MM-dd');

    const mainWs = weekShifts.find(
        (ws) => ws.date === mainShiftDate && ws.start_time === shift.start_time && ws.end_time === shift.end_time,
    );
    if (mainWs) {
        allShifts.push(mainWs);
    } else {
        allShifts.push({
            id: 0,
            uuid: shift.uuid,
            date: mainShiftDate,
            start_time: shift.start_time,
            end_time: shift.end_time,
            type: shift.type,
            users_by_slot: {},
        });
    }

    for (const ws of weekShifts) {
        if (!allShifts.some((s) => s.id === ws.id)) {
            allShifts.push(ws);
        }
    }

    allShifts.forEach((ws, idx) => {
        expandShiftCells(ws, SHIFT_BG_COLORS[idx % SHIFT_BG_COLORS.length]);
    });

    // Expand other department shifts (grey, non-editable)
    for (const ws of otherWeekShifts) {
        expandShiftCells(ws, '#4b5563', true);
    }

    // Add cells for users assigned outside shift time ranges (users_by_slot may contain
    // time slots that fall outside start_time–end_time). These get a transparent background.
    // For overnight shifts, time slots before end_time belong to the next calendar day.
    for (const ws of [...allShifts, ...otherWeekShifts]) {
        const isOther = otherWeekShifts.includes(ws as OtherWeekShift);
        const wsStartMin = timeToMinutes(ws.start_time);
        const wsEndMin = timeToMinutes(ws.end_time);
        const isOvernight = wsEndMin <= wsStartMin;

        for (const [timeSlot, slotUsers] of Object.entries(ws.users_by_slot)) {
            if (slotUsers.length === 0) continue;
            const hour = parseInt(timeSlot.split(':')[0], 10);
            const slotMin = hour * 60;

            // For overnight shifts, slots before end_time belong to the next day
            let dateStr = ws.date;
            if (isOvernight && slotMin < wsEndMin) {
                dateStr = format(addDays(new Date(ws.date), 1), 'yyyy-MM-dd');
            }

            const ck = cellKey(dateStr, hour);
            if (!cellDataMap.has(ck)) {
                cellDataMap.set(ck, {
                    bgColor: 'transparent',
                    shiftUuid: ws.uuid,
                    timeSlot,
                    users: slotUsers,
                    isOther,
                });
            }
        }
    }

    // Map date → shift UUID so clicking outside shift hours uses the correct day's shift
    const dateToShiftUuid = new Map<string, string>();
    for (const ws of allShifts) {
        if (!dateToShiftUuid.has(ws.date)) {
            dateToShiftUuid.set(ws.date, ws.uuid);
        }
    }

    // User color map (from all slots' users, including other shifts)
    const userColorMap = new Map<number, string>();
    let colorIdx = 0;
    const allShiftSources = [...allShifts, ...otherWeekShifts];
    for (const ws of allShiftSources) {
        for (const slotUsers of Object.values(ws.users_by_slot)) {
            for (const user of slotUsers) {
                if (!userColorMap.has(user.id)) {
                    userColorMap.set(user.id, USER_COLORS[colorIdx % USER_COLORS.length]);
                    colorIdx++;
                }
            }
        }
    }

    // Get available members for a cell (exclude already-assigned users in this cell)
    const getAvailableMembers = (cellData: CellData | undefined): DepartmentMember[] => {
        const assignedIds = new Set(cellData?.users.map((u) => u.id) || []);
        return members.filter((m) => m.id !== undefined && !assignedIds.has(m.id!));
    };

    useEffect(() => {
        if (scrollRef.current) {
            const scrollTo = Math.max(0, slotTopPx - HOUR_HEIGHT);
            scrollRef.current.scrollTop = scrollTo;
        }
    }, []);

    // Add user to a specific time slot
    const assignUser = useCallback((userId: number, shiftUuid: string, timeSlot: string) => {
        if (isSubmitting) return;
        setIsSubmitting(true);

        router.post(
            `/departments/${departmentUuid}/schedule/${scheduleUuid}/shifts/${shiftUuid}/add-user`,
            { user_id: userId, time_slot: timeSlot },
            {
                preserveScroll: true,
                onFinish: () => {
                    setOpenCellKey(null);
                    setIsSubmitting(false);
                },
            },
        );
    }, [isSubmitting, departmentUuid, scheduleUuid]);

    // Remove user from a specific time slot
    const removeUser = useCallback((userId: number, shiftUuid: string, timeSlot: string) => {
        if (isSubmitting) return;
        setIsSubmitting(true);

        router.delete(
            `/departments/${departmentUuid}/schedule/${scheduleUuid}/shifts/${shiftUuid}/remove-user`,
            {
                data: { user_id: userId, time_slot: timeSlot },
                preserveScroll: true,
                onFinish: () => {
                    setIsSubmitting(false);
                },
            },
        );
    }, [isSubmitting, departmentUuid, scheduleUuid]);

    // Unique users for legend (including other shifts)
    const uniqueUsers = allShiftSources
        .flatMap((ws) => Object.values(ws.users_by_slot).flat())
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
                    <div className="flex items-center gap-4">
                        {isEditable && (
                            <div className="flex items-center gap-2">
                                <PencilIcon className={`h-4 w-4 ${editMode ? 'text-icc-blue' : 'text-gray-400'}`} />
                                <Switch
                                    checked={editMode}
                                    onCheckedChange={(checked) => {
                                        setEditMode(checked);
                                        if (!checked) setOpenCellKey(null);
                                    }}
                                />
                                <span className={`text-xs font-medium ${editMode ? 'text-icc-blue' : 'text-gray-400'}`}>
                                    Édition
                                </span>
                            </div>
                        )}
                        <h2 className="text-sm font-normal text-gray-900 dark:text-white capitalize">
                            {format(currentDate, 'MMMM yyyy', { locale: fr })}
                        </h2>
                    </div>
                </div>
            </div>

            {/* Legend removed — user names with colors are shown directly in cells */}

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
                        const dateStr = format(day, 'yyyy-MM-dd');

                        return (
                            <div key={dayIndex} className="flex-1 border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                                {visibleHours.map((hour) => {
                                    const ck = cellKey(dateStr, hour);
                                    const cellData = cellDataMap.get(ck);
                                    const cellUsers = cellData?.users || [];
                                    const isOpen = openCellKey === ck;
                                    const isInShiftRange = !!cellData;
                                    const isOtherShift = cellData?.isOther === true;
                                    // In edit mode, cells are editable if a shift exists for that day
                                    const dayShiftUuid = dateToShiftUuid.get(dateStr);
                                    const isCellEditable = editMode && !!(cellData?.shiftUuid || dayShiftUuid);
                                    // Use the cell's shift, or fall back to the correct shift for this day
                                    const effectiveShiftUuid = cellData?.shiftUuid || dayShiftUuid || shift.uuid;
                                    const effectiveTimeSlot = cellData?.timeSlot || hourToTimeSlot(hour);
                                    const available = isCellEditable ? getAvailableMembers(cellData) : [];

                                    return (
                                        <div
                                            key={hour}
                                            className={`border-b border-gray-100 dark:border-gray-700/50 relative ${isCellEditable ? 'cursor-pointer hover:bg-icc-blue/5 dark:hover:bg-icc-blue/10 transition-colors' : ''}`}
                                            style={{
                                                height: `${HOUR_HEIGHT}px`,
                                                ...(cellData
                                                    ? { backgroundColor: isOtherShift ? '#9ca3af0d' : `${cellData.bgColor}18` }
                                                    : {}),
                                            }}
                                            onClick={(e) => {
                                                if (isCellEditable) {
                                                    e.preventDefault();
                                                    setOpenCellKey(isOpen ? null : ck);
                                                }
                                            }}
                                        >
                                            {/* Assigned users for this specific slot */}
                                            {cellUsers.length > 0 && (
                                                <div className="flex flex-wrap gap-0.5 p-1">
                                                    {cellUsers.map((user) => {
                                                        const color = userColorMap.get(user.id) || '#6b7280';
                                                        return (
                                                            <div
                                                                key={user.id}
                                                                className={`flex items-center gap-1 rounded-full px-1.5 py-0.5 max-w-full ${isOtherShift ? 'opacity-75' : ''}`}
                                                                style={{ backgroundColor: `${color}18`, border: `1px solid ${color}40` }}
                                                                title={user.name}
                                                            >
                                                                <div className="w-2 h-2 rounded-full flex-shrink-0" style={{ backgroundColor: color }} />
                                                                <span className="text-[10px] font-medium truncate leading-none" style={{ color }}>{user.name}</span>
                                                                {isCellEditable && (
                                                                    <button
                                                                        type="button"
                                                                        disabled={isSubmitting}
                                                                        className="ml-0.5 p-0 rounded-full hover:bg-red-100 dark:hover:bg-red-900/30 disabled:opacity-50"
                                                                        title={`Retirer ${user.name}`}
                                                                        onMouseDown={(e) => {
                                                                            e.stopPropagation();
                                                                            e.preventDefault();
                                                                        }}
                                                                        onClick={(e) => {
                                                                            e.stopPropagation();
                                                                            e.preventDefault();
                                                                            removeUser(user.id, effectiveShiftUuid, effectiveTimeSlot);
                                                                        }}
                                                                    >
                                                                        <XMarkIcon className="h-3 w-3 text-gray-400 hover:text-red-500" />
                                                                    </button>
                                                                )}
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            )}

                                            {/* Popover */}
                                            {isOpen && isCellEditable && (
                                                <div
                                                    className="absolute left-0 top-0 z-30 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700"
                                                    onClick={(e) => e.stopPropagation()}
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
                                                                                assignUser(m.id, effectiveShiftUuid, effectiveTimeSlot);
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
