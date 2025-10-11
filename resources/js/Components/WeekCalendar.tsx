import React, { useState } from 'react';
import { format, startOfWeek, addDays, isSameDay, parseISO, getHours, getMinutes, addWeeks, subWeeks } from 'date-fns';
import { fr } from 'date-fns/locale';
import { router } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon } from '@heroicons/react/24/outline';
import { Calendar } from '@/Components/ui/calendar';

interface Event {
    id: number;
    title: string;
    description?: string;
    start_date: string;
    end_date?: string;
    location?: string;
    category?: {
        id: number;
        name: string;
    };
}

interface WeekCalendarProps {
    events: Event[];
    canCreateEvents?: boolean;
}

export default function WeekCalendar({ events, canCreateEvents = false }: WeekCalendarProps) {
    const [currentDate, setCurrentDate] = useState(new Date());
    const [selectedDate, setSelectedDate] = useState<Date | undefined>(new Date());

    const weekStart = startOfWeek(currentDate, { locale: fr });
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

        const top = (startHour * 60 + startMinute) / 60 * 64; // 64px per hour
        const duration = ((endHour * 60 + endMinute) - (startHour * 60 + startMinute)) / 60;
        const height = Math.max(duration * 64, 32); // Minimum 32px height

        return { top, height };
    };

    const handleEventClick = (eventId: number) => {
        router.visit(route('events.show', eventId));
    };

    const handleCreateEvent = (date: Date, hour: number) => {
        if (canCreateEvents) {
            const eventDate = new Date(date);
            eventDate.setHours(hour, 0, 0, 0);
            router.visit(route('events.create', {
                date: format(eventDate, 'yyyy-MM-dd HH:mm:ss')
            }));
        }
    };

    const goToPreviousWeek = () => setCurrentDate(subWeeks(currentDate, 1));
    const goToNextWeek = () => setCurrentDate(addWeeks(currentDate, 1));
    const goToToday = () => setCurrentDate(new Date());

    return (
        <div className="flex gap-4 h-[calc(100vh-12rem)]">
            {/* Mini Calendar Sidebar */}
            <div className="w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 flex-shrink-0">
                <Calendar
                    mode="single"
                    selected={selectedDate}
                    onSelect={(date) => {
                        setSelectedDate(date);
                        if (date) setCurrentDate(date);
                    }}
                    locale={fr}
                    className="rounded-md border-0"
                    modifiers={{
                        hasEvent: events.map(e => parseISO(e.start_date))
                    }}
                    modifiersClassNames={{
                        hasEvent: "bg-icc-blue/10 font-bold text-icc-blue dark:bg-icc-blue/20"
                    }}
                />
            </div>

            {/* Week View */}
            <div className="flex-1 bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden flex flex-col">
                {/* Header */}
                <div className="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                {format(weekStart, 'MMMM yyyy', { locale: fr })}
                            </h2>
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={goToPreviousWeek}
                                    className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    <ChevronLeftIcon className="h-5 w-5" />
                                </button>
                                <button
                                    onClick={goToToday}
                                    className="px-3 py-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    Aujourd'hui
                                </button>
                                <button
                                    onClick={goToNextWeek}
                                    className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    <ChevronRightIcon className="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Calendar Grid */}
                <div className="flex-1 overflow-auto">
                    <div className="flex">
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
                            const isToday = isSameDay(day, new Date());

                            return (
                                <div key={dayIndex} className="flex-1 border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                                    {/* Day header */}
                                    <div className={`h-12 border-b border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center ${
                                        isToday ? 'bg-icc-blue/10 dark:bg-icc-blue/20' : ''
                                    }`}>
                                        <div className="text-xs text-gray-600 dark:text-gray-400 uppercase">
                                            {format(day, 'EEE', { locale: fr })}
                                        </div>
                                        <div className={`text-lg font-semibold ${
                                            isToday
                                                ? 'text-icc-blue dark:text-icc-blue w-8 h-8 rounded-full bg-icc-blue text-white flex items-center justify-center'
                                                : 'text-gray-900 dark:text-white'
                                        }`}>
                                            {format(day, 'd')}
                                        </div>
                                    </div>

                                    {/* Time slots */}
                                    <div className="relative">
                                        {hours.map(hour => (
                                            <div
                                                key={hour}
                                                className="h-16 border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer"
                                                style={{ height: '64px' }}
                                                onClick={() => handleCreateEvent(day, hour)}
                                            />
                                        ))}

                                        {/* Events */}
                                        {dayEvents.map(event => {
                                            const { top, height } = getEventStyle(event);
                                            return (
                                                <div
                                                    key={event.id}
                                                    className="absolute left-1 right-1 bg-icc-blue/90 hover:bg-icc-blue text-white rounded-md px-2 py-1 cursor-pointer overflow-hidden shadow-sm border-l-4 border-icc-blue"
                                                    style={{
                                                        top: `${top}px`,
                                                        height: `${height}px`,
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
                </div>
            </div>
        </div>
    );
}
