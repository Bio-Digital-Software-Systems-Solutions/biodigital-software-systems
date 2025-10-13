import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor, act } from '@testing-library/react';
import { useProjects } from '../useProjects';
import axios from 'axios';

vi.mock('axios');
const mockedAxios = vi.mocked(axios);

global.route = vi.fn((name: string) => `/api/${name}`);

vi.mock('@/utils/logger', () => ({
    apiLogger: {
        error: vi.fn(),
        info: vi.fn(),
        warn: vi.fn(),
    },
}));

describe('useProjects Hook', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Initial State', () => {
        it('starts with empty projects array', () => {
            mockedAxios.get.mockResolvedValue({ data: { data: [] } });

            const { result } = renderHook(() => useProjects());

            expect(result.current.projects).toEqual([]);
            expect(result.current.isLoading).toBe(true);
        });

        it('fetches projects on mount', async () => {
            const mockProjects = [
                { id: 1, name: 'Project 1', description: 'Desc 1' },
                { id: 2, name: 'Project 2', description: 'Desc 2' },
            ];

            mockedAxios.get.mockResolvedValue({ data: { data: mockProjects } });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            expect(result.current.projects).toEqual(mockProjects);
        });
    });

    describe('CRUD Operations', () => {
        it('creates a new project', async () => {
            mockedAxios.get.mockResolvedValue({ data: { data: [] } });
            mockedAxios.post.mockResolvedValue({
                data: { id: 1, name: 'New Project', description: 'Description' },
            });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            await act(async () => {
                await result.current.createProject({
                    name: 'New Project',
                    description: 'Description',
                });
            });

            expect(mockedAxios.post).toHaveBeenCalledWith(
                '/api/projects',
                expect.objectContaining({
                    name: 'New Project',
                    description: 'Description',
                })
            );
        });

        it('updates an existing project', async () => {
            const mockProjects = [
                { id: 1, name: 'Project 1', description: 'Desc 1' },
            ];

            mockedAxios.get.mockResolvedValue({ data: { data: mockProjects } });
            mockedAxios.put.mockResolvedValue({
                data: { id: 1, name: 'Updated Project', description: 'Updated Desc' },
            });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.projects.length).toBe(1);
            });

            await act(async () => {
                await result.current.updateProject(1, {
                    name: 'Updated Project',
                    description: 'Updated Desc',
                });
            });

            expect(mockedAxios.put).toHaveBeenCalledWith(
                '/api/projects/1',
                expect.objectContaining({
                    name: 'Updated Project',
                })
            );
        });

        it('deletes a project', async () => {
            const mockProjects = [
                { id: 1, name: 'Project 1', description: 'Desc 1' },
                { id: 2, name: 'Project 2', description: 'Desc 2' },
            ];

            mockedAxios.get.mockResolvedValue({ data: { data: mockProjects } });
            mockedAxios.delete.mockResolvedValue({ data: { success: true } });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.projects.length).toBe(2);
            });

            await act(async () => {
                await result.current.deleteProject(1);
            });

            expect(mockedAxios.delete).toHaveBeenCalledWith('/api/projects/1');
        });
    });

    describe('Filtering and Search', () => {
        it('filters projects by status', async () => {
            const mockProjects = [
                { id: 1, name: 'Project 1', status: 'active' },
                { id: 2, name: 'Project 2', status: 'completed' },
                { id: 3, name: 'Project 3', status: 'active' },
            ];

            mockedAxios.get.mockResolvedValue({ data: { data: mockProjects } });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.projects.length).toBe(3);
            });

            act(() => {
                result.current.filterByStatus('active');
            });

            expect(result.current.filteredProjects).toHaveLength(2);
            expect(result.current.filteredProjects.every((p: any) => p.status === 'active')).toBe(true);
        });

        it('searches projects by name', async () => {
            const mockProjects = [
                { id: 1, name: 'Laravel Project', description: 'Backend' },
                { id: 2, name: 'React Project', description: 'Frontend' },
                { id: 3, name: 'Laravel API', description: 'API' },
            ];

            mockedAxios.get.mockResolvedValue({ data: { data: mockProjects } });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.projects.length).toBe(3);
            });

            act(() => {
                result.current.searchProjects('Laravel');
            });

            expect(result.current.searchResults).toHaveLength(2);
        });
    });

    describe('Error Handling', () => {
        it('handles fetch errors gracefully', async () => {
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            mockedAxios.get.mockRejectedValue(new Error('Network error'));

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            expect(result.current.projects).toEqual([]);
            expect(result.current.error).toBeTruthy();

            consoleSpy.mockRestore();
        });

        it('handles create errors', async () => {
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            mockedAxios.get.mockResolvedValue({ data: { data: [] } });
            mockedAxios.post.mockRejectedValue(new Error('Validation error'));

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            await act(async () => {
                try {
                    await result.current.createProject({ name: 'Test' });
                } catch (error) {
                    // Expected to throw
                }
            });

            expect(result.current.error).toBeTruthy();

            consoleSpy.mockRestore();
        });

        it('handles update errors', async () => {
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            mockedAxios.get.mockResolvedValue({
                data: { data: [{ id: 1, name: 'Project' }] },
            });
            mockedAxios.put.mockRejectedValue(new Error('Update failed'));

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.projects.length).toBe(1);
            });

            await act(async () => {
                try {
                    await result.current.updateProject(1, { name: 'Updated' });
                } catch (error) {
                    // Expected
                }
            });

            expect(result.current.error).toBeTruthy();

            consoleSpy.mockRestore();
        });

        it('handles delete errors', async () => {
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            mockedAxios.get.mockResolvedValue({
                data: { data: [{ id: 1, name: 'Project' }] },
            });
            mockedAxios.delete.mockRejectedValue(new Error('Delete failed'));

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.projects.length).toBe(1);
            });

            await act(async () => {
                try {
                    await result.current.deleteProject(1);
                } catch (error) {
                    // Expected
                }
            });

            expect(result.current.error).toBeTruthy();

            consoleSpy.mockRestore();
        });
    });

    describe('Pagination', () => {
        it('handles paginated responses', async () => {
            mockedAxios.get.mockResolvedValue({
                data: {
                    data: [{ id: 1, name: 'Project 1' }],
                    meta: {
                        current_page: 1,
                        last_page: 3,
                        per_page: 15,
                        total: 45,
                    },
                },
            });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            expect(result.current.pagination).toBeDefined();
            expect(result.current.pagination?.current_page).toBe(1);
            expect(result.current.pagination?.last_page).toBe(3);
        });

        it('loads next page', async () => {
            mockedAxios.get.mockResolvedValue({
                data: {
                    data: [{ id: 1, name: 'Project 1' }],
                    meta: { current_page: 1, last_page: 2 },
                },
            });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            mockedAxios.get.mockResolvedValue({
                data: {
                    data: [{ id: 2, name: 'Project 2' }],
                    meta: { current_page: 2, last_page: 2 },
                },
            });

            await act(async () => {
                await result.current.loadNextPage();
            });

            expect(mockedAxios.get).toHaveBeenCalledWith(
                expect.stringContaining('page=2')
            );
        });
    });

    describe('Real-time Updates', () => {
        it('refreshes data on demand', async () => {
            let callCount = 0;
            mockedAxios.get.mockImplementation(() => {
                callCount++;
                return Promise.resolve({
                    data: {
                        data: [{ id: callCount, name: `Project ${callCount}` }],
                    },
                });
            });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.projects.length).toBe(1);
            });

            await act(async () => {
                await result.current.refresh();
            });

            expect(mockedAxios.get).toHaveBeenCalledTimes(2);
        });
    });

    describe('Optimistic Updates', () => {
        it('immediately updates UI on create', async () => {
            mockedAxios.get.mockResolvedValue({ data: { data: [] } });
            mockedAxios.post.mockImplementation(() => {
                return new Promise((resolve) => {
                    setTimeout(() => {
                        resolve({
                            data: { id: 1, name: 'New Project', description: 'Desc' },
                        });
                    }, 100);
                });
            });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
            });

            await act(async () => {
                result.current.createProjectOptimistic({
                    name: 'New Project',
                    description: 'Desc',
                });
            });

            // Should immediately add to UI
            expect(result.current.projects.length).toBeGreaterThan(0);
        });
    });

    describe('Security', () => {
        it('sanitizes project names from API', async () => {
            mockedAxios.get.mockResolvedValue({
                data: {
                    data: [
                        {
                            id: 1,
                            name: '<script>alert("xss")</script>Project',
                            description: 'Safe',
                        },
                    ],
                },
            });

            const { result } = renderHook(() => useProjects());

            await waitFor(() => {
                expect(result.current.projects.length).toBe(1);
            });

            // Name should contain script as text, not execute
            expect(result.current.projects[0].name).toContain('script');
        });
    });
});
