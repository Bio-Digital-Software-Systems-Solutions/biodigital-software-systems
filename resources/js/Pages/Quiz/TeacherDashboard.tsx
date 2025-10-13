import { Head } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { BarChart3, BookOpen, Users, TrendingUp, CheckCircle2, XCircle, Clock, FileText } from 'lucide-react';

interface Statistics {
    total_quizzes: number;
    draft_quizzes: number;
    published_quizzes: number;
    archived_quizzes: number;
    total_questions: number;
    total_attempts: number;
    average_score: number;
    pass_rate: number;
}

interface RecentAttempt {
    id: number;
    student_name: string;
    quiz_title: string;
    training_name: string;
    score: number;
    max_score: number;
    percentage: number;
    passed: boolean;
    completed_at: string;
}

interface QuizPerformance {
    uuid: string;
    title: string;
    training_name: string;
    status: string;
    total_attempts: number;
    passed_attempts: number;
    pass_rate: number;
    average_score: number;
}

interface Props {
    statistics: Statistics;
    recentAttempts: RecentAttempt[];
    quizPerformance: QuizPerformance[];
}

export default function TeacherDashboard({ statistics, recentAttempts, quizPerformance }: Props) {
    const getStatusBadge = (status: string) => {
        const styles = {
            draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
            published: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            archived: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        };
        const labels = {
            draft: 'Brouillon',
            published: 'Publié',
            archived: 'Archivé',
        };
        return (
            <Badge className={styles[status as keyof typeof styles] || styles.draft}>
                {labels[status as keyof typeof labels] || status}
            </Badge>
        );
    };

    return (
        <DashboardLayout>
            <Head title="Dashboard Professeur - Quiz" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        Dashboard Professeur - Quiz
                    </h1>
                    <p className="mt-2 text-gray-600 dark:text-gray-400">
                        Vue d'ensemble de tous vos quiz et statistiques globales
                    </p>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    {/* Total Quizzes */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Quiz</CardTitle>
                            <FileText className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.total_quizzes}</div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {statistics.published_quizzes} publiés • {statistics.draft_quizzes} brouillons
                            </p>
                        </CardContent>
                    </Card>

                    {/* Total Questions */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Questions</CardTitle>
                            <BookOpen className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.total_questions}</div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Dans tous les quiz
                            </p>
                        </CardContent>
                    </Card>

                    {/* Total Attempts */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Tentatives</CardTitle>
                            <Users className="h-4 w-4 text-orange-600 dark:text-orange-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.total_attempts}</div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Quiz complétés
                            </p>
                        </CardContent>
                    </Card>

                    {/* Pass Rate */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Taux de Réussite</CardTitle>
                            <TrendingUp className="h-4 w-4 text-green-600 dark:text-green-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.pass_rate}%</div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Score moyen: {statistics.average_score}%
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {/* Recent Attempts */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Tentatives Récentes</CardTitle>
                                    <CardDescription>
                                        Les 10 derniers quiz complétés
                                    </CardDescription>
                                </div>
                                <Clock className="h-5 w-5 text-gray-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            {recentAttempts.length === 0 ? (
                                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                    Aucune tentative récente
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {recentAttempts.map((attempt) => (
                                        <div
                                            key={attempt.id}
                                            className="flex items-start justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                        >
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <p className="font-medium text-sm text-gray-900 dark:text-white">
                                                        {attempt.student_name}
                                                    </p>
                                                    {attempt.passed ? (
                                                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                                                    ) : (
                                                        <XCircle className="h-4 w-4 text-red-600" />
                                                    )}
                                                </div>
                                                <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                    {attempt.quiz_title}
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-gray-500 mt-0.5">
                                                    {attempt.training_name}
                                                </p>
                                            </div>
                                            <div className="text-right ml-4">
                                                <p className="text-sm font-bold text-gray-900 dark:text-white">
                                                    {attempt.percentage}%
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {attempt.score}/{attempt.max_score}
                                                </p>
                                                <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                    {attempt.completed_at}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Quiz Performance */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Performance des Quiz</CardTitle>
                                    <CardDescription>
                                        Top 10 quiz par nombre de tentatives
                                    </CardDescription>
                                </div>
                                <BarChart3 className="h-5 w-5 text-gray-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            {quizPerformance.length === 0 ? (
                                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                    Aucun quiz disponible
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {quizPerformance.map((quiz) => (
                                        <div
                                            key={quiz.uuid}
                                            className="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                        >
                                            <div className="flex items-start justify-between mb-2">
                                                <div className="flex-1">
                                                    <p className="font-medium text-sm text-gray-900 dark:text-white">
                                                        {quiz.title}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                        {quiz.training_name}
                                                    </p>
                                                </div>
                                                {getStatusBadge(quiz.status)}
                                            </div>
                                            <div className="grid grid-cols-3 gap-2 text-center mt-3">
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        Tentatives
                                                    </p>
                                                    <p className="text-sm font-semibold text-gray-900 dark:text-white">
                                                        {quiz.total_attempts}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        Taux réussite
                                                    </p>
                                                    <p className="text-sm font-semibold text-green-600 dark:text-green-400">
                                                        {quiz.pass_rate}%
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        Score moyen
                                                    </p>
                                                    <p className="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                                        {quiz.average_score}%
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}
