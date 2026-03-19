import { Clock, AlertTriangle } from 'lucide-react';
import { TimerUrgency } from '@/Hooks/useQuizTimer';

interface QuizTimerBadgeProps {
    formatted: string;
    urgency: TimerUrgency;
}

const urgencyStyles: Record<TimerUrgency, { container: string; text: string; icon: string }> = {
    normal: {
        container: 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700',
        text: 'text-blue-700 dark:text-blue-300',
        icon: 'text-blue-500 dark:text-blue-400',
    },
    warning: {
        container: 'bg-orange-50 dark:bg-orange-900/30 border-orange-200 dark:border-orange-700',
        text: 'text-orange-700 dark:text-orange-300',
        icon: 'text-orange-500 dark:text-orange-400',
    },
    critical: {
        container: 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700 animate-pulse',
        text: 'text-red-700 dark:text-red-300',
        icon: 'text-red-500 dark:text-red-400',
    },
};

export default function QuizTimerBadge({ formatted, urgency }: QuizTimerBadgeProps) {
    const styles = urgencyStyles[urgency];
    const Icon = urgency === 'normal' ? Clock : AlertTriangle;

    return (
        <div className={`flex items-center gap-1.5 px-3 py-1.5 border rounded-full ${styles.container}`}>
            <Icon className={`h-4 w-4 ${styles.icon}`} />
            <span className={`text-sm font-bold font-mono tabular-nums ${styles.text}`}>
                {formatted}
            </span>
        </div>
    );
}
