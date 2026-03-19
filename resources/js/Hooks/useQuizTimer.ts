import { useEffect, useState, useCallback, useRef } from 'react';

export type TimerUrgency = 'normal' | 'warning' | 'critical';

interface UseQuizTimerOptions {
    startedAt: string;
    durationMinutes: number;
    onTimeUp: () => void;
}

interface UseQuizTimerReturn {
    timeRemaining: number;
    totalDuration: number;
    formatted: string;
    urgency: TimerUrgency;
    percentRemaining: number;
}

export function useQuizTimer({ startedAt, durationMinutes, onTimeUp }: UseQuizTimerOptions): UseQuizTimerReturn {
    const totalDuration = durationMinutes * 60;
    const onTimeUpRef = useRef(onTimeUp);
    onTimeUpRef.current = onTimeUp;

    const calculateTimeRemaining = useCallback(() => {
        const start = new Date(startedAt).getTime();
        const now = Date.now();
        const elapsed = Math.floor((now - start) / 1000);
        return Math.max(0, totalDuration - elapsed);
    }, [startedAt, totalDuration]);

    const [timeRemaining, setTimeRemaining] = useState<number>(calculateTimeRemaining);

    useEffect(() => {
        setTimeRemaining(calculateTimeRemaining());

        const interval = setInterval(() => {
            const remaining = calculateTimeRemaining();
            setTimeRemaining(remaining);

            if (remaining <= 0) {
                clearInterval(interval);
                onTimeUpRef.current();
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [calculateTimeRemaining]);

    const percentRemaining = totalDuration > 0 ? (timeRemaining / totalDuration) * 100 : 0;

    const urgency: TimerUrgency =
        timeRemaining <= 60 ? 'critical' :
        percentRemaining <= 25 ? 'warning' :
        'normal';

    const formatTime = (seconds: number): string => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    return {
        timeRemaining,
        totalDuration,
        formatted: formatTime(timeRemaining),
        urgency,
        percentRemaining,
    };
}
