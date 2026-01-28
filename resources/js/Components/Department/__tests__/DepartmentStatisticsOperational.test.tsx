import { describe, it, expect, vi } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DepartmentStatisticsOperational, { DepartmentStatistics } from '../DepartmentStatisticsOperational';

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
        ],
    },
    ...overrides,
});

describe('DepartmentStatisticsOperational Component', () => {
    describe('Rendering', () => {
        it('renders without crashing', () => {
            render(<DepartmentStatisticsOperational />);
            expect(screen.getByText('Taux de Complétion')).toBeInTheDocument();
        });

        it('renders with statistics data', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Taux de Complétion')).toBeInTheDocument();
            expect(screen.getByText('Vélocité ce mois')).toBeInTheDocument();
            expect(screen.getByText('Tâches en retard')).toBeInTheDocument();
            expect(screen.getByText('Temps moyen')).toBeInTheDocument();
        });

        it('displays empty state when no statistics provided', () => {
            render(<DepartmentStatisticsOperational />);

            expect(screen.getByText('0%')).toBeInTheDocument();
            expect(screen.getByText('0 / 0 tâches')).toBeInTheDocument();
        });
    });

    describe('Collective Performance Cards', () => {
        it('displays completion rate correctly', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('50%')).toBeInTheDocument();
            expect(screen.getByText('25 / 50 tâches')).toBeInTheDocument();
        });

        it('displays velocity with positive change indicator', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('+25% vs mois dernier')).toBeInTheDocument();
        });

        it('displays velocity with negative change indicator', () => {
            const stats = createMockStatistics({
                performance: {
                    ...createMockStatistics().performance!,
                    collective: {
                        ...createMockStatistics().performance!.collective,
                        velocity_change: -10,
                    },
                },
            });
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('-10% vs mois dernier')).toBeInTheDocument();
        });

        it('displays overdue rate text', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('10% du total')).toBeInTheDocument();
        });

        it('displays average completion time', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('3j')).toBeInTheDocument();
            expect(screen.getByText('Durée moyenne de complétion')).toBeInTheDocument();
        });
    });

    describe('Status Distribution', () => {
        it('renders status distribution labels', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Répartition par Statut')).toBeInTheDocument();
            expect(screen.getByText('Terminées')).toBeInTheDocument();
            expect(screen.getByText('En cours')).toBeInTheDocument();
            expect(screen.getByText('En attente')).toBeInTheDocument();
            // "En retard" appears in multiple places
            expect(screen.getAllByText('En retard').length).toBeGreaterThan(0);
        });

        it('renders distribution with progress bars', () => {
            const stats = createMockStatistics();
            const { container } = render(<DepartmentStatisticsOperational statistics={stats} />);

            const progressBars = container.querySelectorAll('.rounded-full.h-2');
            expect(progressBars.length).toBeGreaterThan(0);
        });
    });

    describe('Priority Distribution', () => {
        it('renders priority distribution labels', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Répartition par Priorité')).toBeInTheDocument();
            expect(screen.getByText('Critique')).toBeInTheDocument();
            expect(screen.getByText('Haute')).toBeInTheDocument();
            expect(screen.getByText('Moyenne')).toBeInTheDocument();
            expect(screen.getByText('Basse')).toBeInTheDocument();
        });
    });

    describe('Task Evolution', () => {
        it('renders task evolution section', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Évolution des Tâches')).toBeInTheDocument();
            expect(screen.getByText('Tâches créées et complétées au fil du temps')).toBeInTheDocument();
        });

        it('shows period toggle buttons', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByRole('button', { name: 'Semaine' })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: 'Mois' })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: 'Trimestre' })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: 'Semestre' })).toBeInTheDocument();
        });

        it('switches evolution period on button click', async () => {
            const user = userEvent.setup();
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            // Click on Monthly
            await user.click(screen.getByRole('button', { name: 'Mois' }));

            // Monthly labels should be visible
            expect(screen.getByText('Jan')).toBeInTheDocument();
            expect(screen.getByText('Feb')).toBeInTheDocument();
        });

        it('shows empty state when no evolution data', () => {
            const stats = createMockStatistics({
                task_evolution: {
                    weekly: [],
                    monthly: [],
                    quarterly: [],
                    semester: [],
                },
            });
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Aucune donnée disponible pour cette période')).toBeInTheDocument();
        });

        it('displays weekly data by default', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('S1')).toBeInTheDocument();
            expect(screen.getByText('S2')).toBeInTheDocument();
            expect(screen.getByText('S3')).toBeInTheDocument();
        });

        it('displays legend for chart', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Créées')).toBeInTheDocument();
            // 'Complétées' appears in multiple places (legend and table header)
            expect(screen.getAllByText('Complétées').length).toBeGreaterThan(0);
        });
    });

    describe('Tasks by Member', () => {
        it('renders member task distribution', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Répartition par Membre')).toBeInTheDocument();
            // Members appear in multiple sections (task distribution and performance table)
            expect(screen.getAllByText('Jean Dupont').length).toBeGreaterThan(0);
            expect(screen.getAllByText('Marie Martin').length).toBeGreaterThan(0);
            expect(screen.getByText('Non assigné')).toBeInTheDocument();
        });

        it('shows task counts per member', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('15 tâches')).toBeInTheDocument();
            expect(screen.getByText('20 tâches')).toBeInTheDocument();
            expect(screen.getByText('5 tâches')).toBeInTheDocument();
        });

        it('shows completion rate badges', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            // Percentages may appear in multiple places
            expect(screen.getAllByText('67%').length).toBeGreaterThan(0);
            expect(screen.getAllByText('90%').length).toBeGreaterThan(0);
        });

        it('shows empty state when no members', () => {
            const stats = createMockStatistics({ tasks_by_member: [] });
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Aucune tâche assignée')).toBeInTheDocument();
        });
    });

    describe('Individual Performance Table', () => {
        it('renders performance table headers', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Performances Individuelles')).toBeInTheDocument();
            expect(screen.getByText('Membre')).toBeInTheDocument();
            expect(screen.getByText('Tâches')).toBeInTheDocument();
            // 'Complétées' appears multiple times
            expect(screen.getAllByText('Complétées').length).toBeGreaterThan(0);
            expect(screen.getByText('Taux')).toBeInTheDocument();
            expect(screen.getByText('Temps moy.')).toBeInTheDocument();
            expect(screen.getByText('Ce mois')).toBeInTheDocument();
        });

        it('displays individual performance data', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            const table = screen.getByRole('table');
            const rows = within(table).getAllByRole('row');
            // Header row + 2 data rows
            expect(rows.length).toBeGreaterThanOrEqual(3);
        });

        it('shows medal icons for top performers', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('🥇')).toBeInTheDocument();
            expect(screen.getByText('🥈')).toBeInTheDocument();
        });

        it('shows empty state when no performance data', () => {
            const stats = createMockStatistics({
                performance: { ...createMockStatistics().performance!, individual: [] },
            });
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Aucune donnée de performance disponible')).toBeInTheDocument();
        });
    });

    describe('Scheduling Section', () => {
        it('renders scheduling information', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Planning')).toBeInTheDocument();
            expect(screen.getByText('Shifts à venir')).toBeInTheDocument();
            expect(screen.getByText('Absences en attente')).toBeInTheDocument();
            expect(screen.getByText('Absences approuvées')).toBeInTheDocument();
            expect(screen.getByText('Échanges en attente')).toBeInTheDocument();
        });
    });

    describe('Needs Section', () => {
        it('renders needs information', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Besoins')).toBeInTheDocument();
        });

        it('displays needs by status', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            // Status names are capitalized and shown
            expect(screen.getByText('pending')).toBeInTheDocument();
            expect(screen.getByText('approved')).toBeInTheDocument();
            expect(screen.getByText('rejected')).toBeInTheDocument();
        });

        it('displays total cost', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Coût total')).toBeInTheDocument();
        });

        it('shows empty state when no needs', () => {
            const stats = createMockStatistics({
                needs: { total: 0, by_status: {}, by_priority: {}, total_cost: 0 },
            });
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Aucun besoin')).toBeInTheDocument();
        });
    });

    describe('Forms & Workflows Section', () => {
        it('renders forms and workflows information', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Formulaires & Workflows')).toBeInTheDocument();
        });

        it('displays form section', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Formulaires')).toBeInTheDocument();
        });

        it('displays workflow section', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            expect(screen.getByText('Workflows')).toBeInTheDocument();
            expect(screen.getByText('Actifs')).toBeInTheDocument();
            expect(screen.getByText('Obsolètes')).toBeInTheDocument();
        });
    });

    describe('Accessibility', () => {
        it('has proper heading structure', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            const headings = screen.getAllByRole('heading');
            expect(headings.length).toBeGreaterThan(0);
        });

        it('has accessible table structure', () => {
            const stats = createMockStatistics();
            render(<DepartmentStatisticsOperational statistics={stats} />);

            const table = screen.getByRole('table');
            expect(table).toBeInTheDocument();

            const columnHeaders = within(table).getAllByRole('columnheader');
            expect(columnHeaders.length).toBe(7);
        });
    });
});
