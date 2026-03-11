import React, { useState, useEffect, PropsWithChildren, ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageProps } from '@/Types';
import { toast } from 'sonner';
import {
    HomeIcon,
    CalendarDaysIcon,
    BookOpenIcon,
    PencilSquareIcon,
    ChatBubbleLeftRightIcon,
    UsersIcon,
    ClipboardDocumentListIcon,
    BuildingLibraryIcon,
    CogIcon,
    Bars3Icon,
    XMarkIcon,
    BellIcon,
    UserCircleIcon,
    MagnifyingGlassIcon,
    FolderIcon,
    AcademicCapIcon,
    UserGroupIcon,
    ChartBarIcon,
    BeakerIcon,
    EnvelopeIcon,
    ShieldCheckIcon,
    HeartIcon,
    ClockIcon,
    ArrowPathIcon,
    DocumentTextIcon,
    ClipboardDocumentCheckIcon,
    BanknotesIcon,
} from '@heroicons/react/24/outline';
import Dropdown from '@/Components/Dropdown';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import SkipLink from '@/Components/SkipLink';
import { Toaster } from '@/Components/ui/toaster';
import { useNotifications } from '@/Hooks/useNotifications';
import { Role, isAdmin, hasRole } from '@/Enums/Role';
import UnauthorizedModal from '@/Components/UnauthorizedModal';
import PageHeader from '@/Components/PageHeader';

interface NavItem {
    name: string;
    href: string;
    icon: React.ComponentType<{ className?: string }>;
    permission?: string;
    excludeRoles?: Role[];
    requireRole?: Role;
    current?: boolean;
}

interface DashboardLayoutProps extends PropsWithChildren {
    title?: string;
    description?: string;
    actions?: ReactNode;
    header?: ReactNode;
}

export default function DashboardLayout({ children, title, description, actions, header }: DashboardLayoutProps) {
    const { auth, flash } = usePage<PageProps>().props;
    const { t } = useTranslation();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { notificationCount } = useNotifications();

    // Display flash messages as toasts
    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.message) {
            toast.info(flash.message);
        }
    }, [flash]);

    const navigation: NavItem[] = [
        { name: t('dashboard'), href: '/dashboard', icon: HomeIcon, current: true },
        { name: t('events'), href: '/events', icon: CalendarDaysIcon, permission: 'view events' },
        { name: t('appointments'), href: '/appointments', icon: CalendarDaysIcon, permission: 'view appointments' },
        { name: 'Soins Pastoraux', href: '/pastoral-care/appointments', icon: HeartIcon, permission: 'view pastoral care' },
        //{ name: 'Prendre RDV Pastoral', href: '/pastoral-care/book', icon: CalendarDaysIcon },
        { name: 'Mes Disponibilités', href: '/pastoral-availability', icon: ClockIcon, permission: 'manage pastor availability' },
        { name: t('articles'), href: '/articles', icon: PencilSquareIcon, permission: 'view articles' },
        { name: t('books'), href: '/books', icon: BookOpenIcon, permission: 'view books' },
        { name: t('books.rent'), href: '/my-rentals', icon: BuildingLibraryIcon, permission: 'view books' },
        { name: 'Formations', href: '/trainings', icon: AcademicCapIcon, permission: 'view trainings' },
        { name: 'Gestion Classes', href: '/training-classes', icon: ClipboardDocumentListIcon, permission: 'manage trainings' },
        { name: 'Espace enseignant', href: '/teacher/dashboard', icon: ChartBarIcon, permission: 'access teacher dashboard' },
        { name: 'Espace étudiant', href: '/student/dashboard', icon: UserCircleIcon, permission: 'access student dashboard' },
        { name: t('chat'), href: '/chat', icon: ChatBubbleLeftRightIcon, permission: 'use chat' },
        { name: 'Projets', href: '/projects', icon: FolderIcon, permission: 'view projects' },
        { name: 'Départements', href: '/departments', icon: UserGroupIcon, permission: 'view departments' },
        { name: 'Rapports', href: '/reports', icon: DocumentTextIcon, permission: 'view reports' },
        { name: 'Groupes', href: '/groups', icon: UsersIcon, permission: 'view groups' },
        { name: 'Programmes', href: '/programs', icon: ClipboardDocumentListIcon, permission: 'view programs' },
        { name: 'Stocks', href: '/stocks', icon: BeakerIcon, permission: 'view stocks' },
        { name: 'Messages', href: '/messages', icon: EnvelopeIcon, permission: 'view messages' },
        { name: 'Gestion Utilisateurs', href: '/user-management', icon: ShieldCheckIcon, requireRole: Role.SUPER_ADMIN },
        { name: 'Workflows', href: '/workflows', icon: ArrowPathIcon, permission: 'view workflows' },
        { name: 'Formulaires', href: '/forms', icon: DocumentTextIcon, permission: 'view forms' },
        { name: 'Besoins', href: '/needs', icon: ClipboardDocumentCheckIcon, permission: 'view needs' },
        { name: 'Comptabilité', href: '/accounting', icon: BanknotesIcon, permission: 'view accounting' },
    ];

    const hasPermission = (permission?: string) => {
        if (!permission) return true;
        const userPermissions = auth.user?.permissions?.map((p: any) => typeof p === 'string' ? p : p.name) || [];
        return userPermissions.includes(permission) || isAdmin(auth.user?.roles);
    };

    const isRoleExcluded = (excludeRoles?: Role[]) => {
        if (!excludeRoles || excludeRoles.length === 0) return false;

        // Check if user has ONLY excluded roles
        const userRoles = auth.user?.roles || [];
        if (userRoles.length === 0) return false;

        // If user has only one role and it's in the excluded list
        if (userRoles.length === 1 && excludeRoles.some(role =>
            userRoles.some(ur => ur.name === role)
        )) {
            return true;
        }

        return false;
    };

    const hasRequiredRole = (requireRole?: Role) => {
        if (!requireRole) return true;
        return hasRole(auth.user?.roles, requireRole);
    };

    const filteredNavigation = navigation.filter(item =>
        hasPermission(item.permission) && !isRoleExcluded(item.excludeRoles) && hasRequiredRole(item.requireRole)
    );

    const Sidebar = ({ mobile = false }: { mobile?: boolean }) => (
        <div className={`flex flex-col h-full ${mobile ? 'w-full' : 'w-64'}`}>
            <div className="flex items-center h-16 px-4 bg-primary">
                <Link href="/" className="flex items-center">
                    <img src="/Logo.svg" alt="AIG-App Logo" className="h-10 w-auto" />
                </Link>
            </div>
            <nav
                className="flex-1 px-4 py-4 bg-white dark:bg-gray-800 space-y-2"
                aria-label="Main navigation"
            >
                {filteredNavigation.map((item) => (
                    <Link
                        key={item.name}
                        href={item.href}
                        className={`flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${
                            item.current
                                ? 'bg-blue-100 text-primary dark:bg-blue-900 dark:text-blue-200'
                                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                        }`}
                        aria-current={item.current ? 'page' : undefined}
                    >
                        <item.icon className="mr-3 h-5 w-5" aria-hidden="true" />
                        {item.name}
                    </Link>
                ))}
            </nav>
            <div className="p-4 bg-gray-50 dark:bg-gray-900">
                <div className="flex items-center">
                    <div className="flex-shrink-0">
                        <div className="h-8 w-8 bg-primary rounded-full flex items-center justify-center">
                            <span className="text-sm font-medium text-white">
                                {auth.user?.first_name?.[0]}{auth.user?.last_name?.[0]}
                            </span>
                        </div>
                    </div>
                    <div className="ml-3">
                        <p className="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {auth.user?.first_name} {auth.user?.last_name}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {typeof auth.user?.roles?.[0] === 'string' ? auth.user?.roles?.[0] : auth.user?.roles?.[0]?.name || 'Utilisateur'}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex">
            {/* Skip to main content link */}
            <SkipLink target="#main-content" />

            {/* Mobile sidebar */}
            <div
                className={`fixed inset-0 z-40 md:hidden ${sidebarOpen ? 'block' : 'hidden'}`}
                role="dialog"
                aria-modal="true"
                aria-label="Mobile navigation menu"
            >
                <div
                    className="fixed inset-0 bg-gray-600 bg-opacity-75"
                    onClick={() => setSidebarOpen(false)}
                    aria-hidden="true"
                />
                <div className="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-gray-800">
                    <div className="absolute top-0 right-0 -mr-12 pt-2">
                        <button
                            className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                            onClick={() => setSidebarOpen(false)}
                            aria-label="Close navigation menu"
                        >
                            <XMarkIcon className="h-6 w-6 text-white" aria-hidden="true" />
                        </button>
                    </div>
                    <Sidebar mobile />
                </div>
            </div>

            {/* Desktop sidebar */}
            <div className="hidden md:flex md:flex-shrink-0">
                <div className="flex flex-col w-64 h-screen">
                    <Sidebar />
                </div>
            </div>

            {/* Main content */}
            <div className="flex-1 flex flex-col">
                {/* Top navigation */}
                <header className="sticky top-0 z-10 bg-white dark:bg-gray-800 shadow">
                    <div className="flex items-center justify-between h-16 px-4">
                        <button
                            className="px-4 text-gray-500 focus:outline-none focus:text-gray-700 md:hidden"
                            onClick={() => setSidebarOpen(true)}
                            aria-label="Open navigation menu"
                            aria-expanded={sidebarOpen}
                        >
                            <Bars3Icon className="h-6 w-6" aria-hidden="true" />
                        </button>

                        {/* Search bar */}
                        <div className="flex-1 max-w-lg mx-4">
                            <div className="relative" role="search">
                                <label htmlFor="global-search" className="sr-only">
                                    Rechercher dans l'application
                                </label>
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
                                </div>
                                <input
                                    id="global-search"
                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-primary focus:border-primary"
                                    placeholder="Rechercher..."
                                    type="search"
                                    aria-label="Recherche globale"
                                />
                            </div>
                        </div>

                        {/* Right side icons */}
                        <div className="flex items-center space-x-4">
                            {/* Theme Switcher */}
                            <ThemeSwitcher />

                            {/* Language Switcher */}
                            <LanguageSwitcher />

                            {/* Notifications */}
                            <Link
                                href={route('messages.index')}
                                className="relative p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:text-gray-500 rounded-md"
                                aria-label={`Notifications${notificationCount > 0 ? ` (${notificationCount} non lues)` : ''}`}
                            >
                                <BellIcon className="h-6 w-6" aria-hidden="true" />
                                {notificationCount > 0 && (
                                    <span
                                        className="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full min-w-[20px]"
                                        aria-label={`${notificationCount} notifications non lues`}
                                    >
                                        {notificationCount > 99 ? '99+' : notificationCount}
                                    </span>
                                )}
                            </Link>

                            {/* Profile dropdown */}
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <span className="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                        >
                                            <UserCircleIcon className="h-6 w-6 mr-2" />
                                            {auth.user?.first_name}
                                            <svg
                                                className="ml-2 -mr-0.5 h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fillRule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clipRule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </Dropdown.Trigger>

                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>
                                        {t('profile')}
                                    </Dropdown.Link>
                                    <Dropdown.Link href={route('settings.index')} className="flex items-center">
                                        <CogIcon className="h-4 w-4 mr-2" />
                                        Paramètres
                                    </Dropdown.Link>
                                    <Dropdown.Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                    >
                                        {t('logout')}
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                {/* Page content */}
                <main
                    id="main-content"
                    className="flex-1 overflow-x-hidden overflow-y-auto"
                    role="main"
                    aria-label="Contenu principal"
                >
                    <div className="p-4 max-w-full">
                        {header || (
                            <PageHeader
                                title={title}
                                description={description}
                                actions={actions}
                            />
                        )}
                        {children}
                    </div>
                </main>
            </div>

            {/* Toast notifications */}
            <Toaster position="top-right" richColors closeButton />

            {/* Unauthorized Modal */}
            <UnauthorizedModal />
        </div>
    );
}