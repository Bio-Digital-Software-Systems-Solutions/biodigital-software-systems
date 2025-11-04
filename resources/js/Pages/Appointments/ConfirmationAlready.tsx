import React from 'react';
import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    InformationCircleIcon,
    CheckCircleIcon,
    XCircleIcon,
    CalendarIcon,
    ClockIcon
} from '@heroicons/react/24/outline';

interface Props {
    status: string; // 'confirmé' or 'décliné'
    appointment: {
        title: string;
        start_datetime: string;
    };
}

export default function ConfirmationAlready({ status, appointment }: Props) {
    const startDate = new Date(appointment.start_datetime);
    const isConfirmed = status === 'confirmé';

    const StatusIcon = isConfirmed ? CheckCircleIcon : XCircleIcon;
    const colorClass = isConfirmed ? 'green' : 'red';

    return (
        <>
            <Head title="Réponse déjà enregistrée" />
            <div className={`min-h-screen bg-gradient-to-br from-${colorClass}-50 via-background to-${colorClass}-50 flex items-center justify-center p-4`}>
                <Card className={`w-full max-w-md shadow-lg border-${colorClass}-200`}>
                    <CardHeader className="text-center space-y-4">
                        <div className={`mx-auto w-16 h-16 bg-${colorClass}-100 dark:bg-${colorClass}-900 rounded-full flex items-center justify-center`}>
                            <StatusIcon className={`h-8 w-8 text-${colorClass}-600 dark:text-${colorClass}-400`} />
                        </div>
                        <CardTitle className={`text-2xl text-${colorClass}-900 dark:text-${colorClass}-100`}>
                            Réponse déjà enregistrée
                        </CardTitle>
                        <CardDescription className={`text-${colorClass}-700 dark:text-${colorClass}-300`}>
                            Vous avez déjà {status} votre participation à ce rendez-vous.
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
                            <div className={`p-4 rounded-lg bg-${colorClass}-50 dark:bg-${colorClass}-900/20 border border-${colorClass}-200 dark:border-${colorClass}-800`}>
                                <div className="flex items-center gap-2">
                                    <InformationCircleIcon className={`h-5 w-5 text-${colorClass}-600 dark:text-${colorClass}-400`} />
                                    <span className={`text-sm font-medium text-${colorClass}-800 dark:text-${colorClass}-200`}>
                                        {isConfirmed
                                            ? 'Votre participation est confirmée'
                                            : 'Vous avez décliné cette invitation'
                                        }
                                    </span>
                                </div>
                            </div>

                            <div className="text-center text-sm text-gray-600 dark:text-gray-400">
                                <p>
                                    Si vous souhaitez modifier votre réponse, veuillez contacter l'organisateur du rendez-vous.
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
                                    onClick={() => window.location.href = 'mailto:contact@icc-muenchen.de?subject=Modification de réponse rendez-vous'}
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