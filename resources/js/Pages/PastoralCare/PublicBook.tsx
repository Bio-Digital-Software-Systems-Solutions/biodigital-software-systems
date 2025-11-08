import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
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
import { Badge } from '@/Components/ui/badge';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Separator } from '@/Components/ui/separator';
import {
    CalendarDays,
    Clock,
    User,
    Mail,
    Phone,
    MessageSquare,
    MapPin,
    Video,
    Users,
    Heart,
    CheckCircle,
    Loader2,
    AlertCircle,
} from 'lucide-react';
import { toast } from 'sonner';
import { format, addDays, isBefore, startOfDay } from 'date-fns';
import { fr } from 'date-fns/locale';

interface Pastor {
    id: number;
    name: string;
    email: string;
    phone?: string;
}

interface TimeSlot {
    time: string;
    available: boolean;
}

interface BookingFormData {
    pastor_id: string;
    client_name: string;
    client_email: string;
    client_phone: string;
    appointment_date: string;
    appointment_time: string;
    duration_minutes: number;
    location_type: 'in_person' | 'zoom' | 'hybrid';
    zoom_link: string;
    notes: string;
}

interface Props {
    auth: {
        user: {
            id: number;
            first_name: string;
            last_name: string;
            email: string;
        };
    };
}

export default function PublicBook({ auth }: Props) {
    const [pastors, setPastors] = useState<Pastor[]>([]);
    const [availableSlots, setAvailableSlots] = useState<string[]>([]);
    const [isLoadingPastors, setIsLoadingPastors] = useState(true);
    const [isLoadingSlots, setIsLoadingSlots] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [currentStep, setCurrentStep] = useState(1);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const [formData, setFormData] = useState<BookingFormData>({
        pastor_id: '',
        client_name: `${auth.user.first_name} ${auth.user.last_name}`,
        client_email: auth.user.email,
        client_phone: '',
        appointment_date: '',
        appointment_time: '',
        duration_minutes: 60,
        location_type: 'in_person',
        zoom_link: '',
        notes: '',
    });

    // Load pastors on component mount
    useEffect(() => {
        fetchPastors();
    }, []);

    // Load available slots when pastor, date, or duration changes
    useEffect(() => {
        if (formData.pastor_id && formData.appointment_date && formData.duration_minutes) {
            fetchAvailableSlots();
        }
    }, [formData.pastor_id, formData.appointment_date, formData.duration_minutes]);

    const fetchPastors = async () => {
        try {
            const response = await fetch('/api/pastoral-care/pastors');
            const data = await response.json();
            if (data.success) {
                setPastors(data.data);
            } else {
                toast.error('Erreur lors du chargement des pasteurs');
            }
        } catch (error) {
            toast.error('Erreur de connexion');
        } finally {
            setIsLoadingPastors(false);
        }
    };

    const fetchAvailableSlots = async () => {
        setIsLoadingSlots(true);
        try {
            const params = new URLSearchParams({
                pastor_id: formData.pastor_id,
                date: formData.appointment_date,
                duration: formData.duration_minutes.toString(),
            });

            const response = await fetch(`/api/pastoral-care/available-slots?${params}`);
            const data = await response.json();

            if (data.success) {
                setAvailableSlots(data.data.slots);
            } else {
                toast.error('Erreur lors du chargement des créneaux');
                setAvailableSlots([]);
            }
        } catch (error) {
            toast.error('Erreur de connexion');
            setAvailableSlots([]);
        } finally {
            setIsLoadingSlots(false);
        }
    };

    const handleInputChange = (field: keyof BookingFormData, value: string | number) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: '' }));
        }
    };

    const validateStep = (step: number): boolean => {
        const stepErrors: Record<string, string> = {};

        if (step === 1) {
            if (!formData.pastor_id) stepErrors.pastor_id = 'Veuillez sélectionner un pasteur';
            if (!formData.appointment_date) stepErrors.appointment_date = 'Veuillez sélectionner une date';
            if (!formData.appointment_time) stepErrors.appointment_time = 'Veuillez sélectionner un créneau';
        }

        if (step === 2) {
            if (!formData.client_name.trim()) stepErrors.client_name = 'Votre nom est requis';
            if (!formData.client_email.trim()) stepErrors.client_email = 'Votre email est requis';
            if (formData.client_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.client_email)) {
                stepErrors.client_email = 'Veuillez entrer un email valide';
            }
            if (formData.location_type === 'zoom' && !formData.zoom_link.trim()) {
                stepErrors.zoom_link = 'Le lien Zoom est requis pour la visioconférence';
            }
        }

        setErrors(stepErrors);
        return Object.keys(stepErrors).length === 0;
    };

    const handleNextStep = () => {
        if (validateStep(currentStep)) {
            setCurrentStep(prev => prev + 1);
        }
    };

    const handlePrevStep = () => {
        setCurrentStep(prev => prev - 1);
    };

    const handleSubmit = async () => {
        const isValid = validateStep(2);

        if (!isValid) {
            return;
        }

        setIsSubmitting(true);

        router.post('/pastoral-care/appointments', formData as any, {
            onSuccess: () => {
                // The controller will handle the redirect and flash message
                // No need to do anything here as the success message is handled by the flash
            },
            onError: (errors) => {
                setErrors(errors);
                const errorMessage = errors.appointment_time?.[0] ||
                                   errors.pastor_id?.[0] ||
                                   errors.client_name?.[0] ||
                                   errors.client_email?.[0] ||
                                   'Erreur lors de la création du rendez-vous';
                toast.error(errorMessage);
                setIsSubmitting(false);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const getNextAvailableDates = () => {
        const dates = [];
        const today = startOfDay(new Date());

        for (let i = 1; i <= 14; i++) { // Next 2 weeks
            const date = addDays(today, i);
            // Skip Sundays (day 0) and Saturdays (day 6)
            if (date.getDay() !== 0 && date.getDay() !== 6) {
                dates.push(date);
            }
        }
        return dates;
    };

    return (
        <DashboardLayout
            title="Prendre rendez-vous pastoral"
        >
            <Head title="Prendre rendez-vous - Soin Pastoral" />

            <div className="py-6">
                <div className="mx-auto sm:px-6 lg:px-8">{/* Description */}
                    <div className="text-center mb-8">
                        <p className="text-lg text-gray-600 dark:text-gray-300">
                            Prenez rendez-vous avec un membre de notre équipe pastorale pour un accompagnement spirituel personnalisé
                        </p>
                    </div>

                    {/* Progress Steps */}
                    <div className="flex items-center justify-center mb-8">
                        <div className="flex items-center space-x-4">
                            <div className={`flex items-center justify-center w-10 h-10 rounded-full border-2 ${
                                currentStep >= 1 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-300'
                            }`}>
                                {currentStep > 1 ? <CheckCircle className="h-6 w-6" /> : '1'}
                            </div>
                            <div className={`h-1 w-16 ${currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-300'}`} />
                            <div className={`flex items-center justify-center w-10 h-10 rounded-full border-2 ${
                                currentStep >= 2 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-300'
                            }`}>
                                {currentStep > 2 ? <CheckCircle className="h-6 w-6" /> : '2'}
                            </div>
                            <div className={`h-1 w-16 ${currentStep >= 3 ? 'bg-blue-600' : 'bg-gray-300'}`} />
                            <div className={`flex items-center justify-center w-10 h-10 rounded-full border-2 ${
                                currentStep >= 3 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-300'
                            }`}>
                                {currentStep > 3 ? <CheckCircle className="h-6 w-6" /> : '3'}
                            </div>
                        </div>
                    </div>

                    <Card className="shadow-xl">
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                {currentStep === 1 && (
                                    <>
                                        <CalendarDays className="h-6 w-6 mr-2" />
                                        Choisissez votre créneau
                                    </>
                                )}
                                {currentStep === 2 && (
                                    <>
                                        <User className="h-6 w-6 mr-2" />
                                        Vos informations
                                    </>
                                )}
                                {currentStep === 3 && (
                                    <>
                                        <CheckCircle className="h-6 w-6 mr-2" />
                                        Confirmation
                                    </>
                                )}
                            </CardTitle>
                            <CardDescription>
                                {currentStep === 1 && "Sélectionnez un pasteur, une date et un horaire qui vous conviennent"}
                                {currentStep === 2 && "Complétez vos informations de contact et préférences"}
                                {currentStep === 3 && "Vérifiez les détails de votre rendez-vous avant de confirmer"}
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            {/* Step 1: Pastor and Time Selection */}
                            {currentStep === 1 && (
                                <div className="space-y-6">
                                    {/* Pastor Selection */}
                                    <div>
                                        <Label htmlFor="pastor" className="text-base font-medium">
                                            Sélectionnez un pasteur *
                                        </Label>
                                        {isLoadingPastors ? (
                                            <div className="flex items-center justify-center py-8">
                                                <Loader2 className="h-6 w-6 animate-spin" />
                                                <span className="ml-2">Chargement des pasteurs...</span>
                                            </div>
                                        ) : (
                                            <div className="grid gap-4 mt-2">
                                                {pastors.map((pastor) => (
                                                    <div
                                                        key={pastor.id}
                                                        className={`p-4 border-2 rounded-lg cursor-pointer transition-all ${
                                                            formData.pastor_id === pastor.id.toString()
                                                                ? 'border-blue-600 bg-blue-50 dark:bg-blue-950'
                                                                : 'border-gray-200 hover:border-gray-300'
                                                        }`}
                                                        onClick={() => handleInputChange('pastor_id', pastor.id.toString())}
                                                    >
                                                        <div className="flex items-center space-x-3">
                                                            <div className="flex-shrink-0">
                                                                <div className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                                                    <User className="h-6 w-6 text-gray-600" />
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <h3 className="font-medium text-gray-900 dark:text-white">
                                                                    {pastor.name}
                                                                </h3>
                                                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                                                    {pastor.email}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                        {errors.pastor_id && (
                                            <p className="text-red-600 text-sm mt-2 flex items-center">
                                                <AlertCircle className="h-4 w-4 mr-1" />
                                                {errors.pastor_id}
                                            </p>
                                        )}
                                    </div>

                                    <Separator />

                                    {/* Duration Selection */}
                                    <div>
                                        <Label className="text-base font-medium">Durée du rendez-vous</Label>
                                        <Select
                                            value={formData.duration_minutes.toString()}
                                            onValueChange={(value) => handleInputChange('duration_minutes', parseInt(value))}
                                        >
                                            <SelectTrigger className="mt-2">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="30">30 minutes</SelectItem>
                                                <SelectItem value="60">1 heure</SelectItem>
                                                <SelectItem value="90">1 heure 30</SelectItem>
                                                <SelectItem value="120">2 heures</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Date Selection */}
                                    <div>
                                        <Label className="text-base font-medium">Choisissez une date *</Label>
                                        <div className="grid grid-cols-2 md:grid-cols-3 gap-3 mt-2">
                                            {getNextAvailableDates().map((date) => (
                                                <Button
                                                    key={date.toISOString()}
                                                    variant={formData.appointment_date === format(date, 'yyyy-MM-dd') ? 'default' : 'outline'}
                                                    className="h-auto p-3 flex flex-col items-center"
                                                    onClick={() => handleInputChange('appointment_date', format(date, 'yyyy-MM-dd'))}
                                                >
                                                    <span className="text-sm font-medium">
                                                        {format(date, 'EEE', { locale: fr })}
                                                    </span>
                                                    <span className="text-lg font-bold">
                                                        {format(date, 'd', { locale: fr })}
                                                    </span>
                                                    <span className="text-xs">
                                                        {format(date, 'MMM', { locale: fr })}
                                                    </span>
                                                </Button>
                                            ))}
                                        </div>
                                        {errors.appointment_date && (
                                            <p className="text-red-600 text-sm mt-2 flex items-center">
                                                <AlertCircle className="h-4 w-4 mr-1" />
                                                {errors.appointment_date}
                                            </p>
                                        )}
                                    </div>

                                    {/* Time Slot Selection */}
                                    {formData.pastor_id && formData.appointment_date && (
                                        <div>
                                            <Label className="text-base font-medium">Choisissez un horaire *</Label>
                                            {isLoadingSlots ? (
                                                <div className="flex items-center justify-center py-8">
                                                    <Loader2 className="h-6 w-6 animate-spin" />
                                                    <span className="ml-2">Chargement des créneaux...</span>
                                                </div>
                                            ) : availableSlots.length > 0 ? (
                                                <div className="grid grid-cols-3 md:grid-cols-4 gap-3 mt-2">
                                                    {availableSlots.map((time) => (
                                                        <Button
                                                            key={time}
                                                            variant={formData.appointment_time === time ? 'default' : 'outline'}
                                                            className="h-12"
                                                            onClick={() => handleInputChange('appointment_time', time)}
                                                        >
                                                            {time}
                                                        </Button>
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="text-center py-8 text-gray-500">
                                                    <Clock className="h-12 w-12 mx-auto mb-2 text-gray-400" />
                                                    <p>Aucun créneau disponible pour cette date.</p>
                                                    <p className="text-sm">Veuillez choisir une autre date.</p>
                                                </div>
                                            )}
                                            {errors.appointment_time && (
                                                <p className="text-red-600 text-sm mt-2 flex items-center">
                                                    <AlertCircle className="h-4 w-4 mr-1" />
                                                    {errors.appointment_time}
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Step 2: Contact Information */}
                            {currentStep === 2 && (
                                <div className="space-y-6">
                                    {/* Personal Information */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <Label htmlFor="client_name">Votre nom complet *</Label>
                                            <Input
                                                id="client_name"
                                                value={formData.client_name}
                                                onChange={(e) => handleInputChange('client_name', e.target.value)}
                                                className="mt-2"
                                                placeholder="Jean Dupont"
                                            />
                                            {errors.client_name && (
                                                <p className="text-red-600 text-sm mt-1 flex items-center">
                                                    <AlertCircle className="h-4 w-4 mr-1" />
                                                    {errors.client_name}
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="client_email">Votre email *</Label>
                                            <Input
                                                id="client_email"
                                                type="email"
                                                value={formData.client_email}
                                                onChange={(e) => handleInputChange('client_email', e.target.value)}
                                                className="mt-2"
                                                placeholder="jean.dupont@example.com"
                                            />
                                            {errors.client_email && (
                                                <p className="text-red-600 text-sm mt-1 flex items-center">
                                                    <AlertCircle className="h-4 w-4 mr-1" />
                                                    {errors.client_email}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="client_phone">Votre téléphone (optionnel)</Label>
                                        <Input
                                            id="client_phone"
                                            type="tel"
                                            value={formData.client_phone}
                                            onChange={(e) => handleInputChange('client_phone', e.target.value)}
                                            className="mt-2"
                                            placeholder="+33 1 23 45 67 89"
                                        />
                                    </div>

                                    <Separator />

                                    {/* Meeting Type */}
                                    <div>
                                        <Label className="text-base font-medium">Type de rendez-vous</Label>
                                        <RadioGroup
                                            value={formData.location_type}
                                            onValueChange={(value: 'in_person' | 'zoom' | 'hybrid') =>
                                                handleInputChange('location_type', value)
                                            }
                                            className="mt-3"
                                        >
                                            <div className="flex items-center space-x-2">
                                                <RadioGroupItem value="in_person" id="in_person" />
                                                <Label htmlFor="in_person" className="flex items-center cursor-pointer">
                                                    <MapPin className="h-4 w-4 mr-2" />
                                                    En présentiel à l'église
                                                </Label>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <RadioGroupItem value="zoom" id="zoom" />
                                                <Label htmlFor="zoom" className="flex items-center cursor-pointer">
                                                    <Video className="h-4 w-4 mr-2" />
                                                    Visioconférence (Zoom)
                                                </Label>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <RadioGroupItem value="hybrid" id="hybrid" />
                                                <Label htmlFor="hybrid" className="flex items-center cursor-pointer">
                                                    <Users className="h-4 w-4 mr-2" />
                                                    Hybride (au choix du pasteur)
                                                </Label>
                                            </div>
                                        </RadioGroup>
                                    </div>

                                    {/* Zoom Link if needed */}
                                    {formData.location_type === 'zoom' && (
                                        <div>
                                            <Label htmlFor="zoom_link">Lien Zoom *</Label>
                                            <Input
                                                id="zoom_link"
                                                value={formData.zoom_link}
                                                onChange={(e) => handleInputChange('zoom_link', e.target.value)}
                                                className="mt-2"
                                                placeholder="https://zoom.us/j/..."
                                            />
                                            <p className="text-sm text-gray-500 mt-1">
                                                Si vous préférez utiliser votre propre salle Zoom, sinon le pasteur vous fournira un lien.
                                            </p>
                                            {errors.zoom_link && (
                                                <p className="text-red-600 text-sm mt-1 flex items-center">
                                                    <AlertCircle className="h-4 w-4 mr-1" />
                                                    {errors.zoom_link}
                                                </p>
                                            )}
                                        </div>
                                    )}

                                    {/* Notes */}
                                    <div>
                                        <Label htmlFor="notes">
                                            Sujet ou préoccupations (optionnel)
                                        </Label>
                                        <Textarea
                                            id="notes"
                                            value={formData.notes}
                                            onChange={(e) => handleInputChange('notes', e.target.value)}
                                            className="mt-2"
                                            rows={4}
                                            placeholder="Décrivez brièvement le sujet que vous aimeriez aborder ou vos préoccupations spirituelles..."
                                        />
                                        <p className="text-sm text-gray-500 mt-1">
                                            Ces informations aideront le pasteur à mieux se préparer pour votre rencontre.
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Step 3: Confirmation */}
                            {currentStep === 3 && (
                                <div className="space-y-6">
                                    <div className="bg-blue-50 dark:bg-blue-950 p-6 rounded-lg">
                                        <h3 className="text-lg font-medium text-blue-900 dark:text-blue-100 mb-4">
                                            Récapitulatif de votre rendez-vous
                                        </h3>

                                        <div className="space-y-3">
                                            <div className="flex items-center">
                                                <User className="h-5 w-5 text-blue-600 mr-3" />
                                                <span className="font-medium">Pasteur :</span>
                                                <span className="ml-2">
                                                    {pastors.find(p => p.id.toString() === formData.pastor_id)?.name}
                                                </span>
                                            </div>

                                            <div className="flex items-center">
                                                <CalendarDays className="h-5 w-5 text-blue-600 mr-3" />
                                                <span className="font-medium">Date :</span>
                                                <span className="ml-2">
                                                    {formData.appointment_date && format(new Date(formData.appointment_date), 'EEEE d MMMM yyyy', { locale: fr })}
                                                </span>
                                            </div>

                                            <div className="flex items-center">
                                                <Clock className="h-5 w-5 text-blue-600 mr-3" />
                                                <span className="font-medium">Heure :</span>
                                                <span className="ml-2">
                                                    {formData.appointment_time} ({formData.duration_minutes} minutes)
                                                </span>
                                            </div>

                                            <div className="flex items-center">
                                                {formData.location_type === 'in_person' ? (
                                                    <MapPin className="h-5 w-5 text-blue-600 mr-3" />
                                                ) : (
                                                    <Video className="h-5 w-5 text-blue-600 mr-3" />
                                                )}
                                                <span className="font-medium">Type :</span>
                                                <span className="ml-2">
                                                    {formData.location_type === 'in_person' && 'En présentiel'}
                                                    {formData.location_type === 'zoom' && 'Visioconférence'}
                                                    {formData.location_type === 'hybrid' && 'Hybride'}
                                                </span>
                                            </div>

                                            <div className="flex items-center">
                                                <Mail className="h-5 w-5 text-blue-600 mr-3" />
                                                <span className="font-medium">Contact :</span>
                                                <span className="ml-2">{formData.client_email}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="bg-amber-50 dark:bg-amber-950 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
                                        <div className="flex items-start">
                                            <AlertCircle className="h-5 w-5 text-amber-600 mr-3 mt-0.5" />
                                            <div className="text-sm text-amber-800 dark:text-amber-200">
                                                <h4 className="font-medium mb-2">Important :</h4>
                                                <ul className="space-y-1">
                                                    <li>• Vous recevrez un email de confirmation avec les détails</li>
                                                    <li>• Veuillez confirmer votre présence via le lien dans l'email</li>
                                                    <li>• Les annulations doivent être faites au moins 24h à l'avance</li>
                                                    <li>• En cas de problème, contactez directement le pasteur</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Navigation Buttons */}
                            <div className="flex justify-between pt-6 border-t">
                                <div>
                                    {currentStep > 1 && (
                                        <Button variant="outline" onClick={handlePrevStep}>
                                            Précédent
                                        </Button>
                                    )}
                                </div>

                                <div>
                                    {currentStep < 3 ? (
                                        <Button onClick={handleNextStep}>
                                            Suivant
                                        </Button>
                                    ) : (
                                        <Button
                                            onClick={handleSubmit}
                                            disabled={isSubmitting}
                                            className="bg-green-600 hover:bg-green-700"
                                        >
                                            {isSubmitting ? (
                                                <>
                                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                                    Création en cours...
                                                </>
                                            ) : (
                                                <>
                                                    <CheckCircle className="h-4 w-4 mr-2" />
                                                    Confirmer le rendez-vous
                                                </>
                                            )}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Footer Info */}
                    <div className="text-center mt-8 text-gray-600 dark:text-gray-400">
                        <p className="text-sm">
                            Besoin d'aide ? Contactez-nous à{' '}
                            <a href="mailto:info@icc-munich.de" className="text-blue-600 hover:underline">
                                info@icc-munich.de
                            </a>
                            {' '}ou au{' '}
                            <a href="tel:+498912345678" className="text-blue-600 hover:underline">
                                +49 89 123 456 78
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}