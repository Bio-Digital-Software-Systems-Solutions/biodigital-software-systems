import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { EventBadge, BadgeStats, BadgeTemplate, BadgePrintData, BadgeStatus } from '@/Types/event';
import { apiLogger } from '@/utils/logger';

interface UseEventBadgesOptions {
    eventId: number;
    autoFetch?: boolean;
    statusFilter?: BadgeStatus;
}

export const useEventBadges = ({ eventId, autoFetch = true, statusFilter }: UseEventBadgesOptions) => {
    const [badges, setBadges] = useState<EventBadge[]>([]);
    const [stats, setStats] = useState<BadgeStats | null>(null);
    const [templates, setTemplates] = useState<Record<string, BadgeTemplate>>({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 20,
        total: 0,
    });

    const fetchBadges = useCallback(async (page = 1) => {
        try {
            setLoading(true);
            const response = await axios.get(`/api/events/${eventId}/badges`, {
                params: { page, status: statusFilter },
            });
            setBadges(response.data.data || []);
            setStats(response.data.stats || null);
            setPagination({
                currentPage: response.data.meta?.current_page || 1,
                lastPage: response.data.meta?.last_page || 1,
                perPage: response.data.meta?.per_page || 20,
                total: response.data.meta?.total || 0,
            });
            setError(null);
        } catch (err) {
            setError('Erreur lors du chargement des badges');
            apiLogger.error('Error fetching badges', err);
        } finally {
            setLoading(false);
        }
    }, [eventId, statusFilter]);

    const fetchStats = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/badges/stats`);
            setStats(response.data.data);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching badge stats', err);
            throw err;
        }
    }, [eventId]);

    const fetchTemplates = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/badges/templates`);
            setTemplates(response.data.data || {});
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching templates', err);
            throw err;
        }
    }, [eventId]);

    const generateBadge = useCallback(async (registrationId: number, options?: { template?: string; custom_fields?: Record<string, any> }) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/badges/${registrationId}/generate`, options);
            const newBadge = response.data.data;
            setBadges(prev => [newBadge, ...prev]);
            fetchStats();
            return newBadge;
        } catch (err) {
            apiLogger.error('Error generating badge', err);
            throw err;
        }
    }, [eventId, fetchStats]);

    const generateBulk = useCallback(async (template?: string) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/badges/generate-bulk`, { template });
            await fetchBadges();
            return response.data;
        } catch (err) {
            apiLogger.error('Error generating bulk badges', err);
            throw err;
        }
    }, [eventId, fetchBadges]);

    const updateBadge = useCallback(async (badgeId: number, data: {
        first_name?: string;
        last_name?: string;
        company?: string;
        job_title?: string;
        template?: string;
        custom_fields?: Record<string, any>;
    }) => {
        try {
            const response = await axios.patch(`/api/events/${eventId}/badges/${badgeId}`, data);
            const updated = response.data.data;
            setBadges(prev => prev.map(b => b.id === badgeId ? updated : b));
            return updated;
        } catch (err) {
            apiLogger.error('Error updating badge', err);
            throw err;
        }
    }, [eventId]);

    const markPrinted = useCallback(async (badgeId: number) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/badges/${badgeId}/printed`);
            const updated = response.data.data;
            setBadges(prev => prev.map(b => b.id === badgeId ? updated : b));
            fetchStats();
            return updated;
        } catch (err) {
            apiLogger.error('Error marking badge as printed', err);
            throw err;
        }
    }, [eventId, fetchStats]);

    const markBulkPrinted = useCallback(async (badgeIds: number[]) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/badges/mark-printed-bulk`, {
                badge_ids: badgeIds,
            });
            await fetchBadges();
            return response.data;
        } catch (err) {
            apiLogger.error('Error bulk marking as printed', err);
            throw err;
        }
    }, [eventId, fetchBadges]);

    const markCollected = useCallback(async (badgeId: number) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/badges/${badgeId}/collected`);
            const updated = response.data.data;
            setBadges(prev => prev.map(b => b.id === badgeId ? updated : b));
            fetchStats();
            return updated;
        } catch (err) {
            apiLogger.error('Error marking badge as collected', err);
            throw err;
        }
    }, [eventId, fetchStats]);

    const reportLost = useCallback(async (badgeId: number) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/badges/${badgeId}/lost`);
            const newBadge = response.data.data;
            setBadges(prev => [newBadge, ...prev.map(b => b.id === badgeId ? { ...b, status: 'lost' as BadgeStatus } : b)]);
            fetchStats();
            return newBadge;
        } catch (err) {
            apiLogger.error('Error reporting badge as lost', err);
            throw err;
        }
    }, [eventId, fetchStats]);

    const getPrintData = useCallback(async (badgeId: number): Promise<BadgePrintData> => {
        try {
            const response = await axios.get(`/api/events/${eventId}/badges/${badgeId}/print-data`);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error getting print data', err);
            throw err;
        }
    }, [eventId]);

    const getBulkPrintData = useCallback(async (badgeIds: number[]): Promise<BadgePrintData[]> => {
        try {
            const response = await axios.post(`/api/events/${eventId}/badges/print-data-bulk`, {
                badge_ids: badgeIds,
            });
            return response.data.data || [];
        } catch (err) {
            apiLogger.error('Error getting bulk print data', err);
            throw err;
        }
    }, [eventId]);

    const searchBadges = useCallback(async (query: string): Promise<EventBadge[]> => {
        if (query.length < 2) return [];

        try {
            const response = await axios.get(`/api/events/${eventId}/badges/search`, {
                params: { q: query },
            });
            return response.data.data || [];
        } catch (err) {
            apiLogger.error('Error searching badges', err);
            return [];
        }
    }, [eventId]);

    const findByQR = useCallback(async (qrData: string): Promise<EventBadge | null> => {
        try {
            const response = await axios.post(`/api/events/${eventId}/badges/find-qr`, {
                qr_data: qrData,
            });
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error finding badge by QR', err);
            return null;
        }
    }, [eventId]);

    const getPendingPrint = useCallback(async (): Promise<EventBadge[]> => {
        try {
            const response = await axios.get(`/api/events/${eventId}/badges/pending-print`);
            return response.data.data || [];
        } catch (err) {
            apiLogger.error('Error getting pending print', err);
            throw err;
        }
    }, [eventId]);

    const getPendingCollection = useCallback(async (): Promise<EventBadge[]> => {
        try {
            const response = await axios.get(`/api/events/${eventId}/badges/pending-collection`);
            return response.data.data || [];
        } catch (err) {
            apiLogger.error('Error getting pending collection', err);
            throw err;
        }
    }, [eventId]);

    useEffect(() => {
        if (autoFetch) {
            fetchBadges();
            fetchTemplates();
        }
    }, [autoFetch, fetchBadges, fetchTemplates]);

    return {
        badges,
        stats,
        templates,
        loading,
        error,
        pagination,
        fetchBadges,
        fetchStats,
        fetchTemplates,
        generateBadge,
        generateBulk,
        updateBadge,
        markPrinted,
        markBulkPrinted,
        markCollected,
        reportLost,
        getPrintData,
        getBulkPrintData,
        searchBadges,
        findByQR,
        getPendingPrint,
        getPendingCollection,
        refetch: fetchBadges,
    };
};
