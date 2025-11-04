import React from 'react';
import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    CheckCircleIcon,
    CalendarIcon,
    ClockIcon,
    MapPinIcon
} from '@heroicons/react/24/outline';

interface Props {
    appointment: {
        title: string;
        start_datetime: string;
        location?: string;
        description?: string;
    };
    participant: {
        first_name: string;
        last_name: string;
    };
}

export default function ConfirmationSuccess({ appointment, participant }: Props) {
    const startDate = new Date(appointment.start_datetime);

    return (
        <>
            <Head title="Confirmation réussie" />
            <div className="min-h-screen bg-gradient-to-br from-green-50 via-background to-green-50 flex items-center justify-center p-4">
                <Card className="w-full max-w-md shadow-lg border-green-200">
                    <CardHeader className="text-center space-y-4">
                        <div className="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                            <CheckCircleIcon className="h-8 w-8 text-green-600 dark:text-green-400" />
                        </div>
                        <CardTitle className="text-2xl text-green-900 dark:text-green-100">
                            Participation confirmée !
                        </CardTitle>
                        <CardDescription className="text-green-700 dark:text-green-300">
                            Merci {participant.first_name}, votre participation a été confirmée avec succès.
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
                            <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                                Détails du rendez-vous
                            </h3>

                            <div className="space-y-2 text-sm">
                                <div className="flex items-center gap-2">
                                    <CalendarIcon className="h-4 w-4 text-gray-500" />
                                    <span className="font-medium">{appointment.title}</span>
                                </div>

                                <div className="flex items-center gap-2">
                                    <ClockIcon className="h-4 w-4 text-gray-500" />
                                    <span>
                                        {startDate.toLocaleDateString('fr-FR', {
                                            weekday: 'long',
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric'
                                        })}
                                    </span>
                                </div>

                                <div className="flex items-center gap-2">
                                    <ClockIcon className="h-4 w-4 text-gray-500" />
                                    <span>
                                        {startDate.toLocaleTimeString('fr-FR', {
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}
                                    </span>
                                </div>

                                {appointment.location && (
                                    <div className="flex items-center gap-2">
                                        <MapPinIcon className="h-4 w-4 text-gray-500" />
                                        <span>{appointment.location}</span>
                                    </div>
                                )}
                            </div>

                            {appointment.description && (
                                <div className="pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        {appointment.description}
                                    </p>
                                </div>
                            )}
                        </div>

                        <div className="text-center text-sm text-gray-600 dark:text-gray-400">
                            <p>Un email de confirmation vous sera envoyé sous peu.</p>
                            <p className="mt-2">
                                N'hésitez pas à ajouter cet événement à votre calendrier !
                            </p>
                        </div>

                        <div className="text-center">
                            <Button
                                variant="outline"
                                onClick={() => window.close()}
                                className="w-full"
                            >
                                Fermer cette page
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}