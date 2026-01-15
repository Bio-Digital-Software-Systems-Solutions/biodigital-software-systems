import { useState, useEffect, useCallback, useRef } from 'react';
import axios from 'axios';
import { EventCheckin, EventRegistration, CheckInStats, CheckInResult } from '@/Types/event';
import { apiLogger } from '@/utils/logger';

interface UseEventCheckInOptions {
    eventId: number | string;
    autoFetch?: boolean;
    pollInterval?: number; // ms, for live feed
}

export const useEventCheckIn = ({ eventId, autoFetch = true, pollInterval }: UseEventCheckInOptions) => {
    const [checkins, setCheckins] = useState<EventCheckin[]>([]);
    const [stats, setStats] = useState<CheckInStats | null>(null);
    const [recentCheckins, setRecentCheckins] = useState<EventCheckin[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [lastId, setLastId] = useState(0);
    const pollRef = useRef<NodeJS.Timeout | null>(null);

    const fetchCheckins = useCallback(async (page = 1, sessionId?: number) => {
        try {
            setLoading(true);
            const response = await axios.get(`/api/events/${eventId}/checkin`, {
                params: { page, session_id: sessionId },
            });
            setCheckins(response.data.data || []);
            setError(null);
        } catch (err) {
            setError('Erreur lors du chargement des check-ins');
            apiLogger.error('Error fetching checkins', err);
        } finally {
            setLoading(false);
        }
    }, [eventId]);

    const fetchStats = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/checkin/stats`);
            setStats(response.data.data);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching check-in stats', err);
            throw err;
        }
    }, [eventId]);

    const fetchRecent = useCallback(async (limit = 10) => {
        try {
            const response = await axios.get(`/api/events/${eventId}/checkin/recent`, {
                params: { limit },
            });
            setRecentCheckins(response.data.data || []);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching recent checkins', err);
            throw err;
        }
    }, [eventId]);

    const checkInByQR = useCallback(async (qrCode: string, sessionId?: number, metadata?: { device_id?: string; location?: string }): Promise<CheckInResult> => {
        try {
            const response = await axios.post(`/api/events/${eventId}/checkin/qr`, {
                qr_code: qrCode,
                session_id: sessionId,
                ...metadata,
            });

            if (response.data.success && response.data.checkin) {
                setRecentCheckins(prev => [response.data.checkin, ...prev.slice(0, 9)]);
            }

            // Refresh stats after successful check-in
            if (response.data.success) {
                fetchStats();
            }

            return response.data;
        } catch (err: any) {
            apiLogger.error('Error checking in by QR', err);
            return {
                success: false,
                message: err.response?.data?.message || 'Erreur lors du check-in',
            };
        }
    }, [eventId, fetchStats]);

    const checkInByNumber = useCallback(async (registrationNumber: string, sessionId?: number, metadata?: { device_id?: string; location?: string }): Promise<CheckInResult> => {
        try {
            const response = await axios.post(`/api/events/${eventId}/checkin/number`, {
                registration_number: registrationNumber,
                session_id: sessionId,
                ...metadata,
            });

            if (response.data.success && response.data.checkin) {
                setRecentCheckins(prev => [response.data.checkin, ...prev.slice(0, 9)]);
            }

            if (response.data.success) {
                fetchStats();
            }

            return response.data;
        } catch (err: any) {
            apiLogger.error('Error checking in by number', err);
            return {
                success: false,
                message: err.response?.data?.message || 'Erreur lors du check-in',
            };
        }
    }, [eventId, fetchStats]);

    const checkInManual = useCallback(async (registrationId: number, sessionId?: number, metadata?: { device_id?: string; location?: string }): Promise<CheckInResult> => {
        try {
            const response = await axios.post(`/api/events/${eventId}/checkin/${registrationId}`, {
                session_id: sessionId,
                ...metadata,
            });

            if (response.data.success && response.data.checkin) {
                setRecentCheckins(prev => [response.data.checkin, ...prev.slice(0, 9)]);
            }

            if (response.data.success) {
                fetchStats();
            }

            return response.data;
        } catch (err: any) {
            apiLogger.error('Error manual check-in', err);
            return {
                success: false,
                message: err.response?.data?.message || 'Erreur lors du check-in',
            };
        }
    }, [eventId, fetchStats]);

    const checkOut = useCallback(async (registrationId: number, sessionId?: number) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/checkin/${registrationId}/checkout`, {
                session_id: sessionId,
            });
            fetchStats();
            return response.data;
        } catch (err) {
            apiLogger.error('Error checking out', err);
            throw err;
        }
    }, [eventId, fetchStats]);

    const undoCheckIn = useCallback(async (checkinId: number) => {
        try {
            await axios.delete(`/api/events/${eventId}/checkin/${checkinId}`);
            setRecentCheckins(prev => prev.filter(c => c.id !== checkinId));
            fetchStats();
        } catch (err) {
            apiLogger.error('Error undoing check-in', err);
            throw err;
        }
    }, [eventId, fetchStats]);

    const searchAttendees = useCallback(async (query: string): Promise<EventRegistration[]> => {
        if (query.length < 2) return [];

        try {
            const response = await axios.get(`/api/events/${eventId}/checkin/search`, {
                params: { q: query },
            });
            return response.data.data || [];
        } catch (err) {
            apiLogger.error('Error searching attendees', err);
            return [];
        }
    }, [eventId]);

    const getCheckInHistory = useCallback(async (registrationId: number) => {
        try {
            const response = await axios.get(`/api/events/${eventId}/checkin/${registrationId}/history`);
            return response.data.data || [];
        } catch (err) {
            apiLogger.error('Error fetching check-in history', err);
            throw err;
        }
    }, [eventId]);

    const getSessionAttendance = useCallback(async (sessionId: number) => {
        try {
            const response = await axios.get(`/api/events/${eventId}/checkin/session/${sessionId}`);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching session attendance', err);
            throw err;
        }
    }, [eventId]);

    const markNoShows = useCallback(async () => {
        try {
            const response = await axios.post(`/api/events/${eventId}/checkin/no-shows`);
            return response.data;
        } catch (err) {
            apiLogger.error('Error marking no-shows', err);
            throw err;
        }
    }, [eventId]);

    // Live feed polling
    const fetchLiveFeed = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/checkin/live`, {
                params: { since: lastId },
            });

            if (response.data.checkins?.length > 0) {
                setRecentCheckins(prev => [...response.data.checkins, ...prev].slice(0, 50));
                setLastId(response.data.last_id);
            }

            if (response.data.stats) {
                setStats(response.data.stats);
            }
        } catch (err) {
            apiLogger.error('Error fetching live feed', err);
        }
    }, [eventId, lastId]);

    // Start/stop polling
    const startPolling = useCallback(() => {
        if (pollInterval && !pollRef.current) {
            pollRef.current = setInterval(fetchLiveFeed, pollInterval);
        }
    }, [pollInterval, fetchLiveFeed]);

    const stopPolling = useCallback(() => {
        if (pollRef.current) {
            clearInterval(pollRef.current);
            pollRef.current = null;
        }
    }, []);

    useEffect(() => {
        if (autoFetch) {
            fetchStats();
            fetchRecent();
        }
    }, [autoFetch, fetchStats, fetchRecent]);

    useEffect(() => {
        if (pollInterval) {
            startPolling();
        }
        return () => stopPolling();
    }, [pollInterval, startPolling, stopPolling]);

    return {
        checkins,
        stats,
        recentCheckins,
        loading,
        error,
        fetchCheckins,
        fetchStats,
        fetchRecent,
        checkInByQR,
        checkInByNumber,
        checkInManual,
        checkOut,
        undoCheckIn,
        searchAttendees,
        getCheckInHistory,
        getSessionAttendance,
        markNoShows,
        startPolling,
        stopPolling,
        refetch: () => {
            fetchStats();
            fetchRecent();
        },
    };
};
