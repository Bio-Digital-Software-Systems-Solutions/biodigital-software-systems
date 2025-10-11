import React, { useState } from 'react';
import { format, startOfWeek, startOfMonth, addDays, isSameDay, parseISO, getHours, getMinutes, addWeeks, subWeeks, addMonths, subMonths, endOfMonth, startOfDay, endOfWeek, isSameMonth, addYears, subYears, startOfYear, getMonth } from 'date-fns';
import { fr } from 'date-fns/locale';
import { router } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon, ChevronDownIcon } from '@heroicons/react/24/outline';
import { Calendar } from '@/Components/ui/calendar';
import EventQuickCreateModal from '@/Components/EventQuickCreateModal';

interface Event {
    id: number;
    title: string;
    description?: string;
    start_date: string;
    end_date?: string;
    location?: string;
    color?: string;
    category?: {
        id: number;
        name: string;
    };
}

interface CalendarViewProps {
    events: Event[];
    canCreateEvents?: boolean;
}

type CalendarViewMode = 'day' | 'week' | 'month' | 'year';

export default function CalendarView({ events, canCreateEvents = false }: CalendarViewProps) {
    const [currentDate, setCurrentDate] = useState(new Date());
    const [selectedDate, setSelectedDate] = useState<Date | undefined>(new Date());
    const [viewMode, setViewMode] = useState<CalendarViewMode>('week');
    const [showViewMenu, setShowViewMenu] = useState(false);
    const [showQuickCreate, setShowQuickCreate] = useState(false);
    const [quickCreateDate, setQuickCreateDate] = useState<Date | undefined>();
    const [quickCreateHour, setQuickCreateHour] = useState<number | undefined>();
    const today = startOfDay(new Date()); // Create a single reference to today

    const weekStart = startOfWeek(currentDate, { locale: fr, weekStartsOn: 1 });
    const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));
    const hours = Array.from({ length: 24 }, (_, i) => i);

    // Get events for a specific day
    const getEventsForDay = (day: Date) => {
        return events.filter(event => {
            const eventDate = parseISO(event.start_date);
            return isSameDay(eventDate, day);
        });
    };

    // Calculate event position and height
    const getEventStyle = (event: Event) => {
        const startDate = parseISO(event.start_date);
        const endDate = event.end_date ? parseISO(event.end_date) : startDate;

        const startHour = getHours(startDate);
        const startMinute = getMinutes(startDate);
        const endHour = getHours(endDate);
        const endMinute = getMinutes(endDate);

        const top = (startHour * 60 + startMinute) / 60 * 64;
        const duration = ((endHour * 60 + endMinute) - (startHour * 60 + startMinute)) / 60;
        const height = Math.max(duration * 64, 32);

        return { top, height };
    };

    const handleEventClick = (eventId: number) => {
        router.visit(route('events.show', eventId));
    };

    const handleCreateEvent = (date?: Date, hour?: number) => {
        if (canCreateEvents) {
            setQuickCreateDate(date || new Date());
            setQuickCreateHour(hour);
            setShowQuickCreate(true);
        }
    };

    const goToPrevious = () => {
        if (viewMode === 'week') setCurrentDate(subWeeks(currentDate, 1));
        if (viewMode === 'month') setCurrentDate(subMonths(currentDate, 1));
        if (viewMode === 'year') setCurrentDate(subYears(currentDate, 1));
    };

    const goToNext = () => {
        if (viewMode === 'week') setCurrentDate(addWeeks(currentDate, 1));
        if (viewMode === 'month') setCurrentDate(addMonths(currentDate, 1));
        if (viewMode === 'year') setCurrentDate(addYears(currentDate, 1));
    };

    const goToToday = () => setCurrentDate(new Date());

    // Month view calendar days
    const getMonthDays = () => {
        const monthStart = startOfMonth(currentDate);
        const monthStartWeek = startOfWeek(monthStart, { locale: fr, weekStartsOn: 1 });
        const monthEnd = endOfMonth(currentDate);
        const monthEndWeek = endOfWeek(monthEnd, { locale: fr, weekStartsOn: 1 });

        const days = [];
        let day = monthStartWeek;

        while (day <= monthEndWeek) {
            days.push(day);
            day = addDays(day, 1);
        }

        return days;
    };

    const monthDays = getMonthDays();

    return (
        <>
            <EventQuickCreateModal
                show={showQuickCreate}
                onClose={() => setShowQuickCreate(false)}
                initialDate={quickCreateDate}
                initialHour={quickCreateHour}
            />

            <div className="flex gap-4 h-[calc(100vh-12rem)]">
                {/* Sidebar */}
                <div className="w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-lg flex flex-col flex-shrink-0">
                {/* Create Button */}
                {canCreateEvents && (
                    <div className="p-4">
                        <button
                            onClick={() => handleCreateEvent()}
                            className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-full shadow-sm hover:shadow-md transition-shadow text-gray-700 dark:text-gray-200 font-medium"
                        >
                            <span className="text-2xl">+</span>
                            <span>Create</span>
                        </button>
                    </div>
                )}

                {/* Mini Calendar */}
                <div className="px-4 pb-4">
                    <div className="text-sm font-normal text-gray-900 dark:text-white mb-3 lowercase first-letter:uppercase text-center">
                        {format(currentDate, 'MMMM', { locale: fr })}
                    </div>
                    <div className="grid grid-cols-7 gap-1">
                        {/* Day headers */}
                        {['L', 'M', 'M', 'J', 'V', 'S', 'D'].map((d, i) => (
                            <div key={i} className="text-center text-xs font-medium text-gray-500 dark:text-gray-400 pb-1">
                                {d}
                            </div>
                        ))}

                        {/* Calendar days */}
                        {(() => {
                            const monthStart = startOfMonth(currentDate);
                            const monthStartWeek = startOfWeek(monthStart, { locale: fr, weekStartsOn: 1 });
                            const monthEnd = endOfMonth(currentDate);
                            const monthEndWeek = endOfWeek(monthEnd, { locale: fr, weekStartsOn: 1 });

                            const days = [];
                            let day = monthStartWeek;
                            while (day <= monthEndWeek) {
                                days.push(day);
                                day = addDays(day, 1);
                            }

                            return days.map((day, idx) => {
                                const isCurrentMonth = isSameMonth(day, currentDate);
                                const isToday = isSameDay(day, today);
                                const isSelected = selectedDate && isSameDay(day, selectedDate);
                                const dayHasEvents = getEventsForDay(day).length > 0;

                                return (
                                    <div
                                        key={idx}
                                        className={`
                                            text-center text-sm h-7 w-7 flex items-center justify-center rounded-full cursor-pointer transition-colors
                                            ${!isCurrentMonth ? 'text-gray-300 dark:text-gray-700' : 'text-gray-900 dark:text-gray-100'}
                                            ${isSelected ? 'bg-icc-blue text-white font-medium' : ''}
                                            ${isToday && !isSelected ? 'bg-gray-100 dark:bg-gray-700 font-medium' : ''}
                                            ${dayHasEvents && !isSelected && !isToday ? 'font-bold' : ''}
                                            ${isCurrentMonth && !isSelected ? 'hover:bg-gray-100 dark:hover:bg-gray-700' : ''}
                                        `}
                                        onClick={() => {
                                            setSelectedDate(day);
                                            setCurrentDate(day);
                                        }}
                                    >
                                        {format(day, 'd')}
                                    </div>
                                );
                            });
                        })()}
                    </div>
                </div>
            </div>

            {/* Main Calendar Area */}
            <div className="flex-1 bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden flex flex-col">
                {/* Header */}
                <div className="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={goToPrevious}
                                    className="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    <ChevronLeftIcon className="h-5 w-5" />
                                </button>
                                <button
                                    onClick={goToNext}
                                    className="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    <ChevronRightIcon className="h-5 w-5" />
                                </button>
                            </div>
                            <button
                                onClick={goToToday}
                                className="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Today
                            </button>
                            <h2 className="text-xl font-normal text-gray-900 dark:text-white">
                                {viewMode === 'year'
                                    ? format(currentDate, 'yyyy', { locale: fr })
                                    : format(currentDate, 'MMMM yyyy', { locale: fr })
                                }
                            </h2>
                        </div>

                        {/* View Selector */}
                        <div className="relative">
                            <button
                                onClick={() => setShowViewMenu(!showViewMenu)}
                                className="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <span className="capitalize">{viewMode === 'week' ? 'Week' : viewMode === 'month' ? 'Month' : viewMode === 'day' ? 'Day' : 'Year'}</span>
                                <ChevronDownIcon className="h-4 w-4" />
                            </button>

                            {showViewMenu && (
                                <div className="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1 z-10">
                                    {(['day', 'week', 'month', 'year'] as CalendarViewMode[]).map(mode => (
                                        <button
                                            key={mode}
                                            onClick={() => {
                                                setViewMode(mode);
                                                setShowViewMenu(false);
                                            }}
                                            className={`w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 capitalize ${
                                                viewMode === mode ? 'bg-gray-100 dark:bg-gray-600' : ''
                                            }`}
                                        >
                                            {mode === 'week' ? 'Week' : mode === 'month' ? 'Month' : mode === 'day' ? 'Day' : 'Year'}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Calendar Content */}
                <div className="flex-1 overflow-auto">
                    {/* Week View */}
                    {viewMode === 'week' && (
                        <div className="flex h-full">
                            {/* Time column */}
                            <div className="w-20 flex-shrink-0 border-r border-gray-200 dark:border-gray-700">
                                <div className="h-12 border-b border-gray-200 dark:border-gray-700" />
                                {hours.map(hour => (
                                    <div
                                        key={hour}
                                        className="h-16 border-b border-gray-200 dark:border-gray-700 pr-2 text-right text-xs text-gray-500 dark:text-gray-400"
                                        style={{ height: '64px' }}
                                    >
                                        {hour > 0 && format(new Date().setHours(hour, 0, 0, 0), 'HH:mm')}
                                    </div>
                                ))}
                            </div>

                            {/* Days columns */}
                            {weekDays.map((day, dayIndex) => {
                                const dayEvents = getEventsForDay(day);
                                const isToday = isSameDay(day, today);

                                return (
                                    <div key={dayIndex} className="flex-1 border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                                        <div className={`h-12 border-b border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center ${
                                            isToday ? 'bg-icc-blue/10 dark:bg-icc-blue/20' : ''
                                        }`}>
                                            <div className="text-xs text-gray-600 dark:text-gray-400 uppercase">
                                                {format(day, 'EEE', { locale: fr })}
                                            </div>
                                            <div className={`text-lg font-semibold ${
                                                isToday
                                                    ? 'text-white w-8 h-8 rounded-full bg-icc-blue flex items-center justify-center'
                                                    : 'text-gray-900 dark:text-white'
                                            }`}>
                                                {format(day, 'd')}
                                            </div>
                                        </div>

                                        <div className="relative">
                                            {hours.map(hour => (
                                                <div
                                                    key={hour}
                                                    className="h-16 border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer"
                                                    style={{ height: '64px' }}
                                                    onClick={() => handleCreateEvent(day, hour)}
                                                />
                                            ))}

                                            {dayEvents.map(event => {
                                                const { top, height } = getEventStyle(event);
                                                const eventColor = event.color || '#3b82f6';
                                                return (
                                                    <div
                                                        key={event.id}
                                                        className="absolute left-1 right-1 text-white rounded-md px-2 py-1 cursor-pointer overflow-hidden shadow-sm border-l-4 transition-opacity hover:opacity-90"
                                                        style={{
                                                            top: `${top}px`,
                                                            height: `${height}px`,
                                                            backgroundColor: eventColor,
                                                            borderLeftColor: eventColor,
                                                        }}
                                                        onClick={() => handleEventClick(event.id)}
                                                    >
                                                        <div className="text-xs font-semibold truncate">
                                                            {format(parseISO(event.start_date), 'HH:mm')} {event.title}
                                                        </div>
                                                        {height > 40 && event.location && (
                                                            <div className="text-xs opacity-90 truncate">
                                                                {event.location}
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {/* Month View */}
                    {viewMode === 'month' && (
                        <div className="p-4">
                            <div className="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700 border border-gray-200 dark:border-gray-700">
                                {/* Day headers */}
                                {['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].map(day => (
                                    <div key={day} className="bg-gray-50 dark:bg-gray-800 p-2 text-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                        {day}
                                    </div>
                                ))}

                                {/* Calendar days */}
                                {monthDays.map((day, idx) => {
                                    const dayEvents = getEventsForDay(day);
                                    const isToday = isSameDay(day, today);
                                    const isCurrentMonth = isSameMonth(day, currentDate);

                                    return (
                                        <div
                                            key={idx}
                                            className={`bg-white dark:bg-gray-800 min-h-[120px] p-2 ${
                                                !isCurrentMonth ? 'opacity-40' : ''
                                            } hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer`}
                                            onClick={() => handleCreateEvent(day)}
                                        >
                                            <div className={`text-sm font-medium mb-1 ${
                                                isToday
                                                    ? 'text-white w-6 h-6 rounded-full bg-icc-blue flex items-center justify-center'
                                                    : 'text-gray-900 dark:text-white'
                                            }`}>
                                                {format(day, 'd')}
                                            </div>
                                            <div className="space-y-1">
                                                {dayEvents.slice(0, 3).map(event => {
                                                    const eventColor = event.color || '#3b82f6';
                                                    return (
                                                        <div
                                                            key={event.id}
                                                            className="text-xs text-white rounded px-1 py-0.5 truncate cursor-pointer transition-opacity hover:opacity-90"
                                                            style={{ backgroundColor: eventColor }}
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                handleEventClick(event.id);
                                                            }}
                                                        >
                                                            {format(parseISO(event.start_date), 'HH:mm')} {event.title}
                                                        </div>
                                                    );
                                                })}
                                                {dayEvents.length > 3 && (
                                                    <div className="text-xs text-gray-500 dark:text-gray-400">
                                                        +{dayEvents.length - 3} more
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    {/* Day View - Similar to week but single column */}
                    {viewMode === 'day' && (
                        <div className="flex h-full">
                            <div className="w-20 flex-shrink-0 border-r border-gray-200 dark:border-gray-700">
                                <div className="h-12 border-b border-gray-200 dark:border-gray-700" />
                                {hours.map(hour => (
                                    <div key={hour} className="h-16 border-b border-gray-200 dark:border-gray-700 pr-2 text-right text-xs text-gray-500" style={{ height: '64px' }}>
                                        {hour > 0 && format(new Date().setHours(hour, 0, 0, 0), 'HH:mm')}
                                    </div>
                                ))}
                            </div>
                            <div className="flex-1">
                                <div className="h-12 border-b border-gray-200 dark:border-gray-700 flex items-center justify-center">
                                    <div className="text-sm font-medium">{format(currentDate, 'EEEE d MMMM yyyy', { locale: fr })}</div>
                                </div>
                                <div className="relative">
                                    {hours.map(hour => (
                                        <div key={hour} className="h-16 border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer" style={{ height: '64px' }} onClick={() => handleCreateEvent(currentDate, hour)} />
                                    ))}
                                    {getEventsForDay(currentDate).map(event => {
                                        const { top, height } = getEventStyle(event);
                                        const eventColor = event.color || '#3b82f6';
                                        return (
                                            <div
                                                key={event.id}
                                                className="absolute left-4 right-4 text-white rounded-md px-3 py-2 cursor-pointer shadow-sm border-l-4 transition-opacity hover:opacity-90"
                                                style={{
                                                    top: `${top}px`,
                                                    height: `${height}px`,
                                                    backgroundColor: eventColor,
                                                    borderLeftColor: eventColor,
                                                }}
                                                onClick={() => handleEventClick(event.id)}
                                            >
                                                <div className="font-semibold">{format(parseISO(event.start_date), 'HH:mm')} {event.title}</div>
                                                {event.location && <div className="text-sm opacity-90">{event.location}</div>}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Year View */}
                    {viewMode === 'year' && (
                        <div className="p-6">
                            <div className="grid grid-cols-4 gap-8">
                                {Array.from({ length: 12 }, (_, monthIndex) => {
                                    const monthDate = new Date(currentDate.getFullYear(), monthIndex, 1);
                                    const monthStartWeek = startOfWeek(monthDate, { locale: fr, weekStartsOn: 1 });
                                    const monthEnd = endOfMonth(monthDate);
                                    const monthEndWeek = endOfWeek(monthEnd, { locale: fr, weekStartsOn: 1 });

                                    const daysInMonth = [];
                                    let day = monthStartWeek;
                                    while (day <= monthEndWeek) {
                                        daysInMonth.push(day);
                                        day = addDays(day, 1);
                                    }

                                    const monthEvents = events.filter(event => {
                                        const eventDate = parseISO(event.start_date);
                                        return getMonth(eventDate) === monthIndex && eventDate.getFullYear() === currentDate.getFullYear();
                                    });

                                    return (
                                        <div key={monthIndex} className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                            <div className="text-center font-medium text-sm text-gray-900 dark:text-white mb-3 lowercase first-letter:uppercase">
                                                {format(monthDate, 'MMMM', { locale: fr })}
                                            </div>

                                            <div className="grid grid-cols-7 gap-1">
                                                {/* Mini day headers */}
                                                {['L', 'M', 'M', 'J', 'V', 'S', 'D'].map((d, i) => (
                                                    <div key={i} className="text-center text-xs font-medium text-gray-500 dark:text-gray-400 pb-1">
                                                        {d}
                                                    </div>
                                                ))}

                                                {/* Mini calendar days */}
                                                {daysInMonth.map((day, idx) => {
                                                    const isCurrentMonth = isSameMonth(day, monthDate);
                                                    const isToday = isSameDay(day, today);
                                                    const dayHasEvents = getEventsForDay(day).length > 0;

                                                    return (
                                                        <div
                                                            key={idx}
                                                            className={`
                                                                text-center text-xs h-7 w-7 flex items-center justify-center rounded-full
                                                                ${!isCurrentMonth ? 'text-gray-300 dark:text-gray-700' : 'text-gray-900 dark:text-gray-100'}
                                                                ${isToday ? 'bg-icc-blue text-white font-bold' : ''}
                                                                ${dayHasEvents && !isToday ? 'font-bold' : ''}
                                                                ${isCurrentMonth && !isToday ? 'hover:bg-gray-100 dark:hover:bg-gray-700' : ''}
                                                                cursor-pointer transition-colors
                                                            `}
                                                            onClick={() => {
                                                                setCurrentDate(day);
                                                                setViewMode('day');
                                                            }}
                                                        >
                                                            {format(day, 'd')}
                                                        </div>
                                                    );
                                                })}
                                            </div>

                                            {/* Event count indicator */}
                                            {monthEvents.length > 0 && (
                                                <div className="mt-3 text-center text-xs text-gray-500 dark:text-gray-400 font-normal">
                                                    {monthEvents.length} événement{monthEvents.length > 1 ? 's' : ''}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
        </>
    );
}
