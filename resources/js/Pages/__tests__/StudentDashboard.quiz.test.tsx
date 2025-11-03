import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import StudentDashboard from '../StudentDashboard';

// Mock Inertia
vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual('@inertiajs/react');
    return {
        ...actual,
        usePage: () => ({
            props: mockPageProps,
        }),
        Head: ({ children }: any) => <>{children}</>,
        Link: ({ children, href }: any) => <a href={href}>{children}</a>,
    };
});

// Mock DashboardLayout
vi.mock('@/Layouts/DashboardLayout', () => ({
    default: ({ children }: any) => <div data-testid="dashboard-layout">{children}</div>,
}));

// Mock route helper
global.route = vi.fn((name: string, params?: any) => {
    if (name === 'quizzes.start') {
        return `/quizzes/${params}/start`;
    }
    return `/${name.replace('.', '/')}`;
}) as any;

let mockPageProps: any;

describe('StudentDashboard - Quiz Attempts', () => {
    const baseProps = {
        auth: {
            user: {
                id: 1,
                name: 'Test Student',
                email: 'test@example.com',
            },
        },
        trainings: [],
    };

    beforeEach(() => {
        mockPageProps = { ...baseProps };
    });

    it('displays "Commencer" button for quiz with no attempts', () => {
        const trainings = [
            {
                id: 1,
                uuid: 'training-uuid-1',
                title: 'Test Training',
                description: 'Test description',
                duration: '10 hours',
                level: 'beginner' as const,
                price: 100,
                teacher: null,
                topics: [],
                materials: [],
                quizzes: [
                    {
                        id: 1,
                        uuid: 'quiz-uuid-1',
                        title: 'Test Quiz',
                        description: 'Test quiz description',
                        duration_minutes: 30,
                        max_score: 100,
                        passing_score: 60,
                        available_from: null,
                        available_until: null,
                        max_attempts: 3,
                        attempts_count: 0,
                        can_retake: true,
                        attempt: null,
                    },
                ],
                progress: 0,
                grade: 0,
                attendanceRate: 0,
                nextClass: null,
            },
        ];

        mockPageProps.trainings = trainings;

        render(<StudentDashboard {...mockPageProps} />);

        // Switch to Évaluations tab
        const evaluationsTab = screen.getByRole('tab', { name: /évaluations/i });
        evaluationsTab.click();

        // Check for "Commencer" button
        const startButton = screen.getByRole('button', { name: /commencer/i });
        expect(startButton).toBeDefined();
        expect(startButton.getAttribute('disabled')).toBeNull();
    });

    it('displays "Refaire" button for quiz with attempts remaining', () => {
        const trainings = [
            {
                id: 1,
                uuid: 'training-uuid-1',
                title: 'Test Training',
                description: 'Test description',
                duration: '10 hours',
                level: 'beginner' as const,
                price: 100,
                teacher: null,
                topics: [],
                materials: [],
                quizzes: [
                    {
                        id: 1,
                        uuid: 'quiz-uuid-1',
                        title: 'Test Quiz',
                        description: 'Test quiz description',
                        duration_minutes: 30,
                        max_score: 100,
                        passing_score: 60,
                        available_from: null,
                        available_until: null,
                        max_attempts: 3,
                        attempts_count: 1,
                        can_retake: true,
                        attempt: {
                            id: 1,
                            score: 50,
                            status: 'completed' as const,
                            completed_at: '2025-11-01T10:00:00.000000Z',
                        },
                    },
                ],
                progress: 0,
                grade: 0,
                attendanceRate: 0,
                nextClass: null,
            },
        ];

        mockPageProps.trainings = trainings;

        render(<StudentDashboard {...mockPageProps} />);

        // Switch to Évaluations tab
        const evaluationsTab = screen.getByRole('tab', { name: /évaluations/i });
        evaluationsTab.click();

        // Check for "Refaire" button
        const retakeButton = screen.getByRole('button', { name: /refaire/i });
        expect(retakeButton).toBeDefined();
        expect(retakeButton.getAttribute('disabled')).toBeNull();

        // Check attempts count display
        expect(screen.getByText(/tentative 1\/3/i)).toBeDefined();
    });

    it('displays disabled "Terminé" button when max attempts reached', () => {
        const trainings = [
            {
                id: 1,
                uuid: 'training-uuid-1',
                title: 'Test Training',
                description: 'Test description',
                duration: '10 hours',
                level: 'beginner' as const,
                price: 100,
                teacher: null,
                topics: [],
                materials: [],
                quizzes: [
                    {
                        id: 1,
                        uuid: 'quiz-uuid-1',
                        title: 'Test Quiz',
                        description: 'Test quiz description',
                        duration_minutes: 30,
                        max_score: 100,
                        passing_score: 60,
                        available_from: null,
                        available_until: null,
                        max_attempts: 2,
                        attempts_count: 2,
                        can_retake: false,
                        attempt: {
                            id: 2,
                            score: 55,
                            status: 'completed' as const,
                            completed_at: '2025-11-03T10:00:00.000000Z',
                        },
                    },
                ],
                progress: 0,
                grade: 0,
                attendanceRate: 0,
                nextClass: null,
            },
        ];

        mockPageProps.trainings = trainings;

        render(<StudentDashboard {...mockPageProps} />);

        // Switch to Évaluations tab
        const evaluationsTab = screen.getByRole('tab', { name: /évaluations/i });
        evaluationsTab.click();

        // Check for "Terminé" button
        const finishedButton = screen.getByRole('button', { name: /terminé/i });
        expect(finishedButton).toBeDefined();
        expect(finishedButton.hasAttribute('disabled')).toBe(true);

        // Check attempts count display
        expect(screen.getByText(/tentative 2\/2/i)).toBeDefined();
    });

    it('displays correct button for quiz with 5 max attempts and 4 used', () => {
        const trainings = [
            {
                id: 1,
                uuid: 'training-uuid-1',
                title: 'Test Training',
                description: 'Test description',
                duration: '10 hours',
                level: 'beginner' as const,
                price: 100,
                teacher: null,
                topics: [],
                materials: [],
                quizzes: [
                    {
                        id: 1,
                        uuid: 'quiz-uuid-1',
                        title: 'Test Quiz',
                        description: 'Test quiz description',
                        duration_minutes: 30,
                        max_score: 100,
                        passing_score: 60,
                        available_from: null,
                        available_until: null,
                        max_attempts: 5,
                        attempts_count: 4,
                        can_retake: true,
                        attempt: {
                            id: 4,
                            score: 58,
                            status: 'completed' as const,
                            completed_at: '2025-11-03T10:00:00.000000Z',
                        },
                    },
                ],
                progress: 0,
                grade: 0,
                attendanceRate: 0,
                nextClass: null,
            },
        ];

        mockPageProps.trainings = trainings;

        render(<StudentDashboard {...mockPageProps} />);

        // Switch to Évaluations tab
        const evaluationsTab = screen.getByRole('tab', { name: /évaluations/i });
        evaluationsTab.click();

        // Check for "Refaire" button (1 attempt remaining)
        const retakeButton = screen.getByRole('button', { name: /refaire/i });
        expect(retakeButton).toBeDefined();
        expect(retakeButton.getAttribute('disabled')).toBeNull();

        // Check attempts count display
        expect(screen.getByText(/tentative 4\/5/i)).toBeDefined();
    });

    it('hides inactive or draft quizzes from student view', () => {
        const trainings = [
            {
                id: 1,
                uuid: 'training-uuid-1',
                title: 'Test Training',
                description: 'Test description',
                duration: '10 hours',
                level: 'beginner' as const,
                price: 100,
                teacher: null,
                topics: [],
                materials: [],
                quizzes: [], // Empty because inactive/draft quizzes are filtered on backend
                progress: 0,
                grade: 0,
                attendanceRate: 0,
                nextClass: null,
            },
        ];

        mockPageProps.trainings = trainings;

        render(<StudentDashboard {...mockPageProps} />);

        // Switch to Évaluations tab
        const evaluationsTab = screen.getByRole('tab', { name: /évaluations/i });
        evaluationsTab.click();

        // Should show "no evaluations" message
        expect(screen.getByText(/aucune évaluation disponible/i)).toBeDefined();
    });

    it('displays passed status with green badge when score is sufficient', () => {
        const trainings = [
            {
                id: 1,
                uuid: 'training-uuid-1',
                title: 'Test Training',
                description: 'Test description',
                duration: '10 hours',
                level: 'beginner' as const,
                price: 100,
                teacher: null,
                topics: [],
                materials: [],
                quizzes: [
                    {
                        id: 1,
                        uuid: 'quiz-uuid-1',
                        title: 'Test Quiz',
                        description: 'Test quiz description',
                        duration_minutes: 30,
                        max_score: 100,
                        passing_score: 60,
                        available_from: null,
                        available_until: null,
                        max_attempts: 3,
                        attempts_count: 1,
                        can_retake: true,
                        attempt: {
                            id: 1,
                            score: 85,
                            status: 'completed' as const,
                            completed_at: '2025-11-01T10:00:00.000000Z',
                        },
                    },
                ],
                progress: 0,
                grade: 0,
                attendanceRate: 0,
                nextClass: null,
            },
        ];

        mockPageProps.trainings = trainings;

        render(<StudentDashboard {...mockPageProps} />);

        // Switch to Évaluations tab
        const evaluationsTab = screen.getByRole('tab', { name: /évaluations/i });
        evaluationsTab.click();

        // Check for "Réussi" badge
        expect(screen.getByText(/réussi/i)).toBeDefined();

        // Check score display
        expect(screen.getByText(/note: 85\/100/i)).toBeDefined();
    });
});
