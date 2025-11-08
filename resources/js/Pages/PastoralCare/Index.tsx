import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import {
    CalendarIcon,
    ClockIcon,
    UserGroupIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
    PhoneIcon,
    EnvelopeIcon,
    MapPinIcon,
    VideoCameraIcon
} from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface PastoralCareAppointment {
    id: number;
    uuid: string;
    user?: User;
    pastor: User;
    appointment_date: string;
    appointment_time: string;
    duration_minutes: number;
    status: 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';
    location_type: 'in_person' | 'zoom' | 'hybrid';
    zoom_link?: string;
    client_name: string;
    client_email: string;
    client_phone?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface Stats {
    total_appointments: number;
    pending_appointments: number;
    confirmed_appointments: number;
    completed_appointments: number;
    cancelled_appointments: number;
    this_week_appointments: number;
    next_week_appointments: number;
}

interface PaginatedAppointments {
    data: PastoralCareAppointment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Props {
    appointments: PaginatedAppointments;
    stats: Stats;
    canManageAll?: boolean;
    permissions?: {
        canCreate: boolean;
        canEdit: boolean;
        canDelete: boolean;
        canManage: boolean;
    };
    auth: {
        user: User;
    };
}

export default function Index({ appointments, stats, canManageAll, permissions, auth }: Props) {
    const [activeTab, setActiveTab] = useState('dashboard');

    const formatDate = (dateString: string) => {
        return format(new Date(dateString), 'EEEE d MMMM yyyy', { locale: fr });
    };

    const formatTime = (timeString: string) => {
        return format(new Date(timeString), 'HH:mm', { locale: fr });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">En attente</Badge>;
            case 'confirmed':
                return <Badge variant="default" className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Confirmé</Badge>;
            case 'completed':
                return <Badge variant="outline" className="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Terminé</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Annulé</Badge>;
            case 'no_show':
                return <Badge variant="destructive" className="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Absent</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const getLocationIcon = (locationType: string) => {
        switch (locationType) {
            case 'zoom':
                return <VideoCameraIcon className="h-4 w-4 text-blue-600" />;
            case 'hybrid':
                return <div className="flex space-x-1">
                    <MapPinIcon className="h-3 w-3 text-green-600" />
                    <VideoCameraIcon className="h-3 w-3 text-blue-600" />
                </div>;
            default:
                return <MapPinIcon className="h-4 w-4 text-green-600" />;
        }
    };

    const handleStatusUpdate = async (appointmentUuid: string, newStatus: string) => {
        try {
            await router.patch(`/pastoral-care/appointments/${appointmentUuid}`, {
                status: newStatus
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Statut mis à jour avec succès');
                },
                onError: () => {
                    toast.error('Erreur lors de la mise à jour du statut');
                }
            });
        } catch (error) {
            toast.error('Erreur lors de la mise à jour du statut');
        }
    };

    const filteredAppointments = (status?: string) => {
        if (!status) return appointments.data;
        return appointments.data.filter(appointment => appointment.status === status);
    };

    const upcomingAppointments = appointments.data
        .filter(apt => apt.status !== 'cancelled' && new Date(apt.appointment_date) >= new Date())
        .sort((a, b) => new Date(a.appointment_date).getTime() - new Date(b.appointment_date).getTime())
        .slice(0, 5);

    return (
        <DashboardLayout
            title="Soin Pastoral"
            actions={
                <div className="flex items-center space-x-3">
                    <Button
                        onClick={() => router.visit('/pastoral-care/appointments/create')}
                        className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md"
                    >
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Nouveau rendez-vous
                    </Button>
                    <Button
                        onClick={() => router.visit('/pastoral-care/book')}
                        className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center shadow-md"
                    >
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Réserver en ligne
                    </Button>
                </div>
            }
        >
            <Head title="Soin Pastoral - Dashboard" />

            <div className="py-6">
                <div className="mx-auto sm:px-6 lg:px-8">
                    <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
                        <TabsList className="grid w-full grid-cols-4">
                            <TabsTrigger value="dashboard">Dashboard</TabsTrigger>
                            <TabsTrigger value="pending">En attente ({stats.pending_appointments})</TabsTrigger>
                            <TabsTrigger value="confirmed">Confirmés ({stats.confirmed_appointments})</TabsTrigger>
                            <TabsTrigger value="all">Tous ({stats.total_appointments})</TabsTrigger>
                        </TabsList>

                        {/* Dashboard Tab */}
                        <TabsContent value="dashboard" className="space-y-6">
                            {/* Statistics Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Total</CardTitle>
                                        <UserGroupIcon className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.total_appointments}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Rendez-vous au total
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">En attente</CardTitle>
                                        <ClockIcon className="h-4 w-4 text-yellow-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-yellow-600">{stats.pending_appointments}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Nécessitent une action
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Cette semaine</CardTitle>
                                        <CalendarIcon className="h-4 w-4 text-blue-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-blue-600">{stats.this_week_appointments}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Rendez-vous programmés
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Terminés</CardTitle>
                                        <CheckCircleIcon className="h-4 w-4 text-green-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-green-600">{stats.completed_appointments}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Accompagnements réalisés
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Upcoming Appointments */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Prochains rendez-vous</CardTitle>
                                    <CardDescription>
                                        Vos 5 prochains rendez-vous confirmés ou en attente
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {upcomingAppointments.length === 0 ? (
                                        <div className="text-center py-8">
                                            <CalendarIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Aucun rendez-vous à venir
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {upcomingAppointments.map((appointment) => (
                                                <div
                                                    key={appointment.id}
                                                    className="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                                >
                                                    <div className="flex items-center space-x-4">
                                                        <div className="flex-shrink-0">
                                                            <div className="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center">
                                                                <span className="text-white font-semibold text-sm">
                                                                    {appointment.client_name.charAt(0)}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h3 className="font-medium text-gray-900 dark:text-white">
                                                                {appointment.client_name}
                                                            </h3>
                                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                                {formatDate(appointment.appointment_date)} à {formatTime(appointment.appointment_time)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center space-x-3">
                                                        {getLocationIcon(appointment.location_type)}
                                                        {getStatusBadge(appointment.status)}
                                                        <div className="flex space-x-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => router.visit(`/pastoral-care/appointments/${appointment.uuid}`)}
                                                            >
                                                                <EyeIcon className="h-4 w-4" />
                                                            </Button>
                                                            {(permissions?.canEdit ?? true) && appointment.status === 'pending' && (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => router.visit(`/pastoral-care/appointments/${appointment.uuid}/edit`)}
                                                                >
                                                                    <PencilIcon className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Pending Appointments Tab */}
                        <TabsContent value="pending" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Rendez-vous en attente de confirmation</CardTitle>
                                    <CardDescription>
                                        Ces rendez-vous nécessitent votre attention
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {filteredAppointments('pending').length === 0 ? (
                                        <div className="text-center py-8">
                                            <CheckCircleIcon className="h-12 w-12 text-green-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Aucun rendez-vous en attente
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {filteredAppointments('pending').map((appointment) => (
                                                <AppointmentCard
                                                    key={appointment.id}
                                                    appointment={appointment}
                                                    onStatusUpdate={handleStatusUpdate}
                                                    showActions={true}
                                                    permissions={permissions}
                                                />
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Confirmed Appointments Tab */}
                        <TabsContent value="confirmed" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Rendez-vous confirmés</CardTitle>
                                    <CardDescription>
                                        Rendez-vous confirmés et à venir
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {filteredAppointments('confirmed').length === 0 ? (
                                        <div className="text-center py-8">
                                            <CalendarIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Aucun rendez-vous confirmé
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {filteredAppointments('confirmed').map((appointment) => (
                                                <AppointmentCard
                                                    key={appointment.id}
                                                    appointment={appointment}
                                                    onStatusUpdate={handleStatusUpdate}
                                                    showActions={true}
                                                    permissions={permissions}
                                                />
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* All Appointments Tab */}
                        <TabsContent value="all" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Tous les rendez-vous</CardTitle>
                                    <CardDescription>
                                        Historique complet de vos rendez-vous de soin pastoral
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {appointments.data.length === 0 ? (
                                        <div className="text-center py-8">
                                            <UserGroupIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400 mb-4">
                                                Aucun rendez-vous enregistré
                                            </p>
                                            <Button
                                                onClick={() => router.visit('/pastoral-care/appointments/create')}
                                                className="bg-blue-600 hover:bg-blue-700 text-white"
                                            >
                                                <PlusIcon className="h-4 w-4 mr-2" />
                                                Créer un rendez-vous
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {appointments.data.map((appointment) => (
                                                <AppointmentCard
                                                    key={appointment.id}
                                                    appointment={appointment}
                                                    onStatusUpdate={handleStatusUpdate}
                                                    showActions={true}
                                                    permissions={permissions}
                                                />
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </DashboardLayout>
    );
}

// Reusable Appointment Card Component
interface AppointmentCardProps {
    appointment: PastoralCareAppointment;
    onStatusUpdate: (uuid: string, status: string) => void;
    showActions?: boolean;
    permissions?: {
        canEdit: boolean;
    };
}

function AppointmentCard({ appointment, onStatusUpdate, showActions = false, permissions }: AppointmentCardProps) {
    const formatDate = (dateString: string) => {
        return format(new Date(dateString), 'EEEE d MMMM yyyy', { locale: fr });
    };

    const formatTime = (timeString: string) => {
        return format(new Date(timeString), 'HH:mm', { locale: fr });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">En attente</Badge>;
            case 'confirmed':
                return <Badge variant="default" className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Confirmé</Badge>;
            case 'completed':
                return <Badge variant="outline" className="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Terminé</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Annulé</Badge>;
            case 'no_show':
                return <Badge variant="destructive" className="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Absent</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const getLocationIcon = (locationType: string) => {
        switch (locationType) {
            case 'zoom':
                return <VideoCameraIcon className="h-4 w-4 text-blue-600" />;
            case 'hybrid':
                return <div className="flex space-x-1">
                    <MapPinIcon className="h-3 w-3 text-green-600" />
                    <VideoCameraIcon className="h-3 w-3 text-blue-600" />
                </div>;
            default:
                return <MapPinIcon className="h-4 w-4 text-green-600" />;
        }
    };

    return (
        <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <div className="flex items-start justify-between mb-4">
                <div className="flex items-center space-x-4">
                    <div className="flex-shrink-0">
                        <div className="h-12 w-12 bg-blue-600 rounded-full flex items-center justify-center">
                            <span className="text-white font-semibold">
                                {appointment.client_name.charAt(0)}
                            </span>
                        </div>
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 dark:text-white text-lg">
                            {appointment.client_name}
                        </h3>
                        <div className="flex items-center space-x-4 mt-1">
                            <p className="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                <EnvelopeIcon className="h-4 w-4 mr-1" />
                                {appointment.client_email}
                            </p>
                            {appointment.client_phone && (
                                <p className="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                    <PhoneIcon className="h-4 w-4 mr-1" />
                                    {appointment.client_phone}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
                <div className="flex items-center space-x-2">
                    {getLocationIcon(appointment.location_type)}
                    {getStatusBadge(appointment.status)}
                </div>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Date</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {formatDate(appointment.appointment_date)}
                    </p>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Heure</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {formatTime(appointment.appointment_time)} ({appointment.duration_minutes}min)
                    </p>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Type</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {appointment.location_type === 'zoom' ? 'Visioconférence' :
                         appointment.location_type === 'hybrid' ? 'Hybride' : 'En présentiel'}
                    </p>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Créé le</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {format(new Date(appointment.created_at), 'd/M/yyyy', { locale: fr })}
                    </p>
                </div>
            </div>

            {appointment.notes && (
                <div className="mb-4">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</p>
                    <p className="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 p-2 rounded">
                        {appointment.notes}
                    </p>
                </div>
            )}

            <div className="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <div className="flex space-x-2">
                    {showActions && appointment.status === 'pending' && (
                        <>
                            <Button
                                size="sm"
                                onClick={() => onStatusUpdate(appointment.uuid, 'confirmed')}
                                className="bg-green-600 hover:bg-green-700 text-white"
                            >
                                <CheckCircleIcon className="h-4 w-4 mr-1" />
                                Confirmer
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => onStatusUpdate(appointment.uuid, 'cancelled')}
                                className="text-red-600 border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                            >
                                <XCircleIcon className="h-4 w-4 mr-1" />
                                Annuler
                            </Button>
                        </>
                    )}

                    {showActions && appointment.status === 'confirmed' && (
                        <>
                            <Button
                                size="sm"
                                onClick={() => onStatusUpdate(appointment.uuid, 'completed')}
                                className="bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                <CheckCircleIcon className="h-4 w-4 mr-1" />
                                Marquer terminé
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => onStatusUpdate(appointment.uuid, 'no_show')}
                                className="text-orange-600 border-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20"
                            >
                                <XCircleIcon className="h-4 w-4 mr-1" />
                                Absent
                            </Button>
                        </>
                    )}
                </div>

                <div className="flex space-x-2">
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => router.visit(`/pastoral-care/appointments/${appointment.uuid}`)}
                    >
                        <EyeIcon className="h-4 w-4 mr-1" />
                        Voir
                    </Button>
                    {(permissions?.canEdit ?? true) && appointment.status === 'pending' && (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => router.visit(`/pastoral-care/appointments/${appointment.uuid}/edit`)}
                        >
                            <PencilIcon className="h-4 w-4 mr-1" />
                            Modifier
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}