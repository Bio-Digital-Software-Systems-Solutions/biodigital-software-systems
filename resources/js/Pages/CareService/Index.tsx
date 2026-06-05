import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
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
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Separator } from '@/Components/ui/separator';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import {
    CalendarIcon,
    ClockIcon,
    UserGroupIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
    PhoneIcon,
    MapPinIcon,
    VideoCameraIcon,
} from '@heroicons/react/24/outline';
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
    Calendar,
    Loader2,
    AlertCircle,
    ChevronLeft,
    ChevronRight,
    Heart,
} from 'lucide-react';
import { format, addDays, isBefore, startOfDay, startOfWeek, endOfWeek, addWeeks, subWeeks, addMonths, subMonths, startOfMonth, endOfMonth, eachDayOfInterval, isSameDay, isSameMonth } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';
import { apiFetch } from '@/lib/utils';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface Pastor {
    id: number;
    name: string;
    email: string;
    phone?: string;
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

interface CareServiceAppointment {
    id: number;
    uuid: string;
    user?: User;
    pastor: User;
    appointment_date: string;
    appointment_time: string;
    duration_minutes: number;
    status: 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';
    location_type: 'in_person' | 'zoom' | 'hybrid';
    zoom_link?: string;
    client_name: string | null;
    client_email: string | null;
    client_phone?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
    can_be_confirmed: boolean;
    can_be_cancelled: boolean;
}

interface Stats {
    total_appointments: number;
    pending_appointments: number;
    confirmed_appointments: number;
    completed_appointments: number;
    cancelled_appointments: number;
    this_week_appointments: number;
    next_week_appointments: number;
}

interface PaginatedAppointments {
    data: CareServiceAppointment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Props {
    appointments: PaginatedAppointments;
    stats: Stats;
    canManageAll?: boolean;
    permissions?: {
        canCreate: boolean;
        canEdit: boolean;
        canDelete: boolean;
        canManage: boolean;
        canSelectPastor: boolean;
        canViewCareServiceDashboard?: boolean;
        canTransfer?: boolean;
    };
    auth: {
        user: User;
    };
}

export default function Index({ appointments, stats, canManageAll, permissions, auth }: Props) {
    const [activeTab, setActiveTab] = useState('dashboard');

    // Booking form state
    const [pastors, setPastors] = useState<Pastor[]>([]);
    const [availableSlots, setAvailableSlots] = useState<string[]>([]);
    const [availableSlotsWithPastor, setAvailableSlotsWithPastor] = useState<SlotWithPastor[]>([]);
    const [availableDates, setAvailableDates] = useState<string[]>([]);
    const [isLoadingPastors, setIsLoadingPastors] = useState(false);
    const [isLoadingSlots, setIsLoadingSlots] = useState(false);
    const [isLoadingDates, setIsLoadingDates] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [currentStep, setCurrentStep] = useState(1);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [dateViewType, setDateViewType] = useState<'week' | 'month'>('month');
    const [currentDate, setCurrentDate] = useState(new Date());

    // Proposal mode state
    const [proposalMode, setProposalMode] = useState(false);
    const [proposalSlots, setProposalSlots] = useState<Array<{date: string; time: string}>>([]);
    const [isSubmittingProposal, setIsSubmittingProposal] = useState(false);
    const [proposalFormData, setProposalFormData] = useState({
        client_name: '',
        client_email: '',
        client_phone: '',
        notes: '',
    });

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

    // Load pastors when booking tab is active (only if user can select pastor)
    useEffect(() => {
        if (activeTab === 'booking') {
            if (permissions?.canSelectPastor && pastors.length === 0) {
                fetchPastors();
            } else if (!permissions?.canSelectPastor) {
                // Auto-load available days for all pastors
                fetchAllAvailableDays();
            }
        }
    }, [activeTab, permissions?.canSelectPastor]);

    // Load available slots when pastor, date, or duration changes
    useEffect(() => {
        if (permissions?.canSelectPastor) {
            // User can select pastor - need both pastor_id and date
            if (formData.pastor_id && formData.appointment_date && formData.duration_minutes) {
                fetchAvailableSlots();

                // Scroll to time slots section when a date is selected
                setTimeout(() => {
                    const timeSlotsSection = document.querySelector('[data-time-slots-section]');
                    if (timeSlotsSection) {
                        timeSlotsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            }
        } else {
            // User can't select pastor - only need date
            if (formData.appointment_date && formData.duration_minutes) {
                fetchAllAvailableSlots();

                // Scroll to time slots section when a date is selected
                setTimeout(() => {
                    const timeSlotsSection = document.querySelector('[data-time-slots-section]');
                    if (timeSlotsSection) {
                        timeSlotsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            }
        }
    }, [formData.pastor_id, formData.appointment_date, formData.duration_minutes, permissions?.canSelectPastor]);

    // Auto-select first available date when pastor changes (only if user can select pastor)
    useEffect(() => {
        if (permissions?.canSelectPastor && formData.pastor_id && !formData.appointment_date) {
            findAndSelectFirstAvailableDate();
        }
    }, [formData.pastor_id, permissions?.canSelectPastor]);

    const findAndSelectFirstAvailableDate = async () => {
        if (!formData.pastor_id) return;

        try {
            const today = new Date();
            const endDate = addDays(today, 30); // Look for availability in next 30 days

            const params = new URLSearchParams({
                pastor_id: formData.pastor_id,
                start_date: format(today, 'yyyy-MM-dd'),
                end_date: format(endDate, 'yyyy-MM-dd'),
            });

            const response = await fetch(`/api/care-service/available-days?${params}`);
            const data = await response.json();

            if (data.success && data.data.available_days.length > 0) {
                // Store all available dates for calendar highlighting
                const availableDatesArray = data.data.available_days.map((day: any) => day.date);
                setAvailableDates(availableDatesArray);

                // Select the first available date
                const firstAvailableDate = data.data.available_days[0].date;
                setFormData(prev => ({
                    ...prev,
                    appointment_date: firstAvailableDate,
                    appointment_time: '' // Reset selected time
                }));

                // Navigate to the month containing the first available date if needed
                const firstDate = new Date(firstAvailableDate);
                if (!isSameMonth(firstDate, currentDate)) {
                    setCurrentDate(firstDate);
                }
            } else {
                setAvailableDates([]);
            }
        } catch (error) {
            console.error('Error finding first available date:', error);
        }
    };

    const fetchPastors = async () => {
        setIsLoadingPastors(true);
        try {
            const result = await apiFetch<{ success: boolean; data: Pastor[] }>('/api/care-service/pastors');
            if (result.success && result.data?.success) {
                setPastors(result.data.data);
            } else {
                toast.error(result.error || 'Erreur lors du chargement des pasteurs');
            }
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

            const result = await apiFetch<{ success: boolean; data: { slots: string[]; consultation_mode?: string } }>(`/api/care-service/available-slots?${params}`);

            if (result.success && result.data?.success) {
                setAvailableSlots(result.data.data.slots);

                // Update location type from consultation mode
                if (result.data.data.consultation_mode) {
                    setFormData(prev => ({
                        ...prev,
                        location_type: result.data!.data.consultation_mode as 'in_person' | 'zoom' | 'hybrid'
                    }));
                }
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
        setIsLoadingDates(true);
        try {
            const today = new Date();
            const endDate = addDays(today, 30); // Look for availability in next 30 days

            const params = new URLSearchParams({
                start_date: format(today, 'yyyy-MM-dd'),
                end_date: format(endDate, 'yyyy-MM-dd'),
            });

            const result = await apiFetch<{ success: boolean; data: { available_days: { date: string }[] } }>(`/api/care-service/all-available-days?${params}`);

            if (result.success && result.data?.success && result.data.data.available_days.length > 0) {
                const availableDatesArray = result.data.data.available_days.map((day) => day.date);
                setAvailableDates(availableDatesArray);

                // Select the first available date
                const firstAvailableDate = result.data.data.available_days[0].date;
                setFormData(prev => ({
                    ...prev,
                    appointment_date: firstAvailableDate,
                    appointment_time: ''
                }));

                // Navigate to the month containing the first available date if needed
                const firstDate = new Date(firstAvailableDate);
                if (!isSameMonth(firstDate, currentDate)) {
                    setCurrentDate(firstDate);
                }
            } else {
                if (result.error) {
                    toast.error(result.error);
                }
                setAvailableDates([]);
            }
        } finally {
            setIsLoadingDates(false);
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

            const result = await apiFetch<{ success: boolean; data: { slots: SlotWithPastor[] } }>(`/api/care-service/all-available-slots?${params}`);

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
        setFormData(prev => {
            const newData = { ...prev, [field]: value };

            // When pastor changes, reset date and time to trigger auto-selection
            if (field === 'pastor_id') {
                newData.appointment_date = '';
                newData.appointment_time = '';
                // Clear available dates when pastor changes
                setAvailableDates([]);
                setAvailableSlots([]);
            }

            return newData;
        });

        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: '' }));
        }
    };

    const validateStep = (step: number): boolean => {
        const stepErrors: Record<string, string> = {};

        if (step === 1) {
            // Only require pastor_id if user can select pastor
            if (permissions?.canSelectPastor && !formData.pastor_id) {
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

        router.post('/care-service/appointments', formData as any, {
            onSuccess: () => {
                // Reset form
                setFormData({
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
                setCurrentStep(1);
                setActiveTab('dashboard');
                toast.success('Rendez-vous créé avec succès!');
            },
            onError: (errors) => {
                setErrors(errors);
                const errorMessage = errors.appointment_time?.[0] ||
                                   errors.pastor_id?.[0] ||
                                   errors.client_name?.[0] ||
                                   errors.client_email?.[0] ||
                                   'Erreur lors de la création du rendez-vous';
                toast.error(errorMessage);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    // Proposal mode functions
    const switchToProposalMode = () => {
        setProposalMode(true);
        // Initialize proposal form with user's info and keep the selected date
        setProposalFormData({
            client_name: `${auth.user.first_name} ${auth.user.last_name}`,
            client_email: auth.user.email,
            client_phone: '',
            notes: '',
        });
        // Pre-populate with selected date if exists
        if (formData.appointment_date) {
            setProposalSlots([{ date: formData.appointment_date, time: '' }]);
        } else {
            setProposalSlots([{ date: '', time: '' }]);
        }
    };

    const cancelProposalMode = () => {
        setProposalMode(false);
        setProposalSlots([]);
        setProposalFormData({
            client_name: '',
            client_email: '',
            client_phone: '',
            notes: '',
        });
    };

    const addProposalSlot = () => {
        setProposalSlots(prev => [...prev, { date: formData.appointment_date || '', time: '' }]);
    };

    const removeProposalSlot = (index: number) => {
        setProposalSlots(prev => prev.filter((_, i) => i !== index));
    };

    const updateProposalSlot = (index: number, field: 'date' | 'time', value: string) => {
        setProposalSlots(prev => {
            const updated = [...prev];
            updated[index] = { ...updated[index], [field]: value };
            return updated;
        });
    };

    const submitProposal = async () => {
        // Validate proposal
        const validSlots = proposalSlots.filter(s => s.date && s.time);
        if (validSlots.length === 0) {
            toast.error('Veuillez ajouter au moins un créneau avec date et heure');
            return;
        }
        if (!proposalFormData.client_name.trim()) {
            toast.error('Votre nom est requis');
            return;
        }
        if (!proposalFormData.client_email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(proposalFormData.client_email)) {
            toast.error('Veuillez entrer un email valide');
            return;
        }
        if (!proposalFormData.notes.trim()) {
            toast.error('Veuillez indiquer la raison de votre proposition');
            return;
        }

        setIsSubmittingProposal(true);

        try {
            // Use the first slot as the primary appointment date/time
            const primarySlot = validSlots[0];

            // Build proposal reason with all proposed slots
            let proposalReason = proposalFormData.notes;
            if (validSlots.length > 1) {
                proposalReason += '\n\nCréneaux proposés:\n';
                validSlots.forEach((slot, index) => {
                    proposalReason += `${index + 1}. ${slot.date} à ${slot.time}\n`;
                });
            }

            const response = await fetch('/api/care-service/proposals', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    appointment_date: primarySlot.date,
                    appointment_time: primarySlot.time,
                    duration_minutes: formData.duration_minutes,
                    location_type: formData.location_type || 'in_person',
                    client_name: proposalFormData.client_name,
                    client_email: proposalFormData.client_email,
                    client_phone: proposalFormData.client_phone || null,
                    notes: proposalFormData.notes || null,
                    proposal_reason: proposalReason,
                }),
            });

            const data = await response.json();

            if (data.success) {
                toast.success('Proposition envoyée avec succès! Vous recevrez un email de confirmation.');
                cancelProposalMode();
                setActiveTab('dashboard');
            } else {
                toast.error(data.message || 'Erreur lors de l\'envoi de la proposition');
            }
        } catch (error) {
            toast.error('Erreur lors de l\'envoi de la proposition');
        } finally {
            setIsSubmittingProposal(false);
        }
    };

    const getAvailableDates = () => {
        const today = startOfDay(new Date());
        let startDate: Date;
        let endDate: Date;

        if (dateViewType === 'week') {
            startDate = startOfWeek(currentDate, { weekStartsOn: 1 });
            endDate = endOfWeek(currentDate, { weekStartsOn: 1 });
        } else {
            startDate = startOfMonth(currentDate);
            endDate = endOfMonth(currentDate);
        }

        const allDates = eachDayOfInterval({ start: startDate, end: endDate });

        return allDates.filter(date => {
            const isPast = date < today;
            return !isPast;
        });
    };

    // Get all days for month view (including previous/next month days to fill the grid)
    const getMonthDays = () => {
        const monthStart = startOfMonth(currentDate);
        const monthStartWeek = startOfWeek(monthStart, { weekStartsOn: 1 });
        const monthEnd = endOfMonth(currentDate);
        const monthEndWeek = endOfWeek(monthEnd, { weekStartsOn: 1 });

        const days = [];
        let day = monthStartWeek;

        while (day <= monthEndWeek) {
            days.push(day);
            day = addDays(day, 1);
        }

        return days;
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

    const formatDate = (dateString: string) => {
        return format(new Date(dateString), 'EEEE d MMMM yyyy', { locale: fr });
    };

    const formatTime = (timeString: string) => {
        return format(new Date(timeString), 'HH:mm', { locale: fr });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">En attente</Badge>;
            case 'confirmed':
                return <Badge variant="default" className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Confirmé</Badge>;
            case 'completed':
                return <Badge variant="outline" className="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Terminé</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Annulé</Badge>;
            case 'no_show':
                return <Badge variant="destructive" className="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Absent</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const getLocationIcon = (locationType: string) => {
        switch (locationType) {
            case 'zoom':
                return <Video className="h-4 w-4 text-blue-600" />;
            case 'hybrid':
                return <div className="flex space-x-1">
                    <MapPin className="h-3 w-3 text-green-600" />
                    <Video className="h-3 w-3 text-blue-600" />
                </div>;
            default:
                return <MapPin className="h-4 w-4 text-green-600" />;
        }
    };

    const handleStatusUpdate = async (appointmentUuid: string, newStatus: string) => {
        try {
            if (newStatus === 'confirmed') {
                // Use the POST route for confirmation
                await router.post(`/care-service/appointments/${appointmentUuid}/confirm`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success('Rendez-vous confirmé avec succès');
                    },
                    onError: () => {
                        toast.error('Erreur lors de la confirmation du rendez-vous');
                    }
                });
            } else if (newStatus === 'cancelled') {
                // Use the POST route for cancellation
                await router.post(`/care-service/appointments/${appointmentUuid}/cancel`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success('Rendez-vous annulé avec succès');
                    },
                    onError: () => {
                        toast.error('Erreur lors de l\'annulation du rendez-vous');
                    }
                });
            } else {
                // For other status changes, use the generic route (if it exists)
                await router.patch(`/care-service/appointments/${appointmentUuid}`, {
                    status: newStatus
                }, {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success('Statut mis à jour avec succès');
                    },
                    onError: () => {
                        toast.error('Erreur lors de la mise à jour du statut');
                    }
                });
            }
        } catch (error) {
            toast.error('Erreur lors de la mise à jour du statut');
        }
    };

    const filteredAppointments = (status?: string) => {
        if (!status) return appointments.data;
        return appointments.data.filter(appointment => appointment.status === status);
    };

    const upcomingAppointments = appointments.data
        .filter(apt => apt.status !== 'cancelled' && new Date(apt.appointment_date) >= new Date())
        .sort((a, b) => new Date(a.appointment_date).getTime() - new Date(b.appointment_date).getTime())
        .slice(0, 5);

    return (
        <DashboardLayout
            title="Care Services"
            actions={
                canManageAll ? (
                    <Button
                        onClick={() => router.visit('/care-service/appointments/create')}
                        className="bg-blue-600 hover:bg-blue-700 text-white px-2 sm:px-4 py-2 rounded-lg flex items-center shadow-md text-sm"
                    >
                        <PlusIcon className="h-4 w-4 sm:mr-2" />
                        <span className="hidden sm:inline">Nouveau rendez-vous</span>
                    </Button>
                ) : undefined
            }
        >
            <Head title="Care Services - Dashboard" />

            <div className="py-4 sm:py-6">
                <div className="mx-auto px-2 sm:px-6 lg:px-8">
                    <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4 sm:space-y-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <TabsList className="grid w-full grid-cols-2 sm:grid-cols-5 h-auto gap-1 p-1">
                                <TabsTrigger value="dashboard" className="text-xs sm:text-sm py-2">Dashboard</TabsTrigger>
                                <TabsTrigger value="booking" className="text-xs sm:text-sm py-2">Prise de RDV</TabsTrigger>
                                <TabsTrigger value="pending" className="text-xs sm:text-sm py-2">En attente ({stats.pending_appointments})</TabsTrigger>
                                <TabsTrigger value="confirmed" className="text-xs sm:text-sm py-2">Confirmés ({stats.confirmed_appointments})</TabsTrigger>
                                <TabsTrigger value="all" className="text-xs sm:text-sm py-2 col-span-2 sm:col-span-1">Tous ({stats.total_appointments})</TabsTrigger>
                            </TabsList>
                            {permissions?.canViewCareServiceDashboard && (
                                <Button
                                    variant="outline"
                                    onClick={() => router.visit('/care-service/dashboard')}
                                    className="w-full sm:w-auto bg-purple-50 text-purple-700 border-purple-300 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700 dark:hover:bg-purple-900/50"
                                >
                                    <Heart className="h-4 w-4 mr-2" />
                                    Tableau de bord
                                </Button>
                            )}
                        </div>

                        {/* Dashboard Tab */}
                        <TabsContent value="dashboard" className="space-y-6">
                            {/* Statistics Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Total</CardTitle>
                                        <UserGroupIcon className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.total_appointments}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Rendez-vous au total
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">En attente</CardTitle>
                                        <ClockIcon className="h-4 w-4 text-yellow-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-yellow-600">{stats.pending_appointments}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Nécessitent une action
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Cette semaine</CardTitle>
                                        <CalendarIcon className="h-4 w-4 text-blue-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-blue-600">{stats.this_week_appointments}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Rendez-vous programmés
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Terminés</CardTitle>
                                        <CheckCircleIcon className="h-4 w-4 text-green-600" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold text-green-600">{stats.completed_appointments}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Accompagnements réalisés
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Upcoming Appointments */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Prochains rendez-vous</CardTitle>
                                    <CardDescription>
                                        {upcomingAppointments.length > 0
                                            ? `Vos ${upcomingAppointments.length} prochain${upcomingAppointments.length > 1 ? 's' : ''} rendez-vous confirmé${upcomingAppointments.length > 1 ? 's' : ''} ou en attente`
                                            : 'Aucun rendez-vous à venir'}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {upcomingAppointments.length === 0 ? (
                                        <div className="text-center py-8">
                                            <CalendarIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Aucun rendez-vous à venir
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-3 sm:space-y-4">
                                            {upcomingAppointments.map((appointment) => (
                                                <div
                                                    key={appointment.id}
                                                    className="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 sm:p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors gap-3"
                                                >
                                                    <div className="flex items-center space-x-3 sm:space-x-4">
                                                        <div className="flex-shrink-0">
                                                            <div className="h-9 w-9 sm:h-10 sm:w-10 bg-blue-600 rounded-full flex items-center justify-center">
                                                                <span className="text-white font-semibold text-xs sm:text-sm">
                                                                    {appointment.client_name ? appointment.client_name.charAt(0) : 'R'}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <h3 className="font-medium text-gray-900 dark:text-white text-sm sm:text-base truncate">
                                                                {appointment.client_name || 'Rendez-vous interne'}
                                                            </h3>
                                                            <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                                                {formatDate(appointment.appointment_date)} à {formatTime(appointment.appointment_time)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center justify-between sm:justify-end gap-2 sm:gap-3 pl-12 sm:pl-0">
                                                        <div className="flex items-center gap-2">
                                                            {getLocationIcon(appointment.location_type)}
                                                            {getStatusBadge(appointment.status)}
                                                        </div>
                                                        <div className="flex gap-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => router.visit(`/care-service/appointments/${appointment.uuid}`)}
                                                            >
                                                                <EyeIcon className="h-4 w-4" />
                                                            </Button>
                                                            {(permissions?.canEdit ?? true) && appointment.status === 'pending' && (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => router.visit(`/care-service/appointments/${appointment.uuid}/edit`)}
                                                                >
                                                                    <PencilIcon className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Booking Tab */}
                        <TabsContent value="booking" className="space-y-6">
                            <div className="text-center mb-8">
                                <p className="text-lg text-gray-600 dark:text-gray-300">
                                    {proposalMode
                                        ? "Proposez vos créneaux préférés à notre équipe care service"
                                        : "Prenez rendez-vous avec un membre de notre équipe care service pour un accompagnement spirituel personnalisé"
                                    }
                                </p>
                            </div>

                            {/* Proposal Mode Form */}
                            {proposalMode ? (
                                <Card className="shadow-xl">
                                    <CardHeader>
                                        <CardTitle className="flex items-center">
                                            <CalendarDays className="h-6 w-6 mr-2 text-orange-500" />
                                            Proposer vos créneaux
                                        </CardTitle>
                                        <CardDescription>
                                            Indiquez les dates et heures qui vous conviennent. Notre équipe vous recontactera.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {/* Proposed Slots */}
                                        <div>
                                            <Label className="text-base font-medium mb-3 block">
                                                Créneaux proposés *
                                            </Label>
                                            <div className="space-y-3">
                                                {proposalSlots.map((slot, index) => (
                                                    <div key={index} className="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                        <div className="flex-1">
                                                            <Label className="text-sm text-gray-500 mb-1 block">Date</Label>
                                                            <Input
                                                                type="date"
                                                                value={slot.date}
                                                                min={format(new Date(), 'yyyy-MM-dd')}
                                                                onChange={(e) => updateProposalSlot(index, 'date', e.target.value)}
                                                            />
                                                        </div>
                                                        <div className="flex-1">
                                                            <Label className="text-sm text-gray-500 mb-1 block">Heure</Label>
                                                            <Input
                                                                type="time"
                                                                value={slot.time}
                                                                onChange={(e) => updateProposalSlot(index, 'time', e.target.value)}
                                                            />
                                                        </div>
                                                        {proposalSlots.length > 1 && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                className="text-red-500 hover:text-red-700 mt-6"
                                                                onClick={() => removeProposalSlot(index)}
                                                            >
                                                                <XCircleIcon className="h-5 w-5" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className="mt-3"
                                                onClick={addProposalSlot}
                                            >
                                                <PlusIcon className="h-4 w-4 mr-2" />
                                                Ajouter un autre créneau
                                            </Button>
                                        </div>

                                        <Separator />

                                        {/* Duration */}
                                        <div>
                                            <Label className="text-base font-medium">Durée souhaitée</Label>
                                            <Select
                                                value={formData.duration_minutes.toString()}
                                                onValueChange={(value) => handleInputChange('duration_minutes', parseInt(value))}
                                            >
                                                <SelectTrigger className="mt-2">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="30">30 minutes</SelectItem>
                                                    <SelectItem value="45">45 minutes</SelectItem>
                                                    <SelectItem value="60">1 heure</SelectItem>
                                                    <SelectItem value="90">1 heure 30</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <Separator />

                                        {/* Contact Information */}
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <Label htmlFor="proposal_name">Votre nom *</Label>
                                                <Input
                                                    id="proposal_name"
                                                    value={proposalFormData.client_name}
                                                    onChange={(e) => setProposalFormData(prev => ({ ...prev, client_name: e.target.value }))}
                                                    className="mt-2"
                                                    placeholder="Jean Dupont"
                                                />
                                            </div>
                                            <div>
                                                <Label htmlFor="proposal_email">Votre email *</Label>
                                                <Input
                                                    id="proposal_email"
                                                    type="email"
                                                    value={proposalFormData.client_email}
                                                    onChange={(e) => setProposalFormData(prev => ({ ...prev, client_email: e.target.value }))}
                                                    className="mt-2"
                                                    placeholder="email@exemple.com"
                                                />
                                            </div>
                                            <div>
                                                <Label htmlFor="proposal_phone">Votre téléphone</Label>
                                                <Input
                                                    id="proposal_phone"
                                                    type="tel"
                                                    value={proposalFormData.client_phone}
                                                    onChange={(e) => setProposalFormData(prev => ({ ...prev, client_phone: e.target.value }))}
                                                    className="mt-2"
                                                    placeholder="+33 6 12 34 56 78"
                                                />
                                            </div>
                                        </div>

                                        {/* Notes - Required */}
                                        <div>
                                            <Label htmlFor="proposal_notes">Raison de votre proposition *</Label>
                                            <Textarea
                                                id="proposal_notes"
                                                value={proposalFormData.notes}
                                                onChange={(e) => setProposalFormData(prev => ({ ...prev, notes: e.target.value }))}
                                                className="mt-2"
                                                placeholder="Précisez ici le sujet de votre demande ou toute information utile..."
                                                rows={4}
                                                required
                                            />
                                        </div>

                                        {/* Actions */}
                                        <div className="flex justify-between pt-4">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={cancelProposalMode}
                                            >
                                                <ChevronLeft className="h-4 w-4 mr-2" />
                                                Retour
                                            </Button>
                                            <Button
                                                type="button"
                                                onClick={submitProposal}
                                                disabled={isSubmittingProposal}
                                                className="bg-orange-500 hover:bg-orange-600 text-white"
                                            >
                                                {isSubmittingProposal ? (
                                                    <>
                                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                                        Envoi...
                                                    </>
                                                ) : (
                                                    <>
                                                        Envoyer la proposition
                                                        <ChevronRight className="h-4 w-4 ml-2" />
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            ) : (
                            <>
                            {/* Progress Steps */}
                            <div className="flex items-center justify-center mb-8">
                                <div className="flex items-center space-x-4">
                                    <div className={`flex items-center justify-center w-10 h-10 rounded-full border-2 ${
                                        currentStep >= 1 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-300'
                                    }`}>
                                        {currentStep > 1 ? <CheckCircleIcon className="h-6 w-6" /> : '1'}
                                    </div>
                                    <div className={`h-1 w-16 ${currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-300'}`} />
                                    <div className={`flex items-center justify-center w-10 h-10 rounded-full border-2 ${
                                        currentStep >= 2 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-300'
                                    }`}>
                                        {currentStep > 2 ? <CheckCircleIcon className="h-6 w-6" /> : '2'}
                                    </div>
                                    <div className={`h-1 w-16 ${currentStep >= 3 ? 'bg-blue-600' : 'bg-gray-300'}`} />
                                    <div className={`flex items-center justify-center w-10 h-10 rounded-full border-2 ${
                                        currentStep >= 3 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-300'
                                    }`}>
                                        {currentStep > 3 ? <CheckCircleIcon className="h-6 w-6" /> : '3'}
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
                                                <CheckCircleIcon className="h-6 w-6 mr-2" />
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
                                            {/* Pastor Selection - Only shown if user has permission */}
                                            {permissions?.canSelectPastor && (
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

                                                {/* Date Grid - Week View */}
                                                {dateViewType === 'week' && (
                                                    <div className="grid gap-3 grid-cols-2 md:grid-cols-5">
                                                        {getAvailableDates().map((date) => {
                                                            const dateString = format(date, 'yyyy-MM-dd');
                                                            const isSelected = formData.appointment_date === dateString;
                                                            const hasAvailableSlots = availableDates.includes(dateString);

                                                            return (
                                                                <Button
                                                                    key={date.toISOString()}
                                                                    type="button"
                                                                    variant={'outline'}
                                                                    className={`h-auto p-3 flex flex-col items-center transition-all duration-200 ${
                                                                        isSelected
                                                                            ? 'bg-purple-600 dark:bg-purple-600 border-2 border-purple-600 dark:border-purple-600 text-white hover:bg-purple-700 dark:hover:bg-purple-500'
                                                                            : hasAvailableSlots
                                                                                ? 'bg-purple-100 dark:bg-purple-800/50 border-2 border-purple-400 dark:border-purple-500 text-purple-800 dark:text-purple-200 hover:bg-purple-200 dark:hover:bg-purple-700/50'
                                                                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                                                    }`}
                                                                    onClick={() => handleInputChange('appointment_date', dateString)}
                                                                >
                                                                    <span className={`text-sm font-medium ${
                                                                        isSelected
                                                                            ? 'text-white'
                                                                            : hasAvailableSlots
                                                                                ? 'text-purple-800 dark:text-purple-200 font-bold'
                                                                                : 'text-gray-600 dark:text-gray-400'
                                                                    }`}>
                                                                        {format(date, 'EEE', { locale: fr })}
                                                                    </span>
                                                                    <span className={`text-lg font-bold ${
                                                                        isSelected
                                                                            ? 'text-white'
                                                                            : hasAvailableSlots
                                                                                ? 'text-purple-800 dark:text-purple-200'
                                                                                : 'text-gray-900 dark:text-gray-100'
                                                                    }`}>
                                                                        {format(date, 'd', { locale: fr })}
                                                                    </span>
                                                                    <span className={`text-xs ${
                                                                        isSelected
                                                                            ? 'text-white'
                                                                            : hasAvailableSlots
                                                                                ? 'text-purple-700 dark:text-purple-300 font-semibold'
                                                                                : 'text-gray-500 dark:text-gray-400'
                                                                    }`}>
                                                                        {format(date, 'MMM', { locale: fr })}
                                                                    </span>
                                                                </Button>
                                                            );
                                                        })}
                                                    </div>
                                                )}

                                                {/* Calendar View - Month View */}
                                                {dateViewType === 'month' && (
                                                    <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                                                        {/* Day headers */}
                                                        <div className="grid grid-cols-7">
                                                            {['LUN', 'MAR', 'MER', 'JEU', 'VEN', 'SAM', 'DIM'].map(day => (
                                                                <div key={day} className="bg-gray-100 dark:bg-gray-700 p-1 sm:p-3 text-center text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 border-r border-b border-gray-200 dark:border-gray-600 last:border-r-0">
                                                                    <span className="hidden sm:inline">{day}</span>
                                                                    <span className="sm:hidden">{day.charAt(0)}</span>
                                                                </div>
                                                            ))}
                                                        </div>

                                                        {/* Calendar grid */}
                                                        <div className="grid grid-cols-7">
                                                        {/* Calendar days */}
                                                        {getMonthDays().map((day, idx) => {
                                                            const today = startOfDay(new Date());
                                                            const isToday = isSameDay(day, today);
                                                            const isCurrentMonth = isSameMonth(day, currentDate);
                                                            const isPast = day < today;
                                                            const isWeekend = day.getDay() === 0 || day.getDay() === 6;
                                                            const isAvailable = !isPast && isCurrentMonth;
                                                            const isClickable = isCurrentMonth && !isPast; // Allow clicking only future days of current month
                                                            const isSelected = formData.appointment_date === format(day, 'yyyy-MM-dd');
                                                            const hasAvailableSlots = availableDates.includes(format(day, 'yyyy-MM-dd'));

                                                            return (
                                                                <div
                                                                    key={idx}
                                                                    className={`min-h-[48px] sm:min-h-[80px] p-1 sm:p-3 transition-all duration-200 border-r border-b border-gray-100 dark:border-gray-700 ${
                                                                        !isCurrentMonth ? 'opacity-30' : ''
                                                                    } ${
                                                                        isClickable
                                                                            ? `cursor-pointer hover:shadow-sm`
                                                                            : 'cursor-not-allowed'
                                                                    } ${
                                                                        isSelected
                                                                            ? '!bg-purple-600 dark:!bg-purple-600 !border-2 !border-purple-600 dark:!border-purple-600 !shadow-lg !rounded-lg hover:!bg-purple-700 dark:hover:!bg-purple-500'
                                                                            : hasAvailableSlots
                                                                                ? 'bg-purple-100 dark:bg-purple-800/50 border-2 border-purple-400 dark:border-purple-500 shadow-md hover:bg-purple-200 dark:hover:bg-purple-700/50'
                                                                                : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                                                    }`}
                                                                    onClick={() => {
                                                                        if (isClickable) {
                                                                            handleInputChange('appointment_date', format(day, 'yyyy-MM-dd'));
                                                                        }
                                                                    }}
                                                                >
                                                                    <div className={`text-xs sm:text-sm font-medium ${
                                                                        isToday && !isSelected
                                                                            ? 'text-white w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-blue-500 flex items-center justify-center'
                                                                            : isSelected
                                                                                ? '!text-white dark:!text-white !font-bold text-sm sm:!text-lg'
                                                                                : hasAvailableSlots
                                                                                    ? 'text-purple-800 dark:text-purple-200 font-bold'
                                                                                    : isPast || !isCurrentMonth
                                                                                        ? 'text-gray-400 dark:text-gray-600'
                                                                                        : 'text-gray-900 dark:text-gray-100'
                                                                    }`}>
                                                                        {format(day, 'd')}
                                                                    </div>
                                                                </div>
                                                            );
                                                        })}
                                                        </div>
                                                    </div>
                                                )}

                                                {/* No dates available message */}
                                                {((dateViewType === 'week' && getAvailableDates().length === 0) ||
                                                  (dateViewType === 'month' && getMonthDays().filter(day => {
                                                    const today = startOfDay(new Date());
                                                    const isCurrentMonth = isSameMonth(day, currentDate);
                                                    const isPast = day < today;
                                                    return !isPast && isCurrentMonth;
                                                  }).length === 0)) && (
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

                                            {/* Time Slot Selection - For users who CAN select pastor */}
                                            {permissions?.canSelectPastor && formData.pastor_id && formData.appointment_date && (
                                                <div data-time-slots-section>
                                                    <Label className="text-base font-medium">Choisissez un horaire *</Label>
                                                    {isLoadingSlots ? (
                                                        <div className="flex items-center justify-center py-8">
                                                            <Loader2 className="h-6 w-6 animate-spin" />
                                                            <span className="ml-2">Chargement des créneaux...</span>
                                                        </div>
                                                    ) : availableSlots.length > 0 ? (
                                                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 sm:gap-3 mt-2">
                                                            {availableSlots.map((time) => (
                                                                <Button
                                                                    key={time}
                                                                    variant={formData.appointment_time === time ? 'default' : 'outline'}
                                                                    className="h-10 sm:h-12 text-sm sm:text-base"
                                                                    onClick={() => handleInputChange('appointment_time', time)}
                                                                >
                                                                    {time}
                                                                </Button>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <div className="text-center py-8">
                                                            <div className="bg-orange-50 dark:bg-orange-900/30 p-6 rounded-lg border border-orange-200 dark:border-orange-800">
                                                                <Clock className="h-12 w-12 mx-auto mb-3 text-orange-500" />
                                                                <p className="text-gray-700 dark:text-gray-200 font-medium mb-2">
                                                                    Aucun créneau disponible pour cette date.
                                                                </p>
                                                                <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                                                    Souhaitez-vous proposer un ou plusieurs créneaux à notre équipe ?
                                                                </p>
                                                                <Button
                                                                    type="button"
                                                                    onClick={switchToProposalMode}
                                                                    className="bg-orange-500 hover:bg-orange-600 text-white"
                                                                >
                                                                    <CalendarDays className="h-4 w-4 mr-2" />
                                                                    Proposer mes créneaux
                                                                </Button>
                                                            </div>
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

                                            {/* Time Slot Selection - For users who CANNOT select pastor */}
                                            {!permissions?.canSelectPastor && formData.appointment_date && (
                                                <div data-time-slots-section>
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
                                                                    className="h-12"
                                                                    onClick={() => {
                                                                        // Set both time and pastor_id when slot is clicked
                                                                        setFormData(prev => ({
                                                                            ...prev,
                                                                            appointment_time: slot.time,
                                                                            pastor_id: slot.pastor_id.toString()
                                                                        }));
                                                                    }}
                                                                >
                                                                    {slot.time}
                                                                </Button>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <div className="text-center py-8">
                                                            <div className="bg-orange-50 dark:bg-orange-900/30 p-6 rounded-lg border border-orange-200 dark:border-orange-800">
                                                                <Clock className="h-12 w-12 mx-auto mb-3 text-orange-500" />
                                                                <p className="text-gray-700 dark:text-gray-200 font-medium mb-2">
                                                                    Aucun créneau disponible pour cette date.
                                                                </p>
                                                                <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                                                    Souhaitez-vous proposer un ou plusieurs créneaux à notre équipe ?
                                                                </p>
                                                                <Button
                                                                    type="button"
                                                                    onClick={switchToProposalMode}
                                                                    className="bg-orange-500 hover:bg-orange-600 text-white"
                                                                >
                                                                    <CalendarDays className="h-4 w-4 mr-2" />
                                                                    Proposer mes créneaux
                                                                </Button>
                                                            </div>
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

                                            {/* Meeting Type - Display Only */}
                                            <div>
                                                <Label className="text-base font-medium">Type de rendez-vous</Label>
                                                <div className="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border">
                                                    <div className="flex items-center">
                                                        {formData.location_type === 'in_person' && (
                                                            <>
                                                                <MapPin className="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" />
                                                                <span className="text-gray-900 dark:text-gray-100 font-medium">
                                                                    En présentiel à l'église
                                                                </span>
                                                            </>
                                                        )}
                                                        {formData.location_type === 'zoom' && (
                                                            <>
                                                                <Video className="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" />
                                                                <span className="text-gray-900 dark:text-gray-100 font-medium">
                                                                    Visioconférence (Zoom)
                                                                </span>
                                                            </>
                                                        )}
                                                        {formData.location_type === 'hybrid' && (
                                                            <>
                                                                <Users className="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" />
                                                                <span className="text-gray-900 dark:text-gray-100 font-medium">
                                                                    Hybride (au choix du pasteur)
                                                                </span>
                                                            </>
                                                        )}
                                                    </div>
                                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                        Type défini par les disponibilités du pasteur
                                                    </p>
                                                </div>
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
                                                    {permissions?.canSelectPastor && (
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
                                                            <CheckCircleIcon className="h-4 w-4 mr-2" />
                                                            Confirmer le rendez-vous
                                                        </>
                                                    )}
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            </>
                            )}
                        </TabsContent>

                        {/* Pending Appointments Tab */}
                        <TabsContent value="pending" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Rendez-vous en attente de confirmation</CardTitle>
                                    <CardDescription>
                                        Ces rendez-vous nécessitent votre attention
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {filteredAppointments('pending').length === 0 ? (
                                        <div className="text-center py-8">
                                            <CheckCircleIcon className="h-12 w-12 text-green-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Aucun rendez-vous en attente
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {filteredAppointments('pending').map((appointment) => (
                                                <AppointmentCard
                                                    key={appointment.id}
                                                    appointment={appointment}
                                                    onStatusUpdate={handleStatusUpdate}
                                                    showActions={true}
                                                    permissions={permissions}
                                                    auth={auth}
                                                />
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Confirmed Appointments Tab */}
                        <TabsContent value="confirmed" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Rendez-vous confirmés</CardTitle>
                                    <CardDescription>
                                        Rendez-vous confirmés et à venir
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {filteredAppointments('confirmed').length === 0 ? (
                                        <div className="text-center py-8">
                                            <CalendarIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400">
                                                Aucun rendez-vous confirmé
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {filteredAppointments('confirmed').map((appointment) => (
                                                <AppointmentCard
                                                    key={appointment.id}
                                                    appointment={appointment}
                                                    onStatusUpdate={handleStatusUpdate}
                                                    showActions={true}
                                                    permissions={permissions}
                                                    auth={auth}
                                                />
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* All Appointments Tab */}
                        <TabsContent value="all" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Tous les rendez-vous</CardTitle>
                                    <CardDescription>
                                        Historique complet de vos rendez-vous de care service
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {appointments.data.length === 0 ? (
                                        <div className="text-center py-8">
                                            <UserGroupIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-600 dark:text-gray-400 mb-4">
                                                Aucun rendez-vous enregistré
                                            </p>
                                            <Button
                                                onClick={() => router.visit('/care-service/appointments/create')}
                                                className="bg-blue-600 hover:bg-blue-700 text-white"
                                            >
                                                <PlusIcon className="h-4 w-4 mr-2" />
                                                Créer un rendez-vous
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {appointments.data.map((appointment) => (
                                                <AppointmentCard
                                                    key={appointment.id}
                                                    appointment={appointment}
                                                    onStatusUpdate={handleStatusUpdate}
                                                    showActions={true}
                                                    permissions={permissions}
                                                    auth={auth}
                                                />
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </DashboardLayout>
    );
}

// Reusable Appointment Card Component
interface AppointmentCardProps {
    appointment: CareServiceAppointment;
    onStatusUpdate: (uuid: string, status: string) => void;
    showActions?: boolean;
    permissions?: {
        canEdit: boolean;
    };
    auth?: {
        user: {
            id: number;
            first_name: string;
            last_name: string;
            email: string;
            roles?: Array<{ name: string }>;
            permissions?: Array<{ name: string }>;
        };
    };
}

function AppointmentCard({ appointment, onStatusUpdate, showActions = false, permissions, auth }: AppointmentCardProps) {
    const isPastor = auth?.user && appointment.pastor && auth.user.id === appointment.pastor.id;
    const isAdminOrSuperAdmin = auth?.user && (
        auth.user.roles?.some((role: any) => ['admin', 'SuperAdmin'].includes(role.name)) ||
        auth.user.permissions?.some((permission: any) => permission.name === 'manage care service')
    );
    const canConfirm = showActions && (isPastor || isAdminOrSuperAdmin);

    const formatDate = (dateString: string) => {
        return format(new Date(dateString), 'EEEE d MMMM yyyy', { locale: fr });
    };

    const formatTime = (timeString: string) => {
        return format(new Date(timeString), 'HH:mm', { locale: fr });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">En attente</Badge>;
            case 'confirmed':
                return <Badge variant="default" className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Confirmé</Badge>;
            case 'completed':
                return <Badge variant="outline" className="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Terminé</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Annulé</Badge>;
            case 'no_show':
                return <Badge variant="destructive" className="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Absent</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const getLocationIcon = (locationType: string) => {
        switch (locationType) {
            case 'zoom':
                return <Video className="h-4 w-4 text-blue-600" />;
            case 'hybrid':
                return <div className="flex space-x-1">
                    <MapPin className="h-3 w-3 text-green-600" />
                    <Video className="h-3 w-3 text-blue-600" />
                </div>;
            default:
                return <MapPin className="h-4 w-4 text-green-600" />;
        }
    };

    return (
        <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <div className="flex items-start justify-between mb-4">
                <div className="flex items-center space-x-4">
                    <div className="flex-shrink-0">
                        <div className="h-12 w-12 bg-blue-600 rounded-full flex items-center justify-center">
                            <span className="text-white font-semibold">
                                {appointment.client_name ? appointment.client_name.charAt(0) : 'R'}
                            </span>
                        </div>
                    </div>
                    <div>
                        <h3 className="font-semibold text-gray-900 dark:text-white text-lg">
                            {appointment.client_name || 'Rendez-vous interne'}
                        </h3>
                        <div className="flex items-center space-x-4 mt-1">
                            {appointment.client_email && (
                                <p className="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                    <Mail className="h-4 w-4 mr-1" />
                                    {appointment.client_email}
                                </p>
                            )}
                            {appointment.client_phone && (
                                <p className="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                    <Phone className="h-4 w-4 mr-1" />
                                    {appointment.client_phone}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
                <div className="flex items-center space-x-2">
                    {getLocationIcon(appointment.location_type)}
                    {getStatusBadge(appointment.status)}
                </div>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Date</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {formatDate(appointment.appointment_date)}
                    </p>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Heure</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {formatTime(appointment.appointment_time)} ({appointment.duration_minutes}min)
                    </p>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Type</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {appointment.location_type === 'zoom' ? 'Visioconférence' :
                         appointment.location_type === 'hybrid' ? 'Hybride' : 'En présentiel'}
                    </p>
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Créé le</p>
                    <p className="text-sm text-gray-900 dark:text-white">
                        {format(new Date(appointment.created_at), 'd/M/yyyy', { locale: fr })}
                    </p>
                </div>
            </div>

            {appointment.notes && (
                <div className="mb-4">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</p>
                    <p className="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 p-2 rounded">
                        {appointment.notes}
                    </p>
                </div>
            )}

            <div className="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <div className="flex space-x-2">
                    {showActions && appointment.status === 'pending' && (
                        <>
                            {canConfirm && (
                                <Button
                                    size="sm"
                                    onClick={() => onStatusUpdate(appointment.uuid, 'confirmed')}
                                    className="bg-green-600 hover:bg-green-700 text-white"
                                    disabled={!appointment.can_be_confirmed}
                                >
                                    <CheckCircleIcon className="h-4 w-4 mr-1" />
                                    Confirmer
                                </Button>
                            )}
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => onStatusUpdate(appointment.uuid, 'cancelled')}
                                className="text-red-600 border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                            >
                                <XCircleIcon className="h-4 w-4 mr-1" />
                                Annuler
                            </Button>
                        </>
                    )}

                    {canConfirm && appointment.status === 'confirmed' && (
                        <>
                            <Button
                                size="sm"
                                onClick={() => onStatusUpdate(appointment.uuid, 'completed')}
                                className="bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                <CheckCircleIcon className="h-4 w-4 mr-1" />
                                Marquer terminé
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => onStatusUpdate(appointment.uuid, 'no_show')}
                                className="text-orange-600 border-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20"
                            >
                                <XCircleIcon className="h-4 w-4 mr-1" />
                                Absent
                            </Button>
                        </>
                    )}
                </div>

                <div className="flex space-x-2">
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => router.visit(`/care-service/appointments/${appointment.uuid}`)}
                    >
                        <EyeIcon className="h-4 w-4 mr-1" />
                        Voir
                    </Button>
                    {(permissions?.canEdit ?? true) && appointment.status === 'pending' && (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => router.visit(`/care-service/appointments/${appointment.uuid}/edit`)}
                        >
                            <PencilIcon className="h-4 w-4 mr-1" />
                            Modifier
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}