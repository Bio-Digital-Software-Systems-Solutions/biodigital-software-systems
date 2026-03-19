import { Clock, AlertTriangle } from 'lucide-react';
import { TimerUrgency } from '@/Hooks/useQuizTimer';

interface QuizTimerProps {
    formatted: string;
    urgency: TimerUrgency;
    timeRemaining: number;
    className?: string;
}

const bgStyles: Record<TimerUrgency, string> = {
    normal: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
    warning: 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800',
    critical: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
};

const textStyles: Record<TimerUrgency, string> = {
    normal: 'text-gray-700 dark:text-gray-300',
    warning: 'text-orange-600 dark:text-orange-400',
    critical: 'text-red-600 dark:text-red-400 animate-pulse',
};

export default function QuizTimer({ formatted, urgency, timeRemaining, className = '' }: QuizTimerProps) {
    const Icon = urgency === 'normal' ? Clock : AlertTriangle;
    const colorClass = textStyles[urgency];
    const bgClass = bgStyles[urgency];

    return (
        <div className={`sticky top-0 z-10 ${className}`}>
            <div className={`flex items-center justify-center gap-3 px-4 py-3 border-2 rounded-lg ${bgClass}`}>
                <Icon className={`h-5 w-5 ${colorClass}`} />
                <div className="flex flex-col items-center">
                    <span className="text-xs text-gray-600 dark:text-gray-400">Temps restant</span>
                    <span className={`text-2xl font-bold font-mono tabular-nums ${colorClass}`}>
                        {formatted}
                    </span>
                </div>
                {urgency !== 'normal' && timeRemaining > 0 && (
                    <div className="text-xs text-gray-600 dark:text-gray-400">
                        {urgency === 'critical' ? (
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
