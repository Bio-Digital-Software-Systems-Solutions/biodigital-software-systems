import { Button } from '@/Components/ui/button';
import { useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { FormEventHandler, useState } from 'react';
import { toast } from 'sonner';
import { Shield, Smartphone, Key, CheckCircle2, XCircle } from 'lucide-react';
import { apiLogger } from '@/utils/logger';

interface Props {
    className?: string;
}

export default function TwoFactorAuthenticationForm({ className = '' }: Props) {
    const user = usePage().props.auth.user as any;
    const [enabling, setEnabling] = useState(false);
    const [confirmingPassword, setConfirmingPassword] = useState(false);
    const [qrCode, setQrCode] = useState<string | null>(null);
    const [setupKey, setSetupKey] = useState<string | null>(null);
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);

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
        if (!confirm('Êtes-vous sûr de vouloir désactiver l\'authentification à deux facteurs ?')) {
            return;
        }

        try {
            await axios.delete(route('two-factor.disable'));
            toast.success('Authentification à deux facteurs désactivée');
            setQrCode(null);
            setSetupKey(null);
            setRecoveryCodes([]);
            window.location.reload();
        } catch (error) {
            toast.error('Erreur lors de la désactivation');
            apiLogger.error('Error disabling 2FA', error);
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

    return (
        <section className={className}>
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

            <div className="mt-6 space-y-6">
                {/* Status */}
                <div className="flex items-center gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    {user.two_factor_confirmed_at ? (
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

                {!user.two_factor_confirmed_at ? (
                    /* Enable 2FA */
                    <div className="space-y-4">
                        <div className="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <h3 className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                                Comment ça marche ?
                            </h3>
                            <ol className="list-decimal list-inside text-sm text-blue-800 dark:text-blue-200 space-y-1">
                                <li>Activez l'authentification à deux facteurs</li>
                                <li>Scannez le QR code avec Microsoft Authenticator ou Google Authenticator</li>
                                <li>Conservez vos codes de récupération en lieu sûr</li>
                                <li>À chaque connexion, entrez le code de votre application</li>
                            </ol>
                        </div>

                        <Button
                            onClick={handleEnable}
                            disabled={passwordForm.processing}
                            className="w-full sm:w-auto"
                        >
                            <Smartphone className="h-4 w-4 mr-2" />
                            Activer l'authentification à deux facteurs
                        </Button>
                    </div>
                ) : (
                    /* Manage 2FA */
                    <div className="space-y-4">
                        {qrCode && (
                            <div className="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">
                                    Scannez ce QR code avec votre application d'authentification :
                                </h3>
                                <div
                                    className="flex justify-center mb-4"
                                    dangerouslySetInnerHTML={{ __html: qrCode }}
                                />
                                {setupKey && (
                                    <div className="mt-4">
                                        <p className="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                            Ou entrez cette clé manuellement :
                                        </p>
                                        <code className="block p-3 bg-gray-100 dark:bg-gray-800 rounded text-sm font-mono text-center break-all">
                                            {setupKey}
                                        </code>
                                    </div>
                                )}
                            </div>
                        )}

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
                                <Button
                                    onClick={downloadRecoveryCodes}
                                    variant="outline"
                                    size="sm"
                                    className="w-full"
                                >
                                    Télécharger les codes
                                </Button>
                            </div>
                        )}

                        <div className="flex flex-wrap gap-3">
                            {!qrCode && (
                                <Button
                                    onClick={showRecoveryCodes}
                                    variant="outline"
                                >
                                    <Key className="h-4 w-4 mr-2" />
                                    Afficher les codes de récupération
                                </Button>
                            )}

                            <Button
                                onClick={regenerateRecoveryCodes}
                                variant="outline"
                            >
                                Régénérer les codes de récupération
                            </Button>

                            <Button
                                onClick={disableTwoFactorAuthentication}
                                variant="destructive"
                            >
                                Désactiver l'authentification à deux facteurs
                            </Button>
                        </div>
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
                                <Button
                                    type="submit"
                                    disabled={passwordForm.processing}
                                    className="flex-1"
                                >
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
        </section>
    );
}
