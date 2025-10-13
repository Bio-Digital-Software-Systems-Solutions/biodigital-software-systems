import { useEffect, useState, useCallback } from 'react';
import { Clock, AlertTriangle } from 'lucide-react';

interface QuizTimerProps {
    startedAt: string; // ISO string
    durationMinutes: number;
    onTimeUp: () => void;
    className?: string;
}

export default function QuizTimer({ startedAt, durationMinutes, onTimeUp, className = '' }: QuizTimerProps) {
    const [timeRemaining, setTimeRemaining] = useState<number>(0);
    const [hasWarned, setHasWarned] = useState(false);

    const calculateTimeRemaining = useCallback(() => {
        const start = new Date(startedAt).getTime();
        const now = Date.now();
        const elapsed = Math.floor((now - start) / 1000);
        const total = durationMinutes * 60;
        return Math.max(0, total - elapsed);
    }, [startedAt, durationMinutes]);

    useEffect(() => {
        // Initial calculation
        setTimeRemaining(calculateTimeRemaining());

        // Update every second
        const interval = setInterval(() => {
            const remaining = calculateTimeRemaining();
            setTimeRemaining(remaining);

            // Warning at 5 minutes
            if (remaining <= 300 && remaining > 299 && !hasWarned) {
                setHasWarned(true);
            }

            // Time's up!
            if (remaining <= 0) {
                clearInterval(interval);
                onTimeUp();
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [calculateTimeRemaining, onTimeUp, hasWarned]);

    const formatTime = (seconds: number): string => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    };

    const getColorClass = () => {
        if (timeRemaining <= 60) {
            return 'text-red-600 dark:text-red-400 animate-pulse';
        }
        if (timeRemaining <= 300) {
            return 'text-orange-600 dark:text-orange-400';
        }
        return 'text-gray-700 dark:text-gray-300';
    };

    const getBackgroundClass = () => {
        if (timeRemaining <= 60) {
            return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
        }
        if (timeRemaining <= 300) {
            return 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800';
        }
        return 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800';
    };

    return (
        <div className={`sticky top-0 z-10 ${className}`}>
            <div className={`flex items-center justify-center gap-3 px-4 py-3 border-2 rounded-lg ${getBackgroundClass()}`}>
                {timeRemaining <= 300 ? (
                    <AlertTriangle className={`h-5 w-5 ${getColorClass()}`} />
                ) : (
                    <Clock className={`h-5 w-5 ${getColorClass()}`} />
                )}
                <div className="flex flex-col items-center">
                    <span className="text-xs text-gray-600 dark:text-gray-400">Temps restant</span>
                    <span className={`text-2xl font-bold font-mono ${getColorClass()}`}>
                        {formatTime(timeRemaining)}
                    </span>
                </div>
                {timeRemaining <= 300 && timeRemaining > 0 && (
                    <div className="text-xs text-gray-600 dark:text-gray-400">
                        {timeRemaining <= 60 ? (
                            <span className="font-semibold">Soumission automatique imminente!</span>
                        ) : (
                            <span>Dépêchez-vous!</span>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
