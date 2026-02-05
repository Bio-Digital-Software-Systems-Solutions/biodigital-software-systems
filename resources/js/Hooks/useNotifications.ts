import { useState, useEffect } from 'react';
import axios, { AxiosError } from 'axios';
import { apiLogger } from '@/utils/logger';

interface NotificationCount {
    count: number;
    chat_messages: number;
    system_messages: number;
}

export function useNotifications() {
    const [notificationCount, setNotificationCount] = useState<number>(0);
    const [isLoading, setIsLoading] = useState<boolean>(true);

    const fetchNotificationCount = async () => {
        try {
            const response = await axios.get<NotificationCount>(route('notifications.unread-count'));
            setNotificationCount(response.data.count);
        } catch (error) {
            // Silently ignore 401/403 errors (user not authenticated)
            const axiosError = error as AxiosError;
            if (axiosError.response?.status !== 401 && axiosError.response?.status !== 403) {
                apiLogger.error('Error fetching notification count', error);
            }
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        fetchNotificationCount();

        // Poll for updates every 30 seconds
        const interval = setInterval(fetchNotificationCount, 30000);

        return () => clearInterval(interval);
    }, []);

    return { notificationCount, isLoading, refreshCount: fetchNotificationCount };
}
