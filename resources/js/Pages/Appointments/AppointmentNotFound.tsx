import React from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';
import { ExclamationTriangleIcon, HomeIcon, EnvelopeIcon } from '@heroicons/react/24/outline';

interface Props {
    appointmentId?: string;
}

export default function AppointmentNotFound({ appointmentId }: Props) {
    return (
        <GuestLayout>
            <Head title="Rendez-vous introuvable" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div className="sm:mx-auto sm:w-full sm:max-w-md">
                    <div className="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
                        <div className="text-center">
                            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                                <ExclamationTriangleIcon className="h-6 w-6 text-red-600 dark:text-red-400" aria-hidden="true" />
                            </div>

                            <h2 className="mt-4 text-2xl font-bold leading-9 tracking-tight text-gray-900 dark:text-white">
                                Rendez-vous introuvable
                            </h2>

                            <p className="mt-4 text-sm text-gray-600 dark:text-gray-400">
                                Le rendez-vous que vous essayez de confirmer n'existe plus ou a été supprimé.
                            </p>

                            {appointmentId && (
                                <div className="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-md">
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        ID du rendez-vous: <span className="font-mono">{appointmentId}</span>
                                    </p>
                                </div>
                            )}

                            <div className="mt-6 space-y-4">
                                <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                                    <h3 className="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                                        Que pouvez-vous faire ?
                                    </h3>
                                    <ul className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                        <li>• Vérifiez que vous avez cliqué sur le bon lien dans l'email</li>
                                        <li>• Contactez l'organisateur du rendez-vous</li>
                                        <li>• Le rendez-vous a peut-être été annulé ou reporté</li>
                                    </ul>
                                </div>

                                <div className="flex flex-col sm:flex-row gap-3">
                                    <Link
                                        href="/"
                                        className="flex-1 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    >
                                        <HomeIcon className="h-4 w-4 mr-2" />
                                        Retour à l'accueil
                                    </Link>

                                    <a
                                        href="mailto:support@icc-munich.de?subject=Problème avec un lien de confirmation de rendez-vous"
                                        className="flex-1 inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    >
                                        <EnvelopeIcon className="h-4 w-4 mr-2" />
                                        Nous contacter
                                    </a>
                                </div>
                            </div>

                            <div className="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Si vous continuez à rencontrer des problèmes, n'hésitez pas à nous contacter.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}