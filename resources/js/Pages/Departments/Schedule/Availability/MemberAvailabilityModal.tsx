import React, { useState, useEffect, useRef } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { UserIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';

interface TimeSlot {
    start: string;
    end: string;
}

interface DayAvailability {
    status: string | null;
    is_available: boolean | null;
    is_absent: boolean;
    absence_type: string | null;
    time_slots: TimeSlot[];
}

interface RawDateAvailability {
    is_available?: boolean | null;
    status?: string | null;
    absence?: unknown;
    time_slots?: TimeSlot[];
    // Legacy fields from availabilityMatrix
    slots?: TimeSlot[];
    is_absent?: boolean;
    absence_type?: string | null;
}

interface Employee {
    id: number;
    first_name?: string;
    last_name?: string;
    full_name: string;
}

interface AvailabilityEntry {
    employee: Employee;
    dates: Record<string, RawDateAvailability>;
}

interface MemberWeekData {
    employee: { id: number; full_name: string };
    week_start: string;
    week_end: string;
    prev_week: string;
    next_week: string;
    dates: Record<string, DayAvailability>;
}

interface Props {
    entry: AvailabilityEntry | null;
    weekStart: string;
    weekEnd: string;
    prevWeek: string;
    nextWeek: string;
    departmentUuid: string;
    onClose: () => void;
}

const HOURS = Array.from({ length: 17 }, (_, i) => i + 6); // 06:00 to 22:00

export function toLocalDateKey(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function addDays(dateStr: string, days: number): string {
    const d = new Date(dateStr + 'T00:00:00');
    d.setDate(d.getDate() + days);
    return toLocalDateKey(d);
}

function normalizeDayData(raw: RawDateAvailability): DayAvailability {
    return {
        status: raw.status ?? null,
        is_available: raw.is_available !== undefined ? (raw.is_available as boolean | null) : null,
        is_absent: raw.is_absent ?? (raw.absence !== null && raw.absence !== undefined),
        absence_type: raw.absence_type ?? null,
        time_slots: raw.time_slots ?? raw.slots ?? [],
    };
}

function normalizeFromEntry(
    entry: AvailabilityEntry,
    weekStart: string,
    weekEnd: string,
    prevWeek: string,
    nextWeek: string,
): MemberWeekData {
    const dates: Record<string, DayAvailability> = {};
    for (const [date, raw] of Object.entries(entry.dates)) {
        dates[date] = normalizeDayData(raw);
    }
    return {
        employee: entry.employee,
        week_start: weekStart,
        week_end: weekEnd,
        prev_week: prevWeek,
        next_week: nextWeek,
        dates,
    };
}

/**
 * Remap availability data to a new week using day-of-week matching.
 * Since availability is stored as a weekly pattern, Monday always maps to Monday, etc.
 */
function remapToWeek(
    currentData: MemberWeekData,
    targetWeekStart: string,
): MemberWeekData {
    // Build a day-of-week → availability map from existing data
    const dowMap = new Map<number, DayAvailability>();
    for (const [dateStr, data] of Object.entries(currentData.dates)) {
        const dow = new Date(dateStr + 'T00:00:00').getDay();
        dowMap.set(dow, data);
    }

    const empty: DayAvailability = {
        status: null,
        is_available: null,
        is_absent: false,
        absence_type: null,
        time_slots: [],
    };

    const newDates: Record<string, DayAvailability> = {};
    const cur = new Date(targetWeekStart + 'T00:00:00');
    for (let i = 0; i < 7; i++) {
        const key = toLocalDateKey(cur);
        const dow = cur.getDay();
        newDates[key] = dowMap.get(dow) ?? empty;
        cur.setDate(cur.getDate() + 1);
    }

    const newEnd = addDays(targetWeekStart, 6);
    const newPrev = addDays(targetWeekStart, -7);
    const newNext = addDays(targetWeekStart, 7);

    return {
        ...currentData,
        week_start: targetWeekStart,
        week_end: newEnd,
        prev_week: newPrev,
        next_week: newNext,
        dates: newDates,
    };
}

export function isHourCovered(
    hour: number,
    dayData: DayAvailability,
): 'available' | 'unavailable' | 'absent' | 'unknown' {
    if (dayData.is_absent) { return 'absent'; }
    if (dayData.is_available === false || dayData.status === 'unavailable') { return 'unavailable'; }
    if (dayData.is_available === null && dayData.status === null) { return 'unknown'; }

    // is_available === true
    const slots = dayData.time_slots;
    if (!slots || slots.length === 0) { return 'available'; }

    const hourStr = String(hour).padStart(2, '0') + ':00';
    const inSlot = slots.some((slot) => slot.start <= hourStr && slot.end > hourStr);
    return inSlot ? 'available' : 'unavailable';
}

function cellClass(status: 'available' | 'unavailable' | 'absent' | 'unknown'): string {
    switch (status) {
        case 'available':
            return 'bg-green-200 dark:bg-green-800';
        case 'unavailable':
            return 'bg-red-200 dark:bg-red-900';
        case 'absent':
            return 'bg-orange-200 dark:bg-orange-800';
        default:
            return 'bg-gray-100 dark:bg-gray-700';
    }
}

function getDatesOfWeek(start: string, end: string): string[] {
    const dates: string[] = [];
    const current = new Date(start + 'T00:00:00');
    const endDate = new Date(end + 'T00:00:00');
    while (current <= endDate) {
        dates.push(toLocalDateKey(current));
        current.setDate(current.getDate() + 1);
    }
    return dates;
}

function formatDayLabel(dateStr: string): string {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
}

function formatWeekDisplay(start: string, end: string): string {
    const s = new Date(start + 'T00:00:00');
    const e = new Date(end + 'T00:00:00');
    return `${s.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })} – ${e.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}`;
}

export default function MemberAvailabilityModal({
    entry,
    weekStart,
    weekEnd,
    prevWeek,
    nextWeek,
    departmentUuid,
    onClose,
}: Props) {
    const [weekData, setWeekData] = useState<MemberWeekData | null>(null);
    const [loading, setLoading] = useState(false);
    // Track which employee is currently loaded to avoid stale resets
    const loadedEmployeeId = useRef<number | null>(null);

    useEffect(() => {
        if (entry) {
            // Only reinitialize when a different employee is selected
            if (loadedEmployeeId.current !== entry.employee.id) {
                loadedEmployeeId.current = entry.employee.id;
                setWeekData(normalizeFromEntry(entry, weekStart, weekEnd, prevWeek, nextWeek));
            }
        } else {
            loadedEmployeeId.current = null;
            setWeekData(null);
        }
    }, [entry?.employee.id]); // eslint-disable-line react-hooks/exhaustive-deps

    const navigateWeek = (targetWeekStart: string): void => {
        if (!weekData || !targetWeekStart) { return; }

        // Immediately update UI with local day-of-week remapping (instant, no server call)
        const localData = remapToWeek(weekData, targetWeekStart);
        setWeekData(localData);

        // Fetch server data in background for accurate specific-date overrides
        setLoading(true);
        const url = `/departments/${departmentUuid}/availability/member/${weekData.employee.id}?week=${targetWeekStart}`;
        fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then((res) => {
                if (res.ok) { return res.json() as Promise<MemberWeekData>; }
                return null;
            })
            .then((data) => {
                if (data) { setWeekData(data); }
            })
            .catch(() => { /* keep local data on error */ })
            .finally(() => { setLoading(false); });
    };

    const weekDates = weekData ? getDatesOfWeek(weekData.week_start, weekData.week_end) : [];

    return (
        <Dialog open={!!entry} onOpenChange={(open) => { if (!open) { onClose(); } }}>
            <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden flex flex-col gap-0 p-0">
                <DialogHeader className="px-6 pt-6 pb-3">
                    <DialogTitle className="flex items-center gap-2 text-lg">
                        <UserIcon className="h-5 w-5 text-gray-400 flex-shrink-0" />
                        {weekData?.employee.full_name}
                    </DialogTitle>
                </DialogHeader>

                {weekData && (
                    <>
                        {/* Week navigation */}
                        <div className="flex items-center justify-between px-6 py-3 border-y dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => navigateWeek(weekData.prev_week)}
                                disabled={loading || !weekData.prev_week}
                            >
                                <ChevronLeftIcon className="h-4 w-4" />
                            </Button>
                            <span className="text-sm font-medium text-gray-600 dark:text-gray-300">
                                {formatWeekDisplay(weekData.week_start, weekData.week_end)}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => navigateWeek(weekData.next_week)}
                                disabled={loading || !weekData.next_week}
                            >
                                <ChevronRightIcon className="h-4 w-4" />
                            </Button>
                        </div>

                        {/* Time grid */}
                        <div className="relative overflow-auto flex-1 px-6 py-4">
                            {loading && (
                                <div className="absolute inset-0 bg-white/40 dark:bg-gray-900/40 flex items-center justify-center z-10 pointer-events-none">
                                    <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-600 dark:border-gray-300" />
                                </div>
                            )}
                            <table className="w-full text-xs border-collapse table-fixed">
                                <colgroup>
                                    <col className="w-14" />
                                    {weekDates.map((d) => <col key={d} />)}
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th className="p-1 text-left text-gray-400 font-normal border-b dark:border-gray-700" />
                                        {weekDates.map((dateStr) => (
                                            <th
                                                key={dateStr}
                                                className="p-1 text-center text-gray-700 dark:text-gray-300 font-semibold border-b dark:border-gray-700 capitalize"
                                            >
                                                {formatDayLabel(dateStr)}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {HOURS.map((hour) => (
                                        <tr key={hour}>
                                            <td className="pr-2 text-right font-mono text-gray-400 text-[10px] leading-none py-0 align-middle">
                                                {String(hour).padStart(2, '0')}:00
                                            </td>
                                            {weekDates.map((dateStr) => {
                                                const dayData = weekData.dates[dateStr];
                                                const status = dayData
                                                    ? isHourCovered(hour, dayData)
                                                    : 'unknown';
                                                return (
                                                    <td
                                                        key={dateStr}
                                                        className={`h-7 border border-white dark:border-gray-900 transition-colors ${cellClass(status)}`}
                                                        title={
                                                            status === 'absent' && dayData?.absence_type
                                                                ? dayData.absence_type
                                                                : undefined
                                                        }
                                                    />
                                                );
                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Legend */}
                        <div className="flex items-center gap-5 px-6 py-3 border-t dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400">
                            <div className="flex items-center gap-1.5">
                                <div className="w-3.5 h-3.5 rounded bg-green-200 dark:bg-green-800" />
                                Disponible
                            </div>
                            <div className="flex items-center gap-1.5">
                                <div className="w-3.5 h-3.5 rounded bg-red-200 dark:bg-red-900" />
                                Indisponible
                            </div>
                            <div className="flex items-center gap-1.5">
                                <div className="w-3.5 h-3.5 rounded bg-orange-200 dark:bg-orange-800" />
                                Absent
                            </div>
                            <div className="flex items-center gap-1.5">
                                <div className="w-3.5 h-3.5 rounded bg-gray-100 dark:bg-gray-700 border dark:border-gray-600" />
                                Non défini
                            </div>
                        </div>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
