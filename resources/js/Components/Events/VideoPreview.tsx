import React, { useRef, useState, useCallback, useEffect } from 'react';
import { PlayIcon, VideoCameraIcon } from '@heroicons/react/24/outline';
import { EventMedia } from '@/Types/event.d';

interface VideoPreviewProps {
    media: EventMedia;
    className?: string;
    showPlayIcon?: boolean;
    onClick?: () => void;
}

export default function VideoPreview({
    media,
    className = '',
    showPlayIcon = true,
    onClick,
}: VideoPreviewProps) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const [isHovering, setIsHovering] = useState(false);
    const [isVideoLoaded, setIsVideoLoaded] = useState(false);
    const [isPlaying, setIsPlaying] = useState(false);
    const [hasError, setHasError] = useState(false);

    const getMediaUrl = useCallback(() => {
        return media.file_url || `/storage/${media.file_path}`;
    }, [media.file_url, media.file_path]);

    const getThumbnailUrl = useCallback(() => {
        if (media.thumbnail_url) return media.thumbnail_url;
        if (media.thumbnail_path) return `/storage/${media.thumbnail_path}`;
        return null;
    }, [media.thumbnail_url, media.thumbnail_path]);

    const playVideo = useCallback(() => {
        if (videoRef.current && !isPlaying) {
            const playPromise = videoRef.current.play();
            if (playPromise !== undefined) {
                playPromise
                    .then(() => {
                        setIsPlaying(true);
                    })
                    .catch((error) => {
                        console.warn('Video autoplay prevented:', error);
                    });
            }
        }
    }, [isPlaying]);

    const handleMouseEnter = useCallback(() => {
        setIsHovering(true);

        // If video is already loaded, play immediately
        if (isVideoLoaded && videoRef.current) {
            playVideo();
        }
    }, [isVideoLoaded, playVideo]);

    const handleMouseLeave = useCallback(() => {
        setIsHovering(false);
        setIsPlaying(false);
        if (videoRef.current) {
            videoRef.current.pause();
            videoRef.current.currentTime = 0;
        }
    }, []);

    const handleVideoCanPlay = useCallback(() => {
        setIsVideoLoaded(true);
        setHasError(false);
        // If still hovering when video becomes ready, play it
        if (isHovering) {
            playVideo();
        }
    }, [isHovering, playVideo]);

    const handleVideoError = useCallback(() => {
        setHasError(true);
        setIsVideoLoaded(false);
        setIsPlaying(false);
    }, []);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            if (videoRef.current) {
                videoRef.current.pause();
            }
        };
    }, []);

    const thumbnailUrl = getThumbnailUrl();

    return (
        <div
            className={`relative w-full h-full overflow-hidden ${className}`}
            onMouseEnter={handleMouseEnter}
            onMouseLeave={handleMouseLeave}
            onClick={onClick}
            data-testid="video-preview"
        >
            {/* Video Layer - Always rendered to show first frame as preview */}
            {!hasError && (
                <video
                    ref={videoRef}
                    src={getMediaUrl()}
                    muted
                    loop
                    playsInline
                    preload="metadata"
                    onCanPlay={handleVideoCanPlay}
                    onError={handleVideoError}
                    className="absolute inset-0 w-full h-full object-contain"
                    data-testid="video-element"
                />
            )}

            {/* Thumbnail overlay - shown while video loads or as fallback */}
            {thumbnailUrl && !isVideoLoaded && (
                <img
                    src={thumbnailUrl}
                    alt={media.title || media.file_name}
                    className="absolute inset-0 w-full h-full object-contain"
                    loading="lazy"
                    data-testid="video-thumbnail"
                />
            )}

            {/* Placeholder - only shown on error */}
            {hasError && (
                <div
                    className="absolute inset-0 w-full h-full flex items-center justify-center bg-gray-200 dark:bg-gray-700"
                    data-testid="video-placeholder"
                >
                    <VideoCameraIcon className="h-12 w-12 text-gray-400" />
                </div>
            )}

            {/* Play Icon Overlay - hidden when video is playing */}
            {showPlayIcon && !isPlaying && (
                <div
                    className="absolute inset-0 flex items-center justify-center pointer-events-none"
                    data-testid="play-icon-overlay"
                >
                    <div className="bg-black/50 rounded-full p-3 transition-transform group-hover:scale-110">
                        <PlayIcon className="h-8 w-8 text-white" />
                    </div>
                </div>
            )}

            {/* Duration Badge */}
            {media.duration_for_humans && (
                <div
                    className="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded"
                    data-testid="duration-badge"
                >
                    {media.duration_for_humans}
                </div>
            )}

            {/* Loading indicator while video loads on hover */}
            {isHovering && !isVideoLoaded && !hasError && (
                <div
                    className="absolute inset-0 flex items-center justify-center bg-black/20"
                    data-testid="loading-indicator"
                >
                    <div className="w-8 h-8 border-2 border-white border-t-transparent rounded-full animate-spin" />
                </div>
            )}
        </div>
    );
}
