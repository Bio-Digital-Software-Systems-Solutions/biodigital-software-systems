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
import { SearchableSelect } from '@/Components/ui/searchable-select';
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
    ChevronLeft,
    ChevronRight,
    Calendar,
} from 'lucide-react';
import { toast } from 'sonner';
import { format, addDays, isBefore, startOfDay, startOfWeek, endOfWeek, addWeeks, subWeeks, addMonths, subMonths, startOfMonth, endOfMonth, eachDayOfInterval } from 'date-fns';
import { fr } from 'date-fns/locale';
import { apiFetch } from '@/lib/utils';

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

interface AvailableDay {
    date: string;
    day_name: string;
    full_date: string;
    slots_count: number;
    pastors_count?: number; // Only present when fetching all pastors
}

interface SlotWithPastor {
    time: string;
    pastor_id: number;
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
    canSelectPastor?: boolean;
}

export default function PublicBook({ auth, canSelectPastor = false }: Props) {
    const [pastors, setPastors] = useState<Pastor[]>([]);
    const [availableSlots, setAvailableSlots] = useState<string[]>([]);
    const [availableSlotsWithPastor, setAvailableSlotsWithPastor] = useState<SlotWithPastor[]>([]);
    const [availableDays, setAvailableDays] = useState<AvailableDay[]>([]);
    const [isLoadingPastors, setIsLoadingPastors] = useState(true);
    const [isLoadingSlots, setIsLoadingSlots] = useState(false);
    const [isLoadingDays, setIsLoadingDays] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [currentStep, setCurrentStep] = useState(1);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [dateViewType, setDateViewType] = useState<'week' | 'month'>('month');
    const [currentDate, setCurrentDate] = useState(new Date());

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

    // Load pastors on component mount (only if user can select pastor)
    useEffect(() => {
        if (canSelectPastor) {
            fetchPastors();
        } else {
            setIsLoadingPastors(false);
            // Auto-load available days for all pastors
            fetchAllAvailableDays();
        }
    }, [canSelectPastor]);

    // Load available days when pastor is selected or date range changes (only for users who can select pastor)
    useEffect(() => {
        if (!canSelectPastor) {
            // For users who can't select pastor, load all available days
            fetchAllAvailableDays();
            return;
        }

        if (formData.pastor_id) {
            fetchAvailableDays();
        } else {
            setAvailableDays([]);
            setFormData(prev => ({ ...prev, appointment_date: '', appointment_time: '' }));
        }
    }, [formData.pastor_id, currentDate, dateViewType, canSelectPastor]);

    // Load available slots when pastor, date, or duration changes
    useEffect(() => {
        if (canSelectPastor) {
            // User can select pastor - need both pastor_id and date
            if (formData.pastor_id && formData.appointment_date && formData.duration_minutes) {
                fetchAvailableSlots();
            }
        } else {
            // User can't select pastor - only need date
            if (formData.appointment_date && formData.duration_minutes) {
                fetchAllAvailableSlots();
            }
        }
    }, [formData.pastor_id, formData.appointment_date, formData.duration_minutes, canSelectPastor]);

    const fetchPastors = async () => {
        try {
            const result = await apiFetch<{ success: boolean; data: Pastor[] }>('/api/pastoral-care/pastors');
            if (result.success && result.data?.success) {
                setPastors(result.data.data);
            } else {
                toast.error(result.error || 'Erreur lors du chargement des pasteurs');
            }
        } finally {
            setIsLoadingPastors(false);
        }
    };

    const fetchAvailableDays = async () => {
        if (!formData.pastor_id) {
            return;
        }

        setIsLoadingDays(true);
        try {
            // Calculate date range based on current view
            let startDate: Date;
            let endDate: Date;

            if (dateViewType === 'week') {
                startDate = startOfWeek(currentDate, { weekStartsOn: 1 });
                endDate = endOfWeek(currentDate, { weekStartsOn: 1 });
            } else {
                startDate = startOfMonth(currentDate);
                endDate = endOfMonth(currentDate);
            }

            const params = new URLSearchParams({
                pastor_id: formData.pastor_id,
                start_date: format(startDate, 'yyyy-MM-dd'),
                end_date: format(endDate, 'yyyy-MM-dd'),
            });

            const result = await apiFetch<{ success: boolean; data: { available_days: AvailableDay[] } }>(`/api/pastoral-care/available-days?${params}`);

            if (result.success && result.data?.success) {
                setAvailableDays(result.data.data.available_days);
            } else {
                toast.error(result.error || 'Erreur lors du chargement des jours disponibles');
                setAvailableDays([]);
            }
        } finally {
            setIsLoadingDays(false);
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

            const result = await apiFetch<{ success: boolean; data: { slots: string[] } }>(`/api/pastoral-care/available-slots?${params}`);

            if (result.success && result.data?.success) {
                setAvailableSlots(result.data.data.slots);
            } else {
                toast.error(result.error || 'Erreur lors du chargement des créneaux');
                setAvailableSlots([]);
            }
        } finally {
            setIsLoadingSlots(false);
        }
    };

    // Fetch all available days from all pastors (for users who can't select pastor)
    const fetchAllAvailableDays = async () => {
        setIsLoadingDays(true);
        try {
            // Calculate date range based on current view
            let startDate: Date;
            let endDate: Date;

            if (dateViewType === 'week') {
                startDate = startOfWeek(currentDate, { weekStartsOn: 1 });
                endDate = endOfWeek(currentDate, { weekStartsOn: 1 });
            } else {
                startDate = startOfMonth(currentDate);
                endDate = endOfMonth(currentDate);
            }

            const params = new URLSearchParams({
                start_date: format(startDate, 'yyyy-MM-dd'),
                end_date: format(endDate, 'yyyy-MM-dd'),
            });

            const result = await apiFetch<{ success: boolean; data: { available_days: AvailableDay[] } }>(`/api/pastoral-care/all-available-days?${params}`);

            if (result.success && result.data?.success) {
                setAvailableDays(result.data.data.available_days);
            } else {
                toast.error(result.error || 'Erreur lors du chargement des jours disponibles');
                setAvailableDays([]);
            }
        } finally {
            setIsLoadingDays(false);
        }
    };

    // Fetch all available slots from all pastors for a specific date
    const fetchAllAvailableSlots = async () => {
        setIsLoadingSlots(true);
        try {
            const params = new URLSearchParams({
                date: formData.appointment_date,
                duration: formData.duration_minutes.toString(),
            });

            const result = await apiFetch<{ success: boolean; data: { slots: SlotWithPastor[] } }>(`/api/pastoral-care/all-available-slots?${params}`);

            if (result.success && result.data?.success) {
                setAvailableSlotsWithPastor(result.data.data.slots);
                // Also set regular slots for compatibility
                setAvailableSlots(result.data.data.slots.map((s) => s.time));
            } else {
                toast.error(result.error || 'Erreur lors du chargement des créneaux');
                setAvailableSlotsWithPastor([]);
                setAvailableSlots([]);
            }
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
            // Only require pastor_id if user can select pastor
            if (canSelectPastor && !formData.pastor_id) {
                stepErrors.pastor_id = 'Veuillez sélectionner un pasteur';
            }
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

    const getAvailableDates = () => {
        // If user can select pastor and no pastor selected, show no dates
        if (canSelectPastor && !formData.pastor_id) {
            return [];
        }

        // If loading days, show previous dates temporarily
        if (isLoadingDays) {
            return [];
        }

        // Convert available days to Date objects and filter within current range
        const today = startOfDay(new Date());
        return availableDays
            .map(day => new Date(day.date))
            .filter(date => date >= today)
            .sort((a, b) => a.getTime() - b.getTime());
    };

    const navigateDate = (direction: 'prev' | 'next') => {
        if (dateViewType === 'week') {
            setCurrentDate(direction === 'next' ? addWeeks(currentDate, 1) : subWeeks(currentDate, 1));
        } else {
            setCurrentDate(direction === 'next' ? addMonths(currentDate, 1) : subMonths(currentDate, 1));
        }
    };

    const getDateRangeLabel = () => {
        if (dateViewType === 'week') {
            const start = startOfWeek(currentDate, { weekStartsOn: 1 });
            const end = endOfWeek(currentDate, { weekStartsOn: 1 });
            return `${format(start, 'd MMM', { locale: fr })} - ${format(end, 'd MMM yyyy', { locale: fr })}`;
        } else {
            return format(currentDate, 'MMMM yyyy', { locale: fr });
        }
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
                                {currentStep === 1 && (canSelectPastor
                                    ? "Sélectionnez un pasteur, une date et un horaire qui vous conviennent"
                                    : "Sélectionnez une date et un horaire parmi les créneaux disponibles"
                                )}
                                {currentStep === 2 && "Complétez vos informations de contact et préférences"}
                                {currentStep === 3 && "Vérifiez les détails de votre rendez-vous avant de confirmer"}
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            {/* Step 1: Pastor and Time Selection */}
                            {currentStep === 1 && (
                                <div className="space-y-6">
                                    {/* Pastor Selection - Only shown if user has permission */}
                                    {canSelectPastor && (
                                        <>
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
                                                    <div className="mt-2">
                                                        <SearchableSelect
                                                            options={pastors.map(pastor => ({
                                                                value: pastor.id.toString(),
                                                                label: pastor.name
                                                            }))}
                                                            value={formData.pastor_id}
                                                            onChange={(value) => handleInputChange('pastor_id', value?.toString() || '')}
                                                            placeholder="Choisissez un pasteur..."
                                                            className="w-full"
                                                        />
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
                                        </>
                                    )}

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
                                        <div className="flex items-center justify-between mb-4">
                                            <Label className="text-base font-medium">Choisissez une date *</Label>
                                            <div className="flex items-center space-x-2">
                                                {/* View Type Toggle */}
                                                <div className="flex rounded-lg border border-gray-200 dark:border-gray-600 p-1">
                                                    <Button
                                                        type="button"
                                                        variant={dateViewType === 'month' ? 'default' : 'ghost'}
                                                        size="sm"
                                                        className="px-3 py-1 text-xs"
                                                        onClick={() => setDateViewType('month')}
                                                    >
                                                        Mois
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant={dateViewType === 'week' ? 'default' : 'ghost'}
                                                        size="sm"
                                                        className="px-3 py-1 text-xs"
                                                        onClick={() => setDateViewType('week')}
                                                    >
                                                        Semaine
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Navigation Header */}
                                        <div className="flex items-center justify-between mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => navigateDate('prev')}
                                                className="flex items-center space-x-1"
                                            >
                                                <ChevronLeft className="h-4 w-4" />
                                                <span>Précédent</span>
                                            </Button>

                                            <div className="flex items-center space-x-2 text-sm font-medium">
                                                <Calendar className="h-4 w-4" />
                                                <span>{getDateRangeLabel()}</span>
                                            </div>

                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => navigateDate('next')}
                                                className="flex items-center space-x-1"
                                            >
                                                <span>Suivant</span>
                                                <ChevronRight className="h-4 w-4" />
                                            </Button>
                                        </div>

                                        {/* Date Grid */}
                                        {/* Message to select pastor - only when user can select and hasn't selected */}
                                        {canSelectPastor && !formData.pastor_id && (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                <User className="h-12 w-12 mx-auto mb-2 text-gray-400" />
                                                <p>Veuillez d'abord sélectionner un pasteur</p>
                                                <p className="text-sm">Les dates disponibles s'afficheront automatiquement.</p>
                                            </div>
                                        )}

                                        {/* Loading state */}
                                        {((!canSelectPastor) || formData.pastor_id) && isLoadingDays && (
                                            <div className="flex items-center justify-center py-8">
                                                <Loader2 className="h-6 w-6 animate-spin" />
                                                <span className="ml-2">Chargement des dates disponibles...</span>
                                            </div>
                                        )}

                                        {/* Date buttons grid */}
                                        {((!canSelectPastor) || formData.pastor_id) && !isLoadingDays && (
                                            <div className={`grid gap-3 ${dateViewType === 'week' ? 'grid-cols-2 md:grid-cols-5' : 'grid-cols-2 md:grid-cols-4 lg:grid-cols-6'}`}>
                                                {getAvailableDates().map((date) => {
                                                    const dayInfo = availableDays.find(day => day.date === format(date, 'yyyy-MM-dd'));
                                                    return (
                                                        <Button
                                                            key={date.toISOString()}
                                                            type="button"
                                                            variant={formData.appointment_date === format(date, 'yyyy-MM-dd') ? 'default' : 'outline'}
                                                            className="h-auto p-3 flex flex-col items-center relative"
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
                                                            {dayInfo && dayInfo.slots_count > 0 && (
                                                                <Badge variant="secondary" className="absolute -top-2 -right-2 h-5 w-5 p-0 text-xs">
                                                                    {dayInfo.slots_count}
                                                                </Badge>
                                                            )}
                                                        </Button>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        {/* No dates available message */}
                                        {((!canSelectPastor) || formData.pastor_id) && !isLoadingDays && getAvailableDates().length === 0 && (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                <Calendar className="h-12 w-12 mx-auto mb-2 text-gray-400" />
                                                <p>Aucune date disponible pour cette période.</p>
                                                <p className="text-sm">Utilisez la navigation pour voir d'autres périodes.</p>
                                            </div>
                                        )}

                                        {errors.appointment_date && (
                                            <p className="text-red-600 text-sm mt-2 flex items-center">
                                                <AlertCircle className="h-4 w-4 mr-1" />
                                                {errors.appointment_date}
                                            </p>
                                        )}
                                    </div>

                                    {/* Time Slot Selection */}
                                    {/* For users who CAN select pastor: need pastor_id and appointment_date */}
                                    {canSelectPastor && formData.pastor_id && formData.appointment_date && (
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

                                    {/* For users who CANNOT select pastor: only need appointment_date */}
                                    {!canSelectPastor && formData.appointment_date && (
                                        <div>
                                            <Label className="text-base font-medium">Choisissez un horaire *</Label>
                                            <p className="text-sm text-gray-500 mb-2">
                                                Un pasteur disponible vous sera automatiquement assigné
                                            </p>
                                            {isLoadingSlots ? (
                                                <div className="flex items-center justify-center py-8">
                                                    <Loader2 className="h-6 w-6 animate-spin" />
                                                    <span className="ml-2">Chargement des créneaux...</span>
                                                </div>
                                            ) : availableSlotsWithPastor.length > 0 ? (
                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
                                                    {availableSlotsWithPastor.map((slot, index) => (
                                                        <Button
                                                            key={`${slot.time}-${slot.pastor_id}-${index}`}
                                                            variant={formData.appointment_time === slot.time && formData.pastor_id === slot.pastor_id.toString() ? 'default' : 'outline'}
                                                            className="h-auto p-3 flex flex-col items-start text-left"
                                                            onClick={() => {
                                                                // Set both time and pastor_id when slot is clicked
                                                                setFormData(prev => ({
                                                                    ...prev,
                                                                    appointment_time: slot.time,
                                                                    pastor_id: slot.pastor_id.toString()
                                                                }));
                                                            }}
                                                        >
                                                            <span className="text-lg font-bold">{slot.time}</span>
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
                                            placeholder="+49 1 23 45 67 89"
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
                                            Ces informations aideront le pasteur ou le MLR à mieux se préparer pour votre rencontre.
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
                                            {/* Only show pastor name if user has permission to select pastor */}
                                            {canSelectPastor && (
                                                <div className="flex items-center">
                                                    <User className="h-5 w-5 text-blue-600 mr-3" />
                                                    <span className="font-medium">Pasteur :</span>
                                                    <span className="ml-2">
                                                        {pastors.find(p => p.id.toString() === formData.pastor_id)?.name}
                                                    </span>
                                                </div>
                                            )}

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
                                                    <li>• En cas de problème, contactez directement le pasteur ou le MLR</li>
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
                                contact@icc-munich.de
                            </a>
                            {' '}ou au{' '}
                            <a href="tel:+4917673200275" className="text-blue-600 hover:underline">
                                +49 (0) 17673200275
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}