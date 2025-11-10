import { ClockIcon } from '@heroicons/react/24/outline';
import { Badge } from '@/Components/ui/badge';

interface TimeSlotsMinProps {
    selectedSlots?: string[];
    slotDuration: number;
    timeRange?: string;
    maxDisplay?: number;
    size?: 'sm' | 'xs';
}

export function TimeSlotsMini({
    selectedSlots = [],
    slotDuration,
    timeRange,
    maxDisplay = 3,
    size = 'xs'
}: TimeSlotsMinProps) {
    // If no slots selected, don't show anything or show a placeholder
    if (!selectedSlots || selectedSlots.length === 0) {
        return null;
    }

    // Sort slots chronologically before displaying
    const sortedSlots = [...selectedSlots].sort((a, b) => {
        // Convert HH:MM to minutes for comparison
        const timeA = a.split(':').reduce((acc, time, index) => acc + (index === 0 ? parseInt(time) * 60 : parseInt(time)), 0);
        const timeB = b.split(':').reduce((acc, time, index) => acc + (index === 0 ? parseInt(time) * 60 : parseInt(time)), 0);
        return timeA - timeB;
    });

    const displaySlots = sortedSlots.slice(0, maxDisplay);
    const remainingCount = sortedSlots.length - maxDisplay;

    const sizeClasses = {
        xs: 'text-xs px-1.5 py-0.5',
        sm: 'text-sm px-2 py-1'
    };

    return (
        <div className="flex items-center gap-1 flex-wrap">
            <ClockIcon className="h-3 w-3 text-gray-400 flex-shrink-0" />

            {displaySlots.map((slot, index) => (
                <Badge
                    key={index}
                    variant="secondary"
                    className={`${sizeClasses[size]} bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-mono`}
                >
                    {slot}
                </Badge>
            ))}

            {remainingCount > 0 && (
                <Badge
                    variant="secondary"
                    className={`${sizeClasses[size]} bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300`}
                >
                    +{remainingCount}
                </Badge>
            )}

            <span className="text-xs text-gray-500 ml-1">
                ({selectedSlots.length} créneaux)
            </span>
        </div>
    );
}