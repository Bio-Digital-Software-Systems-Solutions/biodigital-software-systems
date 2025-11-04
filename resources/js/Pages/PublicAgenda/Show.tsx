import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Calendar } from '@/Components/ui/calendar';
import {
    User,
    Calendar as CalendarIcon,
    Clock,
    Eye,
    ChevronLeft,
    ChevronRight,
    MapPin,
    Users,
} from 'lucide-react';
import { format, startOfMonth, endOfMonth, addMonths, subMonths, parseISO, isSameDay } from 'date-fns';
import { fr } from 'date-fns/locale';

interface User {
    id: number;
    uuid: string;
    name: string;
    first_name: string;
    last_name: string;
    avatar?: string;
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

interface PublicAgendaShowProps {
    user: User;
    appointments: CalendarEvent[];
    currentMonth: string;
}

export default function PublicAgendaShow({ user, appointments, currentMonth }: PublicAgendaShowProps) {
    const [selectedDate, setSelectedDate] = useState<Date>(new Date());
    const [currentViewMonth, setCurrentViewMonth] = useState<Date>(new Date(currentMonth));
    const [availableSlots, setAvailableSlots] = useState<AvailableSlot[]>([]);
    const [loadingSlots, setLoadingSlots] = useState(false);

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

    // Get appointments for the selected date
    const appointmentsForDate = appointments.filter(appointment =>
        isSameDay(parseISO(appointment.start), selectedDate)
    );

    // Load available slots for selected date
    const loadAvailableSlots = async (date: Date) => {
        setLoadingSlots(true);
        try {
            const response = await fetch(`/api/users/${user.uuid}/available-slots?date=${format(date, 'yyyy-MM-dd')}&duration=60`);
            const data = await response.json();

            if (data.success) {
                setAvailableSlots(data.available_slots);
            }
        } catch (error) {
            console.error('Error loading available slots:', error);
        } finally {
            setLoadingSlots(false);
        }
    };

    // Load slots when date changes
    React.useEffect(() => {
        loadAvailableSlots(selectedDate);
    }, [selectedDate, user.uuid]);

    const navigateMonth = (direction: 'prev' | 'next') => {
        const newMonth = direction === 'prev'
            ? subMonths(currentViewMonth, 1)
            : addMonths(currentViewMonth, 1);
        setCurrentViewMonth(newMonth);
    };

    // Check if a date has appointments
    const hasAppointments = (date: Date) => {
        return appointments.some(appointment =>
            isSameDay(parseISO(appointment.start), date)
        );
    };

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            <Head title={`Agenda de ${user.name}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-8">
                    <div className="flex items-center space-x-4 mb-4">
                        {user.avatar && (
                            <img
                                src={user.avatar}
                                alt={user.name}
                                className="h-16 w-16 rounded-full object-cover"
                            />
                        )}
                        {!user.avatar && (
                            <div className="h-16 w-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <User className="h-8 w-8 text-gray-400" />
                            </div>
                        )}
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Agenda de {user.name}
                            </h1>
                            <p className="text-gray-600 dark:text-gray-400">
                                Consultez les créneaux disponibles et les rendez-vous publics
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Calendar */}
                    <div className="lg:col-span-1">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle className="flex items-center space-x-2">
                                        <CalendarIcon className="h-5 w-5" />
                                        <span>Calendrier</span>
                                    </CardTitle>
                                    <div className="flex items-center space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => navigateMonth('prev')}
                                        >
                                            <ChevronLeft className="h-4 w-4" />
                                        </Button>
                                        <span className="text-sm font-medium px-3">
                                            {format(currentViewMonth, 'MMMM yyyy', { locale: fr })}
                                        </span>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => navigateMonth('next')}
                                        >
                                            <ChevronRight className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <Calendar
                                    mode="single"
                                    selected={selectedDate}
                                    onSelect={(date) => date && setSelectedDate(date)}
                                    month={currentViewMonth}
                                    onMonthChange={setCurrentViewMonth}
                                    locale={fr}
                                    className="rounded-md border"
                                    modifiers={{
                                        hasAppointments: (date) => hasAppointments(date)
                                    }}
                                    modifiersStyles={{
                                        hasAppointments: {
                                            backgroundColor: 'rgb(59 130 246 / 0.1)',
                                            color: 'rgb(59 130 246)',
                                            fontWeight: '600'
                                        }
                                    }}
                                />
                                <div className="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                    <div className="flex items-center space-x-2">
                                        <div className="w-3 h-3 rounded bg-blue-100 border border-blue-300"></div>
                                        <span>Jours avec rendez-vous publics</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Day Details */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Appointments for selected date */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Eye className="h-5 w-5" />
                                    <span>
                                        Rendez-vous du {format(selectedDate, 'EEEE d MMMM yyyy', { locale: fr })}
                                    </span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {appointmentsForDate.length === 0 ? (
                                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                        <CalendarIcon className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                        <p>Aucun rendez-vous public ce jour-là</p>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {appointmentsForDate.map((appointment) => (
                                            <div
                                                key={appointment.id}
                                                className="flex items-center justify-between p-4 border rounded-lg bg-white dark:bg-gray-800"
                                            >
                                                <div className="flex items-center space-x-4">
                                                    <div
                                                        className="w-4 h-4 rounded"
                                                        style={{ backgroundColor: appointment.backgroundColor }}
                                                    />
                                                    <div>
                                                        <h3 className="font-medium text-gray-900 dark:text-white">
                                                            {appointment.title}
                                                        </h3>
                                                        <div className="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                                            <div className="flex items-center space-x-1">
                                                                <Clock className="h-4 w-4" />
                                                                <span>{appointment.extendedProps.formatted_time}</span>
                                                            </div>
                                                            <div className="flex items-center space-x-1">
                                                                {getTypeIcon(appointment.extendedProps.type)}
                                                                <span>{getTypeLabel(appointment.extendedProps.type)}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <Badge className={getStatusColor(appointment.extendedProps.status)}>
                                                    {getStatusLabel(appointment.extendedProps.status)}
                                                </Badge>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Available slots */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Clock className="h-5 w-5" />
                                    <span>
                                        Créneaux disponibles le {format(selectedDate, 'EEEE d MMMM yyyy', { locale: fr })}
                                    </span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {loadingSlots ? (
                                    <div className="text-center py-8">
                                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                                        <p className="mt-2 text-gray-500 dark:text-gray-400">Chargement des créneaux...</p>
                                    </div>
                                ) : availableSlots.length === 0 ? (
                                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                        <Clock className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                        <p>Aucun créneau disponible ce jour-là</p>
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        {availableSlots.map((slot, index) => (
                                            <div
                                                key={index}
                                                className={`p-3 rounded-lg border text-center ${
                                                    slot.available
                                                        ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-200'
                                                        : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-700 dark:text-red-200'
                                                }`}
                                            >
                                                <div className="font-medium">{slot.formatted_time}</div>
                                                {!slot.available && slot.reason && (
                                                    <div className="text-xs mt-1 opacity-75">{slot.reason}</div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}

                                <div className="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <div className="flex items-start space-x-3">
                                        <div className="flex-shrink-0">
                                            <div className="w-5 h-5 rounded-full bg-blue-200 dark:bg-blue-700 flex items-center justify-center">
                                                <span className="text-blue-800 dark:text-blue-200 text-xs font-medium">i</span>
                                            </div>
                                        </div>
                                        <div className="text-sm text-blue-800 dark:text-blue-200">
                                            <p className="font-medium mb-1">À propos de cet agenda</p>
                                            <p>
                                                Cet agenda affiche uniquement les rendez-vous publics et les créneaux disponibles.
                                                Les créneaux en vert sont libres, ceux en rouge sont occupés.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    );
}