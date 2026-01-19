import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import DatePicker, { registerLocale } from 'react-datepicker';
import { fr } from 'date-fns/locale';
import { format, parseISO } from 'date-fns';
import 'react-datepicker/dist/react-datepicker.css';
import {
    CalendarDaysIcon,
    BookOpenIcon,
    PencilSquareIcon,
    ChatBubbleLeftRightIcon,
    UserIcon,
    BuildingOfficeIcon,
    DocumentIcon,
    FolderIcon,
    ClipboardDocumentListIcon,
    BriefcaseIcon,
    AcademicCapIcon,
    QuestionMarkCircleIcon,
    UsersIcon,
    VideoCameraIcon,
    CubeIcon,
    ListBulletIcon,
    DocumentTextIcon,
    ExclamationCircleIcon,
    CalendarIcon,
    ArrowPathIcon,
    InformationCircleIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/react/24/outline';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';

interface Activity {
    id: number;
    log_name: string | null;
    description: string;
    event: string;
    subject_type: string | null;
    subject_name: string | null;
    subject_id: number | null;
    causer: {
        id: number;
        name: string;
        avatar: string | null;
    } | null;
    properties: Record<string, unknown>;
    icon: string;
    url: string | null;
    created_at: string;
    time_ago: string;
}

interface PaginatedActivities {
    data: Activity[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Stats {
    today: number;
    this_week: number;
    this_month: number;
    total: number;
}

interface Filters {
    type: string | null;
    causer_id: string | null;
    from: string | null;
    to: string | null;
    search: string | null;
}

interface ActivityIndexProps extends PageProps {
    activities: PaginatedActivities;
    logNames: string[];
    stats: Stats;
    filters: Filters;
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
    CalendarDaysIcon,
    PencilSquareIcon,
    BookOpenIcon,
    ChatBubbleLeftRightIcon,
    UserIcon,
    BuildingOfficeIcon,
    DocumentIcon,
    FolderIcon,
    ClipboardDocumentListIcon,
    BriefcaseIcon,
    AcademicCapIcon,
    QuestionMarkCircleIcon,
    UsersIcon,
    VideoCameraIcon,
    CubeIcon,
    ListBulletIcon,
    DocumentTextIcon,
    ExclamationCircleIcon,
    CalendarIcon,
    ArrowPathIcon,
    InformationCircleIcon,
};

const eventColors: Record<string, string> = {
    created: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    updated: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    deleted: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    default: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
};

registerLocale('fr', fr);

export default function Index({ activities, logNames, stats, filters }: ActivityIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedType, setSelectedType] = useState(filters.type || '');
    const [fromDate, setFromDate] = useState<Date | null>(filters.from ? parseISO(filters.from) : null);
    const [toDate, setToDate] = useState<Date | null>(filters.to ? parseISO(filters.to) : null);

    const handleFilter = () => {
        router.get(
            route('activity.index'),
            {
                search: search || undefined,
                type: selectedType || undefined,
                from: fromDate ? format(fromDate, 'yyyy-MM-dd') : undefined,
                to: toDate ? format(toDate, 'yyyy-MM-dd') : undefined,
            },
            { preserveState: true }
        );
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedType('');
        setFromDate(null);
        setToDate(null);
        router.get(route('activity.index'));
    };

    const getIcon = (iconName: string): React.ComponentType<{ className?: string }> => {
        return iconMap[iconName] || InformationCircleIcon;
    };

    const formatSubjectType = (type: string | null): string => {
        if (!type) return '';
        // Convert camelCase/PascalCase to space-separated words
        return type.replace(/([A-Z])/g, ' $1').trim();
    };

    return (
        <DashboardLayout>
            <Head title="Activité - AIG-App" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Journal d'activité
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Historique de toutes les actions effectuées dans l'application
                        </p>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Aujourd'hui
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {stats.today}
                            </div>
                            <p className="text-xs text-gray-500 mt-1">actions</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Cette semaine
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                                {stats.this_week}
                            </div>
                            <p className="text-xs text-gray-500 mt-1">actions</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Ce mois
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {stats.this_month}
                            </div>
                            <p className="text-xs text-gray-500 mt-1">actions</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Total
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                {stats.total}
                            </div>
                            <p className="text-xs text-gray-500 mt-1">actions enregistrées</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <FunnelIcon className="h-5 w-5" />
                            Filtres
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-5">
                            <div className="relative">
                                <MagnifyingGlassIcon className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    placeholder="Rechercher..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                    onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                />
                            </div>

                            <Select value={selectedType} onValueChange={setSelectedType}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Type d'activité" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Tous les types</SelectItem>
                                    {logNames.map((name) => (
                                        <SelectItem key={name} value={name}>
                                            {name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <DatePicker
                                selected={fromDate}
                                onChange={(date) => setFromDate(date)}
                                locale="fr"
                                dateFormat="dd/MM/yyyy"
                                placeholderText="Date de début"
                                className="w-full px-3 py-2 rounded-md border text-sm bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white"
                                isClearable
                            />

                            <DatePicker
                                selected={toDate}
                                onChange={(date) => setToDate(date)}
                                locale="fr"
                                dateFormat="dd/MM/yyyy"
                                placeholderText="Date de fin"
                                className="w-full px-3 py-2 rounded-md border text-sm bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white"
                                isClearable
                                minDate={fromDate || undefined}
                            />

                            <div className="flex gap-2">
                                <Button onClick={handleFilter} className="flex-1">
                                    Filtrer
                                </Button>
                                <Button variant="outline" onClick={clearFilters}>
                                    Effacer
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Activity List */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Activités ({activities.total})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {activities.data.length === 0 ? (
                            <div className="p-8 text-center text-gray-500 dark:text-gray-400">
                                Aucune activité trouvée
                            </div>
                        ) : (
                            <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                {activities.data.map((activity) => {
                                    const IconComponent = getIcon(activity.icon);
                                    const ActivityContent = (
                                        <div className="flex items-start gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <div className="flex-shrink-0">
                                                <div className="p-2 bg-gray-100 dark:bg-gray-700 rounded-full">
                                                    <IconComponent className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                                </div>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <span className="font-medium text-gray-900 dark:text-white">
                                                        {activity.description}
                                                    </span>
                                                    <Badge
                                                        className={eventColors[activity.event] || eventColors.default}
                                                    >
                                                        {activity.event}
                                                    </Badge>
                                                    {activity.subject_type && (
                                                        <Badge variant="outline">
                                                            {formatSubjectType(activity.subject_type)}
                                                        </Badge>
                                                    )}
                                                </div>
                                                {activity.subject_name && (
                                                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        {activity.subject_name}
                                                    </p>
                                                )}
                                                <div className="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                    {activity.causer && (
                                                        <span className="flex items-center gap-1">
                                                            <UserIcon className="h-3 w-3" />
                                                            {activity.causer.name}
                                                        </span>
                                                    )}
                                                    <span>{activity.time_ago}</span>
                                                    {activity.log_name && (
                                                        <Badge variant="secondary" className="text-xs">
                                                            {activity.log_name}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );

                                    return activity.url ? (
                                        <Link
                                            key={activity.id}
                                            href={activity.url}
                                            className="block"
                                        >
                                            {ActivityContent}
                                        </Link>
                                    ) : (
                                        <div key={activity.id}>{ActivityContent}</div>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {activities.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Affichage de {activities.from} à {activities.to} sur {activities.total} résultats
                        </p>
                        <div className="flex items-center gap-2">
                            {activities.links.map((link, index) => {
                                if (link.label.includes('Previous')) {
                                    return (
                                        <Button
                                            key={index}
                                            variant="outline"
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => link.url && router.get(link.url)}
                                        >
                                            <ChevronLeftIcon className="h-4 w-4" />
                                        </Button>
                                    );
                                }
                                if (link.label.includes('Next')) {
                                    return (
                                        <Button
                                            key={index}
                                            variant="outline"
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => link.url && router.get(link.url)}
                                        >
                                            <ChevronRightIcon className="h-4 w-4" />
                                        </Button>
                                    );
                                }
                                return (
                                    <Button
                                        key={index}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                    >
                                        {link.label}
                                    </Button>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
