import React, { useState, useRef, useEffect, useCallback, useMemo } from 'react';
import { format, addDays, isSameDay, startOfDay } from 'date-fns';
import { fr } from 'date-fns/locale';
import { router } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon, XMarkIcon, PencilIcon } from '@heroicons/react/24/outline';
import { Switch } from '@/Components/ui/switch';
import type { Shift, DepartmentMember } from '@/Types/scheduling';

interface CellData {
    bgColor: string;
    shiftUuid: string;
    timeSlot: string;
    users: Array<{ id: number; name: string }>;
}

interface Props {
    shifts: Shift[];
    members: DepartmentMember[];
    weekStart: string;
    departmentUuid: string;
    scheduleUuid: string;
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

export default function ScheduleWeekCalendar({
    shifts,
    members,
    weekStart,
    departmentUuid,
    scheduleUuid,
}: Props) {
    const today = startOfDay(new Date());
    const scrollRef = useRef<HTMLDivElement>(null);

    const [editMode, setEditMode] = useState(false);
    const [openCellKey, setOpenCellKey] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const weekStartDate = new Date(weekStart);
    const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStartDate, i));
    const visibleHours = Array.from({ length: 24 }, (_, i) => i);

    // Build cell data from all shifts
    const { cellDataMap, userColorMap, uniqueUsers, dateToShiftUuid } = useMemo(() => {
        const map = new Map<string, CellData>();
        const colorMap = new Map<number, string>();
        let colorIdx = 0;
        const dateShiftMap = new Map<string, string>();

        // Sort shifts for consistent coloring
        const sortedShifts = [...shifts].sort((a, b) => {
            const dateComp = a.date.localeCompare(b.date);
            if (dateComp !== 0) return dateComp;
            return a.start_time.localeCompare(b.start_time);
        });

        sortedShifts.forEach((shift, idx) => {
            const dateStr = shift.date.split('T')[0];
            const sMin = timeToMinutes(shift.start_time);
            const eMin = timeToMinutes(shift.end_time);
            const sHour = Math.floor(sMin / 60);
            const eHour = Math.floor(eMin / 60);
            const overnight = eMin <= sMin;
            const bgColor = SHIFT_BG_COLORS[idx % SHIFT_BG_COLORS.length];

            if (!dateShiftMap.has(dateStr)) {
                dateShiftMap.set(dateStr, shift.uuid);
            }

            // Build users_by_slot from pivot data
            const usersBySlot: Record<string, Array<{ id: number; name: string }>> = {};
            if (shift.users) {
                for (const user of shift.users) {
                    const pivot = (user as unknown as { pivot?: { time_slot?: string } }).pivot;
                    const timeSlot = pivot?.time_slot;
                    if (timeSlot) {
                        if (!usersBySlot[timeSlot]) {
                            usersBySlot[timeSlot] = [];
                        }
                        const name = (user as unknown as { first_name?: string; last_name?: string; name?: string }).first_name
                            ? `${(user as unknown as { first_name: string }).first_name} ${(user as unknown as { last_name: string }).last_name}`
                            : (user as unknown as { name?: string }).name || 'Unknown';
                        usersBySlot[timeSlot].push({ id: user.id, name });

                        // Assign color to user
                        if (!colorMap.has(user.id)) {
                            colorMap.set(user.id, USER_COLORS[colorIdx % USER_COLORS.length]);
                            colorIdx++;
                        }
                    }
                }
            }

            const setCell = (d: string, h: number) => {
                const ck = cellKey(d, h);
                const timeSlot = hourToTimeSlot(h);
                const slotUsers = usersBySlot[timeSlot] || [];
                // If cell already has data from another shift, merge users
                const existing = map.get(ck);
                if (existing) {
                    const existingIds = new Set(existing.users.map(u => u.id));
                    const newUsers = slotUsers.filter(u => !existingIds.has(u.id));
                    existing.users.push(...newUsers);
                } else {
                    map.set(ck, {
                        bgColor,
                        shiftUuid: shift.uuid,
                        timeSlot,
                        users: [...slotUsers],
                    });
                }
            };

            if (overnight) {
                for (let h = sHour; h < 24; h++) setCell(dateStr, h);
                const nextDay = addDays(new Date(dateStr), 1);
                const nextDayStr = format(nextDay, 'yyyy-MM-dd');
                for (let h = 0; h < eHour; h++) setCell(nextDayStr, h);
            } else {
                for (let h = sHour; h < eHour; h++) setCell(dateStr, h);
            }

            // Add cells for users assigned outside shift time range
            for (const [timeSlot, slotUsers] of Object.entries(usersBySlot)) {
                if (slotUsers.length === 0) continue;
                const hour = parseInt(timeSlot.split(':')[0], 10);
                const slotMin = hour * 60;

                let d = dateStr;
                if (overnight && slotMin < eMin) {
                    d = format(addDays(new Date(dateStr), 1), 'yyyy-MM-dd');
                }

                const ck = cellKey(d, hour);
                if (!map.has(ck)) {
                    map.set(ck, {
                        bgColor: 'transparent',
                        shiftUuid: shift.uuid,
                        timeSlot,
                        users: [...slotUsers],
                    });
                }
            }
        });

        // Build unique users list
        const seen = new Set<number>();
        const users: Array<{ id: number; name: string }> = [];
        for (const cellData of map.values()) {
            for (const u of cellData.users) {
                if (!seen.has(u.id)) {
                    seen.add(u.id);
                    users.push(u);
                }
            }
        }

        return { cellDataMap: map, userColorMap: colorMap, uniqueUsers: users, dateToShiftUuid: dateShiftMap };
    }, [shifts]);

    // Get available members for a cell
    const getAvailableMembers = (cellData: CellData | undefined): DepartmentMember[] => {
        const assignedIds = new Set(cellData?.users.map(u => u.id) || []);
        return members.filter(m => m.id !== undefined && !assignedIds.has(m.id!));
    };

    // Scroll to ~8:00 on mount
    useEffect(() => {
        if (scrollRef.current) {
            const scrollTo = 8 * HOUR_HEIGHT;
            scrollRef.current.scrollTop = scrollTo;
        }
    }, []);

    // Close popover on click outside
    useEffect(() => {
        if (!openCellKey) return;
        const handleClick = () => setOpenCellKey(null);
        const timer = setTimeout(() => document.addEventListener('click', handleClick), 0);
        return () => {
            clearTimeout(timer);
            document.removeEventListener('click', handleClick);
        };
    }, [openCellKey]);

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

    return (
        <div className="flex flex-col overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            {/* Header */}
            <div className="p-4 border-b border-gray-200 dark:border-gray-700">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        {/* Navigation is handled by the parent page's week navigation */}
                    </div>
                    <div className="flex items-center gap-4">
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
                        <h2 className="text-sm font-normal text-gray-900 dark:text-white capitalize">
                            {format(weekStartDate, 'MMMM yyyy', { locale: fr })}
                        </h2>
                    </div>
                </div>
            </div>

            {/* Day headers */}
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

            {/* Grid */}
            <div className="flex-1 overflow-auto" ref={scrollRef} style={{ maxHeight: '768px' }}>
                <div className="flex">
                    {/* Time column */}
                    <div className="w-20 flex-shrink-0 border-r border-gray-200 dark:border-gray-700">
                        {visibleHours.map(hour => (
                            <div
                                key={hour}
                                className="border-b border-gray-200 dark:border-gray-700 pr-2 text-right text-xs text-gray-500 dark:text-gray-400 flex items-start pt-1"
                                style={{ height: `${HOUR_HEIGHT}px` }}
                            >
                                <span className="ml-auto">{String(hour).padStart(2, '0')}:00</span>
                            </div>
                        ))}
                    </div>

                    {/* Day columns */}
                    {weekDays.map((day, dayIndex) => {
                        const dateStr = format(day, 'yyyy-MM-dd');

                        return (
                            <div key={dayIndex} className="flex-1 border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                                {visibleHours.map(hour => {
                                    const ck = cellKey(dateStr, hour);
                                    const cellData = cellDataMap.get(ck);
                                    const cellUsers = cellData?.users || [];
                                    const isOpen = openCellKey === ck;
                                    const dayShiftUuid = dateToShiftUuid.get(dateStr);
                                    const hasShift = !!(cellData?.shiftUuid || dayShiftUuid);
                                    const isCellEditable = editMode && hasShift;
                                    const effectiveShiftUuid = cellData?.shiftUuid || dayShiftUuid || '';
                                    const effectiveTimeSlot = cellData?.timeSlot || hourToTimeSlot(hour);
                                    const available = isCellEditable ? getAvailableMembers(cellData) : [];

                                    return (
                                        <div
                                            key={hour}
                                            className={`border-b border-gray-100 dark:border-gray-700/50 relative ${isCellEditable ? 'cursor-pointer hover:bg-icc-blue/5 dark:hover:bg-icc-blue/10 transition-colors' : ''}`}
                                            style={{
                                                height: `${HOUR_HEIGHT}px`,
                                                ...(cellData && cellData.bgColor !== 'transparent'
                                                    ? { backgroundColor: `${cellData.bgColor}18` }
                                                    : {}),
                                            }}
                                            onDoubleClick={(e) => {
                                                if (isCellEditable) {
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    setOpenCellKey(isOpen ? null : ck);
                                                }
                                            }}
                                        >
                                            {/* Assigned users */}
                                            {cellUsers.length > 0 && (
                                                <div className="flex flex-wrap gap-0.5 p-1">
                                                    {cellUsers.map(user => {
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

                                            {/* Popover for adding members */}
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
                                                            {available.map(m => (
                                                                <li key={m.id ?? m.uuid}>
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
