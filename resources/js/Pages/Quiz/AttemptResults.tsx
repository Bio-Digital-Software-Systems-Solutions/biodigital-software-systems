import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Separator } from '@/Components/ui/separator';
import { CheckCircle2, XCircle, Award, Clock, ArrowLeft, BookOpen } from 'lucide-react';

interface Training {
    id: number;
    uuid: string;
    title: string;
}

interface Quiz {
    id: number;
    uuid: string;
    title: string;
    description: string | null;
}

interface Attempt {
    id: number;
    uuid: string;
    score: number;
    max_score: number;
    passing_score: number;
    percentage: number;
    passed: boolean;
    started_at: string;
    completed_at: string;
    time_taken: string;
}

interface Student {
    name: string;
}

interface QuestionWithAnswer {
    question: string;
    type: string;
    options: string[] | null;
    student_answer: any;
    is_correct: boolean;
    points_earned: number;
    max_points: number;
    feedback: string | null;
}

interface Props {
    attempt: Attempt;
    quiz: Quiz;
    training: Training;
    student: Student;
    questionsWithAnswers: QuestionWithAnswer[];
}

export default function AttemptResults({ attempt, quiz, training, student, questionsWithAnswers }: Props) {
    const formatAnswer = (answer: any, type: string, options: string[] | null) => {
        if (answer === null || answer === undefined) {
            return <span className="text-gray-400 italic">Pas de réponse</span>;
        }

        if (type === 'true_false') {
            return <span className="font-medium">{answer ? 'Vrai' : 'Faux'}</span>;
        }

        if (type === 'multiple_choice') {
            if (Array.isArray(answer)) {
                return (
                    <div className="flex flex-wrap gap-2">
                        {answer.map((ans, idx) => (
                            <Badge key={idx} variant="secondary">{ans}</Badge>
                        ))}
                    </div>
                );
            }
            return <Badge variant="secondary">{answer}</Badge>;
        }

        return <span className="font-medium">{answer}</span>;
    };

    return (
        <DashboardLayout>
            <Head title={`Résultats - ${quiz.title}`} />

            <div className="mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-8">
                    <Link
                        href={route('trainings.show', training.uuid)}
                        className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Retour à la formation
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        Résultats du quiz
                    </h1>
                    <p className="mt-2 text-gray-600 dark:text-gray-400">
                        {quiz.title} - {training.title}
                    </p>
                </div>

                {/* Results Summary */}
                <Card className="mb-8">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-3">
                                    {attempt.passed ? (
                                        <div className="flex items-center gap-2 text-green-600">
                                            <CheckCircle2 className="h-6 w-6" />
                                            <span>Quiz réussi !</span>
                                        </div>
                                    ) : (
                                        <div className="flex items-center gap-2 text-red-600">
                                            <XCircle className="h-6 w-6" />
                                            <span>Quiz non réussi</span>
                                        </div>
                                    )}
                                </CardTitle>
                                <CardDescription className="mt-2">
                                    {student.name}
                                </CardDescription>
                            </div>
                            <div className="text-right">
                                <div className="text-4xl font-bold text-gray-900 dark:text-white">
                                    {attempt.percentage}%
                                </div>
                                <div className="text-sm text-gray-500">
                                    {attempt.score} / {attempt.max_score} points
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <Clock className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                <div>
                                    <div className="text-sm text-gray-600 dark:text-gray-400">Temps écoulé</div>
                                    <div className="font-semibold text-gray-900 dark:text-white">{attempt.time_taken}</div>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <CheckCircle2 className="h-5 w-5 text-green-600 dark:text-green-400" />
                                <div>
                                    <div className="text-sm text-gray-600 dark:text-gray-400">Bonnes réponses</div>
                                    <div className="font-semibold text-gray-900 dark:text-white">
                                        {questionsWithAnswers.filter(q => q.is_correct).length} / {questionsWithAnswers.length}
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <Award className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                <div>
                                    <div className="text-sm text-gray-600 dark:text-gray-400">Score requis</div>
                                    <div className="font-semibold text-gray-900 dark:text-white">{attempt.passing_score}%</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Questions with Feedback */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BookOpen className="h-5 w-5" />
                            Détail des réponses
                        </CardTitle>
                        <CardDescription>
                            Revoyez vos réponses et consultez les feedbacks
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {questionsWithAnswers.map((qa, index) => (
                            <div key={index}>
                                <Card className={`border-2 ${
                                    qa.is_correct
                                        ? 'border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10'
                                        : 'border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10'
                                }`}>
                                    <CardHeader className="pb-3">
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <Badge variant={qa.is_correct ? 'default' : 'destructive'} className="shrink-0">
                                                        Question {index + 1}
                                                    </Badge>
                                                    <div className="flex items-center gap-2">
                                                        {qa.is_correct ? (
                                                            <CheckCircle2 className="h-5 w-5 text-green-600 dark:text-green-400" />
                                                        ) : (
                                                            <XCircle className="h-5 w-5 text-red-600 dark:text-red-400" />
                                                        )}
                                                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            {qa.points_earned} / {qa.max_points} points
                                                        </span>
                                                    </div>
                                                </div>
                                                <CardTitle className="text-lg font-normal text-gray-900 dark:text-white">
                                                    {qa.question}
                                                </CardTitle>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <div>
                                            <div className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Votre réponse :
                                            </div>
                                            <div className="pl-4">
                                                {formatAnswer(qa.student_answer, qa.type, qa.options)}
                                            </div>
                                        </div>

                                        {qa.feedback && (
                                            <>
                                                <Separator />
                                                <div className={`p-4 rounded-lg ${
                                                    qa.is_correct
                                                        ? 'bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700'
                                                        : 'bg-blue-100 dark:bg-blue-900/30 border border-blue-300 dark:border-blue-700'
                                                }`}>
                                                    <div className="flex items-start gap-3">
                                                        <BookOpen className={`h-5 w-5 mt-0.5 shrink-0 ${
                                                            qa.is_correct
                                                                ? 'text-green-700 dark:text-green-400'
                                                                : 'text-blue-700 dark:text-blue-400'
                                                        }`} />
                                                        <div>
                                                            <div className={`text-sm font-medium mb-1 ${
                                                                qa.is_correct
                                                                    ? 'text-green-900 dark:text-green-300'
                                                                    : 'text-blue-900 dark:text-blue-300'
                                                            }`}>
                                                                Feedback
                                                            </div>
                                                            <div className={`text-sm ${
                                                                qa.is_correct
                                                                    ? 'text-green-800 dark:text-green-200'
                                                                    : 'text-blue-800 dark:text-blue-200'
                                                            }`}>
                                                                {qa.feedback}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {/* Action Buttons */}
                <div className="mt-8 flex items-center justify-between">
                    <Link href={route('trainings.show', training.uuid)}>
                        <Button variant="outline">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Retour à la formation
                        </Button>
                    </Link>

                    {!attempt.passed && (
                        <div className="text-sm text-gray-600 dark:text-gray-400">
                            Continuez à étudier et contactez votre formateur si nécessaire
                        </div>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}
