import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Button } from '@/Components/ui/button';
import { Mail, ArrowLeft } from 'lucide-react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout title="Mot de passe oublié">
            <Head title="Mot de passe oublié" />

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
                                    Mot de passe oublié ?
                                </h2>
                                <p className="mt-3 text-sm text-muted-foreground leading-relaxed">
                                    Pas de problème. Indiquez-nous votre adresse email et nous vous enverrons un lien de réinitialisation de mot de passe.
                                </p>
                            </div>

                            {/* Success Status */}
                            {status && (
                                <div className="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                                    <p className="text-sm text-green-600 dark:text-green-400 text-center">
                                        {status}
                                    </p>
                                </div>
                            )}

                            {/* Form */}
                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <label
                                        htmlFor="email"
                                        className="block text-sm font-medium text-foreground mb-2"
                                    >
                                        Adresse email
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="w-full px-4 py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all"
                                        autoFocus
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="votre@email.com"
                                    />
                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full py-3"
                                >
                                    {processing ? 'Envoi en cours...' : 'Envoyer le lien de réinitialisation'}
                                </Button>

                                {/* Back to Login */}
                                <div className="text-center">
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
                                    >
                                        <ArrowLeft className="h-4 w-4" />
                                        Retour à la connexion
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
