import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import {
    CalendarDays,
    Clock,
    Loader2,
    AlertCircle,
    ChevronLeft,
    ChevronRight,
    Calendar,
} from 'lucide-react';
import { toast } from 'sonner';
import { format, startOfWeek, endOfWeek, addWeeks, subWeeks, addMonths, subMonths, startOfMonth, endOfMonth, startOfDay } from 'date-fns';
import { fr } from 'date-fns/locale';
import { apiFetch } from '@/lib/utils';

interface FollowUpModalProps {
    isOpen: boolean;
    onClose: () => void;
    parentAppointment: {
        uuid: string;
        pastor_id: number;
        client_name: string;
        client_email: string;
        client_phone?: string;
        duration_minutes: number;
        location_type: 'in_person' | 'zoom' | 'hybrid';
        zoom_link?: string;
    };
}

interface AvailableDay {
    date: string;
    day_name: string;
    full_date: string;
    slots_count: number;
}

export default function FollowUpModal({ isOpen, onClose, parentAppointment }: FollowUpModalProps) {
    const [currentStep, setCurrentStep] = useState(1);
    const [isLoading, setIsLoading] = useState(false);
    const [isLoadingDays, setIsLoadingDays] = useState(false);
    const [isLoadingSlots, setIsLoadingSlots] = useState(false);
    const [availableDays, setAvailableDays] = useState<AvailableDay[]>([]);
    const [availableSlots, setAvailableSlots] = useState<string[]>([]);
    const [currentDate, setCurrentDate] = useState(new Date());
    const [dateViewType, setDateViewType] = useState<'week' | 'month'>('month');
    const [errors, setErrors] = useState<Record<string, string>>({});

    const [formData, setFormData] = useState({
        appointment_date: '',
        appointment_time: '',
        duration_minutes: parentAppointment.duration_minutes,
        location_type: parentAppointment.location_type,
        zoom_link: parentAppointment.zoom_link || '',
        notes: '',
    });

    // Reset form when modal opens
    useEffect(() => {
        if (isOpen) {
            setCurrentStep(1);
            setFormData({
                appointment_date: '',
                appointment_time: '',
                duration_minutes: parentAppointment.duration_minutes,
                location_type: parentAppointment.location_type,
                zoom_link: parentAppointment.zoom_link || '',
                notes: '',
            });
            setErrors({});
            fetchAvailableDays();
        }
    }, [isOpen]);

    // Fetch available days when date view changes
    useEffect(() => {
        if (isOpen) {
            fetchAvailableDays();
        }
    }, [currentDate, dateViewType]);

    // Fetch available slots when date is selected
    useEffect(() => {
        if (isOpen && formData.appointment_date) {
            fetchAvailableSlots();
        }
    }, [formData.appointment_date, formData.duration_minutes]);

    const fetchAvailableDays = async () => {
        setIsLoadingDays(true);
        try {
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
                pastor_id: parentAppointment.pastor_id.toString(),
                start_date: format(startDate, 'yyyy-MM-dd'),
                end_date: format(endDate, 'yyyy-MM-dd'),
            });

            const result = await apiFetch<{ success: boolean; data: { available_days: AvailableDay[] } }>(`/api/care-service/available-days?${params}`);

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
                pastor_id: parentAppointment.pastor_id.toString(),
                date: formData.appointment_date,
                duration: formData.duration_minutes.toString(),
            });

            const result = await apiFetch<{ success: boolean; data: { slots: string[] } }>(`/api/care-service/available-slots?${params}`);

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

    const getAvailableDates = () => {
        const today = startOfDay(new Date());
        return availableDays
            .map(day => new Date(day.date))
            .filter(date => date >= today)
            .sort((a, b) => a.getTime() - b.getTime());
    };

    const handleInputChange = (field: string, value: string | number) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: '' }));
        }
    };

    const validateStep = (step: number): boolean => {
        const stepErrors: Record<string, string> = {};

        if (step === 1) {
            if (!formData.appointment_date) stepErrors.appointment_date = 'Veuillez sélectionner une date';
            if (!formData.appointment_time) stepErrors.appointment_time = 'Veuillez sélectionner un créneau';
        }

        setErrors(stepErrors);
        return Object.keys(stepErrors).length === 0;
    };

    const handleNextStep = () => {
        if (validateStep(currentStep)) {
            setCurrentStep(prev => prev + 1);
        }
    };

    const handleSubmit = async () => {
        if (!validateStep(1)) return;

        setIsLoading(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const result = await apiFetch<{ success: boolean; message?: string; data: { uuid: string } }>(
                `/api/care-service/appointments/${parentAppointment.uuid}/follow-up`,
                {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(formData),
                }
            );

            if (result.success && result.data?.success) {
                toast.success(result.data.message || 'Rendez-vous de suivi créé avec succès');
                onClose();
                router.visit(`/care-service/appointments/${result.data.data.uuid}`);
            } else {
                toast.error(result.error || result.data?.message || 'Erreur lors de la création du rendez-vous');
            }
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center">
                        <CalendarDays className="h-5 w-5 mr-2" />
                        Planifier un rendez-vous de suivi
                    </DialogTitle>
                    <DialogDescription>
                        Créer un nouveau rendez-vous avec {parentAppointment.client_name}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6 py-4 px-3">
                    {/* Duration Selection */}
                    <div>
                        <Label className="text-sm font-medium">Durée du rendez-vous</Label>
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
                            <Label className="text-sm font-medium">Choisissez une date</Label>
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

                        {/* Navigation Header */}
                        <div className="flex items-center justify-between mb-4 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => navigateDate('prev')}
                            >
                                <ChevronLeft className="h-4 w-4" />
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
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>

                        {/* Date Grid */}
                        {isLoadingDays ? (
                            <div className="flex items-center justify-center py-8">
                                <Loader2 className="h-6 w-6 animate-spin" />
                                <span className="ml-2">Chargement des dates...</span>
                            </div>
                        ) : getAvailableDates().length > 0 ? (
                            <div className={`grid gap-2 ${dateViewType === 'week' ? 'grid-cols-4' : 'grid-cols-4 md:grid-cols-6'}`}>
                                {getAvailableDates().map((date) => {
                                    const dayInfo = availableDays.find(day => day.date === format(date, 'yyyy-MM-dd'));
                                    return (
                                        <Button
                                            key={date.toISOString()}
                                            type="button"
                                            variant={formData.appointment_date === format(date, 'yyyy-MM-dd') ? 'default' : 'outline'}
                                            className="h-auto p-2 flex flex-col items-center relative"
                                            onClick={() => handleInputChange('appointment_date', format(date, 'yyyy-MM-dd'))}
                                        >
                                            <span className="text-xs font-medium">
                                                {format(date, 'EEE', { locale: fr })}
                                            </span>
                                            <span className="text-base font-bold">
                                                {format(date, 'd', { locale: fr })}
                                            </span>
                                            {dayInfo && dayInfo.slots_count > 0 && (
                                                <Badge variant="secondary" className="absolute -top-1 -right-1 h-4 w-4 p-0 text-[10px]">
                                                    {dayInfo.slots_count}
                                                </Badge>
                                            )}
                                        </Button>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="text-center py-6 text-gray-500 dark:text-gray-400">
                                <Calendar className="h-10 w-10 mx-auto mb-2 text-gray-400" />
                                <p className="text-sm">Aucune date disponible pour cette période.</p>
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
                    {formData.appointment_date && (
                        <div>
                            <Label className="text-sm font-medium">Choisissez un horaire</Label>
                            {isLoadingSlots ? (
                                <div className="flex items-center justify-center py-6">
                                    <Loader2 className="h-6 w-6 animate-spin" />
                                    <span className="ml-2">Chargement des créneaux...</span>
                                </div>
                            ) : availableSlots.length > 0 ? (
                                <div className="grid grid-cols-4 md:grid-cols-5 gap-2 mt-2">
                                    {availableSlots.map((time) => (
                                        <Button
                                            key={time}
                                            variant={formData.appointment_time === time ? 'default' : 'outline'}
                                            className="h-10"
                                            onClick={() => handleInputChange('appointment_time', time)}
                                        >
                                            {time}
                                        </Button>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-6 text-gray-500">
                                    <Clock className="h-10 w-10 mx-auto mb-2 text-gray-400" />
                                    <p className="text-sm">Aucun créneau disponible pour cette date.</p>
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

                    {/* Notes */}
                    <div>
                        <Label htmlFor="notes" className="text-sm font-medium">Notes (optionnel)</Label>
                        <Textarea
                            id="notes"
                            value={formData.notes}
                            onChange={(e) => handleInputChange('notes', e.target.value)}
                            placeholder="Notes pour ce rendez-vous de suivi..."
                            className="mt-2 min-h-[80px]"
                        />
                    </div>

                    {/* Client Info Summary */}
                    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <h4 className="text-sm font-medium mb-2">Informations du client</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-300">
                            <strong>Nom:</strong> {parentAppointment.client_name}
                        </p>
                        <p className="text-sm text-gray-600 dark:text-gray-300">
                            <strong>Email:</strong> {parentAppointment.client_email}
                        </p>
                        {parentAppointment.client_phone && (
                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                <strong>Téléphone:</strong> {parentAppointment.client_phone}
                            </p>
                        )}
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={onClose}>
                        Annuler
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={isLoading || !formData.appointment_date || !formData.appointment_time}
                    >
                        {isLoading ? (
                            <>
                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                Création...
                            </>
                        ) : (
                            'Créer le rendez-vous'
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
