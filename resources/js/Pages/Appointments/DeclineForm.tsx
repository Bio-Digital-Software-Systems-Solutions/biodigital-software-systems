import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import {
    XCircleIcon,
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
    token: string;
}

export default function DeclineForm({ appointment, participant, token }: Props) {
    const [message, setMessage] = useState('');
    const [processing, setProcessing] = useState(false);
    const startDate = new Date(appointment.start_datetime);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.post(`/appointments/${appointment.title}/decline/${token}`, {
            message: message
        }, {
            onFinish: () => setProcessing(false)
        });
    };

    const handleCancel = () => {
        window.close();
    };

    return (
        <>
            <Head title="Décliner l'invitation" />
            <div className="min-h-screen bg-gradient-to-br from-orange-50 via-background to-orange-50 flex items-center justify-center p-4">
                <Card className="w-full max-w-lg shadow-lg border-orange-200">
                    <CardHeader className="text-center space-y-4">
                        <div className="mx-auto w-16 h-16 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                            <XCircleIcon className="h-8 w-8 text-orange-600 dark:text-orange-400" />
                        </div>
                        <CardTitle className="text-2xl text-orange-900 dark:text-orange-100">
                            Décliner l'invitation
                        </CardTitle>
                        <CardDescription className="text-orange-700 dark:text-orange-300">
                            Bonjour {participant.first_name}, vous vous apprêtez à décliner l'invitation au rendez-vous suivant.
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
                                        })} à {startDate.toLocaleTimeString('fr-FR', {
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

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="message">
                                    Message (optionnel)
                                </Label>
                                <Textarea
                                    id="message"
                                    value={message}
                                    onChange={(e) => setMessage(e.target.value)}
                                    placeholder="Expliquez brièvement la raison de votre absence (optionnel)..."
                                    rows={4}
                                    className="resize-none"
                                />
                                <p className="text-xs text-gray-500">
                                    Ce message sera transmis à l'organisateur du rendez-vous.
                                </p>
                            </div>

                            <div className="flex gap-3 pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleCancel}
                                    className="flex-1"
                                    disabled={processing}
                                >
                                    Annuler
                                </Button>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    className="flex-1"
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <>
                                            <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                                            Décliner...
                                        </>
                                    ) : (
                                        'Décliner l\'invitation'
                                    )}
                                </Button>
                            </div>
                        </form>

                        <div className="text-center text-xs text-gray-500">
                            <p>
                                Une fois déclinée, vous ne pourrez plus modifier votre réponse sauf en contactant l'organisateur.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}