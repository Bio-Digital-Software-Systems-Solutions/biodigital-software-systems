import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import type { PageProps } from '@/Types';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { CalendarIcon, ClockIcon, MapPinIcon, VideoCameraIcon, PhoneIcon, EnvelopeIcon, XCircleIcon, ArrowLeftIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';
import { apiFetch } from '@/lib/utils';

interface CareServiceAppointment {
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
    cancellation_reason?: string;
}

interface Props {
    appointment: CareServiceAppointment;
}

export default function PublicCancel({ appointment }: Props) {
    const appName = usePage<PageProps>().props.app.name;
    const [cancellationReason, setCancellationReason] = useState('');
    const [isCancelling, setIsCancelling] = useState(false);

    const handleCancel = async () => {
        if (!cancellationReason.trim()) {
            toast.error('Veuillez indiquer une raison pour l\'annulation.');
            return;
        }

        setIsCancelling(true);

        try {
            const response = await apiFetch<{ success: boolean; message: string }>(`/api/care-service/appointments/${appointment.uuid}/cancel`, {
                method: 'POST',
                body: JSON.stringify({
                    cancellation_reason: cancellationReason
                }),
            });

            if (response.success && response.data?.success) {
                toast.success(response.data.message || 'Rendez-vous annulé avec succès.');
                router.visit('/care-service/success?action=cancelled');
            } else {
                toast.error(response.data?.message || response.error || 'Erreur lors de l\'annulation. Veuillez réessayer.');
            }
        } catch (error) {
            toast.error('Erreur lors de l\'annulation. Veuillez réessayer.');
        } finally {
            setIsCancelling(false);
        }
    };

    const handleGoBack = () => {
        router.visit(`/care-service/confirm/${appointment.uuid}`);
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

    // If already cancelled, show different view
    if (appointment.status === 'cancelled') {
        return (
            <>
                <Head title="Rendez-vous annulé - Care Services" />

                <div className="min-h-screen bg-gradient-to-br from-red-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 py-12 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-2xl mx-auto">
                        {/* Header */}
                        <div className="text-center mb-8">
                            <XCircleIcon className="h-16 w-16 text-red-600 mx-auto mb-4" />
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                Rendez-vous annulé
                            </h1>
                            <p className="text-lg text-gray-600 dark:text-gray-300">
                                Ce rendez-vous a déjà été annulé
                            </p>
                        </div>

                        {/* Appointment Details Card */}
                        <Card className="mb-8">
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-xl">Détails du rendez-vous annulé</CardTitle>
                                    {getStatusBadge(appointment.status)}
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <div className="flex items-center space-x-2 text-gray-700 dark:text-gray-300 mb-1">
                                            <CalendarIcon className="h-5 w-5" />
                                            <span className="font-medium">Date</span>
                                        </div>
                                        <p className="text-lg text-gray-900 dark:text-white ml-7">
                                            {formatDate(appointment.appointment_date)}
                                        </p>
                                    </div>
                                    <div>
                                        <div className="flex items-center space-x-2 text-gray-700 dark:text-gray-300 mb-1">
                                            <ClockIcon className="h-5 w-5" />
                                            <span className="font-medium">Heure</span>
                                        </div>
                                        <p className="text-lg text-gray-900 dark:text-white ml-7">
                                            {formatTime(appointment.appointment_time)} ({appointment.duration_minutes} min)
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <div className="flex items-center space-x-2 text-gray-700 dark:text-gray-300 mb-1">
                                        <span className="font-medium">Pasteur</span>
                                    </div>
                                    <p className="text-gray-900 dark:text-white ml-7">
                                        {appointment.pastor.first_name} {appointment.pastor.last_name}
                                    </p>
                                </div>

                                {appointment.cancellation_reason && (
                                    <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Raison de l'annulation</h3>
                                        <p className="text-gray-700 dark:text-gray-300 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                                            {appointment.cancellation_reason}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Action Button */}
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                                        Souhaitez-vous prendre un nouveau rendez-vous ?
                                    </p>

                                    <Button
                                        onClick={() => router.visit('/care-service/public/book')}
                                        className="bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        Prendre un nouveau rendez-vous
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Annuler votre rendez-vous - Care Services" />

            <div className="min-h-screen bg-gradient-to-br from-red-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl mx-auto">
                    {/* Header */}
                    <div className="text-center mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            Annuler votre rendez-vous
                        </h1>
                        <p className="text-lg text-gray-600 dark:text-gray-300">
                            Care Service - {appName}
                        </p>
                    </div>

                    {/* Appointment Details Card */}
                    <Card className="mb-8">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-xl">Rendez-vous à annuler</CardTitle>
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
                        </CardContent>
                    </Card>

                    {/* Cancellation Form */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle className="text-xl text-red-600 dark:text-red-400">
                                Confirmer l'annulation
                            </CardTitle>
                            <CardDescription>
                                Veuillez indiquer la raison de l'annulation de votre rendez-vous.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="cancellation_reason" className="text-base font-medium">
                                    Raison de l'annulation *
                                </Label>
                                <Textarea
                                    id="cancellation_reason"
                                    placeholder="Expliquez brièvement pourquoi vous souhaitez annuler ce rendez-vous..."
                                    value={cancellationReason}
                                    onChange={(e) => setCancellationReason(e.target.value)}
                                    className="mt-2 min-h-[100px]"
                                    required
                                />
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Cette information aidera votre pasteur à mieux comprendre votre situation.
                                </p>
                            </div>

                            <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <h4 className="font-medium text-yellow-800 dark:text-yellow-200 mb-2">
                                    Information importante
                                </h4>
                                <ul className="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                                    <li>• Une notification sera envoyée à votre pasteur</li>
                                    <li>• Cette action ne peut pas être annulée</li>
                                    <li>• Vous pourrez reprendre un nouveau rendez-vous à tout moment</li>
                                    <li>• En cas d'urgence, contactez directement votre pasteur</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Action Buttons */}
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex flex-col sm:flex-row gap-4">
                                <Button
                                    onClick={handleGoBack}
                                    variant="outline"
                                    disabled={isCancelling}
                                    className="flex-1"
                                >
                                    <ArrowLeftIcon className="h-5 w-5 mr-2" />
                                    Retour
                                </Button>

                                <Button
                                    onClick={handleCancel}
                                    disabled={isCancelling || !cancellationReason.trim()}
                                    className="flex-1 bg-red-600 hover:bg-red-700 text-white"
                                >
                                    <XCircleIcon className="h-5 w-5 mr-2" />
                                    {isCancelling ? 'Annulation...' : 'Confirmer l\'annulation'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

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