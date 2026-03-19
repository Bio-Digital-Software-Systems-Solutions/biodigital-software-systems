import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Take from '../Take';
import { router } from '@inertiajs/react';

// Mock Inertia router
vi.mock('@inertiajs/react', async () => {
    const actual = await vi.importActual('@inertiajs/react');
    return {
        ...actual,
        router: {
            post: vi.fn(),
        },
        Head: ({ children }: any) => <>{children}</>,
    };
});

// Mock QuizTimer to avoid time-based complexity
vi.mock('@/Components/Quiz/QuizTimer', () => ({
    default: ({ className }: any) => (
        <div className={className} data-testid="quiz-timer">Timer</div>
    ),
}));

// Mock QuizTimerBadge
vi.mock('@/Components/Quiz/QuizTimerBadge', () => ({
    default: () => <div data-testid="quiz-timer-badge">Badge</div>,
}));

// Mock useQuizTimer
vi.mock('@/Hooks/useQuizTimer', () => ({
    useQuizTimer: ({ onTimeUp }: any) => {
        // Store onTimeUp so tests can call it
        (globalThis as any).__quizTimerOnTimeUp = onTimeUp;
        return {
            timeRemaining: 1800,
            totalDuration: 1800,
            formatted: '30:00',
            urgency: 'normal' as const,
            percentRemaining: 100,
        };
    },
}));

// Mock DashboardLayout
vi.mock('@/Layouts/DashboardLayout', () => ({
    default: ({ children }: any) => <div data-testid="dashboard-layout">{children}</div>,
}));

// Mock RadioGroup with functional onValueChange
vi.mock('@/Components/ui/radio-group', () => ({
    RadioGroup: ({ children, value, onValueChange }: any) => (
        <div data-testid="radio-group" data-value={value} onChange={(e: any) => onValueChange?.(e.target.value)}>
            {children}
        </div>
    ),
    RadioGroupItem: ({ value, id }: any) => (
        <input
            type="radio"
            value={value}
            id={id}
            data-testid={`radio-${value}`}
            name={id?.split('-')[0]}
        />
    ),
}));

// Mock toast
vi.mock('sonner', () => ({
    toast: {
        success: vi.fn(),
        error: vi.fn(),
    },
}));

// Mock route helper
(globalThis as any).route = vi.fn((name: string, params?: any) => {
    if (name === 'quiz-attempts.submit') {
        return `/quiz-attempts/${params}/submit`;
    }
    return '/';
});

describe('Quiz Take Page', () => {
    const mockQuiz = {
        id: 1,
        uuid: 'quiz-uuid-123',
        title: 'Test Quiz',
        description: 'A test quiz description',
        duration_minutes: 30,
        max_score: 100,
        passing_score: 70,
        questions: [
            {
                id: 1,
                question: 'What is 2 + 2?',
                type: 'multiple_choice' as const,
                options: ['2', '3', '4', '5'],
                points: 10,
                correct_answers_count: 1,
            },
            {
                id: 2,
                question: 'The sky is blue',
                type: 'true_false' as const,
                options: null,
                points: 5,
                correct_answers_count: 1,
            },
            {
                id: 3,
                question: 'What is the capital of France?',
                type: 'short_answer' as const,
                options: null,
                points: 15,
                correct_answers_count: 1,
            },
        ],
    };

    const mockAttempt = {
        id: 1,
        uuid: 'attempt-uuid-456',
        started_at: new Date().toISOString(),
        time_remaining_seconds: 1800,
    };

    beforeEach(() => {
        localStorage.clear();
        vi.clearAllMocks();
    });

    afterEach(() => {
        localStorage.clear();
    });

    describe('Rendering', () => {
        it('should render quiz title and description', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByText('Test Quiz')).toBeInTheDocument();
            expect(screen.getByText('A test quiz description')).toBeInTheDocument();
        });

        it('should render quiz metadata', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByText(/Questions: 3/)).toBeInTheDocument();
            expect(screen.getByText(/Points total: 100/)).toBeInTheDocument();
            expect(screen.getByText(/Score minimum: 70/)).toBeInTheDocument();
        });

        it('should render all questions', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByText('What is 2 + 2?')).toBeInTheDocument();
            expect(screen.getByText('The sky is blue')).toBeInTheDocument();
            expect(screen.getByText('What is the capital of France?')).toBeInTheDocument();
        });

        it('should render timer component', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByTestId('quiz-timer')).toBeInTheDocument();
        });

        it('should render submit button', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByRole('button', { name: /soumettre le quiz/i })).toBeInTheDocument();
        });

        it('should show question numbers correctly', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByText(/Question 1/)).toBeInTheDocument();
            expect(screen.getByText(/Question 2/)).toBeInTheDocument();
            expect(screen.getByText(/Question 3/)).toBeInTheDocument();
        });

        it('should show points for each question', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByText('10 points')).toBeInTheDocument();
            expect(screen.getByText('5 points')).toBeInTheDocument();
            expect(screen.getByText('15 points')).toBeInTheDocument();
        });

        it('should show singular "point" for 1 point questions', () => {
            const singlePointQuiz = {
                ...mockQuiz,
                questions: [
                    {
                        id: 1,
                        question: 'Test question',
                        type: 'multiple_choice' as const,
                        options: ['A', 'B'],
                        points: 1,
                        correct_answers_count: 1,
                    },
                ],
            };

            render(<Take quiz={singlePointQuiz} attempt={mockAttempt} />);

            expect(screen.getByText('1 point')).toBeInTheDocument();
        });
    });

    describe('Answer Selection - Multiple Choice (single answer)', () => {
        it('should render radio buttons for single-answer multiple choice', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByTestId('radio-2')).toBeInTheDocument();
            expect(screen.getByTestId('radio-3')).toBeInTheDocument();
            expect(screen.getByTestId('radio-4')).toBeInTheDocument();
            expect(screen.getByTestId('radio-5')).toBeInTheDocument();
        });

        it('should render all option labels', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByLabelText('2')).toBeInTheDocument();
            expect(screen.getByLabelText('3')).toBeInTheDocument();
            expect(screen.getByLabelText('4')).toBeInTheDocument();
            expect(screen.getByLabelText('5')).toBeInTheDocument();
        });
    });

    describe('Answer Selection - Multiple Choice (multiple answers / checkboxes)', () => {
        const multiAnswerQuiz = {
            ...mockQuiz,
            questions: [
                {
                    id: 10,
                    question: 'Which animals cannot fly?',
                    type: 'multiple_choice' as const,
                    options: ['Dog', 'Cat', 'Eagle', 'Fish'],
                    points: 5,
                    correct_answers_count: 3,
                },
            ],
        };

        it('should render checkboxes for multi-answer questions', () => {
            render(<Take quiz={multiAnswerQuiz} attempt={mockAttempt} />);

            expect(screen.getByText('Dog')).toBeInTheDocument();
            expect(screen.getByText('Cat')).toBeInTheDocument();
            expect(screen.getByText('Eagle')).toBeInTheDocument();
            expect(screen.getByText('Fish')).toBeInTheDocument();
        });

        it('should show expected answer count hint', () => {
            render(<Take quiz={multiAnswerQuiz} attempt={mockAttempt} />);

            expect(screen.getByText(/3 réponses attendues/)).toBeInTheDocument();
        });

        it('should toggle checkbox on row click without double-firing', async () => {
            const user = userEvent.setup();
            render(<Take quiz={multiAnswerQuiz} attempt={mockAttempt} />);

            // Click the row text (not the checkbox directly)
            await user.click(screen.getByText('Dog'));

            await waitFor(() => {
                const saved = localStorage.getItem('quiz_1_answers');
                expect(saved).toBeTruthy();
                const parsed = JSON.parse(saved!);
                // Should contain exactly one "Dog" entry, NOT duplicates
                expect(parsed['10']).toEqual(['Dog']);
            });
        });

        it('should not create duplicate entries when clicking checkbox option', async () => {
            const user = userEvent.setup();
            render(<Take quiz={multiAnswerQuiz} attempt={mockAttempt} />);

            // Click the option row
            await user.click(screen.getByText('Cat'));

            await waitFor(() => {
                const saved = JSON.parse(localStorage.getItem('quiz_1_answers')!);
                const catCount = saved['10'].filter((v: string) => v === 'Cat').length;
                expect(catCount).toBe(1);
            });
        });

        it('should allow selecting multiple options', async () => {
            const user = userEvent.setup();
            render(<Take quiz={multiAnswerQuiz} attempt={mockAttempt} />);

            await user.click(screen.getByText('Dog'));
            await user.click(screen.getByText('Cat'));
            await user.click(screen.getByText('Fish'));

            await waitFor(() => {
                const saved = JSON.parse(localStorage.getItem('quiz_1_answers')!);
                expect(saved['10']).toEqual(expect.arrayContaining(['Dog', 'Cat', 'Fish']));
                expect(saved['10']).toHaveLength(3);
            });
        });

        it('should deselect an option when clicking it again', async () => {
            const user = userEvent.setup();
            render(<Take quiz={multiAnswerQuiz} attempt={mockAttempt} />);

            // Select Dog
            await user.click(screen.getByText('Dog'));

            await waitFor(() => {
                const saved = JSON.parse(localStorage.getItem('quiz_1_answers')!);
                expect(saved['10']).toEqual(['Dog']);
            });

            // Deselect Dog
            await user.click(screen.getByText('Dog'));

            await waitFor(() => {
                const saved = JSON.parse(localStorage.getItem('quiz_1_answers')!);
                expect(saved['10']).toEqual([]);
            });
        });

        it('should count multi-answer question as answered when at least one option is selected', async () => {
            const user = userEvent.setup();
            render(<Take quiz={multiAnswerQuiz} attempt={mockAttempt} />);

            expect(screen.getByText(/0\/1 répondu\(es\)/)).toBeInTheDocument();

            await user.click(screen.getByText('Dog'));

            await waitFor(() => {
                expect(screen.getByText(/1\/1 répondu\(es\)/)).toBeInTheDocument();
            });
        });
    });

    describe('Answer Selection - True/False', () => {
        it('should render true and false options', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByLabelText('Vrai')).toBeInTheDocument();
            expect(screen.getByLabelText('Faux')).toBeInTheDocument();
        });
    });

    describe('Answer Selection - Short Answer', () => {
        it('should allow typing a short answer', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            const input = screen.getByPlaceholderText('Votre réponse...');
            await user.type(input, 'Paris');

            expect(input).toHaveValue('Paris');
        });
    });

    describe('Answer Progress Tracking', () => {
        it('should show 0/3 answered initially', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByText(/0\/3 répondu\(es\)/)).toBeInTheDocument();
        });
    });

    describe('LocalStorage Persistence', () => {
        it('should save answers to localStorage', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            const input = screen.getByPlaceholderText('Votre réponse...');
            await user.type(input, 'Paris');

            await waitFor(() => {
                const saved = localStorage.getItem('quiz_1_answers');
                expect(saved).toBeTruthy();
                const parsed = JSON.parse(saved!);
                expect(parsed['3']).toBe('Paris');
            });
        });

        it('should load saved answers from localStorage on mount', () => {
            localStorage.setItem(
                'quiz_1_answers',
                JSON.stringify({
                    3: 'Paris',
                })
            );

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByPlaceholderText('Votre réponse...')).toHaveValue('Paris');
        });
    });

    describe('Quiz Submission', () => {
        it('should show confirmation dialog when submitting with unanswered questions', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            const submitButton = screen.getByRole('button', { name: /soumettre le quiz/i });
            await user.click(submitButton);

            // AlertDialog should appear with unanswered warning
            await waitFor(() => {
                expect(screen.getByText(/Questions sans réponse/)).toBeInTheDocument();
            });
        });

        it('should submit when confirming dialog with unanswered questions', async () => {
            const user = userEvent.setup();
            const routerPostMock = vi.mocked(router.post);

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Submit without answering
            await user.click(screen.getByRole('button', { name: /soumettre le quiz/i }));

            // Confirm in dialog
            await waitFor(() => {
                expect(screen.getByText(/Soumettre quand même/)).toBeInTheDocument();
            });
            await user.click(screen.getByText(/Soumettre quand même/));

            await waitFor(() => {
                expect(routerPostMock).toHaveBeenCalledWith(
                    '/quiz-attempts/attempt-uuid-456/submit',
                    {
                        answers: [
                            { question_id: 1, answer: null },
                            { question_id: 2, answer: null },
                            { question_id: 3, answer: null },
                        ],
                    },
                    expect.any(Object)
                );
            });
        });

        it('should not submit when cancelling dialog', async () => {
            const user = userEvent.setup();
            const routerPostMock = vi.mocked(router.post);

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            await user.click(screen.getByRole('button', { name: /soumettre le quiz/i }));

            await waitFor(() => {
                expect(screen.getByText(/Annuler/)).toBeInTheDocument();
            });
            await user.click(screen.getByText(/Annuler/));

            expect(routerPostMock).not.toHaveBeenCalled();
        });

        it('should submit quiz with short answer directly when all questions answered', async () => {
            const shortAnswerOnlyQuiz = {
                ...mockQuiz,
                questions: [
                    {
                        id: 3,
                        question: 'Capital of France?',
                        type: 'short_answer' as const,
                        options: null,
                        points: 10,
                        correct_answers_count: 1,
                    },
                ],
            };
            const user = userEvent.setup();
            const routerPostMock = vi.mocked(router.post);

            render(<Take quiz={shortAnswerOnlyQuiz} attempt={mockAttempt} />);

            await user.type(screen.getByPlaceholderText('Votre réponse...'), 'Paris');

            await user.click(screen.getByRole('button', { name: /soumettre le quiz/i }));

            await waitFor(() => {
                expect(routerPostMock).toHaveBeenCalledWith(
                    '/quiz-attempts/attempt-uuid-456/submit',
                    {
                        answers: [
                            { question_id: 3, answer: 'Paris' },
                        ],
                    },
                    expect.any(Object)
                );
            });
        });

        it('should disable submit button while submitting', async () => {
            const shortAnswerOnlyQuiz = {
                ...mockQuiz,
                questions: [
                    {
                        id: 3,
                        question: 'Capital of France?',
                        type: 'short_answer' as const,
                        options: null,
                        points: 10,
                        correct_answers_count: 1,
                    },
                ],
            };
            const user = userEvent.setup();
            vi.mocked(router.post).mockImplementation(() => undefined as any);

            render(<Take quiz={shortAnswerOnlyQuiz} attempt={mockAttempt} />);

            await user.type(screen.getByPlaceholderText('Votre réponse...'), 'Paris');

            const submitButton = screen.getByRole('button', { name: /soumettre le quiz/i });
            await user.click(submitButton);

            await waitFor(() => {
                expect(submitButton).toBeDisabled();
                expect(screen.getByText('Soumission...')).toBeInTheDocument();
            });
        });
    });

    describe('Auto-submission on Time Up', () => {
        it('should submit quiz when time expires', async () => {
            const routerPostMock = vi.mocked(router.post);

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Trigger the onTimeUp callback stored by the mock hook
            const onTimeUp = (globalThis as any).__quizTimerOnTimeUp;
            expect(onTimeUp).toBeDefined();
            onTimeUp();

            await waitFor(() => {
                expect(routerPostMock).toHaveBeenCalled();
            });
        });
    });
});
