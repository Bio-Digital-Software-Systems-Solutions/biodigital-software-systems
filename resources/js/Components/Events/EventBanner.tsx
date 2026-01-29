import React from 'react';
import { PhotoIcon } from '@heroicons/react/24/outline';
import { EventMedia } from '@/Types/event.d';

interface EventBannerProps {
    banner?: EventMedia;
    eventTitle?: string;
    className?: string;
    aspectRatio?: 'video' | 'wide' | 'square';
}

export default function EventBanner({
    banner,
    eventTitle,
    className = '',
    aspectRatio = 'video',
}: EventBannerProps) {
    const aspectClasses = {
        video: 'aspect-video',
        wide: 'aspect-[21/9]',
        square: 'aspect-square',
    };

    const getMediaUrl = (item: EventMedia) => {
        return item.file_url || `/storage/${item.file_path}`;
    };

    if (!banner) {
        return (
            <div
                className={`${aspectClasses[aspectRatio]} w-full bg-gradient-to-br from-primary/20 to-primary/5 dark:from-primary/30 dark:to-primary/10 rounded-lg flex items-center justify-center ${className}`}
            >
                <div className="text-center text-gray-400 dark:text-gray-500">
                    <PhotoIcon className="h-12 w-12 mx-auto mb-2" />
                    <p className="text-sm">Aucun banner défini</p>
                </div>
            </div>
        );
    }

    return (
        <div
            className={`w-full rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center ${className}`}
        >
            <img
                src={getMediaUrl(banner)}
                alt={eventTitle || banner.title || banner.file_name}
                className="max-w-full max-h-[400px] w-auto h-auto object-contain"
            />
        </div>
    );
}
