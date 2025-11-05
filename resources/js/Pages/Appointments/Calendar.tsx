import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Calendar as CalendarIcon,
    Clock,
    MapPin,
    Users,
    Filter,
    List,
} from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { format, parseISO, startOfMonth, endOfMonth, eachDayOfInterval, addMonths, subMonths, isSameDay, isSameMonth, isToday, getDay, isPast, startOfDay } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';

import type { AppointmentCalendarProps, Appointment, AppointmentStatus, AppointmentType } from '@/Types/appointment';

export default function AppointmentCalendar() {
    const { appointments: rawAppointments, currentMonth: currentMonthStr } = usePage<AppointmentCalendarProps>().props;

    // Convert calendar events back to appointments for easier handling
    const appointments: Appointment[] = rawAppointments.map(event => ({
        id: parseInt(event.id),
        uuid: event.id,
        title: event.title,
        start_datetime: event.start,
        end_datetime: event.end,
        status: event.extendedProps.status,
        type: event.extendedProps.type,
        location: event.extendedProps.location,
        organizer: { name: event.extendedProps.organizer } as any,
        participants_count: event.extendedProps.participants_count,
        // Add other required fields with defaults
        description: '',
        visibility: 'private',
        user_id: 0,
        created_at: '',
        updated_at: '',
        duration_minutes: 0,
        is_past: false,
        is_future: false,
        is_today: false,
        can_be_cancelled: false,
        can_be_modified: false,
        formatted_date: '',
        formatted_time_range: '',
    }));

    const [currentMonth, setCurrentMonth] = useState(new Date(currentMonthStr));
    const [selectedStatus, setSelectedStatus] = useState<AppointmentStatus | 'all'>('all');
    const [selectedType, setSelectedType] = useState<AppointmentType | 'all'>('all');

    const monthStart = startOfMonth(currentMonth);
    const monthEnd = endOfMonth(currentMonth);
    const daysInMonth = eachDayOfInterval({ start: monthStart, end: monthEnd });

    // Add padding days for the calendar grid
    const startPadding = getDay(monthStart); // 0 = Sunday, 1 = Monday, etc.
    const paddingDays = Array.from({ length: startPadding }, (_, i) =>
        new Date(monthStart.getTime() - (startPadding - i) * 24 * 60 * 60 * 1000)
    );

    const allCalendarDays = [...paddingDays, ...daysInMonth];

    const navigateToMonth = (direction: 'prev' | 'next') => {
        const newMonth = direction === 'prev' ? subMonths(currentMonth, 1) : addMonths(currentMonth, 1);
        setCurrentMonth(newMonth);

        router.get(route('appointments.calendar'), {
            month: format(newMonth, 'yyyy-MM'),
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            type: selectedType !== 'all' ? selectedType : undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFilterChange = () => {
        router.get(route('appointments.calendar'), {
            month: format(currentMonth, 'yyyy-MM'),
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            type: selectedType !== 'all' ? selectedType : undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getAppointmentsForDay = (day: Date) => {
        return appointments.filter(appointment =>
            isSameDay(parseISO(appointment.start_datetime), day) &&
            (selectedStatus === 'all' || appointment.status === selectedStatus) &&
            (selectedType === 'all' || appointment.type === selectedType)
        );
    };

    const getStatusColor = (status: AppointmentStatus): string => {
        switch (status) {
            case 'confirmed': return 'bg-green-500';
            case 'pending': return 'bg-yellow-500';
            case 'cancelled': return 'bg-red-500';
            case 'completed': return 'bg-blue-500';
            default: return 'bg-gray-500';
        }
    };

    const getStatusLabel = (status: AppointmentStatus) => {
        switch (status) {
            case 'pending': return 'En attente';
            case 'confirmed': return 'Confirmé';
            case 'cancelled': return 'Annulé';
            case 'completed': return 'Terminé';
            default: return status;
        }
    };

    const getTypeLabel = (type: AppointmentType) => {
        switch (type) {
            case 'individual': return 'Individuel';
            case 'group': return 'Groupe';
            case 'consultation': return 'Consultation';
            case 'meeting': return 'Réunion';
            default: return type;
        }
    };

    const handleDayClick = (day: Date) => {
        // Empêcher la sélection de dates passées
        if (isPast(startOfDay(day))) {
            toast.error('Impossible de créer un rendez-vous à une date passée');
            return;
        }

        const dateStr = format(day, 'yyyy-MM-dd');
        router.get(route('appointments.create'), {
            date: dateStr,
        });
    };

    return (
        <DashboardLayout>
            <Head title="Calendrier des rendez-vous" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-8">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Calendrier des rendez-vous
                            </h1>
                            <p className="mt-2 text-gray-600 dark:text-gray-400">
                                Vue calendrier de vos rendez-vous
                            </p>
                        </div>
                        <div className="flex items-center space-x-4">
                            <Button variant="outline" asChild>
                                <Link href={route('appointments.index')}>
                                    <List className="h-4 w-4 mr-2" />
                                    Vue liste
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href={route('appointments.create')}>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Nouveau rendez-vous
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Calendar Controls and Filters */}
                <div className="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-4 sm:space-y-0 sm:space-x-4">
                    {/* Calendar Navigation */}
                    <div className="flex items-center space-x-4">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => navigateToMonth('prev')}
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white min-w-[200px] text-center">
                            {format(currentMonth, 'MMMM yyyy', { locale: fr })}
                        </h2>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => navigateToMonth('next')}
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                    </div>

                    {/* Filters */}
                    <div className="flex items-center space-x-4">
                        <div className="flex items-center space-x-2">
                            <Filter className="h-4 w-4 text-gray-500" />
                            <Select
                                value={selectedStatus}
                                onValueChange={(value) => {
                                    setSelectedStatus(value as any);
                                    setTimeout(handleFilterChange, 0);
                                }}
                            >
                                <SelectTrigger className="w-[140px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les statuts</SelectItem>
                                    <SelectItem value="pending">En attente</SelectItem>
                                    <SelectItem value="confirmed">Confirmé</SelectItem>
                                    <SelectItem value="cancelled">Annulé</SelectItem>
                                    <SelectItem value="completed">Terminé</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Select
                            value={selectedType}
                            onValueChange={(value) => {
                                setSelectedType(value as any);
                                setTimeout(handleFilterChange, 0);
                            }}
                        >
                            <SelectTrigger className="w-[140px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les types</SelectItem>
                                <SelectItem value="individual">Individuel</SelectItem>
                                <SelectItem value="group">Groupe</SelectItem>
                                <SelectItem value="consultation">Consultation</SelectItem>
                                <SelectItem value="meeting">Réunion</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Calendar Grid */}
                <Card>
                    <CardContent className="p-0">
                        {/* Days of week header */}
                        <div className="grid grid-cols-7 border-b">
                            {['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'].map((day) => (
                                <div key={day} className="p-4 text-center text-sm font-medium text-gray-500 dark:text-gray-400 border-r last:border-r-0">
                                    {day}
                                </div>
                            ))}
                        </div>

                        {/* Calendar days */}
                        <div className="grid grid-cols-7">
                            {allCalendarDays.map((day, index) => {
                                const dayAppointments = getAppointmentsForDay(day);
                                const isCurrentMonth = isSameMonth(day, currentMonth);
                                const isDayToday = isToday(day);
                                const isPastDay = isPast(startOfDay(day));

                                return (
                                    <div
                                        key={index}
                                        className={`min-h-[120px] p-2 border-r border-b last:border-r-0 ${
                                            !isCurrentMonth ? 'bg-gray-50 dark:bg-gray-800/50' : ''
                                        } ${isDayToday ? 'bg-blue-50 dark:bg-blue-900/20' : ''} ${
                                            isPastDay
                                                ? 'bg-gray-100 dark:bg-gray-700/30 cursor-not-allowed opacity-60'
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer'
                                        } transition-colors`}
                                        onClick={() => !isPastDay && handleDayClick(day)}
                                    >
                                        <div className={`text-sm font-medium mb-2 ${
                                            !isCurrentMonth
                                                ? 'text-gray-400'
                                                : isPastDay
                                                ? 'text-gray-400 dark:text-gray-500'
                                                : isDayToday
                                                ? 'text-blue-600 dark:text-blue-400'
                                                : 'text-gray-900 dark:text-white'
                                        }`}>
                                            {format(day, 'd')}
                                        </div>

                                        {/* Appointments for this day */}
                                        <div className="space-y-1">
                                            {dayAppointments.slice(0, 3).map((appointment) => (
                                                <Link
                                                    key={appointment.id}
                                                    href={route('appointments.show', appointment.uuid)}
                                                    className="block"
                                                    onClick={(e) => e.stopPropagation()}
                                                >
                                                    <div className={`text-xs p-1 rounded text-white truncate ${getStatusColor(appointment.status)}`}>
                                                        <div className="font-medium truncate">
                                                            {appointment.title}
                                                        </div>
                                                        <div className="flex items-center space-x-1 mt-1">
                                                            <Clock className="h-3 w-3" />
                                                            <span>
                                                                {format(parseISO(appointment.start_datetime), 'HH:mm')}
                                                            </span>
                                                            {appointment.participants_count > 0 && (
                                                                <>
                                                                    <Users className="h-3 w-3 ml-1" />
                                                                    <span>{appointment.participants_count}</span>
                                                                </>
                                                            )}
                                                        </div>
                                                    </div>
                                                </Link>
                                            ))}
                                            {dayAppointments.length > 3 && (
                                                <div className="text-xs text-gray-500 dark:text-gray-400 text-center">
                                                    +{dayAppointments.length - 3} autres
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                {/* Legend */}
                <div className="mt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Légende</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div className="flex items-center space-x-2">
                                    <div className="w-3 h-3 rounded bg-yellow-500"></div>
                                    <span className="text-sm">En attente</span>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <div className="w-3 h-3 rounded bg-green-500"></div>
                                    <span className="text-sm">Confirmé</span>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <div className="w-3 h-3 rounded bg-red-500"></div>
                                    <span className="text-sm">Annulé</span>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <div className="w-3 h-3 rounded bg-blue-500"></div>
                                    <span className="text-sm">Terminé</span>
                                </div>
                            </div>
                            <div className="mt-4 text-sm text-gray-600 dark:text-gray-400">
                                Cliquez sur un jour pour créer un nouveau rendez-vous ou sur un rendez-vous existant pour le voir.
                                <br />
                                <span className="text-xs text-gray-500">Les dates passées ne sont pas sélectionnables.</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}