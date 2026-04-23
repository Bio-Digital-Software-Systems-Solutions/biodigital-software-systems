import React, { useState, useMemo } from 'react';
import {
    CalendarDaysIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClockIcon,
    MapPinIcon,
    UserGroupIcon,
    PlusIcon,
} from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Checkbox } from '@/Components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import type { Appointment, AppointmentStatus } from '@/Types/appointment';
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
} from 'date-fns';
import { fr } from 'date-fns/locale';

interface GroupMeeting {
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

interface GroupCalendarProps {
    appointments: Appointment[];
    meetings?: GroupMeeting[];
    groupId: number;
    groupUuid: string;
    canManage?: boolean;
    groupUsers?: Array<{ id: number; name: string; email: string }>;
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

interface MeetingFormData {
    title: string;
    description: string;
    start_date: string;
    start_time: string;
    end_date: string;
    end_time: string;
    location: string;
    type: string;
    notes: string;
    is_mandatory: boolean;
    notify_all_members: boolean;
}

const defaultFormData: MeetingFormData = {
    title: '',
    description: '',
    start_date: '',
    start_time: '09:00',
    end_date: '',
    end_time: '10:00',
    location: '',
    type: 'meeting',
    notes: '',
    is_mandatory: false,
    notify_all_members: true,
};

export default function GroupCalendar({
    appointments,
    meetings = [],
    groupId,
    groupUuid,
    canManage = false,
    groupUsers = [],
}: GroupCalendarProps) {
    const [currentMonth, setCurrentMonth] = useState(new Date());
    const [calendarTab, setCalendarTab] = useState<'appointments' | 'meetings'>('meetings');
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [formData, setFormData] = useState<MeetingFormData>({ ...defaultFormData });
    const [processing, setProcessing] = useState(false);

    const allEvents = useMemo(() => {
        const events: Array<{ date: Date; title: string; type: 'appointment' | 'meeting'; status: string; uuid: string }> = [];

        appointments.forEach(apt => {
            events.push({
                date: parseISO(apt.start_datetime),
                title: apt.title,
                type: 'appointment',
                status: apt.status,
                uuid: apt.uuid,
            });
        });

        meetings.forEach(meeting => {
            if (meeting.appointment) {
                events.push({
                    date: parseISO(meeting.appointment.start_datetime),
                    title: meeting.appointment.title,
                    type: 'meeting',
                    status: meeting.appointment.status,
                    uuid: meeting.uuid,
                });
            }
        });

        return events;
    }, [appointments, meetings]);

    const calendarDays = useMemo(() => {
        const monthStart = startOfMonth(currentMonth);
        const monthEnd = endOfMonth(currentMonth);
        const startDate = startOfWeek(monthStart, { weekStartsOn: 1 });
        const endDate = endOfWeek(monthEnd, { weekStartsOn: 1 });

        const days: Date[] = [];
        let day = startDate;
        while (day <= endDate) {
            days.push(day);
            day = addDays(day, 1);
        }
        return days;
    }, [currentMonth]);

    const getEventsForDay = (day: Date) => {
        return allEvents.filter(event => isSameDay(event.date, day));
    };

    const handleCreateMeeting = async () => {
        if (!formData.title || !formData.start_date || !formData.end_date) {
            toast.error('Veuillez remplir tous les champs obligatoires.');
            return;
        }

        setProcessing(true);
        try {
            const startDatetime = `${formData.start_date}T${formData.start_time}:00`;
            const endDatetime = `${formData.end_date}T${formData.end_time}:00`;

            await axios.post(`/api/groups/${groupUuid}/meetings`, {
                title: formData.title,
                description: formData.description || null,
                start_datetime: startDatetime,
                end_datetime: endDatetime,
                location: formData.location || null,
                type: formData.type,
                notes: formData.notes || null,
                is_mandatory: formData.is_mandatory,
                notify_all_members: formData.notify_all_members,
            });

            toast.success('Réunion créée avec succès.');
            setShowCreateDialog(false);
            setFormData({ ...defaultFormData });
            window.location.reload();
        } catch (error: unknown) {
            const axiosError = error as { response?: { data?: { message?: string } } };
            toast.error(axiosError.response?.data?.message || 'Erreur lors de la création.');
        } finally {
            setProcessing(false);
        }
    };

    const handleDeleteMeeting = async (meetingUuid: string) => {
        try {
            await axios.delete(`/api/groups/${groupUuid}/meetings/${meetingUuid}`);
            toast.success('Réunion supprimée avec succès.');
            window.location.reload();
        } catch {
            toast.error('Erreur lors de la suppression.');
        }
    };

    const upcomingMeetings = meetings
        .filter(m => m.appointment && new Date(m.appointment.start_datetime) >= new Date())
        .sort((a, b) => {
            const dateA = a.appointment ? new Date(a.appointment.start_datetime).getTime() : 0;
            const dateB = b.appointment ? new Date(b.appointment.start_datetime).getTime() : 0;
            return dateA - dateB;
        });

    return (
        <div className="space-y-4">
            {/* Header with Create Button */}
            <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Agenda du Groupe</h2>
                {canManage && (
                    <Button size="sm" onClick={() => setShowCreateDialog(true)}>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Nouvelle Réunion
                    </Button>
                )}
            </div>

            <Tabs value={calendarTab} onValueChange={(v) => setCalendarTab(v as 'appointments' | 'meetings')}>
                <TabsList className="grid w-full grid-cols-2">
                    <TabsTrigger value="meetings">
                        <UserGroupIcon className="h-4 w-4 mr-2" />
                        Réunions
                        <Badge variant="secondary" className="ml-2">{meetings.length}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="appointments">
                        <CalendarDaysIcon className="h-4 w-4 mr-2" />
                        Rendez-vous
                        <Badge variant="secondary" className="ml-2">{appointments.length}</Badge>
                    </TabsTrigger>
                </TabsList>

                {/* Calendar Grid */}
                <div className="mt-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <div className="flex items-center justify-between">
                                <Button variant="ghost" size="sm" onClick={() => setCurrentMonth(subMonths(currentMonth, 1))}>
                                    <ChevronLeftIcon className="h-4 w-4" />
                                </Button>
                                <CardTitle className="text-base capitalize">
                                    {format(currentMonth, 'MMMM yyyy', { locale: fr })}
                                </CardTitle>
                                <Button variant="ghost" size="sm" onClick={() => setCurrentMonth(addMonths(currentMonth, 1))}>
                                    <ChevronRightIcon className="h-4 w-4" />
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-7 gap-px">
                                {['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].map(day => (
                                    <div key={day} className="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">
                                        {day}
                                    </div>
                                ))}
                                {calendarDays.map((day, idx) => {
                                    const dayEvents = getEventsForDay(day);
                                    const isCurrentMonth = isSameMonth(day, currentMonth);
                                    return (
                                        <div
                                            key={idx}
                                            className={`min-h-[60px] p-1 border border-gray-100 dark:border-gray-700 rounded ${
                                                !isCurrentMonth ? 'bg-gray-50/50 dark:bg-gray-800/30' : ''
                                            } ${isToday(day) ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' : ''}`}
                                        >
                                            <div className={`text-xs font-medium ${
                                                isToday(day) ? 'text-blue-600 dark:text-blue-400' :
                                                isCurrentMonth ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-600'
                                            }`}>
                                                {format(day, 'd')}
                                            </div>
                                            {dayEvents.slice(0, 2).map((event, i) => (
                                                <div
                                                    key={i}
                                                    className={`text-xs truncate px-1 py-0.5 rounded mt-0.5 ${
                                                        event.type === 'meeting'
                                                            ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                                                            : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                    }`}
                                                >
                                                    {event.title}
                                                </div>
                                            ))}
                                            {dayEvents.length > 2 && (
                                                <div className="text-xs text-gray-500 dark:text-gray-400 px-1">
                                                    +{dayEvents.length - 2}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Meetings List */}
                <TabsContent value="meetings" className="mt-4">
                    {upcomingMeetings.length > 0 ? (
                        <div className="space-y-3">
                            {upcomingMeetings.map(meeting => (
                                <Card key={meeting.uuid}>
                                    <CardContent className="p-4">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <h3 className="font-medium text-gray-900 dark:text-white">
                                                        {meeting.appointment?.title}
                                                    </h3>
                                                    {meeting.is_mandatory && (
                                                        <Badge variant="destructive" className="text-xs">Obligatoire</Badge>
                                                    )}
                                                    {meeting.appointment?.status && (
                                                        <Badge className={statusColors[meeting.appointment.status as AppointmentStatus]}>
                                                            {statusLabels[meeting.appointment.status as AppointmentStatus]}
                                                        </Badge>
                                                    )}
                                                </div>
                                                {meeting.appointment?.description && (
                                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                        {meeting.appointment.description}
                                                    </p>
                                                )}
                                                <div className="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                                    <span className="flex items-center gap-1">
                                                        <ClockIcon className="h-4 w-4" />
                                                        {meeting.appointment?.start_datetime &&
                                                            format(parseISO(meeting.appointment.start_datetime), 'dd MMM yyyy HH:mm', { locale: fr })}
                                                    </span>
                                                    {meeting.appointment?.location && (
                                                        <span className="flex items-center gap-1">
                                                            <MapPinIcon className="h-4 w-4" />
                                                            {meeting.appointment.location}
                                                        </span>
                                                    )}
                                                    {meeting.appointment?.participants_count !== undefined && (
                                                        <span className="flex items-center gap-1">
                                                            <UserGroupIcon className="h-4 w-4" />
                                                            {meeting.appointment.participants_count} participant(s)
                                                        </span>
                                                    )}
                                                </div>
                                                {meeting.notes && (
                                                    <p className="text-sm text-gray-600 dark:text-gray-300 mt-2 italic">
                                                        {meeting.notes}
                                                    </p>
                                                )}
                                            </div>
                                            {canManage && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-red-600 hover:text-red-700"
                                                    onClick={() => handleDeleteMeeting(meeting.uuid)}
                                                >
                                                    Supprimer
                                                </Button>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                            <CalendarDaysIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                            <p>Aucune réunion à venir</p>
                        </div>
                    )}
                </TabsContent>

                {/* Appointments List */}
                <TabsContent value="appointments" className="mt-4">
                    {appointments.length > 0 ? (
                        <div className="space-y-3">
                            {appointments.map(apt => (
                                <Card key={apt.uuid}>
                                    <CardContent className="p-4">
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <h3 className="font-medium text-gray-900 dark:text-white">{apt.title}</h3>
                                                    <Badge className={statusColors[apt.status]}>
                                                        {statusLabels[apt.status]}
                                                    </Badge>
                                                </div>
                                                <div className="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                                    <span className="flex items-center gap-1">
                                                        <ClockIcon className="h-4 w-4" />
                                                        {format(parseISO(apt.start_datetime), 'dd MMM yyyy HH:mm', { locale: fr })}
                                                    </span>
                                                    {apt.location && (
                                                        <span className="flex items-center gap-1">
                                                            <MapPinIcon className="h-4 w-4" />
                                                            {apt.location}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                            <CalendarDaysIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                            <p>Aucun rendez-vous</p>
                        </div>
                    )}
                </TabsContent>
            </Tabs>

            {/* Create Meeting Dialog */}
            <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                <DialogContent className="max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Nouvelle Réunion</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-4 px-6">
                        <div>
                            <Label>Titre *</Label>
                            <Input
                                value={formData.title}
                                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                placeholder="Titre de la réunion"
                            />
                        </div>
                        <div>
                            <Label>Description</Label>
                            <Textarea
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                placeholder="Description..."
                                rows={2}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Date de début *</Label>
                                <Input
                                    type="date"
                                    value={formData.start_date}
                                    onChange={(e) => setFormData({ ...formData, start_date: e.target.value, end_date: e.target.value || formData.end_date })}
                                />
                            </div>
                            <div>
                                <Label>Heure de début</Label>
                                <Input
                                    type="time"
                                    value={formData.start_time}
                                    onChange={(e) => setFormData({ ...formData, start_time: e.target.value })}
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Date de fin *</Label>
                                <Input
                                    type="date"
                                    value={formData.end_date}
                                    onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label>Heure de fin</Label>
                                <Input
                                    type="time"
                                    value={formData.end_time}
                                    onChange={(e) => setFormData({ ...formData, end_time: e.target.value })}
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Lieu</Label>
                            <Input
                                value={formData.location}
                                onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                                placeholder="Lieu de la réunion"
                            />
                        </div>
                        <div>
                            <Label>Type</Label>
                            <Select value={formData.type} onValueChange={(v) => setFormData({ ...formData, type: v })}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="meeting">Réunion</SelectItem>
                                    <SelectItem value="group">Groupe</SelectItem>
                                    <SelectItem value="consultation">Consultation</SelectItem>
                                    <SelectItem value="individual">Individuel</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Notes</Label>
                            <Textarea
                                value={formData.notes}
                                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                rows={2}
                            />
                        </div>
                        <div className="flex items-center gap-4">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <Checkbox
                                    checked={formData.is_mandatory}
                                    onCheckedChange={(checked) => setFormData({ ...formData, is_mandatory: !!checked })}
                                />
                                <span className="text-sm">Obligatoire</span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <Checkbox
                                    checked={formData.notify_all_members}
                                    onCheckedChange={(checked) => setFormData({ ...formData, notify_all_members: !!checked })}
                                />
                                <span className="text-sm">Notifier tous les membres</span>
                            </label>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowCreateDialog(false)}>
                            Annuler
                        </Button>
                        <Button onClick={handleCreateMeeting} disabled={processing}>
                            {processing ? 'Création...' : 'Créer'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
