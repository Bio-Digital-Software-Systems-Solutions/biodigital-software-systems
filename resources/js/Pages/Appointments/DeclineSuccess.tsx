import React from 'react';
import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    CheckCircleIcon,
    CalendarIcon,
    ClockIcon
} from '@heroicons/react/24/outline';

interface Props {
    appointment: {
        title: string;
        start_datetime: string;
    };
    participant: {
        first_name: string;
        last_name: string;
    };
}

export default function DeclineSuccess({ appointment, participant }: Props) {
    const startDate = new Date(appointment.start_datetime);

    return (
        <>
            <Head title="Invitation déclinée" />
            <div className="min-h-screen bg-gradient-to-br from-orange-50 via-background to-orange-50 flex items-center justify-center p-4">
                <Card className="w-full max-w-md shadow-lg border-orange-200">
                    <CardHeader className="text-center space-y-4">
                        <div className="mx-auto w-16 h-16 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                            <CheckCircleIcon className="h-8 w-8 text-orange-600 dark:text-orange-400" />
                        </div>
                        <CardTitle className="text-2xl text-orange-900 dark:text-orange-100">
                            Invitation déclinée
                        </CardTitle>
                        <CardDescription className="text-orange-700 dark:text-orange-300">
                            Merci {participant.first_name}, votre réponse a été enregistrée.
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
                            <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                                Rendez-vous décliné
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
                            <div className="p-4 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                                <div className="text-center">
                                    <p className="text-sm font-medium text-orange-800 dark:text-orange-200">
                                        ✓ Votre refus a été transmis à l'organisateur
                                    </p>
                                </div>
                            </div>

                            <div className="text-center text-sm text-gray-600 dark:text-gray-400">
                                <p>
                                    L'organisateur du rendez-vous a été informé de votre absence.
                                </p>
                                <p className="mt-2">
                                    Si vous changez d'avis, veuillez le contacter directement.
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
                                    onClick={() => window.location.href = 'mailto:contact@icc-muenchen.de?subject=Changement de réponse rendez-vous'}
                                    className="w-full text-xs"
                                >
                                    Contacter l'organisateur
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}