import React, { useEffect, useRef } from 'react';
import videojs from 'video.js';
import 'video.js/dist/video-js.css';
import { logger } from '@/utils/logger';

interface VideoJSPlayerProps {
    src: string;
    poster?: string;
    onReady?: (player: any) => void;
}

const VideoJSPlayer: React.FC<VideoJSPlayerProps> = ({ src, poster, onReady }) => {
    const videoRef = useRef<HTMLDivElement>(null);
    const playerRef = useRef<any>(null);

    useEffect(() => {
        // Make sure Video.js player is only initialized once
        if (!playerRef.current) {
            const videoElement = document.createElement('video-js');

            videoElement.classList.add('vjs-big-play-centered');
            videoRef.current?.appendChild(videoElement);

            const player = playerRef.current = videojs(videoElement, {
                controls: true,
                autoplay: false,
                preload: 'metadata',
                fluid: true,
                responsive: true,
                playbackRates: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2],
                controlBar: {
                    volumePanel: {
                        inline: false
                    },
                    progressControl: {
                        seekBar: true
                    }
                },
                html5: {
                    nativeControlsForTouch: false
                }
            }, () => {
                logger.debug('Video.js player initialized');
                onReady && onReady(player);
            });

            // Set the source
            player.src({
                src: src,
                type: 'video/mp4'
            });

            if (poster) {
                player.poster(poster);
            }

            // Disable download
            player.el().querySelector('video')?.setAttribute('controlsList', 'nodownload');

            // Disable right click
            player.el().querySelector('video')?.addEventListener('contextmenu', (e: Event) => {
                e.preventDefault();
            });

        } else {
            const player = playerRef.current;
            player.src({
                src: src,
                type: 'video/mp4'
            });
        }
    }, [src, poster, onReady]);

    // Dispose the Video.js player when the component unmounts
    useEffect(() => {
        const player = playerRef.current;

        return () => {
            if (player && !player.isDisposed()) {
                player.dispose();
                playerRef.current = null;
            }
        };
    }, []);

    return (
        <div data-vjs-player>
            <div ref={videoRef} />
        </div>
    );
};

export default VideoJSPlayer;
