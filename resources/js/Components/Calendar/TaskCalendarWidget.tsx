import React, { useState } from 'react';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon } from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';
import { parseISO, format, isSameDay } from 'date-fns';

interface AppointmentParticipant {
    id: number;
    first_name: string;
    last_name: string;
    status: string;
}

interface Appointment {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    start_datetime: string;
    end_datetime: string;
    location?: string;
    status: string;
    type: string;
    visibility: string;
    max_participants?: number;
    organizer: {
        id: number;
        first_name: string;
        last_name: string;
    };
    participants: AppointmentParticipant[];
    participants_count: number;
    appointmentable_type: string;
    appointmentable?: {
        id: number;
        title?: string;
    };
}

interface Props {
    taskId: number;
    taskUuid: string;
    appointments: Appointment[];
    onCreateClick: (date: Date) => void;
    onAppointmentClick?: (appointment: Appointment) => void;
}

const DAYS_OF_WEEK = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
const MONTHS = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
];

export default function TaskCalendarWidget({
    taskId,
    taskUuid,
    appointments,
    onCreateClick,
    onAppointmentClick
}: Props) {
    const [currentDate, setCurrentDate] = useState(new Date());
    const [selectedDate, setSelectedDate] = useState<Date | null>(new Date());

    const getDaysInMonth = (year: number, month: number) => {
        return new Date(year, month + 1, 0).getDate();
    };

    const getFirstDayOfMonth = (year: number, month: number) => {
        const day = new Date(year, month, 1).getDay();
        // Convert Sunday (0) to 7 for Monday-first week
        return day === 0 ? 6 : day - 1;
    };

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const daysInMonth = getDaysInMonth(year, month);
    const firstDayOfMonth = getFirstDayOfMonth(year, month);

    // Get previous month days to show
    const prevMonthDays = getDaysInMonth(year, month - 1);
    const prevMonthDaysToShow = firstDayOfMonth;

    // Get appointments for a specific date
    const getAppointmentsForDate = (date: Date) => {
        return appointments.filter(apt => {
            const aptDate = parseISO(apt.start_datetime);
            return isSameDay(aptDate, date);
        });
    };

    // Check if date has appointments
    const hasAppointments = (date: Date) => {
        return getAppointmentsForDate(date).length > 0;
    };

    // Navigate months
    const goToPreviousMonth = () => {
        setCurrentDate(new Date(year, month - 1, 1));
    };

    const goToNextMonth = () => {
        setCurrentDate(new Date(year, month + 1, 1));
    };

    // Check if date is today
    const isToday = (day: number) => {
        const today = new Date();
        return (
            day === today.getDate() &&
            month === today.getMonth() &&
            year === today.getFullYear()
        );
    };

    // Check if date is selected
    const isSelected = (day: number) => {
        if (!selectedDate) return false;
        return (
            day === selectedDate.getDate() &&
            month === selectedDate.getMonth() &&
            year === selectedDate.getFullYear()
        );
    };

    // Handle date click
    const handleDateClick = (day: number) => {
        const date = new Date(year, month, day);
        setSelectedDate(date);
    };

    // Get appointments for selected date
    const selectedDateAppointments = selectedDate
        ? getAppointmentsForDate(selectedDate)
        : [];

    // Build calendar grid
    const calendarDays = [];

    // Previous month days
    for (let i = prevMonthDaysToShow - 1; i >= 0; i--) {
        calendarDays.push({
            day: prevMonthDays - i,
            isCurrentMonth: false,
            date: new Date(year, month - 1, prevMonthDays - i)
        });
    }

    // Current month days
    for (let i = 1; i <= daysInMonth; i++) {
        calendarDays.push({
            day: i,
            isCurrentMonth: true,
            date: new Date(year, month, i)
        });
    }

    // Next month days to fill remaining grid
    const remainingDays = 42 - calendarDays.length; // 6 rows * 7 days
    for (let i = 1; i <= remainingDays; i++) {
        calendarDays.push({
            day: i,
            isCurrentMonth: false,
            date: new Date(year, month + 1, i)
        });
    }

    const formatTime = (datetime: string) => {
        return format(parseISO(datetime), 'HH:mm');
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'pending':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
            case 'confirmed':
                return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
            case 'cancelled':
                return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
            case 'completed':
                return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }
    };

    return (
        <div className="space-y-4">
            {/* Create Button */}
            <Button
                onClick={() => onCreateClick(selectedDate || new Date())}
                className="w-full flex items-center justify-center gap-2 py-3 border-2 border-primary text-primary bg-transparent hover:bg-primary hover:text-white transition-colors rounded-full"
                variant="outline"
            >
                <PlusIcon className="h-5 w-5" />
                Créer
            </Button>

            {/* Mini Calendar */}
            <div className="bg-white dark:bg-gray-800 rounded-lg">
                {/* Month Navigation */}
                <div className="flex items-center justify-between mb-4">
                    <button
                        onClick={goToPreviousMonth}
                        className="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                        aria-label="Mois précédent"
                    >
                        <ChevronLeftIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                    </button>
                    <h3 className="text-base font-semibold text-gray-900 dark:text-white">
                        {MONTHS[month]}
                    </h3>
                    <button
                        onClick={goToNextMonth}
                        className="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                        aria-label="Mois suivant"
                    >
                        <ChevronRightIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                    </button>
                </div>

                {/* Days of Week Header */}
                <div className="grid grid-cols-7 gap-1 mb-2">
                    {DAYS_OF_WEEK.map((day, index) => (
                        <div
                            key={index}
                            className="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-1"
                        >
                            {day}
                        </div>
                    ))}
                </div>

                {/* Calendar Grid */}
                <div className="grid grid-cols-7 gap-1">
                    {calendarDays.map((item, index) => {
                        const hasApt = item.isCurrentMonth && hasAppointments(item.date);
                        const isTodayDate = item.isCurrentMonth && isToday(item.day);
                        const isSelectedDate = item.isCurrentMonth && isSelected(item.day);

                        return (
                            <button
                                key={index}
                                onClick={() => item.isCurrentMonth && handleDateClick(item.day)}
                                disabled={!item.isCurrentMonth}
                                className={`
                                    relative w-8 h-8 mx-auto flex items-center justify-center text-sm rounded-full
                                    transition-colors
                                    ${!item.isCurrentMonth
                                        ? 'text-gray-300 dark:text-gray-600 cursor-default'
                                        : 'hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer'
                                    }
                                    ${isTodayDate && !isSelectedDate
                                        ? 'bg-primary text-white'
                                        : ''
                                    }
                                    ${isSelectedDate
                                        ? 'bg-primary text-white ring-2 ring-primary ring-offset-2 dark:ring-offset-gray-800'
                                        : ''
                                    }
                                    ${item.isCurrentMonth && !isTodayDate && !isSelectedDate
                                        ? 'text-gray-900 dark:text-white'
                                        : ''
                                    }
                                `}
                            >
                                {item.day}
                                {/* Appointment indicator dot */}
                                {hasApt && !isSelectedDate && !isTodayDate && (
                                    <span className="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 bg-primary rounded-full" />
                                )}
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* Selected Date Appointments */}
            {selectedDate && (
                <div className="space-y-2">
                    <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {selectedDate.toLocaleDateString('fr-FR', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long'
                        })}
                    </h4>

                    {selectedDateAppointments.length > 0 ? (
                        <div className="space-y-2">
                            {selectedDateAppointments.map((apt) => (
                                <div
                                    key={apt.id}
                                    onClick={() => onAppointmentClick?.(apt)}
                                    className="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {apt.title}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {formatTime(apt.start_datetime)} - {formatTime(apt.end_datetime)}
                                            </p>
                                        </div>
                                        <span className={`text-xs px-2 py-0.5 rounded-full ${getStatusColor(apt.status)}`}>
                                            {apt.status === 'pending' ? 'En attente' :
                                             apt.status === 'confirmed' ? 'Confirmé' :
                                             apt.status === 'cancelled' ? 'Annulé' :
                                             apt.status === 'completed' ? 'Terminé' : apt.status}
                                        </span>
                                    </div>
                                    {apt.location && (
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {apt.location}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-2">
                            Aucun rendez-vous
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
