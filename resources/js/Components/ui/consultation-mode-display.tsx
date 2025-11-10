import { Badge } from '@/Components/ui/badge';
import {
    UserGroupIcon,
    VideoCameraIcon,
    ComputerDesktopIcon,
    LinkIcon,
    MapPinIcon
} from '@heroicons/react/24/outline';

interface ConsultationModeDisplayProps {
    mode: 'in_person' | 'online' | 'hybrid';
    meetingLink?: string;
    location?: string;
    room?: string;
    size?: 'sm' | 'md' | 'lg';
    showLink?: boolean;
    showLocation?: boolean;
}

export function ConsultationModeDisplay({
    mode,
    meetingLink,
    location,
    room,
    size = 'md',
    showLink = false,
    showLocation = false
}: ConsultationModeDisplayProps) {
    const modeConfig = {
        in_person: {
            label: 'En présentiel',
            icon: UserGroupIcon,
            color: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            description: 'Rendez-vous physique'
        },
        online: {
            label: 'En ligne',
            icon: VideoCameraIcon,
            color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            description: 'Visioconférence'
        },
        hybrid: {
            label: 'Hybride',
            icon: ComputerDesktopIcon,
            color: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            description: 'Présentiel ou en ligne'
        }
    };

    const config = modeConfig[mode];
    const Icon = config.icon;

    const sizeConfig = {
        sm: {
            badge: 'px-2 py-1 text-xs',
            icon: 'h-3 w-3',
            text: 'text-xs'
        },
        md: {
            badge: 'px-2.5 py-1.5 text-sm',
            icon: 'h-4 w-4',
            text: 'text-sm'
        },
        lg: {
            badge: 'px-3 py-2 text-base',
            icon: 'h-5 w-5',
            text: 'text-base'
        }
    };

    const sizes = sizeConfig[size];

    return (
        <div className="space-y-1">
            <Badge className={`${config.color} ${sizes.badge} flex items-center space-x-1.5 w-fit`}>
                <Icon className={sizes.icon} />
                <span>{config.label}</span>
            </Badge>

            {showLink && meetingLink && mode !== 'in_person' && (
                <div className="flex items-center space-x-1">
                    <LinkIcon className={`${sizes.icon} text-gray-500`} />
                    <a
                        href={meetingLink}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={`${sizes.text} text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 underline truncate max-w-48`}
                    >
                        Lien de réunion
                    </a>
                </div>
            )}

            {showLocation && (location || room) && mode !== 'online' && (
                <div className="flex items-center space-x-1">
                    <MapPinIcon className={`${sizes.icon} text-gray-500`} />
                    <span className={`${sizes.text} text-gray-600 dark:text-gray-400 truncate max-w-48`}>
                        {[location, room].filter(Boolean).join(' - ')}
                    </span>
                </div>
            )}
        </div>
    );
}