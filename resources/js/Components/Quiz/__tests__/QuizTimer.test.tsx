import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import QuizTimer from '../QuizTimer';

describe('QuizTimer', () => {
    let onTimeUpMock: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        vi.useFakeTimers();
        onTimeUpMock = vi.fn();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.useRealTimers();
    });

    describe('Time Display', () => {
        it('should display remaining time correctly', () => {
            // Start time is 1 minute ago, duration is 10 minutes
            const startedAt = new Date(Date.now() - 60 * 1000).toISOString();

            render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Should show 9:00 remaining (10 minutes - 1 minute elapsed)
            expect(screen.getByText(/9:00/)).toBeInTheDocument();
            expect(screen.getByText('Temps restant')).toBeInTheDocument();
        });

        it('should format time with hours when duration exceeds 60 minutes', () => {
            // Start time is 5 minutes ago, duration is 120 minutes (2 hours)
            const startedAt = new Date(Date.now() - 5 * 60 * 1000).toISOString();

            render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={120}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Should show 1:55:00 (2 hours - 5 minutes = 115 minutes = 1h 55m)
            expect(screen.getByText(/1:55:00/)).toBeInTheDocument();
        });

        it('should update time every second', async () => {
            const startedAt = new Date(Date.now() - 58 * 1000).toISOString();

            render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Initial: 9:02 (10 minutes - 58 seconds = 542 seconds = 9:02)
            expect(screen.getByText(/9:02/)).toBeInTheDocument();

            // Advance 1 second and trigger React updates
            await vi.advanceTimersByTimeAsync(1000);

            expect(screen.getByText(/9:01/)).toBeInTheDocument();

            // Advance 1 more second
            await vi.advanceTimersByTimeAsync(1000);

            expect(screen.getByText(/9:00/)).toBeInTheDocument();
        });
    });

    describe('Color Changes', () => {
        it('should display normal blue color when time > 5 minutes', () => {
            const startedAt = new Date(Date.now() - 1000).toISOString();

            const { container } = render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Check for blue background class
            const timerDiv = container.querySelector('div[class*="bg-blue"]');
            expect(timerDiv).toBeInTheDocument();
        });

        it('should display orange color when time <= 5 minutes', () => {
            // Start 5 minutes and 30 seconds ago, duration 10 minutes = 4:30 remaining
            const startedAt = new Date(Date.now() - 5.5 * 60 * 1000).toISOString();

            const { container } = render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Check for orange background and warning message
            const timerDiv = container.querySelector('div[class*="bg-orange"]');
            expect(timerDiv).toBeInTheDocument();
            expect(screen.getByText('Dépêchez-vous!')).toBeInTheDocument();
        });

        it('should display red color and pulse animation when time <= 1 minute', () => {
            // Start 9 minutes 30 seconds ago, duration 10 minutes = 30 seconds remaining
            const startedAt = new Date(Date.now() - 9.5 * 60 * 1000).toISOString();

            const { container } = render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Check for red background and pulse animation
            const timerDiv = container.querySelector('div[class*="bg-red"]');
            expect(timerDiv).toBeInTheDocument();

            const timeText = screen.getByText(/0:30/);
            expect(timeText).toHaveClass('animate-pulse');

            expect(screen.getByText('Soumission automatique imminente!')).toBeInTheDocument();
        });
    });

    describe('Icons', () => {
        it('should show Clock icon when time > 5 minutes', () => {
            const startedAt = new Date(Date.now() - 1000).toISOString();

            const { container } = render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Clock icon should be present
            const clockIcon = container.querySelector('svg');
            expect(clockIcon).toBeInTheDocument();
        });

        it('should show AlertTriangle icon when time <= 5 minutes', () => {
            const startedAt = new Date(Date.now() - 5.5 * 60 * 1000).toISOString();

            render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Should show warning message which indicates AlertTriangle is shown
            expect(screen.getByText('Dépêchez-vous!')).toBeInTheDocument();
        });
    });

    describe('Time Up Callback', () => {
        it('should call onTimeUp when time reaches 0', async () => {
            // Start 10 minutes ago, duration 10 minutes = 0 seconds remaining
            const startedAt = new Date(Date.now() - 10 * 60 * 1000).toISOString();

            render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Should show 0:00
            expect(screen.getByText(/0:00/)).toBeInTheDocument();

            // Advance timers to trigger the interval callback
            await vi.advanceTimersByTimeAsync(1000);

            // onTimeUp should be called
            expect(onTimeUpMock).toHaveBeenCalledTimes(1);
        });

        it('should call onTimeUp only once when time expires', async () => {
            // Start 9 minutes 59 seconds ago, duration 10 minutes = 1 second remaining
            const startedAt = new Date(Date.now() - (10 * 60 - 1) * 1000).toISOString();

            render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Should show 0:01
            expect(screen.getByText(/0:01/)).toBeInTheDocument();

            // Advance 1 second to trigger time up
            await vi.advanceTimersByTimeAsync(1000);

            expect(onTimeUpMock).toHaveBeenCalledTimes(1);

            // Advance more time, should not call again (interval is cleared)
            await vi.advanceTimersByTimeAsync(5000);

            expect(onTimeUpMock).toHaveBeenCalledTimes(1);
        });

        it('should not call onTimeUp if component unmounts before time expires', () => {
            const startedAt = new Date(Date.now() - 1000).toISOString();

            const { unmount } = render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Unmount before time expires
            unmount();

            // Advance time
            vi.advanceTimersByTime(10 * 60 * 1000);

            // Should not have called onTimeUp
            expect(onTimeUpMock).not.toHaveBeenCalled();
        });
    });

    describe('Edge Cases', () => {
        it('should handle time already expired when component mounts', () => {
            // Started 15 minutes ago with 10 minute duration
            const startedAt = new Date(Date.now() - 15 * 60 * 1000).toISOString();

            render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Should show 0:00
            expect(screen.getByText(/0:00/)).toBeInTheDocument();
        });

        it('should handle very short duration (1 minute)', () => {
            const startedAt = new Date(Date.now() - 30 * 1000).toISOString();

            render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={1}
                    onTimeUp={onTimeUpMock}
                />
            );

            // Should show 0:30 and be in urgent state
            expect(screen.getByText(/0:30/)).toBeInTheDocument();
            expect(screen.getByText('Soumission automatique imminente!')).toBeInTheDocument();
        });

        it('should apply custom className', () => {
            const startedAt = new Date(Date.now()).toISOString();

            const { container } = render(
                <QuizTimer
                    startedAt={startedAt}
                    durationMinutes={10}
                    onTimeUp={onTimeUpMock}
                    className="custom-class"
                />
            );

            const rootDiv = container.firstChild;
            expect(rootDiv).toHaveClass('custom-class');
        });
    });
});
