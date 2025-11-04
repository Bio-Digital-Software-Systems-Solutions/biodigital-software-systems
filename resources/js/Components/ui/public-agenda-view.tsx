import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Calendar } from '@/Components/ui/calendar';
import {
    User,
    Calendar as CalendarIcon,
    Clock,
    Plus,
    ChevronLeft,
    ChevronRight,
    MapPin,
    Users,
} from 'lucide-react';
import { format, startOfMonth, endOfMonth, addMonths, subMonths, parseISO, isSameDay, startOfWeek, addDays, getHours, getMinutes, addWeeks, subWeeks, startOfDay, endOfWeek, isSameMonth } from 'date-fns';
import { fr } from 'date-fns/locale';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface User {
    id: number;
    uuid: string;
    name: string;
    first_name: string;
    last_name: string;
    email: string;
}

interface CalendarEvent {
    id: string;
    title: string;
    start: string;
    end: string;
    backgroundColor: string;
    borderColor: string;
    extendedProps: {
        type: string;
        status: string;
        formatted_time: string;
    };
}

interface AvailableSlot {
    start_datetime: string;
    end_datetime: string;
    formatted_time: string;
    available: boolean;
    reason?: string;
}

interface PublicAgendaViewProps {
    user: User;
    onCreateAppointment?: (user: User, date: string, startTime: string) => void;
}

export function PublicAgendaView({ user, onCreateAppointment }: PublicAgendaViewProps) {
    const [selectedDate, setSelectedDate] = useState<Date>(new Date());
    const [currentViewMonth, setCurrentViewMonth] = useState<Date>(new Date());
    const [currentDate, setCurrentDate] = useState<Date>(new Date());
    const [appointments, setAppointments] = useState<CalendarEvent[]>([]);
    const [availableSlots, setAvailableSlots] = useState<AvailableSlot[]>([]);
    const [loadingAppointments, setLoadingAppointments] = useState(false);
    const [loadingSlots, setLoadingSlots] = useState(false);

    const fetchAppointments = async (month: Date) => {
        setLoadingAppointments(true);
        try {
            const response = await fetch(`/api/users/${user.uuid}/schedule?month=${format(month, 'yyyy-MM')}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const data = await response.json();
                setAppointments(data.appointments || []);
            } else {
                console.error('Failed to fetch appointments');
                setAppointments([]);
            }
        } catch (error) {
            console.error('Error fetching appointments:', error);
            setAppointments([]);
        } finally {
            setLoadingAppointments(false);
        }
    };

    const fetchAvailableSlots = async (date: Date) => {
        setLoadingSlots(true);
        try {
            const response = await fetch(`/api/users/${user.uuid}/available-slots?date=${format(date, 'yyyy-MM-dd')}&duration=60`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const data = await response.json();
                setAvailableSlots(data.available_slots || []);
            } else {
                console.error('Failed to fetch available slots');
                setAvailableSlots([]);
            }
        } catch (error) {
            console.error('Error fetching available slots:', error);
            setAvailableSlots([]);
        } finally {
            setLoadingSlots(false);
        }
    };

    useEffect(() => {
        fetchAppointments(currentViewMonth);
    }, [user.uuid, currentViewMonth]);

    useEffect(() => {
        fetchAvailableSlots(selectedDate);
    }, [user.uuid, selectedDate]);

    const navigateMonth = (direction: 'prev' | 'next') => {
        const newMonth = direction === 'prev'
            ? subMonths(currentViewMonth, 1)
            : addMonths(currentViewMonth, 1);
        setCurrentViewMonth(newMonth);
    };

    const navigateWeek = (direction: 'prev' | 'next') => {
        const newDate = direction === 'prev'
            ? subWeeks(currentDate, 1)
            : addWeeks(currentDate, 1);
        setCurrentDate(newDate);
        setSelectedDate(newDate);
    };

    const goToToday = () => {
        const today = new Date();
        setCurrentDate(today);
        setSelectedDate(today);
        setCurrentViewMonth(today);
    };

    // Week setup for calendar view
    const weekStart = startOfWeek(currentDate, { locale: fr, weekStartsOn: 1 });
    const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));
    const hours = Array.from({ length: 17 }, (_, i) => i + 7); // 7:00 to 23:00
    const today = startOfDay(new Date());

    const getAppointmentsForDate = (date: Date) => {
        return appointments.filter(appointment =>
            isSameDay(parseISO(appointment.start), date)
        );
    };

    const hasAppointments = (date: Date) => {
        return getAppointmentsForDate(date).length > 0;
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'confirmed': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            case 'completed': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'confirmed': return 'Confirmé';
            case 'pending': return 'En attente';
            case 'completed': return 'Terminé';
            default: return status;
        }
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'individual': return 'Individuel';
            case 'group': return 'Groupe';
            case 'consultation': return 'Consultation';
            case 'meeting': return 'Réunion';
            default: return type;
        }
    };

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'group':
            case 'meeting':
                return <Users className="h-4 w-4" />;
            default:
                return <User className="h-4 w-4" />;
        }
    };

    const handleCreateAppointment = (date: Date, hour: number) => {
        if (onCreateAppointment) {
            const startTime = format(new Date().setHours(hour, 0, 0, 0), 'HH:mm');
            onCreateAppointment(user, format(date, 'yyyy-MM-dd'), startTime);
        } else {
            // Default behavior: redirect to create appointment page
            router.get(route('appointments.create'), {
                date: format(date, 'yyyy-MM-dd'),
                time: format(new Date().setHours(hour, 0, 0, 0), 'HH:mm'),
                participant_ids: [user.id],
            });
        }
    };

    // Get slot availability for a specific date and hour
    const getSlotAvailability = (date: Date, hour: number) => {
        const slotTime = format(new Date().setHours(hour, 0, 0, 0), 'HH:mm');
        const dateString = format(date, 'yyyy-MM-dd');

        // Check if this specific slot is in our available slots
        const slot = availableSlots.find(s =>
            s.formatted_time.startsWith(slotTime) &&
            s.start_datetime.includes(dateString)
        );

        if (slot) {
            return slot;
        }

        // If no specific slot found, determine based on time
        const now = new Date();
        const slotDateTime = new Date(date);
        slotDateTime.setHours(hour, 0, 0, 0);

        const isPast = slotDateTime < now;

        return {
            start_datetime: slotDateTime.toISOString(),
            end_datetime: new Date(slotDateTime.getTime() + 60*60*1000).toISOString(),
            formatted_time: `${slotTime} - ${format(new Date().setHours(hour + 1, 0, 0, 0), 'HH:mm')}`,
            available: !isPast,
            reason: isPast ? 'Passé' : undefined
        } as AvailableSlot;
    };

    return (
        <div className="space-y-6">
            {/* User Info Header */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center space-x-3">
                        <User className="h-6 w-6" />
                        <div>
                            <h3 className="text-lg font-semibold">{user.name}</h3>
                            <p className="text-sm text-gray-500">{user.email}</p>
                        </div>
                    </CardTitle>
                </CardHeader>
            </Card>

            {/* Calendar View */}
            <div className="flex gap-4 h-[calc(100vh-16rem)]">
                {/* Sidebar with mini calendar */}
                <div className="w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-lg flex flex-col flex-shrink-0">
                    {/* Mini Calendar */}
                    <div className="p-4">
                        <div className="flex items-center justify-between mb-4">
                            <button
                                onClick={() => navigateMonth('prev')}
                                className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </button>
                            <div className="text-sm font-normal text-gray-900 dark:text-white lowercase first-letter:uppercase">
                                {format(currentViewMonth, 'MMMM', { locale: fr })}
                            </div>
                            <button
                                onClick={() => navigateMonth('next')}
                                className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </button>
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
                                const monthStart = startOfMonth(currentViewMonth);
                                const monthStartWeek = startOfWeek(monthStart, { locale: fr, weekStartsOn: 1 });
                                const monthEnd = endOfMonth(currentViewMonth);
                                const monthEndWeek = endOfWeek(monthEnd, { locale: fr, weekStartsOn: 1 });

                                const days = [];
                                let day = monthStartWeek;
                                while (day <= monthEndWeek) {
                                    days.push(day);
                                    day = addDays(day, 1);
                                }

                                return days.map((day, idx) => {
                                    const isCurrentMonth = isSameMonth(day, currentViewMonth);
                                    const isToday = isSameDay(day, today);
                                    const isSelected = selectedDate && isSameDay(day, selectedDate);
                                    const dayHasEvents = hasAppointments(day);

                                    return (
                                        <div
                                            key={idx}
                                            className={`
                                                text-center text-sm h-8 w-8 flex items-center justify-center rounded-full cursor-pointer transition-colors
                                                ${!isCurrentMonth ? 'text-gray-300 dark:text-gray-700' : 'text-gray-900 dark:text-gray-100'}
                                                ${isSelected ? 'bg-icc-blue text-white font-medium' : ''}
                                                ${isToday && !isSelected ? 'bg-blue-600 text-white font-medium' : ''}
                                                ${dayHasEvents && !isSelected && !isToday ? 'font-bold' : ''}
                                                ${isCurrentMonth && !isSelected && !isToday ? 'hover:bg-gray-100 dark:hover:bg-gray-700' : ''}
                                            `}
                                            onClick={() => {
                                                if (isCurrentMonth) {
                                                    setSelectedDate(day);
                                                    setCurrentDate(day);
                                                }
                                            }}
                                        >
                                            {format(day, 'd')}
                                        </div>
                                    );
                                });
                            })()}
                        </div>

                        <div className="mt-4 text-xs text-gray-500 dark:text-gray-400">
                            <div className="flex items-center space-x-2">
                                <div className="w-3 h-3 rounded bg-blue-100 border border-blue-300"></div>
                                <span>Jours avec rendez-vous publics</span>
                            </div>
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
                                        onClick={() => navigateWeek('prev')}
                                        className="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        <ChevronLeft className="h-5 w-5" />
                                    </button>
                                    <button
                                        onClick={() => navigateWeek('next')}
                                        className="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        <ChevronRight className="h-5 w-5" />
                                    </button>
                                </div>
                                <button
                                    onClick={goToToday}
                                    className="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    Aujourd'hui
                                </button>
                                <h2 className="text-xl font-normal text-gray-900 dark:text-white">
                                    Agenda de {user.name}
                                </h2>
                            </div>
                        </div>
                    </div>

                    {/* Calendar Content */}
                    <div className="flex-1 overflow-auto">
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
                                        {format(new Date().setHours(hour, 0, 0, 0), 'HH:mm')}
                                    </div>
                                ))}
                            </div>

                            {/* Days columns */}
                            {weekDays.map((day, dayIndex) => {
                                const dayAppointments = getAppointmentsForDate(day);
                                const isToday = isSameDay(day, today);
                                const isSelected = isSameDay(day, selectedDate);

                                return (
                                    <div key={dayIndex} className="flex-1 border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                                        <div className={`h-12 border-b border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center ${
                                            isToday ? 'bg-icc-blue/10 dark:bg-icc-blue/20' : ''
                                        } ${isSelected ? 'bg-blue-50 dark:bg-blue-900/10' : ''}`}>
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
                                            {hours.map(hour => {
                                                const slot = getSlotAvailability(day, hour);
                                                return (
                                                    <div
                                                        key={hour}
                                                        className={`h-16 border-b border-gray-100 dark:border-gray-700/50 cursor-pointer transition-colors ${
                                                            slot.available
                                                                ? 'hover:bg-green-50 dark:hover:bg-green-900/20'
                                                                : slot.reason === 'Passé'
                                                                ? 'bg-gray-50 dark:bg-gray-800 opacity-60'
                                                                : 'bg-red-50 dark:bg-red-900/20'
                                                        }`}
                                                        style={{ height: '64px' }}
                                                        onClick={() => slot.available && handleCreateAppointment(day, hour)}
                                                    >
                                                        {slot.available && (
                                                            <div className="flex items-center justify-center h-full text-xs text-green-600 dark:text-green-400 opacity-0 hover:opacity-100 transition-opacity">
                                                                <Plus className="h-4 w-4" />
                                                            </div>
                                                        )}
                                                        {slot.reason && !slot.available && (
                                                            <div className="flex items-center justify-center h-full text-xs text-gray-500 dark:text-gray-400">
                                                                {slot.reason}
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}

                                            {/* Render appointments */}
                                            {dayAppointments.map(appointment => {
                                                const startDate = parseISO(appointment.start);
                                                const endDate = parseISO(appointment.end);
                                                const startHour = getHours(startDate);
                                                const startMinute = getMinutes(startDate);
                                                const endHour = getHours(endDate);
                                                const endMinute = getMinutes(endDate);

                                                const top = ((startHour - 7) * 60 + startMinute) / 60 * 64;
                                                const duration = ((endHour * 60 + endMinute) - (startHour * 60 + startMinute)) / 60;
                                                const height = Math.max(duration * 64, 32);

                                                return (
                                                    <div
                                                        key={appointment.id}
                                                        className="absolute left-1 right-1 text-white rounded-md px-2 py-1 overflow-hidden shadow-sm border-l-4 transition-opacity hover:opacity-90"
                                                        style={{
                                                            top: `${top}px`,
                                                            height: `${height}px`,
                                                            backgroundColor: appointment.backgroundColor,
                                                            borderLeftColor: appointment.backgroundColor,
                                                        }}
                                                    >
                                                        <div className="text-xs font-semibold truncate">
                                                            {format(startDate, 'HH:mm')} {appointment.title}
                                                        </div>
                                                        {height > 40 && (
                                                            <div className="text-xs opacity-90 truncate">
                                                                {getTypeLabel(appointment.extendedProps.type)}
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
        </div>
    );
}