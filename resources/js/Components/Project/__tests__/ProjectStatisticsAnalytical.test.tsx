import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import ProjectStatisticsAnalytical, { ProjectAnalyticsData } from '../ProjectStatisticsAnalytical';

const createMockData = (overrides: Partial<ProjectAnalyticsData> = {}): ProjectAnalyticsData => ({
    projects_by_status: [
        { label: 'Actif', value: 5, color: '#10B981' },
        { label: 'Planification', value: 2, color: '#3B82F6' },
        { label: 'En pause', value: 1, color: '#F59E0B' },
        { label: 'Terminé', value: 3, color: '#8B5CF6' },
        { label: 'Annulé', value: 0, color: '#EF4444' },
    ],
    tasks_by_status: [
        { label: 'Terminé', value: 10, color: '#10B981' },
        { label: 'En cours', value: 5, color: '#3B82F6' },
        { label: 'À faire', value: 3, color: '#F59E0B' },
        { label: 'En attente', value: 2, color: '#F97316' },
        { label: 'En revue', value: 1, color: '#8B5CF6' },
        { label: 'Bloqué', value: 0, color: '#EF4444' },
        { label: 'Annulé', value: 0, color: '#6B7280' },
    ],
    tasks_by_priority: [
        { label: 'Critique', value: 2, color: '#EF4444' },
        { label: 'Haute', value: 5, color: '#F97316' },
        { label: 'Moyenne', value: 8, color: '#F59E0B' },
        { label: 'Basse', value: 4, color: '#3B82F6' },
        { label: 'Très basse', value: 2, color: '#6B7280' },
    ],
    sprints_by_status: [
        { label: 'Actif', value: 2, color: '#10B981' },
        { label: 'Planifié', value: 1, color: '#3B82F6' },
        { label: 'Terminé', value: 4, color: '#8B5CF6' },
        { label: 'Annulé', value: 0, color: '#EF4444' },
    ],
    task_evolution: {
        weekly: [
            { label: 'S1', created: 5, completed: 3 },
            { label: 'S2', created: 8, completed: 6 },
            { label: 'S3', created: 4, completed: 4 },
            { label: 'S4', created: 10, completed: 7 },
            { label: 'S5', created: 6, completed: 5 },
            { label: 'S6', created: 3, completed: 2 },
            { label: 'S7', created: 7, completed: 6 },
            { label: 'S8', created: 9, completed: 8 },
        ],
        monthly: [
            { label: 'Jan', created: 20, completed: 15 },
            { label: 'Fév', created: 25, completed: 20 },
            { label: 'Mar', created: 30, completed: 22 },
            { label: 'Avr', created: 18, completed: 16 },
            { label: 'Mai', created: 22, completed: 18 },
            { label: 'Juin', created: 28, completed: 24 },
            { label: 'Juil', created: 15, completed: 12 },
            { label: 'Août', created: 10, completed: 8 },
            { label: 'Sep', created: 25, completed: 20 },
            { label: 'Oct', created: 30, completed: 25 },
            { label: 'Nov', created: 35, completed: 28 },
            { label: 'Déc', created: 28, completed: 22 },
        ],
        quarterly: [
            { label: 'T1 2025', created: 75, completed: 57 },
            { label: 'T2 2025', created: 68, completed: 58 },
            { label: 'T3 2025', created: 50, completed: 40 },
            { label: 'T4 2025', created: 93, completed: 75 },
        ],
        semester: [
            { label: 'S1 2024', created: 100, completed: 80 },
            { label: 'S2 2024', created: 120, completed: 95 },
            { label: 'S1 2025', created: 143, completed: 115 },
            { label: 'S2 2025', created: 143, completed: 115 },
        ],
        yearly: [
            { label: '2023', created: 200, completed: 160 },
            { label: '2024', created: 220, completed: 175 },
            { label: '2025', created: 286, completed: 230 },
        ],
    },
    completion_by_project: [
        { name: 'Projet Alpha', value: 80, color: '#3B82F6', completed: 8, total: 10 },
        { name: 'Projet Beta', value: 50, color: '#10B981', completed: 5, total: 10 },
        { name: 'Projet Gamma', value: 100, color: '#F97316', completed: 3, total: 3 },
    ],
    completion_by_assignee: [
        { name: 'Jean Dupont', value: 75, color: '#3B82F6', completed: 6, total: 8 },
        { name: 'Marie Martin', value: 90, color: '#10B981', completed: 9, total: 10 },
    ],
    projects_by_member: [
        { label: 'Jean Dupont', value: 3, color: '#3B82F6' },
        { label: 'Marie Martin', value: 2, color: '#10B981' },
    ],
    tasks_by_member: [
        { label: 'Jean Dupont', value: 8, color: '#3B82F6' },
        { label: 'Marie Martin', value: 10, color: '#10B981' },
        { label: 'Pierre Bernard', value: 5, color: '#F97316' },
    ],
    global_progress: {
        percentage: 28,
        completed: 16,
        total: 58,
    },
    ...overrides,
});

describe('ProjectStatisticsAnalytical', () => {
    it('renders without crashing', () => {
        const { container } = render(
            <ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />
        );
        expect(container).toBeTruthy();
    });

    it('shows projects by status doughnut in dashboard context', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Projets par Statut')).toBeTruthy();
    });

    it('does not show projects by status doughnut in project context', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="project" />);
        expect(screen.queryByText('Projets par Statut')).toBeNull();
    });

    it('does not show projects by status doughnut in tasks context', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="tasks" />);
        expect(screen.queryByText('Projets par Statut')).toBeNull();
    });

    it('renders tasks by status doughnut', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Tâches par Statut')).toBeTruthy();
    });

    it('renders tasks by priority doughnut', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Tâches par Priorité')).toBeTruthy();
    });

    it('renders sprints by status doughnut when data exists', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Sprints par Statut')).toBeTruthy();
    });

    it('does not render sprints doughnut when sprints_by_status is undefined', () => {
        const data = createMockData({ sprints_by_status: undefined });
        render(<ProjectStatisticsAnalytical statistics={data} context="dashboard" />);
        expect(screen.queryByText('Sprints par Statut')).toBeNull();
    });

    it('renders evolution chart', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Évolution des Tâches')).toBeTruthy();
    });

    it('renders period selector in evolution chart', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Semaine')).toBeTruthy();
        expect(screen.getByText('Mois')).toBeTruthy();
        expect(screen.getByText('Trimestre')).toBeTruthy();
        expect(screen.getByText('Semestre')).toBeTruthy();
        expect(screen.getByText('Année')).toBeTruthy();
    });

    it('renders projects by member doughnut in dashboard context', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Projets par Responsable')).toBeTruthy();
    });

    it('renders tasks by member bar chart', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Tâches par Membre')).toBeTruthy();
        expect(screen.getByText('Répartition des tâches assignées')).toBeTruthy();
        // Should show total count
        expect(screen.getByText(/Total:/)).toBeTruthy();
    });

    it('renders completion by project in dashboard context', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Taux de Complétion par Projet')).toBeTruthy();
    });

    it('renders completion by assignee in project context', () => {
        const data = createMockData({ completion_by_project: undefined });
        render(<ProjectStatisticsAnalytical statistics={data} context="project" />);
        expect(screen.getByText('Taux de Complétion par Membre')).toBeTruthy();
    });

    it('shows total count in doughnut center', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        // tasks_by_status total = 10+5+3+2+1+0+0 = 21, appears in multiple doughnuts
        const totals = screen.getAllByText('21');
        expect(totals.length).toBeGreaterThanOrEqual(1);
    });

    it('shows "Aucune donnée" for empty doughnut data', () => {
        const data = createMockData({
            tasks_by_status: [
                { label: 'Terminé', value: 0, color: '#10B981' },
                { label: 'En cours', value: 0, color: '#3B82F6' },
            ],
        });
        render(<ProjectStatisticsAnalytical statistics={data} context="tasks" />);
        expect(screen.getByText('Aucune donnée')).toBeTruthy();
    });

    it('renders bar chart items with percentages', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Projet Alpha')).toBeTruthy();
        expect(screen.getByText('80%')).toBeTruthy();
        expect(screen.getByText('Projet Beta')).toBeTruthy();
        expect(screen.getByText('50%')).toBeTruthy();
    });

    it('renders week labels in area chart', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('S1')).toBeTruthy();
        expect(screen.getByText('S8')).toBeTruthy();
    });

    it('renders legend for area chart', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Tâches créées')).toBeTruthy();
        expect(screen.getByText('Tâches terminées')).toBeTruthy();
    });

    it('renders all doughnut labels with values and percentages', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        // From tasks_by_priority: Critique value=2
        expect(screen.getByText('Critique')).toBeTruthy();
    });

    it('renders empty state for bar chart when no data', () => {
        const data = createMockData({
            completion_by_project: [],
            completion_by_assignee: undefined,
        });
        render(<ProjectStatisticsAnalytical statistics={data} context="dashboard" />);
        expect(screen.getByText('Aucune donnée')).toBeTruthy();
    });

    it('renders global progress in dashboard context', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="dashboard" />);
        expect(screen.getByText('Progression Globale')).toBeTruthy();
        expect(screen.getByText('28%')).toBeTruthy();
        expect(screen.getByText('16 sur 58 tâches terminées')).toBeTruthy();
    });

    it('renders global progress in tasks context', () => {
        render(<ProjectStatisticsAnalytical statistics={createMockData()} context="tasks" />);
        expect(screen.getByText('Progression Globale')).toBeTruthy();
        expect(screen.getByText('28%')).toBeTruthy();
    });

    it('does not render global progress when data is undefined', () => {
        const data = createMockData({ global_progress: undefined });
        render(<ProjectStatisticsAnalytical statistics={data} context="dashboard" />);
        expect(screen.queryByText('Progression Globale')).toBeNull();
    });

    it('renders velocity gauge when velocity data exists', () => {
        const data = createMockData({
            velocity: {
                daily: { value: 2.5, total: 75, period_count: 30, max: 10, label: 'jour' },
                weekly: { value: 15.3, total: 122, period_count: 8, max: 50, label: 'semaine' },
                monthly: { value: 45.2, total: 543, period_count: 12, max: 200, label: 'mois' },
            },
        });
        render(<ProjectStatisticsAnalytical statistics={data} context="dashboard" />);
        expect(screen.getByText('Vélocité')).toBeTruthy();
    });

    it('shows monthly velocity value by default', () => {
        const data = createMockData({
            velocity: {
                daily: { value: 2.5, total: 75, period_count: 30, max: 10, label: 'jour' },
                weekly: { value: 15.3, total: 122, period_count: 8, max: 50, label: 'semaine' },
                monthly: { value: 45.2, total: 543, period_count: 12, max: 200, label: 'mois' },
            },
        });
        render(<ProjectStatisticsAnalytical statistics={data} context="dashboard" />);
        expect(screen.getByText('45.2')).toBeTruthy();
        expect(screen.getByText(/tâches \/ mois/)).toBeTruthy();
    });

    it('does not render velocity gauge when velocity data is undefined', () => {
        const data = createMockData({ velocity: undefined });
        render(<ProjectStatisticsAnalytical statistics={data} context="dashboard" />);
        expect(screen.queryByText('Vélocité')).toBeNull();
    });

    it('renders velocity period selector with all options', () => {
        const data = createMockData({
            velocity: {
                daily: { value: 2.5, total: 75, period_count: 30, max: 10, label: 'jour' },
                weekly: { value: 15.3, total: 122, period_count: 8, max: 50, label: 'semaine' },
                monthly: { value: 45.2, total: 543, period_count: 12, max: 200, label: 'mois' },
            },
        });
        render(<ProjectStatisticsAnalytical statistics={data} context="dashboard" />);

        // Check select element exists with period options
        const select = screen.getByRole('combobox', { name: /période/i });
        expect(select).toBeTruthy();
    });

    it('renders velocity in project context', () => {
        const data = createMockData({
            velocity: {
                daily: { value: 1.2, total: 36, period_count: 30, max: 10, label: 'jour' },
                weekly: { value: 8.5, total: 68, period_count: 8, max: 50, label: 'semaine' },
                monthly: { value: 25.0, total: 300, period_count: 12, max: 200, label: 'mois' },
            },
        });
        render(<ProjectStatisticsAnalytical statistics={data} context="project" />);
        expect(screen.getByText('Vélocité')).toBeTruthy();
        expect(screen.getByText('25')).toBeTruthy();
    });

    it('renders velocity in tasks context', () => {
        const data = createMockData({
            velocity: {
                daily: { value: 3.0, total: 90, period_count: 30, max: 10, label: 'jour' },
                weekly: { value: 20.0, total: 160, period_count: 8, max: 50, label: 'semaine' },
                monthly: { value: 60.5, total: 726, period_count: 12, max: 200, label: 'mois' },
            },
        });
        render(<ProjectStatisticsAnalytical statistics={data} context="tasks" />);
        expect(screen.getByText('Vélocité')).toBeTruthy();
    });
});
