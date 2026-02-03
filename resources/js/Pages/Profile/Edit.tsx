import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
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
                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className=""
                        />
                    </div>

                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <UpdatePasswordForm className="" />
                    </div>

                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <TwoFactorAuthenticationForm className="" />
                    </div>

                    <div className="bg-white dark:bg-gray-800 p-4 sm:p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
                        <DeleteUserForm className="" />
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
