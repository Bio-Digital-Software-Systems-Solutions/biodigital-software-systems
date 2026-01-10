import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import BurndownChart from '../BurndownChart';

// Mock recharts to avoid ResizeObserver issues
vi.mock('recharts', () => ({
    LineChart: ({ children }: any) => <div data-testid="line-chart">{children}</div>,
    Line: () => null,
    XAxis: () => null,
    YAxis: () => null,
    CartesianGrid: () => null,
    Tooltip: () => null,
    Legend: () => null,
    ResponsiveContainer: ({ children }: any) => <div data-testid="responsive-container">{children}</div>,
    ReferenceLine: () => null,
}));

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock CSRF token meta tag
const mockCsrfToken = 'test-csrf-token';
const originalQuerySelector = document.querySelector.bind(document);
vi.spyOn(document, 'querySelector').mockImplementation((selector: string) => {
    if (selector === 'meta[name="csrf-token"]') {
        return { content: mockCsrfToken } as HTMLMetaElement;
    }
    return originalQuerySelector(selector);
});

const mockBurndownData = {
    success: true,
    data: {
        chartData: [
            {
                date: '2024-01-01',
                dayNumber: 1,
                formattedDate: '01/01',
                ideal: 100,
                actual: 100,
                completed: 0,
                totalScope: 100,
            },
            {
                date: '2024-01-08',
                dayNumber: 8,
                formattedDate: '08/01',
                ideal: 50,
                actual: 60,
                completed: 40,
                totalScope: 100,
            },
            {
                date: '2024-01-14',
                dayNumber: 14,
                formattedDate: '14/01',
                ideal: 0,
                actual: null,
                completed: null,
                totalScope: 100,
            },
        ],
        summary: {
            totalStoryPoints: 100,
            completedPoints: 40,
            remainingPoints: 60,
            progressPercentage: 40,
            velocity: 5.71,
            daysElapsed: 7,
            totalDays: 14,
            estimatedCompletionDate: '2024-01-18',
            isOnTrack: false,
        },
        sprint: {
            id: 1,
            uuid: 'test-uuid-123',
            name: 'Sprint 1',
            startDate: '2024-01-01',
            endDate: '2024-01-14',
        },
    },
};

const mockOnTrackData = {
    success: true,
    data: {
        ...mockBurndownData.data,
        summary: {
            ...mockBurndownData.data.summary,
            isOnTrack: true,
            remainingPoints: 50,
            completedPoints: 50,
            estimatedCompletionDate: null,
        },
    },
};

describe('BurndownChart Component', () => {
    const testSprintUuid = 'test-sprint-uuid-123';

    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.resetAllMocks();
    });

    describe('Loading State', () => {
        it('shows loading spinner while fetching data', async () => {
            mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves

            render(<BurndownChart sprintUuid={testSprintUuid} />);

            // Check for spinner (the div with animate-spin class)
            const spinnerContainer = document.querySelector('.animate-spin');
            expect(spinnerContainer).toBeInTheDocument();
        });
    });

    describe('Error State', () => {
        it('shows error message when fetch fails', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText(/Network error/i)).toBeInTheDocument();
            });
        });

        it('shows error for non-ok response', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 500,
            });

            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText(/Failed to fetch burndown data/i)).toBeInTheDocument();
            });
        });

        it('shows error for invalid response format', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({ success: false }),
            });

            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText(/Invalid response format/i)).toBeInTheDocument();
            });
        });
    });

    describe('Data Display', () => {
        beforeEach(() => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });
        });

        it('displays total story points', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText('100')).toBeInTheDocument();
            });
            expect(screen.getByText('Total Points')).toBeInTheDocument();
        });

        it('displays completed points', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText('40')).toBeInTheDocument();
            });
        });

        it('displays remaining points', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText('60')).toBeInTheDocument();
            });
        });

        it('displays velocity', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText(/5\.71 pts\/j/)).toBeInTheDocument();
            });
        });

        it('displays day progress', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText(/Jour 7 sur 14/)).toBeInTheDocument();
            });
        });
    });

    describe('Sprint Status', () => {
        it('shows "potential delay" badge when not on track', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });

            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText(/Retard potentiel/i)).toBeInTheDocument();
            });
        });

        it('shows estimated completion date when behind schedule', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });

            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText(/Fin estimée/i)).toBeInTheDocument();
            });
        });

        it('shows "on track" badge when sprint is on track', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockOnTrackData),
            });

            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByText(/En bonne voie/i)).toBeInTheDocument();
            });
        });
    });

    describe('Chart Type Toggle', () => {
        beforeEach(() => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });
        });

        it('shows toggle buttons when chartType is "both"', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} chartType="both" />);

            await waitFor(() => {
                expect(screen.getByRole('button', { name: /burn-down/i })).toBeInTheDocument();
                expect(screen.getByRole('button', { name: /burn-up/i })).toBeInTheDocument();
            });
        });

        it('starts with burn-down view by default', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} chartType="both" />);

            await waitFor(() => {
                const burndownButton = screen.getByRole('button', { name: /burn-down/i });
                expect(burndownButton).toHaveClass('bg-primary');
            });
        });

        it('switches to burn-up view when clicking burn-up button', async () => {
            const user = userEvent.setup();
            render(<BurndownChart sprintUuid={testSprintUuid} chartType="both" />);

            await waitFor(() => {
                expect(screen.getByRole('button', { name: /burn-up/i })).toBeInTheDocument();
            });

            const burnupButton = screen.getByRole('button', { name: /burn-up/i });
            await user.click(burnupButton);

            expect(burnupButton).toHaveClass('bg-green-600');
        });

        it('does not show toggle when chartType is "burndown"', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} chartType="burndown" />);

            await waitFor(() => {
                expect(screen.getByText('Total Points')).toBeInTheDocument();
            });

            expect(screen.queryByRole('button', { name: /burn-up/i })).not.toBeInTheDocument();
        });
    });

    describe('Chart Legend', () => {
        beforeEach(() => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });
        });

        it('shows burn-down legend when in burn-down mode', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} chartType="burndown" />);

            await waitFor(() => {
                expect(screen.getByText(/Ligne idéale/i)).toBeInTheDocument();
                expect(screen.getByText(/Ligne réelle/i)).toBeInTheDocument();
            });
        });

        it('shows burn-up legend when in burn-up mode', async () => {
            const user = userEvent.setup();
            render(<BurndownChart sprintUuid={testSprintUuid} chartType="both" />);

            await waitFor(() => {
                expect(screen.getByRole('button', { name: /burn-up/i })).toBeInTheDocument();
            });

            await user.click(screen.getByRole('button', { name: /burn-up/i }));

            await waitFor(() => {
                expect(screen.getByText(/Objectif:/i)).toBeInTheDocument();
                expect(screen.getByText(/Complété:/i)).toBeInTheDocument();
            });
        });
    });

    describe('API Interaction', () => {
        it('fetches data with correct sprint UUID', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });

            const customUuid = 'custom-sprint-uuid-456';
            render(<BurndownChart sprintUuid={customUuid} />);

            await waitFor(() => {
                // Verify the URL contains the correct sprint UUID
                expect(mockFetch).toHaveBeenCalled();
                const callArgs = mockFetch.mock.calls[0];
                expect(callArgs[0]).toBe(`/api/sprints/${customUuid}/burndown`);
                expect(callArgs[1]).toMatchObject({
                    headers: expect.objectContaining({
                        'Accept': 'application/json',
                    }),
                    credentials: 'include',
                });
            });
        });

        it('refetches data when sprintUuid changes', async () => {
            mockFetch.mockResolvedValue({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });

            const { rerender } = render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(mockFetch).toHaveBeenCalledTimes(1);
            });

            // Clear the mock to track only the new calls
            mockFetch.mockClear();

            const newUuid = 'new-sprint-uuid-789';
            rerender(<BurndownChart sprintUuid={newUuid} />);

            await waitFor(() => {
                expect(mockFetch).toHaveBeenCalled();
                const callArgs = mockFetch.mock.calls[0];
                expect(callArgs[0]).toBe(`/api/sprints/${newUuid}/burndown`);
            });
        });

        it('does not fetch when sprintUuid is empty', async () => {
            mockFetch.mockResolvedValue({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });

            render(<BurndownChart sprintUuid="" />);

            // Wait a bit to ensure no fetch was made
            await new Promise(resolve => setTimeout(resolve, 100));

            expect(mockFetch).not.toHaveBeenCalled();
        });
    });

    describe('Accessibility', () => {
        beforeEach(() => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });
        });

        it('toggle buttons have correct type attribute', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} chartType="both" />);

            await waitFor(() => {
                const burndownButton = screen.getByRole('button', { name: /burn-down/i });
                const burnupButton = screen.getByRole('button', { name: /burn-up/i });

                expect(burndownButton).toHaveAttribute('type', 'button');
                expect(burnupButton).toHaveAttribute('type', 'button');
            });
        });

        it('status badges are visible and readable', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                const badge = screen.getByText(/Retard potentiel/i);
                expect(badge).toBeVisible();
            });
        });
    });

    describe('Chart Rendering', () => {
        beforeEach(() => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(mockBurndownData),
            });
        });

        it('renders the chart container', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByTestId('responsive-container')).toBeInTheDocument();
            });
        });

        it('renders the line chart', async () => {
            render(<BurndownChart sprintUuid={testSprintUuid} />);

            await waitFor(() => {
                expect(screen.getByTestId('line-chart')).toBeInTheDocument();
            });
        });
    });
});
