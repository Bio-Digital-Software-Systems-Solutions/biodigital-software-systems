import { useState, useMemo } from 'react';
import { GlobeAltIcon, PlusIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { SearchableSelect } from '@/Components/ui/searchable-select';

interface SpokenLanguage {
    id: number;
    uuid: string;
    name: string;
    code: string;
    native_name: string | null;
}

interface UserLanguage {
    id: number;
    level: 'beginner' | 'intermediate' | 'advanced' | 'native';
}

interface Props {
    availableLanguages: SpokenLanguage[];
    userLanguages: UserLanguage[];
    className?: string;
}

const levelLabels: Record<string, string> = {
    beginner: 'Débutant',
    intermediate: 'Intermédiaire',
    advanced: 'Avancé',
    native: 'Natif',
};

const levelColors: Record<string, string> = {
    beginner: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    intermediate: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    advanced: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    native: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
};

export default function ProfileLanguagesForm({
    availableLanguages,
    userLanguages: initialUserLanguages,
    className = '',
}: Props) {
    const [userLanguages, setUserLanguages] = useState<UserLanguage[]>(initialUserLanguages);
    const [selectedLanguageId, setSelectedLanguageId] = useState<number | null>(null);
    const [selectedLevel, setSelectedLevel] = useState<string>('intermediate');
    const [processing, setProcessing] = useState(false);

    const getLanguageById = (id: number) => availableLanguages.find((l) => l.id === id);

    const availableToAdd = availableLanguages.filter(
        (lang) => !userLanguages.some((ul) => ul.id === lang.id)
    );

    // Convert available languages to SearchableSelect options
    const languageOptions = useMemo(() => {
        return availableToAdd.map((lang) => ({
            value: lang.id,
            label: `${getFlagEmoji(lang.code)} ${lang.name}${lang.native_name ? ` (${lang.native_name})` : ''}`,
        }));
    }, [availableToAdd]);

    const addLanguage = () => {
        if (!selectedLanguageId) return;

        const newLanguage: UserLanguage = {
            id: selectedLanguageId,
            level: selectedLevel as UserLanguage['level'],
        };

        setUserLanguages([...userLanguages, newLanguage]);
        setSelectedLanguageId(null);
        setSelectedLevel('intermediate');
    };

    const removeLanguage = (languageId: number) => {
        setUserLanguages(userLanguages.filter((l) => l.id !== languageId));
    };

    const updateLevel = (languageId: number, newLevel: string) => {
        setUserLanguages(
            userLanguages.map((l) =>
                l.id === languageId ? { ...l, level: newLevel as UserLanguage['level'] } : l
            )
        );
    };

    const saveLanguages = async () => {
        setProcessing(true);
        try {
            await axios.put('/api/profile/languages', {
                languages: userLanguages,
            });
            toast.success('Langues mises à jour avec succès');
        } catch (error: any) {
            toast.error('Erreur lors de la mise à jour', {
                description: error.response?.data?.message || 'Une erreur est survenue',
            });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <GlobeAltIcon className="w-5 h-5" />
                    Langues parlées
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Indiquez les langues que vous parlez et votre niveau de maîtrise.
                </p>
            </header>

            <div className="mt-6 space-y-4">
                {/* Current Languages */}
                {userLanguages.length > 0 && (
                    <div className="space-y-3">
                        {userLanguages.map((userLang) => {
                            const language = getLanguageById(userLang.id);
                            if (!language) return null;

                            return (
                                <div
                                    key={userLang.id}
                                    className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg"
                                >
                                    <div className="flex items-center gap-3">
                                        <span className="text-2xl">{getFlagEmoji(language.code)}</span>
                                        <div>
                                            <span className="font-medium text-gray-900 dark:text-white">
                                                {language.name}
                                            </span>
                                            {language.native_name && (
                                                <span className="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                    ({language.native_name})
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Select
                                            value={userLang.level}
                                            onValueChange={(value) => updateLevel(userLang.id, value)}
                                        >
                                            <SelectTrigger className="w-[140px]">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="beginner">Débutant</SelectItem>
                                                <SelectItem value="intermediate">Intermédiaire</SelectItem>
                                                <SelectItem value="advanced">Avancé</SelectItem>
                                                <SelectItem value="native">Natif</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <button
                                            type="button"
                                            onClick={() => removeLanguage(userLang.id)}
                                            title="Supprimer cette langue"
                                            aria-label="Supprimer cette langue"
                                            className="p-1 text-gray-400 hover:text-red-500 transition-colors"
                                        >
                                            <XMarkIcon className="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Add New Language */}
                {availableToAdd.length > 0 && (
                    <div className="flex flex-col sm:flex-row gap-3 p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                        <SearchableSelect
                            options={languageOptions}
                            value={selectedLanguageId}
                            onChange={(value) => setSelectedLanguageId(value as number | null)}
                            placeholder="Rechercher une langue..."
                            noOptionsMessage="Aucune langue trouvée"
                            className="flex-1"
                            isClearable={false}
                        />

                        <Select value={selectedLevel} onValueChange={setSelectedLevel}>
                            <SelectTrigger className="w-full sm:w-[140px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="beginner">Débutant</SelectItem>
                                <SelectItem value="intermediate">Intermédiaire</SelectItem>
                                <SelectItem value="advanced">Avancé</SelectItem>
                                <SelectItem value="native">Natif</SelectItem>
                            </SelectContent>
                        </Select>

                        <button
                            type="button"
                            onClick={addLanguage}
                            disabled={!selectedLanguageId}
                            className="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <PlusIcon className="w-4 h-4" />
                            Ajouter
                        </button>
                    </div>
                )}

                {/* Empty State */}
                {userLanguages.length === 0 && availableToAdd.length === 0 && (
                    <p className="text-center text-gray-500 dark:text-gray-400 py-4">
                        Aucune langue disponible
                    </p>
                )}

                {/* Save Button */}
                <div className="flex justify-end pt-4">
                    <PrimaryButton onClick={saveLanguages} disabled={processing}>
                        {processing ? 'Enregistrement...' : 'Enregistrer les langues'}
                    </PrimaryButton>
                </div>
            </div>
        </section>
    );
}

// Helper function to get flag emoji from language code
function getFlagEmoji(languageCode: string): string {
    const languageToCountry: Record<string, string> = {
        en: 'GB',
        fr: 'FR',
        de: 'DE',
        es: 'ES',
        it: 'IT',
        pt: 'PT',
        nl: 'NL',
        ru: 'RU',
        zh: 'CN',
        ja: 'JP',
        ko: 'KR',
        ar: 'SA',
        hi: 'IN',
        tr: 'TR',
        pl: 'PL',
        uk: 'UA',
        cs: 'CZ',
        sv: 'SE',
        da: 'DK',
        fi: 'FI',
        no: 'NO',
        el: 'GR',
        he: 'IL',
        th: 'TH',
        vi: 'VN',
        id: 'ID',
        ms: 'MY',
        ro: 'RO',
        hu: 'HU',
        bg: 'BG',
        hr: 'HR',
        sk: 'SK',
        sl: 'SI',
        et: 'EE',
        lv: 'LV',
        lt: 'LT',
    };

    const countryCode = languageToCountry[languageCode.toLowerCase()] || languageCode.toUpperCase();

    try {
        const codePoints = countryCode
            .toUpperCase()
            .split('')
            .map((char) => 127397 + char.charCodeAt(0));
        return String.fromCodePoint(...codePoints);
    } catch {
        return '🌐';
    }
}
