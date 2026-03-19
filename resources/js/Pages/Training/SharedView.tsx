import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { Button } from '@/Components/ui/button';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    AcademicCapIcon,
    ClockIcon,
    CurrencyEuroIcon,
    UserIcon,
    CheckCircleIcon,
    TagIcon,
} from '@heroicons/react/24/outline';

interface TrainingTopic {
    id: number;
    name: string;
    description: string | null;
}

interface TrainingClass {
    id: number;
    uuid: string;
    name: string;
    date: string;
    start_time: string;
    end_time: string;
    room: string | null;
}

interface Training {
    uuid: string;
    title: string;
    description: string;
    duration: string;
    level: string;
    price: string;
    category: string | null;
    image_url: string | null;
    topics: TrainingTopic[];
    classes: TrainingClass[];
    teacher: { name: string } | null;
}

interface Props {
    training: Training;
    token: string;
}

const levelLabels: Record<string, string> = {
    beginner: 'D\u00e9butant',
    intermediate: 'Interm\u00e9diaire',
    advanced: 'Avanc\u00e9',
};

export default function SharedView({ training, token }: Props) {
    const { auth } = usePage<PageProps>().props;
    const [selectedClassId, setSelectedClassId] = useState('');
    const [motivation, setMotivation] = useState('');
    const [paymentMethod, setPaymentMethod] = useState('card');
    const [hasReadTerms, setHasReadTerms] = useState(false);
    const [hasReadPrivacyPolicy, setHasReadPrivacyPolicy] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [enrolled, setEnrolled] = useState(false);

    const handleEnroll = () => {
        if (!auth?.user) {
            window.location.href = route('login');
            return;
        }

        if (!selectedClassId) {
            toast.error('Veuillez choisir une session');
            return;
        }
        if (!motivation || motivation.length < 50) {
            toast.error('La motivation doit contenir au moins 50 caract\u00e8res');
            return;
        }
        if (!hasReadTerms || !hasReadPrivacyPolicy) {
            toast.error('Veuillez accepter les conditions');
            return;
        }

        setProcessing(true);
        router.post(route('trainings.shared.enroll', token), {
            selectedClassId,
            motivation,
            paymentMethod,
            hasReadTerms,
            hasReadPrivacyPolicy,
        }, {
            onSuccess: () => {
                setEnrolled(true);
                toast.success('Inscription enregistr\u00e9e avec succ\u00e8s !');
                setProcessing(false);
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                toast.error(typeof firstError === 'string' ? firstError : 'Erreur lors de l\'inscription');
                setProcessing(false);
            },
        });
    };

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('fr-FR', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    if (enrolled) {
        return (
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
                <Head title="Inscription enregistr\u00e9e" />
                <div className="max-w-md w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
                    <div className="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-6">
                        <CheckCircleIcon className="h-8 w-8 text-green-600 dark:text-green-400" />
                    </div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Inscription enregistr&eacute;e
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400 mb-8">
                        Votre demande d'inscription &agrave; <strong>{training.title}</strong> a &eacute;t&eacute; enregistr&eacute;e.
                        Vous recevrez une confirmation par email.
                    </p>
                    <Link
                        href="/"
                        className="inline-flex items-center justify-center px-6 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        Retour &agrave; l'accueil
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            <Head title={`Inscription - ${training.title}`} />

            <div className="max-w-4xl mx-auto px-4 py-8 sm:py-12">
                {/* Training Header */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
                    {training.image_url && (
                        <div className="h-48 sm:h-64 overflow-hidden">
                            <img
                                src={training.image_url}
                                alt={training.title}
                                className="w-full h-full object-cover"
                            />
                        </div>
                    )}
                    <div className="p-6">
                        <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-3">
                            {training.title}
                        </h1>

                        <div className="flex flex-wrap gap-3 mb-4">
                            <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                <AcademicCapIcon className="h-4 w-4" />
                                {levelLabels[training.level] || training.level}
                            </span>
                            <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                <ClockIcon className="h-4 w-4" />
                                {training.duration}
                            </span>
                            <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                <CurrencyEuroIcon className="h-4 w-4" />
                                {parseFloat(training.price) === 0 ? 'Gratuit' : `${training.price} \u20ac`}
                            </span>
                            {training.category && (
                                <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                    <TagIcon className="h-4 w-4" />
                                    {training.category}
                                </span>
                            )}
                            {training.teacher && (
                                <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                    <UserIcon className="h-4 w-4" />
                                    {training.teacher.name}
                                </span>
                            )}
                        </div>

                        <p className="text-gray-600 dark:text-gray-400 whitespace-pre-line">
                            {training.description}
                        </p>

                        {training.topics.length > 0 && (
                            <div className="mt-4">
                                <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Sujets abord&eacute;s :
                                </h3>
                                <ul className="space-y-1">
                                    {training.topics.map((topic) => (
                                        <li key={topic.id} className="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <CheckCircleIcon className="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                                            <span>{topic.name}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                </div>

                {/* Enrollment Form */}
                {!auth?.user ? (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                        <h2 className="text-xl font-bold text-gray-900 dark:text-white mb-3">
                            Inscription &agrave; cette formation
                        </h2>
                        <p className="text-gray-600 dark:text-gray-400 mb-6">
                            Connectez-vous pour vous inscrire &agrave; cette formation.
                        </p>
                        <Link
                            href={route('login')}
                            className="inline-flex items-center justify-center px-6 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 transition-colors"
                        >
                            Se connecter
                        </Link>
                    </div>
                ) : (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 className="text-xl font-bold text-gray-900 dark:text-white mb-6">
                            Formulaire d'inscription
                        </h2>

                        <div className="space-y-6">
                            {/* Class Selection */}
                            {training.classes.length > 0 && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Choisir une session *
                                    </label>
                                    <div className="space-y-2">
                                        {training.classes.map((classItem) => (
                                            <label
                                                key={classItem.id}
                                                className={`flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition-all ${
                                                    selectedClassId === classItem.id.toString()
                                                        ? 'border-primary bg-blue-50 dark:bg-blue-900/20'
                                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="class"
                                                    value={classItem.id}
                                                    checked={selectedClassId === classItem.id.toString()}
                                                    onChange={() => setSelectedClassId(classItem.id.toString())}
                                                    className="w-4 h-4 text-primary"
                                                />
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                        {classItem.name}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        {formatDate(classItem.date)} &bull; {classItem.start_time} - {classItem.end_time}
                                                        {classItem.room && ` \u2022 ${classItem.room}`}
                                                    </p>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Motivation */}
                            <div>
                                <label htmlFor="motivation" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Motivation * <span className="font-normal text-gray-500">(min. 50 caract&egrave;res)</span>
                                </label>
                                <textarea
                                    id="motivation"
                                    value={motivation}
                                    onChange={(e) => setMotivation(e.target.value)}
                                    placeholder="D&eacute;crivez pourquoi vous souhaitez suivre cette formation..."
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm min-h-[120px]"
                                />
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">
                                    {motivation.length}/500 caract&egrave;res
                                </p>
                            </div>

                            {/* Payment Method */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Mode de paiement *
                                </label>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    {[
                                        { value: 'card', label: 'Carte' },
                                        { value: 'monthly', label: 'Mensuel' },
                                        { value: 'quarterly', label: 'Trimestriel' },
                                        { value: 'full', label: 'Int\u00e9gral' },
                                    ].map((option) => (
                                        <label
                                            key={option.value}
                                            className={`flex items-center justify-center p-2 border-2 rounded-lg cursor-pointer text-sm transition-all ${
                                                paymentMethod === option.value
                                                    ? 'border-primary bg-blue-50 dark:bg-blue-900/20 text-primary'
                                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 text-gray-700 dark:text-gray-300'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="payment"
                                                value={option.value}
                                                checked={paymentMethod === option.value}
                                                onChange={() => setPaymentMethod(option.value)}
                                                className="sr-only"
                                            />
                                            {option.label}
                                        </label>
                                    ))}
                                </div>
                            </div>

                            {/* Terms */}
                            <div className="space-y-3">
                                <label className="flex items-start gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={hasReadTerms}
                                        onChange={(e) => setHasReadTerms(e.target.checked)}
                                        className="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500 mt-0.5"
                                    />
                                    <span className="text-sm text-gray-700 dark:text-gray-300">
                                        J'accepte les conditions g&eacute;n&eacute;rales de vente *
                                    </span>
                                </label>
                                <label className="flex items-start gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={hasReadPrivacyPolicy}
                                        onChange={(e) => setHasReadPrivacyPolicy(e.target.checked)}
                                        className="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500 mt-0.5"
                                    />
                                    <span className="text-sm text-gray-700 dark:text-gray-300">
                                        J'accepte la politique de confidentialit&eacute; *
                                    </span>
                                </label>
                            </div>

                            {/* Submit */}
                            <Button
                                onClick={handleEnroll}
                                disabled={processing}
                                className="w-full"
                                size="lg"
                            >
                                {processing ? 'Inscription en cours...' : 'S\'inscrire \u00e0 cette formation'}
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
