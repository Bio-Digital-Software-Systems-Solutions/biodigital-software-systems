import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    ArrowLeft,
    Download,
    TrendingUp,
    TrendingDown,
    Users,
    CheckCircle2,
    XCircle,
    Clock,
    Target
} from 'lucide-react';

interface Student {
    id: number;
    name: string;
    email: string;
}

interface Attempt {
    id: number;
    student: Student;
    score: number;
    max_score: number;
    percentage: number;
    passed: boolean;
    started_at: string;
    completed_at: string;
    time_taken: string;
}

interface Quiz {
    id: number;
    uuid: string;
    title: string;
    description: string | null;
    max_score: number;
    passing_score: number;
    questions_count: number;
}

interface Training {
    id: number;
    uuid: string;
    name: string;
}

interface Statistics {
    total_attempts: number;
    passed_count: number;
    failed_count: number;
    average_score: number;
    highest_score: number;
    lowest_score: number;
}

interface Props {
    training: Training;
    quiz: Quiz;
    attempts: Attempt[];
    statistics: Statistics;
}

export default function QuizResults({ training, quiz, attempts, statistics }: Props) {
    const getScoreColor = (percentage: number, passed: boolean) => {
        if (passed) {
            if (percentage >= 90) return 'text-green-600 dark:text-green-400';
            if (percentage >= 80) return 'text-blue-600 dark:text-blue-400';
            return 'text-yellow-600 dark:text-yellow-400';
        }
        return 'text-red-600 dark:text-red-400';
    };

    const getPassRate = () => {
        if (statistics.total_attempts === 0) return 0;
        return ((statistics.passed_count / statistics.total_attempts) * 100).toFixed(1);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Résultats - ${quiz.title}`} />

            <div className="mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-8">
                    <Link
                        href={route('trainings.quizzes.index', training.uuid)}
                        className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Retour à la liste des quiz
                    </Link>
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Résultats du quiz
                            </h1>
                            <p className="mt-2 text-gray-600 dark:text-gray-400">
                                {quiz.title}
                            </p>
                            <p className="text-sm text-gray-500 dark:text-gray-500">
                                Formation: {training.name}
                            </p>
                        </div>
                        <a href={route('trainings.quizzes.export-csv', [training.uuid, quiz.uuid])}>
                            <Button variant="outline">
                                <Download className="h-4 w-4 mr-2" />
                                Exporter CSV
                            </Button>
                        </a>
                    </div>
                </div>

                {/* Quiz Info */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-lg">Informations du quiz</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div className="flex items-center gap-2">
                                <Target className="h-4 w-4 text-gray-500" />
                                <span className="text-gray-600 dark:text-gray-400">
                                    Score max: <span className="font-semibold text-gray-900 dark:text-white">{quiz.max_score} pts</span>
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <CheckCircle2 className="h-4 w-4 text-gray-500" />
                                <span className="text-gray-600 dark:text-gray-400">
                                    Pour réussir: <span className="font-semibold text-gray-900 dark:text-white">{quiz.passing_score} pts</span>
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600 dark:text-gray-400">
                                    Questions: <span className="font-semibold text-gray-900 dark:text-white">{quiz.questions_count}</span>
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                <Users className="h-4 w-4" />
                                Tentatives
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-gray-900 dark:text-white">
                                {statistics.total_attempts}
                            </div>
                            <p className="text-xs text-gray-500 mt-1">
                                {statistics.passed_count} réussi(es), {statistics.failed_count} échoué(es)
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                <Target className="h-4 w-4" />
                                Taux de réussite
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {getPassRate()}%
                            </div>
                            <p className="text-xs text-gray-500 mt-1">
                                {statistics.passed_count} sur {statistics.total_attempts}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                <TrendingUp className="h-4 w-4" />
                                Score moyen
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-gray-900 dark:text-white">
                                {statistics.average_score?.toFixed(1) || 0}%
                            </div>
                            <p className="text-xs text-gray-500 mt-1">
                                Moyenne générale
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                Scores extrêmes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between">
                                <div>
                                    <div className="text-2xl font-bold text-green-600 dark:text-green-400 flex items-center gap-1">
                                        <TrendingUp className="h-4 w-4" />
                                        {statistics.highest_score || 0}
                                    </div>
                                    <p className="text-xs text-gray-500">Maximum</p>
                                </div>
                                <div>
                                    <div className="text-2xl font-bold text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <TrendingDown className="h-4 w-4" />
                                        {statistics.lowest_score || 0}
                                    </div>
                                    <p className="text-xs text-gray-500">Minimum</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Results Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Résultats des étudiants</CardTitle>
                        <CardDescription>
                            Liste de toutes les tentatives complétées
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {attempts.length === 0 ? (
                            <div className="text-center py-12">
                                <Users className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    Aucun résultat
                                </h3>
                                <p className="text-gray-600 dark:text-gray-400">
                                    Aucun étudiant n'a encore complété ce quiz
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Étudiant</TableHead>
                                            <TableHead className="text-center">Score</TableHead>
                                            <TableHead className="text-center">Pourcentage</TableHead>
                                            <TableHead className="text-center">Statut</TableHead>
                                            <TableHead className="text-center">Temps</TableHead>
                                            <TableHead>Complété le</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {attempts.map((attempt) => (
                                            <TableRow key={attempt.id}>
                                                <TableCell>
                                                    <div>
                                                        <div className="font-medium text-gray-900 dark:text-white">
                                                            {attempt.student.name}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {attempt.student.email}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <span className={`font-semibold ${getScoreColor(attempt.percentage, attempt.passed)}`}>
                                                        {attempt.score}/{attempt.max_score}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <span className={`font-semibold ${getScoreColor(attempt.percentage, attempt.passed)}`}>
                                                        {attempt.percentage.toFixed(1)}%
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {attempt.passed ? (
                                                        <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">
                                                            <CheckCircle2 className="h-3 w-3 mr-1" />
                                                            Réussi
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200">
                                                            <XCircle className="h-3 w-3 mr-1" />
                                                            Échoué
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <div className="flex items-center justify-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                                                        <Clock className="h-3 w-3" />
                                                        {attempt.time_taken}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">
                                                        {formatDate(attempt.completed_at)}
                                                    </span>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Back Button */}
                <div className="mt-8 flex items-center justify-between">
                    <Link href={route('trainings.quizzes.index', training.uuid)}>
                        <Button variant="outline">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Retour aux quiz
                        </Button>
                    </Link>
                    <Link href={route('trainings.quizzes.edit', [training.uuid, quiz.uuid])}>
                        <Button variant="outline">
                            Modifier le quiz
                        </Button>
                    </Link>
                </div>
            </div>
        </DashboardLayout>
    );
}
