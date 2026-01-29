import React, { useState } from 'react';
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
    XCircleIcon,
    TicketIcon,
    ClipboardDocumentListIcon,
    QrCodeIcon,
    ChartBarIcon,
    PhotoIcon,
} from '@heroicons/react/24/outline';
import { format, parseISO, isPast } from 'date-fns';
import { fr } from 'date-fns/locale';
import { isAdmin } from '@/Enums/Role';
import { userHasPermission } from '@/Enums/Permission';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from '@/Components/ui/accordion';
import { TicketManager, RegistrationList, CheckInScanner, EventAnalyticsDashboard } from '@/Components/Event';
import { EventMediaGallery, EventBanner } from '@/Components/Events';
import { EventMedia } from '@/Types/event.d';

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
    media?: EventMedia[];
}

interface ShowProps extends PageProps {
    event: Event;
    banners?: EventMedia[];
    galleryImages?: EventMedia[];
    galleryVideos?: EventMedia[];
}

type TabType = 'details' | 'gallery' | 'tickets' | 'registrations' | 'checkin' | 'analytics';

const Show: React.FC<ShowProps> = ({ auth, event, banners = [], galleryImages = [], galleryVideos = [] }) => {
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [activeTab, setActiveTab] = useState<TabType>('details');
    const eventColor = event.color || '#3b82f6';

    const allGalleryMedia = [...galleryImages, ...galleryVideos];
    const startDate = parseISO(event.start_date);
    const endDate = parseISO(event.end_date);

    // Check if current user is participating
    const isParticipating = event.participants?.some(p => p.id === auth.user?.id) || false;
    const isEventFull = event.max_participants ? (event.participants?.length || 0) >= event.max_participants : false;
    const canRegister = !isParticipating && !isEventFull;

    // Check if event is past and user permissions
    const isEventPast = isPast(endDate);
    const isSuperAdmin = auth.user?.roles?.some(role => role.name === 'SuperAdmin') || false;

    // Permission checks
    const canEditEvent = (auth.user?.id === event.creator?.id ||
                        userHasPermission(auth.user, 'edit events')) &&
                        (!isEventPast || isSuperAdmin);

    const canDeleteEvent = (auth.user?.id === event.creator?.id ||
                          userHasPermission(auth.user, 'delete events')) &&
                          (!isEventPast || isSuperAdmin);

    const canParticipate = !isEventPast || isSuperAdmin;

    // Specific permission checks for each tab
    const canManageTickets = isSuperAdmin ||
        userHasPermission(auth.user, 'manage tickets') ||
        userHasPermission(auth.user, 'edit events') ||
        auth.user?.id === event.creator?.id;

    const canViewRegistrations = isSuperAdmin ||
        userHasPermission(auth.user, 'view registrations') ||
        userHasPermission(auth.user, 'manage registrations') ||
        auth.user?.id === event.creator?.id;

    const canCheckIn = isSuperAdmin ||
        userHasPermission(auth.user, 'checkin events') ||
        userHasPermission(auth.user, 'manage registrations') ||
        auth.user?.id === event.creator?.id;

    const canViewAnalytics = isSuperAdmin ||
        userHasPermission(auth.user, 'view analytics') ||
        userHasPermission(auth.user, 'view events') ||
        auth.user?.id === event.creator?.id;

    const hasMedia = allGalleryMedia.length > 0 || banners.length > 0;

    const tabs = [
        { id: 'details' as TabType, label: 'Détails', icon: CalendarIcon, show: true },
        { id: 'gallery' as TabType, label: `Galerie${hasMedia ? ` (${allGalleryMedia.length})` : ''}`, icon: PhotoIcon, show: hasMedia },
        { id: 'tickets' as TabType, label: 'Billets', icon: TicketIcon, show: canManageTickets },
        { id: 'registrations' as TabType, label: 'Inscriptions', icon: ClipboardDocumentListIcon, show: canViewRegistrations },
        { id: 'checkin' as TabType, label: 'Check-in', icon: QrCodeIcon, show: canCheckIn },
        { id: 'analytics' as TabType, label: 'Analytics', icon: ChartBarIcon, show: canViewAnalytics },
    ].filter(tab => tab.show);

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
        setDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        router.delete(route('events.destroy', event.uuid), {
            onSuccess: () => {
                setDeleteDialogOpen(false);
            },
        });
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

                {/* Hero Section with Color */}
                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-6">
                    {banners.length > 0 ? (
                        <div className="relative h-56 sm:h-72 md:h-96">
                            <img
                                src={banners[0].file_url || `/storage/${banners[0].file_path}`}
                                alt={event.title}
                                className="w-full h-full object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
                        </div>
                    ) : (
                        <div
                            className="h-2"
                            style={{ backgroundColor: eventColor }}
                        />
                    )}
                    <div className="p-6">
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
                    </div>
                </div>

                {/* Tabs Navigation */}
                {tabs.length > 1 && (
                    <div className="mb-6 border-b border-gray-200 dark:border-gray-700">
                        <nav className="flex space-x-4 overflow-x-auto" aria-label="Tabs">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors flex items-center gap-2 ${
                                        activeTab === tab.id
                                            ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'
                                    }`}
                                >
                                    <tab.icon className="h-4 w-4" />
                                    {tab.label}
                                </button>
                            ))}
                        </nav>
                    </div>
                )}

                {/* Tab Content */}
                {activeTab === 'details' && (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Description */}
                        {event.description && (
                            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                    Description
                                </h2>
                                <div className="prose dark:prose-invert max-w-none">
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                        {event.description}
                                    </p>
                                </div>
                            </div>
                        )}

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
                                <div className="rounded-xl bg-gray-50 dark:bg-gray-700/50 overflow-hidden">
                                    <Accordion>
                                        <AccordionItem value="participants" className="border-none">
                                            <AccordionTrigger className="px-4 py-4 hover:no-underline">
                                                <div className="flex items-center gap-4">
                                                    <div className="p-2 rounded-lg bg-white dark:bg-gray-700">
                                                        <UserGroupIcon className="w-6 h-6 text-icc-blue" />
                                                    </div>
                                                    <div className="text-left">
                                                        <div className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                            Participants
                                                        </div>
                                                        <div className="text-gray-900 dark:text-white font-medium">
                                                            {event.participants?.length || 0}{event.max_participants ? ` / ${event.max_participants}` : ''} inscrits
                                                        </div>
                                                    </div>
                                                </div>
                                            </AccordionTrigger>
                                                <AccordionContent>
                                                    {event.participants && event.participants.length > 0 && (
                                                        <div className="flex flex-wrap gap-2 px-4 pb-4">
                                                            {event.participants.map((participant) => (
                                                                <div
                                                                    key={participant.id}
                                                                    className="flex items-center gap-2 bg-white dark:bg-gray-700 rounded-full pl-1 pr-3 py-1 border border-gray-200 dark:border-gray-600"
                                                                >
                                                                    <div className="w-6 h-6 rounded-full bg-icc-blue text-white flex items-center justify-center text-xs font-medium">
                                                                        {(participant.first_name?.[0] || participant.name[0]).toUpperCase()}
                                                                    </div>
                                                                    <span className="text-sm text-gray-700 dark:text-gray-300">
                                                                        {participant.first_name && participant.last_name
                                                                            ? `${participant.first_name} ${participant.last_name}`
                                                                            : participant.name}
                                                                    </span>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                    {(!event.participants || event.participants.length === 0) && (
                                                        <div className="px-4 pb-4 text-sm text-gray-500 dark:text-gray-400">
                                                            Aucun participant inscrit pour le moment.
                                                        </div>
                                                    )}
                                                </AccordionContent>
                                            </AccordionItem>
                                        </Accordion>
                                    </div>
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
                                {canParticipate ? (
                                    isParticipating ? (
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
                                    )
                                ) : (
                                    <div className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg">
                                        <XCircleIcon className="w-5 h-5" />
                                        {isParticipating ? 'Événement terminé - Participation non modifiable' : 'Événement terminé - Inscription fermée'}
                                    </div>
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

                        {/* Banner Preview */}
                        {banners.length > 0 && (
                            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Banner
                                </h3>
                                <EventBanner
                                    banner={banners[0]}
                                    eventTitle={event.title}
                                    className="w-full"
                                />
                            </div>
                        )}
                    </div>
                </div>
                )}

                {/* Tickets Tab */}
                {activeTab === 'tickets' && (
                    <TicketManager eventId={event.uuid} />
                )}

                {/* Registrations Tab */}
                {activeTab === 'registrations' && (
                    <RegistrationList eventId={event.uuid} />
                )}

                {/* Check-in Tab */}
                {activeTab === 'checkin' && (
                    <CheckInScanner eventId={event.uuid} />
                )}

                {/* Analytics Tab */}
                {activeTab === 'analytics' && (
                    <EventAnalyticsDashboard eventId={event.uuid} />
                )}

                {/* Gallery Tab */}
                {activeTab === 'gallery' && (
                    <div className="space-y-6">
                        {/* Banner Accordion */}
                        {banners.length > 0 && (
                            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                                <Accordion>
                                    <AccordionItem value="banner">
                                        <AccordionTrigger className="text-xl font-semibold">
                                            <div className="flex items-center gap-2">
                                                <PhotoIcon className="h-5 w-5 text-gray-500" />
                                                Banner / Flyer
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="p-4">
                                            <EventBanner
                                                banner={banners[0]}
                                                eventTitle={event.title}
                                                className="max-w-sm mx-auto"
                                            />
                                        </AccordionContent>
                                    </AccordionItem>
                                </Accordion>
                            </div>
                        )}

                        {/* Media Gallery */}
                        {allGalleryMedia.length > 0 && (
                            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                    Photos et Vidéos
                                </h2>
                                <EventMediaGallery
                                    media={allGalleryMedia}
                                    eventUuid={event.uuid}
                                    canEdit={canEditEvent}
                                />
                            </div>
                        )}
                    </div>
                )}
            </div>

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={confirmDelete}
                title="Supprimer l'événement"
                description={`Êtes-vous sûr de vouloir supprimer l'événement "${event.title}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
};

export default Show;