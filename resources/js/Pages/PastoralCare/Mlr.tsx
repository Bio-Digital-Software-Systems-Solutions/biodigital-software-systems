import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import {
    HoverCard,
    HoverCardContent,
    HoverCardTrigger,
} from '@/Components/ui/hover-card';
import {
    CalendarIcon,
    ClockIcon,
    UserGroupIcon,
    CheckCircleIcon,
    XCircleIcon,
    EyeIcon,
    ArrowsRightLeftIcon,
    ChartBarIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/react/24/outline';
import {
    Calendar,
    Clock,
    User,
    Mail,
    Phone,
    MapPin,
    Video,
    Users,
    TrendingUp,
    ArrowRightLeft,
    BarChart3,
    PieChart,
    Activity,
    Timer,
    RefreshCw,
    Heart,
} from 'lucide-react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';
import MlrStatisticsAnalytical, { MlrAnalyticsData } from '@/Components/PastoralCare/MlrStatisticsAnalytical';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface PastoralCareAppointment {
    id: number;
    uuid: string;
    user?: User;
    pastor: User;
    transferred_from?: User;
    transferred_to?: User;
    parent?: PastoralCareAppointment;
    appointment_date: string;
    appointment_time: string;
    duration_minutes: number;
    status: 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';
    location_type: 'in_person' | 'zoom' | 'hybrid';
    zoom_link?: string;
    client_name: string | null;
    client_email: string | null;
    client_phone?: string;
    notes?: string;
    theme?: string;
    theme_label?: string;
    transferred_at?: string;
    transfer_reason?: string;
    created_at: string;
}

interface PaginatedAppointments {
    data: PastoralCareAppointment[];
    links: any[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
}

interface Pastor {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface StatsByPastor {
    pastor_id: number;
    pastor_name: string;
    count: number;
    percentage: number;
}

interface StatsByTheme {
    theme: string;
    theme_label: string;
    count: number;
    percentage: number;
}

interface StatusDistribution {
    status: string;
    label: string;
    count: number;
    percentage: number;
}

interface FollowUpStats {
    total: number;
    follow_ups: number;
    initial: number;
    follow_up_rate: number;
    average_follow_ups_per_initial: number;
}

interface TransferStats {
    total: number;
    transferred: number;
    transfer_rate: number;
    by_destination: Array<{
        user_id: number;
        user_name: string;
        count: number;
    }>;
}

interface AvailabilityItem {
    id: number;
    type: string;
    day_of_week: number;
    day_label?: string;
    specific_date?: string;
    start_time: string;
    end_time: string;
    slot_duration: number;
    consultation_mode: string;
    location?: string;
    room?: string;
    meeting_link?: string;
    notes?: string;
    time_slots: (TimeSlot | string)[];
    slots_count: number;
}

interface TimeSlot {
    time: string;
    status: 'available' | 'occupied' | 'passed';
}

interface PastorAvailability {
    pastor_id: number;
    pastor_name: string;
    availabilities: AvailabilityItem[];
    total_slots_per_week: number;
}

interface TrendData {
    period: string;
    total: number;
    completed: number;
    cancelled: number;
    pending: number;
    confirmed: number;
}

interface Stats {
    period: {
        start: string;
        end: string;
        label: string;
    };
    overview: {
        total: number;
        pending: number;
        confirmed: number;
        completed: number;
        cancelled: number;
        this_week: number;
        next_week: number;
        completion_rate: number;
    };
    average_duration: {
        average: number;
        min: number;
        max: number;
        count: number;
        formatted: string;
    };
    by_pastor: StatsByPastor[];
    by_theme: StatsByTheme[];
    by_status: {
        total: number;
        distribution: StatusDistribution[];
    };
    follow_ups: FollowUpStats;
    transfers: TransferStats;
    trend: TrendData[];
    incoming: PastoralCareAppointment[];
    availabilities: PastorAvailability[];
    analytics?: MlrAnalyticsData;
}

interface Props {
    stats: Stats;
    appointments: PaginatedAppointments;
    pastors: Pastor[];
    themes: Record<string, string>;
    currentPeriod: string;
    can: {
        transfer: boolean;
        viewAll: boolean;
        viewStatistics: boolean;
    };
    auth: {
        user: User;
    };
}

export default function Mlr({ stats, appointments, pastors, themes, currentPeriod, can, auth }: Props) {
    const [period, setPeriod] = useState(currentPeriod);
    const [activeTab, setActiveTab] = useState('overview');
    const [statisticsView, setStatisticsView] = useState<'operational' | 'analytics'>('operational');
    const [transferDialogOpen, setTransferDialogOpen] = useState(false);
    const [selectedAppointment, setSelectedAppointment] = useState<PastoralCareAppointment | null>(null);
    const [transferToId, setTransferToId] = useState<string>('');
    const [transferReason, setTransferReason] = useState('');
    const [isTransferring, setIsTransferring] = useState(false);

    const handlePeriodChange = (newPeriod: string) => {
        setPeriod(newPeriod);
        router.get('/pastoral-care/mlr', { period: newPeriod }, { preserveState: true });
    };

    const openTransferDialog = (appointment: PastoralCareAppointment) => {
        setSelectedAppointment(appointment);
        setTransferToId('');
        setTransferReason('');
        setTransferDialogOpen(true);
    };

    const handleTransfer = () => {
        if (!selectedAppointment || !transferToId) {
            toast.error('Veuillez sélectionner un destinataire');
            return;
        }

        setIsTransferring(true);

        router.post(`/pastoral-care/appointments/${selectedAppointment.uuid}/transfer`, {
            transferred_to_id: parseInt(transferToId),
            transfer_reason: transferReason,
        }, {
            onSuccess: () => {
                toast.success('Rendez-vous transféré avec succès');
                setTransferDialogOpen(false);
                setSelectedAppointment(null);
            },
            onError: (errors) => {
                const errorMessage = Object.values(errors).flat().join(', ');
                toast.error(errorMessage || 'Erreur lors du transfert');
            },
            onFinish: () => {
                setIsTransferring(false);
            },
        });
    };

    const formatDate = (dateString: string) => {
        return format(new Date(dateString), 'EEEE d MMMM yyyy', { locale: fr });
    };

    const formatTime = (timeString: string) => {
        return format(new Date(timeString), 'HH:mm', { locale: fr });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">En attente</Badge>;
            case 'confirmed':
                return <Badge variant="default" className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Confirmé</Badge>;
            case 'completed':
                return <Badge variant="outline" className="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Terminé</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Annulé</Badge>;
            case 'no_show':
                return <Badge variant="destructive" className="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Absent</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const getLocationIcon = (locationType: string) => {
        switch (locationType) {
            case 'zoom':
                return <Video className="h-4 w-4 text-blue-600" />;
            case 'hybrid':
                return <div className="flex space-x-1">
                    <MapPin className="h-3 w-3 text-green-600" />
                    <Video className="h-3 w-3 text-blue-600" />
                </div>;
            default:
                return <MapPin className="h-4 w-4 text-green-600" />;
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'pending': return 'bg-yellow-500';
            case 'confirmed': return 'bg-blue-500';
            case 'completed': return 'bg-green-500';
            case 'cancelled': return 'bg-red-500';
            case 'no_show': return 'bg-orange-500';
            default: return 'bg-gray-500';
        }
    };

    const getDayLabel = (dayOfWeek: number) => {
        const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        return days[dayOfWeek] || 'Inconnu';
    };

    return (
        <DashboardLayout title="MLR - Tableau de bord">
            <Head title="MLR - Tableau de bord Pastoral" />

            <div className="py-6">
                <div className="mx-auto sm:px-6 lg:px-8">
                    {/* Header with Period Selector */}
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                                <Heart className="h-7 w-7 mr-3 text-red-500" />
                                Tableau de bord MLR
                            </h1>
                            <p className="text-gray-600 dark:text-gray-400 mt-1">
                                Vue globale des soins pastoraux - {stats.period.label}
                            </p>
                        </div>
                        <div className="flex items-center space-x-4">
                            <Select value={period} onValueChange={handlePeriodChange}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Période" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="week">Cette semaine</SelectItem>
                                    <SelectItem value="month">Ce mois</SelectItem>
                                    <SelectItem value="quarter">Ce trimestre</SelectItem>
                                    <SelectItem value="year">Cette année</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                variant="outline"
                                onClick={() => router.reload()}
                            >
                                <RefreshCw className="h-4 w-4 mr-2" />
                                Actualiser
                            </Button>
                        </div>
                    </div>

                    <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
                        <TabsList className="grid w-full grid-cols-5">
                            <TabsTrigger value="overview">Vue d'ensemble</TabsTrigger>
                            <TabsTrigger value="incoming">Entrants ({stats.incoming.length})</TabsTrigger>
                            <TabsTrigger value="statistics">Statistiques</TabsTrigger>
                            <TabsTrigger value="availabilities">Disponibilités</TabsTrigger>
                            <TabsTrigger value="all">Tous ({appointments.meta.total})</TabsTrigger>
                        </TabsList>

                        {/* Overview Tab */}
                        <TabsContent value="overview" className="space-y-6">
                            {/* Key Metrics */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Total RDV</CardTitle>
                                        <UserGroupIcon className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.overview.total}</div>
                                        <p className="text-xs text-muted-foreground">
                                            {stats.period.label}
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">En attente</CardTitle>
                                        <ClockIcon className="h-4 w-4 text-yellow-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-yellow-600">{stats.overview.pending}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Nécessitent une action
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Durée moyenne</CardTitle>
                                        <Timer className="h-4 w-4 text-blue-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-blue-600">{stats.average_duration.formatted}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Min: {stats.average_duration.min}min / Max: {stats.average_duration.max}min
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Taux de complétion</CardTitle>
                                        <TrendingUp className="h-4 w-4 text-green-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-green-600">{stats.overview.completion_rate}%</div>
                                        <p className="text-xs text-muted-foreground">
                                            {stats.overview.completed} terminés sur {stats.overview.completed + stats.overview.cancelled}
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Secondary Metrics */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Follow-ups</CardTitle>
                                        <RefreshCw className="h-4 w-4 text-purple-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-purple-600">{stats.follow_ups.follow_ups}</div>
                                        <p className="text-xs text-muted-foreground">
                                            {stats.follow_ups.follow_up_rate}% des RDV sont des suivis
                                        </p>
                                        <div className="mt-2 text-xs">
                                            <span className="text-gray-500">Moyenne: {stats.follow_ups.average_follow_ups_per_initial} follow-ups/initial</span>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Transferts</CardTitle>
                                        <ArrowRightLeft className="h-4 w-4 text-orange-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-orange-600">{stats.transfers.transferred}</div>
                                        <p className="text-xs text-muted-foreground">
                                            {stats.transfers.transfer_rate}% des RDV ont été transférés
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Cette semaine / Prochaine</CardTitle>
                                        <CalendarIcon className="h-4 w-4 text-indigo-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex items-center space-x-4">
                                            <div>
                                                <div className="text-xl font-bold text-indigo-600">{stats.overview.this_week}</div>
                                                <p className="text-xs text-muted-foreground">Cette semaine</p>
                                            </div>
                                            <div className="text-gray-300 dark:text-gray-600">|</div>
                                            <div>
                                                <div className="text-xl font-bold text-indigo-400">{stats.overview.next_week}</div>
                                                <p className="text-xs text-muted-foreground">Semaine prochaine</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Status Distribution */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <PieChart className="h-5 w-5 mr-2" />
                                        Répartition par statut
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-4">
                                        {stats.by_status.distribution.map((item) => (
                                            <div key={item.status} className="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <div className={`w-3 h-3 rounded-full ${getStatusColor(item.status)}`}></div>
                                                <div>
                                                    <p className="font-medium">{item.label}</p>
                                                    <p className="text-sm text-gray-500">{item.count} ({item.percentage}%)</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    {/* Progress bar visualization */}
                                    <div className="mt-4 h-4 rounded-full overflow-hidden flex bg-gray-200 dark:bg-gray-700">
                                        {stats.by_status.distribution.map((item) => (
                                            <div
                                                key={item.status}
                                                className={`${getStatusColor(item.status)} transition-all`}
                                                style={{ width: `${item.percentage}%` }}
                                                title={`${item.label}: ${item.percentage}%`}
                                            ></div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Incoming Appointments Tab */}
                        <TabsContent value="incoming" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Rendez-vous entrants</CardTitle>
                                    <CardDescription>
                                        Nouveaux rendez-vous en attente de traitement
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {stats.incoming.length === 0 ? (
                                        <div className="text-center py-8">
                                            <CheckCircleIcon className="h-12 w-12 text-green-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Aucun rendez-vous entrant en attente
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {stats.incoming.map((appointment) => (
                                                <AppointmentCard
                                                    key={appointment.id}
                                                    appointment={appointment}
                                                    onTransfer={() => openTransferDialog(appointment)}
                                                    canTransfer={can.transfer}
                                                />
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Statistics Tab */}
                        <TabsContent value="statistics" className="space-y-6">
                            {/* View Toggle */}
                            <div className="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1 w-fit">
                                <button
                                    onClick={() => setStatisticsView('operational')}
                                    className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                                        statisticsView === 'operational'
                                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                    }`}
                                >
                                    Opérationnel
                                </button>
                                <button
                                    onClick={() => setStatisticsView('analytics')}
                                    className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                                        statisticsView === 'analytics'
                                            ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                    }`}
                                >
                                    Analytique
                                </button>
                            </div>

                            {/* Analytics View */}
                            {statisticsView === 'analytics' && stats.analytics && (
                                <MlrStatisticsAnalytical data={stats.analytics} />
                            )}

                            {/* Operational View */}
                            {statisticsView === 'operational' && (
                            <>
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Distribution by Pastor */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center">
                                            <BarChart3 className="h-5 w-5 mr-2" />
                                            Répartition par agent/pasteur
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {stats.by_pastor.length === 0 ? (
                                            <p className="text-gray-500 text-center py-4">Aucune donnée disponible</p>
                                        ) : (
                                            <div className="space-y-4">
                                                {stats.by_pastor.map((item) => (
                                                    <div key={item.pastor_id}>
                                                        <div className="flex justify-between mb-1">
                                                            <span className="font-medium">{item.pastor_name}</span>
                                                            <span className="text-gray-500">{item.count} ({item.percentage}%)</span>
                                                        </div>
                                                        <div className="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                            <div
                                                                className="h-full bg-blue-500 rounded-full transition-all"
                                                                style={{ width: `${item.percentage}%` }}
                                                            ></div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                {/* Distribution by Theme */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center">
                                            <PieChart className="h-5 w-5 mr-2" />
                                            Répartition par thème
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {stats.by_theme.length === 0 ? (
                                            <p className="text-gray-500 text-center py-4">Aucune donnée disponible</p>
                                        ) : (
                                            <div className="space-y-4">
                                                {stats.by_theme.map((item, index) => {
                                                    const colors = ['bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500', 'bg-amber-500', 'bg-emerald-500', 'bg-rose-500', 'bg-cyan-500'];
                                                    const color = colors[index % colors.length];
                                                    return (
                                                        <div key={item.theme}>
                                                            <div className="flex justify-between mb-1">
                                                                <span className="font-medium">{item.theme_label}</span>
                                                                <span className="text-gray-500">{item.count} ({item.percentage}%)</span>
                                                            </div>
                                                            <div className="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                                <div
                                                                    className={`h-full ${color} rounded-full transition-all`}
                                                                    style={{ width: `${item.percentage}%` }}
                                                                ></div>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                {/* Follow-up Statistics */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center">
                                            <RefreshCw className="h-5 w-5 mr-2" />
                                            Statistiques de suivi
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <p className="text-sm text-gray-500">Total RDV</p>
                                                <p className="text-2xl font-bold">{stats.follow_ups.total}</p>
                                            </div>
                                            <div className="p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                                                <p className="text-sm text-purple-600 dark:text-purple-400">Follow-ups</p>
                                                <p className="text-2xl font-bold text-purple-600">{stats.follow_ups.follow_ups}</p>
                                            </div>
                                            <div className="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                                                <p className="text-sm text-blue-600 dark:text-blue-400">RDV initiaux</p>
                                                <p className="text-2xl font-bold text-blue-600">{stats.follow_ups.initial}</p>
                                            </div>
                                            <div className="p-4 bg-green-50 dark:bg-green-900/30 rounded-lg">
                                                <p className="text-sm text-green-600 dark:text-green-400">Taux de suivi</p>
                                                <p className="text-2xl font-bold text-green-600">{stats.follow_ups.follow_up_rate}%</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Transfer Statistics */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center">
                                            <ArrowRightLeft className="h-5 w-5 mr-2" />
                                            Statistiques de transfert
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            <div className="flex justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <span>Total transférés</span>
                                                <span className="font-bold">{stats.transfers.transferred}</span>
                                            </div>
                                            <div className="flex justify-between p-3 bg-orange-50 dark:bg-orange-900/30 rounded-lg">
                                                <span>Taux de transfert</span>
                                                <span className="font-bold text-orange-600">{stats.transfers.transfer_rate}%</span>
                                            </div>
                                            {stats.transfers.by_destination.length > 0 && (
                                                <div className="mt-4">
                                                    <p className="text-sm font-medium text-gray-500 mb-2">Top destinataires</p>
                                                    {stats.transfers.by_destination.slice(0, 5).map((dest) => (
                                                        <div key={dest.user_id} className="flex justify-between py-1">
                                                            <span className="text-sm">{dest.user_name}</span>
                                                            <span className="text-sm font-medium">{dest.count}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Trend Chart */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Activity className="h-5 w-5 mr-2" />
                                        Évolution des rendez-vous
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {stats.trend.length === 0 ? (
                                        <p className="text-gray-500 text-center py-8">Aucune donnée de tendance disponible</p>
                                    ) : (
                                        <div className="space-y-2">
                                            {/* Simple bar chart representation */}
                                            <div className="flex items-end space-x-2 h-40">
                                                {stats.trend.map((item, index) => {
                                                    const maxTotal = Math.max(...stats.trend.map(t => t.total));
                                                    const height = maxTotal > 0 ? (item.total / maxTotal) * 100 : 0;
                                                    return (
                                                        <div key={index} className="flex-1 flex flex-col items-center">
                                                            <div
                                                                className="w-full bg-blue-500 rounded-t transition-all hover:bg-blue-600"
                                                                style={{ height: `${height}%`, minHeight: item.total > 0 ? '4px' : '0' }}
                                                                title={`${item.period}: ${item.total} RDV`}
                                                            ></div>
                                                            <span className="text-xs text-gray-500 mt-1 truncate w-full text-center">
                                                                {item.period.split('-').pop()}
                                                            </span>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                            <div className="flex justify-between text-sm text-gray-500">
                                                <span>Début: {stats.trend[0]?.period}</span>
                                                <span>Fin: {stats.trend[stats.trend.length - 1]?.period}</span>
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                            </>
                            )}
                        </TabsContent>

                        {/* Availabilities Tab */}
                        <TabsContent value="availabilities" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Disponibilités des agents/pasteurs</CardTitle>
                                    <CardDescription>
                                        Vue globale des créneaux disponibles
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {stats.availabilities.length === 0 ? (
                                        <div className="text-center py-8">
                                            <Calendar className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Aucune disponibilité configurée
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-6">
                                            {stats.availabilities.map((pastor) => (
                                                <div key={pastor.pastor_id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                                    <div className="flex justify-between items-center mb-4">
                                                        <div className="flex items-center space-x-3">
                                                            <div className="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center">
                                                                <span className="text-white font-semibold">
                                                                    {pastor.pastor_name.charAt(0)}
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <h3 className="font-semibold">{pastor.pastor_name}</h3>
                                                                <p className="text-sm text-gray-500">
                                                                    {pastor.total_slots_per_week} créneaux/semaine
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                        {pastor.availabilities.map((avail) => (
                                                            <HoverCard key={avail.id} openDelay={200} closeDelay={100}>
                                                                <HoverCardTrigger asChild>
                                                                    <div
                                                                        className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600"
                                                                    >
                                                                        {/* Header: Day/Date */}
                                                                        <div className="font-semibold text-base mb-2">
                                                                            {avail.type === 'weekly'
                                                                                ? avail.day_label || getDayLabel(avail.day_of_week)
                                                                                : format(new Date(avail.specific_date!), 'd MMM yyyy', { locale: fr })
                                                                            }
                                                                        </div>

                                                                        {/* Duration */}
                                                                        <div className="flex items-center text-gray-500 mb-2">
                                                                            <Timer className="h-4 w-4 mr-1" />
                                                                            <span>{avail.slot_duration} min</span>
                                                                        </div>

                                                                        {/* Time Slots Grid */}
                                                                        <div className="flex items-center flex-wrap gap-1.5 mb-3">
                                                                            <ClockIcon className="h-4 w-4 text-gray-400" />
                                                                            {avail.time_slots.slice(0, 5).map((slot, idx) => {
                                                                                // Handle both old string format and new object format
                                                                                const slotTime = typeof slot === 'string' ? slot : slot.time;
                                                                                const slotStatus = typeof slot === 'string' ? 'available' : slot.status;
                                                                                return (
                                                                                    <span
                                                                                        key={idx}
                                                                                        title={slotStatus === 'available' ? 'Libre' : slotStatus === 'occupied' ? 'Occupé' : 'Dépassé'}
                                                                                        className={`px-2 py-0.5 rounded text-xs font-medium cursor-default ${
                                                                                            slotStatus === 'available'
                                                                                                ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border border-green-300 dark:border-green-700'
                                                                                                : slotStatus === 'occupied'
                                                                                                ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border border-red-300 dark:border-red-700'
                                                                                                : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600'
                                                                                        }`}
                                                                                    >
                                                                                        {slotTime}
                                                                                    </span>
                                                                                );
                                                                            })}
                                                                            {avail.time_slots.length > 5 && (
                                                                                <span className="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700 rounded text-xs font-medium">
                                                                                    +{avail.time_slots.length - 5}
                                                                                </span>
                                                                            )}
                                                                            <span className="text-xs text-gray-400 ml-1">
                                                                                ({avail.slots_count} créneaux)
                                                                            </span>
                                                                        </div>

                                                                        {/* Consultation Mode Badge */}
                                                                        <Badge
                                                                            variant="outline"
                                                                            className={`text-xs ${
                                                                                avail.consultation_mode === 'in_person'
                                                                                    ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700'
                                                                                    : avail.consultation_mode === 'zoom'
                                                                                    ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700'
                                                                                    : 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700'
                                                                            }`}
                                                                        >
                                                                            {avail.consultation_mode === 'in_person' && <MapPin className="h-3 w-3 mr-1" />}
                                                                            {avail.consultation_mode === 'zoom' && <Video className="h-3 w-3 mr-1" />}
                                                                            {avail.consultation_mode === 'hybrid' && <Users className="h-3 w-3 mr-1" />}
                                                                            {avail.consultation_mode === 'in_person' ? 'En présentiel' :
                                                                             avail.consultation_mode === 'zoom' ? 'Visioconférence' : 'Hybride'}
                                                                        </Badge>
                                                                    </div>
                                                                </HoverCardTrigger>
                                                                <HoverCardContent className="w-96" side="top">
                                                                    <div className="space-y-4">
                                                                        {/* Header */}
                                                                        <div className="flex items-center space-x-3">
                                                                            <div className="h-10 w-10 bg-purple-600 rounded-full flex items-center justify-center">
                                                                                <Calendar className="h-5 w-5 text-white" />
                                                                            </div>
                                                                            <div>
                                                                                <h4 className="font-semibold text-sm">
                                                                                    {avail.type === 'weekly' ? 'Créneau hebdomadaire' : 'Créneau ponctuel'}
                                                                                </h4>
                                                                                <p className="text-xs text-muted-foreground">
                                                                                    {pastor.pastor_name}
                                                                                </p>
                                                                            </div>
                                                                        </div>

                                                                        {/* Basic Info */}
                                                                        <div className="border-t pt-3 space-y-2">
                                                                            <div className="flex items-center text-sm">
                                                                                <CalendarIcon className="h-4 w-4 mr-2 text-gray-500" />
                                                                                <span className="font-medium">
                                                                                    {avail.type === 'weekly'
                                                                                        ? `Chaque ${avail.day_label || getDayLabel(avail.day_of_week)}`
                                                                                        : format(new Date(avail.specific_date!), 'EEEE d MMMM yyyy', { locale: fr })
                                                                                    }
                                                                                </span>
                                                                            </div>

                                                                            <div className="flex items-center text-sm">
                                                                                <ClockIcon className="h-4 w-4 mr-2 text-gray-500" />
                                                                                <span>{avail.start_time} - {avail.end_time}</span>
                                                                            </div>

                                                                            <div className="flex items-center text-sm">
                                                                                <Timer className="h-4 w-4 mr-2 text-gray-500" />
                                                                                <span>Durée: {avail.slot_duration} min par créneau</span>
                                                                            </div>
                                                                        </div>

                                                                        {/* Time Slots List */}
                                                                        <div className="border-t pt-3">
                                                                            <div className="flex items-center justify-between mb-2">
                                                                                <p className="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                                                    Créneaux ({avail.slots_count}):
                                                                                </p>
                                                                                <div className="flex items-center gap-2 text-[10px]">
                                                                                    <span className="flex items-center gap-1">
                                                                                        <span className="w-2 h-2 rounded-full bg-green-500"></span>
                                                                                        Libre
                                                                                    </span>
                                                                                    <span className="flex items-center gap-1">
                                                                                        <span className="w-2 h-2 rounded-full bg-red-500"></span>
                                                                                        Occupé
                                                                                    </span>
                                                                                    <span className="flex items-center gap-1">
                                                                                        <span className="w-2 h-2 rounded-full bg-gray-400"></span>
                                                                                        Dépassé
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                            <div className="flex flex-wrap gap-1.5">
                                                                                {avail.time_slots.map((slot, idx) => {
                                                                                    // Handle both old string format and new object format
                                                                                    const slotTime = typeof slot === 'string' ? slot : slot.time;
                                                                                    const slotStatus = typeof slot === 'string' ? 'available' : slot.status;
                                                                                    return (
                                                                                        <span
                                                                                            key={idx}
                                                                                            title={slotStatus === 'available' ? 'Libre' : slotStatus === 'occupied' ? 'Occupé' : 'Dépassé'}
                                                                                            className={`px-2 py-1 rounded text-xs font-medium cursor-default ${
                                                                                                slotStatus === 'available'
                                                                                                    ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border border-green-300 dark:border-green-700'
                                                                                                    : slotStatus === 'occupied'
                                                                                                    ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border border-red-300 dark:border-red-700'
                                                                                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600'
                                                                                            }`}
                                                                                        >
                                                                                            {slotTime}
                                                                                        </span>
                                                                                    );
                                                                                })}
                                                                            </div>
                                                                        </div>

                                                                        {/* Location Info */}
                                                                        {(avail.location || avail.room) && (
                                                                            <div className="border-t pt-3 space-y-2">
                                                                                <p className="text-xs font-medium text-gray-600 dark:text-gray-400">Lieu:</p>
                                                                                {avail.room && (
                                                                                    <div className="flex items-center text-sm">
                                                                                        <Users className="h-4 w-4 mr-2 text-purple-500" />
                                                                                        <span className="font-medium">Salle: {avail.room}</span>
                                                                                    </div>
                                                                                )}
                                                                                {avail.location && (
                                                                                    <div className="flex items-start text-sm">
                                                                                        <MapPin className="h-4 w-4 mr-2 text-green-500 mt-0.5" />
                                                                                        <span>{avail.location}</span>
                                                                                    </div>
                                                                                )}
                                                                            </div>
                                                                        )}

                                                                        {/* Meeting Link */}
                                                                        {avail.meeting_link && (
                                                                            <div className="border-t pt-3">
                                                                                <div className="flex items-center text-sm">
                                                                                    <Video className="h-4 w-4 mr-2 text-blue-500" />
                                                                                    <a
                                                                                        href={avail.meeting_link}
                                                                                        target="_blank"
                                                                                        rel="noopener noreferrer"
                                                                                        className="text-blue-600 hover:underline truncate"
                                                                                    >
                                                                                        {avail.meeting_link}
                                                                                    </a>
                                                                                </div>
                                                                            </div>
                                                                        )}

                                                                        {/* Notes */}
                                                                        {avail.notes && (
                                                                            <div className="border-t pt-3">
                                                                                <p className="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes:</p>
                                                                                <p className="text-sm text-gray-600 dark:text-gray-400">{avail.notes}</p>
                                                                            </div>
                                                                        )}

                                                                        {/* Consultation Mode */}
                                                                        <div className="border-t pt-3">
                                                                            <div className="flex items-center text-sm">
                                                                                {avail.consultation_mode === 'zoom' ? (
                                                                                    <Video className="h-4 w-4 mr-2 text-blue-500" />
                                                                                ) : avail.consultation_mode === 'in_person' ? (
                                                                                    <MapPin className="h-4 w-4 mr-2 text-green-500" />
                                                                                ) : (
                                                                                    <Users className="h-4 w-4 mr-2 text-purple-500" />
                                                                                )}
                                                                                <span>
                                                                                    {avail.consultation_mode === 'in_person' ? 'Consultation en présentiel uniquement' :
                                                                                     avail.consultation_mode === 'zoom' ? 'Consultation en visioconférence uniquement' : 'Présentiel ou visioconférence au choix'}
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </HoverCardContent>
                                                            </HoverCard>
                                                        ))}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* All Appointments Tab */}
                        <TabsContent value="all" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Tous les rendez-vous</CardTitle>
                                    <CardDescription>
                                        Liste complète des rendez-vous de soin pastoral
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {appointments.data.length === 0 ? (
                                        <div className="text-center py-8">
                                            <UserGroupIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Aucun rendez-vous trouvé
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {appointments.data.map((appointment) => (
                                                <AppointmentCard
                                                    key={appointment.id}
                                                    appointment={appointment}
                                                    onTransfer={() => openTransferDialog(appointment)}
                                                    canTransfer={can.transfer && ['pending', 'confirmed'].includes(appointment.status)}
                                                />
                                            ))}

                                            {/* Pagination */}
                                            {appointments.meta.last_page > 1 && (
                                                <div className="flex justify-center space-x-2 mt-6">
                                                    <Button
                                                        variant="outline"
                                                        disabled={appointments.meta.current_page === 1}
                                                        onClick={() => router.get('/pastoral-care/mlr', {
                                                            period,
                                                            page: appointments.meta.current_page - 1
                                                        })}
                                                    >
                                                        Précédent
                                                    </Button>
                                                    <span className="flex items-center px-4">
                                                        Page {appointments.meta.current_page} sur {appointments.meta.last_page}
                                                    </span>
                                                    <Button
                                                        variant="outline"
                                                        disabled={appointments.meta.current_page === appointments.meta.last_page}
                                                        onClick={() => router.get('/pastoral-care/mlr', {
                                                            period,
                                                            page: appointments.meta.current_page + 1
                                                        })}
                                                    >
                                                        Suivant
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>

            {/* Transfer Dialog */}
            <Dialog open={transferDialogOpen} onOpenChange={setTransferDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Transférer le rendez-vous</DialogTitle>
                        <DialogDescription>
                            Sélectionnez le pasteur/agent destinataire et indiquez la raison du transfert.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4 px-6">
                        {selectedAppointment && (
                            <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm">
                                <p><strong>Client:</strong> {selectedAppointment.client_name || 'Non spécifié'}</p>
                                <p><strong>Date:</strong> {formatDate(selectedAppointment.appointment_date)}</p>
                                <p><strong>Heure:</strong> {formatTime(selectedAppointment.appointment_time)}</p>
                                <p><strong>Assigné à:</strong> {selectedAppointment.pastor.first_name} {selectedAppointment.pastor.last_name}</p>
                            </div>
                        )}
                        <div>
                            <Label htmlFor="transfer_to">Transférer à *</Label>
                            <SearchableSelect
                                options={pastors
                                    .filter(p => p.id !== selectedAppointment?.pastor.id)
                                    .map(p => ({
                                        value: p.id.toString(),
                                        label: `${p.first_name} ${p.last_name}`
                                    }))}
                                value={transferToId}
                                onChange={(value) => setTransferToId(value?.toString() || '')}
                                placeholder="Sélectionnez un destinataire..."
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <Label htmlFor="transfer_reason">Raison du transfert (optionnel)</Label>
                            <Textarea
                                id="transfer_reason"
                                value={transferReason}
                                onChange={(e) => setTransferReason(e.target.value)}
                                placeholder="Indiquez la raison du transfert..."
                                className="mt-2"
                                rows={3}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setTransferDialogOpen(false)}>
                            Annuler
                        </Button>
                        <Button onClick={handleTransfer} disabled={isTransferring || !transferToId}>
                            {isTransferring ? 'Transfert en cours...' : 'Transférer'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </DashboardLayout>
    );
}

// Appointment Card Component
interface AppointmentCardProps {
    appointment: PastoralCareAppointment;
    onTransfer: () => void;
    canTransfer: boolean;
}

function AppointmentCard({ appointment, onTransfer, canTransfer }: AppointmentCardProps) {
    const formatDate = (dateString: string) => {
        return format(new Date(dateString), 'EEEE d MMMM yyyy', { locale: fr });
    };

    const formatTime = (timeString: string) => {
        return format(new Date(timeString), 'HH:mm', { locale: fr });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">En attente</Badge>;
            case 'confirmed':
                return <Badge variant="default" className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Confirmé</Badge>;
            case 'completed':
                return <Badge variant="outline" className="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Terminé</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Annulé</Badge>;
            case 'no_show':
                return <Badge variant="destructive" className="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Absent</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const getLocationIcon = (locationType: string) => {
        switch (locationType) {
            case 'zoom':
                return <Video className="h-4 w-4 text-blue-600" />;
            case 'hybrid':
                return <div className="flex space-x-1">
                    <MapPin className="h-3 w-3 text-green-600" />
                    <Video className="h-3 w-3 text-blue-600" />
                </div>;
            default:
                return <MapPin className="h-4 w-4 text-green-600" />;
        }
    };

    return (
        <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <div className="flex items-start justify-between mb-4">
                <div className="flex items-center space-x-4">
                    <div className="flex-shrink-0">
                        <div className="h-12 w-12 bg-blue-600 rounded-full flex items-center justify-center">
                            <span className="text-white font-semibold">
                                {appointment.client_name ? appointment.client_name.charAt(0) : 'R'}
                            </span>
                        </div>
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 dark:text-white text-lg">
                            {appointment.client_name || 'Rendez-vous interne'}
                        </h3>
                        <div className="flex items-center space-x-4 mt-1">
                            {appointment.client_email && (
                                <p className="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                    <Mail className="h-4 w-4 mr-1" />
                                    {appointment.client_email}
                                </p>
                            )}
                            {appointment.client_phone && (
                                <p className="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                    <Phone className="h-4 w-4 mr-1" />
                                    {appointment.client_phone}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
                <div className="flex items-center space-x-2">
                    {getLocationIcon(appointment.location_type)}
                    {getStatusBadge(appointment.status)}
                </div>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Date</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {formatDate(appointment.appointment_date)}
                    </p>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Heure</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {formatTime(appointment.appointment_time)} ({appointment.duration_minutes}min)
                    </p>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Type</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {appointment.location_type === 'zoom' ? 'Visioconférence' :
                         appointment.location_type === 'hybrid' ? 'Hybride' : 'En présentiel'}
                    </p>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Créé le</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {format(new Date(appointment.created_at), 'd/M/yyyy', { locale: fr })}
                    </p>
                </div>
            </div>

            {/* Theme and Transfer info */}
            <div className="flex flex-wrap gap-2 mb-4">
                {appointment.theme_label && (
                    <Badge variant="outline" className="bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                        {appointment.theme_label}
                    </Badge>
                )}
                {appointment.parent && (
                    <Badge variant="outline" className="bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                        Follow-up
                    </Badge>
                )}
                {appointment.transferred_at && (
                    <Badge variant="outline" className="bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                        Transféré
                    </Badge>
                )}
            </div>

            {appointment.notes && (
                <div className="mb-4">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</p>
                    <p className="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 p-2 rounded">
                        {appointment.notes}
                    </p>
                </div>
            )}

            <div className="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <div className="text-sm text-gray-500">
                    Pasteur: <span className="font-medium">{appointment.pastor.first_name} {appointment.pastor.last_name}</span>
                </div>
                <div className="flex space-x-2">
                    {canTransfer && (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={onTransfer}
                            className="text-orange-600 border-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20"
                        >
                            <ArrowsRightLeftIcon className="h-4 w-4 mr-1" />
                            Transférer
                        </Button>
                    )}
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => router.visit(`/pastoral-care/appointments/${appointment.uuid}`)}
                    >
                        <EyeIcon className="h-4 w-4 mr-1" />
                        Voir
                    </Button>
                </div>
            </div>
        </div>
    );
}
