import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import VideoPreview from '../VideoPreview';
import { EventMedia } from '@/Types/event.d';

// Mock video element methods
const mockPlay = vi.fn().mockResolvedValue(undefined);
const mockPause = vi.fn();

beforeEach(() => {
    // Reset mocks
    mockPlay.mockClear();
    mockPause.mockClear();

    // Mock HTMLVideoElement prototype
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
    uuid: 'test-uuid',
    event_id: 1,
    title: 'Test Video',
    file_path: 'events/media/test.mp4',
    file_name: 'test.mp4',
    file_type: 'video/mp4',
    file_size: 1024000,
    media_type: 'video',
    collection: 'gallery',
    is_featured: false,
    sort_order: 0,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    ...overrides,
});

describe('VideoPreview Component', () => {
    describe('Rendering', () => {
        it('renders video preview container', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            expect(screen.getByTestId('video-preview')).toBeInTheDocument();
        });

        it('renders video element immediately to show first frame', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            expect(screen.getByTestId('video-element')).toBeInTheDocument();
        });

        it('does not show placeholder when video loads successfully', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            // Placeholder should not be visible when there's no error
            expect(screen.queryByTestId('video-placeholder')).not.toBeInTheDocument();
        });

        it('renders thumbnail as loading overlay when provided', () => {
            const media = createMockMedia({
                thumbnail_path: 'events/media/thumbnails/test_thumb.jpg',
            });
            render(<VideoPreview media={media} />);

            const thumbnail = screen.getByTestId('video-thumbnail');
            expect(thumbnail).toBeInTheDocument();
            expect(thumbnail).toHaveAttribute(
                'src',
                '/storage/events/media/thumbnails/test_thumb.jpg'
            );
        });

        it('renders thumbnail from thumbnail_url when provided', () => {
            const media = createMockMedia({
                thumbnail_url: 'https://example.com/thumb.jpg',
            });
            render(<VideoPreview media={media} />);

            const thumbnail = screen.getByTestId('video-thumbnail');
            expect(thumbnail).toHaveAttribute('src', 'https://example.com/thumb.jpg');
        });

        it('hides thumbnail after video loads', async () => {
            const media = createMockMedia({
                thumbnail_path: 'events/media/thumbnails/test_thumb.jpg',
            });
            render(<VideoPreview media={media} />);

            // Thumbnail visible initially
            expect(screen.getByTestId('video-thumbnail')).toBeInTheDocument();

            const video = screen.getByTestId('video-element');

            // Simulate video load
            await act(async () => {
                fireEvent.canPlay(video);
            });

            // Thumbnail should be hidden after video loads
            expect(screen.queryByTestId('video-thumbnail')).not.toBeInTheDocument();
        });

        it('renders play icon overlay by default', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            expect(screen.getByTestId('play-icon-overlay')).toBeInTheDocument();
        });

        it('hides play icon when showPlayIcon is false', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} showPlayIcon={false} />);

            expect(screen.queryByTestId('play-icon-overlay')).not.toBeInTheDocument();
        });

        it('renders duration badge when duration is provided', () => {
            const media = createMockMedia({
                duration_for_humans: '2:30',
            });
            render(<VideoPreview media={media} />);

            const badge = screen.getByTestId('duration-badge');
            expect(badge).toBeInTheDocument();
            expect(badge).toHaveTextContent('2:30');
        });

        it('does not render duration badge when duration is not provided', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            expect(screen.queryByTestId('duration-badge')).not.toBeInTheDocument();
        });

        it('applies custom className', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} className="custom-class" />);

            expect(screen.getByTestId('video-preview')).toHaveClass('custom-class');
        });

        it('uses alt text from title on thumbnail', () => {
            const media = createMockMedia({
                title: 'My Video Title',
                thumbnail_path: 'thumb.jpg',
            });
            render(<VideoPreview media={media} />);

            expect(screen.getByAltText('My Video Title')).toBeInTheDocument();
        });

        it('uses file_name as alt text fallback on thumbnail', () => {
            const media = createMockMedia({
                title: undefined,
                file_name: 'video_file.mp4',
                thumbnail_path: 'thumb.jpg',
            });
            render(<VideoPreview media={media} />);

            expect(screen.getByAltText('video_file.mp4')).toBeInTheDocument();
        });
    });

    describe('Hover Behavior', () => {
        it('video element is always present to show first frame', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            // Video element should be present immediately
            expect(screen.getByTestId('video-element')).toBeInTheDocument();
        });

        it('shows loading indicator on hover while video loads', async () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const container = screen.getByTestId('video-preview');

            await act(async () => {
                fireEvent.mouseEnter(container);
            });

            // Loading indicator should be visible before video loads
            expect(screen.getByTestId('loading-indicator')).toBeInTheDocument();
        });

        it('plays video when loaded and hovering', async () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const container = screen.getByTestId('video-preview');
            const video = screen.getByTestId('video-element');

            await act(async () => {
                fireEvent.mouseEnter(container);
            });

            // Simulate video can play
            await act(async () => {
                fireEvent.canPlay(video);
            });

            expect(mockPlay).toHaveBeenCalled();
        });

        it('pauses video and resets on mouse leave', async () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const container = screen.getByTestId('video-preview');
            const video = screen.getByTestId('video-element') as HTMLVideoElement;

            // Hover
            await act(async () => {
                fireEvent.mouseEnter(container);
            });

            // Simulate video can play
            await act(async () => {
                fireEvent.canPlay(video);
            });

            // Leave
            await act(async () => {
                fireEvent.mouseLeave(container);
            });

            expect(mockPause).toHaveBeenCalled();
        });

        it('hides play icon when video is playing', async () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const container = screen.getByTestId('video-preview');
            const video = screen.getByTestId('video-element');

            // Initially play icon is visible
            expect(screen.getByTestId('play-icon-overlay')).toBeInTheDocument();

            // Hover
            await act(async () => {
                fireEvent.mouseEnter(container);
            });

            // Simulate video can play and play succeeds
            await act(async () => {
                fireEvent.canPlay(video);
            });

            // Play icon should be hidden when isPlaying is true
            await waitFor(() => {
                expect(screen.queryByTestId('play-icon-overlay')).not.toBeInTheDocument();
            });
        });

        it('hides loading indicator after video loads', async () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const container = screen.getByTestId('video-preview');
            const video = screen.getByTestId('video-element');

            await act(async () => {
                fireEvent.mouseEnter(container);
            });

            expect(screen.getByTestId('loading-indicator')).toBeInTheDocument();

            await act(async () => {
                fireEvent.canPlay(video);
            });

            expect(screen.queryByTestId('loading-indicator')).not.toBeInTheDocument();
        });
    });

    describe('Video Element Configuration', () => {
        it('video is muted', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const video = screen.getByTestId('video-element');
            expect(video).toHaveAttribute('muted');
        });

        it('video loops', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const video = screen.getByTestId('video-element');
            expect(video).toHaveAttribute('loop');
        });

        it('video has playsInline attribute', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const video = screen.getByTestId('video-element');
            expect(video).toHaveAttribute('playsinline');
        });

        it('uses file_url when available', () => {
            const media = createMockMedia({
                file_url: 'https://cdn.example.com/video.mp4',
            });
            render(<VideoPreview media={media} />);

            const video = screen.getByTestId('video-element');
            expect(video).toHaveAttribute('src', 'https://cdn.example.com/video.mp4');
        });

        it('uses storage path when file_url not available', () => {
            const media = createMockMedia({
                file_path: 'events/media/1/video.mp4',
            });
            render(<VideoPreview media={media} />);

            const video = screen.getByTestId('video-element');
            expect(video).toHaveAttribute('src', '/storage/events/media/1/video.mp4');
        });
    });

    describe('Error Handling', () => {
        it('shows placeholder when video fails to load', async () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const video = screen.getByTestId('video-element');

            // Simulate error
            await act(async () => {
                fireEvent.error(video);
            });

            // Should show placeholder on error
            expect(screen.getByTestId('video-placeholder')).toBeInTheDocument();
            // Video element should be hidden
            expect(screen.queryByTestId('video-element')).not.toBeInTheDocument();
        });

        it('handles play rejection gracefully', async () => {
            mockPlay.mockRejectedValueOnce(new Error('Autoplay blocked'));

            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const container = screen.getByTestId('video-preview');
            const video = screen.getByTestId('video-element');

            await act(async () => {
                fireEvent.mouseEnter(container);
            });

            // This should not throw
            await act(async () => {
                fireEvent.canPlay(video);
            });

            // Component should still be rendered
            expect(screen.getByTestId('video-preview')).toBeInTheDocument();
        });
    });

    describe('Click Handling', () => {
        it('triggers onClick when clicked', async () => {
            const media = createMockMedia();
            const handleClick = vi.fn();
            render(<VideoPreview media={media} onClick={handleClick} />);

            const container = screen.getByTestId('video-preview');

            await userEvent.click(container);

            expect(handleClick).toHaveBeenCalledTimes(1);
        });

        it('works without onClick handler', async () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const container = screen.getByTestId('video-preview');

            // Should not throw
            await userEvent.click(container);
        });
    });

    describe('Accessibility', () => {
        it('has proper container structure', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const container = screen.getByTestId('video-preview');
            expect(container).toHaveClass('relative');
        });

        it('thumbnail has alt text', () => {
            const media = createMockMedia({
                title: 'Accessible Video',
                thumbnail_path: 'thumb.jpg',
            });
            render(<VideoPreview media={media} />);

            const img = screen.getByRole('img');
            expect(img).toHaveAttribute('alt', 'Accessible Video');
        });

        it('play icon overlay is not interactive', () => {
            const media = createMockMedia();
            render(<VideoPreview media={media} />);

            const overlay = screen.getByTestId('play-icon-overlay');
            expect(overlay).toHaveClass('pointer-events-none');
        });
    });
});
