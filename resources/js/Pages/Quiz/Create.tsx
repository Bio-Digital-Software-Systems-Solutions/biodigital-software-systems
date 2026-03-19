import { useState, FormEvent, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Separator } from '@/Components/ui/separator';
import { Switch } from '@/Components/ui/switch';
import { Plus, Trash2, Save, ArrowLeft, GripVertical } from 'lucide-react';
import { toast } from 'sonner';

interface Training {
    id: number;
    uuid: string;
    name: string;
}

interface TrainingClassMaterial {
    id: number;
    uuid: string;
    title: string;
    type: string;
    order: number;
}

interface TrainingClass {
    id: number;
    uuid: string;
    name: string;
    date: string;
    start_time: string;
    end_time: string;
    room?: string;
    students_count: number;
    materials: TrainingClassMaterial[];
}

interface Props {
    training: Training;
    trainingClasses: TrainingClass[];
}

interface Question {
    id: string;
    question: string;
    type: 'multiple_choice' | 'true_false' | 'short_answer';
    options: string[];
    correct_answers: any[];
    feedback_correct?: string;
    feedback_incorrect?: string;
    points: number;
}

interface Quiz {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    duration_minutes: number;
    passing_score: number;
    available_from?: string;
    available_until?: string;
    is_active: boolean;
    max_attempts: number;
    score_display: 'best' | 'last' | 'average';
    status: 'draft' | 'published' | 'archived';
}

export default function QuizCreate({ training, trainingClasses }: Props) {
    const formRef = useRef<HTMLFormElement>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Quiz details
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [durationMinutes, setDurationMinutes] = useState(30);
    const [passingScore, setPassingScore] = useState(60);
    const [availableFrom, setAvailableFrom] = useState('');
    const [availableUntil, setAvailableUntil] = useState('');
    const [isActive, setIsActive] = useState(true);
    const [maxAttempts, setMaxAttempts] = useState(1);
    const [scoreDisplay, setScoreDisplay] = useState<'best' | 'last' | 'average'>('best');
    const [status, setStatus] = useState<'draft' | 'published' | 'archived'>('draft');

    // Class and material assignments
    const [assignedClasses, setAssignedClasses] = useState<number[]>([]);
    const [assignedMaterials, setAssignedMaterials] = useState<number[]>([]);

    // Questions
    const [questions, setQuestions] = useState<Question[]>([]);

    const addQuestion = () => {
        const newQuestion: Question = {
            id: `q_${Date.now()}_${Math.random()}`,
            question: '',
            type: 'multiple_choice',
            options: ['', ''],
            correct_answers: [],
            feedback_correct: '',
            feedback_incorrect: '',
            points: 5
        };
        setQuestions([...questions, newQuestion]);
    };

    const removeQuestion = (id: string) => {
        setQuestions(questions.filter(q => q.id !== id));
    };

    const updateQuestion = (id: string, field: keyof Question, value: any) => {
        setQuestions(questions.map(q => {
            if (q.id === id) {
                const updated = { ...q, [field]: value };

                // Reset options and correct answers when type changes
                if (field === 'type') {
                    if (value === 'multiple_choice') {
                        updated.options = ['', ''];
                        updated.correct_answers = [];
                    } else if (value === 'true_false') {
                        updated.options = [];
                        updated.correct_answers = [];
                    } else if (value === 'short_answer') {
                        updated.options = [];
                        updated.correct_answers = [''];
                    }
                }

                return updated;
            }
            return q;
        }));
    };

    const addOption = (questionId: string) => {
        setQuestions(questions.map(q => {
            if (q.id === questionId) {
                return { ...q, options: [...q.options, ''] };
            }
            return q;
        }));
    };

    const removeOption = (questionId: string, index: number) => {
        setQuestions(questions.map(q => {
            if (q.id === questionId) {
                const removedOption = q.options[index];
                const newOptions = q.options.filter((_, i) => i !== index);
                const newCorrectAnswers = q.correct_answers.filter(a => a !== removedOption);
                return { ...q, options: newOptions, correct_answers: newCorrectAnswers };
            }
            return q;
        }));
    };

    const updateOption = (questionId: string, index: number, value: string) => {
        setQuestions(questions.map(q => {
            if (q.id === questionId) {
                const oldValue = q.options[index];
                const newOptions = [...q.options];
                newOptions[index] = value;

                // Sync correct_answers: replace old option text with new one
                const newCorrectAnswers = q.correct_answers.map(a => a === oldValue ? value : a);

                return { ...q, options: newOptions, correct_answers: newCorrectAnswers };
            }
            return q;
        }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // Validation
        if (!title.trim()) {
            toast.error('Veuillez saisir un titre');
            return;
        }

        if (questions.length === 0) {
            toast.error('Veuillez ajouter au moins une question');
            return;
        }

        // Validate each question
        for (const q of questions) {
            if (!q.question.trim()) {
                toast.error('Toutes les questions doivent avoir un énoncé');
                return;
            }

            if (q.type === 'multiple_choice') {
                const validOptions = q.options.filter(opt => opt.trim() !== '');
                if (validOptions.length < 2) {
                    toast.error('Les questions à choix multiples doivent avoir au moins 2 options');
                    return;
                }
                if (q.correct_answers.length === 0) {
                    toast.error('Veuillez sélectionner au moins une bonne réponse pour chaque question');
                    return;
                }
            }

            if (q.type === 'true_false' && q.correct_answers.length === 0) {
                toast.error('Veuillez sélectionner la bonne réponse (Vrai/Faux) pour chaque question');
                return;
            }

            if (q.type === 'short_answer') {
                const validAnswers = q.correct_answers.filter(a => a && a.trim() !== '');
                if (validAnswers.length === 0) {
                    toast.error('Veuillez saisir au moins une réponse correcte pour les questions à réponse courte');
                    return;
                }
            }
        }

        setIsSubmitting(true);

        const formattedQuestions = questions.map(q => {
            const validOptions = q.type === 'multiple_choice' ? q.options.filter(opt => opt.trim() !== '') : null;
            // Sanitize correct_answers: only keep values matching valid options
            const sanitizedCorrectAnswers = q.type === 'multiple_choice' && validOptions
                ? q.correct_answers.filter(a => validOptions.includes(a))
                : q.correct_answers;

            return {
                question: q.question,
                type: q.type,
                options: validOptions,
                correct_answers: sanitizedCorrectAnswers,
                feedback_correct: q.feedback_correct || null,
                feedback_incorrect: q.feedback_incorrect || null,
                points: q.points
            };
        });

        router.post(route('trainings.quizzes.store', training.uuid), {
            title,
            description: description || null,
            duration_minutes: durationMinutes,
            passing_score: passingScore,
            available_from: availableFrom || null,
            available_until: availableUntil || null,
            is_active: isActive,
            max_attempts: maxAttempts,
            score_display: scoreDisplay,
            status: status,
            questions: formattedQuestions,
            assigned_classes: assignedClasses,
            assigned_materials: assignedMaterials
        }, {
            onSuccess: () => {
                toast.success('Quiz créé avec succès');
            },
            onError: (errors) => {
                setIsSubmitting(false);
                console.error(errors);
                toast.error('Erreur lors de la création du quiz');
            }
        });
    };

    const calculateTotalPoints = () => {
        return questions.reduce((sum, q) => sum + q.points, 0);
    };

    return (
        <DashboardLayout>
            <Head title={`Créer un quiz - ${training.name}`} />

            <div className="mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-8">
                    <Link
                        href={route('trainings.quizzes.index', training.uuid)}
                        className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Retour à la liste
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        Créer un nouveau quiz
                    </h1>
                    <p className="mt-2 text-gray-600 dark:text-gray-400">
                        Formation: {training.name}
                    </p>
                </div>

                <form ref={formRef} onSubmit={handleSubmit} className="space-y-8">
                    {/* Quiz Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Informations du quiz</CardTitle>
                            <CardDescription>
                                Définissez les paramètres généraux du quiz
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Title */}
                            <div className="space-y-2">
                                <Label htmlFor="title">Titre *</Label>
                                <Input
                                    id="title"
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                    placeholder="Ex: Quiz PHP Basics"
                                    required
                                />
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    placeholder="Description du quiz (optionnel)"
                                    rows={3}
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Duration */}
                                <div className="space-y-2">
                                    <Label htmlFor="duration">Durée (minutes) *</Label>
                                    <Input
                                        id="duration"
                                        type="number"
                                        min="1"
                                        max="240"
                                        value={durationMinutes}
                                        onChange={(e) => {
                                            const value = parseInt(e.target.value);
                                            setDurationMinutes(isNaN(value) ? 1 : value);
                                        }}
                                        required
                                    />
                                </div>

                                {/* Passing Score */}
                                <div className="space-y-2">
                                    <Label htmlFor="passing_score">
                                        Score minimum (%) *
                                    </Label>
                                    <Input
                                        id="passing_score"
                                        type="number"
                                        min="0"
                                        max="100"
                                        value={passingScore}
                                        onChange={(e) => {
                                            const value = parseInt(e.target.value);
                                            setPassingScore(isNaN(value) ? 0 : value);
                                        }}
                                        required
                                    />
                                    <p className="text-xs text-gray-500">
                                        Total de points: {calculateTotalPoints()}
                                    </p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Available From */}
                                <div className="space-y-2">
                                    <Label htmlFor="available_from">Disponible à partir de</Label>
                                    <Input
                                        id="available_from"
                                        type="date"
                                        value={availableFrom}
                                        onChange={(e) => setAvailableFrom(e.target.value)}
                                    />
                                </div>

                                {/* Available Until */}
                                <div className="space-y-2">
                                    <Label htmlFor="available_until">Disponible jusqu'au</Label>
                                    <Input
                                        id="available_until"
                                        type="date"
                                        value={availableUntil}
                                        onChange={(e) => setAvailableUntil(e.target.value)}
                                    />
                                </div>
                            </div>

                            {/* Is Active */}
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Quiz actif</Label>
                                    <p className="text-sm text-gray-500">
                                        Les étudiants peuvent passer le quiz s'il est actif
                                    </p>
                                </div>
                                <Switch
                                    checked={isActive}
                                    onCheckedChange={setIsActive}
                                />
                            </div>

                            <Separator />

                            {/* Multiple Attempts Settings */}
                            <div className="space-y-6">
                                <div className="space-y-2">
                                    <h3 className="text-lg font-medium">Paramètres des tentatives</h3>
                                    <p className="text-sm text-gray-500">
                                        Configurez le nombre d'essais autorisés et l'affichage du score
                                    </p>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Max Attempts */}
                                    <div className="space-y-2">
                                        <Label htmlFor="max_attempts">
                                            Nombre maximum de tentatives
                                        </Label>
                                        <Input
                                            id="max_attempts"
                                            type="number"
                                            min="1"
                                            max="10"
                                            value={maxAttempts}
                                            onChange={(e) => {
                                                const value = parseInt(e.target.value);
                                                setMaxAttempts(isNaN(value) ? 1 : value);
                                            }}
                                        />
                                        <p className="text-xs text-gray-500">
                                            Les étudiants pourront passer le quiz jusqu'à {maxAttempts} fois
                                        </p>
                                    </div>

                                    {/* Score Display */}
                                    <div className="space-y-2">
                                        <Label htmlFor="score_display">
                                            Affichage du score
                                        </Label>
                                        <Select
                                            value={scoreDisplay}
                                            onValueChange={(value) => setScoreDisplay(value as 'best' | 'last' | 'average')}
                                        >
                                            <SelectTrigger id="score_display">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="best">Meilleur score</SelectItem>
                                                <SelectItem value="last">Dernier score</SelectItem>
                                                <SelectItem value="average">Score moyen</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-gray-500">
                                            {scoreDisplay === 'best' && 'Le meilleur score parmi toutes les tentatives'}
                                            {scoreDisplay === 'last' && 'Le score de la dernière tentative'}
                                            {scoreDisplay === 'average' && 'La moyenne de tous les scores'}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            {/* Status Settings */}
                            <div className="space-y-6">
                                <div className="space-y-2">
                                    <h3 className="text-lg font-medium">Statut du quiz</h3>
                                    <p className="text-sm text-gray-500">
                                        Choisissez si le quiz est un brouillon ou publié
                                    </p>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="status">Statut</Label>
                                    <Select
                                        value={status}
                                        onValueChange={(value) => setStatus(value as 'draft' | 'published' | 'archived')}
                                    >
                                        <SelectTrigger id="status">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft">Brouillon</SelectItem>
                                            <SelectItem value="published">Publié</SelectItem>
                                            <SelectItem value="archived">Archivé</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p className="text-xs text-gray-500">
                                        {status === 'draft' && 'Le quiz est en cours de préparation et non visible par les étudiants'}
                                        {status === 'published' && 'Le quiz est visible et accessible aux étudiants'}
                                        {status === 'archived' && 'Le quiz est archivé et non accessible'}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Class and Material Assignments */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Assignations</CardTitle>
                            <CardDescription>
                                Choisissez les classes et supports de cours pour ce quiz
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Training Classes */}
                            <div>
                                <Label className="text-base font-medium">Classes de formation</Label>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    Sélectionnez les classes qui auront accès à ce quiz
                                </p>
                                <div className="grid gap-3">
                                    {trainingClasses.map((trainingClass) => (
                                        <div key={trainingClass.id} className="flex items-center space-x-3 p-3 border rounded-lg">
                                            <input
                                                type="checkbox"
                                                id={`class-${trainingClass.id}`}
                                                checked={assignedClasses.includes(trainingClass.id)}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        setAssignedClasses([...assignedClasses, trainingClass.id]);
                                                    } else {
                                                        setAssignedClasses(assignedClasses.filter(id => id !== trainingClass.id));
                                                        // Also remove any materials from this class
                                                        const materialsToRemove = trainingClass.materials.map(m => m.id);
                                                        setAssignedMaterials(assignedMaterials.filter(id => !materialsToRemove.includes(id)));
                                                    }
                                                }}
                                                className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            />
                                            <label htmlFor={`class-${trainingClass.id}`} className="flex-1 cursor-pointer">
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="font-medium text-gray-900 dark:text-white">
                                                            {trainingClass.name}
                                                        </p>
                                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                                            {new Date(trainingClass.date).toLocaleDateString('fr-FR')} •
                                                            {trainingClass.start_time} - {trainingClass.end_time}
                                                            {trainingClass.room && ` • ${trainingClass.room}`}
                                                        </p>
                                                    </div>
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">
                                                        {trainingClass.students_count} étudiants
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    ))}
                                </div>
                                {trainingClasses.length === 0 && (
                                    <p className="text-sm text-gray-500 dark:text-gray-400 italic">
                                        Aucune classe disponible pour cette formation
                                    </p>
                                )}
                            </div>

                            {/* Training Class Materials */}
                            {assignedClasses.length > 0 && (
                                <div>
                                    <Label className="text-base font-medium">Supports de cours (optionnel)</Label>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        Associez le quiz à des supports de cours spécifiques
                                    </p>
                                    <div className="space-y-4">
                                        {trainingClasses
                                            .filter(tc => assignedClasses.includes(tc.id))
                                            .map((trainingClass) => (
                                                <div key={trainingClass.id} className="border rounded-lg p-4">
                                                    <h4 className="font-medium text-gray-900 dark:text-white mb-3">
                                                        {trainingClass.name}
                                                    </h4>
                                                    {trainingClass.materials.length > 0 ? (
                                                        <div className="grid gap-2">
                                                            {trainingClass.materials.map((material) => (
                                                                <div key={material.id} className="flex items-center space-x-3">
                                                                    <input
                                                                        type="checkbox"
                                                                        id={`material-${material.id}`}
                                                                        checked={assignedMaterials.includes(material.id)}
                                                                        onChange={(e) => {
                                                                            if (e.target.checked) {
                                                                                setAssignedMaterials([...assignedMaterials, material.id]);
                                                                            } else {
                                                                                setAssignedMaterials(assignedMaterials.filter(id => id !== material.id));
                                                                            }
                                                                        }}
                                                                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                                    />
                                                                    <label htmlFor={`material-${material.id}`} className="flex-1 cursor-pointer">
                                                                        <div className="flex items-center justify-between">
                                                                            <div>
                                                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                                                    {material.title}
                                                                                </p>
                                                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                                                    {material.type}
                                                                                </p>
                                                                            </div>
                                                                            <div className="text-xs text-gray-400">
                                                                                Ordre: {material.order}
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <p className="text-sm text-gray-500 dark:text-gray-400 italic">
                                                            Aucun support de cours disponible
                                                        </p>
                                                    )}
                                                </div>
                                            ))}
                                    </div>
                                </div>
                            )}

                            {/* Assignment Summary */}
                            {(assignedClasses.length > 0 || assignedMaterials.length > 0) && (
                                <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                    <h4 className="font-medium text-blue-900 dark:text-blue-200 mb-2">
                                        Résumé des assignations
                                    </h4>
                                    <div className="text-sm text-blue-800 dark:text-blue-300">
                                        <p>• {assignedClasses.length} classe(s) sélectionnée(s)</p>
                                        {assignedMaterials.length > 0 && (
                                            <p>• {assignedMaterials.length} support(s) de cours sélectionné(s)</p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Questions */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Questions</CardTitle>
                                    <CardDescription>
                                        Ajoutez et configurez les questions du quiz
                                    </CardDescription>
                                </div>
                                <Button type="button" onClick={addQuestion} variant="outline">
                                    <Plus className="h-4 w-4 mr-2" />
                                    Ajouter une question
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {questions.length === 0 ? (
                                <div className="text-center py-12 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                                    <p className="text-gray-500 dark:text-gray-400 mb-4">
                                        Aucune question ajoutée
                                    </p>
                                    <Button type="button" onClick={addQuestion} variant="outline">
                                        <Plus className="h-4 w-4 mr-2" />
                                        Ajouter votre première question
                                    </Button>
                                </div>
                            ) : (
                                questions.map((question, index) => (
                                    <Card key={question.id} className="border-2">
                                        <CardHeader className="pb-4">
                                            <div className="flex items-start justify-between">
                                                <div className="flex items-center gap-3">
                                                    <GripVertical className="h-5 w-5 text-gray-400" />
                                                    <CardTitle className="text-lg">
                                                        Question {index + 1}
                                                    </CardTitle>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => removeQuestion(question.id)}
                                                    className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            {/* Question Text */}
                                            <div className="space-y-2">
                                                <Label>Énoncé de la question *</Label>
                                                <Textarea
                                                    value={question.question}
                                                    onChange={(e) => updateQuestion(question.id, 'question', e.target.value)}
                                                    placeholder="Saisissez votre question..."
                                                    rows={2}
                                                />
                                            </div>

                                            <div className="grid grid-cols-2 gap-4">
                                                {/* Question Type */}
                                                <div className="space-y-2">
                                                    <Label>Type de question</Label>
                                                    <Select
                                                        value={question.type}
                                                        onValueChange={(value) => updateQuestion(question.id, 'type', value)}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="multiple_choice">Choix multiples</SelectItem>
                                                            <SelectItem value="true_false">Vrai/Faux</SelectItem>
                                                            <SelectItem value="short_answer">Réponse courte</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>

                                                {/* Points */}
                                                <div className="space-y-2">
                                                    <Label>Points</Label>
                                                    <Input
                                                        type="number"
                                                        min="1"
                                                        value={question.points}
                                                        onChange={(e) => {
                                                            const value = parseInt(e.target.value);
                                                            updateQuestion(question.id, 'points', isNaN(value) ? 1 : value);
                                                        }}
                                                    />
                                                </div>
                                            </div>

                                            {/* Question Type Specific Fields */}
                                            {question.type === 'multiple_choice' && (
                                                <div className="space-y-3">
                                                    <div className="flex items-center justify-between">
                                                        <Label>Options de réponse</Label>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => addOption(question.id)}
                                                        >
                                                            <Plus className="h-3 w-3 mr-1" />
                                                            Ajouter une option
                                                        </Button>
                                                    </div>

                                                    {question.options.map((option, optIndex) => (
                                                        <div key={optIndex} className="flex items-center gap-2">
                                                            <Input
                                                                value={option}
                                                                onChange={(e) => updateOption(question.id, optIndex, e.target.value)}
                                                                placeholder={`Option ${optIndex + 1}`}
                                                                className="flex-1"
                                                            />
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => {
                                                                    const isCorrect = question.correct_answers.includes(option);
                                                                    if (isCorrect) {
                                                                        updateQuestion(
                                                                            question.id,
                                                                            'correct_answers',
                                                                            question.correct_answers.filter(a => a !== option)
                                                                        );
                                                                    } else {
                                                                        updateQuestion(
                                                                            question.id,
                                                                            'correct_answers',
                                                                            [...question.correct_answers, option]
                                                                        );
                                                                    }
                                                                }}
                                                                className={
                                                                    question.correct_answers.includes(option)
                                                                        ? 'bg-green-50 border-green-500 text-green-700 hover:bg-green-100'
                                                                        : ''
                                                                }
                                                            >
                                                                {question.correct_answers.includes(option) ? '✓ Correcte' : 'Correcte?'}
                                                            </Button>
                                                            {question.options.length > 2 && (
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => removeOption(question.id, optIndex)}
                                                                    className="text-red-600"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            )}

                                            {question.type === 'true_false' && (
                                                <div className="space-y-2">
                                                    <Label>Bonne réponse</Label>
                                                    <Select
                                                        value={question.correct_answers[0]?.toString() || ''}
                                                        onValueChange={(value) => updateQuestion(question.id, 'correct_answers', [value === 'true'])}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Sélectionnez la bonne réponse" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="true">Vrai</SelectItem>
                                                            <SelectItem value="false">Faux</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            )}

                                            {question.type === 'short_answer' && (
                                                <div className="space-y-2">
                                                    <Label>Réponse(s) correcte(s)</Label>
                                                    <p className="text-xs text-gray-500">
                                                        Saisissez les réponses acceptées (une par ligne)
                                                    </p>
                                                    <Textarea
                                                        value={question.correct_answers.join('\n')}
                                                        onChange={(e) => updateQuestion(
                                                            question.id,
                                                            'correct_answers',
                                                            e.target.value.split('\n').filter(a => a.trim() !== '')
                                                        )}
                                                        placeholder="Réponse 1&#10;Réponse 2&#10;..."
                                                        rows={3}
                                                    />
                                                </div>
                                            )}

                                            <Separator className="my-4" />

                                            {/* Feedback Fields */}
                                            <div className="space-y-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor={`feedback_correct_${question.id}`}>
                                                        Feedback pour réponse correcte (optionnel)
                                                    </Label>
                                                    <p className="text-xs text-gray-500">
                                                        Message affiché quand l'étudiant répond correctement
                                                    </p>
                                                    <Textarea
                                                        id={`feedback_correct_${question.id}`}
                                                        value={question.feedback_correct || ''}
                                                        onChange={(e) => updateQuestion(question.id, 'feedback_correct', e.target.value)}
                                                        placeholder="Ex: Excellent ! Vous avez bien compris le concept..."
                                                        rows={2}
                                                    />
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor={`feedback_incorrect_${question.id}`}>
                                                        Feedback pour réponse incorrecte (optionnel)
                                                    </Label>
                                                    <p className="text-xs text-gray-500">
                                                        Message affiché quand l'étudiant répond incorrectement
                                                    </p>
                                                    <Textarea
                                                        id={`feedback_incorrect_${question.id}`}
                                                        value={question.feedback_incorrect || ''}
                                                        onChange={(e) => updateQuestion(question.id, 'feedback_incorrect', e.target.value)}
                                                        placeholder="Ex: Pas tout à fait. Revoyez la section sur..."
                                                        rows={2}
                                                    />
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Separator />

                    {/* Submit Buttons */}
                    <div className="flex items-center justify-between">
                        <Link href={route('trainings.quizzes.index', training.uuid)}>
                            <Button type="button" variant="outline">
                                Annuler
                            </Button>
                        </Link>
                        <div className="flex gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={isSubmitting || questions.length === 0}
                                onClick={() => {
                                    setStatus('draft');
                                    setTimeout(() => {
                                        formRef.current?.requestSubmit();
                                    }, 50);
                                }}
                            >
                                {isSubmitting && status === 'draft' ? (
                                    <>Sauvegarde...</>
                                ) : (
                                    <>
                                        <Save className="h-4 w-4 mr-2" />
                                        Sauvegarder comme brouillon
                                    </>
                                )}
                            </Button>
                            <Button
                                type="button"
                                disabled={isSubmitting || questions.length === 0}
                                className="bg-green-600 hover:bg-green-700"
                                onClick={() => {
                                    setStatus('published');
                                    setTimeout(() => {
                                        formRef.current?.requestSubmit();
                                    }, 50);
                                }}
                            >
                                {isSubmitting && status === 'published' ? (
                                    <>Publication...</>
                                ) : (
                                    <>
                                        <Save className="h-4 w-4 mr-2" />
                                        Publier le quiz
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
