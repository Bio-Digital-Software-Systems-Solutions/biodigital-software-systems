import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import ViewSwitcher from '@/Components/ViewSwitcher';
import CalendarView from '@/Components/CalendarView';
import {
    PlusIcon,
    CalendarDaysIcon,
    MapPinIcon,
    UsersIcon,
    ClockIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon
} from '@heroicons/react/24/outline';
import { userHasPermission } from '@/Enums/Permission';
import { isPast, parseISO } from 'date-fns';

type ViewMode = 'grid' | 'list' | 'calendar';

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
    creator: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
    };
    address?: {
        street: string;
        city: string;
        postal_code: string;
        country: string;
    };
    participants: Array<{
        id: number;
        first_name: string;
        last_name: string;
    }>;
    created_at: string;
    updated_at: string;
}

interface EventsPageProps extends PageProps {
    events: {
        data: Event[];
        links: any[];
        meta: any;
    };
}

export default function Index() {
    const { events, auth } = usePage<EventsPageProps>().props;
    const [viewMode, setViewMode] = useState<ViewMode>('grid');

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'planned':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            case 'ongoing':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'completed':
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
            case 'cancelled':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'planned':
                return 'Planifié';
            case 'ongoing':
                return 'En cours';
            case 'completed':
                return 'Terminé';
            case 'cancelled':
                return 'Annulé';
            default:
                return status;
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const canManageEvents = userHasPermission(auth.user, 'create events');

    // Check if user can modify event (past events only for SuperAdmin)
    const canModifyEvent = (event: Event) => {
        const isEventPast = isPast(parseISO(event.end_date));
        const isSuperAdmin = auth.user?.roles?.some(role => role.name === 'SuperAdmin') || false;
        const hasEditPermission = auth.user?.id === event.creator.id || userHasPermission(auth.user, 'edit events');

        return hasEditPermission && (!isEventPast || isSuperAdmin);
    };

    return (
        <DashboardLayout
            title="Événements"
            description="Gérez et participez aux événements de votre organisation"
            actions={
                <>
                    <ViewSwitcher currentView={viewMode} onViewChange={(view) => setViewMode(view)} showCalendar={true} />
                    {canManageEvents && (
                        <Link
                            href={route('events.create')}
                            className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                        >
                            <PlusIcon className="h-5 w-5 mr-2" />
                            Nouvel événement
                        </Link>
                    )}
                </>
            }
        >
            <Head title="Événements - AIG-App" />

            {/* Calendar View */}
                {viewMode === 'calendar' && (
                    <CalendarView
                        events={events.data}
                        canCreateEvents={canManageEvents}
                    />
                )}

                {/* List View */}
                {viewMode === 'list' && (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Événement
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Lieu
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Participants
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Statut
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {events.data.map((event) => (
                                        <tr key={event.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <Link
                                                        href={route('events.show', event.uuid)}
                                                        className="text-sm font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 transition-colors"
                                                    >
                                                        {event.title}
                                                    </Link>
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">
                                                        Par {event.creator.full_name}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center text-sm text-gray-900 dark:text-white">
                                                    <ClockIcon className="h-4 w-4 mr-2 text-gray-400" />
                                                    {formatDate(event.start_date)}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center text-sm text-gray-900 dark:text-white">
                                                    <MapPinIcon className="h-4 w-4 mr-2 text-gray-400 flex-shrink-0" />
                                                    <span className="truncate max-w-xs">
                                                        {event.location || (event.address ? `${event.address.street}, ${event.address.city}` : 'Non spécifié')}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center text-sm text-gray-900 dark:text-white">
                                                    <UsersIcon className="h-4 w-4 mr-2 text-gray-400" />
                                                    {event.participants.length}
                                                    {event.max_participants && ` / ${event.max_participants}`}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(event.status)}`}>
                                                    {getStatusLabel(event.status)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link
                                                        href={route('events.show', event.uuid)}
                                                        className="text-primary dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                        title="Voir détails"
                                                    >
                                                        <EyeIcon className="h-5 w-5" />
                                                    </Link>
                                                    {canModifyEvent(event) && (
                                                        <>
                                                            <Link
                                                                href={route('events.edit', event.uuid)}
                                                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                                title="Modifier"
                                                            >
                                                                <PencilIcon className="h-5 w-5" />
                                                            </Link>
                                                            <Link
                                                                href={route('events.destroy', event.uuid)}
                                                                method="delete"
                                                                as="button"
                                                                className="text-gray-400 hover:text-red-600"
                                                                data-confirm="Êtes-vous sûr de vouloir supprimer cet événement ?"
                                                                title="Supprimer"
                                                            >
                                                                <TrashIcon className="h-5 w-5" />
                                                            </Link>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {events.data.length === 0 && (
                            <div className="text-center py-12">
                                <CalendarDaysIcon className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Aucun événement
                                </h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Commencez par créer votre premier événement.
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Grid View */}
                {viewMode === 'grid' && (
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {events.data.map((event) => (
                            <div
                                key={event.id}
                                className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden"
                            >
                                {/* Event Header */}
                                <div className="p-6">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <Link
                                                href={route('events.show', event.uuid)}
                                                className="text-lg font-semibold text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 transition-colors"
                                            >
                                                {event.title}
                                            </Link>
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                Par {event.creator.full_name}
                                            </p>
                                        </div>
                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(event.status)}`}>
                                            {getStatusLabel(event.status)}
                                        </span>
                                    </div>

                                    {event.description && (
                                        <p className="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                            {event.description}
                                        </p>
                                    )}

                                    {/* Event Details */}
                                    <div className="mt-4 space-y-2">
                                        <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                            <ClockIcon className="h-4 w-4 mr-2" />
                                            <span>{formatDate(event.start_date)}</span>
                                        </div>

                                        {(event.location || event.address) && (
                                            <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                <MapPinIcon className="h-4 w-4 mr-2" />
                                                <span>
                                                    {event.location ||
                                                     `${event.address?.street}, ${event.address?.city}`}
                                                </span>
                                            </div>
                                        )}

                                        <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                            <UsersIcon className="h-4 w-4 mr-2" />
                                            <span>
                                                {event.participants.length}
                                                {event.max_participants && ` / ${event.max_participants}`} participants
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {/* Event Actions */}
                                <div className="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                                    <div className="flex items-center justify-between">
                                        <Link
                                            href={route('events.show', event.uuid)}
                                            className="inline-flex items-center text-sm text-primary dark:text-blue-400 hover:text-primary dark:hover:text-blue-300"
                                        >
                                            <EyeIcon className="h-4 w-4 mr-1" />
                                            Voir détails
                                        </Link>

                                        {canModifyEvent(event) && (
                                            <div className="flex space-x-2">
                                                <Link
                                                    href={route('events.edit', event.uuid)}
                                                    className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                >
                                                    <PencilIcon className="h-4 w-4" />
                                                </Link>
                                                <Link
                                                    href={route('events.destroy', event.uuid)}
                                                    method="delete"
                                                    as="button"
                                                    className="text-gray-400 hover:text-red-600"
                                                    data-confirm="Êtes-vous sûr de vouloir supprimer cet événement ?"
                                                >
                                                    <TrashIcon className="h-4 w-4" />
                                                </Link>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Empty State for Grid View */}
                {viewMode === 'grid' && events.data.length === 0 && (
                    <div className="text-center py-12">
                        <CalendarDaysIcon className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            Aucun événement
                        </h3>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Commencez par créer votre premier événement.
                        </p>
                        {canManageEvents && (
                            <div className="mt-6">
                                <Link
                                    href={route('events.create')}
                                    className="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary text-white font-medium rounded-lg transition duration-200"
                                >
                                    <PlusIcon className="h-5 w-5 mr-2" />
                                    Nouvel événement
                                </Link>
                            </div>
                        )}
                    </div>
                )}

                {/* Pagination */}
                {events.data.length > 0 && events.meta?.last_page > 1 && (
                    <div className="mt-8 flex justify-center">
                        <nav className="flex space-x-2">
                            {events.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`px-3 py-2 text-sm font-medium rounded-lg ${
                                        link.active
                                            ? 'bg-primary text-white'
                                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700'
                                    } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </nav>
                    </div>
                )}
        </DashboardLayout>
    );
}