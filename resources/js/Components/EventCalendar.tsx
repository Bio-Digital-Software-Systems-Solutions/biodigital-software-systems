import React, { useState } from 'react';
import { Calendar } from '@/Components/ui/calendar';
import { format, isSameDay, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';
import { router } from '@inertiajs/react';
import { CalendarIcon, PlusIcon, MapPinIcon, ClockIcon } from '@heroicons/react/24/outline';

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

interface EventCalendarProps {
    events: Event[];
    canCreateEvents?: boolean;
}

export default function EventCalendar({ events, canCreateEvents = false }: EventCalendarProps) {
    const [selectedDate, setSelectedDate] = useState<Date | undefined>(new Date());
    const [showEventModal, setShowEventModal] = useState(false);

    // Get events for a specific date
    const getEventsForDate = (date: Date) => {
        return events.filter(event => {
            const eventDate = parseISO(event.start_date);
            return isSameDay(eventDate, date);
        });
    };

    // Get all event dates for highlighting
    const eventDates = events.map(event => parseISO(event.start_date));

    // Handle date click
    const handleDateSelect = (date: Date | undefined) => {
        setSelectedDate(date);
    };

    // Handle event click
    const handleEventClick = (eventId: number) => {
        router.visit(route('events.show', eventId));
    };

    // Handle create event
    const handleCreateEvent = () => {
        if (selectedDate) {
            router.visit(route('events.create', {
                date: format(selectedDate, 'yyyy-MM-dd')
            }));
        }
    };

    const selectedDateEvents = selectedDate ? getEventsForDate(selectedDate) : [];

    return (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Calendar Section */}
            <div className="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <CalendarIcon className="h-6 w-6 text-icc-blue" />
                        Calendrier des événements
                    </h2>
                </div>

                <Calendar
                    mode="single"
                    selected={selectedDate}
                    onSelect={handleDateSelect}
                    locale={fr}
                    className="rounded-md border dark:border-gray-700"
                    modifiers={{
                        hasEvent: eventDates
                    }}
                    modifiersClassNames={{
                        hasEvent: "bg-icc-blue/10 font-bold text-icc-blue dark:bg-icc-blue/20"
                    }}
                />
            </div>

            {/* Events List for Selected Date */}
            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                        {selectedDate ? format(selectedDate, 'dd MMMM yyyy', { locale: fr }) : 'Sélectionner une date'}
                    </h3>
                    {canCreateEvents && selectedDate && (
                        <button
                            onClick={handleCreateEvent}
                            className="p-2 rounded-lg bg-icc-blue text-white hover:bg-primary transition-colors"
                            title="Créer un événement"
                        >
                            <PlusIcon className="h-5 w-5" />
                        </button>
                    )}
                </div>

                <div className="space-y-3 max-h-[600px] overflow-y-auto">
                    {selectedDateEvents.length > 0 ? (
                        selectedDateEvents.map(event => (
                            <div
                                key={event.id}
                                onClick={() => handleEventClick(event.id)}
                                className="p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-icc-blue dark:hover:border-icc-blue cursor-pointer transition-all hover:shadow-md"
                            >
                                <h4 className="font-semibold text-gray-900 dark:text-white mb-2">
                                    {event.title}
                                </h4>

                                {event.description && (
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">
                                        {event.description}
                                    </p>
                                )}

                                <div className="space-y-1">
                                    <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <ClockIcon className="h-4 w-4" />
                                        <span>{format(parseISO(event.start_date), 'HH:mm', { locale: fr })}</span>
                                        {event.end_date && (
                                            <>
                                                <span>-</span>
                                                <span>{format(parseISO(event.end_date), 'HH:mm', { locale: fr })}</span>
                                            </>
                                        )}
                                    </div>

                                    {event.location && (
                                        <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <MapPinIcon className="h-4 w-4" />
                                            <span className="truncate">{event.location}</span>
                                        </div>
                                    )}

                                    {event.category && (
                                        <div className="mt-2">
                                            <span className="inline-block px-2 py-1 text-xs rounded-full bg-icc-blue/10 text-icc-blue dark:bg-icc-blue/20">
                                                {event.category.name}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="text-center py-12">
                            <CalendarIcon className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Aucun événement pour cette date
                            </p>
                            {canCreateEvents && (
                                <button
                                    onClick={handleCreateEvent}
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-icc-blue text-white rounded-lg hover:bg-primary transition-colors"
                                >
                                    <PlusIcon className="h-5 w-5" />
                                    Créer un événement
                                </button>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
