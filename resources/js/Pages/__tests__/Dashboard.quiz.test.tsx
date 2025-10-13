import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import Dashboard from '../Dashboard';

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

describe('Dashboard - Quiz Section', () => {
    const baseProps = {
        auth: {
            user: {
                id: 1,
                name: 'Test User',
                email: 'test@example.com',
            },
        },
        stats: {
            upcomingEvents: { value: 12, change: { value: '+2', type: 'increase' as const } },
            publishedArticles: { value: 25, change: { value: '+3', type: 'increase' as const } },
            availableBooks: { value: 48, change: { value: '-1', type: 'decrease' as const } },
            unreadMessages: { value: 6, change: { value: '0', type: 'stable' as const } },
        },
        recentActivities: [],
        performance: {
            participationRate: 91,
            articlesViewedThisMonth: 18,
            booksBorrowed: 9,
        },
    };

    beforeEach(() => {
        mockPageProps = { ...baseProps };
    });

    describe('Quiz Stats Cards', () => {
        it('should display quiz stats when quizzes are available', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Test Quiz',
                    description: 'Description',
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Test Training' },
                },
            ];
            mockPageProps.quizStats = {
                total_completed: 5,
                total_passed: 4,
                average_score: 85,
                pending_quizzes: 7, // Changed to avoid collision with other stats
                pass_rate: 80,
            };

            render(<Dashboard />);

            expect(screen.getByText('Quiz à venir')).toBeInTheDocument();
            expect(screen.getByText('À faire')).toBeInTheDocument();
            expect(screen.getByText('7')).toBeInTheDocument(); // pending_quizzes
            expect(screen.getByText('Complétés')).toBeInTheDocument();
            expect(screen.getByText('5')).toBeInTheDocument(); // total_completed
            expect(screen.getByText('Taux de réussite')).toBeInTheDocument();
            expect(screen.getByText('80%')).toBeInTheDocument(); // pass_rate
            expect(screen.getByText('Score moyen')).toBeInTheDocument();
            expect(screen.getByText('85')).toBeInTheDocument(); // average_score
        });

        it('should show passed/completed ratio', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Test Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Test Training' },
                },
            ];
            mockPageProps.quizStats = {
                total_completed: 10,
                total_passed: 8,
                average_score: 75,
                pending_quizzes: 2,
                pass_rate: 80,
            };

            render(<Dashboard />);

            expect(screen.getByText('8/10 réussis')).toBeInTheDocument();
        });
    });

    describe('Quiz List Display', () => {
        it('should display quiz title and training', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Mathematics Quiz',
                    description: 'A quiz about math',
                    duration_minutes: 45,
                    max_score: 100,
                    passing_score: 60,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Math 101' },
                },
            ];

            render(<Dashboard />);

            expect(screen.getByText('Mathematics Quiz')).toBeInTheDocument();
            expect(screen.getByText('Math 101')).toBeInTheDocument();
            expect(screen.getByText('A quiz about math')).toBeInTheDocument();
        });

        it('should display quiz metadata', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Science Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 50,
                    passing_score: 35,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Science' },
                },
            ];

            render(<Dashboard />);

            expect(screen.getByText('30 min')).toBeInTheDocument();
            expect(screen.getByText('35/50 pts requis')).toBeInTheDocument();
        });

        it('should display start button for each quiz', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Quiz 1',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Training 1' },
                },
                {
                    id: 2,
                    uuid: 'quiz-2',
                    title: 'Quiz 2',
                    description: null,
                    duration_minutes: 45,
                    max_score: 80,
                    passing_score: 50,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 2, uuid: 'training-2', title: 'Training 2' },
                },
            ];

            render(<Dashboard />);

            const startButtons = screen.getAllByRole('button', { name: /commencer/i });
            expect(startButtons).toHaveLength(2);
        });

        it('should link to correct quiz start URL', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-uuid-123',
                    title: 'Test Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            const { container } = render(<Dashboard />);

            const link = container.querySelector('a[href="/quizzes/quiz-uuid-123/start"]');
            expect(link).toBeInTheDocument();
        });
    });

    describe('Urgent Quiz Indicators', () => {
        it('should show urgent badge for urgent quizzes', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Urgent Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: '2025-10-14T10:00:00',
                    days_until_deadline: 1,
                    is_urgent: true,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            render(<Dashboard />);

            expect(screen.getByText('Urgent')).toBeInTheDocument();
        });

        it('should apply red border to urgent quiz cards', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Urgent Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: '2025-10-14T10:00:00',
                    days_until_deadline: 1,
                    is_urgent: true,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            const { container } = render(<Dashboard />);

            const urgentCard = container.querySelector('.border-red-300');
            expect(urgentCard).toBeInTheDocument();
        });

        it('should not show urgent badge for normal quizzes', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Normal Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: '2025-10-20T10:00:00',
                    days_until_deadline: 7,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            render(<Dashboard />);

            expect(screen.queryByText('Urgent')).not.toBeInTheDocument();
        });
    });

    describe('Deadline Display', () => {
        it('should show "Dernier jour!" for deadline today', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: new Date().toISOString(),
                    days_until_deadline: 0,
                    is_urgent: true,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            render(<Dashboard />);

            expect(screen.getByText('Dernier jour !')).toBeInTheDocument();
        });

        it('should show "Reste 1 jour" for 1 day remaining', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: '2025-10-14T10:00:00',
                    days_until_deadline: 1,
                    is_urgent: true,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            render(<Dashboard />);

            expect(screen.getByText('Reste 1 jour')).toBeInTheDocument();
        });

        it('should show "Reste X jours" for multiple days remaining', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: '2025-10-20T10:00:00',
                    days_until_deadline: 7,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            render(<Dashboard />);

            expect(screen.getByText('Reste 7 jours')).toBeInTheDocument();
        });

        it('should show "Expiré" for expired quizzes', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: '2025-10-01T10:00:00',
                    days_until_deadline: -1,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            render(<Dashboard />);

            expect(screen.getByText('Expiré')).toBeInTheDocument();
        });

        it('should not show deadline text when no deadline is set', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Quiz',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            render(<Dashboard />);

            expect(screen.queryByText(/Reste/)).not.toBeInTheDocument();
            expect(screen.queryByText(/Expiré/)).not.toBeInTheDocument();
        });
    });

    describe('Quiz Section Visibility', () => {
        it('should not display quiz section when no quizzes available', () => {
            mockPageProps.upcomingQuizzes = [];

            render(<Dashboard />);

            expect(screen.queryByText('Quiz à venir')).not.toBeInTheDocument();
        });

        it('should not display quiz section when upcomingQuizzes is undefined', () => {
            mockPageProps.upcomingQuizzes = undefined;

            render(<Dashboard />);

            expect(screen.queryByText('Quiz à venir')).not.toBeInTheDocument();
        });

        it('should display quiz section with multiple quizzes', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Quiz 1',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Training 1' },
                },
                {
                    id: 2,
                    uuid: 'quiz-2',
                    title: 'Quiz 2',
                    description: null,
                    duration_minutes: 45,
                    max_score: 80,
                    passing_score: 50,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 2, uuid: 'training-2', title: 'Training 2' },
                },
                {
                    id: 3,
                    uuid: 'quiz-3',
                    title: 'Quiz 3',
                    description: null,
                    duration_minutes: 60,
                    max_score: 120,
                    passing_score: 90,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 3, uuid: 'training-3', title: 'Training 3' },
                },
            ];
            mockPageProps.quizStats = {
                total_completed: 10,
                total_passed: 8,
                average_score: 75,
                pending_quizzes: 3,
                pass_rate: 80,
            };

            render(<Dashboard />);

            expect(screen.getByText('Quiz 1')).toBeInTheDocument();
            expect(screen.getByText('Quiz 2')).toBeInTheDocument();
            expect(screen.getByText('Quiz 3')).toBeInTheDocument();
        });
    });

    describe('Quiz Description', () => {
        it('should display quiz description when available', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Quiz with Description',
                    description: 'This is a detailed description of the quiz',
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            render(<Dashboard />);

            expect(screen.getByText('This is a detailed description of the quiz')).toBeInTheDocument();
        });

        it('should not display description element when description is null', () => {
            mockPageProps.upcomingQuizzes = [
                {
                    id: 1,
                    uuid: 'quiz-1',
                    title: 'Quiz without Description',
                    description: null,
                    duration_minutes: 30,
                    max_score: 100,
                    passing_score: 70,
                    available_until: null,
                    days_until_deadline: null,
                    is_urgent: false,
                    training: { id: 1, uuid: 'training-1', title: 'Training' },
                },
            ];

            render(<Dashboard />);

            // Only the title should be visible
            expect(screen.getByText('Quiz without Description')).toBeInTheDocument();
        });
    });
});
