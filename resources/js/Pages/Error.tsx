import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ShieldAlert, Home, ArrowLeft, Lock, AlertTriangle, ServerCrash } from 'lucide-react';

interface ErrorProps {
    status: number;
}

export default function Error({ status }: ErrorProps) {
    const getErrorContent = () => {
        switch (status) {
            case 403:
                return {
                    icon: <ShieldAlert className="h-20 w-20 text-red-500" />,
                    title: 'Accès Non Autorisé',
                    description: "Vous n'avez pas les permissions nécessaires pour accéder à cette ressource.",
                    details: "Cette action nécessite des permissions spécifiques que vous ne possédez pas actuellement. Si vous pensez que c'est une erreur, veuillez contacter votre administrateur.",
                    suggestions: [
                        'Vérifiez que vous êtes connecté avec le bon compte',
                        'Contactez un administrateur pour obtenir les permissions nécessaires',
                        'Retournez à la page d\'accueil pour accéder aux ressources autorisées'
                    ]
                };
            case 404:
                return {
                    icon: <AlertTriangle className="h-20 w-20 text-yellow-500" />,
                    title: 'Page Non Trouvée',
                    description: 'La page que vous recherchez n\'existe pas ou a été déplacée.',
                    details: "L'URL que vous avez demandée n'a pas pu être trouvée sur ce serveur. Elle peut avoir été supprimée ou déplacée.",
                    suggestions: [
                        'Vérifiez l\'URL pour vous assurer qu\'elle est correcte',
                        'Utilisez le menu de navigation pour trouver ce que vous cherchez',
                        'Retournez à la page d\'accueil'
                    ]
                };
            case 500:
                return {
                    icon: <ServerCrash className="h-20 w-20 text-red-600" />,
                    title: 'Erreur Serveur',
                    description: 'Une erreur interne s\'est produite sur le serveur.',
                    details: 'Le serveur a rencontré une erreur inattendue qui l\'a empêché de traiter votre demande. Nos équipes ont été notifiées.',
                    suggestions: [
                        'Essayez de rafraîchir la page',
                        'Réessayez dans quelques minutes',
                        'Contactez le support si le problème persiste'
                    ]
                };
            case 503:
                return {
                    icon: <ServerCrash className="h-20 w-20 text-orange-500" />,
                    title: 'Service Non Disponible',
                    description: 'Le service est temporairement indisponible.',
                    details: 'Le serveur est actuellement en maintenance ou surchargé. Veuillez réessayer plus tard.',
                    suggestions: [
                        'Réessayez dans quelques minutes',
                        'Vérifiez notre page de statut pour les mises à jour',
                        'Contactez le support si l\'indisponibilité persiste'
                    ]
                };
            case 419:
                return {
                    icon: <Lock className="h-20 w-20 text-blue-500" />,
                    title: 'Session Expirée',
                    description: 'Votre session a expiré pour des raisons de sécurité.',
                    details: 'La page a expiré en raison d\'une inactivité. Veuillez rafraîchir la page et réessayer.',
                    suggestions: [
                        'Rafraîchissez la page et réessayez',
                        'Reconnectez-vous si nécessaire',
                        'Assurez-vous que les cookies sont activés'
                    ]
                };
            default:
                return {
                    icon: <AlertTriangle className="h-20 w-20 text-gray-500" />,
                    title: `Erreur ${status}`,
                    description: 'Une erreur inattendue s\'est produite.',
                    details: 'Quelque chose s\'est mal passé lors du traitement de votre demande.',
                    suggestions: [
                        'Essayez de rafraîchir la page',
                        'Retournez à la page d\'accueil',
                        'Contactez le support si le problème persiste'
                    ]
                };
        }
    };

    const errorContent = getErrorContent();

    return (
        <>
            <Head title={`${status} - ${errorContent.title}`} />

            <div className="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-200 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 flex items-center justify-center p-4">
                <div className="max-w-2xl w-full">
                    <Card className="shadow-2xl border-2 dark:border-gray-700">
                        <CardHeader className="text-center pb-4">
                            <div className="flex justify-center mb-6">
                                {errorContent.icon}
                            </div>

                            <div className="space-y-2">
                                <div className="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-full mb-2">
                                    <span className="text-4xl font-bold text-gray-900 dark:text-gray-100">{status}</span>
                                </div>

                                <CardTitle className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {errorContent.title}
                                </CardTitle>

                                <CardDescription className="text-lg text-gray-600 dark:text-gray-400">
                                    {errorContent.description}
                                </CardDescription>
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <p className="text-sm text-gray-700 dark:text-gray-300">
                                    {errorContent.details}
                                </p>
                            </div>

                            <div>
                                <h3 className="font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                                    <AlertTriangle className="h-5 w-5 text-amber-500" />
                                    Ce que vous pouvez faire :
                                </h3>
                                <ul className="space-y-2 ml-7">
                                    {errorContent.suggestions.map((suggestion, index) => (
                                        <li key={index} className="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                            <span className="text-primary dark:text-blue-400 font-bold">•</span>
                                            <span>{suggestion}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div className="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <Button
                                    asChild
                                    className="flex-1 gap-2"
                                    size="lg"
                                >
                                    <Link href="/">
                                        <Home className="h-4 w-4" />
                                        Retour à l'accueil
                                    </Link>
                                </Button>

                                <Button
                                    variant="outline"
                                    onClick={() => window.history.back()}
                                    className="flex-1 gap-2"
                                    size="lg"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    Page précédente
                                </Button>
                            </div>

                            {status === 403 && (
                                <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                    <p className="text-sm text-amber-800 dark:text-amber-200 flex items-start gap-2">
                                        <Lock className="h-4 w-4 mt-0.5 flex-shrink-0" />
                                        <span>
                                            <strong>Besoin d'aide ?</strong> Contactez votre administrateur système ou envoyez un email à{' '}
                                            <a href="mailto:support@icc-muenchen.de" className="underline hover:text-amber-900 dark:hover:text-amber-100">
                                                support@icc-muenchen.de
                                            </a>
                                        </span>
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="mt-6 text-center">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Si vous continuez à rencontrer des problèmes, n'hésitez pas à nous contacter.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
