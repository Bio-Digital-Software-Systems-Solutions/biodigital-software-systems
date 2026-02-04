import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import { Button } from '@/Components/ui/button';
import { Shield, Smartphone, Mail, RefreshCw } from 'lucide-react';
import axios from 'axios';
import { toast } from 'sonner';

interface Props {
    totpEnabled: boolean;
    emailEnabled: boolean;
    preferredMethod: 'totp' | 'email';
}

type MethodType = 'totp' | 'email' | 'recovery';

export default function TwoFactorChallenge({
    totpEnabled = true,
    emailEnabled = false,
    preferredMethod = 'totp',
}: Props) {
    const [method, setMethod] = useState<MethodType>(
        preferredMethod === 'email' && emailEnabled ? 'email' : 'totp'
    );
    const [emailSent, setEmailSent] = useState(false);
    const [sendingEmail, setSendingEmail] = useState(false);
    const [countdown, setCountdown] = useState(0);

    const form = useForm({
        code: '',
        recovery_code: '',
        email_code: '',
    });

    // Countdown timer for resend
    useEffect(() => {
        if (countdown > 0) {
            const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [countdown]);

    const switchMethod = (newMethod: MethodType) => {
        setMethod(newMethod);
        form.setData({
            code: '',
            recovery_code: '',
            email_code: '',
        });
        form.clearErrors();
    };

    const sendEmailCode = async () => {
        setSendingEmail(true);
        try {
            const response = await axios.post(route('two-factor.email.send'));
            setEmailSent(true);
            setCountdown(60); // 60 seconds cooldown
            toast.success(response.data.message || 'Code envoyé par email');
        } catch (error: any) {
            const message = error.response?.data?.message || 'Erreur lors de l\'envoi du code';
            toast.error(message);

            // If there's already a pending code, still mark as sent
            if (error.response?.data?.can_resend === false) {
                setEmailSent(true);
                const remaining = error.response?.data?.remaining_seconds || 0;
                setCountdown(Math.ceil(remaining));
            }
        } finally {
            setSendingEmail(false);
        }
    };

    const resendEmailCode = async () => {
        if (countdown > 0) return;

        setSendingEmail(true);
        try {
            const response = await axios.post(route('two-factor.email.resend'));
            setCountdown(60);
            toast.success(response.data.message || 'Nouveau code envoyé');
        } catch (error: any) {
            const message = error.response?.data?.message || 'Erreur lors de l\'envoi du code';
            toast.error(message);
        } finally {
            setSendingEmail(false);
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (method === 'recovery') {
            form.post(route('two-factor.login'));
        } else if (method === 'email') {
            // Submit email code to our custom endpoint
            router.post(route('two-factor.email.verify'), {
                email_code: form.data.email_code,
            }, {
                onError: (errors) => {
                    form.setError('email_code', errors.email_code || 'Code invalide');
                },
            });
        } else {
            form.post(route('two-factor.login'));
        }
    };

    const getIcon = () => {
        switch (method) {
            case 'email':
                return <Mail className="h-8 w-8 text-primary" />;
            case 'recovery':
                return <Shield className="h-8 w-8 text-primary" />;
            default:
                return <Smartphone className="h-8 w-8 text-primary" />;
        }
    };

    const getDescription = () => {
        switch (method) {
            case 'email':
                return emailSent
                    ? 'Entrez le code à 8 chiffres envoyé à votre adresse email.'
                    : 'Nous allons vous envoyer un code de vérification par email.';
            case 'recovery':
                return 'Veuillez confirmer l\'accès à votre compte en entrant l\'un de vos codes de récupération d\'urgence.';
            default:
                return 'Veuillez confirmer l\'accès à votre compte en entrant le code d\'authentification fourni par votre application d\'authentification.';
        }
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
                                    {getIcon()}
                                </div>
                            </div>

                            {/* Header */}
                            <div className="mb-8 text-center">
                                <h2 className="text-3xl font-bold text-foreground">
                                    Authentification à deux facteurs
                                </h2>
                                <p className="mt-3 text-sm text-muted-foreground leading-relaxed">
                                    {getDescription()}
                                </p>
                            </div>

                            {/* Form */}
                            <form onSubmit={submit} className="space-y-6">
                                {method === 'recovery' && (
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
                                )}

                                {method === 'totp' && (
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

                                {method === 'email' && (
                                    <div>
                                        {!emailSent ? (
                                            <Button
                                                type="button"
                                                onClick={sendEmailCode}
                                                disabled={sendingEmail}
                                                className="w-full py-3"
                                            >
                                                <Mail className="h-4 w-4 mr-2" />
                                                {sendingEmail ? 'Envoi en cours...' : 'Envoyer le code par email'}
                                            </Button>
                                        ) : (
                                            <>
                                                <label
                                                    htmlFor="email_code"
                                                    className="block text-sm font-medium text-foreground mb-2"
                                                >
                                                    Code de vérification (8 chiffres)
                                                </label>
                                                <input
                                                    id="email_code"
                                                    type="text"
                                                    inputMode="numeric"
                                                    className="w-full px-4 py-3 rounded-lg border border-input bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-all text-center text-2xl tracking-widest font-mono"
                                                    value={form.data.email_code}
                                                    onChange={(e) =>
                                                        form.setData('email_code', e.target.value.replace(/\D/g, ''))
                                                    }
                                                    autoFocus
                                                    autoComplete="one-time-code"
                                                    maxLength={8}
                                                    placeholder="00000000"
                                                />
                                                <InputError message={form.errors.email_code} className="mt-2" />

                                                {/* Resend button */}
                                                <div className="mt-3 text-center">
                                                    <button
                                                        type="button"
                                                        onClick={resendEmailCode}
                                                        disabled={countdown > 0 || sendingEmail}
                                                        className="text-sm text-muted-foreground hover:text-foreground transition-colors inline-flex items-center gap-1 disabled:opacity-50"
                                                    >
                                                        <RefreshCw className={`h-3 w-3 ${sendingEmail ? 'animate-spin' : ''}`} />
                                                        {countdown > 0
                                                            ? `Renvoyer dans ${countdown}s`
                                                            : 'Renvoyer le code'}
                                                    </button>
                                                </div>
                                                <p className="mt-2 text-xs text-muted-foreground text-center">
                                                    Le code expire dans 10 minutes
                                                </p>
                                            </>
                                        )}
                                    </div>
                                )}

                                {(method !== 'email' || emailSent) && (
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                        className="w-full py-3"
                                    >
                                        {form.processing ? 'Vérification...' : 'Se connecter'}
                                    </Button>
                                )}

                                {/* Method toggles */}
                                <div className="space-y-2 pt-2">
                                    {/* TOTP option */}
                                    {totpEnabled && method !== 'totp' && (
                                        <div className="text-center">
                                            <button
                                                type="button"
                                                onClick={() => switchMethod('totp')}
                                                className="text-sm text-muted-foreground hover:text-foreground transition-colors underline inline-flex items-center gap-1"
                                            >
                                                <Smartphone className="h-3 w-3" />
                                                Utiliser l'application d'authentification
                                            </button>
                                        </div>
                                    )}

                                    {/* Email option */}
                                    {emailEnabled && method !== 'email' && (
                                        <div className="text-center">
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    switchMethod('email');
                                                    setEmailSent(false);
                                                }}
                                                className="text-sm text-muted-foreground hover:text-foreground transition-colors underline inline-flex items-center gap-1"
                                            >
                                                <Mail className="h-3 w-3" />
                                                Recevoir un code par email
                                            </button>
                                        </div>
                                    )}

                                    {/* Recovery option */}
                                    {method !== 'recovery' && (
                                        <div className="text-center">
                                            <button
                                                type="button"
                                                onClick={() => switchMethod('recovery')}
                                                className="text-sm text-muted-foreground hover:text-foreground transition-colors underline inline-flex items-center gap-1"
                                            >
                                                <Shield className="h-3 w-3" />
                                                Utiliser un code de récupération
                                            </button>
                                        </div>
                                    )}
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
