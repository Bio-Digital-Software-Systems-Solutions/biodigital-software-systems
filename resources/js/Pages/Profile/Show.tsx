import React from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    MapPinIcon,
    CalendarIcon,
    ArrowLeftIcon,
    BuildingOfficeIcon,
    UserGroupIcon,
    ClockIcon,
    GlobeAltIcon,
    ComputerDesktopIcon,
    CheckCircleIcon,
} from '@heroicons/react/24/outline';

interface Department {
    id: number;
    name: string;
    code: string;
}

interface Group {
    id: number;
    name: string;
    code: string;
}

interface User {
    id: number;
    name: string;
    first_name: string | null;
    last_name: string | null;
    email: string;
    email_verified_at: string | null;
    phone: string | null;
    address: string | null;
    avatar: string | null;
    birth_date: string | null;
    status: string;
    created_at: string;
    last_login_at: string | null;
    last_login_ip: string | null;
    last_login_user_agent: string | null;
    departments: Department[];
    groups: Group[];
    roles: string[];
}

interface Props {
    user: User;
}

export default function ShowProfile({ user }: Props) {
    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const formatDateTime = (date: string) => {
        return new Date(date).toLocaleString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const parseBrowser = (userAgent: string | null) => {
        if (!userAgent) return 'Inconnu';

        if (userAgent.includes('Chrome') && !userAgent.includes('Edg')) return 'Chrome';
        if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) return 'Safari';
        if (userAgent.includes('Firefox')) return 'Firefox';
        if (userAgent.includes('Edg')) return 'Edge';
        if (userAgent.includes('Opera') || userAgent.includes('OPR')) return 'Opera';

        return 'Autre';
    };

    const getInitials = (name: string) => {
        if (!name) return '?';
        const parts = name.split(' ');
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
        }
        return name.charAt(0).toUpperCase();
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'active':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'inactive':
                return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            case 'suspended':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'active':
                return 'Actif';
            case 'inactive':
                return 'Inactif';
            case 'suspended':
                return 'Suspendu';
            default:
                return status;
        }
    };

    return (
        <DashboardLayout>
            <Head title={user.name} />

            <div className="p-6">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={window.history.length > 1 ? '#' : '/dashboard'} onClick={(e) => {
                            if (window.history.length > 1) {
                                e.preventDefault();
                                window.history.back();
                            }
                        }}>
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour
                        </Link>
                    </Button>
                </div>

                {/* Profile Card */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <div className="flex items-start gap-6">
                            {/* Avatar */}
                            <div className="flex-shrink-0">
                                {user.avatar ? (
                                    <img
                                        src={`/storage/${user.avatar}`}
                                        alt={user.name}
                                        className="h-24 w-24 rounded-full object-cover"
                                    />
                                ) : (
                                    <div className="h-24 w-24 rounded-full bg-primary flex items-center justify-center text-white text-2xl font-bold">
                                        {getInitials(user.name)}
                                    </div>
                                )}
                            </div>

                            {/* User Info */}
                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                        {user.name}
                                    </h1>
                                    <Badge className={getStatusColor(user.status)}>
                                        {getStatusLabel(user.status)}
                                    </Badge>
                                    {user.roles.map((role) => (
                                        <Badge key={role} className="bg-purple-500">
                                            {role}
                                        </Badge>
                                    ))}
                                </div>

                                <div className="space-y-2 text-gray-600 dark:text-gray-300">
                                    {user.email && (
                                        <div className="flex items-center gap-2">
                                            <EnvelopeIcon className="h-5 w-5 text-gray-400" />
                                            <a href={`mailto:${user.email}`} className="hover:text-primary dark:hover:text-blue-400">
                                                {user.email}
                                            </a>
                                            {user.email_verified_at && (
                                                <CheckCircleIcon className="h-4 w-4 text-green-500" title="Email vérifié" />
                                            )}
                                        </div>
                                    )}
                                    {user.birth_date && (
                                        <div className="flex items-center gap-2">
                                            <CalendarIcon className="h-5 w-5 text-gray-400" />
                                            <span>{formatDate(user.birth_date)}</span>
                                        </div>
                                    )}
                                    {user.phone && (
                                        <div className="flex items-center gap-2">
                                            <PhoneIcon className="h-5 w-5 text-gray-400" />
                                            <a href={`tel:${user.phone}`} className="hover:text-primary dark:hover:text-blue-400">
                                                {user.phone}
                                            </a>
                                        </div>
                                    )}
                                    {user.address && (
                                        <div className="flex items-center gap-2">
                                            <MapPinIcon className="h-5 w-5 text-gray-400" />
                                            <span>{user.address}</span>
                                        </div>
                                    )}
                                    <div className="flex items-center gap-2">
                                        <CalendarIcon className="h-5 w-5 text-gray-400" />
                                        <span>Membre depuis {formatDate(user.created_at)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {/* Status and History */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <ClockIcon className="h-5 w-5 text-gray-500" />
                                <CardTitle>Statut et historique</CardTitle>
                            </div>
                            <CardDescription>
                                Informations de connexion
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div>
                                    <div className="text-sm text-gray-500 dark:text-gray-400 mb-2">Statut actuel</div>
                                    <Badge className={getStatusColor(user.status)}>
                                        {getStatusLabel(user.status)}
                                    </Badge>
                                </div>
                                {user.last_login_at && (
                                    <>
                                        <div>
                                            <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                                                <ClockIcon className="h-4 w-4" />
                                                Dernière connexion
                                            </div>
                                            <div className="text-gray-900 dark:text-white">
                                                {formatDateTime(user.last_login_at)}
                                            </div>
                                        </div>
                                        {user.last_login_ip && (
                                            <div>
                                                <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                                                    <GlobeAltIcon className="h-4 w-4" />
                                                    Adresse IP
                                                </div>
                                                <div className="text-gray-900 dark:text-white font-mono text-sm">
                                                    {user.last_login_ip}
                                                </div>
                                            </div>
                                        )}
                                        {user.last_login_user_agent && (
                                            <div>
                                                <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                                                    <ComputerDesktopIcon className="h-4 w-4" />
                                                    Navigateur
                                                </div>
                                                <div className="text-gray-900 dark:text-white">
                                                    {parseBrowser(user.last_login_user_agent)}
                                                </div>
                                            </div>
                                        )}
                                    </>
                                )}
                                {!user.last_login_at && (
                                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                        <ClockIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                        <p>Aucune connexion enregistrée</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Departments */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <BuildingOfficeIcon className="h-5 w-5 text-gray-500" />
                                <CardTitle>Départements</CardTitle>
                            </div>
                            <CardDescription>
                                {user.departments.length} département(s)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {user.departments.length > 0 ? (
                                <div className="space-y-2">
                                    {user.departments.map((dept) => (
                                        <Link
                                            key={dept.id}
                                            href={`/departments/${dept.code}`}
                                            className="block p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            <div className="font-medium text-gray-900 dark:text-white">
                                                {dept.name}
                                            </div>
                                            <div className="text-sm text-gray-500 dark:text-gray-400">
                                                Code: {dept.code}
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <BuildingOfficeIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                    <p>Aucun département</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Groups */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <UserGroupIcon className="h-5 w-5 text-gray-500" />
                                <CardTitle>Groupes</CardTitle>
                            </div>
                            <CardDescription>
                                {user.groups.length} groupe(s)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {user.groups.length > 0 ? (
                                <div className="space-y-2">
                                    {user.groups.map((group) => (
                                        <Link
                                            key={group.id}
                                            href={`/groups/${group.code}`}
                                            className="block p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            <div className="font-medium text-gray-900 dark:text-white">
                                                {group.name}
                                            </div>
                                            <div className="text-sm text-gray-500 dark:text-gray-400">
                                                Code: {group.code}
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <UserGroupIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                    <p>Aucun groupe</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}
