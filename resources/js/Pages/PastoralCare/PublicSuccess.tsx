import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { CheckCircleIcon, CalendarIcon, XCircleIcon, ClockIcon, HeartIcon } from '@heroicons/react/24/outline';

interface Props {
    action?: 'booked' | 'confirmed' | 'cancelled';
    appointmentUuid?: string;
}

export default function PublicSuccess({ action = 'booked', appointmentUuid }: Props) {
    const getSuccessContent = () => {
        switch (action) {
            case 'confirmed':
                return {
                    icon: <CheckCircleIcon className="h-16 w-16 text-green-600 mx-auto mb-6" />,
                    title: 'Rendez-vous confirmé !',
                    description: 'Votre rendez-vous de soin pastoral a été confirmé avec succès.',
                    message: `Nous avons envoyé une confirmation à votre adresse email. Vous recevrez également un rappel 24 heures avant votre rendez-vous.`,
                    bgGradient: 'from-green-50 to-emerald-100 dark:from-gray-900 dark:to-gray-800',
                    recommendations: [
                        'Marquez la date dans votre calendrier personnel',
                        'Préparez vos questions ou sujets à aborder',
                        'Prenez un moment de prière avant la rencontre',
                        'Assurez-vous d\'avoir l\'information de connexion si c\'est en visioconférence'
                    ]
                };

            case 'cancelled':
                return {
                    icon: <XCircleIcon className="h-16 w-16 text-orange-600 mx-auto mb-6" />,
                    title: 'Rendez-vous annulé',
                    description: 'Votre rendez-vous de soin pastoral a été annulé.',
                    message: `Nous comprenons que la vie peut parfois présenter des défis inattendus. Votre pasteur a été notifié de cette annulation.`,
                    bgGradient: 'from-orange-50 to-yellow-100 dark:from-gray-900 dark:to-gray-800',
                    recommendations: [
                        'N\'hésitez pas à reprendre un nouveau rendez-vous quand vous le souhaitez',
                        'En cas d\'urgence spirituelle, contactez directement votre pasteur',
                        'Participez à nos cultes et événements communautaires',
                        'Consultez les ressources spirituelles sur notre site web'
                    ]
                };

            default: // 'booked'
                return {
                    icon: <CheckCircleIcon className="h-16 w-16 text-blue-600 mx-auto mb-6" />,
                    title: 'Demande envoyée !',
                    description: 'Votre demande de rendez-vous de soin pastoral a été transmise.',
                    message: `Nous avons envoyé votre demande au pasteur sélectionné. Vous recevrez un email de confirmation que vous devrez valider pour finaliser votre rendez-vous.`,
                    bgGradient: 'from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800',
                    recommendations: [
                        'Vérifiez votre boîte email (y compris les spams)',
                        'Confirmez votre rendez-vous via le lien dans l\'email',
                        'Le pasteur sera notifié une fois votre confirmation reçue',
                        'Vous pouvez annuler à tout moment via le lien dans l\'email'
                    ]
                };
        }
    };

    const content = getSuccessContent();

    const handleBookAnother = () => {
        router.visit('/pastoral-care/public/book');
    };

    const handleGoHome = () => {
        router.visit('/');
    };

    return (
        <>
            <Head title={`${content.title} - Soin Pastoral`} />

            <div className={`min-h-screen bg-gradient-to-br ${content.bgGradient} py-12 px-4 sm:px-6 lg:px-8`}>
                <div className="max-w-2xl mx-auto">
                    {/* Header */}
                    <div className="text-center mb-8">
                        {content.icon}
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            {content.title}
                        </h1>
                        <p className="text-lg text-gray-600 dark:text-gray-300">
                            {content.description}
                        </p>
                    </div>

                    {/* Main Message Card */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle className="text-xl flex items-center">
                                <HeartIcon className="h-6 w-6 mr-2 text-blue-600" />
                                Soin Pastoral - ICC Munich
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                {content.message}
                            </p>

                            {action === 'booked' && (
                                <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                    <h4 className="font-medium text-blue-800 dark:text-blue-200 mb-2 flex items-center">
                                        <ClockIcon className="h-5 w-5 mr-2" />
                                        Prochaines étapes
                                    </h4>
                                    <ol className="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                                        <li className="flex items-start">
                                            <span className="bg-blue-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center mr-2 mt-0.5 flex-shrink-0">1</span>
                                            <span>Vérifiez votre email pour le lien de confirmation</span>
                                        </li>
                                        <li className="flex items-start">
                                            <span className="bg-blue-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center mr-2 mt-0.5 flex-shrink-0">2</span>
                                            <span>Cliquez sur "Confirmer le rendez-vous" dans l'email</span>
                                        </li>
                                        <li className="flex items-start">
                                            <span className="bg-blue-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center mr-2 mt-0.5 flex-shrink-0">3</span>
                                            <span>Recevez la confirmation finale de votre pasteur</span>
                                        </li>
                                    </ol>
                                </div>
                            )}

                            {action === 'confirmed' && (
                                <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                    <h4 className="font-medium text-green-800 dark:text-green-200 mb-2">
                                        ✅ Votre rendez-vous est maintenant confirmé
                                    </h4>
                                    <p className="text-sm text-green-700 dark:text-green-300">
                                        Vous recevrez un rappel par email 24 heures avant votre rendez-vous avec toutes les informations nécessaires.
                                    </p>
                                </div>
                            )}

                            {action === 'cancelled' && (
                                <div className="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                                    <h4 className="font-medium text-orange-800 dark:text-orange-200 mb-2">
                                        Votre pasteur a été notifié
                                    </h4>
                                    <p className="text-sm text-orange-700 dark:text-orange-300">
                                        Il comprendra votre situation et reste disponible pour un futur accompagnement.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recommendations Card */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle className="text-lg">
                                {action === 'cancelled' ? 'Ressources et accompagnement' : 'Recommandations'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-3">
                                {content.recommendations.map((recommendation, index) => (
                                    <li key={index} className="flex items-start space-x-3">
                                        <div className="flex-shrink-0 w-2 h-2 bg-blue-600 rounded-full mt-2"></div>
                                        <span className="text-gray-700 dark:text-gray-300">{recommendation}</span>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>

                    {/* Contact Information Card */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle className="text-lg">Besoin d'aide ?</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h4 className="font-medium text-gray-900 dark:text-white mb-2">Support technique</h4>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Problèmes avec la réservation ?
                                    </p>
                                    <a href="mailto:info@icc-munich.de" className="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                        info@icc-munich.de
                                    </a>
                                </div>
                                <div>
                                    <h4 className="font-medium text-gray-900 dark:text-white mb-2">Urgence spirituelle</h4>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Besoin d'un accompagnement immédiat ?
                                    </p>
                                    <a href="tel:+4989123456789" className="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                        +49 89 123456789
                                    </a>
                                </div>
                            </div>

                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 className="font-medium text-gray-900 dark:text-white mb-2">ICC Munich</h4>
                                <div className="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <p>International Christian Community Munich</p>
                                    <p>Site web : <a href="https://icc-munich.de" target="_blank" rel="noopener noreferrer" className="text-blue-600 dark:text-blue-400 hover:underline">icc-munich.de</a></p>
                                    <p>Nos cultes, études bibliques et événements communautaires sont ouverts à tous.</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Action Buttons */}
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex flex-col sm:flex-row gap-4">
                                {action !== 'cancelled' && (
                                    <Button
                                        onClick={handleBookAnother}
                                        variant="outline"
                                        className="flex-1"
                                    >
                                        <CalendarIcon className="h-5 w-5 mr-2" />
                                        Prendre un autre rendez-vous
                                    </Button>
                                )}

                                {action === 'cancelled' && (
                                    <Button
                                        onClick={handleBookAnother}
                                        className="flex-1 bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        <CalendarIcon className="h-5 w-5 mr-2" />
                                        Prendre un nouveau rendez-vous
                                    </Button>
                                )}

                                <Button
                                    onClick={handleGoHome}
                                    variant={action === 'cancelled' ? 'outline' : 'default'}
                                    className="flex-1"
                                >
                                    Retour à l'accueil
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Spiritual Message */}
                    <div className="text-center mt-8 p-6 bg-white/50 dark:bg-gray-800/50 rounded-lg backdrop-blur-sm">
                        <p className="text-gray-700 dark:text-gray-300 italic">
                            "Venez à moi, vous tous qui êtes fatigués et chargés, et je vous donnerai du repos."
                        </p>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-2">- Matthieu 11:28</p>
                    </div>
                </div>
            </div>
        </>
    );
}