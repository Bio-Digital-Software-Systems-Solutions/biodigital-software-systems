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

    const defaultTitle = `Bienvenue, ${auth.user?.first_name || 'Utilisateur'} !`;

    return (
        <div className="mb-4 sm:mb-6">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div className="flex items-center gap-3 sm:gap-4 min-w-0">
                    <Avatar className="h-12 w-12 sm:h-16 sm:w-16 border-2 border-primary flex-shrink-0">
                        <AvatarImage
                            src={auth.user?.avatar ? `/storage/${auth.user.avatar}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user?.first_name || 'User')}`}
                            alt={auth.user?.first_name || 'User'}
                        />
                        <AvatarFallback className="bg-primary text-white text-base sm:text-lg font-semibold">
                            {auth.user?.first_name?.[0] || 'U'}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white truncate">
                            {title || defaultTitle}
                        </h1>
                    </div>
                </div>
                {actions && (
                    <div className="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto">
                        {actions}
                    </div>
                )}
            </div>
            {description && (
                <div className="mt-3 sm:mt-4">
                    <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        {description}
                    </p>
                </div>
            )}
        </div>
    );
}
