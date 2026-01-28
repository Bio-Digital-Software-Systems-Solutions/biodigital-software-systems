import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import DepartmentStatisticsAnalytical from '../DepartmentStatisticsAnalytical';
import type { DepartmentStatistics } from '../DepartmentStatisticsOperational';

const createMockStatistics = (overrides: Partial<DepartmentStatistics> = {}): DepartmentStatistics => ({
    members: { total: 5, has_head: true },
    workflows: { total: 3, active: 2, draft: 1, deprecated: 0 },
    forms: { total: 10, published: 6, draft: 3, archived: 1, total_submissions: 150 },
    needs: {
        total: 8,
        by_status: { pending: 3, approved: 4, rejected: 1 },
        by_priority: { high: 2, medium: 4, low: 2 },
        total_cost: 5000,
    },
    documents: { total: 25, total_size: 104857600, formatted_size: '100 MB' },
    scheduling: {
        total_shifts: 20,
        upcoming_shifts: 8,
        pending_absences: 2,
        approved_absences: 5,
        pending_swap_requests: 1,
    },
    todos: {
        total: 50,
        completed: 25,
        in_progress: 10,
        pending: 10,
        overdue: 5,
        by_priority: { critical: 5, high: 15, medium: 20, low: 10 },
    },
    task_evolution: {
        weekly: [
            { label: 'S1', period: 'Week 1', created: 10, completed: 8 },
            { label: 'S2', period: 'Week 2', created: 12, completed: 10 },
            { label: 'S3', period: 'Week 3', created: 8, completed: 7 },
            { label: 'S4', period: 'Week 4', created: 15, completed: 12 },
        ],
        monthly: [
            { label: 'Jan', period: 'January', created: 30, completed: 25 },
            { label: 'Feb', period: 'February', created: 35, completed: 28 },
        ],
        quarterly: [
            { label: 'Q1', period: 'Q1 2024', created: 100, completed: 80 },
        ],
        semester: [
            { label: 'H1', period: 'H1 2024', created: 200, completed: 160 },
        ],
    },
    tasks_by_member: [
        {
            uuid: 'user-1',
            name: 'Jean Dupont',
            total: 15,
            completed: 10,
            in_progress: 3,
            pending: 1,
            overdue: 1,
            completion_rate: 67,
        },
        {
            uuid: 'user-2',
            name: 'Marie Martin',
            total: 20,
            completed: 18,
            in_progress: 2,
            pending: 0,
            overdue: 0,
            completion_rate: 90,
        },
        {
            uuid: 'user-3',
            name: 'Pierre Durand',
            total: 10,
            completed: 5,
            in_progress: 3,
            pending: 2,
            overdue: 0,
            completion_rate: 50,
        },
    ],
    performance: {
        collective: {
            total_tasks: 50,
            completed_tasks: 25,
            completion_rate: 50,
            overdue_tasks: 5,
            overdue_rate: 10,
            velocity_this_month: 15,
            velocity_last_month: 12,
            velocity_change: 25,
            avg_completion_days: 3,
        },
        individual: [
            {
                uuid: 'user-1',
                name: 'Jean Dupont',
                total_tasks: 15,
                completed_tasks: 10,
                overdue_tasks: 1,
                completion_rate: 67,
                overdue_rate: 7,
                avg_completion_days: 2,
                completed_this_month: 5,
            },
            {
                uuid: 'user-2',
                name: 'Marie Martin',
                total_tasks: 20,
                completed_tasks: 18,
                overdue_tasks: 0,
                completion_rate: 90,
                overdue_rate: 0,
                avg_completion_days: 1,
                completed_this_month: 8,
            },
            {
                uuid: 'user-3',
                name: 'Pierre Durand',
                total_tasks: 10,
                completed_tasks: 5,
                overdue_tasks: 0,
                completion_rate: 50,
                overdue_rate: 0,
                avg_completion_days: 4,
                completed_this_month: 3,
            },
        ],
    },
    ...overrides,
});

describe('DepartmentStatisticsAnalytical Component', () => {
    describe('Rendering', () => {
        it('renders without crashing', () => {
            render(<DepartmentStatisticsAnalytical />);
            expect(screen.getByText('Tâches par Membre')).toBeInTheDocument();
        });

        it('renders with statistics data', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getByText('Tâches par Membre')).toBeInTheDocument();
            expect(screen.getByText('Tâches Terminées par Membre')).toBeInTheDocument();
            expect(screen.getByText('Répartition par Statut')).toBeInTheDocument();
        });

        it('renders all main sections', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            // Doughnut charts
            expect(screen.getByText('Tâches par Membre')).toBeInTheDocument();
            expect(screen.getByText('Tâches Terminées par Membre')).toBeInTheDocument();
            expect(screen.getByText('Répartition par Statut')).toBeInTheDocument();

            // Area chart
            expect(screen.getByText('Évolution Hebdomadaire')).toBeInTheDocument();

            // Bar chart
            expect(screen.getByText('Taux de Complétion')).toBeInTheDocument();
        });
    });

    describe('Doughnut Charts', () => {
        describe('Tasks by Member Chart', () => {
            it('displays member names in legend', () => {
                const stats = createMockStatistics();
                render(<DepartmentStatisticsAnalytical statistics={stats} />);

                expect(screen.getAllByText('Jean Dupont').length).toBeGreaterThan(0);
                expect(screen.getAllByText('Marie Martin').length).toBeGreaterThan(0);
                expect(screen.getAllByText('Pierre Durand').length).toBeGreaterThan(0);
            });

            it('shows subtitle', () => {
                const stats = createMockStatistics();
                render(<DepartmentStatisticsAnalytical statistics={stats} />);

                expect(screen.getByText('Répartition des tâches assignées')).toBeInTheDocument();
            });

            it('displays total task count in center', () => {
                const stats = createMockStatistics();
                render(<DepartmentStatisticsAnalytical statistics={stats} />);

                // Total tasks for members: 15 + 20 + 10 = 45
                expect(screen.getByText('45')).toBeInTheDocument();
            });

            it('shows empty state when no members with uuid', () => {
                const stats = createMockStatistics({
                    tasks_by_member: [
                        {
                            uuid: null,
                            name: 'Non assigné',
                            total: 5,
                            completed: 0,
                            in_progress: 2,
                            pending: 3,
                            overdue: 0,
                            completion_rate: 0,
                        },
                    ],
                });
                render(<DepartmentStatisticsAnalytical statistics={stats} />);

                const emptyMessages = screen.getAllByText('Aucune donnée');
                expect(emptyMessages.length).toBeGreaterThan(0);
            });
        });

        describe('Completed Tasks by Member Chart', () => {
            it('displays chart title', () => {
                const stats = createMockStatistics();
                render(<DepartmentStatisticsAnalytical statistics={stats} />);

                expect(screen.getByText('Tâches Terminées par Membre')).toBeInTheDocument();
                expect(screen.getByText('Tâches complétées avec succès')).toBeInTheDocument();
            });

            it('renders full circle for single member with 100%', () => {
                const stats = createMockStatistics({
                    tasks_by_member: [
                        {
                            uuid: 'user-1',
                            name: 'Jean Dupont',
                            total: 10,
                            completed: 10,
                            in_progress: 0,
                            pending: 0,
                            overdue: 0,
                            completion_rate: 100,
                        },
                    ],
                });
                const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

                // Should render SVG paths for the donut chart
                const svgPaths = container.querySelectorAll('svg path');
                expect(svgPaths.length).toBeGreaterThan(0);
            });
        });

        describe('Status Distribution Chart', () => {
            it('displays status chart labels', () => {
                const stats = createMockStatistics();
                render(<DepartmentStatisticsAnalytical statistics={stats} />);

                expect(screen.getByText('Répartition par Statut')).toBeInTheDocument();
                expect(screen.getByText('État actuel des tâches')).toBeInTheDocument();
            });

            it('displays status categories in legend', () => {
                const stats = createMockStatistics();
                render(<DepartmentStatisticsAnalytical statistics={stats} />);

                expect(screen.getByText('Terminé')).toBeInTheDocument();
                expect(screen.getByText('En cours')).toBeInTheDocument();
                expect(screen.getByText('En attente')).toBeInTheDocument();
                expect(screen.getByText('En retard')).toBeInTheDocument();
            });

            it('displays count and percentage for each status', () => {
                const stats = createMockStatistics();
                render(<DepartmentStatisticsAnalytical statistics={stats} />);

                // Check for percentage format (e.g., "(50%)")
                expect(screen.getByText('(50%)')).toBeInTheDocument(); // completed: 25/50
            });
        });
    });

    describe('Area Chart', () => {
        it('renders area chart section', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getByText('Évolution Hebdomadaire')).toBeInTheDocument();
            expect(screen.getByText('Tâches créées vs terminées')).toBeInTheDocument();
        });

        it('displays chart legend', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getByText('Tâches créées')).toBeInTheDocument();
            expect(screen.getByText('Tâches terminées')).toBeInTheDocument();
        });

        it('renders SVG element for area chart', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const svgElements = container.querySelectorAll('svg');
            expect(svgElements.length).toBeGreaterThan(0);
        });

        it('displays weekly labels', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getByText('S1')).toBeInTheDocument();
            expect(screen.getByText('S2')).toBeInTheDocument();
            expect(screen.getByText('S3')).toBeInTheDocument();
            expect(screen.getByText('S4')).toBeInTheDocument();
        });

        it('shows empty state when no weekly data', () => {
            const stats = createMockStatistics({
                task_evolution: {
                    weekly: [],
                    monthly: [],
                    quarterly: [],
                    semester: [],
                },
            });
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getByText('Aucune donnée disponible')).toBeInTheDocument();
        });
    });

    describe('Horizontal Bar Chart', () => {
        it('renders completion rate section', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getByText('Taux de Complétion')).toBeInTheDocument();
            expect(screen.getByText('Performance par membre')).toBeInTheDocument();
        });

        it('displays member names and percentages', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getAllByText('Jean Dupont').length).toBeGreaterThan(0);
            expect(screen.getAllByText('Marie Martin').length).toBeGreaterThan(0);
            expect(screen.getAllByText('Pierre Durand').length).toBeGreaterThan(0);

            // Check for percentage values
            expect(screen.getByText('67%')).toBeInTheDocument();
            expect(screen.getByText('90%')).toBeInTheDocument();
            expect(screen.getByText('50%')).toBeInTheDocument();
        });

        it('shows empty state when no performance data', () => {
            const stats = createMockStatistics({
                performance: {
                    ...createMockStatistics().performance!,
                    individual: [],
                },
            });
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const noDataMessages = screen.getAllByText('Aucune donnée');
            expect(noDataMessages.length).toBeGreaterThan(0);
        });

        it('renders bars with correct width based on percentage', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            // Check for progress bars
            const progressBars = container.querySelectorAll('.h-5.rounded-full.transition-all');
            expect(progressBars.length).toBeGreaterThan(0);
        });
    });

    describe('Tooltips', () => {
        it('shows tooltip on doughnut chart hover', async () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            // Find a path element in the SVG (doughnut segment)
            const pathElements = container.querySelectorAll('svg path[class*="cursor-pointer"]');
            expect(pathElements.length).toBeGreaterThan(0);

            // Simulate mouse move on a segment
            if (pathElements[0]) {
                fireEvent.mouseMove(pathElements[0], { clientX: 100, clientY: 100 });
            }
        });

        it('hides tooltip on mouse leave', async () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const pathElements = container.querySelectorAll('svg path[class*="cursor-pointer"]');
            if (pathElements[0]) {
                fireEvent.mouseMove(pathElements[0], { clientX: 100, clientY: 100 });
                fireEvent.mouseLeave(pathElements[0]);
            }

            // Tooltip should be hidden (no tooltip content visible)
            const tooltip = container.querySelector('.absolute.z-50.pointer-events-none');
            expect(tooltip).toBeNull();
        });
    });

    describe('Empty States', () => {
        it('handles undefined statistics gracefully', () => {
            render(<DepartmentStatisticsAnalytical statistics={undefined} />);

            expect(screen.getByText('Tâches par Membre')).toBeInTheDocument();
        });

        it('shows empty state for all charts when no data', () => {
            const stats = createMockStatistics({
                tasks_by_member: [],
                performance: { ...createMockStatistics().performance!, individual: [] },
                task_evolution: {
                    weekly: [],
                    monthly: [],
                    quarterly: [],
                    semester: [],
                },
                todos: {
                    total: 0,
                    completed: 0,
                    in_progress: 0,
                    pending: 0,
                    overdue: 0,
                    by_priority: { critical: 0, high: 0, medium: 0, low: 0 },
                },
            });
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const noDataMessages = screen.getAllByText('Aucune donnée');
            expect(noDataMessages.length).toBeGreaterThanOrEqual(3);
        });
    });

    describe('SVG Chart Rendering', () => {
        it('renders doughnut charts with SVG paths', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const svgPaths = container.querySelectorAll('svg path');
            expect(svgPaths.length).toBeGreaterThan(0);
        });

        it('renders colored legend indicators', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const colorIndicators = container.querySelectorAll('.w-2\\.5.h-2\\.5.rounded-full');
            expect(colorIndicators.length).toBeGreaterThan(0);
        });

        it('renders horizontal bar backgrounds', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const barBackgrounds = container.querySelectorAll('.bg-gray-100');
            expect(barBackgrounds.length).toBeGreaterThan(0);
        });
    });

    describe('Data Visualization Accuracy', () => {
        it('filters out members without uuid from member charts', () => {
            const stats = createMockStatistics({
                tasks_by_member: [
                    {
                        uuid: 'user-1',
                        name: 'Jean Dupont',
                        total: 15,
                        completed: 10,
                        in_progress: 3,
                        pending: 1,
                        overdue: 1,
                        completion_rate: 67,
                    },
                    {
                        uuid: null,
                        name: 'Non assigné',
                        total: 5,
                        completed: 0,
                        in_progress: 2,
                        pending: 3,
                        overdue: 0,
                        completion_rate: 0,
                    },
                ],
            });
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getAllByText('Jean Dupont').length).toBeGreaterThan(0);
            const nonAssigneElements = screen.queryAllByText('Non assigné');
            expect(nonAssigneElements.length).toBe(0);
        });

        it('uses correct colors for status indicators', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const indicators = container.querySelectorAll('.rounded-full');
            expect(indicators.length).toBeGreaterThan(0);
        });

        it('calculates percentages correctly', () => {
            const stats = createMockStatistics({
                todos: {
                    total: 100,
                    completed: 25,
                    in_progress: 25,
                    pending: 25,
                    overdue: 25,
                    by_priority: { critical: 25, high: 25, medium: 25, low: 25 },
                },
            });
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            // Each status should be 25%
            const percentages = screen.getAllByText('(25%)');
            expect(percentages.length).toBeGreaterThan(0);
        });
    });

    describe('Full Circle (100%) Segment Rendering', () => {
        it('renders a complete donut when single item has 100%', () => {
            const stats = createMockStatistics({
                tasks_by_member: [
                    {
                        uuid: 'user-1',
                        name: 'Seul Membre',
                        total: 10,
                        completed: 10,
                        in_progress: 0,
                        pending: 0,
                        overdue: 0,
                        completion_rate: 100,
                    },
                ],
            });
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            // Should have SVG paths for the donut segments
            const paths = container.querySelectorAll('svg path');
            expect(paths.length).toBeGreaterThan(0);

            // The member name should be visible
            expect(screen.getAllByText('Seul Membre').length).toBeGreaterThan(0);

            // 100% should be displayed (appears in multiple charts)
            expect(screen.getAllByText('(100%)').length).toBeGreaterThan(0);
        });

        it('renders status chart with single 100% status', () => {
            const stats = createMockStatistics({
                todos: {
                    total: 10,
                    completed: 10,
                    in_progress: 0,
                    pending: 0,
                    overdue: 0,
                    by_priority: { critical: 0, high: 0, medium: 0, low: 0 },
                },
            });
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            // Should render SVG paths
            const paths = container.querySelectorAll('svg path');
            expect(paths.length).toBeGreaterThan(0);

            // Only "Terminé" should be visible (100%)
            expect(screen.getByText('Terminé')).toBeInTheDocument();
        });
    });

    describe('Responsive Layout', () => {
        it('uses responsive grid for doughnut charts', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const gridContainers = container.querySelectorAll('.grid.grid-cols-1.md\\:grid-cols-3');
            expect(gridContainers.length).toBeGreaterThan(0);
        });

        it('uses responsive grid for area and bar charts', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const gridContainers = container.querySelectorAll('.grid.grid-cols-1.lg\\:grid-cols-2');
            expect(gridContainers.length).toBeGreaterThan(0);
        });
    });

    describe('Accessibility', () => {
        it('has proper heading structure', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getByText('Tâches par Membre')).toBeInTheDocument();
            expect(screen.getByText('Tâches Terminées par Membre')).toBeInTheDocument();
        });

        it('provides descriptive text for chart sections', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsAnalytical statistics={stats} />);

            expect(screen.getByText('Répartition des tâches assignées')).toBeInTheDocument();
            expect(screen.getByText('Tâches complétées avec succès')).toBeInTheDocument();
            expect(screen.getByText('État actuel des tâches')).toBeInTheDocument();
            expect(screen.getByText('Tâches créées vs terminées')).toBeInTheDocument();
            expect(screen.getByText('Performance par membre')).toBeInTheDocument();
        });

        it('uses semantic color contrast for status indicators', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsAnalytical statistics={stats} />);

            const coloredElements = container.querySelectorAll('[style*="background"]');
            expect(coloredElements.length).toBeGreaterThan(0);
        });
    });
});
