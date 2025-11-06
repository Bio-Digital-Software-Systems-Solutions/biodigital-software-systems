import React from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import {
    CalendarDaysIcon,
    BookOpenIcon,
    PencilSquareIcon,
    AcademicCapIcon,
    CheckCircleIcon,
    ClockIcon,
    MapPinIcon,
    UserIcon,
} from '@heroicons/react/24/outline';

interface Event {
    id: number;
    uuid: string;
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    location: string;
    is_participating: boolean;
}

interface Article {
    id: number;
    uuid: string;
    slug: string;
    title: string;
    excerpt: string;
    published_at: string;
    author: string;
    featured_image: string | null;
}

interface Training {
    id: number;
    uuid: string;
    title: string;
    description: string;
    topic: string;
    duration_hours: number;
    is_enrolled: boolean;
}

interface MyTraining {
    id: number;
    uuid: string;
    title: string;
    status: string;
    progress: number;
    topic: string;
}

interface MyEvent {
    id: number;
    uuid: string;
    title: string;
    start_date: string;
    location: string;
}

interface Stats {
    totalEvents: number;
    myEvents: number;
    totalArticles: number;
    totalTrainings: number;
    myTrainings: number;
}

interface UserDashboardProps extends PageProps {
    upcomingEvents: Event[];
    recentArticles: Article[];
    availableTrainings: Training[];
    myTrainings: MyTraining[];
    myEvents: MyEvent[];
    stats: Stats;
}

export default function UserDashboard() {
    const { auth, upcomingEvents, recentArticles, availableTrainings, myTrainings, myEvents, stats } = usePage<UserDashboardProps>().props;

    const handleToggleParticipation = (eventUuid: string) => {
        router.post(route('events.toggle-participation', eventUuid), {}, {
            preserveScroll: true,
        });
    };

    const handleEnrollTraining = (trainingUuid: string) => {
        router.post(route('trainings.enroll', trainingUuid), {}, {
            preserveScroll: true,
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved':
                return 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/30';
            case 'pending':
                return 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-900/30';
            case 'rejected':
                return 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/30';
            default:
                return 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'approved':
                return 'Approuvé';
            case 'pending':
                return 'En attente';
            case 'rejected':
                return 'Rejeté';
            default:
                return status;
        }
    };

    return (
        <DashboardLayout
            description="Voici un aperçu de vos activités et des contenus disponibles."
        >
            <Head title="Mon Tableau de Bord - AIG-App" />

            {/* Stats Grid */}
                <div className="grid gap-6 mb-6 md:grid-cols-2 xl:grid-cols-5">
                    <div className="p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Événements à venir
                                </p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {stats.totalEvents}
                                </p>
                            </div>
                            <div className="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                                <CalendarDaysIcon className="h-6 w-6 text-primary dark:text-blue-400" />
                            </div>
                        </div>
                    </div>

                    <div className="p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Mes événements
                                </p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {stats.myEvents}
                                </p>
                            </div>
                            <div className="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                                <CheckCircleIcon className="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>

                    <div className="p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Articles publiés
                                </p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {stats.totalArticles}
                                </p>
                            </div>
                            <div className="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                                <PencilSquareIcon className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                    </div>

                    <div className="p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Formations disponibles
                                </p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {stats.totalTrainings}
                                </p>
                            </div>
                            <div className="p-3 bg-orange-100 dark:bg-orange-900 rounded-full">
                                <AcademicCapIcon className="h-6 w-6 text-orange-600 dark:text-orange-400" />
                            </div>
                        </div>
                    </div>

                    <div className="p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Mes formations
                                </p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {stats.myTrainings}
                                </p>
                            </div>
                            <div className="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-full">
                                <BookOpenIcon className="h-6 w-6 text-primary dark:text-indigo-400" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* My Activities Section */}
                <div className="grid gap-6 mb-6 lg:grid-cols-2">
                    {/* My Trainings */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <AcademicCapIcon className="h-5 w-5 mr-2" />
                                    Mes formations
                                </h3>
                            </div>
                            <div className="space-y-3">
                                {myTrainings.length > 0 ? (
                                    myTrainings.map((training) => (
                                        <Link
                                            key={training.id}
                                            href={route('trainings.show', training.uuid)}
                                            className="block p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                                        >
                                            <div className="flex items-center justify-between mb-2">
                                                <h4 className="font-medium text-gray-900 dark:text-white">{training.title}</h4>
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(training.status)}`}>
                                                    {getStatusLabel(training.status)}
                                                </span>
                                            </div>
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">{training.topic}</p>
                                            <div className="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                <div
                                                    className="bg-primary dark:bg-primary h-2 rounded-full transition-all"
                                                    style={{ width: `${training.progress}%` }}
                                                />
                                            </div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{training.progress}% complété</p>
                                        </Link>
                                    ))
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                        Vous n'êtes inscrit à aucune formation pour le moment.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* My Events */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <CalendarDaysIcon className="h-5 w-5 mr-2" />
                                    Mes événements à venir
                                </h3>
                            </div>
                            <div className="space-y-3">
                                {myEvents.length > 0 ? (
                                    myEvents.map((event) => (
                                        <Link
                                            key={event.id}
                                            href={route('events.show', event.uuid)}
                                            className="block p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                                        >
                                            <h4 className="font-medium text-gray-900 dark:text-white mb-2">{event.title}</h4>
                                            <div className="flex items-center text-sm text-gray-600 dark:text-gray-400 space-x-4">
                                                <span className="flex items-center">
                                                    <ClockIcon className="h-4 w-4 mr-1" />
                                                    {new Date(event.start_date).toLocaleDateString('fr-FR')}
                                                </span>
                                                <span className="flex items-center">
                                                    <MapPinIcon className="h-4 w-4 mr-1" />
                                                    {event.location}
                                                </span>
                                            </div>
                                        </Link>
                                    ))
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                        Vous ne participez à aucun événement pour le moment.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Available Content Sections */}
                <div className="space-y-6">
                    {/* Upcoming Events */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <CalendarDaysIcon className="h-5 w-5 mr-2" />
                                    Événements à venir
                                </h3>
                                <Link href={route('events.index')} className="text-sm text-primary dark:text-blue-400 hover:underline">
                                    Voir tous
                                </Link>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {upcomingEvents.slice(0, 6).map((event) => (
                                    <div key={event.id} className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div className="flex items-start justify-between mb-2">
                                            <Link
                                                href={route('events.show', event.uuid)}
                                                className="font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400"
                                            >
                                                {event.title}
                                            </Link>
                                        </div>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{event.description}</p>
                                        <div className="flex items-center text-xs text-gray-500 dark:text-gray-400 mb-3 space-x-3">
                                            <span className="flex items-center">
                                                <ClockIcon className="h-4 w-4 mr-1" />
                                                {new Date(event.start_date).toLocaleDateString('fr-FR')}
                                            </span>
                                            <span className="flex items-center">
                                                <MapPinIcon className="h-4 w-4 mr-1" />
                                                {event.location}
                                            </span>
                                        </div>
                                        <button
                                            onClick={() => handleToggleParticipation(event.uuid)}
                                            className={`w-full px-3 py-2 text-sm font-medium rounded-lg transition ${
                                                event.is_participating
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/40'
                                                    : 'bg-primary text-white hover:bg-primary dark:bg-primary dark:hover:bg-primary'
                                            }`}
                                        >
                                            {event.is_participating ? 'Inscrit ✓' : 'Participer'}
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Recent Articles */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <PencilSquareIcon className="h-5 w-5 mr-2" />
                                    Articles récents
                                </h3>
                                <Link href={route('articles.index')} className="text-sm text-primary dark:text-blue-400 hover:underline">
                                    Voir tous
                                </Link>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {recentArticles.slice(0, 6).map((article) => (
                                    <Link
                                        key={article.id}
                                        href={route('articles.show', article.uuid)}
                                        className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                                    >
                                        {article.featured_image && (
                                            <img
                                                src={article.featured_image}
                                                alt={article.title}
                                                className="w-full h-32 object-cover rounded-lg mb-3"
                                            />
                                        )}
                                        <h4 className="font-medium text-gray-900 dark:text-white mb-2 line-clamp-2">{article.title}</h4>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">{article.excerpt}</p>
                                        <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                            <span className="flex items-center">
                                                <UserIcon className="h-4 w-4 mr-1" />
                                                {article.author}
                                            </span>
                                            <span>{new Date(article.published_at).toLocaleDateString('fr-FR')}</span>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Available Trainings */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <AcademicCapIcon className="h-5 w-5 mr-2" />
                                    Formations disponibles
                                </h3>
                                <Link href={route('trainings.index')} className="text-sm text-primary dark:text-blue-400 hover:underline">
                                    Voir toutes
                                </Link>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {availableTrainings.slice(0, 6).map((training) => (
                                    <div key={training.id} className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <Link
                                            href={route('trainings.show', training.uuid)}
                                            className="font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 block mb-2"
                                        >
                                            {training.title}
                                        </Link>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{training.description}</p>
                                        <div className="flex items-center justify-between mb-3">
                                            <span className="text-xs text-gray-500 dark:text-gray-400">{training.topic}</span>
                                            <span className="text-xs text-gray-500 dark:text-gray-400">{training.duration_hours}h</span>
                                        </div>
                                        <button
                                            onClick={() => handleEnrollTraining(training.uuid)}
                                            disabled={training.is_enrolled}
                                            className={`w-full px-3 py-2 text-sm font-medium rounded-lg transition ${
                                                training.is_enrolled
                                                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed dark:bg-gray-600 dark:text-gray-400'
                                                    : 'bg-primary text-white hover:bg-primary dark:bg-primary dark:hover:bg-primary'
                                            }`}
                                        >
                                            {training.is_enrolled ? 'Déjà inscrit' : "S'inscrire"}
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
        </DashboardLayout>
    );
}
