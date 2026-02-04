import { useState } from 'react';
import { ShieldCheckIcon, GlobeAltIcon, LockClosedIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';
import { Switch } from '@/Components/ui/switch';
import { Label } from '@/Components/ui/label';

interface PrivacySettings {
    email: boolean;
    phone_number: boolean;
    birth_date: boolean;
    address: boolean;
    bio: boolean;
    position: boolean;
    languages: boolean;
    interests: boolean;
    skills: boolean;
}

interface Props {
    privacySettings: PrivacySettings;
    defaultPrivacySettings: PrivacySettings;
    className?: string;
    hideHeader?: boolean;
}

const fieldLabels: Record<keyof PrivacySettings, string> = {
    email: 'Adresse email',
    phone_number: 'Numéro de téléphone',
    birth_date: 'Date de naissance',
    address: 'Adresse',
    bio: 'Biographie',
    position: 'Poste / Fonction',
    languages: 'Langues parlées',
    interests: 'Centres d\'intérêt',
    skills: 'Compétences',
};

const fieldDescriptions: Record<keyof PrivacySettings, string> = {
    email: 'Les autres membres pourront voir votre adresse email',
    phone_number: 'Les autres membres pourront voir votre numéro de téléphone',
    birth_date: 'Les autres membres pourront voir votre date de naissance',
    address: 'Les autres membres pourront voir votre adresse',
    bio: 'Les autres membres pourront voir votre biographie',
    position: 'Les autres membres pourront voir votre poste ou fonction',
    languages: 'Les autres membres pourront voir vos langues parlées',
    interests: 'Les autres membres pourront voir vos centres d\'intérêt',
    skills: 'Les autres membres pourront voir vos compétences',
};

export default function ProfilePrivacyForm({
    privacySettings: initialSettings,
    defaultPrivacySettings,
    className = '',
    hideHeader = false,
}: Props) {
    const [settings, setSettings] = useState<PrivacySettings>(initialSettings);
    const [processing, setProcessing] = useState(false);

    const toggleSetting = (field: keyof PrivacySettings) => {
        setSettings((prev) => ({
            ...prev,
            [field]: !prev[field],
        }));
    };

    const setAllPublic = () => {
        const allPublic = Object.keys(settings).reduce((acc, key) => {
            acc[key as keyof PrivacySettings] = true;
            return acc;
        }, {} as PrivacySettings);
        setSettings(allPublic);
    };

    const setAllPrivate = () => {
        const allPrivate = Object.keys(settings).reduce((acc, key) => {
            acc[key as keyof PrivacySettings] = false;
            return acc;
        }, {} as PrivacySettings);
        setSettings(allPrivate);
    };

    const resetToDefault = () => {
        setSettings({ ...defaultPrivacySettings });
    };

    const saveSettings = async () => {
        setProcessing(true);
        try {
            await axios.put('/api/profile/privacy', {
                privacy_settings: settings,
            });
            toast.success('Paramètres de confidentialité mis à jour avec succès');
        } catch (error: any) {
            toast.error('Erreur lors de la mise à jour', {
                description: error.response?.data?.message || 'Une erreur est survenue',
            });
        } finally {
            setProcessing(false);
        }
    };

    const publicCount = Object.values(settings).filter(Boolean).length;
    const totalCount = Object.keys(settings).length;

    return (
        <section className={className}>
            {!hideHeader && (
                <header>
                    <h2 className="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <ShieldCheckIcon className="w-5 h-5" />
                        Confidentialité du profil
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Choisissez les informations qui seront visibles sur votre profil public.
                    </p>
                </header>
            )}

            <div className={`${hideHeader ? '' : 'mt-6'} space-y-6`}>
                {/* Quick Actions */}
                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={setAllPublic}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 hover:bg-green-100 dark:hover:bg-green-900/50 rounded-md transition-colors"
                    >
                        <GlobeAltIcon className="w-4 h-4" />
                        Tout public
                    </button>
                    <button
                        type="button"
                        onClick={setAllPrivate}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-md transition-colors"
                    >
                        <LockClosedIcon className="w-4 h-4" />
                        Tout privé
                    </button>
                    <button
                        type="button"
                        onClick={resetToDefault}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md transition-colors"
                    >
                        Par défaut
                    </button>
                </div>

                {/* Privacy Status Summary */}
                <div className="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            {publicCount === totalCount ? (
                                <GlobeAltIcon className="w-8 h-8 text-green-500" />
                            ) : publicCount === 0 ? (
                                <LockClosedIcon className="w-8 h-8 text-red-500" />
                            ) : (
                                <ShieldCheckIcon className="w-8 h-8 text-amber-500" />
                            )}
                            <div>
                                <p className="font-medium text-gray-900 dark:text-white">
                                    {publicCount === totalCount
                                        ? 'Profil entièrement public'
                                        : publicCount === 0
                                          ? 'Profil entièrement privé'
                                          : 'Profil partiellement public'}
                                </p>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {publicCount} sur {totalCount} informations visibles
                                </p>
                            </div>
                        </div>
                        <div className="text-right">
                            <div className="flex items-center gap-1">
                                {Array.from({ length: totalCount }).map((_, i) => (
                                    <div
                                        key={i}
                                        className={`w-2 h-6 rounded-sm ${
                                            i < publicCount
                                                ? 'bg-green-500'
                                                : 'bg-gray-300 dark:bg-gray-600'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Individual Settings */}
                <div className="space-y-4">
                    {(Object.keys(settings) as Array<keyof PrivacySettings>).map((field) => (
                        <div
                            key={field}
                            className="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                        >
                            <div className="flex-1 mr-4">
                                <Label
                                    htmlFor={`privacy-${field}`}
                                    className="text-base font-medium text-gray-900 dark:text-white cursor-pointer"
                                >
                                    {fieldLabels[field]}
                                </Label>
                                <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                    {fieldDescriptions[field]}
                                </p>
                            </div>
                            <div className="flex items-center gap-3">
                                <span
                                    className={`text-xs font-medium px-2 py-1 rounded ${
                                        settings[field]
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    }`}
                                >
                                    {settings[field] ? 'Public' : 'Privé'}
                                </span>
                                <Switch
                                    id={`privacy-${field}`}
                                    checked={settings[field]}
                                    onCheckedChange={() => toggleSetting(field)}
                                />
                            </div>
                        </div>
                    ))}
                </div>

                {/* Note */}
                <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p className="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Note :</strong> Votre nom, prénom et avatar sont toujours visibles sur
                        votre profil public. Ces paramètres de confidentialité s'appliquent uniquement
                        aux informations complémentaires.
                    </p>
                </div>

                {/* Save Button */}
                <div className="flex justify-end pt-4">
                    <PrimaryButton onClick={saveSettings} disabled={processing}>
                        {processing ? 'Enregistrement...' : 'Enregistrer les paramètres'}
                    </PrimaryButton>
                </div>
            </div>
        </section>
    );
}
