import { usePage } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { PageProps } from '@/Types';
import { ReactNode } from 'react';

interface PageHeaderProps {
    title?: string;
    description?: string;
    actions?: ReactNode;
}

export default function PageHeader({ title, description, actions }: PageHeaderProps) {
    const { auth } = usePage<PageProps>().props;

    const defaultTitle = `Bienvenue, ${auth.user?.first_name || 'Utilisateur'} ! 👋`;
    const defaultDescription = "Voici un aperçu de vos activités récentes et des statistiques de votre espace de travail.";

    return (
        <div className="mb-6">
            <div className="sm:flex sm:items-center sm:justify-between">
                <div className="flex items-center gap-4">
                    <Avatar className="h-16 w-16 border-2 border-primary">
                        <AvatarImage
                            src={auth.user?.avatar ? `/storage/${auth.user.avatar}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user?.first_name || 'User')}`}
                            alt={auth.user?.first_name || 'User'}
                        />
                        <AvatarFallback className="bg-primary text-white text-lg font-semibold">
                            {auth.user?.first_name?.[0] || 'U'}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            {title || defaultTitle}
                        </h1>
                    </div>
                </div>
                {actions && (
                    <div className="mt-4 sm:mt-0 flex flex-wrap items-center gap-2 sm:gap-3">
                        {actions}
                    </div>
                )}
            </div>
            {description && (
                <div className="mt-4">
                    <p className="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        {description}
                    </p>
                </div>
            )}
        </div>
    );
}
