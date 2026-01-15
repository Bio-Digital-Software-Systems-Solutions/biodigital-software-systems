import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { EventTicket, TicketStats, TicketFormData, PriceCalculation } from '@/Types/event';
import { apiLogger } from '@/utils/logger';

interface UseEventTicketsOptions {
    eventId: number | string;
    autoFetch?: boolean;
}

export const useEventTickets = ({ eventId, autoFetch = true }: UseEventTicketsOptions) => {
    const [tickets, setTickets] = useState<EventTicket[]>([]);
    const [stats, setStats] = useState<TicketStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const fetchTickets = useCallback(async () => {
        try {
            setLoading(true);
            const response = await axios.get(`/api/events/${eventId}/tickets`);
            setTickets(response.data.data || []);
            setStats(response.data.stats || null);
            setError(null);
        } catch (err) {
            setError('Erreur lors du chargement des billets');
            apiLogger.error('Error fetching tickets', err);
        } finally {
            setLoading(false);
        }
    }, [eventId]);

    const fetchAvailableTickets = useCallback(async () => {
        try {
            const response = await axios.get(`/api/events/${eventId}/tickets/available`);
            return response.data.data || [];
        } catch (err) {
            apiLogger.error('Error fetching available tickets', err);
            throw err;
        }
    }, [eventId]);

    const createTicket = useCallback(async (data: TicketFormData) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/tickets`, data);
            const newTicket = response.data.data;
            setTickets(prev => [...prev, newTicket]);
            return newTicket;
        } catch (err) {
            apiLogger.error('Error creating ticket', err);
            throw err;
        }
    }, [eventId]);

    const updateTicket = useCallback(async (ticketId: number, data: Partial<TicketFormData>) => {
        try {
            const response = await axios.patch(`/api/events/${eventId}/tickets/${ticketId}`, data);
            const updatedTicket = response.data.data;
            setTickets(prev => prev.map(t => t.id === ticketId ? updatedTicket : t));
            return updatedTicket;
        } catch (err) {
            apiLogger.error('Error updating ticket', err);
            throw err;
        }
    }, [eventId]);

    const deleteTicket = useCallback(async (ticketId: number) => {
        try {
            await axios.delete(`/api/events/${eventId}/tickets/${ticketId}`);
            setTickets(prev => prev.filter(t => t.id !== ticketId));
        } catch (err) {
            apiLogger.error('Error deleting ticket', err);
            throw err;
        }
    }, [eventId]);

    const checkAvailability = useCallback(async (ticketId: number, quantity: number) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/tickets/${ticketId}/availability`, { quantity });
            return response.data;
        } catch (err) {
            apiLogger.error('Error checking availability', err);
            throw err;
        }
    }, [eventId]);

    const calculatePrice = useCallback(async (ticketId: number, quantity: number, promoCode?: string): Promise<PriceCalculation> => {
        try {
            const response = await axios.post(`/api/events/${eventId}/tickets/${ticketId}/price`, {
                quantity,
                promo_code: promoCode,
            });
            return response.data;
        } catch (err) {
            apiLogger.error('Error calculating price', err);
            throw err;
        }
    }, [eventId]);

    const validatePromoCode = useCallback(async (code: string) => {
        try {
            const response = await axios.post(`/api/events/${eventId}/promo-code/validate`, { code });
            return response.data;
        } catch (err) {
            apiLogger.error('Error validating promo code', err);
            throw err;
        }
    }, [eventId]);

    const reorderTickets = useCallback(async (order: number[]) => {
        try {
            await axios.post(`/api/events/${eventId}/tickets/reorder`, { order });
            await fetchTickets();
        } catch (err) {
            apiLogger.error('Error reordering tickets', err);
            throw err;
        }
    }, [eventId, fetchTickets]);

    useEffect(() => {
        if (autoFetch) {
            fetchTickets();
        }
    }, [autoFetch, fetchTickets]);

    return {
        tickets,
        stats,
        loading,
        error,
        fetchTickets,
        fetchAvailableTickets,
        createTicket,
        updateTicket,
        deleteTicket,
        checkAvailability,
        calculatePrice,
        validatePromoCode,
        reorderTickets,
        refetch: fetchTickets,
    };
};
