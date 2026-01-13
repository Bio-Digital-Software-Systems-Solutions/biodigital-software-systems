import React, { useState, useMemo } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    CalendarDaysIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClockIcon,
    MapPinIcon,
    UserGroupIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
    UsersIcon,
    BellIcon,
} from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Checkbox } from '@/Components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Calendar } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import UserMultiSelect, { SimpleUser } from '@/Components/UserMultiSelect';
import type { Appointment, AppointmentStatus, AppointmentType } from '@/Types/appointment';
import { toast } from 'sonner';
import axios from 'axios';
import {
    format,
    startOfMonth,
    endOfMonth,
    startOfWeek,
    endOfWeek,
    addDays,
    addMonths,
    subMonths,
    isSameMonth,
    isSameDay,
    isToday,
    parseISO,
    setHours,
    setMinutes,
} from 'date-fns';
import { fr } from 'date-fns/locale';

interface DepartmentMeeting {
    uuid: string;
    is_mandatory: boolean;
    notify_all_members: boolean;
    notes: string | null;
    notified_at: string | null;
    created_at: string;
    creator: {
        id: number;
        uuid: string;
        name: string;
    } | null;
    appointment: Appointment | null;
}

interface DepartmentCalendarProps {
    appointments: Appointment[];
    meetings?: DepartmentMeeting[];
    departmentId: number;
    departmentUuid: string;
    canManage?: boolean;
    departmentUsers?: Array<{ id: number; name: string; email: string }>;
}

const statusColors: Record<AppointmentStatus, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    completed: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
};

const statusLabels: Record<AppointmentStatus, string> = {
    pending: 'En attente',
    confirmed: 'Confirmé',
    cancelled: 'Annulé',
    completed: 'Terminé',
};

const typeLabels: Record<AppointmentType, string> = {
    individual: 'Individuel',
    group: 'Groupe',
    consultation: 'Consultation',
    meeting: 'Réunion',
};

const typeColors: Record<AppointmentType, string> = {
    individual: 'bg-blue-500',
    group: 'bg-purple-500',
    consultation: 'bg-orange-500',
    meeting: 'bg-teal-500',
};

interface MeetingFormData {
    title: string;
    description: string;
    start_datetime: string;
    end_datetime: string;
    location: string;
    type: AppointmentType;
    notify_all_members: boolean;
    is_mandatory: boolean;
    notes: string;
    participant_ids: number[];
}

// Time options for the selects
const timeOptions = Array.from({ length: 48 }, (_, i) => {
    const hours = Math.floor(i / 2);
    const minutes = i % 2 === 0 ? '00' : '30';
    return `${hours.toString().padStart(2, '0')}:${minutes}`;
});

export default function DepartmentCalendar({
    appointments,
    meetings = [],
    departmentId,
    departmentUuid,
    canManage = false,
    departmentUsers = [],
}: DepartmentCalendarProps) {
    // Get the authenticated user to exclude from participant selection
    const { auth } = usePage().props as any;
    const authUserId = auth?.user?.id;

    const [currentMonth, setCurrentMonth] = useState(new Date());
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [view, setView] = useState<'calendar' | 'list'>('calendar');
    const [activeTab, setActiveTab] = useState<'appointments' | 'meetings'>('meetings');
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingMeeting, setEditingMeeting] = useState<DepartmentMeeting | null>(null);
    const [initialParticipants, setInitialParticipants] = useState<SimpleUser[]>([]);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [startDate, setStartDate] = useState<Date | undefined>(undefined);
    const [endDate, setEndDate] = useState<Date | undefined>(undefined);
    const [startTime, setStartTime] = useState('09:00');
    const [endTime, setEndTime] = useState('10:00');
    const [startDateOpen, setStartDateOpen] = useState(false);
    const [endDateOpen, setEndDateOpen] = useState(false);
    const [formData, setFormData] = useState<MeetingFormData>({
        title: '',
        description: '',
        start_datetime: '',
        end_datetime: '',
        location: '',
        type: 'meeting',
        notify_all_members: true,
        is_mandatory: false,
        notes: '',
        participant_ids: [],
    });

    // Get all items (appointments and meetings)
    const allItems = useMemo(() => {
        if (activeTab === 'appointments') {
            return appointments;
        }
        // For meetings tab, return appointments from meetings
        return meetings
            .filter((m) => m.appointment)
            .map((m) => ({
                ...m.appointment!,
                _meeting: m,
            }));
    }, [appointments, meetings, activeTab]);

    // Group items by date
    const itemsByDate = useMemo(() => {
        const grouped: Record<string, (Appointment & { _meeting?: DepartmentMeeting })[]> = {};
        allItems.forEach((item) => {
            const dateKey = format(parseISO(item.start_datetime), 'yyyy-MM-dd');
            if (!grouped[dateKey]) {
                grouped[dateKey] = [];
            }
            grouped[dateKey].push(item as Appointment & { _meeting?: DepartmentMeeting });
        });
        return grouped;
    }, [allItems]);

    // Generate calendar days
    const calendarDays = useMemo(() => {
        const monthStart = startOfMonth(currentMonth);
        const monthEnd = endOfMonth(monthStart);
        const startDate = startOfWeek(monthStart, { locale: fr });
        const endDate = endOfWeek(monthEnd, { locale: fr });

        const days: Date[] = [];
        let day = startDate;

        while (day <= endDate) {
            days.push(day);
            day = addDays(day, 1);
        }

        return days;
    }, [currentMonth]);

    // Filter items for selected date
    const selectedDateItems = useMemo(() => {
        if (!selectedDate) return [];
        const dateKey = format(selectedDate, 'yyyy-MM-dd');
        return itemsByDate[dateKey] || [];
    }, [selectedDate, itemsByDate]);

    // Get upcoming items (next 7 days)
    const upcomingItems = useMemo(() => {
        const now = new Date();
        const weekFromNow = addDays(now, 7);
        return allItems
            .filter((item) => {
                const itemDate = parseISO(item.start_datetime);
                return itemDate >= now && itemDate <= weekFromNow;
            })
            .sort((a, b) => parseISO(a.start_datetime).getTime() - parseISO(b.start_datetime).getTime())
            .slice(0, 5);
    }, [allItems]);

    const handlePrevMonth = () => setCurrentMonth(subMonths(currentMonth, 1));
    const handleNextMonth = () => setCurrentMonth(addMonths(currentMonth, 1));
    const handleToday = () => {
        setCurrentMonth(new Date());
        setSelectedDate(new Date());
    };

    const handleCreateMeeting = async () => {
        if (!formData.title || !startDate || !endDate) {
            toast.error('Veuillez remplir tous les champs obligatoires');
            return;
        }

        // Combine date and time
        const [startHours, startMinutes] = startTime.split(':').map(Number);
        const [endHours, endMinutes] = endTime.split(':').map(Number);

        const startDateTime = setMinutes(setHours(startDate, startHours), startMinutes);
        const endDateTime = setMinutes(setHours(endDate, endHours), endMinutes);

        const dataToSubmit = {
            ...formData,
            start_datetime: format(startDateTime, "yyyy-MM-dd'T'HH:mm"),
            end_datetime: format(endDateTime, "yyyy-MM-dd'T'HH:mm"),
        };

        setIsSubmitting(true);
        try {
            const response = await axios.post(`/api/departments/${departmentUuid}/meetings`, dataToSubmit);
            if (response.data.success) {
                toast.success('Réunion créée avec succès');
                setIsCreateDialogOpen(false);
                setFormData({
                    title: '',
                    description: '',
                    start_datetime: '',
                    end_datetime: '',
                    location: '',
                    type: 'meeting',
                    notify_all_members: true,
                    is_mandatory: false,
                    notes: '',
                    participant_ids: [],
                });
                setStartDate(undefined);
                setEndDate(undefined);
                setStartTime('09:00');
                setEndTime('10:00');
                // Refresh the page to get updated data
                router.reload();
            }
        } catch (error: any) {
            const message = error.response?.data?.message || 'Erreur lors de la création de la réunion';
            toast.error(message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleEditMeeting = (meeting: DepartmentMeeting) => {
        if (!meeting.appointment) return;

        const appointment = meeting.appointment;
        const startDateTime = parseISO(appointment.start_datetime);
        const endDateTime = parseISO(appointment.end_datetime);

        // Convert participants to SimpleUser format for UserMultiSelect
        const participantsAsSimpleUsers: SimpleUser[] = (appointment.participants || []).map(p => {
            // Handle both formats: { name: "..." } and { first_name: "...", last_name: "..." }
            const nameParts = (p as any).name?.split(' ') || [];
            return {
                id: p.id,
                first_name: (p as any).first_name || nameParts[0] || '',
                last_name: (p as any).last_name || nameParts.slice(1).join(' ') || '',
                email: p.email,
            };
        });

        // Set initial participants for the UserMultiSelect
        setInitialParticipants(participantsAsSimpleUsers);

        // Set form data from existing meeting
        setFormData({
            title: appointment.title,
            description: appointment.description || '',
            start_datetime: appointment.start_datetime,
            end_datetime: appointment.end_datetime,
            location: appointment.location || '',
            type: appointment.type,
            notify_all_members: meeting.notify_all_members,
            is_mandatory: meeting.is_mandatory,
            notes: meeting.notes || '',
            participant_ids: appointment.participants?.map(p => p.id) || [],
        });

        // Set date and time pickers
        setStartDate(startDateTime);
        setEndDate(endDateTime);
        setStartTime(format(startDateTime, 'HH:mm'));
        setEndTime(format(endDateTime, 'HH:mm'));

        setEditingMeeting(meeting);
        setIsEditDialogOpen(true);
    };

    const handleUpdateMeeting = async () => {
        if (!editingMeeting || !formData.title || !startDate || !endDate) {
            toast.error('Veuillez remplir tous les champs obligatoires');
            return;
        }

        // Combine date and time
        const [startHours, startMinutes] = startTime.split(':').map(Number);
        const [endHours, endMinutes] = endTime.split(':').map(Number);

        const startDateTime = setMinutes(setHours(startDate, startHours), startMinutes);
        const endDateTime = setMinutes(setHours(endDate, endHours), endMinutes);

        const dataToSubmit = {
            ...formData,
            start_datetime: format(startDateTime, "yyyy-MM-dd'T'HH:mm"),
            end_datetime: format(endDateTime, "yyyy-MM-dd'T'HH:mm"),
        };

        setIsSubmitting(true);
        try {
            const response = await axios.patch(`/api/departments/${departmentUuid}/meetings/${editingMeeting.uuid}`, dataToSubmit);
            if (response.data.success) {
                toast.success('Réunion mise à jour avec succès');
                setIsEditDialogOpen(false);
                setEditingMeeting(null);
                resetFormData();
                // Refresh the page to get updated data
                router.reload();
            }
        } catch (error: any) {
            const message = error.response?.data?.message || 'Erreur lors de la mise à jour de la réunion';
            toast.error(message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const resetFormData = () => {
        setFormData({
            title: '',
            description: '',
            start_datetime: '',
            end_datetime: '',
            location: '',
            type: 'meeting',
            notify_all_members: true,
            is_mandatory: false,
            notes: '',
            participant_ids: [],
        });
        setStartDate(undefined);
        setEndDate(undefined);
        setStartTime('09:00');
        setEndTime('10:00');
        setInitialParticipants([]);
    };

    const handleCloseEditDialog = () => {
        setIsEditDialogOpen(false);
        setEditingMeeting(null);
        resetFormData();
    };

    const renderDayCell = (day: Date) => {
        const dateKey = format(day, 'yyyy-MM-dd');
        const dayItems = itemsByDate[dateKey] || [];
        const isCurrentMonth = isSameMonth(day, currentMonth);
        const isSelected = selectedDate && isSameDay(day, selectedDate);
        const isTodayDate = isToday(day);

        return (
            <div
                key={dateKey}
                onClick={() => setSelectedDate(day)}
                className={`
                    min-h-[80px] p-1 border border-gray-200 dark:border-gray-700 cursor-pointer
                    transition-colors hover:bg-gray-50 dark:hover:bg-gray-800
                    ${!isCurrentMonth ? 'bg-gray-50 dark:bg-gray-900/50' : 'bg-white dark:bg-gray-900'}
                    ${isSelected ? 'ring-2 ring-primary' : ''}
                `}
            >
                <div className={`
                    text-sm font-medium mb-1 w-7 h-7 flex items-center justify-center rounded-full
                    ${!isCurrentMonth ? 'text-gray-400' : 'text-gray-900 dark:text-white'}
                    ${isTodayDate ? 'bg-primary text-white' : ''}
                `}>
                    {format(day, 'd')}
                </div>
                <div className="space-y-1">
                    {dayItems.slice(0, 2).map((item) => (
                        <div
                            key={item.uuid}
                            className={`
                                text-xs px-1 py-0.5 rounded truncate
                                ${typeColors[item.type]} text-white
                            `}
                            title={item.title}
                        >
                            {format(parseISO(item.start_datetime), 'HH:mm')} {item.title}
                        </div>
                    ))}
                    {dayItems.length > 2 && (
                        <div className="text-xs text-gray-500 dark:text-gray-400 px-1">
                            +{dayItems.length - 2} autres
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const renderItemCard = (item: Appointment & { _meeting?: DepartmentMeeting }) => {
        const meeting = item._meeting;

        return (
            <div
                key={item.uuid}
                className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
                <div className={`w-1 h-full min-h-[60px] rounded-full ${typeColors[item.type]}`} />
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1 flex-wrap">
                        <span className="font-medium text-gray-900 dark:text-white truncate">
                            {item.title}
                        </span>
                        <Badge className={statusColors[item.status]}>
                            {statusLabels[item.status]}
                        </Badge>
                        {meeting?.is_mandatory && (
                            <Badge variant="destructive" className="text-xs">
                                Obligatoire
                            </Badge>
                        )}
                        {meeting?.notify_all_members && (
                            <Badge variant="secondary" className="text-xs">
                                <BellIcon className="h-3 w-3 mr-1" />
                                Tous notifiés
                            </Badge>
                        )}
                    </div>
                    <div className="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                        <div className="flex items-center gap-1">
                            <ClockIcon className="h-4 w-4" />
                            {item.formatted_time_range}
                        </div>
                        {item.location && (
                            <div className="flex items-center gap-1">
                                <MapPinIcon className="h-4 w-4" />
                                {item.location}
                            </div>
                        )}
                        <div className="flex items-center gap-1">
                            <UserGroupIcon className="h-4 w-4" />
                            {item.participants_count} participant(s)
                        </div>
                    </div>
                    {item.description && (
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                            {item.description}
                        </p>
                    )}
                    {meeting?.notes && (
                        <p className="text-sm text-gray-600 dark:text-gray-300 mt-1 italic">
                            Note: {meeting.notes}
                        </p>
                    )}
                    {meeting?.creator && (
                        <p className="text-xs text-gray-400 mt-1">
                            Créé par {meeting.creator.name}
                        </p>
                    )}
                </div>
                <div className="flex flex-col gap-1">
                    {canManage && meeting && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleEditMeeting(meeting)}
                            title="Modifier la réunion"
                        >
                            <PencilIcon className="h-4 w-4" />
                        </Button>
                    )}
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/appointments/${item.uuid}`} title="Voir les détails">
                            <EyeIcon className="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </div>
        );
    };

    return (
        <div className="space-y-4">
            {/* Tabs for switching between appointments and meetings */}
            <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as 'appointments' | 'meetings')}>
                <div className="flex items-center justify-between flex-wrap gap-2">
                    <TabsList>
                        <TabsTrigger value="meetings" className="flex items-center gap-2">
                            <UsersIcon className="h-4 w-4" />
                            Réunions ({meetings.length})
                        </TabsTrigger>
                        <TabsTrigger value="appointments" className="flex items-center gap-2">
                            <CalendarDaysIcon className="h-4 w-4" />
                            Rendez-vous ({appointments.length})
                        </TabsTrigger>
                    </TabsList>
                    <div className="flex items-center gap-2">
                        {canManage && activeTab === 'meetings' && (
                            <>
                                <Button onClick={() => setIsCreateDialogOpen(true)}>
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Nouvelle Réunion
                                </Button>
                                <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
                                <DialogContent className="sm:max-w-[600px] max-h-[90vh] overflow-y-auto px-6">
                                    <DialogHeader>
                                        <DialogTitle>Créer une réunion de département</DialogTitle>
                                        <DialogDescription>
                                            Planifiez une nouvelle réunion pour ce département. Les membres seront notifiés par email.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="grid gap-4 py-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="title">Titre *</Label>
                                            <Input
                                                id="title"
                                                placeholder="Ex: Réunion hebdomadaire"
                                                value={formData.title}
                                                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="description">Description</Label>
                                            <Textarea
                                                id="description"
                                                placeholder="Description de la réunion..."
                                                value={formData.description}
                                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                            />
                                        </div>
                                        {/* Date/Time pickers */}
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="grid gap-2">
                                                <Label>Date de début *</Label>
                                                <Popover open={startDateOpen} onOpenChange={setStartDateOpen}>
                                                    <PopoverTrigger asChild>
                                                        <Button
                                                            variant="outline"
                                                            className={`w-full justify-start text-left font-normal ${!startDate ? 'text-muted-foreground' : ''}`}
                                                        >
                                                            <CalendarDaysIcon className="mr-2 h-4 w-4" />
                                                            {startDate ? format(startDate, 'dd/MM/yyyy', { locale: fr }) : 'Sélectionner'}
                                                        </Button>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-auto p-0" align="start">
                                                        <Calendar
                                                            mode="single"
                                                            selected={startDate}
                                                            onSelect={(date) => {
                                                                setStartDate(date);
                                                                if (!endDate && date) setEndDate(date);
                                                                setStartDateOpen(false);
                                                            }}
                                                            locale={fr}
                                                        />
                                                    </PopoverContent>
                                                </Popover>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Heure de début *</Label>
                                                <Select value={startTime} onValueChange={setStartTime}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Heure" />
                                                    </SelectTrigger>
                                                    <SelectContent className="max-h-[200px]">
                                                        {timeOptions.map((time) => (
                                                            <SelectItem key={time} value={time}>
                                                                {time}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="grid gap-2">
                                                <Label>Date de fin *</Label>
                                                <Popover open={endDateOpen} onOpenChange={setEndDateOpen}>
                                                    <PopoverTrigger asChild>
                                                        <Button
                                                            variant="outline"
                                                            className={`w-full justify-start text-left font-normal ${!endDate ? 'text-muted-foreground' : ''}`}
                                                        >
                                                            <CalendarDaysIcon className="mr-2 h-4 w-4" />
                                                            {endDate ? format(endDate, 'dd/MM/yyyy', { locale: fr }) : 'Sélectionner'}
                                                        </Button>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-auto p-0" align="start">
                                                        <Calendar
                                                            mode="single"
                                                            selected={endDate}
                                                            onSelect={(date) => {
                                                                setEndDate(date);
                                                                setEndDateOpen(false);
                                                            }}
                                                            locale={fr}
                                                            disabled={(date) => startDate ? date < startDate : false}
                                                        />
                                                    </PopoverContent>
                                                </Popover>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Heure de fin *</Label>
                                                <Select value={endTime} onValueChange={setEndTime}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Heure" />
                                                    </SelectTrigger>
                                                    <SelectContent className="max-h-[200px]">
                                                        {timeOptions.map((time) => (
                                                            <SelectItem key={time} value={time}>
                                                                {time}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="location">Lieu</Label>
                                            <Input
                                                id="location"
                                                placeholder="Ex: Salle de conférence A"
                                                value={formData.location}
                                                onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="type">Type</Label>
                                            <Select
                                                value={formData.type}
                                                onValueChange={(v) => setFormData({ ...formData, type: v as AppointmentType })}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Type de réunion" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="meeting">Réunion</SelectItem>
                                                    <SelectItem value="group">Groupe</SelectItem>
                                                    <SelectItem value="consultation">Consultation</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="notes">Notes internes</Label>
                                            <Textarea
                                                id="notes"
                                                placeholder="Notes visibles uniquement par les organisateurs..."
                                                value={formData.notes}
                                                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                            />
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id="notify_all_members"
                                                checked={formData.notify_all_members}
                                                onCheckedChange={(checked) =>
                                                    setFormData({ ...formData, notify_all_members: checked as boolean, participant_ids: checked ? [] : formData.participant_ids })
                                                }
                                            />
                                            <Label htmlFor="notify_all_members" className="text-sm font-normal cursor-pointer">
                                                Notifier tous les membres du département
                                            </Label>
                                        </div>
                                        {/* Conditional User selection when not notifying all members */}
                                        {!formData.notify_all_members && (
                                            <div className="grid gap-2">
                                                <Label>Sélectionner les participants</Label>
                                                <UserMultiSelect
                                                    selectedUserIds={formData.participant_ids}
                                                    onChange={(ids) => setFormData({ ...formData, participant_ids: ids })}
                                                    placeholder="Rechercher des membres..."
                                                    maxHeight="200px"
                                                    excludeUserIds={authUserId ? [authUserId] : []}
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    Vous serez automatiquement ajouté comme organisateur. Seuls les participants sélectionnés seront notifiés.
                                                </p>
                                            </div>
                                        )}
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id="is_mandatory"
                                                checked={formData.is_mandatory}
                                                onCheckedChange={(checked) =>
                                                    setFormData({ ...formData, is_mandatory: checked as boolean })
                                                }
                                            />
                                            <Label htmlFor="is_mandatory" className="text-sm font-normal cursor-pointer">
                                                Réunion obligatoire
                                            </Label>
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <Button variant="outline" onClick={() => setIsCreateDialogOpen(false)}>
                                            Annuler
                                        </Button>
                                        <Button onClick={handleCreateMeeting} disabled={isSubmitting}>
                                            {isSubmitting ? 'Création...' : 'Créer la réunion'}
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                            {/* Edit Meeting Dialog */}
                            <Dialog open={isEditDialogOpen} onOpenChange={(open) => !open && handleCloseEditDialog()}>
                                <DialogContent className="sm:max-w-[600px] max-h-[90vh] overflow-y-auto px-6">
                                    <DialogHeader>
                                        <DialogTitle>Modifier la réunion</DialogTitle>
                                        <DialogDescription>
                                            Modifiez les informations de cette réunion.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="grid gap-4 py-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="edit-title">Titre *</Label>
                                            <Input
                                                id="edit-title"
                                                placeholder="Ex: Réunion hebdomadaire"
                                                value={formData.title}
                                                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="edit-description">Description</Label>
                                            <Textarea
                                                id="edit-description"
                                                placeholder="Description de la réunion..."
                                                value={formData.description}
                                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                            />
                                        </div>
                                        {/* Date/Time pickers */}
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="grid gap-2">
                                                <Label>Date de début *</Label>
                                                <Popover open={startDateOpen} onOpenChange={setStartDateOpen}>
                                                    <PopoverTrigger asChild>
                                                        <Button
                                                            variant="outline"
                                                            className={`w-full justify-start text-left font-normal ${!startDate ? 'text-muted-foreground' : ''}`}
                                                        >
                                                            <CalendarDaysIcon className="mr-2 h-4 w-4" />
                                                            {startDate ? format(startDate, 'dd/MM/yyyy', { locale: fr }) : 'Sélectionner'}
                                                        </Button>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-auto p-0" align="start">
                                                        <Calendar
                                                            mode="single"
                                                            selected={startDate}
                                                            onSelect={(date) => {
                                                                setStartDate(date);
                                                                if (!endDate && date) setEndDate(date);
                                                                setStartDateOpen(false);
                                                            }}
                                                            locale={fr}
                                                        />
                                                    </PopoverContent>
                                                </Popover>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Heure de début *</Label>
                                                <Select value={startTime} onValueChange={setStartTime}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Heure" />
                                                    </SelectTrigger>
                                                    <SelectContent className="max-h-[200px]">
                                                        {timeOptions.map((time) => (
                                                            <SelectItem key={time} value={time}>
                                                                {time}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="grid gap-2">
                                                <Label>Date de fin *</Label>
                                                <Popover open={endDateOpen} onOpenChange={setEndDateOpen}>
                                                    <PopoverTrigger asChild>
                                                        <Button
                                                            variant="outline"
                                                            className={`w-full justify-start text-left font-normal ${!endDate ? 'text-muted-foreground' : ''}`}
                                                        >
                                                            <CalendarDaysIcon className="mr-2 h-4 w-4" />
                                                            {endDate ? format(endDate, 'dd/MM/yyyy', { locale: fr }) : 'Sélectionner'}
                                                        </Button>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-auto p-0" align="start">
                                                        <Calendar
                                                            mode="single"
                                                            selected={endDate}
                                                            onSelect={(date) => {
                                                                setEndDate(date);
                                                                setEndDateOpen(false);
                                                            }}
                                                            locale={fr}
                                                            disabled={(date) => startDate ? date < startDate : false}
                                                        />
                                                    </PopoverContent>
                                                </Popover>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Heure de fin *</Label>
                                                <Select value={endTime} onValueChange={setEndTime}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Heure" />
                                                    </SelectTrigger>
                                                    <SelectContent className="max-h-[200px]">
                                                        {timeOptions.map((time) => (
                                                            <SelectItem key={time} value={time}>
                                                                {time}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="edit-location">Lieu</Label>
                                            <Input
                                                id="edit-location"
                                                placeholder="Ex: Salle de conférence A"
                                                value={formData.location}
                                                onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="edit-type">Type</Label>
                                            <Select
                                                value={formData.type}
                                                onValueChange={(v) => setFormData({ ...formData, type: v as AppointmentType })}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Type de réunion" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="meeting">Réunion</SelectItem>
                                                    <SelectItem value="group">Groupe</SelectItem>
                                                    <SelectItem value="consultation">Consultation</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="edit-notes">Notes internes</Label>
                                            <Textarea
                                                id="edit-notes"
                                                placeholder="Notes visibles uniquement par les organisateurs..."
                                                value={formData.notes}
                                                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                            />
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id="edit-notify_all_members"
                                                checked={formData.notify_all_members}
                                                onCheckedChange={(checked) =>
                                                    setFormData({ ...formData, notify_all_members: checked as boolean, participant_ids: checked ? [] : formData.participant_ids })
                                                }
                                            />
                                            <Label htmlFor="edit-notify_all_members" className="text-sm font-normal cursor-pointer">
                                                Notifier tous les membres du département
                                            </Label>
                                        </div>
                                        {/* Conditional User selection when not notifying all members */}
                                        {!formData.notify_all_members && (
                                            <div className="grid gap-2">
                                                <Label>Sélectionner les participants</Label>
                                                <UserMultiSelect
                                                    selectedUserIds={formData.participant_ids}
                                                    onChange={(ids) => setFormData({ ...formData, participant_ids: ids })}
                                                    placeholder="Rechercher des membres..."
                                                    maxHeight="200px"
                                                    initialUsers={initialParticipants}
                                                    excludeUserIds={authUserId ? [authUserId] : []}
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    Vous êtes automatiquement inclus comme organisateur. Seuls les participants sélectionnés seront notifiés.
                                                </p>
                                            </div>
                                        )}
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id="edit-is_mandatory"
                                                checked={formData.is_mandatory}
                                                onCheckedChange={(checked) =>
                                                    setFormData({ ...formData, is_mandatory: checked as boolean })
                                                }
                                            />
                                            <Label htmlFor="edit-is_mandatory" className="text-sm font-normal cursor-pointer">
                                                Réunion obligatoire
                                            </Label>
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <Button variant="outline" onClick={handleCloseEditDialog}>
                                            Annuler
                                        </Button>
                                        <Button onClick={handleUpdateMeeting} disabled={isSubmitting}>
                                            {isSubmitting ? 'Enregistrement...' : 'Enregistrer les modifications'}
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                            </>
                        )}
                        {canManage && activeTab === 'appointments' && (
                            <Button asChild>
                                <Link href={`/appointments/create?appointmentable_type=department&appointmentable_id=${departmentId}`}>
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Nouveau RDV
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <TabsContent value="meetings" className="mt-4">
                    {/* Calendar Navigation */}
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={handlePrevMonth}>
                                <ChevronLeftIcon className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleToday}>
                                Aujourd'hui
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleNextMonth}>
                                <ChevronRightIcon className="h-4 w-4" />
                            </Button>
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            {format(currentMonth, 'MMMM yyyy', { locale: fr })}
                        </h3>
                        <div className="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                            <Button
                                variant={view === 'calendar' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setView('calendar')}
                            >
                                <CalendarDaysIcon className="h-4 w-4" />
                            </Button>
                            <Button
                                variant={view === 'list' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setView('list')}
                            >
                                Liste
                            </Button>
                        </div>
                    </div>

                    {view === 'calendar' ? (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            {/* Calendar Grid */}
                            <div className="lg:col-span-2">
                                {/* Weekday Headers */}
                                <div className="grid grid-cols-7 mb-1">
                                    {['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].map((day) => (
                                        <div
                                            key={day}
                                            className="py-2 text-center text-sm font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            {day}
                                        </div>
                                    ))}
                                </div>
                                {/* Calendar Days */}
                                <div className="grid grid-cols-7 border-t border-l border-gray-200 dark:border-gray-700">
                                    {calendarDays.map(renderDayCell)}
                                </div>
                            </div>

                            {/* Selected Date Details */}
                            <div className="space-y-4">
                                {selectedDate ? (
                                    <Card>
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-base">
                                                {format(selectedDate, 'EEEE d MMMM', { locale: fr })}
                                            </CardTitle>
                                            <CardDescription>
                                                {selectedDateItems.length} {activeTab === 'meetings' ? 'réunion(s)' : 'rendez-vous'}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-2">
                                            {selectedDateItems.length > 0 ? (
                                                selectedDateItems.map(renderItemCard)
                                            ) : (
                                                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                    <CalendarDaysIcon className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                                    <p className="text-sm">
                                                        {activeTab === 'meetings' ? 'Aucune réunion' : 'Aucun rendez-vous'}
                                                    </p>
                                                    {canManage && activeTab === 'meetings' && (
                                                        <Button
                                                            className="mt-2"
                                                            size="sm"
                                                            onClick={() => setIsCreateDialogOpen(true)}
                                                        >
                                                            <PlusIcon className="h-4 w-4 mr-1" />
                                                            Créer
                                                        </Button>
                                                    )}
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                ) : (
                                    <Card>
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-base">
                                                {activeTab === 'meetings' ? 'Prochaines réunions' : 'Prochains rendez-vous'}
                                            </CardTitle>
                                            <CardDescription>7 prochains jours</CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-2">
                                            {upcomingItems.length > 0 ? (
                                                upcomingItems.map((item) => (
                                                    <div
                                                        key={item.uuid}
                                                        className="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded"
                                                    >
                                                        <div className={`w-2 h-2 rounded-full ${typeColors[item.type]}`} />
                                                        <div className="flex-1 min-w-0">
                                                            <div className="text-sm font-medium truncate flex items-center gap-1">
                                                                {item.title}
                                                                {(item as any)._meeting?.is_mandatory && (
                                                                    <Badge variant="destructive" className="text-xs ml-1">!</Badge>
                                                                )}
                                                            </div>
                                                            <div className="text-xs text-gray-500 dark:text-gray-400">
                                                                {format(parseISO(item.start_datetime), 'EEE d MMM HH:mm', { locale: fr })}
                                                            </div>
                                                        </div>
                                                        <Link
                                                            href={`/appointments/${item.uuid}`}
                                                            className="text-primary hover:text-primary/80"
                                                        >
                                                            <EyeIcon className="h-4 w-4" />
                                                        </Link>
                                                    </div>
                                                ))
                                            ) : (
                                                <div className="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                                                    {activeTab === 'meetings' ? 'Aucune réunion à venir' : 'Aucun rendez-vous à venir'}
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                )}
                            </div>
                        </div>
                    ) : (
                        /* List View */
                        <div className="space-y-3">
                            {allItems.length > 0 ? (
                                allItems
                                    .sort((a, b) => parseISO(a.start_datetime).getTime() - parseISO(b.start_datetime).getTime())
                                    .map((item) => (
                                        <div key={item.uuid} className="flex items-start gap-4">
                                            <div className="text-center min-w-[60px]">
                                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                                    {format(parseISO(item.start_datetime), 'd')}
                                                </div>
                                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                                    {format(parseISO(item.start_datetime), 'MMM', { locale: fr })}
                                                </div>
                                            </div>
                                            {renderItemCard(item as Appointment & { _meeting?: DepartmentMeeting })}
                                        </div>
                                    ))
                            ) : (
                                <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <CalendarDaysIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                    <p>{activeTab === 'meetings' ? 'Aucune réunion enregistrée' : 'Aucun rendez-vous enregistré'}</p>
                                    {canManage && activeTab === 'meetings' && (
                                        <Button className="mt-4" onClick={() => setIsCreateDialogOpen(true)}>
                                            <PlusIcon className="h-4 w-4 mr-2" />
                                            Créer une réunion
                                        </Button>
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                </TabsContent>

                <TabsContent value="appointments" className="mt-4">
                    {/* Same calendar navigation for appointments */}
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={handlePrevMonth}>
                                <ChevronLeftIcon className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleToday}>
                                Aujourd'hui
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleNextMonth}>
                                <ChevronRightIcon className="h-4 w-4" />
                            </Button>
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            {format(currentMonth, 'MMMM yyyy', { locale: fr })}
                        </h3>
                        <div className="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                            <Button
                                variant={view === 'calendar' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setView('calendar')}
                            >
                                <CalendarDaysIcon className="h-4 w-4" />
                            </Button>
                            <Button
                                variant={view === 'list' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setView('list')}
                            >
                                Liste
                            </Button>
                        </div>
                    </div>

                    {view === 'calendar' ? (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            {/* Calendar Grid */}
                            <div className="lg:col-span-2">
                                {/* Weekday Headers */}
                                <div className="grid grid-cols-7 mb-1">
                                    {['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].map((day) => (
                                        <div
                                            key={day}
                                            className="py-2 text-center text-sm font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            {day}
                                        </div>
                                    ))}
                                </div>
                                {/* Calendar Days */}
                                <div className="grid grid-cols-7 border-t border-l border-gray-200 dark:border-gray-700">
                                    {calendarDays.map(renderDayCell)}
                                </div>
                            </div>

                            {/* Selected Date Details */}
                            <div className="space-y-4">
                                {selectedDate ? (
                                    <Card>
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-base">
                                                {format(selectedDate, 'EEEE d MMMM', { locale: fr })}
                                            </CardTitle>
                                            <CardDescription>
                                                {selectedDateItems.length} rendez-vous
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-2">
                                            {selectedDateItems.length > 0 ? (
                                                selectedDateItems.map(renderItemCard)
                                            ) : (
                                                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                    <CalendarDaysIcon className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                                    <p className="text-sm">Aucun rendez-vous</p>
                                                    {canManage && (
                                                        <Button className="mt-2" size="sm" asChild>
                                                            <Link href={`/appointments/create?appointmentable_type=department&appointmentable_id=${departmentId}&date=${format(selectedDate, 'yyyy-MM-dd')}`}>
                                                                <PlusIcon className="h-4 w-4 mr-1" />
                                                                Créer
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                ) : (
                                    <Card>
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-base">Prochains rendez-vous</CardTitle>
                                            <CardDescription>7 prochains jours</CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-2">
                                            {upcomingItems.length > 0 ? (
                                                upcomingItems.map((item) => (
                                                    <div
                                                        key={item.uuid}
                                                        className="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded"
                                                    >
                                                        <div className={`w-2 h-2 rounded-full ${typeColors[item.type]}`} />
                                                        <div className="flex-1 min-w-0">
                                                            <div className="text-sm font-medium truncate">
                                                                {item.title}
                                                            </div>
                                                            <div className="text-xs text-gray-500 dark:text-gray-400">
                                                                {format(parseISO(item.start_datetime), 'EEE d MMM HH:mm', { locale: fr })}
                                                            </div>
                                                        </div>
                                                        <Link
                                                            href={`/appointments/${item.uuid}`}
                                                            className="text-primary hover:text-primary/80"
                                                        >
                                                            <EyeIcon className="h-4 w-4" />
                                                        </Link>
                                                    </div>
                                                ))
                                            ) : (
                                                <div className="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                                                    Aucun rendez-vous à venir
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                )}
                            </div>
                        </div>
                    ) : (
                        /* List View */
                        <div className="space-y-3">
                            {allItems.length > 0 ? (
                                allItems
                                    .sort((a, b) => parseISO(a.start_datetime).getTime() - parseISO(b.start_datetime).getTime())
                                    .map((item) => (
                                        <div key={item.uuid} className="flex items-start gap-4">
                                            <div className="text-center min-w-[60px]">
                                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                                    {format(parseISO(item.start_datetime), 'd')}
                                                </div>
                                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                                    {format(parseISO(item.start_datetime), 'MMM', { locale: fr })}
                                                </div>
                                            </div>
                                            {renderItemCard(item as Appointment & { _meeting?: DepartmentMeeting })}
                                        </div>
                                    ))
                            ) : (
                                <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <CalendarDaysIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                    <p>Aucun rendez-vous enregistré</p>
                                    {canManage && (
                                        <Button className="mt-4" asChild>
                                            <Link href={`/appointments/create?appointmentable_type=department&appointmentable_id=${departmentId}`}>
                                                <PlusIcon className="h-4 w-4 mr-2" />
                                                Créer un rendez-vous
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                </TabsContent>
            </Tabs>
        </div>
    );
}
