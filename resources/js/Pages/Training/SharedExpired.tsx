import { Head, Link } from '@inertiajs/react';
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline';

interface Props {
    message: string;
}

export default function SharedExpired({ message }: Props) {
    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
            <Head title="Lien expir&eacute;" />

            <div className="max-w-md w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
                <div className="mx-auto w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mb-6">
                    <ExclamationTriangleIcon className="h-8 w-8 text-yellow-600 dark:text-yellow-400" />
                </div>

                <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    Lien d'inscription expir&eacute;
                </h1>

                <p className="text-gray-600 dark:text-gray-400 mb-8">
                    {message}
                </p>

                <Link
                    href="/"
                    className="inline-flex items-center justify-center px-6 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 transition-colors"
                >
                    Retour &agrave; l'accueil
                </Link>
            </div>
        </div>
    );
}
