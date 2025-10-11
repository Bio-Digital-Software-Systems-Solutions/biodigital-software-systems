import React, { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { apiLogger } from '@/utils/logger';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Progress } from '@/Components/ui/progress';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Table as TableIcon } from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import {
  BookOpen,
  Users,
  Clock,
  Star,
  CheckCircle,
  Search,
  Filter,
  SortAsc,
  Grid,
  List,
  Calendar,
  MapPin
} from 'lucide-react';

interface Topic {
  id: string;
  name: string;
  description: string;
  isCompleted?: boolean;
}

interface Session {
  id: string;
  start_date: string;
  end_date: string;
  location?: string;
  max_participants?: number;
  enrolled_count?: number;
  status: 'upcoming' | 'ongoing' | 'completed';
}

interface Training {
  id: string;
  title: string;
  description: string;
  duration: string;
  level: 'beginner' | 'intermediate' | 'advanced';
  price: number;
  image?: string;
  image_url?: string;
  topics: Topic[];
  sessions?: Session[];
  isEnrolled?: boolean;
  enrollmentStatus?: 'pending' | 'approved' | 'rejected';
  rating?: number;
  studentsCount?: number;
  category?: string;
  progress?: number;
}

interface TrainingCardProps {
  training: Training;
  onEnroll: (trainingId: string) => void;
  onViewDetails: (trainingId: string) => void;
}

const TrainingCard: React.FC<TrainingCardProps> = ({ training, onEnroll, onViewDetails }) => {
  const getLevelColor = (level: string) => {
    switch (level) {
      case 'beginner': return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300';
      case 'intermediate': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300';
      case 'advanced': return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
  };

  const getLevelLabel = (level: string) => {
    switch (level) {
      case 'beginner': return 'Débutant';
      case 'intermediate': return 'Intermédiaire';
      case 'advanced': return 'Avancé';
      default: return level;
    }
  };

  const completedTopics = training.topics.filter(topic => topic.isCompleted).length;
  const progressPercentage = training.topics.length > 0
    ? (completedTopics / training.topics.length) * 100
    : 0;

  return (
    <Card className="group hover:shadow-xl transition-all duration-300 overflow-hidden h-full flex flex-col bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
      {training.image_url && (
        <div className="h-48 overflow-hidden">
          <img
            src={training.image_url}
            alt={training.title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </div>
      )}

      <CardHeader className="pb-3 flex-shrink-0">
        <div className="flex items-start justify-between gap-2">
          <CardTitle className="text-lg font-semibold line-clamp-2 group-hover:text-primary dark:group-hover:text-blue-400 transition-colors flex-1">
            {training.title}
          </CardTitle>
          <div className="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400 shrink-0">
            <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
            <span>{training.rating || 4.5}</span>
          </div>
        </div>

        <CardDescription className="line-clamp-2 dark:text-gray-400">
          {training.description}
        </CardDescription>

        <div className="flex flex-wrap gap-2 mt-2">
          <Badge variant="secondary" className={getLevelColor(training.level)}>
            {getLevelLabel(training.level)}
          </Badge>
        </div>
      </CardHeader>

      <CardContent className="space-y-4 flex-1">
        <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
          <div className="flex items-center gap-1">
            <Clock className="h-4 w-4" />
            <span>{training.duration}</span>
          </div>
          <div className="flex items-center gap-1">
            <Users className="h-4 w-4" />
            <span>{training.studentsCount || 0} étudiants</span>
          </div>
        </div>

        <div className="space-y-3 flex-1">
          <div className="flex items-center gap-2">
            <BookOpen className="h-4 w-4 text-gray-600 dark:text-gray-400" />
            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
              Thèmes abordés ({training.topics.length})
            </span>
          </div>

          <div className="space-y-2 max-h-32 overflow-y-auto">
            {training.topics.slice(0, 3).map((topic) => (
              <div
                key={topic.id}
                className="flex items-center gap-2 text-sm p-2 rounded-md bg-gray-50 dark:bg-gray-700/50"
              >
                <CheckCircle
                  className={`h-4 w-4 shrink-0 ${
                    topic.isCompleted
                      ? 'text-green-500 fill-green-100'
                      : 'text-gray-400 dark:text-gray-500'
                  }`}
                />
                <span className={topic.isCompleted ? 'text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-400'}>
                  {topic.name}
                </span>
              </div>
            ))}
            {training.topics.length > 3 && (
              <div className="text-sm text-gray-500 dark:text-gray-400 pl-6">
                +{training.topics.length - 3} autres thèmes
              </div>
            )}
          </div>
        </div>
      </CardContent>

      <div className="flex items-center justify-between p-4 border-t bg-gray-50/50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700 flex-shrink-0">
        <div className={`text-xl font-bold ${training.price === 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100'}`}>
          {training.price === 0 ? 'Gratuit' : `${training.price.toLocaleString('fr-FR')}€`}
        </div>

        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => onViewDetails(training.id)}
            className="hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            Détails
          </Button>

          <Button
            size="sm"
            onClick={() => onEnroll(training.id)}
            className="bg-primary hover:bg-primary dark:bg-primary dark:hover:bg-primary"
          >
            S'inscrire
          </Button>
        </div>
      </div>
    </Card>
  );
};

const TrainingListItem: React.FC<TrainingCardProps> = ({ training, onEnroll, onViewDetails }) => {
  const getLevelColor = (level: string) => {
    switch (level) {
      case 'beginner': return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300';
      case 'intermediate': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300';
      case 'advanced': return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
  };

  const getLevelLabel = (level: string) => {
    switch (level) {
      case 'beginner': return 'Débutant';
      case 'intermediate': return 'Intermédiaire';
      case 'advanced': return 'Avancé';
      default: return level;
    }
  };

  return (
    <Card className="group hover:shadow-lg transition-all duration-300 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
      <div className="flex flex-col md:flex-row gap-4 p-4">
        {training.image_url && (
          <div className="w-full md:w-48 h-32 flex-shrink-0 overflow-hidden rounded-lg">
            <img
              src={training.image_url}
              alt={training.title}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
          </div>
        )}

        <div className="flex-1 space-y-2">
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1">
              <h3 className="text-xl font-semibold group-hover:text-primary dark:group-hover:text-blue-400 transition-colors">
                {training.title}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 text-sm mt-1 line-clamp-2">
                {training.description}
              </p>
            </div>
            <div className="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400 shrink-0">
              <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
              <span>{training.rating || 4.5}</span>
            </div>
          </div>

          <div className="flex flex-wrap gap-2">
            <Badge variant="secondary" className={getLevelColor(training.level)}>
              {getLevelLabel(training.level)}
            </Badge>
            {training.category && (
              <Badge variant="outline" className="dark:border-gray-600">
                {training.category}
              </Badge>
            )}
          </div>

          <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <div className="flex items-center gap-1">
              <Clock className="h-4 w-4" />
              <span>{training.duration}</span>
            </div>
            <div className="flex items-center gap-1">
              <Users className="h-4 w-4" />
              <span>{training.studentsCount || 0} étudiants</span>
            </div>
            <div className="flex items-center gap-1">
              <BookOpen className="h-4 w-4" />
              <span>{training.topics.length} thèmes</span>
            </div>
          </div>
        </div>

        <div className="flex md:flex-col items-center md:items-end justify-between md:justify-start gap-3 pt-3 md:pt-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 md:pl-4">
          <div className={`text-2xl font-bold ${training.price === 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100'}`}>
            {training.price === 0 ? 'Gratuit' : `${training.price.toLocaleString('fr-FR')}€`}
          </div>

          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => onViewDetails(training.id)}
              className="hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              Détails
            </Button>

            <Button
              size="sm"
              onClick={() => onEnroll(training.id)}
              className="bg-primary hover:bg-primary dark:bg-primary dark:hover:bg-primary"
            >
              S'inscrire
            </Button>
          </div>
        </div>
      </div>
    </Card>
  );
};

const TrainingBrowseSection: React.FC = () => {
  const { auth } = usePage().props as { auth: { user?: any } };
  const [searchTerm, setSearchTerm] = useState('');
  const [levelFilter, setLevelFilter] = useState<string>('all');
  const [categoryFilter, setCategoryFilter] = useState<string>('all');
  const [sortBy, setSortBy] = useState<string>('title');
  const [viewMode, setViewMode] = useState<'grid' | 'list' | 'table'>('grid');
  const [trainings, setTrainings] = useState<Training[]>([]);
  const [filteredTrainings, setFilteredTrainings] = useState<Training[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedTraining, setSelectedTraining] = useState<Training | null>(null);
  const [isDetailsModalOpen, setIsDetailsModalOpen] = useState(false);
  const [isEnrollmentModalOpen, setIsEnrollmentModalOpen] = useState(false);
  const [selectedSessionInDetails, setSelectedSessionInDetails] = useState<string>('');
  const [enrollmentForm, setEnrollmentForm] = useState({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    motivation: '',
    paymentMethod: 'monthly',
    selectedSessionId: '',
    hasReadTerms: false,
    hasReadPrivacyPolicy: false,
  });

  useEffect(() => {
    const loadTrainings = async () => {
      setIsLoading(true);

      try {
        const response = await fetch('/api/trainings', {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          }
        });
        if (response.ok) {
          const data = await response.json();
          // Map classes to sessions format
          const mappedData = data.map((training: any) => ({
            ...training,
            sessions: training.classes?.map((cls: any) => ({
              id: cls.id.toString(),
              start_date: cls.date,
              end_date: cls.date, // Assuming single day sessions, adjust if needed
              location: cls.room || 'À définir',
              max_participants: cls.max_participants || 30,
              enrolled_count: cls.enrolled_count || 0,
              status: 'upcoming' as const
            })) || []
          }));
          setTrainings(mappedData);
        } else {
          // Fallback sur les données mockées
          const mockTrainings: Training[] = [
            {
              id: '1',
              title: 'Développement Web Full Stack',
              description: 'Apprenez à créer des applications web complètes avec React, Node.js et MongoDB. Formation pratique avec projets réels.',
              duration: '6 mois',
              level: 'intermediate',
              price: 1200,
              category: 'Développement',
              rating: 4.8,
              studentsCount: 156,
              topics: [
                { id: '1', name: 'HTML/CSS avancé', description: 'Maîtrise des technologies front-end' },
                { id: '2', name: 'JavaScript ES6+', description: 'Programmation moderne JavaScript' },
                { id: '3', name: 'React.js', description: 'Framework de développement d\'interfaces' },
                { id: '4', name: 'Node.js & Express', description: 'Développement côté serveur' }
              ],
              sessions: [
                {
                  id: 's1',
                  start_date: '2025-11-15',
                  end_date: '2026-05-15',
                  location: 'Paris - 15e arrondissement',
                  max_participants: 25,
                  enrolled_count: 18,
                  status: 'upcoming'
                },
                {
                  id: 's2',
                  start_date: '2026-01-20',
                  end_date: '2026-07-20',
                  location: 'Lyon - Part-Dieu',
                  max_participants: 20,
                  enrolled_count: 5,
                  status: 'upcoming'
                },
                {
                  id: 's3',
                  start_date: '2026-03-10',
                  end_date: '2026-09-10',
                  location: 'En ligne',
                  max_participants: 50,
                  enrolled_count: 12,
                  status: 'upcoming'
                }
              ]
            },
            {
              id: '2',
              title: 'Data Science et Machine Learning',
              description: 'Formation complète en science des données avec Python, pandas, scikit-learn et TensorFlow.',
              duration: '4 mois',
              level: 'advanced',
              price: 1500,
              category: 'Data Science',
              rating: 4.9,
              studentsCount: 89,
              topics: [
                { id: '7', name: 'Python pour la data', description: 'Maîtrise de Python et ses librairies' },
                { id: '8', name: 'Statistiques descriptives', description: 'Analyse statistique des données' },
                { id: '9', name: 'Machine Learning supervisé', description: 'Algorithmes d\'apprentissage supervisé' }
              ],
              sessions: [
                {
                  id: 's4',
                  start_date: '2025-12-01',
                  end_date: '2026-03-31',
                  location: 'Toulouse - Centre',
                  max_participants: 15,
                  enrolled_count: 14,
                  status: 'upcoming'
                },
                {
                  id: 's5',
                  start_date: '2026-02-15',
                  end_date: '2026-06-15',
                  location: 'En ligne',
                  max_participants: 30,
                  enrolled_count: 8,
                  status: 'upcoming'
                }
              ]
            },
            {
              id: '3',
              title: 'Design UI/UX',
              description: 'Créez des interfaces utilisateur exceptionnelles. De la recherche utilisateur au prototypage.',
              duration: '3 mois',
              level: 'beginner',
              price: 800,
              category: 'Design',
              rating: 4.7,
              studentsCount: 203,
              topics: [
                { id: '12', name: 'Principes du design', description: 'Fondamentaux du design d\'interface' },
                { id: '13', name: 'Recherche utilisateur', description: 'Méthodologies UX research' },
                { id: '14', name: 'Prototypage', description: 'Outils de prototypage (Figma, Sketch)' }
              ],
              sessions: [
                {
                  id: 's6',
                  start_date: '2025-11-25',
                  end_date: '2026-02-25',
                  location: 'Marseille - Vieux Port',
                  max_participants: 18,
                  enrolled_count: 3,
                  status: 'upcoming'
                },
                {
                  id: 's7',
                  start_date: '2026-01-10',
                  end_date: '2026-04-10',
                  location: 'Bordeaux - Centre',
                  max_participants: 22,
                  enrolled_count: 19,
                  status: 'upcoming'
                }
              ]
            }
          ];
          setTrainings(mockTrainings);
        }
      } catch (error) {
        apiLogger.error('Error loading trainings', error);
      } finally {
        setIsLoading(false);
      }
    };

    loadTrainings();
  }, []);

  useEffect(() => {
    let filtered = [...trainings];

    if (searchTerm) {
      filtered = filtered.filter(training =>
        training.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        training.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
        training.topics.some(topic =>
          topic.name.toLowerCase().includes(searchTerm.toLowerCase())
        )
      );
    }

    if (levelFilter !== 'all') {
      filtered = filtered.filter(training => training.level === levelFilter);
    }

    if (categoryFilter !== 'all') {
      filtered = filtered.filter(training => training.category === categoryFilter);
    }

    filtered.sort((a, b) => {
      switch (sortBy) {
        case 'title':
          return a.title.localeCompare(b.title);
        case 'price-asc':
          return a.price - b.price;
        case 'price-desc':
          return b.price - a.price;
        case 'rating':
          return (b.rating || 0) - (a.rating || 0);
        case 'students':
          return (b.studentsCount || 0) - (a.studentsCount || 0);
        default:
          return 0;
      }
    });

    setFilteredTrainings(filtered);
  }, [trainings, searchTerm, levelFilter, categoryFilter, sortBy]);

  const categories = Array.from(new Set(trainings.map(f => f.category).filter(Boolean)));

  const handleEnroll = (trainingId: string) => {
    // Check if user is authenticated
    if (!auth?.user) {
      // Redirect to login with intended URL
      router.visit('/login', {
        data: { intended: window.location.pathname + window.location.search }
      });
      return;
    }

    // Find the training and open enrollment modal
    const training = filteredTrainings.find(t => t.id === trainingId);
    if (training) {
      setSelectedTraining(training);
      setIsEnrollmentModalOpen(true);
    }
  };

  const handleViewDetails = (trainingId: string) => {
    const training = filteredTrainings.find(t => t.id === trainingId);
    if (training) {
      setSelectedTraining(training);
      setSelectedSessionInDetails(''); // Reset session selection
      setIsDetailsModalOpen(true);
    }
  };

  const resetFilters = () => {
    setSearchTerm('');
    setLevelFilter('all');
    setCategoryFilter('all');
    setSortBy('title');
  };

  if (isLoading) {
    return (
      <div id="trainings" className="bg-gray-50 dark:bg-gray-900 py-16 px-6 md:px-12">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-12">
            <Badge variant="secondary" className="mb-4">Formations</Badge>
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Nos Formations
            </h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="h-96 bg-gray-200 dark:bg-gray-700 rounded-lg animate-pulse"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div id="trainings" className="bg-gray-50 dark:bg-gray-900 py-16 px-6 md:px-12">
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-12">
          <Badge variant="secondary" className="mb-4">Formations</Badge>
          <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Nos Formations
          </h2>
          <p className="text-gray-600 dark:text-gray-400 text-lg max-w-3xl mx-auto">
            Se contruire par la parole de Dieu et des formations
          </p>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-8 space-y-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <Input
              type="text"
              placeholder="Rechercher une formation, un thème..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10 dark:bg-gray-700 dark:border-gray-600"
            />
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <div className="flex items-center gap-1.5">
              <Filter className="h-4 w-4 text-gray-500" />
              <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Filtres:</span>
            </div>

            <Select value={levelFilter} onValueChange={setLevelFilter}>
              <SelectTrigger className="h-8 w-[140px] text-xs dark:bg-gray-700 dark:border-gray-600">
                <SelectValue placeholder="Niveau" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous</SelectItem>
                <SelectItem value="beginner">Débutant</SelectItem>
                <SelectItem value="intermediate">Intermédiaire</SelectItem>
                <SelectItem value="advanced">Avancé</SelectItem>
              </SelectContent>
            </Select>

            {categories.length > 0 && (
              <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                <SelectTrigger className="h-8 w-[160px] text-xs dark:bg-gray-700 dark:border-gray-600">
                  <SelectValue placeholder="Catégorie" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Toutes</SelectItem>
                  {categories.map(category => (
                    <SelectItem key={category} value={category || ''}>
                      {category}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}

            <Select value={sortBy} onValueChange={setSortBy}>
              <SelectTrigger className="h-8 w-[150px] text-xs dark:bg-gray-700 dark:border-gray-600">
                <SelectValue placeholder="Tri" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="title">Titre</SelectItem>
                {/* <SelectItem value="price-asc">Prix ↑</SelectItem>
                <SelectItem value="price-desc">Prix ↓</SelectItem>
                <SelectItem value="rating">Note</SelectItem> */}
                <SelectItem value="students">Popularité</SelectItem>
              </SelectContent>
            </Select>

            {(searchTerm || levelFilter !== 'all' || categoryFilter !== 'all') && (
              <Button
                variant="outline"
                size="sm"
                onClick={resetFilters}
                className="h-8 text-xs px-2.5 dark:bg-gray-700 dark:border-gray-600"
              >
                Réinitialiser
              </Button>
            )}

            <div className="ml-auto flex items-center gap-0.5 border border-gray-200 dark:border-gray-600 rounded-md p-0.5">
              <Button
                variant={viewMode === 'grid' ? 'default' : 'ghost'}
                size="sm"
                onClick={() => setViewMode('grid')}
                className="h-7 w-7 p-0"
                title="Vue grille"
              >
                <Grid className="h-3.5 w-3.5" />
              </Button>
              <Button
                variant={viewMode === 'list' ? 'default' : 'ghost'}
                size="sm"
                onClick={() => setViewMode('list')}
                className="h-7 w-7 p-0"
                title="Vue liste"
              >
                <List className="h-3.5 w-3.5" />
              </Button>
              <Button
                variant={viewMode === 'table' ? 'default' : 'ghost'}
                size="sm"
                onClick={() => setViewMode('table')}
                className="h-7 w-7 p-0"
                title="Vue tableau"
              >
                <TableIcon className="h-3.5 w-3.5" />
              </Button>
            </div>
          </div>
        </div>

        {filteredTrainings.length === 0 ? (
          <div className="text-center py-12">
            <div className="text-gray-400 text-lg mb-2">Aucune formation trouvée</div>
            <p className="text-gray-600 dark:text-gray-400">
              Essayez de modifier vos critères de recherche ou
              <button onClick={resetFilters} className="text-primary hover:underline ml-1">
                réinitialisez les filtres
              </button>
            </p>
          </div>
        ) : viewMode === 'grid' ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredTrainings.map((training) => (
              <TrainingCard
                key={training.id}
                training={training}
                onEnroll={handleEnroll}
                onViewDetails={handleViewDetails}
              />
            ))}
          </div>
        ) : viewMode === 'list' ? (
          <div className="space-y-4">
            {filteredTrainings.map((training) => (
              <TrainingListItem
                key={training.id}
                training={training}
                onEnroll={handleEnroll}
                onViewDetails={handleViewDetails}
              />
            ))}
          </div>
        ) : (
          <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-[40%]">Formation</TableHead>
                  <TableHead>Niveau</TableHead>
                  <TableHead>Durée</TableHead>
                  <TableHead>Étudiants</TableHead>
                  <TableHead>Note</TableHead>
                  <TableHead className="text-right">Prix</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredTrainings.map((training) => (
                  <TableRow key={training.id} className="group">
                    <TableCell>
                      <div>
                        <div className="font-medium text-gray-900 dark:text-gray-100 group-hover:text-primary dark:group-hover:text-blue-400">
                          {training.title}
                        </div>
                        <div className="text-sm text-gray-600 dark:text-gray-400 line-clamp-1">
                          {training.description}
                        </div>
                        {training.category && (
                          <Badge variant="outline" className="mt-1 text-xs dark:border-gray-600">
                            {training.category}
                          </Badge>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge
                        variant="secondary"
                        className={
                          training.level === 'beginner' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' :
                          training.level === 'intermediate' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' :
                          'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
                        }
                      >
                        {training.level === 'beginner' ? 'Débutant' :
                         training.level === 'intermediate' ? 'Intermédiaire' : 'Avancé'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1 text-gray-700 dark:text-gray-300">
                        <Clock className="h-4 w-4" />
                        <span className="text-sm">{training.duration}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1 text-gray-700 dark:text-gray-300">
                        <Users className="h-4 w-4" />
                        <span className="text-sm">{training.studentsCount || 0}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1">
                        <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                        <span className="text-sm font-medium">{training.rating || 4.5}</span>
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className={`font-bold ${training.price === 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100'}`}>
                        {training.price === 0 ? 'Gratuit' : `${training.price.toLocaleString('fr-FR')}€`}
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => handleViewDetails(training.id)}
                          className="hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                          Détails
                        </Button>
                        <Button
                          size="sm"
                          onClick={() => handleViewDetails(training.id)}
                          className="bg-primary hover:bg-primary dark:bg-primary dark:hover:bg-primary"
                        >
                          S'inscrire
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </div>

      {/* Modal de détails de formation */}
      {selectedTraining && (
        <Dialog open={isDetailsModalOpen} onOpenChange={setIsDetailsModalOpen}>
          <DialogContent className="max-w-5xl max-h-[90vh] flex flex-col p-0 overflow-hidden">
            {/* Fixed Header */}
            <DialogHeader className="px-6 pt-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
              <DialogTitle className="text-3xl font-bold">{selectedTraining.title}</DialogTitle>
              <DialogDescription className="text-base mt-2">{selectedTraining.description}</DialogDescription>
            </DialogHeader>

            {/* Scrollable Content */}
            <div className="space-y-8 px-6 py-6 overflow-y-auto flex-1">
              {/* Informations principales */}
              <div className="flex flex-wrap gap-3">
                <Badge className={
                  selectedTraining.level === 'beginner' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' :
                  selectedTraining.level === 'intermediate' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' :
                  'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
                }>
                  {selectedTraining.level === 'beginner' ? 'Débutant' :
                   selectedTraining.level === 'intermediate' ? 'Intermédiaire' : 'Avancé'}
                </Badge>
                {selectedTraining.category && <Badge variant="outline">{selectedTraining.category}</Badge>}
                <span className="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                  <Clock className="h-4 w-4" />
                  {selectedTraining.duration}
                </span>
                <span className="text-sm flex items-center gap-1">
                  <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                  {selectedTraining.rating}
                </span>
                <span className="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                  <Users className="h-4 w-4" />
                  {selectedTraining.studentsCount} étudiants
                </span>
              </div>

              {/* Thèmes abordés */}
              {selectedTraining.topics && selectedTraining.topics.length > 0 && (
                <div>
                  <h3 className="text-xl font-semibold mb-4 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                    <BookOpen className="h-6 w-6" />
                    Thèmes abordés ({selectedTraining.topics.length})
                  </h3>
                  <div className="space-y-3">
                    {selectedTraining.topics.map((topic) => (
                      <div
                        key={topic.id}
                        className="p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700"
                      >
                        <div className="flex items-start gap-3">
                          <CheckCircle className="h-5 w-5 text-green-500 mt-0.5 shrink-0" />
                          <div className="flex-1">
                            <h4 className="font-medium text-gray-900 dark:text-gray-100">{topic.name}</h4>
                            {topic.description && (
                              <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {topic.description}
                              </p>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Sessions à venir */}
              {selectedTraining.sessions && selectedTraining.sessions.length > 0 && (
                <div>
                  <h3 className="text-xl font-semibold mb-4 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                    <Calendar className="h-6 w-6" />
                    Sessions à venir ({selectedTraining.sessions.filter(s => s.status === 'upcoming').length})
                  </h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {selectedTraining.sessions
                      .filter(session => session.status === 'upcoming')
                      .map((session) => {
                        const startDate = new Date(session.start_date);
                        const endDate = new Date(session.end_date);
                        const availableSpots = (session.max_participants || 0) - (session.enrolled_count || 0);
                        const isSelected = selectedSessionInDetails === session.id;
                        const isDisabled = availableSpots <= 0;

                        return (
                          <div
                            key={session.id}
                            onClick={() => {
                              if (!isDisabled) {
                                setSelectedSessionInDetails(session.id);
                              }
                            }}
                            className={`p-4 rounded-lg border-2 transition-all cursor-pointer ${
                              isSelected
                                ? 'bg-blue-100 dark:bg-blue-900/30 border-primary dark:border-primary'
                                : 'bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800/50 border-blue-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-gray-600'
                            } ${isDisabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                          >
                            <div className="space-y-3">
                              <div className="flex items-start justify-between gap-2">
                                <div className="flex items-center gap-2">
                                  <Calendar className="h-5 w-5 text-primary dark:text-blue-400" />
                                  <div>
                                    <div className="font-medium text-gray-900 dark:text-gray-100">
                                      {startDate.toLocaleDateString('fr-FR', {
                                        day: 'numeric',
                                        month: 'long',
                                        year: 'numeric'
                                      })}
                                    </div>
                                    <div className="text-sm text-gray-600 dark:text-gray-400">
                                      au {endDate.toLocaleDateString('fr-FR', {
                                        day: 'numeric',
                                        month: 'long',
                                        year: 'numeric'
                                      })}
                                    </div>
                                  </div>
                                </div>
                                <Badge
                                  variant={isDisabled ? 'destructive' : availableSpots > 5 ? 'default' : 'secondary'}
                                  className="shrink-0"
                                >
                                  {isDisabled ? 'Complet' : `${availableSpots} places`}
                                </Badge>
                              </div>

                              {session.location && (
                                <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                  <MapPin className="h-4 w-4" />
                                  <span>{session.location}</span>
                                </div>
                              )}

                              <div className="flex items-center justify-between pt-2 border-t border-blue-200 dark:border-gray-700">
                                <div className="text-sm text-gray-600 dark:text-gray-400">
                                  <span className="font-medium">{session.enrolled_count || 0}</span> / {session.max_participants || 0} inscrits
                                </div>
                                {isSelected && !isDisabled && (
                                  <div className="flex items-center gap-1 text-primary dark:text-blue-400 font-medium text-sm">
                                    <CheckCircle className="h-4 w-4 fill-current" />
                                    <span>Sélectionnée</span>
                                  </div>
                                )}
                              </div>
                            </div>
                          </div>
                        );
                      })}
                  </div>
                </div>
              )}
            </div>

            {/* Fixed Footer */}
            <div className="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
              <div>
                <div className={`text-3xl font-bold ${selectedTraining.price === 0 ? 'text-green-600 dark:text-green-400' : 'text-primary dark:text-blue-400'}`}>
                  {selectedTraining.price === 0 ? 'Gratuit' : `${selectedTraining.price.toLocaleString('fr-FR')}€`}
                </div>
                {selectedTraining.price > 0 && (
                  <div className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    ou 3x {Math.round(selectedTraining.price / 3)}€
                  </div>
                )}
              </div>
              <Button
                onClick={() => {
                  // Check if user is authenticated
                  if (!auth?.user) {
                    // Redirect to login
                    router.visit('/login', {
                      data: { intended: window.location.pathname + window.location.search }
                    });
                    return;
                  }

                  // If a session was selected in details, pre-select it in the enrollment form
                  if (selectedSessionInDetails) {
                    setEnrollmentForm({ ...enrollmentForm, selectedSessionId: selectedSessionInDetails });
                  }
                  setIsEnrollmentModalOpen(true);
                }}
                size="lg"
                className="bg-primary hover:bg-primary text-lg px-8"
              >
                S'inscrire maintenant
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      )}

      {/* Enrollment Modal */}
      {selectedTraining && (
        <Dialog open={isEnrollmentModalOpen} onOpenChange={setIsEnrollmentModalOpen}>
          <DialogContent className="max-w-2xl max-h-[90vh] flex flex-col p-0 overflow-hidden">
            {/* Fixed Header */}
            <DialogHeader className="px-6 pt-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
              <DialogTitle>Inscription - {selectedTraining.title}</DialogTitle>
              <DialogDescription>
                Remplissez le formulaire ci-dessous pour vous inscrire à cette formation
              </DialogDescription>
            </DialogHeader>

            <form
              onSubmit={(e) => {
                e.preventDefault();
                // Submit enrollment using Inertia
                router.post(`/trainings/${selectedTraining.id}/enroll`, enrollmentForm, {
                  onSuccess: () => {
                    setIsEnrollmentModalOpen(false);
                    setIsDetailsModalOpen(false);
                    // Reset form
                    setEnrollmentForm({
                      firstName: '',
                      lastName: '',
                      email: '',
                      phone: '',
                      motivation: '',
                      paymentMethod: 'monthly',
                      selectedSessionId: '',
                      hasReadTerms: false,
                      hasReadPrivacyPolicy: false,
                    });
                  },
                });
              }}
              className="flex flex-col flex-1 overflow-hidden"
            >
              {/* Scrollable Form Content */}
              <div className="space-y-6 px-6 py-4 overflow-y-auto flex-1">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Prénom
                  </label>
                  <input
                    type="text"
                    value={enrollmentForm.firstName}
                    onChange={(e) => setEnrollmentForm({ ...enrollmentForm, firstName: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nom
                  </label>
                  <input
                    type="text"
                    value={enrollmentForm.lastName}
                    onChange={(e) => setEnrollmentForm({ ...enrollmentForm, lastName: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Email
                  </label>
                  <input
                    type="email"
                    value={enrollmentForm.email}
                    onChange={(e) => setEnrollmentForm({ ...enrollmentForm, email: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Téléphone
                  </label>
                  <input
                    type="tel"
                    value={enrollmentForm.phone}
                    onChange={(e) => setEnrollmentForm({ ...enrollmentForm, phone: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Motivation <span className="text-red-500">*</span>
                  <span className="text-xs text-gray-500 ml-2">(minimum 50 caractères)</span>
                </label>
                <textarea
                  value={enrollmentForm.motivation}
                  onChange={(e) => setEnrollmentForm({ ...enrollmentForm, motivation: e.target.value })}
                  rows={4}
                  required
                  minLength={50}
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white"
                  placeholder="Expliquez pourquoi vous souhaitez suivre cette formation..."
                />
                <div className="text-xs text-gray-500 mt-1">
                  {enrollmentForm.motivation.length}/50 caractères
                </div>
              </div>

              {/* Session Selection */}
              {selectedTraining.sessions && selectedTraining.sessions.length > 0 && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Choisir une session <span className="text-red-500">*</span>
                  </label>
                  <div className="space-y-2 max-h-64 overflow-y-auto">
                    {selectedTraining.sessions
                      .filter(session => session.status === 'upcoming')
                      .map((session) => {
                        const availableSpots = session.max_participants - session.enrolled_count;
                        const isDisabled = availableSpots <= 0;

                        return (
                          <label
                            key={session.id}
                            className={`flex items-start p-3 border rounded-lg cursor-pointer transition-colors ${
                              enrollmentForm.selectedSessionId === session.id
                                ? 'border-primary bg-blue-50 dark:bg-blue-900/20'
                                : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                            } ${isDisabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                          >
                            <input
                              type="radio"
                              name="session"
                              value={session.id}
                              checked={enrollmentForm.selectedSessionId === session.id}
                              onChange={(e) => setEnrollmentForm({ ...enrollmentForm, selectedSessionId: e.target.value })}
                              disabled={isDisabled}
                              className="mt-1 mr-3"
                              required
                            />
                            <div className="flex-1">
                              <div className="flex items-center justify-between mb-1">
                                <div className="flex items-center gap-2">
                                  <Calendar className="h-4 w-4 text-primary dark:text-blue-400" />
                                  <span className="font-medium text-gray-900 dark:text-gray-100">
                                    {new Date(session.start_date).toLocaleDateString('fr-FR', {
                                      weekday: 'long',
                                      year: 'numeric',
                                      month: 'long',
                                      day: 'numeric'
                                    })}
                                  </span>
                                </div>
                                <Badge variant={isDisabled ? 'destructive' : 'default'} className="text-xs">
                                  {availableSpots > 0 ? `${availableSpots} places` : 'Complet'}
                                </Badge>
                              </div>
                              <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <div className="flex items-center gap-1">
                                  <MapPin className="h-3 w-3" />
                                  <span>{session.location}</span>
                                </div>
                              </div>
                            </div>
                          </label>
                        );
                      })}
                  </div>
                </div>
              )}

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Mode de paiement <span className="text-red-500">*</span>
                </label>
                <div className="space-y-2">
                  <label className="flex items-center">
                    <input
                      type="radio"
                      name="paymentMethod"
                      value="monthly"
                      checked={enrollmentForm.paymentMethod === 'monthly'}
                      onChange={(e) => setEnrollmentForm({ ...enrollmentForm, paymentMethod: e.target.value })}
                      className="mr-2"
                    />
                    <span className="text-gray-700 dark:text-gray-300">
                      Mensuel - 3 versements de {selectedTraining.price > 0 ? `${Math.round(selectedTraining.price / 3)}€` : '0€'}
                    </span>
                  </label>
                  <label className="flex items-center">
                    <input
                      type="radio"
                      name="paymentMethod"
                      value="quarterly"
                      checked={enrollmentForm.paymentMethod === 'quarterly'}
                      onChange={(e) => setEnrollmentForm({ ...enrollmentForm, paymentMethod: e.target.value })}
                      className="mr-2"
                    />
                    <span className="text-gray-700 dark:text-gray-300">
                      Trimestriel - Paiement unique par trimestre
                    </span>
                  </label>
                  <label className="flex items-center">
                    <input
                      type="radio"
                      name="paymentMethod"
                      value="full"
                      checked={enrollmentForm.paymentMethod === 'full'}
                      onChange={(e) => setEnrollmentForm({ ...enrollmentForm, paymentMethod: e.target.value })}
                      className="mr-2"
                    />
                    <span className="text-gray-700 dark:text-gray-300">
                      Paiement complet - {selectedTraining.price > 0 ? `${selectedTraining.price.toLocaleString('fr-FR')}€` : 'Gratuit'}
                    </span>
                  </label>
                </div>
              </div>

              <div className="space-y-2 border-t pt-4">
                <label className="flex items-start">
                  <input
                    type="checkbox"
                    checked={enrollmentForm.hasReadTerms}
                    onChange={(e) => setEnrollmentForm({ ...enrollmentForm, hasReadTerms: e.target.checked })}
                    required
                    className="mt-1 mr-2"
                  />
                  <span className="text-sm text-gray-700 dark:text-gray-300">
                    J'ai lu et j'accepte les <a href="#" className="text-primary hover:underline">conditions générales</a> <span className="text-red-500">*</span>
                  </span>
                </label>
                <label className="flex items-start">
                  <input
                    type="checkbox"
                    checked={enrollmentForm.hasReadPrivacyPolicy}
                    onChange={(e) => setEnrollmentForm({ ...enrollmentForm, hasReadPrivacyPolicy: e.target.checked })}
                    required
                    className="mt-1 mr-2"
                  />
                  <span className="text-sm text-gray-700 dark:text-gray-300">
                    J'ai lu et j'accepte la <a href="#" className="text-primary hover:underline">politique de confidentialité</a> <span className="text-red-500">*</span>
                  </span>
                </label>
              </div>
              </div>

              {/* Fixed Footer */}
              <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
                <Button
                  type="button"
                  onClick={() => setIsEnrollmentModalOpen(false)}
                  className="bg-gray-300 hover:bg-gray-400 text-gray-800"
                >
                  Annuler
                </Button>
                <Button
                  type="submit"
                  className="bg-primary hover:bg-primary"
                  disabled={
                    !enrollmentForm.motivation ||
                    enrollmentForm.motivation.length < 50 ||
                    !enrollmentForm.hasReadTerms ||
                    !enrollmentForm.hasReadPrivacyPolicy ||
                    (selectedTraining.sessions && selectedTraining.sessions.length > 0 && !enrollmentForm.selectedSessionId)
                  }
                >
                  Confirmer l'inscription
                </Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      )}
    </div>
  );
};

export default TrainingBrowseSection;
