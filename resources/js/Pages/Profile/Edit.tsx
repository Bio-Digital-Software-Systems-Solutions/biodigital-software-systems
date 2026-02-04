import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm';
import ProfileLanguagesForm from './Partials/ProfileLanguagesForm';
import ProfileInterestsForm from './Partials/ProfileInterestsForm';
import ProfileSkillsForm from './Partials/ProfileSkillsForm';
import ProfilePrivacyForm from './Partials/ProfilePrivacyForm';
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from '@/Components/ui/accordion';
import {
    GlobeAltIcon,
    HeartIcon,
    WrenchScrewdriverIcon,
    ShieldCheckIcon,
    KeyIcon,
    DevicePhoneMobileIcon,
    TrashIcon,
} from '@heroicons/react/24/outline';

interface SpokenLanguage {
    id: number;
    uuid: string;
    name: string;
    code: string;
    native_name: string | null;
}

interface Interest {
    id: number;
    uuid: string;
    name: string;
    icon: string | null;
}

interface ProfileSkill {
    id: number;
    uuid: string;
    name: string;
    category: 'soft' | 'hard' | 'technical';
}

interface UserLanguage {
    id: number;
    level: 'beginner' | 'intermediate' | 'advanced' | 'native';
}

interface UserSkill {
    id: number;
    level: 'beginner' | 'intermediate' | 'advanced' | 'expert' | null;
}

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

interface EditProps {
    mustVerifyEmail: boolean;
    status?: string;
    availableLanguages: SpokenLanguage[];
    availableInterests: Interest[];
    availableSkills: ProfileSkill[];
    userLanguages: UserLanguage[];
    userInterests: number[];
    userSkills: UserSkill[];
    privacySettings: PrivacySettings;
    defaultPrivacySettings: PrivacySettings;
    [key: string]: unknown;
}

export default function Edit({
    mustVerifyEmail,
    status,
    availableLanguages = [],
    availableInterests = [],
    availableSkills = [],
    userLanguages = [],
    userInterests = [],
    userSkills = [],
    privacySettings,
    defaultPrivacySettings,
}: PageProps<EditProps>) {
    return (
        <DashboardLayout>
            <Head title="Profile" />

            <div className="p-3 sm:p-4">
                {/* Page Header */}
                <div className="mb-4 sm:mb-6">
                    <h1 className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                        Paramètres du profil
                    </h1>
                    <p className="mt-1 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                        Gérez vos informations personnelles et les paramètres de sécurité de votre compte.
                    </p>
                </div>

                <div className="space-y-4 sm:space-y-6">
                    {/* Basic Profile Information */}
                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className=""
                        />
                    </div>

                    {/* Accordion Sections */}
                    <Accordion>
                        {/* Languages */}
                        <AccordionItem value="languages" className="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                            <AccordionTrigger className="px-4 sm:px-6 py-4">
                                <div className="flex items-center gap-3">
                                    <GlobeAltIcon className="h-5 w-5 text-blue-500" />
                                    <div className="text-left">
                                        <span className="font-semibold">Langues parlées</span>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                            Indiquez les langues que vous parlez et votre niveau de maîtrise.
                                        </p>
                                    </div>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent className="px-4 sm:px-6 pb-4">
                                <ProfileLanguagesForm
                                    availableLanguages={availableLanguages}
                                    userLanguages={userLanguages}
                                    className=""
                                    hideHeader
                                />
                            </AccordionContent>
                        </AccordionItem>

                        {/* Interests */}
                        <AccordionItem value="interests" className="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                            <AccordionTrigger className="px-4 sm:px-6 py-4">
                                <div className="flex items-center gap-3">
                                    <HeartIcon className="h-5 w-5 text-pink-500" />
                                    <div className="text-left">
                                        <span className="font-semibold">Centres d'intérêt</span>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                            Sélectionnez vos centres d'intérêt pour aider les autres membres à mieux vous connaître.
                                        </p>
                                    </div>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent className="px-4 sm:px-6 pb-4">
                                <ProfileInterestsForm
                                    availableInterests={availableInterests}
                                    userInterests={userInterests}
                                    className=""
                                    hideHeader
                                />
                            </AccordionContent>
                        </AccordionItem>

                        {/* Skills */}
                        <AccordionItem value="skills" className="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                            <AccordionTrigger className="px-4 sm:px-6 py-4">
                                <div className="flex items-center gap-3">
                                    <WrenchScrewdriverIcon className="h-5 w-5 text-orange-500" />
                                    <div className="text-left">
                                        <span className="font-semibold">Compétences</span>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                            Ajoutez vos compétences professionnelles et personnelles.
                                        </p>
                                    </div>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent className="px-4 sm:px-6 pb-4">
                                <ProfileSkillsForm
                                    availableSkills={availableSkills}
                                    userSkills={userSkills}
                                    className=""
                                    hideHeader
                                />
                            </AccordionContent>
                        </AccordionItem>

                        {/* Privacy Settings */}
                        <AccordionItem value="privacy" className="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                            <AccordionTrigger className="px-4 sm:px-6 py-4">
                                <div className="flex items-center gap-3">
                                    <ShieldCheckIcon className="h-5 w-5 text-green-500" />
                                    <div className="text-left">
                                        <span className="font-semibold">Confidentialité du profil</span>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                            Choisissez les informations qui seront visibles sur votre profil public.
                                        </p>
                                    </div>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent className="px-4 sm:px-6 pb-4">
                                <ProfilePrivacyForm
                                    privacySettings={privacySettings}
                                    defaultPrivacySettings={defaultPrivacySettings}
                                    className=""
                                    hideHeader
                                />
                            </AccordionContent>
                        </AccordionItem>

                        {/* Password */}
                        <AccordionItem value="password" className="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                            <AccordionTrigger className="px-4 sm:px-6 py-4">
                                <div className="flex items-center gap-3">
                                    <KeyIcon className="h-5 w-5 text-yellow-500" />
                                    <div className="text-left">
                                        <span className="font-semibold">Mot de passe</span>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                            Modifiez votre mot de passe pour sécuriser votre compte.
                                        </p>
                                    </div>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent className="px-4 sm:px-6 pb-4">
                                <UpdatePasswordForm className="" hideHeader />
                            </AccordionContent>
                        </AccordionItem>

                        {/* 2FA */}
                        <AccordionItem value="2fa" className="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                            <AccordionTrigger className="px-4 sm:px-6 py-4">
                                <div className="flex items-center gap-3">
                                    <DevicePhoneMobileIcon className="h-5 w-5 text-indigo-500" />
                                    <div className="text-left">
                                        <span className="font-semibold">Authentification à deux facteurs</span>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                            Ajoutez une couche de sécurité supplémentaire à votre compte.
                                        </p>
                                    </div>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent className="px-4 sm:px-6 pb-4">
                                <TwoFactorAuthenticationForm className="" hideHeader />
                            </AccordionContent>
                        </AccordionItem>

                        {/* Delete Account */}
                        <AccordionItem value="delete" className="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                            <AccordionTrigger className="px-4 sm:px-6 py-4">
                                <div className="flex items-center gap-3">
                                    <TrashIcon className="h-5 w-5 text-red-500" />
                                    <div className="text-left">
                                        <span className="font-semibold text-red-600 dark:text-red-400">Supprimer le compte</span>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                            Supprimez définitivement votre compte et toutes vos données.
                                        </p>
                                    </div>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent className="px-4 sm:px-6 pb-4">
                                <DeleteUserForm className="" hideHeader />
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </div>
            </div>
        </DashboardLayout>
    );
}
