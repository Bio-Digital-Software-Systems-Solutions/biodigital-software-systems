import { Head } from '@inertiajs/react';
import { DocumentTextIcon, ArrowDownTrayIcon } from '@heroicons/react/24/outline';
import { EventProgramme } from '@/Types/event.d';

interface Props {
    programme: EventProgramme;
    eventTitle: string;
    downloadUrl: string;
}

export default function SharedView({ programme, eventTitle, downloadUrl }: Props) {
    return (
        <div className="flex flex-col h-screen bg-gray-50 dark:bg-gray-900">
            <Head title={`Programme - ${eventTitle}`} />

            {/* Header */}
            <div className="flex-shrink-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div className="px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                            <DocumentTextIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-gray-900 dark:text-white">
                                {eventTitle}
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Programme de l'événement
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="hidden sm:flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <DocumentTextIcon className="h-4 w-4" />
                            <span>{programme.file_name}</span>
                            <span className="text-gray-300 dark:text-gray-600">·</span>
                            <span>{programme.file_size_for_humans}</span>
                        </div>
                        <a
                            href={downloadUrl}
                            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
                        >
                            <ArrowDownTrayIcon className="h-4 w-4" />
                            Télécharger
                        </a>
                    </div>
                </div>
            </div>

            {/* Preview - takes all remaining height */}
            {programme.can_preview && programme.file_url ? (
                <div className="flex-1 min-h-0">
                    {programme.is_pdf ? (
                        <object
                            data={programme.file_url}
                            type="application/pdf"
                            className="w-full h-full"
                        >
                            <div className="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                                <div className="text-center">
                                    <p>Impossible d'afficher le PDF dans le navigateur.</p>
                                    <a
                                        href={downloadUrl}
                                        className="text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block"
                                    >
                                        Télécharger le fichier
                                    </a>
                                </div>
                            </div>
                        </object>
                    ) : (
                        <div className="flex items-center justify-center h-full bg-gray-100 dark:bg-gray-900 p-4">
                            <img
                                src={programme.file_url}
                                alt={`Programme - ${eventTitle}`}
                                className="max-w-full max-h-full object-contain"
                            />
                        </div>
                    )}
                </div>
            ) : (
                <div className="flex-1 flex items-center justify-center text-gray-500 dark:text-gray-400">
                    <div className="text-center">
                        <DocumentTextIcon className="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
                        <p>Aperçu non disponible.</p>
                        <a
                            href={downloadUrl}
                            className="text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block"
                        >
                            Télécharger le fichier
                        </a>
                    </div>
                </div>
            )}
        </div>
    );
}
