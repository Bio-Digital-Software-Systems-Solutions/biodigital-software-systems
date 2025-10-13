import React, { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Checkbox } from '@/Components/ui/checkbox';
import { Separator } from '@/Components/ui/separator';
import { Progress } from '@/Components/ui/progress';
import { toast } from 'sonner';
import {
  Calendar,
  Clock,
  MapPin,
  User,
  Users,
  Star,
  BookOpen,
  CreditCard,
  FileText,
  CheckCircle,
  Info,
  Euro,
  X,
  ArrowLeft,
  ClipboardList,
  Play,
  Settings,
  Target
} from 'lucide-react';

interface Topic {
  id: number;
  name: string;
  description: string;
  order: number;
}

interface TrainingClass {
  id: number;
  date: string;
  start_time: string;
  end_time: string;
  room: string;
  notes?: string;
}

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
  status: 'draft' | 'published' | 'archived';
  user_attempt?: {
    score: number;
    max_score: number;
    passed: boolean;
    completed_at: string;
  } | null;
}

interface Training {
  id: number;
  uuid: string;
  title: string;
  description: string;
  duration: string;
  level: 'beginner' | 'intermediate' | 'advanced';
  price: number;
  image?: string;
  image_url?: string;
  category?: string;
  rating: number;
  students_count: number;
  topics: Topic[];
  classes: TrainingClass[];
  quizzes?: Quiz[];
  materials?: any[];
  evaluations?: any[];
}

interface Props {
  auth?: {
    user: {
      id: number;
      name: string;
      email: string;
      first_name?: string;
      last_name?: string;
      permissions?: string[];
    };
  };
  training: Training;
}

// Composant modal d'inscription
const EnrollmentModal = ({ isOpen, onClose, training, auth }) => {
  const [step, setStep] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState<any>({});

  const [formData, setFormData] = useState({
    selectedClassId: '',
    firstName: auth?.user?.first_name || '',
    lastName: auth?.user?.last_name || '',
    email: auth?.user?.email || '',
    phone: '',
    motivation: '',
    previousExperience: '',
    hasReadTerms: false,
    hasReadPrivacyPolicy: false,
    wantsNewsletter: false,
    paymentMethod: 'card',
  });

  const handleInputChange = (field: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));

    if (errors[field]) {
      setErrors((prev: any) => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }
  };

  const validateStep = (stepNumber: number) => {
    const newErrors: any = {};

    switch (stepNumber) {
      case 1:
        if (!formData.selectedClassId) {
          newErrors.selectedClassId = 'Vous devez choisir une session';
        }
        break;

      case 2:
        if (!formData.firstName.trim()) newErrors.firstName = 'Le prénom est requis';
        if (!formData.lastName.trim()) newErrors.lastName = 'Le nom est requis';
        if (!formData.email.trim()) newErrors.email = 'L\'email est requis';
        if (formData.email && !/\S+@\S+\.\S+/.test(formData.email)) {
          newErrors.email = 'Email invalide';
        }
        if (!formData.phone.trim()) newErrors.phone = 'Le téléphone est requis';
        break;

      case 3:
        if (!formData.motivation.trim()) {
          newErrors.motivation = 'La motivation est requise (minimum 50 caractères)';
        } else if (formData.motivation.length < 50) {
          newErrors.motivation = 'La motivation doit contenir au moins 50 caractères';
        }
        break;

      case 4:
        if (!formData.hasReadTerms) {
          newErrors.hasReadTerms = 'Vous devez accepter les conditions générales';
        }
        if (!formData.hasReadPrivacyPolicy) {
          newErrors.hasReadPrivacyPolicy = 'Vous devez accepter la politique de confidentialité';
        }
        if (!formData.paymentMethod) {
          newErrors.paymentMethod = 'Veuillez choisir un mode de paiement';
        }
        break;
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleNext = () => {
    if (validateStep(step)) {
      setStep(prev => prev + 1);
    }
  };

  const handlePrevious = () => {
    setStep(prev => prev - 1);
  };

  const handleSubmit = async () => {
    if (!validateStep(4)) return;

    setIsSubmitting(true);

    router.post(route('trainings.enroll', training.uuid), formData, {
      onSuccess: () => {
        toast.success('Inscription confirmée!', {
          description: 'Votre demande d\'inscription a été envoyée avec succès. Vous recevrez une confirmation par email.',
        });
        onClose();
        setStep(1);
      },
      onError: (errors) => {
        setErrors(errors);

        // Extract the first error message to show in the toast
        const firstErrorKey = Object.keys(errors)[0];
        const firstErrorMessage = errors[firstErrorKey];

        // Determine which step has the error and navigate to it
        if (['selectedClassId'].includes(firstErrorKey)) {
          setStep(1);
        } else if (['firstName', 'lastName', 'email', 'phone'].includes(firstErrorKey)) {
          setStep(2);
        } else if (['motivation', 'previousExperience'].includes(firstErrorKey)) {
          setStep(3);
        } else if (['hasReadTerms', 'hasReadPrivacyPolicy', 'paymentMethod'].includes(firstErrorKey)) {
          setStep(4);
        }

        // Show specific error message if available, otherwise show generic message
        toast.error('Erreur lors de l\'inscription', {
          description: firstErrorMessage || 'Une erreur est survenue lors de l\'inscription. Veuillez vérifier vos informations et réessayer.',
        });
      },
      onFinish: () => {
        setIsSubmitting(false);
      }
    });
  };

  const totalSteps = 4;

  const getLevelLabel = (level: string) => {
    switch (level) {
      case 'beginner': return 'Débutant';
      case 'intermediate': return 'Intermédiaire';
      case 'advanced': return 'Avancé';
      default: return level;
    }
  };

  const getLevelColor = (level: string) => {
    switch (level) {
      case 'beginner': return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300';
      case 'intermediate': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300';
      case 'advanced': return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] flex flex-col">
        {/* En-tête fixe */}
        <div className="p-6 border-b dark:border-gray-700">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h2 className="text-2xl font-bold flex items-center gap-2 dark:text-white">
                <BookOpen className="h-6 w-6 text-primary" />
                Inscription à la formation
              </h2>
              <p className="text-gray-600 dark:text-gray-400 mt-1">
                {training.title} - Étape {step} sur {totalSteps}
              </p>
            </div>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl p-2"
            >
              <X className="h-6 w-6" />
            </button>
          </div>

          {/* Indicateur de progression */}
          <div className="flex items-center justify-between">
            {[1, 2, 3, 4].map((stepNumber) => (
              <div key={stepNumber} className="flex items-center">
                <div className={`
                  w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                  ${step >= stepNumber
                    ? 'bg-primary text-white'
                    : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                  }
                `}>
                  {step > stepNumber ? <CheckCircle className="h-4 w-4" /> : stepNumber}
                </div>
                {stepNumber < 4 && (
                  <div className={`w-12 h-1 mx-2 ${
                    step > stepNumber ? 'bg-primary' : 'bg-gray-200 dark:bg-gray-700'
                  }`} />
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Contenu scrollable */}
        <div className="flex-1 overflow-y-auto p-6">
          {/* Résumé de la formation */}
          <Card className="mb-6">
            <CardContent className="p-4">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <h3 className="text-lg font-semibold">{training.title}</h3>
                  <div className="flex items-center gap-2 mt-2">
                    <Badge className={getLevelColor(training.level)}>
                      {getLevelLabel(training.level)}
                    </Badge>
                    {training.category && <Badge variant="outline">{training.category}</Badge>}
                    <span className="text-sm text-gray-600 dark:text-gray-400">• {training.duration}</span>
                  </div>
                </div>
                <div className="text-right">
                  <div className="text-2xl font-bold text-primary">
                    {training.price === 0 ? 'Gratuit' : `${training.price.toLocaleString('fr-FR')}€`}
                  </div>
                  {training.price > 0 && (
                    <div className="text-sm text-gray-600 dark:text-gray-400">
                      ou 3x {Math.round(training.price / 3)}€
                    </div>
                  )}
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Étape 1: Choix de la session */}
          {step === 1 && (
            <div className="space-y-6">
              <div>
                <h3 className="text-lg font-semibold mb-4 flex items-center gap-2 dark:text-white">
                  <Calendar className="h-5 w-5" />
                  Choisir une session *
                </h3>

                {training.classes && training.classes.length > 0 ? (
                  <div className="space-y-3">
                    {training.classes.map((classItem) => (
                      <Card
                key={classItem.id}
                className={`cursor-pointer border-2 transition-all ${
                  formData.selectedClassId === classItem.id.toString()
                    ? 'border-primary bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                }`}
                onClick={() => handleInputChange('selectedClassId', classItem.id.toString())}
                      >
                <CardContent className="p-4">
                  <div className="flex items-start gap-4">
                    <Calendar className="h-5 w-5 text-primary dark:text-blue-400 mt-1" />
                    <div className="flex-1">
                      <div className="font-medium text-gray-900 dark:text-white">
                        {new Date(classItem.date).toLocaleDateString('fr-FR', {
                          weekday: 'long',
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric'
                        })}
                      </div>
                      <div className="text-sm text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-3">
                        <span className="flex items-center gap-1">
                          <Clock className="h-4 w-4" />
                          {classItem.start_time} - {classItem.end_time}
                        </span>
                        {classItem.room && (
                          <span className="flex items-center gap-1">
                            <MapPin className="h-4 w-4" />
                            {classItem.room}
                          </span>
                        )}
                      </div>
                      {classItem.notes && (
                        <div className="text-sm text-gray-500 dark:text-gray-400 mt-2">
                          {classItem.notes}
                        </div>
                      )}
                    </div>
                    {formData.selectedClassId === classItem.id.toString() && (
                      <CheckCircle className="h-5 w-5 text-primary dark:text-blue-400" />
                    )}
                  </div>
                </CardContent>
                      </Card>
                    ))}
                  </div>
                ) : (
                  <div className="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-start gap-3">
                    <Info className="h-5 w-5 text-primary dark:text-blue-400 mt-0.5 shrink-0" />
                    <p className="text-sm text-gray-700 dark:text-gray-300">
                      Aucune session disponible pour le moment. Veuillez réessayer plus tard.
                    </p>
                  </div>
                )}

                {errors.selectedClassId && (
                  <p className="text-red-500 text-sm mt-2">{errors.selectedClassId}</p>
                )}
              </div>
            </div>
          )}

          {/* Étape 2: Informations personnelles */}
          {step === 2 && (
            <div className="space-y-6">
              <div>
                <h3 className="text-lg font-semibold mb-4 flex items-center gap-2 dark:text-white">
                  <User className="h-5 w-5" />
                  Informations personnelles
                </h3>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="firstName">Prénom *</Label>
                    <Input
                      id="firstName"
                      value={formData.firstName}
                      onChange={(e) => handleInputChange('firstName', e.target.value)}
                      className={errors.firstName ? 'border-red-500' : ''}
                    />
                    {errors.firstName && (
                      <p className="text-red-500 text-sm mt-1">{errors.firstName}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="lastName">Nom *</Label>
                    <Input
                      id="lastName"
                      value={formData.lastName}
                      onChange={(e) => handleInputChange('lastName', e.target.value)}
                      className={errors.lastName ? 'border-red-500' : ''}
                    />
                    {errors.lastName && (
                      <p className="text-red-500 text-sm mt-1">{errors.lastName}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="email">Email *</Label>
                    <Input
                      id="email"
                      type="email"
                      value={formData.email}
                      onChange={(e) => handleInputChange('email', e.target.value)}
                      className={errors.email ? 'border-red-500' : ''}
                    />
                    {errors.email && (
                      <p className="text-red-500 text-sm mt-1">{errors.email}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="phone">Téléphone *</Label>
                    <Input
                      id="phone"
                      value={formData.phone}
                      onChange={(e) => handleInputChange('phone', e.target.value)}
                      className={errors.phone ? 'border-red-500' : ''}
                    />
                    {errors.phone && (
                      <p className="text-red-500 text-sm mt-1">{errors.phone}</p>
                    )}
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Étape 3: Motivation */}
          {step === 3 && (
            <div className="space-y-6">
              <div>
                <h3 className="text-lg font-semibold mb-4 flex items-center gap-2 dark:text-white">
                  <Star className="h-5 w-5" />
                  Motivation et expérience
                </h3>

                <div className="space-y-4">
                  <div>
                    <Label htmlFor="motivation">
                      Pourquoi souhaitez-vous suivre cette formation ? *
                    </Label>
                    <Textarea
                      id="motivation"
                      value={formData.motivation}
                      onChange={(e) => handleInputChange('motivation', e.target.value)}
                      placeholder="Décrivez vos objectifs, ce que vous espérez apprendre..."
                      className={`min-h-[120px] ${errors.motivation ? 'border-red-500' : ''}`}
                    />
                    <div className="flex justify-between items-center mt-1">
                      {errors.motivation && (
                <p className="text-red-500 text-sm">{errors.motivation}</p>
                      )}
                      <p className="text-sm text-gray-500 dark:text-gray-400 ml-auto">
                {formData.motivation.length}/500 caractères (min. 50)
                      </p>
                    </div>
                  </div>

                  <div>
                    <Label htmlFor="previousExperience">
                      Expérience préalable dans le domaine (optionnel)
                    </Label>
                    <Textarea
                      id="previousExperience"
                      value={formData.previousExperience}
                      onChange={(e) => handleInputChange('previousExperience', e.target.value)}
                      placeholder="Décrivez votre expérience ou formation antérieure..."
                      className="min-h-[100px]"
                    />
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Étape 4: Paiement et conditions */}
          {step === 4 && (
            <div className="space-y-6">
              <div>
                <h3 className="text-lg font-semibold mb-4 flex items-center gap-2 dark:text-white">
                  <CreditCard className="h-5 w-5" />
                  Paiement et conditions
                </h3>

                {training.price > 0 && (
                  <div className="mb-6">
                    <Label>Mode de paiement *</Label>
                    <div className="space-y-3 mt-2">
                      <Card
                className={`cursor-pointer border-2 transition-all ${
                  formData.paymentMethod === 'card'
                    ? 'border-primary bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                }`}
                onClick={() => handleInputChange('paymentMethod', 'card')}
                      >
                <CardContent className="p-4">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <CreditCard className="h-5 w-5" />
                      <div>
                        <div className="font-medium">Carte bancaire</div>
                        <div className="text-sm text-gray-600 dark:text-gray-400">Paiement sécurisé immédiat</div>
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="font-bold">{training.price.toLocaleString('fr-FR')}€</div>
                    </div>
                  </div>
                </CardContent>
                      </Card>
                    </div>
                  </div>
                )}

                <Separator />

                <div className="space-y-4">
                  <div className="flex items-start space-x-2">
                    <Checkbox
                      id="terms"
                      checked={formData.hasReadTerms}
                      onCheckedChange={(checked) => handleInputChange('hasReadTerms', checked)}
                    />
                    <div className="grid gap-1.5 leading-none">
                      <Label htmlFor="terms" className="text-sm font-medium leading-none">
                J'accepte les conditions générales de vente *
                      </Label>
                      {errors.hasReadTerms && (
                <p className="text-red-500 text-xs">{errors.hasReadTerms}</p>
                      )}
                    </div>
                  </div>

                  <div className="flex items-start space-x-2">
                    <Checkbox
                      id="privacy"
                      checked={formData.hasReadPrivacyPolicy}
                      onCheckedChange={(checked) => handleInputChange('hasReadPrivacyPolicy', checked)}
                    />
                    <div className="grid gap-1.5 leading-none">
                      <Label htmlFor="privacy" className="text-sm font-medium leading-none">
                J'accepte la politique de confidentialité *
                      </Label>
                      {errors.hasReadPrivacyPolicy && (
                <p className="text-red-500 text-xs">{errors.hasReadPrivacyPolicy}</p>
                      )}
                    </div>
                  </div>

                  <div className="flex items-start space-x-2">
                    <Checkbox
                      id="newsletter"
                      checked={formData.wantsNewsletter}
                      onCheckedChange={(checked) => handleInputChange('wantsNewsletter', checked)}
                    />
                    <Label htmlFor="newsletter" className="text-sm font-medium leading-none">
                      Je souhaite recevoir des informations sur les nouvelles formations
                    </Label>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Footer fixe avec boutons */}
        <div className="p-6 border-t dark:border-gray-700 bg-white dark:bg-gray-800">
          <div className="flex justify-between">
            <div className="flex gap-2">
              {step > 1 && (
                <Button variant="outline" onClick={handlePrevious}>
                  Précédent
                </Button>
              )}
            </div>

            <div className="flex gap-2">
              <Button variant="outline" onClick={onClose}>
                Annuler
              </Button>

              {step < totalSteps ? (
                <Button onClick={handleNext}>
                  Suivant
                </Button>
              ) : (
                <Button
                  onClick={handleSubmit}
                  disabled={isSubmitting}
                  className="bg-green-600 hover:bg-green-700"
                >
                  {isSubmitting ? 'Inscription...' : 'Confirmer l\'inscription'}
                </Button>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

const TrainingShow: React.FC<Props> = ({ auth, training }) => {
  // Modal is closed by default, only opens when user clicks enroll button
  const [isModalOpen, setIsModalOpen] = useState(false);

  // Helper function to check if user has a permission
  const hasPermission = (permission: string): boolean => {
    return auth?.user?.permissions?.includes(permission) || false;
  };

  const getLevelLabel = (level: string) => {
    switch (level) {
      case 'beginner': return 'Débutant';
      case 'intermediate': return 'Intermédiaire';
      case 'advanced': return 'Avancé';
      default: return level;
    }
  };

  const getLevelColor = (level: string) => {
    switch (level) {
      case 'beginner': return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300';
      case 'intermediate': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300';
      case 'advanced': return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
  };

  const content = (
    <>
      <Head title={training.title} />

      <div className="space-y-6 px-6 py-8">
        {/* Bouton retour */}
        <Button
          variant="ghost"
          onClick={() => window.history.back()}
          className="flex items-center gap-2"
        >
          <ArrowLeft className="h-4 w-4" />
          Retour
        </Button>

        {/* En-tête de la formation */}
        <Card>
          {training.image_url && (
            <div className="w-full h-64 overflow-hidden rounded-t-lg">
              <img
                src={training.image_url}
                alt={training.title}
                className="w-full h-full object-cover"
              />
            </div>
          )}
          <CardHeader>
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <CardTitle className="text-2xl mb-2">{training.title}</CardTitle>
                <CardDescription className="mb-4 text-base">
                  {training.description}
                </CardDescription>
                <div className="flex items-center gap-2 flex-wrap">
                  <Badge className={getLevelColor(training.level)}>
                    {getLevelLabel(training.level)}
                  </Badge>
                  {training.category && <Badge variant="outline">{training.category}</Badge>}
                  <span className="text-sm text-gray-600 dark:text-gray-400">• {training.duration}</span>
                  <div className="flex items-center gap-1">
                    <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                    <span className="text-sm">{training.rating}</span>
                  </div>
                  <div className="flex items-center gap-1">
                    <Users className="h-4 w-4" />
                    <span className="text-sm">{training.students_count} étudiants</span>
                  </div>
                </div>
              </div>
              <div className="text-right ml-6">
                <div className="text-3xl font-bold text-primary dark:text-blue-400">
                  {training.price === 0 ? 'Gratuit' : `${training.price.toLocaleString('fr-FR')}€`}
                </div>
                {training.price > 0 && (
                  <div className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    ou 3x {Math.round(training.price / 3)}€
                  </div>
                )}
                {auth ? (
                  <Button
                    onClick={() => setIsModalOpen(true)}
                    className="bg-primary hover:bg-primary mt-4"
                    size="lg"
                  >
                    S'inscrire
                  </Button>
                ) : (
                  <Link href="/login">
                    <Button
                      className="bg-primary hover:bg-primary mt-4"
                      size="lg"
                    >
                      Se connecter pour s'inscrire
                    </Button>
                  </Link>
                )}
              </div>
            </div>
          </CardHeader>
        </Card>

        {/* Thèmes abordés */}
        {training.topics && training.topics.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <BookOpen className="h-5 w-5" />
                Thèmes abordés ({training.topics.length})
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {training.topics.map((topic) => (
                  <div
                    key={topic.id}
                    className="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600"
                  >
                    <div className="flex items-start gap-3">
                      <CheckCircle className="h-5 w-5 text-green-500 mt-0.5 shrink-0" />
                      <div>
                <h4 className="font-medium dark:text-white">{topic.name}</h4>
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
            </CardContent>
          </Card>
        )}

        {/* Sessions de cours */}
        {training.classes && training.classes.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Calendar className="h-5 w-5" />
                Sessions à venir
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {training.classes.slice(0, 5).map((classItem) => (
                  <div
                    key={classItem.id}
                    className="flex items-center gap-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600"
                  >
                    <Calendar className="h-5 w-5 text-primary dark:text-blue-400" />
                    <div className="flex-1">
                      <div className="font-medium dark:text-white">{classItem.date}</div>
                      <div className="text-sm text-gray-600 dark:text-gray-400">
                {classItem.start_time} - {classItem.end_time}
                {classItem.room && ` • ${classItem.room}`}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Quiz & Évaluations - Only visible to teachers, admins, and students with take quizzes permission */}
        {(hasPermission('view quizzes') || hasPermission('manage quizzes') || hasPermission('take quizzes')) && (
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="flex items-center gap-2">
                  <ClipboardList className="h-5 w-5" />
                  Quiz & Évaluations
                </CardTitle>
                {hasPermission('create quizzes') && (
                  <div className="flex gap-2">
                    <Link href={route('trainings.quizzes.create', training.uuid)}>
                      <Button size="sm">
                        <Play className="h-4 w-4 mr-2" />
                        Créer un quiz
                      </Button>
                    </Link>
                    {training.quizzes && training.quizzes.length > 0 && (
                      <Link href={route('trainings.quizzes.index', training.uuid)}>
                        <Button variant="outline" size="sm">
                          <Settings className="h-4 w-4 mr-2" />
                          Gérer les quiz
                        </Button>
                      </Link>
                    )}
                  </div>
                )}
              </div>
            </CardHeader>
            <CardContent>
              {training.quizzes && training.quizzes.length > 0 ? (
                <div className="space-y-4">
                  {training.quizzes.map((quiz) => {
                    const isPublished = quiz.status === 'published';
                    const isAvailable = isPublished &&
                      quiz.is_active &&
                      (!quiz.available_from || new Date(quiz.available_from) <= new Date()) &&
                      (!quiz.available_until || new Date(quiz.available_until) >= new Date());

                    const hasCompleted = !!quiz.user_attempt;

                    return (
                      <Card key={quiz.id} className="border-2">
                        <CardContent className="p-4">
                          <div className="flex items-start justify-between gap-4">
                            <div className="flex-1">
                              <div className="flex items-center gap-3 mb-2">
                                <h4 className="font-semibold text-lg dark:text-white">
                                  {quiz.title}
                                </h4>
                                {quiz.status === 'draft' && (
                                  <Badge variant="secondary" className="bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    Brouillon
                                  </Badge>
                                )}
                                {!isAvailable && isPublished && (
                                  <Badge variant="secondary">Non disponible</Badge>
                                )}
                                {hasCompleted && (
                                  <Badge
                                    variant="outline"
                                    className={
                                      quiz.user_attempt?.passed
                                        ? 'bg-green-50 text-green-700 border-green-200'
                                        : 'bg-red-50 text-red-700 border-red-200'
                                    }
                                  >
                                    {quiz.user_attempt?.passed ? '✓ Réussi' : '✗ Échoué'}
                                  </Badge>
                                )}
                              </div>

                              {quiz.description && (
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                  {quiz.description}
                                </p>
                              )}

                              <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <span className="flex items-center gap-1">
                                  <Clock className="h-4 w-4" />
                                  {quiz.duration_minutes} min
                                </span>
                                <span className="flex items-center gap-1">
                                  <Target className="h-4 w-4" />
                                  {quiz.passing_score}/{quiz.max_score} pts pour réussir
                                </span>
                              </div>

                              {quiz.available_from && quiz.available_until && (
                                <div className="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                  Disponible du {new Date(quiz.available_from).toLocaleDateString('fr-FR')}
                                  {' au '}
                                  {new Date(quiz.available_until).toLocaleDateString('fr-FR')}
                                </div>
                              )}

                              {hasCompleted && quiz.user_attempt && (
                                <div className="mt-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                  <div className="text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Votre score: </span>
                                    <span className={`font-semibold ${
                                      quiz.user_attempt.passed
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-red-600 dark:text-red-400'
                                    }`}>
                                      {quiz.user_attempt.score}/{quiz.user_attempt.max_score} points
                                      {' '}
                                      ({((quiz.user_attempt.score / quiz.user_attempt.max_score) * 100).toFixed(1)}%)
                                    </span>
                                  </div>
                                  <div className="text-xs text-gray-500 mt-1">
                                    Complété le {new Date(quiz.user_attempt.completed_at).toLocaleDateString('fr-FR')}
                                  </div>
                                </div>
                              )}
                            </div>

                            {auth?.user && !hasCompleted && isAvailable && hasPermission('take quizzes') && (
                              <Link href={route('quizzes.start', quiz.uuid)}>
                                <Button className="bg-blue-600 hover:bg-blue-700">
                                  <Play className="h-4 w-4 mr-2" />
                                  Commencer
                                </Button>
                              </Link>
                            )}

                            {!auth?.user && (
                              <Link href="/login">
                                <Button variant="outline">
                                  Se connecter
                                </Button>
                              </Link>
                            )}
                          </div>
                        </CardContent>
                      </Card>
                    );
                  })}
                </div>
              ) : (
                <div className="text-center py-8">
                  <ClipboardList className="h-12 w-12 mx-auto text-gray-400 mb-4" />
                  <p className="text-gray-600 dark:text-gray-400 mb-4">
                    Aucun quiz n'a encore été créé pour cette formation.
                  </p>
                  {hasPermission('create quizzes') && (
                    <Link href={route('trainings.quizzes.create', training.uuid)}>
                      <Button>
                        <Play className="h-4 w-4 mr-2" />
                        Créer le premier quiz
                      </Button>
                    </Link>
                  )}
                </div>
              )}
            </CardContent>
          </Card>
        )}
      </div>

      {/* Modal d'inscription */}
      {auth && (
        <EnrollmentModal
          isOpen={isModalOpen}
          onClose={() => setIsModalOpen(false)}
          training={training}
          auth={auth}
        />
      )}
    </>
  );

  return auth ? (
    <DashboardLayout>{content}</DashboardLayout>
  ) : (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {content}
      </div>
    </div>
  );
};

export default TrainingShow;
