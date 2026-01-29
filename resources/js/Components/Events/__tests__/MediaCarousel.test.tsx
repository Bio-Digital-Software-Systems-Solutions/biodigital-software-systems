import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, act } from '@testing-library/react';
import MediaCarousel from '../MediaCarousel';
import { EventMedia } from '@/Types/event.d';

// Mock video element methods
const mockPlay = vi.fn().mockResolvedValue(undefined);
const mockPause = vi.fn();

beforeEach(() => {
    mockPlay.mockClear();
    mockPause.mockClear();

    Object.defineProperty(HTMLMediaElement.prototype, 'play', {
        configurable: true,
        value: mockPlay,
    });
    Object.defineProperty(HTMLMediaElement.prototype, 'pause', {
        configurable: true,
        value: mockPause,
    });
});

const createMockMedia = (overrides: Partial<EventMedia> = {}): EventMedia => ({
    id: 1,
    uuid: 'test-uuid-1',
    event_id: 1,
    title: 'Test Media',
    file_path: 'events/media/test.jpg',
    file_name: 'test.jpg',
    file_type: 'image/jpeg',
    file_size: 1024000,
    media_type: 'image',
    collection: 'gallery',
    is_featured: false,
    sort_order: 0,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    ...overrides,
});

const createMockMediaList = (): EventMedia[] => [
    createMockMedia({ id: 1, uuid: 'uuid-1', title: 'Image 1', media_type: 'image' }),
    createMockMedia({
        id: 2,
        uuid: 'uuid-2',
        title: 'Video 1',
        media_type: 'video',
        file_path: 'events/media/video.mp4',
        file_type: 'video/mp4',
        duration_for_humans: '2:30',
    }),
    createMockMedia({ id: 3, uuid: 'uuid-3', title: 'Image 2', media_type: 'image' }),
];

describe('MediaCarousel Component', () => {
    describe('Rendering', () => {
        it('renders carousel container', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} />);

            expect(screen.getByTestId('media-carousel')).toBeInTheDocument();
        });

        it('returns null when media array is empty', () => {
            const { container } = render(<MediaCarousel media={[]} />);
            expect(container.firstChild).toBeNull();
        });

        it('renders all slides', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} />);

            expect(screen.getByTestId('carousel-slide-0')).toBeInTheDocument();
            expect(screen.getByTestId('carousel-slide-1')).toBeInTheDocument();
            expect(screen.getByTestId('carousel-slide-2')).toBeInTheDocument();
        });

        it('renders navigation arrows when multiple media', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} />);

            expect(screen.getByTestId('carousel-prev-btn')).toBeInTheDocument();
            expect(screen.getByTestId('carousel-next-btn')).toBeInTheDocument();
        });

        it('does not render navigation arrows for single media', () => {
            const media = [createMockMedia()];
            render(<MediaCarousel media={media} />);

            expect(screen.queryByTestId('carousel-prev-btn')).not.toBeInTheDocument();
            expect(screen.queryByTestId('carousel-next-btn')).not.toBeInTheDocument();
        });

        it('renders dot indicators for multiple media', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} />);

            expect(screen.getByTestId('carousel-dots')).toBeInTheDocument();
            expect(screen.getByTestId('carousel-dot-0')).toBeInTheDocument();
            expect(screen.getByTestId('carousel-dot-1')).toBeInTheDocument();
            expect(screen.getByTestId('carousel-dot-2')).toBeInTheDocument();
        });

        it('renders media counter', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} />);

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('1 / 3');
        });

        it('renders media type indicator', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} />);

            expect(screen.getByTestId('media-type-indicator')).toHaveTextContent('Photo');
        });

        it('applies custom className', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} className="custom-class" />);

            expect(screen.getByTestId('media-carousel')).toHaveClass('custom-class');
        });
    });

    describe('Navigation', () => {
        it('navigates to next slide on next button click', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('2 / 3');
        });

        it('navigates to previous slide on prev button click', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // First go to slide 2
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            // Then go back
            const prevBtn = screen.getByTestId('carousel-prev-btn');
            fireEvent.click(prevBtn);

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('1 / 3');
        });

        it('wraps around when navigating past last slide', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn); // 2
            fireEvent.click(nextBtn); // 3
            fireEvent.click(nextBtn); // wraps to 1

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('1 / 3');
        });

        it('wraps around when navigating before first slide', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            const prevBtn = screen.getByTestId('carousel-prev-btn');
            fireEvent.click(prevBtn); // wraps to last

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('3 / 3');
        });

        it('navigates to specific slide on dot click', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            const dot2 = screen.getByTestId('carousel-dot-2');
            fireEvent.click(dot2);

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('3 / 3');
        });

        it('calls onSlideChange when slide changes', () => {
            const media = createMockMediaList();
            const onSlideChange = vi.fn();
            render(<MediaCarousel media={media} autoPlay={false} onSlideChange={onSlideChange} />);

            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            expect(onSlideChange).toHaveBeenCalledWith(1);
        });
    });

    describe('Auto-play', () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('auto-advances slides when autoPlay is true', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={true} autoPlayInterval={5000} />);

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('1 / 3');

            act(() => {
                vi.advanceTimersByTime(5000);
            });

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('2 / 3');
        });

        it('does not auto-advance when autoPlay is false', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            act(() => {
                vi.advanceTimersByTime(10000);
            });

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('1 / 3');
        });

        it('does not auto-advance for single media', () => {
            const media = [createMockMedia()];
            render(<MediaCarousel media={media} autoPlay={true} autoPlayInterval={5000} />);

            act(() => {
                vi.advanceTimersByTime(10000);
            });

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('1 / 1');
        });
    });

    describe('Video Controls', () => {
        it('shows video controls when current slide is video', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide (index 1)
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            expect(screen.getByTestId('video-controls')).toBeInTheDocument();
            expect(screen.getByTestId('video-play-pause-btn')).toBeInTheDocument();
            expect(screen.getByTestId('video-mute-btn')).toBeInTheDocument();
        });

        it('does not show video controls for image slides', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // First slide is an image
            expect(screen.queryByTestId('video-controls')).not.toBeInTheDocument();
        });

        it('shows video type indicator for video slides', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            expect(screen.getByTestId('media-type-indicator')).toHaveTextContent('Vidéo');
        });

        it('shows duration badge for video with duration', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            expect(screen.getByTestId('duration-badge')).toHaveTextContent('2:30');
        });

        it('toggles mute state when mute button clicked', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            const muteBtn = screen.getByTestId('video-mute-btn');

            // Initially muted (SpeakerXMarkIcon visible)
            expect(muteBtn).toHaveAttribute('aria-label', 'Unmute video');

            // Click to unmute
            fireEvent.click(muteBtn);
            expect(muteBtn).toHaveAttribute('aria-label', 'Mute video');

            // Click to mute again
            fireEvent.click(muteBtn);
            expect(muteBtn).toHaveAttribute('aria-label', 'Unmute video');
        });

        it('toggles play/pause when play button clicked', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            const playPauseBtn = screen.getByTestId('video-play-pause-btn');

            // Initially playing (PauseIcon visible)
            expect(playPauseBtn).toHaveAttribute('aria-label', 'Pause video');

            // Click to pause
            fireEvent.click(playPauseBtn);
            expect(playPauseBtn).toHaveAttribute('aria-label', 'Play video');
            expect(mockPause).toHaveBeenCalled();
        });
    });

    describe('Video Playback', () => {
        it('renders video element for video media', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            expect(screen.getByTestId('carousel-video-1')).toBeInTheDocument();
        });

        it('video has muted attribute by default', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            const video = screen.getByTestId('carousel-video-1');
            expect(video).toHaveAttribute('muted');
        });

        it('video has loop attribute', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            const video = screen.getByTestId('carousel-video-1');
            expect(video).toHaveAttribute('loop');
        });

        it('video has playsInline attribute', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            const video = screen.getByTestId('carousel-video-1');
            expect(video).toHaveAttribute('playsinline');
        });
    });

    describe('Image Display', () => {
        it('renders image element for image media', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            expect(screen.getByTestId('carousel-image-0')).toBeInTheDocument();
        });

        it('uses file_url when available', () => {
            const media = [
                createMockMedia({
                    file_url: 'https://cdn.example.com/image.jpg',
                }),
            ];
            render(<MediaCarousel media={media} autoPlay={false} />);

            const image = screen.getByTestId('carousel-image-0');
            expect(image).toHaveAttribute('src', 'https://cdn.example.com/image.jpg');
        });

        it('uses storage path when file_url not available', () => {
            const media = [
                createMockMedia({
                    file_path: 'events/media/1/image.jpg',
                }),
            ];
            render(<MediaCarousel media={media} autoPlay={false} />);

            const image = screen.getByTestId('carousel-image-0');
            expect(image).toHaveAttribute('src', '/storage/events/media/1/image.jpg');
        });

        it('image has alt text from title', () => {
            const media = [
                createMockMedia({
                    title: 'My Image Title',
                }),
            ];
            render(<MediaCarousel media={media} autoPlay={false} />);

            const image = screen.getByTestId('carousel-image-0');
            expect(image).toHaveAttribute('alt', 'My Image Title');
        });

        it('image uses file_name as alt fallback', () => {
            const media = [
                createMockMedia({
                    title: undefined,
                    file_name: 'my_image.jpg',
                }),
            ];
            render(<MediaCarousel media={media} autoPlay={false} />);

            const image = screen.getByTestId('carousel-image-0');
            expect(image).toHaveAttribute('alt', 'my_image.jpg');
        });
    });

    describe('Click Handling', () => {
        it('calls onMediaClick when media is clicked', () => {
            const media = createMockMediaList();
            const onMediaClick = vi.fn();
            render(<MediaCarousel media={media} autoPlay={false} onMediaClick={onMediaClick} />);

            const image = screen.getByTestId('carousel-image-0');
            fireEvent.click(image);

            expect(onMediaClick).toHaveBeenCalledWith(media[0], 0);
        });

        it('calls onMediaClick with correct media and index on video click', () => {
            const media = createMockMediaList();
            const onMediaClick = vi.fn();
            render(<MediaCarousel media={media} autoPlay={false} onMediaClick={onMediaClick} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            const video = screen.getByTestId('carousel-video-1');
            fireEvent.click(video);

            expect(onMediaClick).toHaveBeenCalledWith(media[1], 1);
        });
    });

    describe('Slide Visibility', () => {
        it('only current slide has opacity-100', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            const slide0 = screen.getByTestId('carousel-slide-0');
            const slide1 = screen.getByTestId('carousel-slide-1');
            const slide2 = screen.getByTestId('carousel-slide-2');

            expect(slide0).toHaveClass('opacity-100');
            expect(slide1).toHaveClass('opacity-0');
            expect(slide2).toHaveClass('opacity-0');
        });

        it('updates visibility when navigating', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            const slide0 = screen.getByTestId('carousel-slide-0');
            const slide1 = screen.getByTestId('carousel-slide-1');

            expect(slide0).toHaveClass('opacity-0');
            expect(slide1).toHaveClass('opacity-100');
        });
    });

    describe('Accessibility', () => {
        it('navigation buttons have aria-labels', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            expect(screen.getByTestId('carousel-prev-btn')).toHaveAttribute(
                'aria-label',
                'Previous slide'
            );
            expect(screen.getByTestId('carousel-next-btn')).toHaveAttribute(
                'aria-label',
                'Next slide'
            );
        });

        it('dot indicators have aria-labels', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            expect(screen.getByTestId('carousel-dot-0')).toHaveAttribute(
                'aria-label',
                'Go to slide 1'
            );
            expect(screen.getByTestId('carousel-dot-1')).toHaveAttribute(
                'aria-label',
                'Go to slide 2'
            );
        });

        it('video control buttons have aria-labels', () => {
            const media = createMockMediaList();
            render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to video slide
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);

            expect(screen.getByTestId('video-play-pause-btn')).toHaveAttribute('aria-label');
            expect(screen.getByTestId('video-mute-btn')).toHaveAttribute('aria-label');
        });
    });

    describe('Media Array Changes', () => {
        it('resets to first slide when media array length changes', () => {
            const media = createMockMediaList();
            const { rerender } = render(<MediaCarousel media={media} autoPlay={false} />);

            // Navigate to slide 3
            const nextBtn = screen.getByTestId('carousel-next-btn');
            fireEvent.click(nextBtn);
            fireEvent.click(nextBtn);
            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('3 / 3');

            // Change media array to only 1 item (simulating filter change)
            const filteredMedia = [media[0]];
            rerender(<MediaCarousel media={filteredMedia} autoPlay={false} />);

            // Should reset to slide 1
            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('1 / 1');
        });

        it('handles empty media array gracefully after having content', () => {
            const media = createMockMediaList();
            const { rerender, container } = render(<MediaCarousel media={media} autoPlay={false} />);

            expect(screen.getByTestId('media-carousel')).toBeInTheDocument();

            // Change to empty array
            rerender(<MediaCarousel media={[]} autoPlay={false} />);

            // Should render nothing
            expect(container.querySelector('[data-testid="media-carousel"]')).not.toBeInTheDocument();
        });

        it('shows correct slide when media array grows', () => {
            const media = [createMockMedia({ id: 1, uuid: 'uuid-1' })];
            const { rerender } = render(<MediaCarousel media={media} autoPlay={false} />);

            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('1 / 1');

            // Add more media
            const expandedMedia = createMockMediaList();
            rerender(<MediaCarousel media={expandedMedia} autoPlay={false} />);

            // Should reset to first slide with new count
            expect(screen.getByTestId('carousel-counter')).toHaveTextContent('1 / 3');
        });
    });
});
