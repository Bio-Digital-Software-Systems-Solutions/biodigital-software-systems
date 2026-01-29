import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    SpeakerWaveIcon,
    SpeakerXMarkIcon,
    PlayIcon,
    PauseIcon,
} from '@heroicons/react/24/outline';
import { EventMedia } from '@/Types/event.d';
import { cn } from '@/lib/utils';

interface MediaCarouselProps {
    media: EventMedia[];
    autoPlay?: boolean;
    autoPlayInterval?: number;
    className?: string;
    onSlideChange?: (index: number) => void;
    onMediaClick?: (media: EventMedia, index: number) => void;
}

export default function MediaCarousel({
    media,
    autoPlay = true,
    autoPlayInterval = 5000,
    className = '',
    onSlideChange,
    onMediaClick,
}: MediaCarouselProps) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [isAutoPlaying, setIsAutoPlaying] = useState(autoPlay);
    const [isMuted, setIsMuted] = useState(true);
    const [isVideoPlaying, setIsVideoPlaying] = useState(true);
    const videoRefs = useRef<Map<number, HTMLVideoElement>>(new Map());
    const autoPlayTimerRef = useRef<NodeJS.Timeout | null>(null);
    const previousMediaLengthRef = useRef(media.length);

    // Reset index when media array changes (e.g., when filtering)
    useEffect(() => {
        if (media.length !== previousMediaLengthRef.current) {
            setCurrentIndex(0);
            previousMediaLengthRef.current = media.length;
        }
    }, [media.length]);

    // Ensure currentIndex is always within bounds
    const safeCurrentIndex = media.length > 0 ? Math.min(currentIndex, media.length - 1) : 0;
    const currentMedia = media[safeCurrentIndex];
    const isCurrentVideo = currentMedia?.media_type === 'video';

    const getMediaUrl = useCallback((item: EventMedia) => {
        return item.file_url || `/storage/${item.file_path}`;
    }, []);

    const goToSlide = useCallback((index: number) => {
        const newIndex = ((index % media.length) + media.length) % media.length;
        setCurrentIndex(newIndex);
        onSlideChange?.(newIndex);
    }, [media.length, onSlideChange]);

    const nextSlide = useCallback(() => {
        goToSlide(safeCurrentIndex + 1);
    }, [safeCurrentIndex, goToSlide]);

    const prevSlide = useCallback(() => {
        goToSlide(safeCurrentIndex - 1);
    }, [safeCurrentIndex, goToSlide]);

    // Handle video playback when slide changes
    useEffect(() => {
        // Pause all videos except current
        videoRefs.current.forEach((video, index) => {
            if (index !== safeCurrentIndex) {
                video.pause();
                video.currentTime = 0;
            }
        });

        // Play current video if it's a video slide
        const currentVideo = videoRefs.current.get(safeCurrentIndex);
        if (currentVideo && isVideoPlaying) {
            currentVideo.play().catch(() => {
                // Autoplay was prevented, that's okay
            });
        }
    }, [safeCurrentIndex, isVideoPlaying]);

    // Handle mute state changes
    useEffect(() => {
        videoRefs.current.forEach((video) => {
            video.muted = isMuted;
        });
    }, [isMuted]);

    // Auto-play timer
    useEffect(() => {
        if (!isAutoPlaying || media.length <= 1) {
            if (autoPlayTimerRef.current) {
                clearInterval(autoPlayTimerRef.current);
            }
            return;
        }

        autoPlayTimerRef.current = setInterval(() => {
            nextSlide();
        }, autoPlayInterval);

        return () => {
            if (autoPlayTimerRef.current) {
                clearInterval(autoPlayTimerRef.current);
            }
        };
    }, [isAutoPlaying, autoPlayInterval, media.length, nextSlide]);

    // Pause auto-play on user interaction
    const handleUserInteraction = useCallback(() => {
        setIsAutoPlaying(false);
        // Resume after 10 seconds of inactivity
        setTimeout(() => {
            if (autoPlay) {
                setIsAutoPlaying(true);
            }
        }, 10000);
    }, [autoPlay]);

    const handlePrevClick = useCallback(() => {
        handleUserInteraction();
        prevSlide();
    }, [handleUserInteraction, prevSlide]);

    const handleNextClick = useCallback(() => {
        handleUserInteraction();
        nextSlide();
    }, [handleUserInteraction, nextSlide]);

    const handleDotClick = useCallback((index: number) => {
        handleUserInteraction();
        goToSlide(index);
    }, [handleUserInteraction, goToSlide]);

    const toggleMute = useCallback(() => {
        setIsMuted((prev) => !prev);
    }, []);

    const toggleVideoPlay = useCallback(() => {
        const currentVideo = videoRefs.current.get(safeCurrentIndex);
        if (currentVideo) {
            if (isVideoPlaying) {
                currentVideo.pause();
            } else {
                currentVideo.play().catch(() => {});
            }
            setIsVideoPlaying(!isVideoPlaying);
        }
    }, [safeCurrentIndex, isVideoPlaying]);

    const handleMediaClick = useCallback(() => {
        if (currentMedia) {
            onMediaClick?.(currentMedia, safeCurrentIndex);
        }
    }, [currentMedia, safeCurrentIndex, onMediaClick]);

    const setVideoRef = useCallback((index: number) => (el: HTMLVideoElement | null) => {
        if (el) {
            videoRefs.current.set(index, el);
        } else {
            videoRefs.current.delete(index);
        }
    }, []);

    if (media.length === 0) {
        return null;
    }

    return (
        <div
            className={cn('relative w-full overflow-hidden rounded-lg bg-gray-900', className)}
            data-testid="media-carousel"
        >
            {/* Slides Container */}
            <div className="relative aspect-video">
                {media.map((item, index) => (
                    <div
                        key={item.id}
                        className={cn(
                            'absolute inset-0 transition-opacity duration-500',
                            index === safeCurrentIndex ? 'opacity-100 z-10' : 'opacity-0 z-0'
                        )}
                        data-testid={`carousel-slide-${index}`}
                    >
                        {item.media_type === 'video' ? (
                            <video
                                ref={setVideoRef(index)}
                                src={getMediaUrl(item)}
                                className="w-full h-full object-contain cursor-pointer"
                                muted={isMuted}
                                loop
                                playsInline
                                preload="metadata"
                                onClick={handleMediaClick}
                                data-testid={`carousel-video-${index}`}
                            />
                        ) : (
                            <img
                                src={getMediaUrl(item)}
                                alt={item.title || item.file_name}
                                className="w-full h-full object-contain cursor-pointer"
                                onClick={handleMediaClick}
                                data-testid={`carousel-image-${index}`}
                            />
                        )}
                    </div>
                ))}
            </div>

            {/* Video Controls - Only show for video slides */}
            {isCurrentVideo && (
                <div
                    className="absolute bottom-16 left-4 z-20 flex items-center gap-2"
                    data-testid="video-controls"
                >
                    <button
                        type="button"
                        onClick={toggleVideoPlay}
                        className="bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-colors"
                        aria-label={isVideoPlaying ? 'Pause video' : 'Play video'}
                        data-testid="video-play-pause-btn"
                    >
                        {isVideoPlaying ? (
                            <PauseIcon className="h-5 w-5" />
                        ) : (
                            <PlayIcon className="h-5 w-5" />
                        )}
                    </button>
                    <button
                        type="button"
                        onClick={toggleMute}
                        className="bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-colors"
                        aria-label={isMuted ? 'Unmute video' : 'Mute video'}
                        data-testid="video-mute-btn"
                    >
                        {isMuted ? (
                            <SpeakerXMarkIcon className="h-5 w-5" />
                        ) : (
                            <SpeakerWaveIcon className="h-5 w-5" />
                        )}
                    </button>
                </div>
            )}

            {/* Navigation Arrows */}
            {media.length > 1 && (
                <>
                    <button
                        type="button"
                        onClick={handlePrevClick}
                        className="absolute left-2 top-1/2 -translate-y-1/2 z-20 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-colors"
                        aria-label="Previous slide"
                        data-testid="carousel-prev-btn"
                    >
                        <ChevronLeftIcon className="h-6 w-6" />
                    </button>
                    <button
                        type="button"
                        onClick={handleNextClick}
                        className="absolute right-2 top-1/2 -translate-y-1/2 z-20 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-colors"
                        aria-label="Next slide"
                        data-testid="carousel-next-btn"
                    >
                        <ChevronRightIcon className="h-6 w-6" />
                    </button>
                </>
            )}

            {/* Dot Indicators */}
            {media.length > 1 && (
                <div
                    className="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2"
                    data-testid="carousel-dots"
                >
                    {media.map((item, index) => (
                        <button
                            type="button"
                            key={item.id}
                            onClick={() => handleDotClick(index)}
                            className={cn(
                                'rounded-full transition-all duration-300',
                                index === safeCurrentIndex
                                    ? 'w-6 h-2 bg-white'
                                    : 'w-2 h-2 bg-white/50 hover:bg-white/75'
                            )}
                            aria-label={`Go to slide ${index + 1}`}
                            data-testid={`carousel-dot-${index}`}
                        />
                    ))}
                </div>
            )}

            {/* Media Counter */}
            <div
                className="absolute top-4 right-4 z-20 bg-black/50 text-white text-sm px-2 py-1 rounded"
                data-testid="carousel-counter"
            >
                {safeCurrentIndex + 1} / {media.length}
            </div>

            {/* Media Type Indicator */}
            {currentMedia && (
                <div
                    className="absolute top-4 left-4 z-20 bg-black/50 text-white text-xs px-2 py-1 rounded flex items-center gap-1"
                    data-testid="media-type-indicator"
                >
                    {isCurrentVideo ? (
                        <>
                            <span className="w-2 h-2 bg-red-500 rounded-full animate-pulse" />
                            Vidéo
                        </>
                    ) : (
                        'Photo'
                    )}
                </div>
            )}

            {/* Duration Badge for videos */}
            {isCurrentVideo && currentMedia.duration_for_humans && (
                <div
                    className="absolute bottom-16 right-4 z-20 bg-black/70 text-white text-xs px-2 py-1 rounded"
                    data-testid="duration-badge"
                >
                    {currentMedia.duration_for_humans}
                </div>
            )}
        </div>
    );
}
