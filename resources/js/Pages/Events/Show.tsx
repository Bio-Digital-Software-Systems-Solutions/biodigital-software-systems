import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import {
    CalendarIcon,
    ClockIcon,
    MapPinIcon,
    UserGroupIcon,
    GlobeAltIcon,
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/react/24/outline';
import { format, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';
import { isAdmin } from '@/Enums/Role';
import { userHasPermission } from '@/Enums/Permission';

interface Address {
    id: number;
    street?: string;
    city?: string;
    postal_code?: string;
    country?: string;
}

interface Creator {
    id: number;
    name: string;
    first_name?: string;
    last_name?: string;
    email: string;
}

interface Participant {
    id: number;
    name: string;
    first_name?: string;
    last_name?: string;
    pivot?: {
        registered_at: string;
        attended: boolean;
    };
}

interface Event {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    start_date: string;
    end_date: string;
    location?: string;
    max_participants?: number;
    is_public: boolean;
    status: string;
    color?: string;
    creator?: Creator;
    address?: Address;
    participants?: Participant[];
}

interface ShowProps extends PageProps {
    event: Event;
}

const Show: React.FC<ShowProps> = ({ auth, event }) => {
    const eventColor = event.color || '#3b82f6';
    const startDate = parseISO(event.start_date);
    const endDate = parseISO(event.end_date);

    // Check if current user is participating
    const isParticipating = event.participants?.some(p => p.id === auth.user?.id) || false;
    const isEventFull = event.max_participants ? (event.participants?.length || 0) >= event.max_participants : false;
    const canRegister = !isParticipating && !isEventFull;

    // Permission checks
    const canEditEvent = auth.user?.id === event.creator?.id ||
                        userHasPermission(auth.user, 'edit events');

    const canDeleteEvent = auth.user?.id === event.creator?.id ||
                          userHasPermission(auth.user, 'delete events');

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            planned: { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-800 dark:text-blue-300', label: 'Planifié' },
            ongoing: { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-800 dark:text-green-300', label: 'En cours' },
            completed: { bg: 'bg-gray-100 dark:bg-gray-900/30', text: 'text-gray-800 dark:text-gray-300', label: 'Terminé' },
            cancelled: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-800 dark:text-red-300', label: 'Annulé' },
        };

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.planned;

        return (
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${config.bg} ${config.text}`}>
                {config.label}
            </span>
        );
    };

    const handleDelete = () => {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
            router.delete(route('events.destroy', event.uuid));
        }
    };

    const handleToggleParticipation = () => {
        router.post(route('events.toggle-participation', event.uuid), {}, {
            preserveScroll: true,
        });
    };

    const handleAddToCalendar = () => {
        // Generate .ics file format
        const formatDateForICS = (date: Date) => {
            return date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
        };

        const icsContent = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AIG-App//Event//EN',
            'BEGIN:VEVENT',
            `UID:${event.id}@aig-app.com`,
            `DTSTAMP:${formatDateForICS(new Date())}`,
            `DTSTART:${formatDateForICS(startDate)}`,
            `DTEND:${formatDateForICS(endDate)}`,
            `SUMMARY:${event.title}`,
            event.description ? `DESCRIPTION:${event.description.replace(/\n/g, '\\n')}` : '',
            event.location ? `LOCATION:${event.location}` : '',
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR'
        ].filter(Boolean).join('\r\n');

        // Create blob and download
        const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = `${event.title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.ics`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    return (
        <DashboardLayout>
            <Head title={event.title} />

            <div className="p-4">
                {/* Back Button */}
                <div className="mb-6">
                    <Link
                        href={route('events.index')}
                        className="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon className="w-4 h-4 mr-2" />
                        Retour aux événements
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Hero Section with Color */}
                        <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                            <div
                                className="h-2"
                                style={{ backgroundColor: eventColor }}
                            />
                            <div className="p-8">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="flex-1">
                                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-3">
                                            {event.title}
                                        </h1>
                                        <div className="flex items-center gap-3">
                                            {getStatusBadge(event.status)}
                                            {event.is_public ? (
                                                <span className="inline-flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                                                    <GlobeAltIcon className="w-4 h-4" />
                                                    Public
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                                                    <XCircleIcon className="w-4 h-4" />
                                                    Privé
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Action Buttons */}
                                    {(canEditEvent || canDeleteEvent) && (
                                        <div className="flex items-center gap-2">
                                            {canEditEvent && (
                                                <Link
                                                    href={route('events.edit', event.uuid)}
                                                    className="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                                    title="Modifier"
                                                >
                                                    <PencilIcon className="w-5 h-5 text-gray-600 dark:text-gray-300" />
                                                </Link>
                                            )}
                                            {canDeleteEvent && (
                                                <button
                                                    onClick={handleDelete}
                                                    className="p-2 rounded-lg bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                                    title="Supprimer"
                                                >
                                                    <TrashIcon className="w-5 h-5 text-red-600 dark:text-red-400" />
                                                </button>
                                            )}
                                        </div>
                                    )}
                                </div>

                                {/* Description */}
                                {event.description && (
                                    <div className="mt-6 prose dark:prose-invert max-w-none">
                                        <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                            {event.description}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Event Details Card */}
                        <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                                Détails de l'événement
                            </h2>

                            <div className="space-y-5">
                                {/* Date & Time */}
                                <div className="flex items-start gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                                    <div className="p-2 rounded-lg bg-white dark:bg-gray-700">
                                        <CalendarIcon className="w-6 h-6 text-icc-blue" />
                                    </div>
                                    <div className="flex-1">
                                        <div className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            Date et heure
                                        </div>
                                        <div className="text-gray-900 dark:text-white font-medium">
                                            {format(startDate, "EEEE d MMMM yyyy", { locale: fr })}
                                        </div>
                                        <div className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {format(startDate, "HH:mm")} - {format(endDate, "HH:mm")}
                                        </div>
                                    </div>
                                </div>

                                {/* Location */}
                                {event.location && (
                                    <div className="flex items-start gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                                        <div className="p-2 rounded-lg bg-white dark:bg-gray-700">
                                            <MapPinIcon className="w-6 h-6 text-icc-blue" />
                                        </div>
                                        <div className="flex-1">
                                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Lieu
                                            </div>
                                            <div className="text-gray-900 dark:text-white font-medium">
                                                {event.location}
                                            </div>
                                            {event.address && (
                                                <div className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    {[
                                                        event.address.street,
                                                        event.address.postal_code,
                                                        event.address.city,
                                                        event.address.country
                                                    ].filter(Boolean).join(', ')}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Participants */}
                                {event.max_participants && (
                                    <div className="flex items-start gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                                        <div className="p-2 rounded-lg bg-white dark:bg-gray-700">
                                            <UserGroupIcon className="w-6 h-6 text-icc-blue" />
                                        </div>
                                        <div className="flex-1">
                                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                Participants
                                            </div>
                                            <div className="text-gray-900 dark:text-white font-medium">
                                                {event.participants?.length || 0} / {event.max_participants} inscrits
                                            </div>
                                            {event.participants && event.participants.length > 0 && (
                                                <div className="flex -space-x-2 mt-3">
                                                    {event.participants.slice(0, 5).map((participant) => (
                                                        <div
                                                            key={participant.id}
                                                            className="w-8 h-8 rounded-full bg-icc-blue text-white flex items-center justify-center text-xs font-medium border-2 border-white dark:border-gray-800"
                                                            title={`${participant.first_name || ''} ${participant.last_name || participant.name}`}
                                                        >
                                                            {(participant.first_name?.[0] || participant.name[0]).toUpperCase()}
                                                        </div>
                                                    ))}
                                                    {event.participants.length > 5 && (
                                                        <div className="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center text-xs font-medium border-2 border-white dark:border-gray-800">
                                                            +{event.participants.length - 5}
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Quick Actions Card */}
                        <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Actions rapides
                            </h3>
                            <div className="space-y-2">
                                {isParticipating ? (
                                    <button
                                        onClick={handleToggleParticipation}
                                        className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium"
                                    >
                                        <XCircleIcon className="w-5 h-5" />
                                        Se désinscrire
                                    </button>
                                ) : (
                                    <button
                                        onClick={handleToggleParticipation}
                                        disabled={isEventFull}
                                        className={`w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors font-medium ${
                                            isEventFull
                                                ? 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                : 'bg-icc-blue text-white hover:bg-icc-blue/90'
                                        }`}
                                    >
                                        <CheckCircleIcon className="w-5 h-5" />
                                        {isEventFull ? 'Événement complet' : 'S\'inscrire à l\'événement'}
                                    </button>
                                )}
                                <button
                                    onClick={handleAddToCalendar}
                                    className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium"
                                >
                                    <CalendarIcon className="w-5 h-5" />
                                    Ajouter au calendrier
                                </button>
                            </div>
                        </div>

                        {/* Participants List */}
                        {event.participants && event.participants.length > 0 && (
                            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Participants ({event.participants.length})
                                </h3>
                                <div className="space-y-3 max-h-64 overflow-y-auto">
                                    {event.participants.map((participant) => (
                                        <div key={participant.id} className="flex items-center gap-3">
                                            <div className="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center justify-center text-sm font-medium">
                                                {(participant.first_name?.[0] || participant.name[0]).toUpperCase()}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {participant.first_name && participant.last_name
                                                        ? `${participant.first_name} ${participant.last_name}`
                                                        : participant.name}
                                                </div>
                                            </div>
                                            {participant.pivot?.attended && (
                                                <CheckCircleIcon className="w-5 h-5 text-green-500" title="A participé" />
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
};

export default Show;