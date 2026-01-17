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
    DocumentTextIcon,
    ArrowPathIcon,
    LinkIcon,
    ArrowDownTrayIcon,
    DocumentArrowDownIcon
} from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import FollowUpModal from '@/Components/PastoralCare/FollowUpModal';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    roles?: Array<{ name: string }>;
    permissions?: Array<{ name: string }>;
}

interface PastorNote {
    content: string;
    created_at: string;
}

interface PastoralCareAppointment {
    id: number;
    uuid: string;
    user?: User;
    pastor: User;
    parent_id?: number;
    parent?: {
        id: number;
        uuid: string;
        appointment_date: string;
        appointment_time: string;
        status: string;
        client_name: string;
        pastor: User;
    };
    follow_ups?: Array<{
        id: number;
        uuid: string;
        appointment_date: string;
        appointment_time: string;
        status: string;
        client_name: string;
        pastor: User;
    }>;
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
    pastor_notes?: PastorNote[];
    cancellation_reason?: string;
    created_at: string;
    updated_at: string;
    cancelled_at?: string;
    confirmation_sent_at?: string;
    reminder_sent_at?: string;
    can_be_confirmed: boolean;
    can_be_cancelled: boolean;
}

interface Props {
    appointment: PastoralCareAppointment;
    canEdit: boolean;
    canConfirm: boolean;
    canCancel: boolean;
    canViewClientNotes: boolean;
    auth: {
        user: User;
    };
}

export default function Show({ appointment, canEdit, canConfirm, canCancel, canViewClientNotes, auth }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [showFollowUpModal, setShowFollowUpModal] = useState(false);
    const [showReportModal, setShowReportModal] = useState(false);
    const [newPastorNote, setNewPastorNote] = useState('');
    const [isUpdatingNotes, setIsUpdatingNotes] = useState(false);
    const [isUpdatingStatus, setIsUpdatingStatus] = useState(false);
    const [isGeneratingReport, setIsGeneratingReport] = useState(false);

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

    const getStatusVariant = (status: string): "default" | "secondary" | "destructive" | "outline" => {
        switch (status) {
            case 'pending':
                return 'secondary';
            case 'confirmed':
                return 'default';
            case 'completed':
                return 'outline';
            case 'cancelled':
            case 'no_show':
                return 'destructive';
            default:
                return 'secondary';
        }
    };

    const getStatusLabel = (status: string): string => {
        switch (status) {
            case 'pending':
                return 'En attente';
            case 'confirmed':
                return 'Confirmé';
            case 'completed':
                return 'Terminé';
            case 'cancelled':
                return 'Annulé';
            case 'no_show':
                return 'Absent';
            default:
                return status;
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
        if (!newPastorNote.trim()) {
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
                    pastor_notes: newPastorNote
                })
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message || 'Notes ajoutées avec succès');
                setNewPastorNote('');
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

    const handleGenerateReport = async (format: 'pdf' | 'excel' | 'word') => {
        setIsGeneratingReport(true);

        try {
            const response = await fetch(`/api/pastoral-care/appointments/${appointment.uuid}/report?format=${format}`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': format === 'pdf' ? 'application/pdf' :
                              format === 'excel' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' :
                              'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                },
            });

            if (!response.ok) {
                // Try to parse error as JSON, but handle case where it's not JSON
                let errorMessage = 'Erreur lors de la génération du rapport';
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } catch {
                    // Response is not JSON, use status text
                    errorMessage = response.statusText || errorMessage;
                }
                throw new Error(errorMessage);
            }

            // Get filename from Content-Disposition header or generate one
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = `rapport_pastoral_${appointment.client_name.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}`;

            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                if (filenameMatch && filenameMatch[1]) {
                    filename = filenameMatch[1].replace(/['"]/g, '');
                }
            } else {
                const extensions: Record<string, string> = { pdf: '.pdf', excel: '.xlsx', word: '.docx' };
                filename += extensions[format] || '.pdf';
            }

            // Create a blob from the response and download it
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            toast.success('Rapport généré avec succès');
            setShowReportModal(false);
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Erreur lors de la génération du rapport');
        } finally {
            setIsGeneratingReport(false);
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
    const isPastor = auth.user.id === appointment.pastor.id;

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
                    {canEdit && (isPastor || (auth.user && (auth.user.roles?.some((role: any) => ['admin', 'SuperAdmin'].includes(role.name)) || auth.user.permissions?.some((permission: any) => permission.name === 'manage pastoral care')))) && (
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

                            {/* Related Appointments - Parent and Follow-ups */}
                            {(appointment.parent || (appointment.follow_ups && appointment.follow_ups.length > 0)) && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center">
                                            <LinkIcon className="h-5 w-5 mr-2" />
                                            Rendez-vous liés
                                        </CardTitle>
                                        <CardDescription>
                                            Navigation entre les rendez-vous de suivi
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {/* Parent appointment - if this is a follow-up */}
                                        {appointment.parent && (
                                            <div>
                                                <h4 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                                                    Rendez-vous précédent
                                                </h4>
                                                <button
                                                    onClick={() => router.visit(`/pastoral-care/appointments/${appointment.parent!.uuid}`)}
                                                    className="w-full text-left p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center space-x-3">
                                                            <ArrowLeftIcon className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                                            <div>
                                                                <p className="font-medium text-gray-900 dark:text-white">
                                                                    {formatDate(appointment.parent.appointment_date)}
                                                                </p>
                                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                                    {formatTime(appointment.parent.appointment_time)} avec {appointment.parent.pastor.first_name} {appointment.parent.pastor.last_name}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <Badge variant={getStatusVariant(appointment.parent.status)}>
                                                            {getStatusLabel(appointment.parent.status)}
                                                        </Badge>
                                                    </div>
                                                </button>
                                            </div>
                                        )}

                                        {/* Follow-up appointments - if this has follow-ups */}
                                        {appointment.follow_ups && appointment.follow_ups.length > 0 && (
                                            <div>
                                                <h4 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                                                    Rendez-vous de suivi ({appointment.follow_ups.length})
                                                </h4>
                                                <div className="space-y-2">
                                                    {appointment.follow_ups.map((followUp) => (
                                                        <button
                                                            key={followUp.uuid}
                                                            onClick={() => router.visit(`/pastoral-care/appointments/${followUp.uuid}`)}
                                                            className="w-full text-left p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors"
                                                        >
                                                            <div className="flex items-center justify-between">
                                                                <div className="flex items-center space-x-3">
                                                                    <ArrowPathIcon className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                                                    <div>
                                                                        <p className="font-medium text-gray-900 dark:text-white">
                                                                            {formatDate(followUp.appointment_date)}
                                                                        </p>
                                                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                                                            {formatTime(followUp.appointment_time)} avec {followUp.pastor.first_name} {followUp.pastor.last_name}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <Badge variant={getStatusVariant(followUp.status)}>
                                                                    {getStatusLabel(followUp.status)}
                                                                </Badge>
                                                            </div>
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            )}

                            {/* Client Notes - Only visible to pastor and client */}
                            {canViewClientNotes && appointment.notes && (
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
                            {((availableStatusUpdates.length > 0 && isPastor) || canEdit) && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Gestion du statut</CardTitle>
                                        <CardDescription>
                                            Actions disponibles pour ce rendez-vous
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {/* Edit Button - Only visible to pastor, admin or super admin */}
                                        {canEdit && (isPastor || (auth.user && (auth.user.roles?.some((role: any) => ['admin', 'SuperAdmin'].includes(role.name)) || auth.user.permissions?.some((permission: any) => permission.name === 'manage pastoral care')))) && (
                                            <Button
                                                onClick={() => router.visit(`/pastoral-care/appointments/${appointment.uuid}/edit`)}
                                                variant="outline"
                                                className="w-full text-blue-600 border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                            >
                                                <PencilIcon className="h-4 w-4 mr-2" />
                                                Modifier le rendez-vous
                                            </Button>
                                        )}
                                        {isPastor && availableStatusUpdates.includes('confirmed') && (
                                            <Button
                                                onClick={handleConfirm}
                                                disabled={isUpdatingStatus || !appointment.can_be_confirmed}
                                                className="w-full bg-green-600 hover:bg-green-700 text-white"
                                            >
                                                <CheckCircleIcon className="h-4 w-4 mr-2" />
                                                Confirmer
                                            </Button>
                                        )}

                                        {isPastor && availableStatusUpdates.includes('completed') && (
                                            <Button
                                                onClick={handleComplete}
                                                disabled={isUpdatingStatus}
                                                className="w-full bg-blue-600 hover:bg-blue-700 text-white"
                                            >
                                                <CheckCircleIcon className="h-4 w-4 mr-2" />
                                                Marquer terminé
                                            </Button>
                                        )}

                                        {isPastor && availableStatusUpdates.includes('no_show') && (
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

                                        {/* Follow-up Button - visible for completed or confirmed appointments */}
                                        {isPastor && ['completed', 'confirmed'].includes(appointment.status) && (
                                            <Button
                                                onClick={() => setShowFollowUpModal(true)}
                                                variant="outline"
                                                className="w-full text-purple-600 border-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20"
                                            >
                                                <ArrowPathIcon className="h-4 w-4 mr-2" />
                                                Planifier un suivi
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

                            {/* Report Generation Button - Only visible to the pastor */}
                            {isPastor && (
                                <Button
                                    onClick={() => setShowReportModal(true)}
                                    variant="outline"
                                    className="w-full"
                                >
                                    <DocumentArrowDownIcon className="h-4 w-4 mr-2" />
                                    Générer un rapport
                                </Button>
                            )}

                            {/* Pastor Notes - Only visible to the pastor */}
                            {isPastor && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Notes du pasteur</CardTitle>
                                        <CardDescription>
                                            Notes privées sur cette rencontre
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {/* Display existing notes with timestamps */}
                                        {appointment.pastor_notes && appointment.pastor_notes.length > 0 && (
                                            <div className="space-y-3">
                                                <Label>Notes existantes</Label>
                                                <div className="space-y-3 max-h-[400px] overflow-y-auto">
                                                    {[...appointment.pastor_notes].reverse().map((note, index) => (
                                                        <div
                                                            key={index}
                                                            className="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800"
                                                        >
                                                            <div className="flex items-center justify-between mb-2">
                                                                <span className="text-xs text-blue-600 dark:text-blue-400 font-medium flex items-center gap-1">
                                                                    <ClockIcon className="h-3 w-3" />
                                                                    {format(new Date(note.created_at), 'd MMM yyyy à HH:mm', { locale: fr })}
                                                                </span>
                                                            </div>
                                                            <p className="text-blue-800 dark:text-blue-200 text-sm leading-relaxed whitespace-pre-wrap break-words" style={{ wordBreak: 'break-word', overflowWrap: 'anywhere' }}>
                                                                {note.content}
                                                            </p>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        <div>
                                            <Label htmlFor="pastor_notes">
                                                {appointment.pastor_notes && appointment.pastor_notes.length > 0 ? 'Ajouter une nouvelle note' : 'Notes privées'}
                                            </Label>
                                            <Textarea
                                                id="pastor_notes"
                                                value={newPastorNote}
                                                onChange={(e) => setNewPastorNote(e.target.value)}
                                                placeholder="Points abordés, conseils donnés, suivi nécessaire..."
                                                className="mt-2 min-h-[100px]"
                                            />
                                        </div>
                                        <Button
                                            onClick={handleNotesUpdate}
                                            disabled={isUpdatingNotes || !newPastorNote.trim()}
                                            className="w-full"
                                        >
                                            {isUpdatingNotes ? 'Ajout...' : 'Ajouter les notes'}
                                        </Button>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Danger Zone - Only visible to Admin and SuperAdmin */}
                            {(auth.user && (auth.user.roles?.some((role: any) => ['admin', 'SuperAdmin'].includes(role.name)) || auth.user.permissions?.some((permission: any) => permission.name === 'manage pastoral care'))) && (
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
                            )}
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

            {/* Follow-Up Modal */}
            <FollowUpModal
                isOpen={showFollowUpModal}
                onClose={() => setShowFollowUpModal(false)}
                parentAppointment={{
                    uuid: appointment.uuid,
                    pastor_id: appointment.pastor.id,
                    client_name: appointment.client_name,
                    client_email: appointment.client_email,
                    client_phone: appointment.client_phone,
                    duration_minutes: appointment.duration_minutes,
                    location_type: appointment.location_type,
                    zoom_link: appointment.zoom_link,
                }}
            />

            {/* Report Generation Modal */}
            <Dialog open={showReportModal} onOpenChange={setShowReportModal}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <DocumentArrowDownIcon className="h-5 w-5" />
                            Générer un rapport
                        </DialogTitle>
                        <DialogDescription>
                            Choisissez le format d'export pour le rapport pastoral.
                            Ce rapport inclut les informations du client, les notes,
                            et l'historique complet des rendez-vous.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="p-6 space-y-4">
                        {isGeneratingReport ? (
                            <div className="flex flex-col items-center justify-center py-8 gap-3">
                                <svg className="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span className="text-sm text-gray-500">Génération en cours...</span>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-3">
                                <Button
                                    onClick={() => handleGenerateReport('pdf')}
                                    variant="outline"
                                    className="w-full justify-start h-auto py-4 px-4"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                            <ArrowDownTrayIcon className="h-5 w-5 text-red-600" />
                                        </div>
                                        <div className="text-left">
                                            <div className="font-medium">PDF</div>
                                            <div className="text-xs text-gray-500">Document portable, idéal pour l'impression</div>
                                        </div>
                                    </div>
                                </Button>
                                <Button
                                    onClick={() => handleGenerateReport('excel')}
                                    variant="outline"
                                    className="w-full justify-start h-auto py-4 px-4"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                            <ArrowDownTrayIcon className="h-5 w-5 text-green-600" />
                                        </div>
                                        <div className="text-left">
                                            <div className="font-medium">Excel</div>
                                            <div className="text-xs text-gray-500">Tableur avec plusieurs feuilles de données</div>
                                        </div>
                                    </div>
                                </Button>
                                <Button
                                    onClick={() => handleGenerateReport('word')}
                                    variant="outline"
                                    className="w-full justify-start h-auto py-4 px-4"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                            <ArrowDownTrayIcon className="h-5 w-5 text-blue-600" />
                                        </div>
                                        <div className="text-left">
                                            <div className="font-medium">Word</div>
                                            <div className="text-xs text-gray-500">Document modifiable pour annotations</div>
                                        </div>
                                    </div>
                                </Button>
                            </div>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </DashboardLayout>
    );
}