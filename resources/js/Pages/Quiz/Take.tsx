import { useState, useEffect, FormEvent } from 'react';
import { Head, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Separator } from '@/Components/ui/separator';
import QuizTimer from '@/Components/Quiz/QuizTimer';
import { AlertCircle, CheckCircle2, Send } from 'lucide-react';
import { toast } from 'sonner';

interface Question {
    id: number;
    question: string;
    type: 'multiple_choice' | 'true_false' | 'short_answer';
    options: string[] | null;
    points: number;
    correct_answers_count: number;
}

interface Quiz {
    id: number;
    uuid: string;
    title: string;
    description: string | null;
    duration_minutes: number;
    max_score: number;
    passing_score: number;
    questions: Question[];
}

interface Attempt {
    id: number;
    uuid: string;
    started_at: string;
    time_remaining_seconds: number;
}

interface Props {
    quiz: Quiz;
    attempt: Attempt;
}

export default function TakeQuiz({ quiz, attempt }: Props) {
    const [answers, setAnswers] = useState<Record<number, any>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Auto-save answers to localStorage
    useEffect(() => {
        const savedAnswers = localStorage.getItem(`quiz_${quiz.id}_answers`);
        if (savedAnswers) {
            try {
                setAnswers(JSON.parse(savedAnswers));
            } catch (e) {
                console.error('Failed to load saved answers', e);
            }
        }
    }, [quiz.id]);

    useEffect(() => {
        localStorage.setItem(`quiz_${quiz.id}_answers`, JSON.stringify(answers));
    }, [answers, quiz.id]);

    const handleAnswerChange = (questionId: number, answer: string | boolean | string[]) => {
        setAnswers(prev => ({
            ...prev,
            [questionId]: answer
        }));
    };

    const handleCheckboxToggle = (questionId: number, option: string, checked: boolean) => {
        setAnswers(prev => {
            const current: string[] = Array.isArray(prev[questionId]) ? prev[questionId] : [];
            const updated = checked
                ? [...current, option]
                : current.filter((v: string) => v !== option);
            return { ...prev, [questionId]: updated };
        });
    };

    const handleTimeUp = () => {
        toast.error('Temps écoulé!', {
            description: 'Le quiz est soumis automatiquement.'
        });
        submitQuiz();
    };

    const submitQuiz = () => {
        if (isSubmitting) return;

        setIsSubmitting(true);

        const formattedAnswers = quiz.questions.map(q => ({
            question_id: q.id,
            answer: answers[q.id] ?? null
        }));

        router.post(route('quiz-attempts.submit', attempt.uuid), {
            answers: formattedAnswers
        }, {
            onSuccess: () => {
                localStorage.removeItem(`quiz_${quiz.id}_answers`);
                toast.success('Quiz soumis avec succès!');
            },
            onError: (errors) => {
                setIsSubmitting(false);
                toast.error('Erreur', {
                    description: 'Une erreur est survenue lors de la soumission.'
                });
            }
        });
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // Check if all questions are answered
        const unansweredCount = quiz.questions.filter(q => !isQuestionAnswered(q.id)).length;

        if (unansweredCount > 0) {
            const confirmSubmit = window.confirm(
                `Vous n'avez pas répondu à ${unansweredCount} question(s). Voulez-vous quand même soumettre le quiz?`
            );
            if (!confirmSubmit) return;
        }

        submitQuiz();
    };

    const isQuestionAnswered = (questionId: number) => {
        const answer = answers[questionId];
        if (Array.isArray(answer)) {
            return answer.length > 0;
        }
        return answer !== undefined && answer !== null && answer !== '';
    };

    const answeredCount = quiz.questions.filter(q => isQuestionAnswered(q.id)).length;

    return (
        <DashboardLayout>
            <Head title={`Quiz: ${quiz.title}`} />

            <div className="mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Timer */}
                <QuizTimer
                    startedAt={attempt.started_at}
                    durationMinutes={quiz.duration_minutes}
                    onTimeUp={handleTimeUp}
                    className="mb-6"
                />

                {/* Quiz Header */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>{quiz.title}</CardTitle>
                        {quiz.description && (
                            <CardDescription>{quiz.description}</CardDescription>
                        )}
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between text-sm">
                            <div className="flex items-center gap-4">
                                <span className="text-gray-600 dark:text-gray-400">
                                    Questions: {quiz.questions.length}
                                </span>
                                <span className="text-gray-600 dark:text-gray-400">
                                    Points total: {quiz.max_score}
                                </span>
                                <span className="text-gray-600 dark:text-gray-400">
                                    Score minimum: {quiz.passing_score}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <CheckCircle2 className="h-4 w-4 text-green-500" />
                                <span className="font-medium">
                                    {answeredCount}/{quiz.questions.length} répondu(es)
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Questions */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {quiz.questions.map((question, index) => (
                        <Card key={question.id} className={isQuestionAnswered(question.id) ? 'border-green-200 dark:border-green-800' : ''}>
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <CardTitle className="text-lg">
                                        Question {index + 1}
                                        {isQuestionAnswered(question.id) && (
                                            <CheckCircle2 className="inline-block ml-2 h-5 w-5 text-green-500" />
                                        )}
                                    </CardTitle>
                                    <span className="text-sm text-gray-600 dark:text-gray-400">
                                        {question.points} point{question.points > 1 ? 's' : ''}
                                    </span>
                                </div>
                                <CardDescription className="text-base text-gray-900 dark:text-white mt-2">
                                    {question.question}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {question.type === 'multiple_choice' && question.options && question.correct_answers_count > 1 && (
                                    <div className="space-y-3">
                                        <p className="text-sm text-amber-600 dark:text-amber-400 font-medium">
                                            Sélectionnez toutes les bonnes réponses ({question.correct_answers_count} réponses attendues)
                                        </p>
                                        {question.options.map((option, optionIndex) => {
                                            const selected: string[] = Array.isArray(answers[question.id]) ? answers[question.id] : [];
                                            const isChecked = selected.includes(option);
                                            return (
                                                <div
                                                    key={optionIndex}
                                                    className={`flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer ${
                                                        isChecked ? 'border-blue-300 bg-blue-50/50 dark:border-blue-700 dark:bg-blue-900/20' : ''
                                                    }`}
                                                    onClick={() => handleCheckboxToggle(question.id, option, !isChecked)}
                                                >
                                                    <Checkbox
                                                        id={`q${question.id}-opt${optionIndex}`}
                                                        checked={isChecked}
                                                        onCheckedChange={(checked) =>
                                                            handleCheckboxToggle(question.id, option, checked === true)
                                                        }
                                                        onClick={(e) => e.stopPropagation()}
                                                    />
                                                    <Label
                                                        htmlFor={`q${question.id}-opt${optionIndex}`}
                                                        className="flex-1 cursor-pointer"
                                                    >
                                                        {option}
                                                    </Label>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}

                                {question.type === 'multiple_choice' && question.options && question.correct_answers_count <= 1 && (
                                    <RadioGroup
                                        value={answers[question.id]?.toString() || ''}
                                        onValueChange={(value) => handleAnswerChange(question.id, value)}
                                    >
                                        <div className="space-y-3">
                                            {question.options.map((option, optionIndex) => (
                                                <div key={optionIndex} className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                    <RadioGroupItem value={option} id={`q${question.id}-opt${optionIndex}`} />
                                                    <Label
                                                        htmlFor={`q${question.id}-opt${optionIndex}`}
                                                        className="flex-1 cursor-pointer"
                                                    >
                                                        {option}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                    </RadioGroup>
                                )}

                                {question.type === 'true_false' && (
                                    <RadioGroup
                                        value={answers[question.id]?.toString() || ''}
                                        onValueChange={(value) => handleAnswerChange(question.id, value === 'true')}
                                    >
                                        <div className="space-y-3">
                                            <div className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                <RadioGroupItem value="true" id={`q${question.id}-true`} />
                                                <Label htmlFor={`q${question.id}-true`} className="flex-1 cursor-pointer">
                                                    Vrai
                                                </Label>
                                            </div>
                                            <div className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                <RadioGroupItem value="false" id={`q${question.id}-false`} />
                                                <Label htmlFor={`q${question.id}-false`} className="flex-1 cursor-pointer">
                                                    Faux
                                                </Label>
                                            </div>
                                        </div>
                                    </RadioGroup>
                                )}

                                {question.type === 'short_answer' && (
                                    <Input
                                        type="text"
                                        value={answers[question.id] || ''}
                                        onChange={(e) => handleAnswerChange(question.id, e.target.value)}
                                        placeholder="Votre réponse..."
                                        className="w-full"
                                    />
                                )}
                            </CardContent>
                        </Card>
                    ))}

                    <Separator />

                    {/* Submit Button */}
                    <Card className="bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                        <CardContent className="pt-6">
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex items-start gap-3">
                                    <AlertCircle className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                                    <div className="text-sm text-gray-700 dark:text-gray-300">
                                        <p className="font-medium">Avant de soumettre:</p>
                                        <ul className="mt-2 space-y-1 list-disc list-inside">
                                            <li>Vérifiez toutes vos réponses</li>
                                            <li>Une fois soumis, vous ne pourrez plus modifier vos réponses</li>
                                            <li>Le quiz sera automatiquement soumis à la fin du temps imparti</li>
                                        </ul>
                                    </div>
                                </div>
                                <Button
                                    type="submit"
                                    size="lg"
                                    disabled={isSubmitting}
                                    className="bg-green-600 hover:bg-green-700 shrink-0"
                                >
                                    {isSubmitting ? (
                                        <>Soumission...</>
                                    ) : (
                                        <>
                                            <Send className="h-4 w-4 mr-2" />
                                            Soumettre le quiz
                                        </>
                                    )}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </DashboardLayout>
    );
}
