import React from 'react';
import { Link } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import {
    ClockIcon,
    UserIcon,
    MapPinIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import type { Shift, ShiftStatus, ShiftType } from '@/Types/scheduling';

interface Props {
    shift: Shift;
    departmentUuid: string;
    scheduleUuid: string;
    compact?: boolean;
    showActions?: boolean;
}

const TYPE_COLORS: Record<ShiftType, string> = {
    morning: 'bg-yellow-100 border-yellow-300 dark:bg-yellow-900/30 dark:border-yellow-700',
    afternoon: 'bg-orange-100 border-orange-300 dark:bg-orange-900/30 dark:border-orange-700',
    evening: 'bg-purple-100 border-purple-300 dark:bg-purple-900/30 dark:border-purple-700',
    night: 'bg-indigo-100 border-indigo-300 dark:bg-indigo-900/30 dark:border-indigo-700',
    full_day: 'bg-blue-100 border-blue-300 dark:bg-blue-900/30 dark:border-blue-700',
    split: 'bg-pink-100 border-pink-300 dark:bg-pink-900/30 dark:border-pink-700',
    on_call: 'bg-gray-100 border-gray-300 dark:bg-gray-800 dark:border-gray-600',
    custom: 'bg-green-100 border-green-300 dark:bg-green-900/30 dark:border-green-700',
};

const TYPE_LABELS: Record<ShiftType, string> = {
    morning: 'Matin',
    afternoon: 'Après-midi',
    evening: 'Soir',
    night: 'Nuit',
    full_day: 'Journée',
    split: 'Coupure',
    on_call: 'Astreinte',
    custom: 'Personnalisé',
};

const STATUS_BADGES: Record<ShiftStatus, { color: string; label: string }> = {
    draft: { color: 'bg-gray-400', label: 'Brouillon' },
    published: { color: 'bg-blue-500', label: 'Publié' },
    confirmed: { color: 'bg-green-500', label: 'Confirmé' },
    in_progress: { color: 'bg-yellow-500', label: 'En cours' },
    completed: { color: 'bg-green-600', label: 'Terminé' },
    cancelled: { color: 'bg-red-500', label: 'Annulé' },
    no_show: { color: 'bg-red-600', label: 'Absent' },
};

export default function ShiftCard({
    shift,
    departmentUuid,
    scheduleUuid,
    compact = false,
    showActions = false,
}: Props) {
    // Use custom color if set, otherwise fall back to type-based Tailwind classes
    const hasCustomColor = !!shift.color;
    const typeColorClasses = TYPE_COLORS[shift.type] || TYPE_COLORS.custom;
    const statusInfo = STATUS_BADGES[shift.status] || STATUS_BADGES.draft;

    const formatTime = (time: string) => {
        return time.slice(0, 5);
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    // Check if shift has assigned users (either via users array or single user)
    const hasAssignedUsers = (shift.users && shift.users.length > 0) || shift.user;
    const assignedUsers = shift.users && shift.users.length > 0 ? shift.users : (shift.user ? [shift.user] : []);

    const getUserDisplayName = (user: any) => {
        if (user.first_name && user.last_name) {
            return `${user.first_name} ${user.last_name}`;
        }
        return user.name || 'Utilisateur';
    };

    if (compact) {
        return (
            <Link
                href={`/departments/${departmentUuid}/schedule/${scheduleUuid}/shifts/${shift.uuid}`}
                className={`block p-2 rounded-lg border-l-4 transition-all hover:shadow-md ${hasCustomColor ? '' : typeColorClasses}`}
                style={hasCustomColor && shift.color ? { borderLeftColor: shift.color, backgroundColor: `${shift.color}20` } : {}}
            >
                <div className="flex items-center justify-between mb-1">
                    <span className="text-xs font-semibold text-gray-700 dark:text-gray-200">
                        {formatTime(shift.start_time)} - {formatTime(shift.end_time)}
                    </span>
                    {shift.is_overtime && (
                        <Badge variant="secondary" className="text-[10px] px-1 py-0">OT</Badge>
                    )}
                </div>

                {shift.title && (
                    <p className="text-xs font-medium text-gray-800 dark:text-gray-100 truncate">
                        {shift.title}
                    </p>
                )}

                {hasAssignedUsers ? (
                    <div className="flex items-center gap-1 mt-1">
                        {assignedUsers.length === 1 ? (
                            <>
                                <Avatar className="h-5 w-5">
                                    <AvatarFallback className="text-[10px] bg-gray-200 dark:bg-gray-600">
                                        {getInitials(getUserDisplayName(assignedUsers[0]))}
                                    </AvatarFallback>
                                </Avatar>
                                <span className="text-xs text-gray-600 dark:text-gray-300 truncate">
                                    {getUserDisplayName(assignedUsers[0])}
                                </span>
                            </>
                        ) : (
                            <>
                                <div className="flex -space-x-1">
                                    {assignedUsers.slice(0, 3).map((user, idx) => (
                                        <Avatar key={user.id || idx} className="h-5 w-5 border border-white dark:border-gray-800">
                                            <AvatarFallback className="text-[10px] bg-gray-200 dark:bg-gray-600">
                                                {getInitials(getUserDisplayName(user))}
                                            </AvatarFallback>
                                        </Avatar>
                                    ))}
                                </div>
                                <span className="text-xs text-gray-600 dark:text-gray-300">
                                    {assignedUsers.length} personnes
                                </span>
                            </>
                        )}
                    </div>
                ) : (
                    <div className="flex items-center gap-1 mt-1 text-orange-600 dark:text-orange-400">
                        <ExclamationTriangleIcon className="h-4 w-4" />
                        <span className="text-xs font-medium">Non assigné</span>
                    </div>
                )}
            </Link>
        );
    }

    return (
        <Link
            href={`/departments/${departmentUuid}/schedule/${scheduleUuid}/shifts/${shift.uuid}`}
            className={`block p-4 rounded-lg border transition-all hover:shadow-lg ${hasCustomColor ? '' : typeColorClasses}`}
            style={hasCustomColor && shift.color ? { borderColor: shift.color, backgroundColor: `${shift.color}15` } : {}}
        >
            <div className="flex items-start justify-between mb-3">
                <div>
                    <div className="flex items-center gap-2">
                        <Badge variant="secondary" className="text-xs">
                            {TYPE_LABELS[shift.type]}
                        </Badge>
                        <Badge className={`${statusInfo.color} text-xs`}>
                            {statusInfo.label}
                        </Badge>
                    </div>
                    {shift.title && (
                        <h4 className="font-semibold text-gray-900 dark:text-white mt-1">
                            {shift.title}
                        </h4>
                    )}
                </div>

                {shift.is_overtime && (
                    <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200">
                        Heures sup.
                    </Badge>
                )}
            </div>

            <div className="space-y-2 text-sm">
                <div className="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                    <ClockIcon className="h-4 w-4" />
                    <span>
                        {formatTime(shift.start_time)} - {formatTime(shift.end_time)}
                        <span className="text-gray-400 ml-2">
                            ({shift.duration_hours}h)
                        </span>
                    </span>
                </div>

                {shift.location && (
                    <div className="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                        <MapPinIcon className="h-4 w-4" />
                        <span>{shift.location}</span>
                    </div>
                )}

                <div className="flex items-center gap-2 mt-3">
                    <UserIcon className="h-4 w-4 text-gray-400" />
                    {hasAssignedUsers ? (
                        <div className="flex items-center gap-2">
                            {assignedUsers.length === 1 ? (
                                <>
                                    <Avatar className="h-6 w-6">
                                        <AvatarFallback className="text-xs">
                                            {getInitials(getUserDisplayName(assignedUsers[0]))}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span className="font-medium text-gray-900 dark:text-white">
                                        {getUserDisplayName(assignedUsers[0])}
                                    </span>
                                </>
                            ) : (
                                <>
                                    <div className="flex -space-x-2">
                                        {assignedUsers.slice(0, 4).map((user, idx) => (
                                            <Avatar key={user.id || idx} className="h-6 w-6 border-2 border-white dark:border-gray-800">
                                                <AvatarFallback className="text-xs">
                                                    {getInitials(getUserDisplayName(user))}
                                                </AvatarFallback>
                                            </Avatar>
                                        ))}
                                    </div>
                                    <span className="font-medium text-gray-900 dark:text-white">
                                        {assignedUsers.length} personnes assignées
                                    </span>
                                </>
                            )}
                            {shift.checked_in_at && (
                                <CheckCircleIcon className="h-4 w-4 text-green-500" />
                            )}
                        </div>
                    ) : (
                        <span className="text-orange-600 dark:text-orange-400 font-medium flex items-center gap-1">
                            <ExclamationTriangleIcon className="h-4 w-4" />
                            Non assigné
                        </span>
                    )}
                </div>
            </div>

            {shift.description && (
                <p className="mt-3 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                    {shift.description}
                </p>
            )}

            {shift.tasks && shift.tasks.length > 0 && (
                <div className="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <span className="text-xs text-gray-500">
                        {shift.tasks.filter(t => t.status === 'completed').length} / {shift.tasks.length} tâches complétées
                    </span>
                </div>
            )}
        </Link>
    );
}
