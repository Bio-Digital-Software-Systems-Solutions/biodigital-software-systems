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

                    {/* Languages */}
                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <ProfileLanguagesForm
                            availableLanguages={availableLanguages}
                            userLanguages={userLanguages}
                            className=""
                        />
                    </div>

                    {/* Interests */}
                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <ProfileInterestsForm
                            availableInterests={availableInterests}
                            userInterests={userInterests}
                            className=""
                        />
                    </div>

                    {/* Skills */}
                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <ProfileSkillsForm
                            availableSkills={availableSkills}
                            userSkills={userSkills}
                            className=""
                        />
                    </div>

                    {/* Privacy Settings */}
                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <ProfilePrivacyForm
                            privacySettings={privacySettings}
                            defaultPrivacySettings={defaultPrivacySettings}
                            className=""
                        />
                    </div>

                    {/* Password */}
                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <UpdatePasswordForm className="" />
                    </div>

                    {/* 2FA */}
                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <TwoFactorAuthenticationForm className="" />
                    </div>

                    {/* Delete Account */}
                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <DeleteUserForm className="" />
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
