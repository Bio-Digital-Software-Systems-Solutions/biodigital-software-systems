import { useEffect, useRef } from 'react';

/**
 * useAnnouncer Hook
 *
 * Creates and manages an ARIA live region for screen reader announcements.
 * Useful for announcing dynamic content changes to screen reader users.
 *
 * @example
 * const announce = useAnnouncer();
 * announce('Data loaded successfully');
 */

export function useAnnouncer() {
    const announcerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        // Create announcer element if it doesn't exist
        if (!announcerRef.current) {
            const announcer = document.createElement('div');
            announcer.setAttribute('role', 'status');
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.className = 'sr-only';
            document.body.appendChild(announcer);
            announcerRef.current = announcer;
        }

        // Cleanup on unmount
        return () => {
            if (announcerRef.current && document.body.contains(announcerRef.current)) {
                document.body.removeChild(announcerRef.current);
            }
        };
    }, []);

    const announce = (message: string, priority: 'polite' | 'assertive' = 'polite') => {
        if (announcerRef.current) {
            announcerRef.current.setAttribute('aria-live', priority);
            // Clear then set message to ensure it's announced even if the same
            announcerRef.current.textContent = '';
            setTimeout(() => {
                if (announcerRef.current) {
                    announcerRef.current.textContent = message;
                }
            }, 100);
        }
    };

    return announce;
}
