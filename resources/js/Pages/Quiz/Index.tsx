import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import type { PageProps } from '@/Types';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    Plus,
    Edit,
    Trash2,
    BarChart3,
    Clock,
    Users,
    CheckCircle2,
    Calendar,
    Target,
    Power,
    PowerOff
} from 'lucide-react';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

interface Quiz {
    id: number;
    uuid: string;
    title: string;
    description: string | null;
    duration_minutes: number;
    max_score: number;
    passing_score: number;
    available_from: string | null;
    available_until: string | null;
    is_active: boolean;
    attempts_count: number;
    completed_attempts_count: number;
    status: 'draft' | 'published' | 'archived';
}

interface Training {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
}

interface Props {
    training: Training;
    quizzes: Quiz[];
}

export default function QuizIndex({ training, quizzes }: Props) {
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [quizToDelete, setQuizToDelete] = useState<Quiz | null>(null);
    const { auth } = usePage<PageProps>().props;

    // Fonction pour vérifier si l'utilisateur peut voir les quiz désactivés
    const canViewInactiveQuizzes = () => {
        const userRoles = auth.user?.roles?.map(role => role.name) || [];
        const userPermissions = auth.user?.permissions?.map((p: any) => typeof p === 'string' ? p : p.name) || [];

        // Les admins et enseignants peuvent voir tous les quiz
        return userRoles.includes('admin') ||
               userRoles.includes('teacher') ||
               userRoles.includes('event-manager') ||
               userRoles.includes('project-manager') ||
               userPermissions.includes('manage quizzes') ||
               userPermissions.includes('edit quizzes');
    };

    // Filtrer les quiz selon les permissions
    const filteredQuizzes = canViewInactiveQuizzes()
        ? quizzes  // Tous les quiz pour enseignants/admins
        : quizzes.filter(quiz => quiz.is_active); // Seulement les quiz actifs pour les autres

    const handleDelete = (quiz: Quiz) => {
        setQuizToDelete(quiz);
        setDeleteDialogOpen(true);
    };

    const confirmDelete = () => {
        if (!quizToDelete) return;

        router.delete(route('trainings.quizzes.destroy', [training.uuid, quizToDelete.uuid]), {
            onSuccess: () => {
                toast.success('Quiz supprimé avec succès');
                setDeleteDialogOpen(false);
                setQuizToDelete(null);
            },
            onError: () => {
                toast.error('Erreur lors de la suppression du quiz');
            }
        });
    };

    const toggleQuizStatus = (quiz: Quiz) => {
        router.patch(route('trainings.quizzes.toggle-status', [training.uuid, quiz.uuid]), {}, {
            onSuccess: () => {
                toast.success(quiz.is_active ? 'Quiz désactivé avec succès' : 'Quiz activé avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la modification du statut du quiz');
            }
        });
    };

    const getStatusBadge = (quiz: Quiz) => {
        switch (quiz.status) {
            case 'draft':
                return <Badge variant="outline" className="bg-gray-50 text-gray-700 border-gray-200">Brouillon</Badge>;
            case 'published':
                return <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">Publié</Badge>;
            case 'archived':
                return <Badge variant="outline" className="bg-yellow-50 text-yellow-700 border-yellow-200">Archivé</Badge>;
            default:
                return <Badge variant="secondary">Inconnu</Badge>;
        }
    };

    const getAvailabilityBadge = (quiz: Quiz) => {
        if (!quiz.is_active) {
            return <Badge variant="secondary">Inactif</Badge>;
        }

        const now = new Date();
        const availableFrom = quiz.available_from ? new Date(quiz.available_from) : null;
        const availableUntil = quiz.available_until ? new Date(quiz.available_until) : null;

        if (availableFrom && availableFrom > now) {
            return <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">À venir</Badge>;
        }

        if (availableUntil && availableUntil < now) {
            return <Badge variant="outline" className="bg-gray-50 text-gray-700 border-gray-200">Expiré</Badge>;
        }

        return <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">Disponible</Badge>;
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'Non défini';
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Quiz - ${training.name}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-8">
                    <div className="flex items-center justify-between mb-4">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Quiz & Évaluations
                            </h1>
                            <p className="mt-2 text-gray-600 dark:text-gray-400">
                                Formation: {training.name}
                            </p>
                        </div>
                        <Link href={route('trainings.quizzes.create', training.uuid)}>
                            <Button className="bg-blue-600 hover:bg-blue-700">
                                <Plus className="h-4 w-4 mr-2" />
                                Créer un quiz
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Total Quiz
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-gray-900 dark:text-white">
                                {filteredQuizzes.length}
                            </div>
                            {canViewInactiveQuizzes() && quizzes.filter(q => !q.is_active).length > 0 && (
                                <p className="text-xs text-gray-500 mt-1">
                                    {quizzes.filter(q => !q.is_active).length} désactivé{quizzes.filter(q => !q.is_active).length > 1 ? 's' : ''}
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Quiz actifs
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-green-600 dark:text-green-400">
                                {filteredQuizzes.filter(q => q.is_active).length}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Tentatives totales
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {filteredQuizzes.reduce((sum, q) => sum + q.attempts_count, 0)}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Quiz List */}
                {filteredQuizzes.length === 0 ? (
                    <Card>
                        <CardContent className="text-center py-12">
                            <BarChart3 className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                {quizzes.length === 0 ? 'Aucun quiz créé' : 'Aucun quiz disponible'}
                            </h3>
                            <p className="text-gray-600 dark:text-gray-400 mb-6">
                                {quizzes.length === 0
                                    ? 'Commencez par créer votre premier quiz pour cette formation'
                                    : canViewInactiveQuizzes()
                                        ? 'Tous les quiz sont actuellement désactivés'
                                        : 'Aucun quiz actif n\'est disponible pour le moment'
                                }
                            </p>
                            {canViewInactiveQuizzes() && (
                                <Link href={route('trainings.quizzes.create', training.uuid)}>
                                    <Button className="bg-blue-600 hover:bg-blue-700">
                                        <Plus className="h-4 w-4 mr-2" />
                                        Créer un quiz
                                    </Button>
                                </Link>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-6">
                        {filteredQuizzes.map((quiz) => (
                            <Card
                                key={quiz.id}
                                className={`hover:shadow-lg transition-shadow ${
                                    !quiz.is_active
                                        ? 'opacity-70 border-dashed border-2 border-gray-300 dark:border-gray-600'
                                        : ''
                                }`}
                            >
                                <CardHeader>
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-2">
                                                <CardTitle className="text-xl">
                                                    <Link
                                                        href={route('trainings.quizzes.edit', [training.uuid, quiz.uuid])}
                                                        className="hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                                    >
                                                        {quiz.title}
                                                    </Link>
                                                </CardTitle>
                                                <div className="flex items-center gap-2">
                                                    {getStatusBadge(quiz)}
                                                    {getAvailabilityBadge(quiz)}
                                                    {!quiz.is_active && canViewInactiveQuizzes() && (
                                                        <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300">
                                                            Désactivé
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                            {quiz.description && (
                                                <CardDescription className="text-sm">
                                                    {quiz.description}
                                                </CardDescription>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2 ml-4">
                                            <Link
                                                href={route('trainings.quizzes.results', [training.uuid, quiz.uuid])}
                                            >
                                                <Button variant="outline" size="sm">
                                                    <BarChart3 className="h-4 w-4 mr-1" />
                                                    Résultats
                                                </Button>
                                            </Link>
                                            <Link
                                                href={route('trainings.quizzes.edit', [training.uuid, quiz.uuid])}
                                            >
                                                <Button variant="outline" size="sm">
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            {canViewInactiveQuizzes() && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => toggleQuizStatus(quiz)}
                                                    className={quiz.is_active
                                                        ? "text-orange-600 hover:text-orange-700 hover:bg-orange-50 dark:hover:bg-orange-900/20"
                                                        : "text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20"
                                                    }
                                                    title={quiz.is_active ? 'Désactiver le quiz' : 'Activer le quiz'}
                                                >
                                                    {quiz.is_active ? <PowerOff className="h-4 w-4" /> : <Power className="h-4 w-4" />}
                                                </Button>
                                            )}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleDelete(quiz)}
                                                className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div className="flex items-center gap-2 text-sm">
                                            <Clock className="h-4 w-4 text-gray-500" />
                                            <span className="text-gray-600 dark:text-gray-400">
                                                {quiz.duration_minutes} min
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2 text-sm">
                                            <Target className="h-4 w-4 text-gray-500" />
                                            <span className="text-gray-600 dark:text-gray-400">
                                                {quiz.passing_score}/{quiz.max_score} pts
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2 text-sm">
                                            <Users className="h-4 w-4 text-gray-500" />
                                            <span className="text-gray-600 dark:text-gray-400">
                                                {quiz.attempts_count} tentative{quiz.attempts_count > 1 ? 's' : ''}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2 text-sm">
                                            <CheckCircle2 className="h-4 w-4 text-gray-500" />
                                            <span className="text-gray-600 dark:text-gray-400">
                                                {quiz.completed_attempts_count} complété{quiz.completed_attempts_count > 1 ? 's' : ''}
                                            </span>
                                        </div>
                                    </div>

                                    {(quiz.available_from || quiz.available_until) && (
                                        <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <Calendar className="h-4 w-4" />
                                                <span>
                                                    Disponible du {formatDate(quiz.available_from)}
                                                    {quiz.available_until && ` au ${formatDate(quiz.available_until)}`}
                                                </span>
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {/* Back Button */}
                <div className="mt-8">
                    <Link href={route('trainings.show', training.uuid)}>
                        <Button variant="outline">
                            Retour à la formation
                        </Button>
                    </Link>
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={confirmDelete}
                title="Supprimer le quiz"
                description={`Êtes-vous sûr de vouloir supprimer le quiz "${quizToDelete?.title}" ? Cette action supprimera également toutes les tentatives des étudiants. Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
}
