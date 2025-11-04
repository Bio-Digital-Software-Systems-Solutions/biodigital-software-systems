import React from 'react';
import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    XCircleIcon,
    CalendarIcon,
    ClockIcon
} from '@heroicons/react/24/outline';

interface Props {
    error: string;
    appointment: {
        title: string;
        start_datetime: string;
    };
}

export default function ConfirmationError({ error, appointment }: Props) {
    const startDate = new Date(appointment.start_datetime);

    return (
        <>
            <Head title="Erreur de confirmation" />
            <div className="min-h-screen bg-gradient-to-br from-red-50 via-background to-red-50 flex items-center justify-center p-4">
                <Card className="w-full max-w-md shadow-lg border-red-200">
                    <CardHeader className="text-center space-y-4">
                        <div className="mx-auto w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                            <XCircleIcon className="h-8 w-8 text-red-600 dark:text-red-400" />
                        </div>
                        <CardTitle className="text-2xl text-red-900 dark:text-red-100">
                            Erreur de confirmation
                        </CardTitle>
                        <CardDescription className="text-red-700 dark:text-red-300">
                            {error}
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
                            <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                                Rendez-vous concerné
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
                                        })} à {startDate.toLocaleTimeString('fr-FR', {
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="text-center text-sm text-gray-600 dark:text-gray-400">
                                <p>Ce lien de confirmation est peut-être expiré ou invalide.</p>
                                <p className="mt-2">
                                    Veuillez contacter l'organisateur du rendez-vous pour obtenir un nouveau lien de confirmation.
                                </p>
                            </div>

                            <div className="text-center space-y-2">
                                <Button
                                    variant="outline"
                                    onClick={() => window.close()}
                                    className="w-full"
                                >
                                    Fermer cette page
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => window.location.href = 'mailto:contact@icc-muenchen.de?subject=Problème de confirmation de rendez-vous'}
                                    className="w-full text-xs"
                                >
                                    Contacter le support
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}