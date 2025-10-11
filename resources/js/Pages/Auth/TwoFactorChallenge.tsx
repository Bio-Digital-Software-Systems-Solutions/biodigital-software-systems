import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Shield, Smartphone } from 'lucide-react';

export default function TwoFactorChallenge() {
    const [recovery, setRecovery] = useState(false);

    const form = useForm({
        code: '',
        recovery_code: '',
    });

    const toggleRecovery: FormEventHandler = (e) => {
        e.preventDefault();
        const isRecovery = !recovery;
        setRecovery(isRecovery);
        form.setData({
            code: '',
            recovery_code: '',
        });
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('two-factor.login'));
    };

    return (
        <GuestLayout title="Authentification à deux facteurs">
            <Head title="Authentification à deux facteurs" />

            <div className="container mx-auto px-4 py-16 sm:px-6 lg:px-8">
                <div className="flex min-h-[calc(100vh-20rem)] items-center justify-center">
                    <div className="w-full max-w-md">
                        <div className="rounded-2xl border border-border bg-card p-8 shadow-lg">
                            {/* Icon */}
                            <div className="mb-6 flex justify-center">
                                <div className="rounded-full bg-primary/10 p-4">
                                    {recovery ? (
                                        <Shield className="h-8 w-8 text-primary" />
                                    ) : (
                                        <Smartphone className="h-8 w-8 text-primary" />
                                    )}
                                </div>
                            </div>

                            {/* Header */}
                            <div className="mb-8 text-center">
                                <h2 className="text-3xl font-bold text-foreground">
                                    Authentification à deux facteurs
                                </h2>
                                <p className="mt-3 text-sm text-muted-foreground leading-relaxed">
                                    {recovery
                                        ? 'Veuillez confirmer l\'accès à votre compte en entrant l\'un de vos codes de récupération d\'urgence.'
                                        : 'Veuillez confirmer l\'accès à votre compte en entrant le code d\'authentification fourni par votre application d\'authentification.'}
                                </p>
                            </div>

                            {/* Form */}
                            <form onSubmit={submit} className="space-y-6">
                                {recovery ? (
                                    <div>
                                        <label
                                            htmlFor="recovery_code"
                                            className="block text-sm font-medium text-foreground mb-2"
                                        >
                                            Code de récupération
                                        </label>
                                        <input
                                            id="recovery_code"
                                            type="text"
                                            className="w-full px-4 py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all font-mono"
                                            value={form.data.recovery_code}
                                            onChange={(e) =>
                                                form.setData('recovery_code', e.target.value)
                                            }
                                            autoFocus
                                            autoComplete="one-time-code"
                                            placeholder="XXXXX-XXXXX"
                                        />
                                        <InputError message={form.errors.recovery_code} className="mt-2" />
                                    </div>
                                ) : (
                                    <div>
                                        <label
                                            htmlFor="code"
                                            className="block text-sm font-medium text-foreground mb-2"
                                        >
                                            Code d'authentification
                                        </label>
                                        <input
                                            id="code"
                                            type="text"
                                            inputMode="numeric"
                                            className="w-full px-4 py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all text-center text-2xl tracking-widest font-mono"
                                            value={form.data.code}
                                            onChange={(e) =>
                                                form.setData('code', e.target.value.replace(/\D/g, ''))
                                            }
                                            autoFocus
                                            autoComplete="one-time-code"
                                            maxLength={6}
                                            placeholder="000000"
                                        />
                                        <InputError message={form.errors.code} className="mt-2" />
                                    </div>
                                )}

                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    className="w-full py-3"
                                >
                                    {form.processing ? 'Vérification...' : 'Se connecter'}
                                </Button>

                                {/* Toggle Recovery */}
                                <div className="text-center">
                                    <button
                                        type="button"
                                        onClick={toggleRecovery}
                                        className="text-sm text-muted-foreground hover:text-foreground transition-colors underline"
                                    >
                                        {recovery
                                            ? 'Utiliser un code d\'authentification'
                                            : 'Utiliser un code de récupération'}
                                    </button>
                                </div>
                            </form>
                        </div>

                        {/* Additional Help */}
                        <div className="mt-6 text-center">
                            <p className="text-sm text-muted-foreground">
                                Problème de connexion ?{' '}
                                <a
                                    href="/#contact"
                                    className="text-primary hover:underline font-medium"
                                >
                                    Contactez le support
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
