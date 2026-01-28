import React, { useState, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import {
    ChartBarIcon,
    UserGroupIcon,
    TicketIcon,
    CurrencyEuroIcon,
    CheckCircleIcon,
    ClockIcon,
    StarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CalendarDaysIcon,
    ArrowPathIcon,
    ArrowDownTrayIcon,
    ChartPieIcon,
    IdentificationIcon,
} from '@heroicons/react/24/outline';
import { useEventAnalytics } from '@/Hooks/useEventAnalytics';
import {
    EventDashboard,
    RegistrationStats,
    TicketStats,
    CheckInStats,
    RevenueStats,
    FeedbackStats,
    BadgeStats,
} from '@/Types/event';
import { toast } from 'sonner';

interface EventAnalyticsDashboardProps {
    eventId: number | string;
    onExport?: (format: string) => void;
}

type TabType = 'overview' | 'registrations' | 'revenue' | 'attendance' | 'feedback';

export const EventAnalyticsDashboard: React.FC<EventAnalyticsDashboardProps> = ({
    eventId,
    onExport,
}) => {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState<TabType>('overview');

    const {
        dashboard,
        loading,
        error,
        refetch,
        exportAnalytics,
        clearCache,
    } = useEventAnalytics({ eventId, autoFetch: true });

    const handleExport = async (format: string) => {
        try {
            const data = await exportAnalytics(format);
            if (onExport) {
                onExport(format);
            }
            toast.success(t('events.analytics.export_success', 'Export réussi'));
        } catch {
            toast.error(t('events.analytics.export_error', 'Erreur lors de l\'export'));
        }
    };

    const handleClearCache = async () => {
        try {
            await clearCache();
            toast.success(t('events.analytics.cache_cleared', 'Cache vidé'));
        } catch {
            toast.error(t('events.analytics.cache_error', 'Erreur lors du vidage du cache'));
        }
    };

    if (loading && !dashboard) {
        return (
            <div className="flex items-center justify-center py-12">
                <ArrowPathIcon className="h-8 w-8 text-gray-400 animate-spin" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="text-center py-12">
                <ChartBarIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-500 dark:text-gray-400">{error}</p>
                <button
                    onClick={refetch}
                    className="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                >
                    {t('common.retry', 'Réessayer')}
                </button>
            </div>
        );
    }

    if (!dashboard) return null;

    const tabs = [
        { id: 'overview' as TabType, label: t('events.analytics.overview', 'Vue d\'ensemble'), icon: ChartBarIcon },
        { id: 'registrations' as TabType, label: t('events.analytics.registrations', 'Inscriptions'), icon: UserGroupIcon },
        { id: 'revenue' as TabType, label: t('events.analytics.revenue', 'Revenus'), icon: CurrencyEuroIcon },
        { id: 'attendance' as TabType, label: t('events.analytics.attendance', 'Présence'), icon: CheckCircleIcon },
        { id: 'feedback' as TabType, label: t('events.analytics.feedback', 'Feedback'), icon: StarIcon },
    ];

    return (
        <div className="space-y-6">
            {/* Header with Actions */}
            <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <ChartBarIcon className="h-6 w-6 text-indigo-600" />
                    {t('events.analytics.title', 'Tableau de bord analytique')}
                </h2>
                <div className="flex gap-2">
                    <button
                        onClick={() => handleExport('json')}
                        className="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center gap-2"
                    >
                        <ArrowDownTrayIcon className="h-4 w-4" />
                        Export JSON
                    </button>
                    <button
                        onClick={handleClearCache}
                        className="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700"
                    >
                        {t('events.analytics.clear_cache', 'Vider le cache')}
                    </button>
                    <button
                        onClick={refetch}
                        disabled={loading}
                        className="px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2 disabled:opacity-50"
                    >
                        <ArrowPathIcon className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                        {t('common.refresh', 'Actualiser')}
                    </button>
                </div>
            </div>

            {/* Event Overview Card */}
            <div className="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-6 text-white">
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 className="text-2xl font-bold">{dashboard.overview.event.title}</h3>
                        <div className="flex items-center gap-4 mt-2 text-indigo-100">
                            <span className="flex items-center gap-1">
                                <CalendarDaysIcon className="h-4 w-4" />
                                {new Date(dashboard.overview.event.start_date).toLocaleDateString('fr-FR')}
                            </span>
                            <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                                dashboard.overview.event.is_ongoing
                                    ? 'bg-green-500'
                                    : dashboard.overview.event.has_ended
                                    ? 'bg-gray-500'
                                    : 'bg-yellow-500'
                            }`}>
                                {dashboard.overview.event.status}
                            </span>
                        </div>
                    </div>
                    <div className="flex gap-6">
                        <div className="text-center">
                            <p className="text-3xl font-bold">{dashboard.overview.capacity.registered}</p>
                            <p className="text-sm text-indigo-100">{t('events.analytics.registered', 'Inscrits')}</p>
                        </div>
                        {dashboard.overview.capacity.max && (
                            <div className="text-center">
                                <p className="text-3xl font-bold">{dashboard.overview.capacity.max}</p>
                                <p className="text-sm text-indigo-100">{t('events.analytics.capacity', 'Capacité')}</p>
                            </div>
                        )}
                        <div className="text-center">
                            <p className="text-3xl font-bold">{Math.abs(Math.round(dashboard.overview.event.days_until))}</p>
                            <p className="text-sm text-indigo-100">
                                {dashboard.overview.event.days_until >= 0
                                    ? t('events.analytics.days_until', 'Jours avant')
                                    : t('events.analytics.days_ago', 'Jours passés')}
                            </p>
                        </div>
                    </div>
                </div>
                {dashboard.overview.capacity.utilization !== undefined && (
                    <div className="mt-4">
                        <div className="flex justify-between text-sm mb-1">
                            <span>{t('events.analytics.utilization', 'Taux de remplissage')}</span>
                            <span>{(dashboard.overview.capacity.utilization ?? 0).toFixed(1)}%</span>
                        </div>
                        <div className="h-2 bg-white/30 rounded-full overflow-hidden">
                            <div
                                className="h-full bg-white rounded-full transition-all duration-500"
                                style={{ width: `${Math.min(100, dashboard.overview.capacity.utilization)}%` }}
                            />
                        </div>
                    </div>
                )}
            </div>

            {/* Tabs */}
            <div className="border-b border-gray-200 dark:border-gray-700">
                <nav className="flex space-x-4 overflow-x-auto" aria-label="Tabs">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors flex items-center gap-2 ${
                                activeTab === tab.id
                                    ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                            }`}
                        >
                            <tab.icon className="h-4 w-4" />
                            {tab.label}
                        </button>
                    ))}
                </nav>
            </div>

            {/* Tab Content */}
            <div className="space-y-6">
                {activeTab === 'overview' && <OverviewTab dashboard={dashboard} />}
                {activeTab === 'registrations' && <RegistrationsTab stats={dashboard.registrations} />}
                {activeTab === 'revenue' && <RevenueTab stats={dashboard.revenue} />}
                {activeTab === 'attendance' && <AttendanceTab checkins={dashboard.checkins} badges={dashboard.badges} />}
                {activeTab === 'feedback' && <FeedbackTab stats={dashboard.feedback} />}
            </div>
        </div>
    );
};

// Overview Tab
const OverviewTab: React.FC<{ dashboard: EventDashboard }> = ({ dashboard }) => {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            {/* Key Metrics */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <MetricCard
                    title={t('events.analytics.total_registrations', 'Inscriptions totales')}
                    value={dashboard.registrations.total}
                    icon={UserGroupIcon}
                    color="blue"
                />
                <MetricCard
                    title={t('events.analytics.checked_in', 'Présents')}
                    value={dashboard.checkins.checked_in}
                    subtitle={`${(dashboard.checkins.attendance_rate ?? 0).toFixed(1)}%`}
                    icon={CheckCircleIcon}
                    color="green"
                />
                <MetricCard
                    title={t('events.analytics.total_revenue', 'Revenus')}
                    value={`${dashboard.revenue.collected.amount.toLocaleString('fr-FR')} ${dashboard.tickets.currency}`}
                    icon={CurrencyEuroIcon}
                    color="purple"
                />
                <MetricCard
                    title={t('events.analytics.avg_rating', 'Note moyenne')}
                    value={dashboard.feedback.overall_rating?.toFixed(1) || '-'}
                    subtitle={`${dashboard.feedback.count} avis`}
                    icon={StarIcon}
                    color="yellow"
                />
            </div>

            {/* Trends Chart Placeholder */}
            {dashboard.trends?.daily && dashboard.trends.daily.length > 0 && (
                <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <ArrowTrendingUpIcon className="h-5 w-5 text-gray-500" />
                        {t('events.analytics.daily_trends', 'Tendances journalières')}
                    </h3>
                    <div className="h-64 flex items-end gap-1">
                        {dashboard.trends.daily.map((day, index) => {
                            const maxReg = Math.max(...dashboard.trends.daily.map(d => d.registrations));
                            const height = maxReg > 0 ? (day.registrations / maxReg) * 100 : 0;
                            return (
                                <div
                                    key={index}
                                    className="flex-1 flex flex-col items-center gap-1"
                                >
                                    <div
                                        className="w-full bg-indigo-500 rounded-t transition-all duration-300 hover:bg-indigo-600"
                                        style={{ height: `${height}%`, minHeight: day.registrations > 0 ? '4px' : '0' }}
                                        title={`${day.registrations} inscriptions`}
                                    />
                                    <span className="text-xs text-gray-500 dark:text-gray-400 transform -rotate-45 origin-left">
                                        {new Date(day.date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Ticket Distribution */}
            {dashboard.tickets && (
                <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <TicketIcon className="h-5 w-5 text-gray-500" />
                        {t('events.analytics.ticket_sales', 'Ventes par type de billet')}
                    </h3>
                    <div className="space-y-4">
                        {Object.entries(dashboard.tickets.by_type).map(([type, data]) => (
                            <div key={type}>
                                <div className="flex justify-between text-sm mb-1">
                                    <span className="font-medium text-gray-700 dark:text-gray-300 capitalize">{type}</span>
                                    <span className="text-gray-500 dark:text-gray-400">
                                        {data.sold} / {data.count} ({data.count > 0 ? ((data.sold / data.count) * 100).toFixed(0) : 0}%)
                                    </span>
                                </div>
                                <div className="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        className="h-full bg-indigo-500 rounded-full transition-all duration-500"
                                        style={{ width: `${(data.sold / data.count) * 100}%` }}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

// Registrations Tab
const RegistrationsTab: React.FC<{ stats: RegistrationStats }> = ({ stats }) => {
    const { t } = useTranslation();

    const statusColors: Record<string, string> = {
        confirmed: 'bg-green-500',
        pending: 'bg-yellow-500',
        waitlisted: 'bg-orange-500',
        cancelled: 'bg-red-500',
        checked_in: 'bg-blue-500',
        no_show: 'bg-gray-500',
    };

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                <MetricCard
                    title={t('events.analytics.total', 'Total')}
                    value={stats.total}
                    icon={UserGroupIcon}
                    color="blue"
                />
                <MetricCard
                    title={t('events.analytics.total_attendees', 'Participants')}
                    value={stats.total_attendees}
                    icon={CheckCircleIcon}
                    color="green"
                />
                <MetricCard
                    title={t('events.analytics.conversion_rate', 'Taux de conversion')}
                    value={`${(stats.conversion_rate ?? 0).toFixed(1)}%`}
                    icon={ArrowTrendingUpIcon}
                    color="purple"
                />
            </div>

            {/* Status Distribution */}
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {t('events.analytics.by_status', 'Par statut')}
                </h3>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                    {Object.entries(stats.by_status).map(([status, count]) => (
                        <div
                            key={status}
                            className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                        >
                            <div className={`w-3 h-3 rounded-full ${statusColors[status] || 'bg-gray-400'}`} />
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400 capitalize">{status}</p>
                                <p className="text-xl font-bold text-gray-900 dark:text-white">{count}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* By Role */}
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {t('events.analytics.by_role', 'Par rôle')}
                </h3>
                <div className="space-y-3">
                    {Object.entries(stats.by_role).map(([role, count]) => (
                        <div key={role} className="flex items-center justify-between">
                            <span className="text-gray-700 dark:text-gray-300 capitalize">{role}</span>
                            <span className="font-medium text-gray-900 dark:text-white">{count}</span>
                        </div>
                    ))}
                </div>
            </div>

            {/* Top Companies */}
            {Object.keys(stats.by_company).length > 0 && (
                <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {t('events.analytics.top_companies', 'Top entreprises')}
                    </h3>
                    <div className="space-y-3">
                        {Object.entries(stats.by_company)
                            .sort(([, a], [, b]) => b - a)
                            .slice(0, 10)
                            .map(([company, count]) => (
                                <div key={company} className="flex items-center justify-between">
                                    <span className="text-gray-700 dark:text-gray-300">{company}</span>
                                    <span className="font-medium text-gray-900 dark:text-white">{count}</span>
                                </div>
                            ))}
                    </div>
                </div>
            )}
        </div>
    );
};

// Revenue Tab
const RevenueTab: React.FC<{ stats: RevenueStats }> = ({ stats }) => {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <MetricCard
                    title={t('events.analytics.expected_revenue', 'Revenus attendus')}
                    value={`${stats.expected.amount.toLocaleString('fr-FR')} ${stats.expected.currency}`}
                    icon={CurrencyEuroIcon}
                    color="blue"
                />
                <MetricCard
                    title={t('events.analytics.collected', 'Collecté')}
                    value={`${stats.collected.amount.toLocaleString('fr-FR')} ${stats.expected.currency}`}
                    icon={CheckCircleIcon}
                    color="green"
                />
                <MetricCard
                    title={t('events.analytics.pending_payments', 'En attente')}
                    value={`${stats.pending.amount.toLocaleString('fr-FR')} ${stats.expected.currency}`}
                    icon={ClockIcon}
                    color="yellow"
                />
                <MetricCard
                    title={t('events.analytics.refunded', 'Remboursé')}
                    value={`${stats.refunded.amount.toLocaleString('fr-FR')} ${stats.expected.currency}`}
                    subtitle={`${stats.refunded.count} remboursements`}
                    icon={ArrowTrendingDownIcon}
                    color="red"
                />
            </div>

            {/* Revenue by Ticket */}
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <TicketIcon className="h-5 w-5 text-gray-500" />
                    {t('events.analytics.revenue_by_ticket', 'Revenus par billet')}
                </h3>
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="text-left text-sm text-gray-500 dark:text-gray-400">
                                <th className="pb-3">{t('events.analytics.ticket', 'Billet')}</th>
                                <th className="pb-3 text-right">{t('events.analytics.quantity', 'Quantité')}</th>
                                <th className="pb-3 text-right">{t('events.analytics.revenue', 'Revenus')}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {stats.by_ticket.map((ticket, index) => (
                                <tr key={index}>
                                    <td className="py-3 text-gray-900 dark:text-white">{ticket.ticket_name}</td>
                                    <td className="py-3 text-right text-gray-600 dark:text-gray-400">{ticket.quantity}</td>
                                    <td className="py-3 text-right font-medium text-gray-900 dark:text-white">
                                        {ticket.revenue.toLocaleString('fr-FR')} €
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Promo Code Usage */}
            {stats.by_promo_code.length > 0 && (
                <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {t('events.analytics.promo_code_usage', 'Utilisation des codes promo')}
                    </h3>
                    <div className="space-y-3">
                        {stats.by_promo_code.map((promo, index) => (
                            <div
                                key={index}
                                className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                            >
                                <div>
                                    <p className="font-mono font-medium text-gray-900 dark:text-white">{promo.code}</p>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">{promo.uses} utilisations</p>
                                </div>
                                <p className="text-lg font-bold text-red-600 dark:text-red-400">
                                    -{promo.discount_total.toLocaleString('fr-FR')} €
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

// Attendance Tab
const AttendanceTab: React.FC<{ checkins: CheckInStats; badges: BadgeStats }> = ({ checkins, badges }) => {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            {/* Check-in Stats */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <MetricCard
                    title={t('events.analytics.expected', 'Attendus')}
                    value={checkins.total_expected}
                    icon={UserGroupIcon}
                    color="blue"
                />
                <MetricCard
                    title={t('events.analytics.checked_in', 'Enregistrés')}
                    value={checkins.checked_in}
                    icon={CheckCircleIcon}
                    color="green"
                />
                <MetricCard
                    title={t('events.analytics.not_checked_in', 'Non enregistrés')}
                    value={checkins.not_checked_in}
                    icon={ClockIcon}
                    color="orange"
                />
                <MetricCard
                    title={t('events.analytics.attendance_rate', 'Taux de présence')}
                    value={`${(checkins.attendance_rate ?? 0).toFixed(1)}%`}
                    icon={ChartPieIcon}
                    color="purple"
                />
            </div>

            {/* Check-ins by Hour */}
            {Object.keys(checkins.by_hour).length > 0 && (
                <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <ClockIcon className="h-5 w-5 text-gray-500" />
                        {t('events.analytics.checkins_by_hour', 'Check-ins par heure')}
                    </h3>
                    <div className="h-48 flex items-end gap-2">
                        {Object.entries(checkins.by_hour).map(([hour, count]) => {
                            const maxCount = Math.max(...Object.values(checkins.by_hour));
                            const height = maxCount > 0 ? (count / maxCount) * 100 : 0;
                            return (
                                <div key={hour} className="flex-1 flex flex-col items-center gap-1">
                                    <span className="text-xs text-gray-500 dark:text-gray-400">{count}</span>
                                    <div
                                        className="w-full bg-green-500 rounded-t transition-all duration-300 hover:bg-green-600"
                                        style={{ height: `${height}%`, minHeight: count > 0 ? '4px' : '0' }}
                                    />
                                    <span className="text-xs text-gray-500 dark:text-gray-400">{hour}h</span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Badge Stats */}
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <IdentificationIcon className="h-5 w-5 text-gray-500" />
                    {t('events.analytics.badge_stats', 'Statistiques badges')}
                </h3>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{badges.generated}</p>
                        <p className="text-sm text-gray-500 dark:text-gray-400">{t('events.analytics.generated', 'Générés')}</p>
                    </div>
                    <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{badges.printed}</p>
                        <p className="text-sm text-gray-500 dark:text-gray-400">{t('events.analytics.printed', 'Imprimés')}</p>
                    </div>
                    <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{badges.collected}</p>
                        <p className="text-sm text-gray-500 dark:text-gray-400">{t('events.analytics.collected', 'Récupérés')}</p>
                    </div>
                    <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{badges.pending_generation}</p>
                        <p className="text-sm text-gray-500 dark:text-gray-400">{t('events.analytics.pending', 'En attente')}</p>
                    </div>
                </div>
            </div>
        </div>
    );
};

// Feedback Tab
const FeedbackTab: React.FC<{ stats: FeedbackStats }> = ({ stats }) => {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <MetricCard
                    title={t('events.analytics.responses', 'Réponses')}
                    value={stats.count}
                    subtitle={`${(stats.response_rate ?? 0).toFixed(1)}% de taux`}
                    icon={UserGroupIcon}
                    color="blue"
                />
                <MetricCard
                    title={t('events.analytics.overall_rating', 'Note globale')}
                    value={stats.overall_rating?.toFixed(1) || '-'}
                    icon={StarIcon}
                    color="yellow"
                />
                <MetricCard
                    title={t('events.analytics.nps', 'NPS')}
                    value={stats.nps?.toFixed(0) || '-'}
                    icon={ArrowTrendingUpIcon}
                    color="purple"
                />
                <MetricCard
                    title={t('events.analytics.would_recommend', 'Recommanderait')}
                    value={stats.would_recommend?.percentage ? `${stats.would_recommend.percentage.toFixed(0)}%` : '-'}
                    icon={CheckCircleIcon}
                    color="green"
                />
            </div>

            {/* Rating Breakdown */}
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {t('events.analytics.ratings_breakdown', 'Détail des notes')}
                </h3>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <RatingItem label={t('events.analytics.content', 'Contenu')} rating={stats.ratings?.content} />
                    <RatingItem label={t('events.analytics.speaker', 'Intervenants')} rating={stats.ratings?.speaker} />
                    <RatingItem label={t('events.analytics.venue', 'Lieu')} rating={stats.ratings?.venue} />
                    <RatingItem label={t('events.analytics.organization', 'Organisation')} rating={stats.ratings?.organization} />
                </div>
            </div>

            {/* Rating Distribution */}
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {t('events.analytics.rating_distribution', 'Distribution des notes')}
                </h3>
                <div className="space-y-3">
                    {[5, 4, 3, 2, 1].map((rating) => {
                        const count = stats.rating_distribution?.[rating] || 0;
                        const percentage = stats.count > 0 ? (count / stats.count) * 100 : 0;
                        return (
                            <div key={rating} className="flex items-center gap-3">
                                <div className="flex items-center gap-1 w-16">
                                    <span className="text-sm font-medium text-gray-900 dark:text-white">{rating}</span>
                                    <StarIcon className="h-4 w-4 text-yellow-500" />
                                </div>
                                <div className="flex-1 h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        className="h-full bg-yellow-500 rounded-full transition-all duration-500"
                                        style={{ width: `${percentage}%` }}
                                    />
                                </div>
                                <span className="text-sm text-gray-500 dark:text-gray-400 w-12 text-right">{count}</span>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
};

// Metric Card Component
interface MetricCardProps {
    title: string;
    value: string | number;
    subtitle?: string;
    icon: React.ForwardRefExoticComponent<React.SVGProps<SVGSVGElement>>;
    color: 'blue' | 'green' | 'yellow' | 'purple' | 'red' | 'orange';
}

const MetricCard: React.FC<MetricCardProps> = ({ title, value, subtitle, icon: Icon, color }) => {
    const colorClasses = {
        blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        green: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
        purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
        red: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    };

    return (
        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div className="flex items-center gap-3">
                <div className={`p-2 rounded-lg ${colorClasses[color]}`}>
                    <Icon className="h-5 w-5" />
                </div>
                <div>
                    <p className="text-sm text-gray-500 dark:text-gray-400">{title}</p>
                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{value}</p>
                    {subtitle && (
                        <p className="text-xs text-gray-400 dark:text-gray-500">{subtitle}</p>
                    )}
                </div>
            </div>
        </div>
    );
};

// Rating Item Component
const RatingItem: React.FC<{ label: string; rating?: number }> = ({ label, rating }) => {
    return (
        <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div className="flex items-center justify-center gap-1 mb-1">
                <span className="text-2xl font-bold text-gray-900 dark:text-white">
                    {rating?.toFixed(1) || '-'}
                </span>
                <StarIcon className="h-5 w-5 text-yellow-500" />
            </div>
            <p className="text-sm text-gray-500 dark:text-gray-400">{label}</p>
        </div>
    );
};

export default EventAnalyticsDashboard;
