import { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { EventRegistration, RegistrationStats, RegistrationFormData, RegistrationStatus, PaginatedResponse } from '@/Types/event';
import { apiLogger } from '@/utils/logger';

interface RegistrationFilters {
    status?: RegistrationStatus;
    ticket_id?: number;
    search?: string;
    participant_role?: string;
    per_page?: number;
}

interface UseEventRegistrationsOptions {
    eventId: number | string;
    autoFetch?: boolean;
    filters?: RegistrationFilters;
}

const defaultFilters: RegistrationFilters = {};

export const useEventRegistrations = ({ eventId, autoFetch = true, filters = defaultFilters }: UseEventRegistrationsOptions) => {
    // Stabilize filters to avoid infinite loop
    const stableFilters = useMemo(() => filters, [
        filters?.status,
        filters?.ticket_id,
        filters?.search,
        filters?.participant_role,
        filters?.per_page,
    ]);
    const [registrations, setRegistrations] = useState<EventRegistration[]>([]);
    const [stats, setStats] = useState<RegistrationStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 20,
        total: 0,
    });

    const fetchRegistrations = useCallback(async (page = 1) => {
        try {
            setLoading(true);
            const response = await axios.get(`/api/events/${eventId}/registrations`, {
                params: { ...stableFilters, page },
            });
            setRegistrations(response.data.data || []);
            setStats(response.data.stats || null);
            setPagination({
                currentPage: response.data.meta?.current_page || 1,
                lastPage: response.data.meta?.last_page || 1,
                perPage: response.data.meta?.per_page || 20,
                total: response.data.meta?.total || 0,
            });
            setError(null);
        } catch (err) {
            setError('Erreur lors du chargement des inscriptions');
            apiLogger.error('Error fetching registrations', err);
        } finally {
            setLoading(false);
        }
    }, [eventId, stableFilters]);

    const register = useCallback(async (data: RegistrationFormData) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/registrations`, data);
            const newRegistration = response.data.data;
            setRegistrations(prev => [newRegistration, ...prev]);
            return newRegistration;
        } catch (err: any) {
            apiLogger.error('Error registering', err);
            throw err.response?.data?.error || 'Erreur lors de l\'inscription';
        }
    }, [eventId]);

    const confirmRegistration = useCallback(async (registrationId: number) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/registrations/${registrationId}/confirm`);
            const updated = response.data.data;
            setRegistrations(prev => prev.map(r => r.id === registrationId ? updated : r));
            return updated;
        } catch (err) {
            apiLogger.error('Error confirming registration', err);
            throw err;
        }
    }, [eventId]);

    const cancelRegistration = useCallback(async (registrationId: number, reason?: string, refund = false) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/registrations/${registrationId}/cancel`, {
                reason,
                refund,
            });
            const updated = response.data.data;
            setRegistrations(prev => prev.map(r => r.id === registrationId ? updated : r));
            return updated;
        } catch (err) {
            apiLogger.error('Error canceling registration', err);
            throw err;
        }
    }, [eventId]);

    const moveToWaitlist = useCallback(async (registrationId: number) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/registrations/${registrationId}/waitlist`);
            const updated = response.data.data;
            setRegistrations(prev => prev.map(r => r.id === registrationId ? updated : r));
            return updated;
        } catch (err) {
            apiLogger.error('Error moving to waitlist', err);
            throw err;
        }
    }, [eventId]);

    const promoteFromWaitlist = useCallback(async (registrationId: number) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/registrations/${registrationId}/promote`);
            const updated = response.data.data;
            setRegistrations(prev => prev.map(r => r.id === registrationId ? updated : r));
            return updated;
        } catch (err) {
            apiLogger.error('Error promoting from waitlist', err);
            throw err;
        }
    }, [eventId]);

    const transferRegistration = useCallback(async (registrationId: number, newAttendeeData: Partial<RegistrationFormData>) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/registrations/${registrationId}/transfer`, newAttendeeData);
            const updated = response.data.data;
            setRegistrations(prev => prev.map(r => r.id === registrationId ? updated : r));
            return updated;
        } catch (err) {
            apiLogger.error('Error transferring registration', err);
            throw err;
        }
    }, [eventId]);

    const recordPayment = useCallback(async (registrationId: number, paymentData: {
        amount: number;
        payment_method: string;
        payment_provider?: string;
        transaction_id?: string;
        fee?: number;
        notes?: string;
    }) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/registrations/${registrationId}/payment`, paymentData);
            return response.data;
        } catch (err) {
            apiLogger.error('Error recording payment', err);
            throw err;
        }
    }, [eventId]);

    const bulkConfirm = useCallback(async (registrationIds: number[]) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/registrations/bulk-confirm`, {
                registration_ids: registrationIds,
            });
            await fetchRegistrations();
            return response.data;
        } catch (err) {
            apiLogger.error('Error bulk confirming', err);
            throw err;
        }
    }, [eventId, fetchRegistrations]);

    const bulkCancel = useCallback(async (registrationIds: number[]) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/registrations/bulk-cancel`, {
                registration_ids: registrationIds,
            });
            await fetchRegistrations();
            return response.data;
        } catch (err) {
            apiLogger.error('Error bulk canceling', err);
            throw err;
        }
    }, [eventId, fetchRegistrations]);

    const exportRegistrations = useCallback(async (statusFilter?: RegistrationStatus | RegistrationStatus[]) => {
        try {
            const response = await axios.get(`/api/events/${eventId}/registrations/export`, {
                params: { status: statusFilter },
            });
            return response.data;
        } catch (err) {
            apiLogger.error('Error exporting registrations', err);
            throw err;
        }
    }, [eventId]);

    const fetchStats = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/registrations/stats`);
            setStats(response.data.data);
            return response.data.data;
        } catch (err) {
            apiLogger.error('Error fetching stats', err);
            throw err;
        }
    }, [eventId]);

    useEffect(() => {
        if (autoFetch) {
            fetchRegistrations();
        }
    }, [autoFetch, fetchRegistrations]);

    return {
        registrations,
        stats,
        loading,
        error,
        pagination,
        fetchRegistrations,
        register,
        confirmRegistration,
        cancelRegistration,
        moveToWaitlist,
        promoteFromWaitlist,
        transferRegistration,
        recordPayment,
        bulkConfirm,
        bulkCancel,
        exportRegistrations,
        fetchStats,
        refetch: fetchRegistrations,
    };
};

// Hook for user's own registrations
export const useMyRegistrations = () => {
    const [registrations, setRegistrations] = useState<EventRegistration[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const fetchMyRegistrations = useCallback(async () => {
        try {
            setLoading(true);
            const response = await axios.get('/api/my-registrations');
            setRegistrations(response.data.data || []);
            setError(null);
        } catch (err) {
            setError('Erreur lors du chargement de vos inscriptions');
            apiLogger.error('Error fetching my registrations', err);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchMyRegistrations();
    }, [fetchMyRegistrations]);

    return {
        registrations,
        loading,
        error,
        refetch: fetchMyRegistrations,
    };
};
