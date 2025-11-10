import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import {
    CalendarIcon,
    ClockIcon,
    UserIcon,
    MapPinIcon,
    VideoCameraIcon,
    PhoneIcon,
    EnvelopeIcon,
    PencilIcon,
    ArrowLeftIcon,
    CheckCircleIcon,
    XCircleIcon,
    DocumentTextIcon
} from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

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
    pastor_notes?: string;
    cancellation_reason?: string;
    created_at: string;
    updated_at: string;
    cancelled_at?: string;
    confirmation_sent_at?: string;
    reminder_sent_at?: string;
}

interface Props {
    appointment: PastoralCareAppointment;
    canEdit: boolean;
    canConfirm: boolean;
    canCancel: boolean;
    auth: {
        user: User;
    };
}

export default function Show({ appointment, canEdit, canConfirm, canCancel, auth }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [pastorNotes, setPastorNotes] = useState(appointment.pastor_notes || '');
    const [isUpdatingNotes, setIsUpdatingNotes] = useState(false);
    const [isUpdatingStatus, setIsUpdatingStatus] = useState(false);

    const formatDate = (dateString: string) => {
        return format(new Date(dateString), 'EEEE d MMMM yyyy', { locale: fr });
    };

    const formatTime = (timeString: string) => {
        return format(new Date(timeString), 'HH:mm', { locale: fr });
    };

    const formatDateTime = (dateString: string) => {
        return format(new Date(dateString), 'd MMMM yyyy à HH:mm', { locale: fr });
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

    const getLocationIcon = () => {
        switch (appointment.location_type) {
            case 'zoom':
                return <VideoCameraIcon className="h-5 w-5 text-blue-600" />;
            case 'hybrid':
                return <div className="flex space-x-1">
                    <MapPinIcon className="h-4 w-4 text-green-600" />
                    <VideoCameraIcon className="h-4 w-4 text-blue-600" />
                </div>;
            default:
                return <MapPinIcon className="h-5 w-5 text-green-600" />;
        }
    };

    const getLocationText = () => {
        switch (appointment.location_type) {
            case 'zoom':
                return 'Visioconférence';
            case 'hybrid':
                return 'Hybride (présentiel + visio)';
            default:
                return 'En présentiel';
        }
    };

    const handleConfirm = async () => {
        setIsUpdatingStatus(true);

        try {
            const response = await fetch(`/api/pastoral-care/appointments/${appointment.uuid}/confirm`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message || 'Rendez-vous confirmé avec succès');
                router.reload({ only: ['appointment'] });
            } else {
                toast.error(data.message || 'Erreur lors de la confirmation');
            }
        } catch (error) {
            toast.error('Erreur lors de la confirmation du rendez-vous');
        } finally {
            setIsUpdatingStatus(false);
        }
    };

    const handleCancel = async () => {
        setIsUpdatingStatus(true);

        try {
            const response = await fetch(`/api/pastoral-care/appointments/${appointment.uuid}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    cancellation_reason: 'Annulé par le pasteur'
                })
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message || 'Rendez-vous annulé avec succès');
                router.reload({ only: ['appointment'] });
            } else {
                toast.error(data.message || 'Erreur lors de l\'annulation');
            }
        } catch (error) {
            toast.error('Erreur lors de l\'annulation du rendez-vous');
        } finally {
            setIsUpdatingStatus(false);
        }
    };

    const handleComplete = async () => {
        setIsUpdatingStatus(true);

        try {
            const response = await fetch(`/api/pastoral-care/appointments/${appointment.uuid}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message || 'Rendez-vous marqué comme terminé');
                router.reload({ only: ['appointment'] });
            } else {
                toast.error(data.message || 'Erreur lors de la finalisation');
            }
        } catch (error) {
            toast.error('Erreur lors de la finalisation du rendez-vous');
        } finally {
            setIsUpdatingStatus(false);
        }
    };

    const handleNoShow = async () => {
        setIsUpdatingStatus(true);

        try {
            const response = await fetch(`/api/pastoral-care/appointments/${appointment.uuid}/no-show`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message || 'Rendez-vous marqué comme absence');
                router.reload({ only: ['appointment'] });
            } else {
                toast.error(data.message || 'Erreur lors du marquage comme absent');
            }
        } catch (error) {
            toast.error('Erreur lors du marquage comme absent');
        } finally {
            setIsUpdatingStatus(false);
        }
    };

    const handleNotesUpdate = async () => {
        if (!pastorNotes.trim()) {
            toast.error('Veuillez entrer des notes');
            return;
        }

        setIsUpdatingNotes(true);

        try {
            const response = await fetch(`/api/pastoral-care/appointments/${appointment.uuid}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    pastor_notes: pastorNotes
                })
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message || 'Notes ajoutées avec succès');
                router.reload({ only: ['appointment'] });
            } else {
                toast.error(data.message || 'Erreur lors de l\'ajout des notes');
            }
        } catch (error) {
            toast.error('Erreur lors de l\'ajout des notes');
        } finally {
            setIsUpdatingNotes(false);
        }
    };

    const handleDelete = async () => {
        try {
            await router.delete(`/pastoral-care/appointments/${appointment.uuid}`, {
                onSuccess: () => {
                    toast.success('Rendez-vous supprimé avec succès');
                    router.visit('/pastoral-care/appointments');
                },
                onError: () => {
                    toast.error('Erreur lors de la suppression');
                }
            });
        } catch (error) {
            toast.error('Erreur lors de la suppression');
        }
    };

    const canUpdateStatus = (currentStatus: string) => {
        switch (currentStatus) {
            case 'pending':
                return ['confirmed', 'cancelled'];
            case 'confirmed':
                return ['completed', 'cancelled', 'no_show'];
            case 'completed':
                return [];
            case 'cancelled':
                return [];
            case 'no_show':
                return ['completed'];
            default:
                return [];
        }
    };

    const availableStatusUpdates = canUpdateStatus(appointment.status);

    return (
        <DashboardLayout
            title="Détails du rendez-vous"
            actions={
                <div className="flex space-x-2">
                    <Button
                        variant="outline"
                        onClick={() => router.visit('/pastoral-care/appointments')}
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                        Retour
                    </Button>
                    {canEdit && (
                        <Button
                            variant="outline"
                            onClick={() => router.visit(`/pastoral-care/appointments/${appointment.uuid}/edit`)}
                        >
                            <PencilIcon className="h-4 w-4 mr-2" />
                            Modifier
                        </Button>
                    )}
                </div>
            }
        >
            <Head title={`Rendez-vous - ${appointment.client_name}`} />

            <div className="py-6">
                <div className="mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Appointment Overview */}
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-xl">Aperçu du rendez-vous</CardTitle>
                                        {getStatusBadge(appointment.status)}
                                    </div>
                                    <CardDescription>
                                        Créé le {formatDateTime(appointment.created_at)}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    {/* Client Information */}
                                    <div className="border-b border-gray-200 dark:border-gray-700 pb-4">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                            <UserIcon className="h-5 w-5 mr-2" />
                                            Informations du client
                                        </h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Nom complet</p>
                                                <p className="text-gray-900 dark:text-white font-medium text-lg">
                                                    {appointment.client_name}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Email</p>
                                                <div className="flex items-center space-x-2">
                                                    <EnvelopeIcon className="h-4 w-4 text-gray-500" />
                                                    <a
                                                        href={`mailto:${appointment.client_email}`}
                                                        className="text-blue-600 dark:text-blue-400 hover:underline"
                                                    >
                                                        {appointment.client_email}
                                                    </a>
                                                </div>
                                            </div>
                                            {appointment.client_phone && (
                                                <div className="md:col-span-2">
                                                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Téléphone</p>
                                                    <div className="flex items-center space-x-2">
                                                        <PhoneIcon className="h-4 w-4 text-gray-500" />
                                                        <a
                                                            href={`tel:${appointment.client_phone}`}
                                                            className="text-blue-600 dark:text-blue-400 hover:underline"
                                                        >
                                                            {appointment.client_phone}
                                                        </a>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Appointment Details */}
                                    <div>
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                            <CalendarIcon className="h-5 w-5 mr-2" />
                                            Détails du rendez-vous
                                        </h3>
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Date</p>
                                                <p className="text-gray-900 dark:text-white font-medium">
                                                    {formatDate(appointment.appointment_date)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Heure</p>
                                                <p className="text-gray-900 dark:text-white font-medium">
                                                    {formatTime(appointment.appointment_time)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Durée</p>
                                                <p className="text-gray-900 dark:text-white font-medium">
                                                    {appointment.duration_minutes} minutes
                                                </p>
                                            </div>
                                        </div>

                                        <div className="mt-4">
                                            <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Type de rencontre</p>
                                            <div className="flex items-center space-x-2">
                                                {getLocationIcon()}
                                                <span className="text-gray-900 dark:text-white font-medium">
                                                    {getLocationText()}
                                                </span>
                                            </div>

                                            {appointment.location_type === 'zoom' && appointment.zoom_link && (
                                                <div className="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                                    <p className="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">
                                                        Lien de visioconférence :
                                                    </p>
                                                    <a
                                                        href={appointment.zoom_link}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-blue-600 dark:text-blue-400 hover:underline text-sm break-all"
                                                    >
                                                        {appointment.zoom_link}
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Pastor Information */}
                                    <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3">Pasteur responsable</h3>
                                        <div className="flex items-center space-x-3">
                                            <div className="flex-shrink-0">
                                                <div className="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center">
                                                    <span className="text-white font-semibold">
                                                        {appointment.pastor.first_name.charAt(0)}{appointment.pastor.last_name.charAt(0)}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-white">
                                                    {appointment.pastor.first_name} {appointment.pastor.last_name}
                                                </p>
                                                <p className="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                                    <EnvelopeIcon className="h-4 w-4 mr-1" />
                                                    <a
                                                        href={`mailto:${appointment.pastor.email}`}
                                                        className="text-blue-600 dark:text-blue-400 hover:underline"
                                                    >
                                                        {appointment.pastor.email}
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Client Notes */}
                            {appointment.notes && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center">
                                            <DocumentTextIcon className="h-5 w-5 mr-2" />
                                            Notes du client
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                            <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                                {appointment.notes}
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Cancellation Reason */}
                            {appointment.status === 'cancelled' && appointment.cancellation_reason && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center text-red-600 dark:text-red-400">
                                            <XCircleIcon className="h-5 w-5 mr-2" />
                                            Raison de l'annulation
                                        </CardTitle>
                                        {appointment.cancelled_at && (
                                            <CardDescription>
                                                Annulé le {formatDateTime(appointment.cancelled_at)}
                                            </CardDescription>
                                        )}
                                    </CardHeader>
                                    <CardContent>
                                        <div className="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                                            <p className="text-red-700 dark:text-red-300">
                                                {appointment.cancellation_reason}
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Status Management */}
                            {(availableStatusUpdates.length > 0 || canEdit) && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Gestion du statut</CardTitle>
                                        <CardDescription>
                                            Actions disponibles pour ce rendez-vous
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {/* Edit Button */}
                                        {canEdit && (
                                            <Button
                                                onClick={() => router.visit(`/pastoral-care/appointments/${appointment.uuid}/edit`)}
                                                variant="outline"
                                                className="w-full text-blue-600 border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                            >
                                                <PencilIcon className="h-4 w-4 mr-2" />
                                                Modifier le rendez-vous
                                            </Button>
                                        )}
                                        {availableStatusUpdates.includes('confirmed') && (
                                            <Button
                                                onClick={handleConfirm}
                                                disabled={isUpdatingStatus}
                                                className="w-full bg-green-600 hover:bg-green-700 text-white"
                                            >
                                                <CheckCircleIcon className="h-4 w-4 mr-2" />
                                                Confirmer
                                            </Button>
                                        )}

                                        {availableStatusUpdates.includes('completed') && (
                                            <Button
                                                onClick={handleComplete}
                                                disabled={isUpdatingStatus}
                                                className="w-full bg-blue-600 hover:bg-blue-700 text-white"
                                            >
                                                <CheckCircleIcon className="h-4 w-4 mr-2" />
                                                Marquer terminé
                                            </Button>
                                        )}

                                        {availableStatusUpdates.includes('no_show') && (
                                            <Button
                                                onClick={handleNoShow}
                                                disabled={isUpdatingStatus}
                                                variant="outline"
                                                className="w-full text-orange-600 border-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20"
                                            >
                                                <XCircleIcon className="h-4 w-4 mr-2" />
                                                Marquer absent
                                            </Button>
                                        )}

                                        {availableStatusUpdates.includes('cancelled') && (
                                            <Button
                                                onClick={handleCancel}
                                                disabled={isUpdatingStatus}
                                                variant="outline"
                                                className="w-full text-red-600 border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                            >
                                                <XCircleIcon className="h-4 w-4 mr-2" />
                                                Annuler
                                            </Button>
                                        )}
                                    </CardContent>
                                </Card>
                            )}

                            {/* Notification History */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Historique des notifications</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="text-sm">
                                        <div className="flex justify-between items-center py-2">
                                            <span className="text-gray-600 dark:text-gray-400">Email de confirmation</span>
                                            <span className={appointment.confirmation_sent_at ? 'text-green-600' : 'text-gray-400'}>
                                                {appointment.confirmation_sent_at ?
                                                    format(new Date(appointment.confirmation_sent_at), 'd/M/y', { locale: fr }) :
                                                    'Non envoyé'
                                                }
                                            </span>
                                        </div>
                                        <div className="flex justify-between items-center py-2">
                                            <span className="text-gray-600 dark:text-gray-400">Rappel</span>
                                            <span className={appointment.reminder_sent_at ? 'text-green-600' : 'text-gray-400'}>
                                                {appointment.reminder_sent_at ?
                                                    format(new Date(appointment.reminder_sent_at), 'd/M/y', { locale: fr }) :
                                                    'Non envoyé'
                                                }
                                            </span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Pastor Notes */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Notes du pasteur</CardTitle>
                                    <CardDescription>
                                        Notes privées sur cette rencontre
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Display existing notes */}
                                    {appointment.pastor_notes && (
                                        <div className="mb-4">
                                            <Label>Notes existantes</Label>
                                            <div className="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                                <p className="text-blue-800 dark:text-blue-200 text-sm leading-relaxed whitespace-pre-wrap">
                                                    {appointment.pastor_notes}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    <div>
                                        <Label htmlFor="pastor_notes">
                                            {appointment.pastor_notes ? 'Ajouter des notes additionnelles' : 'Notes privées'}
                                        </Label>
                                        <Textarea
                                            id="pastor_notes"
                                            value={pastorNotes}
                                            onChange={(e) => setPastorNotes(e.target.value)}
                                            placeholder="Points abordés, conseils donnés, suivi nécessaire..."
                                            className="mt-2 min-h-[100px]"
                                        />
                                    </div>
                                    <Button
                                        onClick={handleNotesUpdate}
                                        disabled={isUpdatingNotes || !pastorNotes.trim()}
                                        className="w-full"
                                    >
                                        {isUpdatingNotes ? 'Ajout...' : (appointment.pastor_notes ? 'Mettre à jour les notes' : 'Ajouter les notes')}
                                    </Button>
                                </CardContent>
                            </Card>

                            {/* Danger Zone */}
                            <Card className="border-red-200 dark:border-red-800">
                                <CardHeader>
                                    <CardTitle className="text-red-600 dark:text-red-400">Zone de danger</CardTitle>
                                    <CardDescription>
                                        Actions irréversibles
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Button
                                        onClick={() => setShowDeleteDialog(true)}
                                        variant="outline"
                                        className="w-full text-red-600 border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    >
                                        Supprimer définitivement
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
                onConfirm={handleDelete}
                title="Supprimer le rendez-vous"
                description={`Êtes-vous sûr de vouloir supprimer définitivement le rendez-vous avec ${appointment.client_name} ? Cette action ne peut pas être annulée.`}
            />
        </DashboardLayout>
    );
}