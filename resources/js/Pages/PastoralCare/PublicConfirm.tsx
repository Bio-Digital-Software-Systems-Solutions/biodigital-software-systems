import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { CalendarIcon, ClockIcon, MapPinIcon, VideoCameraIcon, PhoneIcon, EnvelopeIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';

interface PastoralCareAppointment {
    id: number;
    uuid: string;
    pastor: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
    };
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
}

interface Props {
    appointment: PastoralCareAppointment;
}

export default function PublicConfirm({ appointment }: Props) {
    const [isConfirming, setIsConfirming] = useState(false);
    const [isCancelling, setIsCancelling] = useState(false);

    const handleConfirm = async () => {
        setIsConfirming(true);

        try {
            await router.post(`/api/pastoral-care/${appointment.uuid}/confirm`, {}, {
                onSuccess: () => {
                    toast.success('Rendez-vous confirmé avec succès !');
                    router.visit('/pastoral-care/success');
                },
                onError: () => {
                    toast.error('Erreur lors de la confirmation. Veuillez réessayer.');
                }
            });
        } catch (error) {
            toast.error('Erreur lors de la confirmation. Veuillez réessayer.');
        } finally {
            setIsConfirming(false);
        }
    };

    const handleCancel = () => {
        router.visit(`/pastoral-care/cancel/${appointment.uuid}`);
    };

    const formatDate = (dateString: string) => {
        return format(new Date(dateString), 'EEEE d MMMM yyyy', { locale: fr });
    };

    const formatTime = (timeString: string) => {
        return format(new Date(timeString), 'HH:mm', { locale: fr });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge variant="secondary">En attente</Badge>;
            case 'confirmed':
                return <Badge variant="default">Confirmé</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Annulé</Badge>;
            case 'completed':
                return <Badge variant="outline">Terminé</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const getLocationIcon = () => {
        switch (appointment.location_type) {
            case 'zoom':
                return <VideoCameraIcon className="h-5 w-5" />;
            case 'hybrid':
                return <div className="flex space-x-1">
                    <MapPinIcon className="h-4 w-4" />
                    <VideoCameraIcon className="h-4 w-4" />
                </div>;
            default:
                return <MapPinIcon className="h-5 w-5" />;
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

    return (
        <>
            <Head title="Confirmer votre rendez-vous - Soin Pastoral" />

            <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl mx-auto">
                    {/* Header */}
                    <div className="text-center mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            Confirmation de rendez-vous
                        </h1>
                        <p className="text-lg text-gray-600 dark:text-gray-300">
                            Soin pastoral - ICC Munich
                        </p>
                    </div>

                    {/* Appointment Details Card */}
                    <Card className="mb-8">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-xl">Détails du rendez-vous</CardTitle>
                                {getStatusBadge(appointment.status)}
                            </div>
                            <CardDescription>
                                Demande créée le {format(new Date(appointment.created_at), 'd MMMM yyyy à HH:mm', { locale: fr })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Pastor Information */}
                            <div className="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Pasteur</h3>
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
                                            {appointment.pastor.email}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Date and Time */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div className="flex items-center space-x-2 text-gray-700 dark:text-gray-300 mb-1">
                                        <CalendarIcon className="h-5 w-5" />
                                        <span className="font-medium">Date</span>
                                    </div>
                                    <p className="text-lg font-semibold text-gray-900 dark:text-white ml-7">
                                        {formatDate(appointment.appointment_date)}
                                    </p>
                                </div>
                                <div>
                                    <div className="flex items-center space-x-2 text-gray-700 dark:text-gray-300 mb-1">
                                        <ClockIcon className="h-5 w-5" />
                                        <span className="font-medium">Heure</span>
                                    </div>
                                    <p className="text-lg font-semibold text-gray-900 dark:text-white ml-7">
                                        {formatTime(appointment.appointment_time)} ({appointment.duration_minutes} min)
                                    </p>
                                </div>
                            </div>

                            {/* Location */}
                            <div>
                                <div className="flex items-center space-x-2 text-gray-700 dark:text-gray-300 mb-2">
                                    {getLocationIcon()}
                                    <span className="font-medium">Lieu</span>
                                </div>
                                <p className="text-gray-900 dark:text-white ml-7">
                                    {getLocationText()}
                                </p>
                                {appointment.location_type === 'zoom' && appointment.zoom_link && (
                                    <div className="ml-7 mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <p className="text-sm text-blue-700 dark:text-blue-300 mb-1">
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

                            {/* Client Information */}
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 className="font-semibold text-gray-900 dark:text-white mb-3">Vos informations</h3>
                                <div className="space-y-2">
                                    <p className="text-gray-700 dark:text-gray-300">
                                        <span className="font-medium">Nom :</span> {appointment.client_name}
                                    </p>
                                    <p className="text-gray-700 dark:text-gray-300 flex items-center">
                                        <EnvelopeIcon className="h-4 w-4 mr-2" />
                                        {appointment.client_email}
                                    </p>
                                    {appointment.client_phone && (
                                        <p className="text-gray-700 dark:text-gray-300 flex items-center">
                                            <PhoneIcon className="h-4 w-4 mr-2" />
                                            {appointment.client_phone}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Notes */}
                            {appointment.notes && (
                                <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Notes</h3>
                                    <p className="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                                        {appointment.notes}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Action Buttons */}
                    {appointment.status === 'pending' && (
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center mb-6">
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        Confirmer votre rendez-vous
                                    </h3>
                                    <p className="text-gray-600 dark:text-gray-400">
                                        Veuillez confirmer ou annuler votre rendez-vous de soin pastoral.
                                    </p>
                                </div>

                                <div className="flex flex-col sm:flex-row gap-4">
                                    <Button
                                        onClick={handleConfirm}
                                        disabled={isConfirming || isCancelling}
                                        className="flex-1 bg-green-600 hover:bg-green-700 text-white"
                                    >
                                        <CheckCircleIcon className="h-5 w-5 mr-2" />
                                        {isConfirming ? 'Confirmation...' : 'Confirmer le rendez-vous'}
                                    </Button>

                                    <Button
                                        onClick={handleCancel}
                                        variant="outline"
                                        disabled={isConfirming || isCancelling}
                                        className="flex-1 text-red-600 border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    >
                                        <XCircleIcon className="h-5 w-5 mr-2" />
                                        Annuler le rendez-vous
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {appointment.status === 'confirmed' && (
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <CheckCircleIcon className="h-12 w-12 text-green-600 mx-auto mb-4" />
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        Rendez-vous confirmé
                                    </h3>
                                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                                        Votre rendez-vous a été confirmé. Vous recevrez un rappel 24h avant.
                                    </p>

                                    <Button
                                        onClick={handleCancel}
                                        variant="outline"
                                        className="text-red-600 border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    >
                                        <XCircleIcon className="h-5 w-5 mr-2" />
                                        Annuler le rendez-vous
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {appointment.status === 'cancelled' && (
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <XCircleIcon className="h-12 w-12 text-red-600 mx-auto mb-4" />
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        Rendez-vous annulé
                                    </h3>
                                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                                        Ce rendez-vous a été annulé.
                                    </p>

                                    <Button
                                        onClick={() => router.visit('/pastoral-care/public/book')}
                                        className="bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        Prendre un nouveau rendez-vous
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Footer */}
                    <div className="text-center mt-8">
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Besoin d'aide ? Contactez-nous à{' '}
                            <a href="mailto:info@icc-munich.de" className="text-blue-600 dark:text-blue-400 hover:underline">
                                info@icc-munich.de
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}