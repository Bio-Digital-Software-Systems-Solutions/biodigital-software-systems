import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Button } from '@/Components/ui/button';
import { Mail, CheckCircle } from 'lucide-react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <GuestLayout title="Vérification de l'email">
            <Head title="Vérification de l'email" />

            <div className="container mx-auto px-4 py-16 sm:px-6 lg:px-8">
                <div className="flex min-h-[calc(100vh-20rem)] items-center justify-center">
                    <div className="w-full max-w-md">
                        <div className="rounded-2xl border border-border bg-card p-8 shadow-lg">
                            {/* Icon */}
                            <div className="mb-6 flex justify-center">
                                <div className="rounded-full bg-primary/10 p-4">
                                    <Mail className="h-8 w-8 text-primary" />
                                </div>
                            </div>

                            {/* Header */}
                            <div className="mb-8 text-center">
                                <h2 className="text-3xl font-bold text-foreground">
                                    Vérifiez votre email
                                </h2>
                                <p className="mt-3 text-sm text-muted-foreground leading-relaxed">
                                    Merci de vous être inscrit ! Un email de bienvenue avec un lien de vérification a été envoyé à votre adresse email. Veuillez cliquer sur le lien dans l'email pour confirmer votre compte.
                                </p>
                            </div>

                            {/* Success Status */}
                            {status === 'verification-link-sent' && (
                                <div className="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                                    <div className="flex items-center gap-2">
                                        <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                                        <p className="text-sm text-green-600 dark:text-green-400">
                                            Un nouveau lien de vérification a été envoyé à votre adresse email.
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Resend Form */}
                            <form onSubmit={submit} className="space-y-6">
                                <div className="text-center space-y-4">
                                    <p className="text-sm text-muted-foreground">
                                        Vous n'avez pas reçu l'email ?
                                    </p>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full py-3"
                                        variant="outline"
                                    >
                                        {processing ? 'Envoi en cours...' : 'Renvoyer l\'email de vérification'}
                                    </Button>
                                </div>

                                {/* Dashboard Link */}
                                <div className="text-center pt-4 border-t border-border">
                                    <Link
                                        href={route('dashboard')}
                                        className="inline-flex items-center gap-2 text-sm text-primary hover:underline font-medium"
                                    >
                                        Aller au tableau de bord
                                    </Link>
                                </div>
                            </form>
                        </div>

                        {/* Additional Help */}
                        <div className="mt-6 text-center">
                            <p className="text-sm text-muted-foreground">
                                Besoin d'aide ?{' '}
                                <Link
                                    href="/#contact"
                                    className="text-primary hover:underline font-medium"
                                >
                                    Contactez-nous
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
