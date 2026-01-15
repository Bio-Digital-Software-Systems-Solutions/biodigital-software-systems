import { useState, useEffect, useCallback, useRef } from 'react';
import axios from 'axios';
import {
    EventDashboard,
    EventOverview,
    RegistrationStats,
    RevenueStats,
    FeedbackStats,
    CheckInStats,
    BadgeStats,
} from '@/Types/event';
import { apiLogger } from '@/utils/logger';

interface UseEventAnalyticsOptions {
    eventId: number | string;
    autoFetch?: boolean;
    realtimePollInterval?: number; // ms for realtime updates
}

export const useEventAnalytics = ({ eventId, autoFetch = true, realtimePollInterval }: UseEventAnalyticsOptions) => {
    const [dashboard, setDashboard] = useState<EventDashboard | null>(null);
    const [overview, setOverview] = useState<EventOverview | null>(null);
    const [registrationStats, setRegistrationStats] = useState<RegistrationStats | null>(null);
    const [revenueStats, setRevenueStats] = useState<RevenueStats | null>(null);
    const [feedbackStats, setFeedbackStats] = useState<FeedbackStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const pollRef = useRef<NodeJS.Timeout | null>(null);

    const fetchDashboard = useCallback(async () => {
        try {
            setLoading(true);
            const response = await axios.get(`/api/events/${eventId}/analytics/dashboard`);
            setDashboard(response.data.data);
            setError(null);
            return response.data.data;
        } catch (err) {
            setError('Erreur lors du chargement du dashboard');
            apiLogger.error('Error fetching dashboard', err);
            throw err;
        } finally {
            setLoading(false);
        }
    }, [eventId]);

    const fetchOverview = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/analytics/overview`);
            setOverview(response.data.data);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching overview', err);
            throw err;
        }
    }, [eventId]);

    const fetchRegistrationStats = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/analytics/registrations`);
            setRegistrationStats(response.data.data);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching registration stats', err);
            throw err;
        }
    }, [eventId]);

    const fetchRevenueStats = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/analytics/revenue`);
            setRevenueStats(response.data.data);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching revenue stats', err);
            throw err;
        }
    }, [eventId]);

    const fetchFeedbackStats = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/analytics/feedback`);
            setFeedbackStats(response.data.data);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching feedback stats', err);
            throw err;
        }
    }, [eventId]);

    const fetchTrends = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/analytics/trends`);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching trends', err);
            throw err;
        }
    }, [eventId]);

    const fetchSessionAnalytics = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/analytics/sessions`);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching session analytics', err);
            throw err;
        }
    }, [eventId]);

    const fetchSponsorAnalytics = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/analytics/sponsors`);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching sponsor analytics', err);
            throw err;
        }
    }, [eventId]);

    const exportAnalytics = useCallback(async (format = 'json') => {
        try {
            const response = await axios.get(`/api/events/${eventId}/analytics/export`, {
                params: { format },
            });
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error exporting analytics', err);
            throw err;
        }
    }, [eventId]);

    const fetchRealtime = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/analytics/realtime`);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching realtime data', err);
            throw err;
        }
    }, [eventId]);

    const clearCache = useCallback(async () => {
        try {
            await axios.post(`/api/events/${eventId}/analytics/clear-cache`);
            // Refresh data after clearing cache
            await fetchDashboard();
        } catch (err) {
            apiLogger.error('Error clearing cache', err);
            throw err;
        }
    }, [eventId, fetchDashboard]);

    // Realtime polling
    const startRealtimePolling = useCallback(() => {
        if (realtimePollInterval && !pollRef.current) {
            pollRef.current = setInterval(async () => {
                try {
                    const data = await fetchRealtime();
                    // Update relevant parts of dashboard with realtime data
                    if (dashboard) {
                        setDashboard(prev => prev ? {
                            ...prev,
                            overview: {
                                ...prev.overview,
                                capacity: {
                                    ...prev.overview.capacity,
                                    registered: data.registrations?.confirmed || prev.overview.capacity.registered,
                                },
                            },
                            checkins: {
                                ...prev.checkins,
                                checked_in: data.checkins?.total || prev.checkins.checked_in,
                            },
                        } : null);
                    }
                } catch (err) {
                    // Silent fail for polling
                }
            }, realtimePollInterval);
        }
    }, [realtimePollInterval, fetchRealtime, dashboard]);

    const stopRealtimePolling = useCallback(() => {
        if (pollRef.current) {
            clearInterval(pollRef.current);
            pollRef.current = null;
        }
    }, []);

    useEffect(() => {
        if (autoFetch) {
            fetchDashboard();
        }
    }, [autoFetch, fetchDashboard]);

    useEffect(() => {
        if (realtimePollInterval) {
            startRealtimePolling();
        }
        return () => stopRealtimePolling();
    }, [realtimePollInterval, startRealtimePolling, stopRealtimePolling]);

    return {
        dashboard,
        overview: dashboard?.overview || overview,
        registrationStats: dashboard?.registrations || registrationStats,
        revenueStats: dashboard?.revenue || revenueStats,
        feedbackStats: dashboard?.feedback || feedbackStats,
        ticketStats: dashboard?.tickets,
        checkInStats: dashboard?.checkins,
        badgeStats: dashboard?.badges,
        trends: dashboard?.trends,
        loading,
        error,
        fetchDashboard,
        fetchOverview,
        fetchRegistrationStats,
        fetchRevenueStats,
        fetchFeedbackStats,
        fetchTrends,
        fetchSessionAnalytics,
        fetchSponsorAnalytics,
        exportAnalytics,
        fetchRealtime,
        clearCache,
        startRealtimePolling,
        stopRealtimePolling,
        refetch: fetchDashboard,
    };
};

// Hook for comparing events
export const useEventComparison = () => {
    const [comparison, setComparison] = useState<any>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const compareEvents = useCallback(async (eventAId: number, eventBId: number) => {
        try {
            setLoading(true);
            const response = await axios.post('/api/events/compare', {
                event_a_id: eventAId,
                event_b_id: eventBId,
            });
            setComparison(response.data.data);
            setError(null);
            return response.data.data;
        } catch (err) {
            setError('Erreur lors de la comparaison');
            apiLogger.error('Error comparing events', err);
            throw err;
        } finally {
            setLoading(false);
        }
    }, []);

    return {
        comparison,
        loading,
        error,
        compareEvents,
    };
};
