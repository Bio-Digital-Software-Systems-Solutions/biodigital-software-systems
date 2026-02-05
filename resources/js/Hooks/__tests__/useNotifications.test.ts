import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { useNotifications } from '../useNotifications';
import axios from 'axios';

// Mock axios
vi.mock('axios');
const mockedAxios = vi.mocked(axios);

// Mock route function
global.route = vi.fn((name: string) => `/api/${name}`);

// Mock apiLogger
vi.mock('@/utils/logger', () => ({
    apiLogger: {
        error: vi.fn(),
        info: vi.fn(),
        warn: vi.fn(),
    },
}));

describe('useNotifications Hook', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    describe('Initial State', () => {
        it('starts with loading state', () => {
            mockedAxios.get.mockResolvedValue({
                data: { count: 0, chat_messages: 0, system_messages: 0 },
            });

            const { result } = renderHook(() => useNotifications());

            expect(result.current.isLoading).toBe(true);
            expect(result.current.notificationCount).toBe(0);
        });

        it('fetches notification count on mount', async () => {
            mockedAxios.get.mockResolvedValue({
                data: { count: 5, chat_messages: 3, system_messages: 2 },
            });

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            expect(result.current.notificationCount).toBe(5);
            expect(mockedAxios.get).toHaveBeenCalledWith('/api/notifications.unread-count');
        });
    });

    describe('Data Fetching', () => {
        it('updates notification count from API response', async () => {
            mockedAxios.get.mockResolvedValue({
                data: { count: 10, chat_messages: 7, system_messages: 3 },
            });

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.notificationCount).toBe(10);
            });
        });

        it('sets loading to false after successful fetch', async () => {
            mockedAxios.get.mockResolvedValue({
                data: { count: 3, chat_messages: 1, system_messages: 2 },
            });

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });
        });

        it('handles zero notifications', async () => {
            mockedAxios.get.mockResolvedValue({
                data: { count: 0, chat_messages: 0, system_messages: 0 },
            });

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.notificationCount).toBe(0);
                expect(result.current.isLoading).toBe(false);
            });
        });
    });

    describe('Error Handling', () => {
        it('handles API errors gracefully', async () => {
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            mockedAxios.get.mockRejectedValue(new Error('Network error'));

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            // Should maintain default count of 0
            expect(result.current.notificationCount).toBe(0);

            consoleSpy.mockRestore();
        });

        it('sets loading to false even on error', async () => {
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            mockedAxios.get.mockRejectedValue(new Error('API error'));

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            consoleSpy.mockRestore();
        });

        it('handles malformed API responses', async () => {
            mockedAxios.get.mockResolvedValue({
                data: { invalid: 'data' },
            });

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            // Should handle undefined count gracefully
            expect(result.current.notificationCount).toBeUndefined();
        });
    });

    describe('Polling', () => {
        it('polls for updates every 30 seconds', async () => {
            mockedAxios.get.mockResolvedValue({
                data: { count: 1, chat_messages: 1, system_messages: 0 },
            });

            renderHook(() => useNotifications());

            // Initial call
            expect(mockedAxios.get).toHaveBeenCalledTimes(1);

            // Fast-forward 30 seconds
            vi.advanceTimersByTime(30000);

            await waitFor(() => {
                expect(mockedAxios.get).toHaveBeenCalledTimes(2);
            });

            // Fast-forward another 30 seconds
            vi.advanceTimersByTime(30000);

            await waitFor(() => {
                expect(mockedAxios.get).toHaveBeenCalledTimes(3);
            });
        });

        it('cleans up interval on unmount', async () => {
            mockedAxios.get.mockResolvedValue({
                data: { count: 0, chat_messages: 0, system_messages: 0 },
            });

            const { unmount } = renderHook(() => useNotifications());

            expect(mockedAxios.get).toHaveBeenCalledTimes(1);

            unmount();

            // Fast-forward time after unmount
            vi.advanceTimersByTime(60000);

            // Should not make additional calls
            expect(mockedAxios.get).toHaveBeenCalledTimes(1);
        });

        it('updates count on each poll', async () => {
            let callCount = 0;
            mockedAxios.get.mockImplementation(() => {
                callCount++;
                return Promise.resolve({
                    data: {
                        count: callCount,
                        chat_messages: callCount,
                        system_messages: 0,
                    },
                });
            });

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.notificationCount).toBe(1);
            });

            vi.advanceTimersByTime(30000);

            await waitFor(() => {
                expect(result.current.notificationCount).toBe(2);
            });
        });
    });

    describe('Manual Refresh', () => {
        it('provides refreshCount function', () => {
            mockedAxios.get.mockResolvedValue({
                data: { count: 0, chat_messages: 0, system_messages: 0 },
            });

            const { result } = renderHook(() => useNotifications());

            expect(result.current.refreshCount).toBeDefined();
            expect(typeof result.current.refreshCount).toBe('function');
        });

        it('manual refresh updates count', async () => {
            let count = 0;
            mockedAxios.get.mockImplementation(() => {
                count++;
                return Promise.resolve({
                    data: { count, chat_messages: count, system_messages: 0 },
                });
            });

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.notificationCount).toBe(1);
            });

            // Manual refresh
            await result.current.refreshCount();

            await waitFor(() => {
                expect(result.current.notificationCount).toBe(2);
            });
        });

        it('manual refresh handles errors', async () => {
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

            mockedAxios.get
                .mockResolvedValueOnce({
                    data: { count: 5, chat_messages: 5, system_messages: 0 },
                })
                .mockRejectedValueOnce(new Error('Refresh error'));

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.notificationCount).toBe(5);
            });

            // This should not throw
            await result.current.refreshCount();

            // Count should remain unchanged
            expect(result.current.notificationCount).toBe(5);

            consoleSpy.mockRestore();
        });
    });

    describe('Performance', () => {
        it('does not create multiple intervals', async () => {
            mockedAxios.get.mockResolvedValue({
                data: { count: 0, chat_messages: 0, system_messages: 0 },
            });

            const { rerender } = renderHook(() => useNotifications());

            const initialCalls = mockedAxios.get.mock.calls.length;

            // Force re-render
            rerender();

            vi.advanceTimersByTime(30000);

            await waitFor(() => {
                // Should only have initial + 1 poll call
                expect(mockedAxios.get.mock.calls.length).toBeLessThanOrEqual(
                    initialCalls + 1
                );
            });
        });
    });

    describe('Security', () => {
        it('prevents XSS in API response', async () => {
            mockedAxios.get.mockResolvedValue({
                data: {
                    count: 5,
                    chat_messages: 3,
                    system_messages: 2,
                    // @ts-expect-error Testing malicious data
                    malicious: '<script>alert("xss")</script>',
                },
            });

            const { result } = renderHook(() => useNotifications());

            await waitFor(
                () => {
                    expect(result.current.notificationCount).toBe(5);
                },
                { timeout: 10000 }
            );

            // Hook should only use count, ignoring malicious fields
            expect(result.current).not.toHaveProperty('malicious');
        });
    });

    describe('Auth Error Handling', () => {
        it('silently ignores 401 errors', async () => {
            const { apiLogger } = await import('@/utils/logger');
            const error = {
                response: { status: 401 },
                isAxiosError: true,
            };
            mockedAxios.get.mockRejectedValue(error);

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            // Should not log 401 errors
            expect(apiLogger.error).not.toHaveBeenCalled();
        });

        it('silently ignores 403 errors', async () => {
            const { apiLogger } = await import('@/utils/logger');
            const error = {
                response: { status: 403 },
                isAxiosError: true,
            };
            mockedAxios.get.mockRejectedValue(error);

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            // Should not log 403 errors
            expect(apiLogger.error).not.toHaveBeenCalled();
        });

        it('logs other errors', async () => {
            const { apiLogger } = await import('@/utils/logger');
            const error = {
                response: { status: 500 },
                isAxiosError: true,
            };
            mockedAxios.get.mockRejectedValue(error);

            const { result } = renderHook(() => useNotifications());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            // Should log 500 errors
            expect(apiLogger.error).toHaveBeenCalledWith('Error fetching notification count', error);
        });
    });
});
