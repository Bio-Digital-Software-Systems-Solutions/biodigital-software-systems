import { Button } from '@/Components/ui/button';
import { useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { FormEventHandler, useState, useEffect } from 'react';
import { toast } from 'sonner';
import { Shield, Smartphone, Key, CheckCircle2, XCircle, Mail } from 'lucide-react';
import { apiLogger } from '@/utils/logger';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface Props {
    className?: string;
    hideHeader?: boolean;
}

export default function TwoFactorAuthenticationForm({ className = '', hideHeader = false }: Props) {
    const user = usePage().props.auth.user as any;
    const [enabling, setEnabling] = useState(false);
    const [confirmingPassword, setConfirmingPassword] = useState(false);
    const [qrCode, setQrCode] = useState<string | null>(null);
    const [setupKey, setSetupKey] = useState<string | null>(null);
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
    const [showDisableDialog, setShowDisableDialog] = useState(false);
    const [disableTarget, setDisableTarget] = useState<'totp' | 'email' | null>(null);

    // Email 2FA state
    const [emailTwoFactorEnabled, setEmailTwoFactorEnabled] = useState(user.email_two_factor_enabled || false);
    const [preferredMethod, setPreferredMethod] = useState<string | null>(user.preferred_two_factor_method);

    const passwordForm = useForm({
        password: '',
    });

    const confirmPassword: FormEventHandler = (e) => {
        e.preventDefault();

        passwordForm.post(route('password.confirm'), {
            preserveScroll: true,
            onSuccess: () => {
                setConfirmingPassword(false);
                if (enabling) {
                    enableTwoFactorAuthentication();
                }
            },
            onError: () => {
                toast.error('Mot de passe incorrect');
            },
        });
    };

    const enableTwoFactorAuthentication = async () => {
        try {
            // Enable 2FA
            await axios.post(route('two-factor.enable'));

            // Fetch QR code
            const qrResponse = await axios.get(route('two-factor.qr-code'));
            setQrCode(qrResponse.data.svg);

            // Fetch setup key
            const keyResponse = await axios.get(route('two-factor.secret-key'));
            setSetupKey(keyResponse.data.secretKey);

            // Fetch recovery codes
            const codesResponse = await axios.get(route('two-factor.recovery-codes'));
            setRecoveryCodes(codesResponse.data);

            toast.success('Authentification à deux facteurs activée !');

            // Reload page to update user status
            window.location.reload();
        } catch (error) {
            toast.error('Erreur lors de l\'activation de l\'authentification à deux facteurs');
            apiLogger.error('Error enabling 2FA', error);
        }
    };

    const disableTwoFactorAuthentication = async () => {
        try {
            await axios.delete(route('two-factor.disable'));
            toast.success('Authentification par application désactivée');
            setQrCode(null);
            setSetupKey(null);
            setRecoveryCodes([]);
            window.location.reload();
        } catch (error) {
            toast.error('Erreur lors de la désactivation');
            apiLogger.error('Error disabling 2FA', error);
        }
    };

    const enableEmailTwoFactor = async () => {
        try {
            await axios.post(route('email-two-factor.enable'));
            setEmailTwoFactorEnabled(true);
            toast.success('Authentification par email activée !');
            window.location.reload();
        } catch (error) {
            toast.error('Erreur lors de l\'activation de l\'authentification par email');
            apiLogger.error('Error enabling email 2FA', error);
        }
    };

    const disableEmailTwoFactor = async () => {
        try {
            await axios.delete(route('email-two-factor.disable'));
            setEmailTwoFactorEnabled(false);
            toast.success('Authentification par email désactivée');
            window.location.reload();
        } catch (error) {
            toast.error('Erreur lors de la désactivation');
            apiLogger.error('Error disabling email 2FA', error);
        }
    };

    const handleDisableConfirm = async () => {
        if (disableTarget === 'totp') {
            await disableTwoFactorAuthentication();
        } else if (disableTarget === 'email') {
            await disableEmailTwoFactor();
        }
        setShowDisableDialog(false);
        setDisableTarget(null);
    };

    const setPreferred = async (method: 'totp' | 'email') => {
        try {
            await axios.post(route('email-two-factor.preferred-method'), { method });
            setPreferredMethod(method);
            toast.success('Méthode préférée mise à jour');
        } catch (error) {
            toast.error('Erreur lors de la mise à jour');
        }
    };

    const regenerateRecoveryCodes = async () => {
        try {
            await axios.post(route('two-factor.recovery-codes'));
            const codesResponse = await axios.get(route('two-factor.recovery-codes'));
            setRecoveryCodes(codesResponse.data);
            toast.success('Codes de récupération régénérés');
        } catch (error) {
            toast.error('Erreur lors de la régénération des codes');
        }
    };

    const showRecoveryCodes = async () => {
        try {
            const codesResponse = await axios.get(route('two-factor.recovery-codes'));
            setRecoveryCodes(codesResponse.data);
        } catch (error) {
            toast.error('Erreur lors du chargement des codes');
        }
    };

    const downloadRecoveryCodes = () => {
        const text = recoveryCodes.join('\n');
        const blob = new Blob([text], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'recovery-codes.txt';
        a.click();
        window.URL.revokeObjectURL(url);
        toast.success('Codes de récupération téléchargés');
    };

    const handleEnable = () => {
        setEnabling(true);
        setConfirmingPassword(true);
    };

    const hasTotpEnabled = !!user.two_factor_confirmed_at;
    const hasAny2FA = hasTotpEnabled || emailTwoFactorEnabled;

    return (
        <section className={className}>
            {!hideHeader && (
                <header>
                    <div className="flex items-center gap-3 mb-2">
                        <Shield className="h-6 w-6 text-primary" />
                        <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Authentification à deux facteurs (2FA)
                        </h2>
                    </div>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Ajoutez une couche de sécurité supplémentaire à votre compte en utilisant l'authentification à deux facteurs.
                    </p>
                </header>
            )}

            <div className={`${hideHeader ? '' : 'mt-6'} space-y-6`}>
                {/* Overall Status */}
                <div className="flex items-center gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    {hasAny2FA ? (
                        <>
                            <CheckCircle2 className="h-5 w-5 text-green-600 dark:text-green-400" />
                            <div className="flex-1">
                                <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    Authentification à deux facteurs activée
                                </p>
                                <p className="text-xs text-gray-600 dark:text-gray-400">
                                    Votre compte est protégé par l'authentification à deux facteurs.
                                </p>
                            </div>
                        </>
                    ) : (
                        <>
                            <XCircle className="h-5 w-5 text-gray-400 dark:text-gray-600" />
                            <div className="flex-1">
                                <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    Authentification à deux facteurs désactivée
                                </p>
                                <p className="text-xs text-gray-600 dark:text-gray-400">
                                    Activez l'authentification à deux facteurs pour sécuriser votre compte.
                                </p>
                            </div>
                        </>
                    )}
                </div>

                {/* Methods Section */}
                <div className="space-y-4">
                    {/* TOTP Method */}
                    <div className="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div className="flex items-start justify-between">
                            <div className="flex items-start gap-3">
                                <Smartphone className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                                <div>
                                    <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        Application d'authentification
                                    </h3>
                                    <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Utilisez Microsoft Authenticator, Google Authenticator ou une application similaire.
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                {hasTotpEnabled && preferredMethod === 'totp' && (
                                    <span className="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                        Préféré
                                    </span>
                                )}
                                {hasTotpEnabled ? (
                                    <CheckCircle2 className="h-5 w-5 text-green-600 dark:text-green-400" />
                                ) : (
                                    <XCircle className="h-5 w-5 text-gray-400" />
                                )}
                            </div>
                        </div>

                        {!hasTotpEnabled ? (
                            <div className="mt-4">
                                <div className="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-4">
                                    <h4 className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                                        Comment ça marche ?
                                    </h4>
                                    <ol className="list-decimal list-inside text-xs text-blue-800 dark:text-blue-200 space-y-1">
                                        <li>Activez l'authentification à deux facteurs</li>
                                        <li>Scannez le QR code avec votre application</li>
                                        <li>Conservez vos codes de récupération en lieu sûr</li>
                                        <li>À chaque connexion, entrez le code de votre application</li>
                                    </ol>
                                </div>
                                <Button
                                    onClick={handleEnable}
                                    disabled={passwordForm.processing}
                                    size="sm"
                                >
                                    <Smartphone className="h-4 w-4 mr-2" />
                                    Activer
                                </Button>
                            </div>
                        ) : (
                            <div className="mt-4 space-y-4">
                                {qrCode && (
                                    <div className="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                            Scannez ce QR code :
                                        </h4>
                                        <div
                                            className="flex justify-center mb-3"
                                            dangerouslySetInnerHTML={{ __html: qrCode }}
                                        />
                                        {setupKey && (
                                            <div className="mt-3">
                                                <p className="text-xs text-gray-600 dark:text-gray-400 mb-1">
                                                    Ou entrez cette clé manuellement :
                                                </p>
                                                <code className="block p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs font-mono text-center break-all">
                                                    {setupKey}
                                                </code>
                                            </div>
                                        )}
                                    </div>
                                )}

                                <div className="flex flex-wrap gap-2">
                                    {!qrCode && (
                                        <Button onClick={showRecoveryCodes} variant="outline" size="sm">
                                            <Key className="h-4 w-4 mr-2" />
                                            Voir les codes
                                        </Button>
                                    )}
                                    <Button onClick={regenerateRecoveryCodes} variant="outline" size="sm">
                                        Régénérer les codes
                                    </Button>
                                    {emailTwoFactorEnabled && preferredMethod !== 'totp' && (
                                        <Button onClick={() => setPreferred('totp')} variant="outline" size="sm">
                                            Définir comme préféré
                                        </Button>
                                    )}
                                    <Button
                                        onClick={() => {
                                            setDisableTarget('totp');
                                            setShowDisableDialog(true);
                                        }}
                                        variant="destructive"
                                        size="sm"
                                    >
                                        Désactiver
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Email Method */}
                    <div className="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div className="flex items-start justify-between">
                            <div className="flex items-start gap-3">
                                <Mail className="h-5 w-5 text-purple-600 dark:text-purple-400 mt-0.5" />
                                <div>
                                    <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        Code par email
                                    </h3>
                                    <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        Recevez un code à 8 chiffres par email à chaque connexion. Le code expire après 10 minutes.
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                {emailTwoFactorEnabled && preferredMethod === 'email' && (
                                    <span className="text-xs px-2 py-1 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                        Préféré
                                    </span>
                                )}
                                {emailTwoFactorEnabled ? (
                                    <CheckCircle2 className="h-5 w-5 text-green-600 dark:text-green-400" />
                                ) : (
                                    <XCircle className="h-5 w-5 text-gray-400" />
                                )}
                            </div>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
                            {!emailTwoFactorEnabled ? (
                                <Button onClick={enableEmailTwoFactor} size="sm">
                                    <Mail className="h-4 w-4 mr-2" />
                                    Activer
                                </Button>
                            ) : (
                                <>
                                    {hasTotpEnabled && preferredMethod !== 'email' && (
                                        <Button onClick={() => setPreferred('email')} variant="outline" size="sm">
                                            Définir comme préféré
                                        </Button>
                                    )}
                                    <Button
                                        onClick={() => {
                                            setDisableTarget('email');
                                            setShowDisableDialog(true);
                                        }}
                                        variant="destructive"
                                        size="sm"
                                    >
                                        Désactiver
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </div>

                {/* Recovery Codes */}
                {recoveryCodes.length > 0 && (
                    <div className="p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                        <div className="flex items-start gap-3 mb-4">
                            <Key className="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5" />
                            <div>
                                <h3 className="text-sm font-medium text-amber-900 dark:text-amber-100 mb-1">
                                    Codes de récupération
                                </h3>
                                <p className="text-xs text-amber-800 dark:text-amber-200">
                                    Conservez ces codes en lieu sûr. Ils vous permettront de vous connecter si vous perdez accès à votre appareil.
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2 mb-4">
                            {recoveryCodes.map((code, index) => (
                                <code
                                    key={index}
                                    className="block p-2 bg-white dark:bg-gray-900 rounded text-xs font-mono text-center border border-amber-200 dark:border-amber-800"
                                >
                                    {code}
                                </code>
                            ))}
                        </div>
                        <Button onClick={downloadRecoveryCodes} variant="outline" size="sm" className="w-full">
                            Télécharger les codes
                        </Button>
                    </div>
                )}
            </div>

            {/* Password Confirmation Modal */}
            {confirmingPassword && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Confirmer votre mot de passe
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Pour votre sécurité, veuillez confirmer votre mot de passe avant de continuer.
                        </p>
                        <form onSubmit={confirmPassword} className="space-y-4">
                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Mot de passe
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    value={passwordForm.data.password}
                                    onChange={(e) => passwordForm.setData('password', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                                    autoFocus
                                />
                                {passwordForm.errors.password && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{passwordForm.errors.password}</p>
                                )}
                            </div>
                            <div className="flex gap-3">
                                <Button type="submit" disabled={passwordForm.processing} className="flex-1">
                                    Confirmer
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setConfirmingPassword(false);
                                        setEnabling(false);
                                        passwordForm.reset();
                                    }}
                                    className="flex-1"
                                >
                                    Annuler
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Disable Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showDisableDialog}
                onOpenChange={(open) => {
                    setShowDisableDialog(open);
                    if (!open) setDisableTarget(null);
                }}
                onConfirm={handleDisableConfirm}
                title="Désactiver l'authentification"
                description={
                    disableTarget === 'totp'
                        ? "Êtes-vous sûr de vouloir désactiver l'authentification par application ? Cela réduira la sécurité de votre compte."
                        : "Êtes-vous sûr de vouloir désactiver l'authentification par email ?"
                }
                confirmText="Désactiver"
            />
        </section>
    );
}
