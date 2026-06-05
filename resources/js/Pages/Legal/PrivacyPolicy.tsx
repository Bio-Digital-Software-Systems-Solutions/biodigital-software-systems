import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Shield, Eye, Database, Users, Mail, Phone, MapPin } from 'lucide-react';
import type { PageProps } from '@/Types';

export default function PrivacyPolicy() {
    const appName = usePage<PageProps>().props.app.name;

    return (
        <>
            <Head title="Politique de confidentialité" />

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
                            <Shield className="h-8 w-8 text-primary" />
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Politique de confidentialité
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
                                Introduction
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                Cette politique de confidentialité décrit comment {appName} collecte, utilise et protège
                                vos informations personnelles lorsque vous utilisez notre plateforme de gestion organisationnelle.
                                Nous nous engageons à protéger votre vie privée et à traiter vos données personnelles de manière
                                transparente et sécurisée.
                            </p>
                        </section>

                        {/* Données collectées */}
                        <section>
                            <div className="flex items-center gap-2 mb-4">
                                <Database className="h-5 w-5 text-primary" />
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Données que nous collectons
                                </h2>
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Informations d'identification
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Nom et prénom</li>
                                        <li>Adresse e-mail</li>
                                        <li>Numéro de téléphone</li>
                                        <li>Adresse postale</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Données d'utilisation
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Historique de navigation sur la plateforme</li>
                                        <li>Interactions avec les événements et formations</li>
                                        <li>Préférences de notification</li>
                                        <li>Fichiers téléchargés (articles, supports de cours)</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Données techniques
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Adresse IP</li>
                                        <li>Type de navigateur et version</li>
                                        <li>Système d'exploitation</li>
                                        <li>Données de cookies et stockage local</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        {/* Utilisation des données */}
                        <section>
                            <div className="flex items-center gap-2 mb-4">
                                <Eye className="h-5 w-5 text-primary" />
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Comment nous utilisons vos données
                                </h2>
                            </div>

                            <div className="grid md:grid-cols-2 gap-6">
                                <div className="space-y-3">
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                        Fonctionnement de la plateforme
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Authentification et gestion des comptes</li>
                                        <li>Personnalisation de l'expérience utilisateur</li>
                                        <li>Gestion des événements et inscriptions</li>
                                        <li>Système de prêt de livres</li>
                                    </ul>
                                </div>

                                <div className="space-y-3">
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                        Communication
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Envoi de notifications importantes</li>
                                        <li>Rappels d'événements</li>
                                        <li>Mises à jour sur les formations</li>
                                        <li>Newsletter (avec votre consentement)</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        {/* Partage des données */}
                        <section>
                            <div className="flex items-center gap-2 mb-4">
                                <Users className="h-5 w-5 text-primary" />
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Partage des données
                                </h2>
                            </div>

                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                                <p className="text-blue-800 dark:text-blue-200 font-medium">
                                    🔒 Nous ne vendons jamais vos données personnelles à des tiers.
                                </p>
                            </div>

                            <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                                Vos données peuvent être partagées uniquement dans les cas suivants :
                            </p>

                            <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2">
                                <li>Avec votre consentement explicite</li>
                                <li>Pour répondre à une obligation légale</li>
                                <li>Avec nos prestataires de services techniques (hébergement, maintenance)</li>
                                <li>En cas de fusion, acquisition ou vente d'actifs (après notification)</li>
                            </ul>
                        </section>

                        {/* Sécurité */}
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                                Sécurité de vos données
                            </h2>

                            <div className="grid md:grid-cols-2 gap-6">
                                <div className="space-y-3">
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                        Mesures techniques
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Chiffrement SSL/TLS</li>
                                        <li>Authentification sécurisée</li>
                                        <li>Surveillance des accès</li>
                                        <li>Sauvegardes régulières</li>
                                    </ul>
                                </div>

                                <div className="space-y-3">
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                        Mesures organisationnelles
                                    </h3>
                                    <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                                        <li>Accès limité aux données</li>
                                        <li>Formation du personnel</li>
                                        <li>Politiques de sécurité strictes</li>
                                        <li>Audits de sécurité réguliers</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        {/* Vos droits */}
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                                Vos droits (RGPD)
                            </h2>

                            <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 space-y-4">
                                <div className="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <h3 className="font-medium text-green-800 dark:text-green-200 mb-2">
                                            ✓ Droit d'accès
                                        </h3>
                                        <p className="text-green-700 dark:text-green-300 text-sm">
                                            Consulter les données que nous avons sur vous
                                        </p>
                                    </div>

                                    <div>
                                        <h3 className="font-medium text-green-800 dark:text-green-200 mb-2">
                                            ✓ Droit de rectification
                                        </h3>
                                        <p className="text-green-700 dark:text-green-300 text-sm">
                                            Corriger les informations inexactes
                                        </p>
                                    </div>

                                    <div>
                                        <h3 className="font-medium text-green-800 dark:text-green-200 mb-2">
                                            ✓ Droit à l'effacement
                                        </h3>
                                        <p className="text-green-700 dark:text-green-300 text-sm">
                                            Demander la suppression de vos données
                                        </p>
                                    </div>

                                    <div>
                                        <h3 className="font-medium text-green-800 dark:text-green-200 mb-2">
                                            ✓ Droit à la portabilité
                                        </h3>
                                        <p className="text-green-700 dark:text-green-300 text-sm">
                                            Récupérer vos données dans un format standard
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* Contact */}
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                                Nous contacter
                            </h2>

                            <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                <p className="text-gray-700 dark:text-gray-300 mb-4">
                                    Pour toute question concernant cette politique de confidentialité ou pour exercer vos droits :
                                </p>

                                <div className="grid md:grid-cols-3 gap-4">
                                    <div className="flex items-center gap-2">
                                        <Mail className="h-4 w-4 text-primary" />
                                        <span className="text-sm text-gray-600 dark:text-gray-400">
                                            privacy@icc-munich.org
                                        </span>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Phone className="h-4 w-4 text-primary" />
                                        <span className="text-sm text-gray-600 dark:text-gray-400">
                                            +49 89 123 456 789
                                        </span>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 text-primary" />
                                        <span className="text-sm text-gray-600 dark:text-gray-400">
                                            Munich, Allemagne
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* Footer */}
                        <section className="border-t pt-6">
                            <p className="text-sm text-gray-500 dark:text-gray-400 text-center">
                                Cette politique peut être mise à jour périodiquement.
                                Nous vous informerons des changements importants via notre plateforme.
                            </p>
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}