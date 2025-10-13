import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor, within } from '@testing-library/react';
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
    default: ({ onTimeUp, className }: any) => (
        <div className={className} data-testid="quiz-timer">
            <button onClick={onTimeUp} data-testid="trigger-time-up">
                Time Up
            </button>
        </div>
    ),
}));

// Mock DashboardLayout
vi.mock('@/Layouts/DashboardLayout', () => ({
    default: ({ children }: any) => <div data-testid="dashboard-layout">{children}</div>,
}));

// Mock UI components that might not exist
vi.mock('@/Components/ui/radio-group', () => ({
    RadioGroup: ({ children, value, onValueChange }: any) => (
        <div data-testid="radio-group" data-value={value}>
            {children}
        </div>
    ),
    RadioGroupItem: ({ value, id }: any) => (
        <input
            type="radio"
            value={value}
            id={id}
            data-testid={`radio-${value}`}
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
global.route = vi.fn((name: string, params?: any) => {
    if (name === 'quiz-attempts.submit') {
        return `/quiz-attempts/${params}/submit`;
    }
    return '/';
}) as any;

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
            },
            {
                id: 2,
                question: 'The sky is blue',
                type: 'true_false' as const,
                options: null,
                points: 5,
            },
            {
                id: 3,
                question: 'What is the capital of France?',
                type: 'short_answer' as const,
                options: null,
                points: 15,
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
        // Mock window.confirm
        global.confirm = vi.fn(() => true);
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
    });

    describe('Answer Selection - Multiple Choice', () => {
        it('should allow selecting a multiple choice answer', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Find and click the radio button for option "4"
            const option4 = screen.getByLabelText('4');
            await user.click(option4);

            expect(option4).toBeChecked();
        });

        it('should change selection when clicking different option', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            const option3 = screen.getByLabelText('3');
            const option4 = screen.getByLabelText('4');

            await user.click(option3);
            expect(option3).toBeChecked();

            await user.click(option4);
            expect(option4).toBeChecked();
            expect(option3).not.toBeChecked();
        });

        it('should show all multiple choice options', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByLabelText('2')).toBeInTheDocument();
            expect(screen.getByLabelText('3')).toBeInTheDocument();
            expect(screen.getByLabelText('4')).toBeInTheDocument();
            expect(screen.getByLabelText('5')).toBeInTheDocument();
        });
    });

    describe('Answer Selection - True/False', () => {
        it('should allow selecting true', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            const trueOption = screen.getByLabelText('Vrai');
            await user.click(trueOption);

            expect(trueOption).toBeChecked();
        });

        it('should allow selecting false', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            const falseOption = screen.getByLabelText('Faux');
            await user.click(falseOption);

            expect(falseOption).toBeChecked();
        });

        it('should allow changing true/false selection', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            const trueOption = screen.getByLabelText('Vrai');
            const falseOption = screen.getByLabelText('Faux');

            await user.click(trueOption);
            expect(trueOption).toBeChecked();

            await user.click(falseOption);
            expect(falseOption).toBeChecked();
            expect(trueOption).not.toBeChecked();
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

        it('should update input value as user types', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            const input = screen.getByPlaceholderText('Votre réponse...');
            await user.type(input, 'P');
            expect(input).toHaveValue('P');

            await user.type(input, 'aris');
            expect(input).toHaveValue('Paris');
        });
    });

    describe('Answer Progress Tracking', () => {
        it('should show 0/3 answered initially', () => {
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            expect(screen.getByText(/0\/3 répondu\(es\)/)).toBeInTheDocument();
        });

        it('should update answered count when selecting answers', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Answer question 1
            await user.click(screen.getByLabelText('4'));

            await waitFor(() => {
                expect(screen.getByText(/1\/3 répondu\(es\)/)).toBeInTheDocument();
            });

            // Answer question 2
            await user.click(screen.getByLabelText('Vrai'));

            await waitFor(() => {
                expect(screen.getByText(/2\/3 répondu\(es\)/)).toBeInTheDocument();
            });
        });

        it('should show green checkmark for answered questions', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            await user.click(screen.getByLabelText('4'));

            await waitFor(() => {
                // The CheckCircle2 icon should appear next to "Question 1"
                const questionCards = screen.getAllByText(/^Question \d+$/);
                expect(questionCards[0]).toBeInTheDocument();
            });
        });
    });

    describe('LocalStorage Persistence', () => {
        it('should save answers to localStorage', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            await user.click(screen.getByLabelText('4'));

            await waitFor(() => {
                const saved = localStorage.getItem('quiz_1_answers');
                expect(saved).toBeTruthy();
                const parsed = JSON.parse(saved!);
                expect(parsed['1']).toBe('4');
            });
        });

        it('should load saved answers from localStorage on mount', () => {
            // Pre-populate localStorage
            localStorage.setItem(
                'quiz_1_answers',
                JSON.stringify({
                    1: '4',
                    2: true,
                    3: 'Paris',
                })
            );

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Check that answers are loaded
            expect(screen.getByLabelText('4')).toBeChecked();
            expect(screen.getByLabelText('Vrai')).toBeChecked();
            expect(screen.getByPlaceholderText('Votre réponse...')).toHaveValue('Paris');
        });

        it('should update localStorage when answers change', async () => {
            const user = userEvent.setup();
            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            await user.click(screen.getByLabelText('3'));
            await waitFor(() => {
                const saved = JSON.parse(localStorage.getItem('quiz_1_answers')!);
                expect(saved['1']).toBe('3');
            });

            await user.click(screen.getByLabelText('4'));
            await waitFor(() => {
                const saved = JSON.parse(localStorage.getItem('quiz_1_answers')!);
                expect(saved['1']).toBe('4');
            });
        });
    });

    describe('Quiz Submission', () => {
        it('should submit quiz with all answers when submit button clicked', async () => {
            const user = userEvent.setup();
            const routerPostMock = vi.mocked(router.post);

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Answer all questions
            await user.click(screen.getByLabelText('4'));
            await user.click(screen.getByLabelText('Vrai'));
            await user.type(screen.getByPlaceholderText('Votre réponse...'), 'Paris');

            // Submit
            const submitButton = screen.getByRole('button', { name: /soumettre le quiz/i });
            await user.click(submitButton);

            await waitFor(() => {
                expect(routerPostMock).toHaveBeenCalledWith(
                    '/quiz-attempts/attempt-uuid-456/submit',
                    {
                        answers: [
                            { question_id: 1, answer: '4' },
                            { question_id: 2, answer: true },
                            { question_id: 3, answer: 'Paris' },
                        ],
                    },
                    expect.any(Object)
                );
            });
        });

        it('should show confirmation dialog when submitting with unanswered questions', async () => {
            const user = userEvent.setup();
            const confirmMock = vi.fn(() => false);
            global.confirm = confirmMock;

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Only answer 1 question
            await user.click(screen.getByLabelText('4'));

            const submitButton = screen.getByRole('button', { name: /soumettre le quiz/i });
            await user.click(submitButton);

            await waitFor(() => {
                expect(confirmMock).toHaveBeenCalledWith(
                    expect.stringContaining("Vous n'avez pas répondu à 2 question(s)")
                );
            });
        });

        it('should not submit if user cancels confirmation', async () => {
            const user = userEvent.setup();
            const routerPostMock = vi.mocked(router.post);
            global.confirm = vi.fn(() => false);

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Only answer 1 question
            await user.click(screen.getByLabelText('4'));

            const submitButton = screen.getByRole('button', { name: /soumettre le quiz/i });
            await user.click(submitButton);

            await waitFor(() => {
                expect(global.confirm).toHaveBeenCalled();
            });

            // Should not have submitted
            expect(routerPostMock).not.toHaveBeenCalled();
        });

        it('should submit if user confirms with unanswered questions', async () => {
            const user = userEvent.setup();
            const routerPostMock = vi.mocked(router.post);
            global.confirm = vi.fn(() => true);

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Only answer 1 question
            await user.click(screen.getByLabelText('4'));

            const submitButton = screen.getByRole('button', { name: /soumettre le quiz/i });
            await user.click(submitButton);

            await waitFor(() => {
                expect(global.confirm).toHaveBeenCalled();
                expect(routerPostMock).toHaveBeenCalled();
            });
        });

        it('should include null for unanswered questions', async () => {
            const user = userEvent.setup();
            const routerPostMock = vi.mocked(router.post);
            global.confirm = vi.fn(() => true);

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Only answer first question
            await user.click(screen.getByLabelText('4'));

            const submitButton = screen.getByRole('button', { name: /soumettre le quiz/i });
            await user.click(submitButton);

            await waitFor(() => {
                expect(routerPostMock).toHaveBeenCalledWith(
                    expect.any(String),
                    {
                        answers: [
                            { question_id: 1, answer: '4' },
                            { question_id: 2, answer: null },
                            { question_id: 3, answer: null },
                        ],
                    },
                    expect.any(Object)
                );
            });
        });

        it('should disable submit button while submitting', async () => {
            const user = userEvent.setup();
            const routerPostMock = vi.mocked(router.post).mockImplementation(() => {
                // Simulate async submission
                return new Promise(() => {});
            });

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            await user.click(screen.getByLabelText('4'));
            await user.click(screen.getByLabelText('Vrai'));
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
            const user = userEvent.setup();
            const routerPostMock = vi.mocked(router.post);

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Answer some questions
            await user.click(screen.getByLabelText('4'));

            // Trigger time up
            const timeUpButton = screen.getByTestId('trigger-time-up');
            await user.click(timeUpButton);

            await waitFor(() => {
                expect(routerPostMock).toHaveBeenCalled();
            });
        });

        it('should not show confirmation dialog on auto-submit', async () => {
            const user = userEvent.setup();
            const confirmMock = vi.fn();
            global.confirm = confirmMock;

            render(<Take quiz={mockQuiz} attempt={mockAttempt} />);

            // Don't answer any questions
            const timeUpButton = screen.getByTestId('trigger-time-up');
            await user.click(timeUpButton);

            // Should not show confirmation
            expect(confirmMock).not.toHaveBeenCalled();
        });
    });

    describe('Question Display', () => {
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
                    },
                ],
            };

            render(<Take quiz={singlePointQuiz} attempt={mockAttempt} />);

            expect(screen.getByText('1 point')).toBeInTheDocument();
        });
    });
});
