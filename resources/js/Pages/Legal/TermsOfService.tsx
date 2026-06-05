import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, FileText, User, Shield, AlertTriangle, CheckCircle, Mail, Scale } from 'lucide-react';
import type { PageProps } from '@/Types';

export default function TermsOfService() {
    const appName = usePage<PageProps>().props.app.name;

    return (
        <>
            <Head title="Conditions d'utilisation" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
                <div className="max-w-4xl mx-auto">
                    {/* Header with back link */}
                    <div className="mb-8">
                        <Link
                            href="/"
                            className="inline-flex items-center text-primary hover:text-primary/80 font-medium mb-4"
                        >
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Retour à l'accueil
                        </Link>

                        <div className="flex items-center gap-3 mb-4">
                            <FileText className="h-8 w-8 text-primary" />
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Conditions d'utilisation
                            </h1>
                        </div>

                        <p className="text-gray-600 dark:text-gray-400">
                            Dernière mise à jour : {new Date().toLocaleDateString('fr-FR')}
                        </p>
                    </div>

                    {/* Content */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 space-y-8">
                        {/* Introduction */}
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                                Bienvenue sur {appName}
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                Ces conditions d'utilisation régissent votre accès et utilisation de la plateforme
                                de gestion organisationnelle {appName}. En utilisant nos services, vous acceptez
                                ces conditions dans leur intégralité. Veuillez les lire attentivement.
                            </p>
                        </section>

                        {/* Acceptation */}
                        <section>
                            <div className="flex items-center gap-2 mb-4">
                                <CheckCircle className="h-5 w-5 text-green-500" />
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Acceptation des conditions
                                </h2>
                            </div>

                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <p className="text-blue-800 dark:text-blue-200">
                                    En créant un compte ou en utilisant nos services, vous confirmez que :
                                </p>
                                <ul className="list-disc list-inside text-blue-700 dark:text-blue-300 mt-2 space-y-1">
                                    <li>Vous avez lu et compris ces conditions</li>
                                    <li>Vous acceptez d'être lié par ces termes</li>
                                    <li>Vous avez l'autorité légale pour conclure cet accord</li>
                                </ul>
                            </div>
                        </section>

                        {/* Utilisation du service */}
                        <section>
                            <div className="flex items-center gap-2 mb-4">
                                <User className="h-5 w-5 text-primary" />
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Utilisation du service
                                </h2>
                            </div>

                            <div className="space-y-6">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-3">
                                        Services disponibles
                                    </h3>
                                    <div className="grid md:grid-cols-2 gap-4">
                                        <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                            <li>Gestion d'événements et inscriptions</li>
                                            <li>Système de prêt de livres</li>
                                            <li>Publication et lecture d'articles</li>
                                            <li>Messagerie et chat en temps réel</li>
                                        </ul>
                                        <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                            <li>Gestion de projets et tâches</li>
                                            <li>Formations et supports de cours</li>
                                            <li>Système de notifications</li>
                                            <li>Gestion des départements et groupes</li>
                                        </ul>
                                    </div>
                                </div>

                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-3">
                                        Compte utilisateur
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2">
                                        <li>Vous êtes responsable de la confidentialité de vos identifiants</li>
                                        <li>Vous devez fournir des informations exactes et à jour</li>
                                        <li>Un seul compte par utilisateur est autorisé</li>
                                        <li>Vous devez signaler immédiatement toute utilisation non autorisée</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        {/* Conduites interdites */}
                        <section>
                            <div className="flex items-center gap-2 mb-4">
                                <AlertTriangle className="h-5 w-5 text-red-500" />
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Conduites interdites
                                </h2>
                            </div>

                            <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                                <p className="text-red-800 dark:text-red-200 font-medium mb-2">
                                    Il est strictement interdit de :
                                </p>
                                <div className="grid md:grid-cols-2 gap-4">
                                    <ul className="list-disc list-inside text-red-700 dark:text-red-300 space-y-1">
                                        <li>Utiliser le service à des fins illégales</li>
                                        <li>Publier du contenu offensant ou diffamatoire</li>
                                        <li>Violer les droits d'auteur</li>
                                        <li>Partager des informations confidentielles</li>
                                    </ul>
                                    <ul className="list-disc list-inside text-red-700 dark:text-red-300 space-y-1">
                                        <li>Tenter d'accéder aux comptes d'autres utilisateurs</li>
                                        <li>Perturber le fonctionnement du service</li>
                                        <li>Utiliser des robots ou scripts automatisés</li>
                                        <li>Revendre ou transférer votre compte</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        {/* Contenu utilisateur */}
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                                Contenu utilisateur
                            </h2>

                            <div className="space-y-4">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Propriété du contenu
                                    </h3>
                                    <p className="text-gray-700 dark:text-gray-300">
                                        Vous conservez la propriété de tout contenu que vous publiez sur la plateforme.
                                        Cependant, vous nous accordez une licence non exclusive pour héberger, afficher
                                        et distribuer ce contenu dans le cadre de nos services.
                                    </p>
                                </div>

                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Responsabilité du contenu
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Vous êtes seul responsable du contenu que vous publiez</li>
                                        <li>Le contenu doit respecter les lois en vigueur</li>
                                        <li>Nous nous réservons le droit de supprimer du contenu inapproprié</li>
                                        <li>Vous devez disposer des droits nécessaires pour publier le contenu</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        {/* Propriété intellectuelle */}
                        <section>
                            <div className="flex items-center gap-2 mb-4">
                                <Shield className="h-5 w-5 text-primary" />
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Propriété intellectuelle
                                </h2>
                            </div>

                            <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 space-y-4">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Propriété de la plateforme
                                    </h3>
                                    <p className="text-gray-700 dark:text-gray-300">
                                        La plateforme {appName}, incluant son code, design, logos et fonctionnalités,
                                        est protégée par les droits d'auteur et autres droits de propriété intellectuelle.
                                    </p>
                                </div>

                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Licence d'utilisation
                                    </h3>
                                    <p className="text-gray-700 dark:text-gray-300">
                                        Nous vous accordons une licence limitée, non exclusive et révocable pour utiliser
                                        notre service conformément à ces conditions.
                                    </p>
                                </div>
                            </div>
                        </section>

                        {/* Limitation de responsabilité */}
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                                Limitation de responsabilité
                            </h2>

                            <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <p className="text-yellow-800 dark:text-yellow-200 mb-2">
                                    ⚠️ Le service est fourni "en l'état" sans garantie d'aucune sorte.
                                </p>
                                <ul className="list-disc list-inside text-yellow-700 dark:text-yellow-300 space-y-1">
                                    <li>Nous ne garantissons pas un service ininterrompu</li>
                                    <li>Nous ne sommes pas responsables des pertes de données</li>
                                    <li>Notre responsabilité est limitée aux montants payés (le cas échéant)</li>
                                    <li>Vous utilisez le service à vos propres risques</li>
                                </ul>
                            </div>
                        </section>

                        {/* Résiliation */}
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                                Résiliation
                            </h2>

                            <div className="grid md:grid-cols-2 gap-6">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Par l'utilisateur
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Vous pouvez fermer votre compte à tout moment</li>
                                        <li>Suppression via les paramètres du compte</li>
                                        <li>Demande par e-mail si nécessaire</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Par {appName}
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Violation des conditions d'utilisation</li>
                                        <li>Inactivité prolongée du compte</li>
                                        <li>Comportement inapproprié</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        {/* Droit applicable */}
                        <section>
                            <div className="flex items-center gap-2 mb-4">
                                <Scale className="h-5 w-5 text-primary" />
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Droit applicable
                                </h2>
                            </div>

                            <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                Ces conditions sont régies par le droit allemand. Tout litige sera soumis à la
                                compétence exclusive des tribunaux de Munich, Allemagne. Si une disposition de
                                ces conditions est jugée invalide, les autres dispositions restent en vigueur.
                            </p>
                        </section>

                        {/* Modifications */}
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                                Modifications des conditions
                            </h2>

                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <p className="text-blue-800 dark:text-blue-200">
                                    Nous nous réservons le droit de modifier ces conditions à tout moment.
                                    Les utilisateurs seront informés des changements importants via :
                                </p>
                                <ul className="list-disc list-inside text-blue-700 dark:text-blue-300 mt-2 space-y-1">
                                    <li>Notification sur la plateforme</li>
                                    <li>E-mail aux utilisateurs actifs</li>
                                    <li>Mise à jour de la date de modification</li>
                                </ul>
                            </div>
                        </section>

                        {/* Contact */}
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                                Nous contacter
                            </h2>

                            <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <p className="text-gray-700 dark:text-gray-300 mb-4">
                                    Pour toute question concernant ces conditions d'utilisation :
                                </p>

                                <div className="flex items-center gap-2">
                                    <Mail className="h-4 w-4 text-primary" />
                                    <a
                                        href="mailto:legal@icc-munich.org"
                                        className="text-primary hover:text-primary/80 font-medium"
                                    >
                                        legal@icc-munich.org
                                    </a>
                                </div>
                            </div>
                        </section>

                        {/* Footer */}
                        <section className="border-t pt-6">
                            <p className="text-sm text-gray-500 dark:text-gray-400 text-center">
                                En continuant à utiliser {appName}, vous acceptez la version la plus récente
                                de ces conditions d'utilisation.
                            </p>
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}