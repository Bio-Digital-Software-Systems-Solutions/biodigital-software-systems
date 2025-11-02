import React, { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Progress } from '@/Components/ui/progress';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { formatNumber } from '@/lib/utils';
import {
  BookOpen,
  TrendingUp,
  Award,
  Clock,
  Play,
  FileText,
  Calendar,
  LayoutGrid,
  List,
  Table as TableIcon,
  AlertCircle
} from 'lucide-react';

interface Topic {
  id: number;
  name: string;
  description: string;
  order: number;
}

interface Material {
  id: number;
  uuid: string;
  title: string;
  type: 'video' | 'audio' | 'pdf' | 'powerpoint' | 'document';
  duration?: string;
  url?: string;
  file_url?: string;
  description?: string;
  order: number;
  is_active: boolean;
}

interface QuizAttempt {
  id: number;
  score: number | null;
  status: 'in_progress' | 'completed' | 'abandoned';
  completed_at: string | null;
}

interface Quiz {
  id: number;
  title: string;
  description: string | null;
  duration_minutes: number;
  max_score: number;
  passing_score: number;
  available_from: string | null;
  available_until: string | null;
  attempt: QuizAttempt | null;
}

interface Training {
  id: number;
  uuid: string;
  title: string;
  description: string;
  duration: string;
  level: 'beginner' | 'intermediate' | 'advanced';
  price: number;
  teacher?: {
    id: number;
    name: string;
    email: string;
  } | null;
  topics: Topic[];
  materials?: Material[];
  quizzes?: Quiz[];
  progress: number;
  grade: number;
  attendanceRate: number;
  nextClass?: {
    date: string;
    time: string;
    room: string;
  } | null;
}

interface Props {
  auth: {
    user: {
      id: number;
      name: string;
      email: string;
      first_name?: string;
      last_name?: string;
      avatar?: string;
    };
  };
  trainings: Training[];
}

const StudentDashboard: React.FC<Props> = ({ auth, trainings }) => {
  const [activeTraining, setActiveTraining] = useState<string>(trainings[0]?.id?.toString() || '');
  const [viewMode, setViewMode] = useState<'grid' | 'list' | 'table'>('grid');
  const [showQuizConfirm, setShowQuizConfirm] = useState(false);
  const [selectedQuizId, setSelectedQuizId] = useState<number | null>(null);

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'video': case 'audio': return <Play className="h-4 w-4" />;
      default: return <FileText className="h-4 w-4" />;
    }
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'video': return 'text-red-600 bg-red-50 dark:bg-red-900/20';
      case 'audio': return 'text-purple-600 bg-purple-50 dark:bg-purple-900/20';
      case 'pdf': return 'text-primary bg-blue-50 dark:bg-blue-900/20';
      case 'powerpoint': return 'text-orange-600 bg-orange-50 dark:bg-orange-900/20';
      default: return 'text-gray-600 bg-gray-50 dark:bg-gray-700';
    }
  };

  const currentTraining = trainings.find(f => f.id?.toString() === activeTraining) || trainings[0];

  const courseMaterials = currentTraining?.materials || [];
  const quizzes = currentTraining?.quizzes || [];

  const [mediaModalOpen, setMediaModalOpen] = useState(false);
  const [currentMedia, setCurrentMedia] = useState<{ url: string; type: 'video' | 'audio' | 'pdf' | 'powerpoint'; title: string } | null>(null);

  const openMaterial = (material: Material) => {
    // Use the download route for class materials
    const downloadUrl = route('training-class-materials.download', material.uuid);

    if (material.type === 'video' || material.type === 'audio') {
      setCurrentMedia({ url: downloadUrl, type: material.type, title: material.title });
      setMediaModalOpen(true);
    } else {
      window.open(downloadUrl, '_blank');
    }
  };

  const startQuiz = (quizId: number) => {
    setSelectedQuizId(quizId);
    setShowQuizConfirm(true);
  };

  const confirmStartQuiz = () => {
    if (selectedQuizId) {
      window.location.href = route('quizzes.start', selectedQuizId);
    }
  };

  return (
    <DashboardLayout
      title="Mon Espace Formation"
      description="Tableau de bord étudiant"
    >
      <Head title="Mon Espace Formation" />

      <div className="space-y-6">

        {trainings.length === 0 ? (
          <Card>
            <CardContent className="text-center py-12">
              <BookOpen className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                Aucune formation en cours
              </h3>
              <p className="text-gray-600 dark:text-gray-400 mb-6">
                Explorez notre catalogue et inscrivez-vous à une formation pour commencer votre apprentissage.
              </p>
              <Button
                onClick={() => window.location.href = '/#formations'}
                className="bg-primary hover:bg-primary"
              >
                Parcourir les formations
              </Button>
            </CardContent>
          </Card>
        ) : (
          <>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <Card>
                <CardContent className="p-4">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                      <BookOpen className="h-5 w-5 text-primary dark:text-blue-400" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-600 dark:text-gray-400">Formations</p>
                      <p className="text-2xl font-bold">{trainings.length}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardContent className="p-4">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-green-100 dark:bg-green-900/50 rounded-lg">
                      <TrendingUp className="h-5 w-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-600 dark:text-gray-400">Progression moyenne</p>
                      <p className="text-2xl font-bold">
                        {formatNumber(trainings.reduce((acc, f) => acc + (f.progress || 0), 0) / trainings.length)}%
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardContent className="p-4">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg">
                      <Award className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-600 dark:text-gray-400">Note moyenne</p>
                      <p className="text-2xl font-bold">
                        {formatNumber(trainings.reduce((acc, f) => acc + (f.grade || 0), 0) / trainings.length)}/20
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardContent className="p-4">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-purple-100 dark:bg-purple-900/50 rounded-lg">
                      <Clock className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                      <p className="text-sm text-gray-600 dark:text-gray-400">Assiduité</p>
                      <p className="text-2xl font-bold">
                        {formatNumber(trainings.reduce((acc, f) => acc + (f.attendanceRate || 0), 0) / trainings.length)}%
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>

            {currentTraining?.nextClass && (
              <Card className="bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                <CardContent className="p-4">
                  <div className="flex items-center gap-3">
                    <Calendar className="h-5 w-5 text-primary dark:text-blue-400" />
                    <div>
                      <p className="font-semibold text-blue-900 dark:text-blue-100">Prochain cours: {currentTraining.title}</p>
                      <p className="text-sm text-primary dark:text-blue-300">
                        {formatDate(currentTraining.nextClass.date)} • {currentTraining.nextClass.time} • {currentTraining.nextClass.room}
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            )}

            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle>Mes formations</CardTitle>
                    <CardDescription>Liste des formations dans lesquelles vous êtes inscrit</CardDescription>
                  </div>
                  <div className="flex gap-1 border rounded-lg p-1 bg-gray-50 dark:bg-gray-800">
                    <Button
                      variant={viewMode === 'grid' ? 'default' : 'ghost'}
                      size="sm"
                      onClick={() => setViewMode('grid')}
                      className="h-8 w-8 p-0"
                    >
                      <LayoutGrid className="h-4 w-4" />
                    </Button>
                    <Button
                      variant={viewMode === 'list' ? 'default' : 'ghost'}
                      size="sm"
                      onClick={() => setViewMode('list')}
                      className="h-8 w-8 p-0"
                    >
                      <List className="h-4 w-4" />
                    </Button>
                    <Button
                      variant={viewMode === 'table' ? 'default' : 'ghost'}
                      size="sm"
                      onClick={() => setViewMode('table')}
                      className="h-8 w-8 p-0"
                    >
                      <TableIcon className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {/* Grid View */}
                {viewMode === 'grid' && (
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {trainings.map((training) => (
                      <Link
                        key={training.id}
                        href={route('trainings.show', training.uuid)}
                        className="block"
                      >
                        <Card
                          className={`cursor-pointer transition-all h-full hover:border-blue-400 hover:shadow-md ${
                            activeTraining === training.id.toString()
                              ? 'border-primary bg-blue-50 dark:bg-blue-900/20'
                              : ''
                          }`}
                        >
                          <CardContent className="p-4">
                            <h3 className="font-semibold text-lg line-clamp-2 mb-2">{training.title}</h3>
                            {training.teacher && (
                              <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                Enseignant: {training.teacher.name}
                              </p>
                            )}
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                              {training.description}
                            </p>
                            <div className="space-y-2">
                              <div className="flex items-center justify-between text-sm">
                                <span className="text-gray-600 dark:text-gray-400">Progression</span>
                                <span className="font-semibold">{formatNumber(training.progress || 0)}%</span>
                              </div>
                              <Progress value={training.progress || 0} className="h-2" />
                              <div className="flex items-center justify-between text-sm pt-2">
                                <Badge variant="outline" className="text-xs">
                                  {training.level === 'beginner' ? 'Débutant' : training.level === 'intermediate' ? 'Intermédiaire' : 'Avancé'}
                                </Badge>
                                {training.grade !== undefined && (
                                  <span className="text-sm font-semibold">
                                    Note: {formatNumber(training.grade)}/20
                                  </span>
                                )}
                              </div>
                            </div>
                          </CardContent>
                        </Card>
                      </Link>
                    ))}
                  </div>
                )}

                {/* List View */}
                {viewMode === 'list' && (
                  <div className="space-y-3">
                    {trainings.map((training) => (
                      <Link
                        key={training.id}
                        href={route('trainings.show', training.uuid)}
                        className="block"
                      >
                        <Card
                          className={`cursor-pointer transition-all hover:border-blue-400 hover:shadow-md ${
                            activeTraining === training.id.toString()
                              ? 'border-primary bg-blue-50 dark:bg-blue-900/20'
                              : ''
                          }`}
                        >
                          <CardContent className="p-4">
                            <div className="flex items-center gap-4">
                              <div className="flex-1">
                                <h3 className="font-semibold text-lg mb-1">{training.title}</h3>
                                {training.teacher && (
                                  <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    Enseignant: {training.teacher.name}
                                  </p>
                                )}
                                <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-1 mb-3">
                                  {training.description}
                                </p>
                                <div className="flex items-center gap-4">
                                  <Badge variant="outline" className="text-xs">
                                    {training.level === 'beginner' ? 'Débutant' : training.level === 'intermediate' ? 'Intermédiaire' : 'Avancé'}
                                  </Badge>
                                  <div className="flex-1 max-w-xs">
                                    <div className="flex items-center justify-between text-sm mb-1">
                                      <span className="text-gray-600 dark:text-gray-400">Progression</span>
                                      <span className="font-semibold">{formatNumber(training.progress || 0)}%</span>
                                    </div>
                                    <Progress value={training.progress || 0} className="h-2" />
                                  </div>
                                  {training.grade !== undefined && (
                                    <span className="text-sm font-semibold">
                                      Note: {formatNumber(training.grade)}/20
                                    </span>
                                  )}
                                </div>
                              </div>
                            </div>
                          </CardContent>
                        </Card>
                      </Link>
                    ))}
                  </div>
                )}

                {/* Table View */}
                {viewMode === 'table' && (
                  <div className="overflow-x-auto">
                    <table className="w-full">
                      <thead>
                        <tr className="border-b dark:border-gray-700">
                          <th className="text-left py-3 px-4 font-semibold text-sm">Formation</th>
                          <th className="text-left py-3 px-4 font-semibold text-sm">Niveau</th>
                          <th className="text-left py-3 px-4 font-semibold text-sm">Progression</th>
                          <th className="text-left py-3 px-4 font-semibold text-sm">Note</th>
                        </tr>
                      </thead>
                      <tbody>
                        {trainings.map((training) => (
                          <tr
                            key={training.id}
                            className={`border-b dark:border-gray-700 cursor-pointer transition-colors ${
                              activeTraining === training.id.toString()
                                ? 'bg-blue-50 dark:bg-blue-900/20'
                                : 'hover:bg-blue-50 dark:hover:bg-blue-900/10'
                            }`}
                            onClick={() => window.location.href = route('trainings.show', training.uuid)}
                          >
                            <td className="py-3 px-4">
                              <div>
                                <p className="font-semibold">{training.title}</p>
                                {training.teacher && (
                                  <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Enseignant: {training.teacher.name}
                                  </p>
                                )}
                                <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-1">
                                  {training.description}
                                </p>
                              </div>
                            </td>
                            <td className="py-3 px-4">
                              <Badge variant="outline" className="text-xs">
                                {training.level === 'beginner' ? 'Débutant' : training.level === 'intermediate' ? 'Intermédiaire' : 'Avancé'}
                              </Badge>
                            </td>
                            <td className="py-3 px-4">
                              <div className="flex items-center gap-2">
                                <Progress value={training.progress || 0} className="h-2 w-24" />
                                <span className="text-sm font-semibold">{formatNumber(training.progress || 0)}%</span>
                              </div>
                            </td>
                            <td className="py-3 px-4">
                              {training.grade !== undefined ? (
                                <span className="font-semibold">{formatNumber(training.grade)}/20</span>
                              ) : (
                                <span className="text-gray-400">-</span>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </CardContent>
            </Card>

            <Tabs defaultValue="materials" className="space-y-4">
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="materials">Supports de cours</TabsTrigger>
                <TabsTrigger value="evaluations">Évaluations</TabsTrigger>
                <TabsTrigger value="progress">Ma progression</TabsTrigger>
              </TabsList>

              <TabsContent value="materials" className="space-y-4">
                <Card>
                  <CardHeader>
                    <CardTitle>Supports de cours - {currentTraining?.title}</CardTitle>
                    <CardDescription>
                      Accédez à vos ressources pédagogiques (lecture en ligne uniquement)
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {courseMaterials.length === 0 ? (
                        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                          Aucun support de cours disponible pour le moment.
                        </div>
                      ) : (
                        courseMaterials.map((material) => (
                          <div
                            key={material.id}
                            className="flex items-center justify-between p-4 rounded-lg border bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                          >
                            <div className="flex items-center gap-3">
                              <div className={`p-2 rounded-lg ${getTypeColor(material.type)}`}>
                                {getTypeIcon(material.type)}
                              </div>
                              <div>
                                <h4 className="font-medium">{material.title}</h4>
                                <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                  <Badge variant="outline" className="text-xs">
                                    {material.type.toUpperCase()}
                                  </Badge>
                                  {material.duration && <span>• {material.duration}</span>}
                                </div>
                                {material.description && (
                                  <p className="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                    {material.description}
                                  </p>
                                )}
                              </div>
                            </div>
                            <Button
                              size="sm"
                              className="bg-primary hover:bg-primary"
                              onClick={() => openMaterial(material)}
                            >
                              {material.type === 'video' || material.type === 'audio' ? 'Lire' : 'Ouvrir'}
                            </Button>
                          </div>
                        ))
                      )}
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="evaluations" className="space-y-4">
                <Card>
                  <CardHeader>
                    <CardTitle>Évaluations - {currentTraining?.title}</CardTitle>
                    <CardDescription>Vos résultats et évaluations à venir</CardDescription>
                  </CardHeader>
                  <CardContent>
                    {quizzes.length === 0 ? (
                      <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                        Aucune évaluation disponible pour le moment.
                      </div>
                    ) : (
                      <div className="space-y-4">
                        {quizzes.map((quiz) => {
                          const hasAttempt = quiz.attempt !== null;
                          const isPassed = hasAttempt && quiz.attempt?.score && quiz.attempt.score >= quiz.passing_score;
                          const isCompleted = hasAttempt && quiz.attempt?.status === 'completed';

                          return (
                            <div
                              key={quiz.id}
                              className="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700"
                            >
                              <div className="flex items-center gap-3">
                                <div className={`p-2 rounded-lg ${
                                  isCompleted
                                    ? isPassed
                                      ? 'bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400'
                                      : 'bg-red-100 text-red-600 dark:bg-red-900/50 dark:text-red-400'
                                    : 'bg-orange-100 text-orange-600 dark:bg-orange-900/50 dark:text-orange-400'
                                }`}>
                                  <Award className="h-5 w-5" />
                                </div>
                                <div>
                                  <h4 className="font-medium">{quiz.title}</h4>
                                  <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    {isCompleted ? (
                                      <>
                                        <span>Note: {quiz.attempt?.score}/{quiz.max_score}</span>
                                        <Badge className={isPassed ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'}>
                                          {isPassed ? 'Réussi' : 'Échoué'}
                                        </Badge>
                                        {quiz.attempt?.completed_at && (
                                          <span>• Fait le {new Date(quiz.attempt.completed_at).toLocaleDateString('fr-FR')}</span>
                                        )}
                                      </>
                                    ) : (
                                      <>
                                        {quiz.available_until && (
                                          <span>À rendre avant le {new Date(quiz.available_until).toLocaleDateString('fr-FR')}</span>
                                        )}
                                        <Badge className="bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300">En attente</Badge>
                                      </>
                                    )}
                                  </div>
                                </div>
                              </div>
                              {!isCompleted && (
                                <Button
                                  size="sm"
                                  className="bg-primary hover:bg-primary"
                                  onClick={() => startQuiz(quiz.id)}
                                >
                                  Commencer
                                </Button>
                              )}
                            </div>
                          );
                        })}
                      </div>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="progress" className="space-y-4">
                <Card>
                  <CardHeader>
                    <CardTitle>Progression générale</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="space-y-2">
                      <div className="flex justify-between">
                        <span>Cours complétés</span>
                        <span>{formatNumber(currentTraining?.progress || 0)}%</span>
                      </div>
                      <Progress value={currentTraining?.progress || 0} className="h-3" />
                    </div>

                    <div className="grid grid-cols-2 gap-4 pt-4">
                      <div className="text-center">
                        <div className="text-2xl font-bold text-primary dark:text-blue-400">{formatNumber(currentTraining?.grade || 0)}/20</div>
                        <div className="text-sm text-gray-600 dark:text-gray-400">Note moyenne</div>
                      </div>
                      <div className="text-center">
                        <div className="text-2xl font-bold text-green-600 dark:text-green-400">{formatNumber(currentTraining?.attendanceRate || 0)}%</div>
                        <div className="text-sm text-gray-600 dark:text-gray-400">Assiduité</div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>
          </>
        )}
      </div>

      {/* Quiz Confirmation Dialog */}
      <Dialog open={showQuizConfirm} onOpenChange={setShowQuizConfirm}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                <AlertCircle className="h-6 w-6 text-primary dark:text-blue-400" />
              </div>
              <DialogTitle>Commencer le quiz ?</DialogTitle>
            </div>
            <DialogDescription className="pt-4 space-y-3">
              <p className="text-base">Le timer de 30 minutes démarrera immédiatement et ne pourra pas être arrêté.</p>
              <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                <p className="text-sm text-yellow-800 dark:text-yellow-200 font-medium">
                  ⚠️ Assurez-vous d'être prêt avant de commencer
                </p>
              </div>
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setShowQuizConfirm(false)}
              className="border-gray-300 dark:border-gray-600"
            >
              Annuler
            </Button>
            <Button
              onClick={confirmStartQuiz}
              className="bg-primary hover:bg-primary text-white"
            >
              Commencer
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Media Player Modal */}
      <Dialog open={mediaModalOpen} onOpenChange={setMediaModalOpen}>
        <DialogContent className="max-w-4xl">
          <DialogHeader>
            <DialogTitle>{currentMedia?.title}</DialogTitle>
          </DialogHeader>
          {currentMedia && (
            <div className="mt-4">
              {currentMedia.type === 'video' ? (
                <video
                  src={currentMedia.url}
                  controls
                  controlsList="nodownload"
                  className="w-full rounded-lg"
                  style={{ maxHeight: '500px' }}
                  onContextMenu={(e) => e.preventDefault()}
                >
                  Votre navigateur ne supporte pas la lecture vidéo.
                </video>
              ) : (
                <audio
                  src={currentMedia.url}
                  controls
                  controlsList="nodownload"
                  className="w-full"
                  onContextMenu={(e) => e.preventDefault()}
                >
                  Votre navigateur ne supporte pas la lecture audio.
                </audio>
              )}
            </div>
          )}
        </DialogContent>
      </Dialog>
    </DashboardLayout>
  );
};

export default StudentDashboard;
