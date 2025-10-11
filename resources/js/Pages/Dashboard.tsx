import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import {
    CalendarDaysIcon,
    BookOpenIcon,
    PencilSquareIcon,
    ChatBubbleLeftRightIcon,
    UsersIcon,
    ClipboardDocumentListIcon,
    ChartBarIcon,
    ArrowUpIcon,
    ArrowDownIcon,
} from '@heroicons/react/24/outline';

interface DashboardStats {
    title: string;
    value: string;
    change: string;
    changeType: 'increase' | 'decrease' | 'stable';
    icon: React.ComponentType<{ className?: string }>;
    url: string;
}

interface StatData {
    value: number;
    change: {
        value: string;
        type: 'increase' | 'decrease' | 'stable';
    };
}

interface DashboardProps extends PageProps {
    stats: {
        upcomingEvents: StatData;
        publishedArticles: StatData;
        availableBooks: StatData;
        unreadMessages: StatData;
    };
    recentActivities: Array<{
        id: string;
        type: string;
        title: string;
        description: string;
        time: string;
        icon: string;
        url?: string;
    }>;
    performance: {
        participationRate: number;
        articlesViewedThisMonth: number;
        booksBorrowed: number;
    };
}

export default function Dashboard() {
    const { auth, stats: statsData, recentActivities: activitiesData, performance } = usePage<DashboardProps>().props;

    const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
        'CalendarDaysIcon': CalendarDaysIcon,
        'PencilSquareIcon': PencilSquareIcon,
        'BookOpenIcon': BookOpenIcon,
        'ChatBubbleLeftRightIcon': ChatBubbleLeftRightIcon,
    };

    const stats: DashboardStats[] = [
        {
            title: 'Événements à venir',
            value: String(statsData.upcomingEvents.value),
            change: statsData.upcomingEvents.change.value,
            changeType: statsData.upcomingEvents.change.type,
            icon: CalendarDaysIcon,
            url: route('events.index'),
        },
        {
            title: 'Articles publiés',
            value: String(statsData.publishedArticles.value),
            change: statsData.publishedArticles.change.value,
            changeType: statsData.publishedArticles.change.type,
            icon: PencilSquareIcon,
            url: route('articles.index'),
        },
        {
            title: 'Livres disponibles',
            value: String(statsData.availableBooks.value),
            change: statsData.availableBooks.change.value,
            changeType: statsData.availableBooks.change.type,
            icon: BookOpenIcon,
            url: route('books.index'),
        },
        {
            title: 'Messages non lus',
            value: String(statsData.unreadMessages.value),
            change: statsData.unreadMessages.change.value,
            changeType: statsData.unreadMessages.change.type,
            icon: ChatBubbleLeftRightIcon,
            url: route('chat.index'),
        },
    ];

    const recentActivities = activitiesData.map(activity => ({
        ...activity,
        icon: iconMap[activity.icon] || CalendarDaysIcon,
    }));

    return (
        <DashboardLayout>
            <Head title="Dashboard - AIG-App" />

            {/* Stats Grid */}
                <div className="grid gap-6 mb-6 md:grid-cols-2 xl:grid-cols-4">
                    {stats.map((stat) => (
                        <Link
                            key={stat.title}
                            href={stat.url}
                            className="p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-blue-300 dark:hover:border-primary transition duration-200 cursor-pointer"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        {stat.title}
                                    </p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                        {stat.value}
                                    </p>
                                </div>
                                <div className="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                                    <stat.icon className="h-6 w-6 text-primary dark:text-blue-400" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center">
                                {stat.changeType === 'increase' ? (
                                    <ArrowUpIcon className="h-4 w-4 text-green-500" />
                                ) : (
                                    <ArrowDownIcon className="h-4 w-4 text-red-500" />
                                )}
                                <span
                                    className={`ml-1 text-sm font-medium ${
                                        stat.changeType === 'increase'
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-red-600 dark:text-red-400'
                                    }`}
                                >
                                    {stat.change}
                                </span>
                                <span className="ml-1 text-sm text-gray-500 dark:text-gray-400">
                                    vs mois dernier
                                </span>
                            </div>
                        </Link>
                    ))}
                </div>

                {/* Quick Actions & Recent Activity */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Quick Actions */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Actions rapides
                            </h3>
                            <div className="grid gap-3 grid-cols-2">
                                <Link
                                    href={route('events.create')}
                                    className="flex items-center p-3 text-left bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition duration-200"
                                >
                                    <CalendarDaysIcon className="h-8 w-8 text-primary dark:text-blue-400 mr-3" />
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">Nouvel événement</p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Créer un événement</p>
                                    </div>
                                </Link>
                                <Link
                                    href={route('articles.create')}
                                    className="flex items-center p-3 text-left bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition duration-200"
                                >
                                    <PencilSquareIcon className="h-8 w-8 text-green-600 dark:text-green-400 mr-3" />
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">Nouvel article</p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Écrire un article</p>
                                    </div>
                                </Link>
                                <Link
                                    href={route('books.index')}
                                    className="flex items-center p-3 text-left bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition duration-200"
                                >
                                    <BookOpenIcon className="h-8 w-8 text-purple-600 dark:text-purple-400 mr-3" />
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">Emprunter livre</p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Bibliothèque</p>
                                    </div>
                                </Link>
                                <Link
                                    href={route('departments.index')}
                                    className="flex items-center p-3 text-left bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition duration-200"
                                >
                                    <UsersIcon className="h-8 w-8 text-orange-600 dark:text-orange-400 mr-3" />
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">Gérer équipe</p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Départements</p>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Recent Activity */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Activité récente
                            </h3>
                            <div className="space-y-4">
                                {recentActivities.map((activity) => {
                                    const ActivityContent = (
                                        <div className="flex items-start space-x-3">
                                            <div className="flex-shrink-0">
                                                <div className="p-2 bg-gray-100 dark:bg-gray-700 rounded-full">
                                                    <activity.icon className="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                                </div>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                    {activity.title}
                                                </p>
                                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                                    {activity.description}
                                                </p>
                                                <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                    {activity.time}
                                                </p>
                                            </div>
                                        </div>
                                    );

                                    return activity.url ? (
                                        <Link
                                            key={activity.id}
                                            href={activity.url}
                                            className="block hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg p-2 -m-2 transition duration-200"
                                        >
                                            {ActivityContent}
                                        </Link>
                                    ) : (
                                        <div key={activity.id}>
                                            {ActivityContent}
                                        </div>
                                    );
                                })}
                            </div>
                            <div className="mt-4">
                                <Link
                                    href={route('chat.index')}
                                    className="block w-full text-center text-sm text-primary dark:text-blue-400 hover:text-primary dark:hover:text-blue-300 font-medium"
                                >
                                    Voir toute l'activité
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Charts Section */}
                <div className="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <div className="p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                Aperçu des performances
                            </h3>
                            <ChartBarIcon className="h-6 w-6 text-gray-400" />
                        </div>
                        <div className="grid gap-6 md:grid-cols-3">
                            <div className="text-center">
                                <div className="text-3xl font-bold text-primary dark:text-blue-400">{performance.participationRate}%</div>
                                <div className="text-sm text-gray-500 dark:text-gray-400">Taux de participation aux événements</div>
                            </div>
                            <div className="text-center">
                                <div className="text-3xl font-bold text-green-600 dark:text-green-400">{performance.articlesViewedThisMonth}</div>
                                <div className="text-sm text-gray-500 dark:text-gray-400">Articles publiés</div>
                            </div>
                            <div className="text-center">
                                <div className="text-3xl font-bold text-purple-600 dark:text-purple-400">{performance.booksBorrowed}</div>
                                <div className="text-sm text-gray-500 dark:text-gray-400">Emprunts de livres</div>
                            </div>
                        </div>
                    </div>
                </div>
        </DashboardLayout>
    );
}
