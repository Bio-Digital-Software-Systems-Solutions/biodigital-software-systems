import { useState, useEffect } from 'react';
import axios from 'axios';
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
            apiLogger.error('Error fetching notification count', error);
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
