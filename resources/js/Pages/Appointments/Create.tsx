import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
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
import { Checkbox } from '@/Components/ui/checkbox';
import { Separator } from '@/Components/ui/separator';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowLeft,
    Save,
    Users,
    Calendar,
    Clock,
    MapPin,
    Plus,
    X,
    User,
    Eye,
    EyeOff,
    Search,
    Video,
    Link as LinkIcon,
    Bell,
    Mail,
    MessageSquare,
    Smartphone,
} from 'lucide-react';
import { toast } from 'sonner';
import { format, addDays, addHours, startOfHour } from 'date-fns';
import TimeSlotPicker from '@/Components/appointments/TimeSlotPicker';

import type { AppointmentCreateEditProps, AppointmentFormData, User as UserType, MeetingMode, MeetingPlatform, NotificationChannel } from '@/Types/appointment';

export default function AppointmentCreate() {
    const { users, types, prefilledData, preselectedParticipants = [], auth } = usePage<AppointmentCreateEditProps>().props;

    // Pre-fill form with suggested date/time if provided
    const now = new Date();
    const suggestedStart = prefilledData?.date
        ? new Date(`${prefilledData.date}T${prefilledData.time || '09:00'}`)
        : startOfHour(addHours(now, 1));
    const suggestedEnd = addHours(suggestedStart, 1);

    // Minimum datetime for form inputs (current date and time)
    const minDateTime = format(now, "yyyy-MM-dd'T'HH:mm");

    // Initialize with preselected participants if any
    const initialParticipantIds = prefilledData?.participant_ids || [];

    const { data, setData, post, processing, errors, transform } = useForm<AppointmentFormData>({
        title: '',
        description: '',
        start_datetime: format(suggestedStart, "yyyy-MM-dd'T'HH:mm"),
        end_datetime: format(suggestedEnd, "yyyy-MM-dd'T'HH:mm"),
        location: '',
        meeting_mode: 'in_person',
        meeting_link: '',
        meeting_platform: undefined,
        type: 'individual',
        visibility: 'private',
        participant_ids: initialParticipantIds,
        notification_channels: ['email'],
    });

    const [selectedParticipants, setSelectedParticipants] = useState<UserType[]>(preselectedParticipants || []);
    const [userSearchQuery, setUserSearchQuery] = useState<string>('');

    const handleParticipantToggle = (user: UserType, checked: boolean) => {
        if (checked) {
            setSelectedParticipants([...selectedParticipants, user]);
            setData('participant_ids', [...(data.participant_ids || []), user.id]);
        } else {
            setSelectedParticipants(selectedParticipants.filter(p => p.id !== user.id));
            setData('participant_ids', (data.participant_ids || []).filter(id => id !== user.id));
        }
    };

    const removeParticipant = (userId: number) => {
        setSelectedParticipants(selectedParticipants.filter(p => p.id !== userId));
        setData('participant_ids', (data.participant_ids || []).filter(id => id !== userId));
    };

    const handleNotificationChannelToggle = (channel: NotificationChannel, checked: boolean) => {
        const currentChannels = data.notification_channels || [];
        if (checked) {
            setData('notification_channels', [...currentChannels, channel]);
        } else {
            // Ensure at least email is always selected
            if (channel === 'email' && currentChannels.length === 1) {
                return;
            }
            setData('notification_channels', currentChannels.filter(c => c !== channel));
        }
    };

    // Filter users based on search query
    const filteredUsers = users.filter(user => {
        if (!userSearchQuery.trim()) return true;
        const query = userSearchQuery.toLowerCase();
        return (
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query)
        );
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Transform datetime fields to proper format
        transform((data) => ({
            ...data,
            start_datetime: new Date(data.start_datetime).toISOString().slice(0, 19).replace('T', ' '),
            end_datetime: new Date(data.end_datetime).toISOString().slice(0, 19).replace('T', ' '),
        }));

        post(route('appointments.store'), {
            onSuccess: () => {
                toast.success('Rendez-vous créé avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la création du rendez-vous');
            }
        });
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'individual': return 'Individuel';
            case 'group': return 'Groupe';
            case 'consultation': return 'Consultation';
            case 'meeting': return 'Réunion';
            default: return type;
        }
    };

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'group':
            case 'meeting':
                return <Users className="h-4 w-4" />;
            default:
                return <User className="h-4 w-4" />;
        }
    };

    const getVisibilityLabel = (visibility: string) => {
        switch (visibility) {
            case 'private': return 'Privé';
            case 'public': return 'Public';
            default: return visibility;
        }
    };

    const getVisibilityIcon = (visibility: string) => {
        switch (visibility) {
            case 'public': return <Eye className="h-4 w-4" />;
            default: return <EyeOff className="h-4 w-4" />;
        }
    };

    return (
        <DashboardLayout>
            <Head title="Nouveau rendez-vous" />

            <div className="mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="mb-8">
                    <Link
                        href={route('appointments.index')}
                        className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Retour aux rendez-vous
                    </Link>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        Nouveau rendez-vous
                    </h1>
                    <p className="mt-2 text-gray-600 dark:text-gray-400">
                        Planifiez un nouveau rendez-vous avec vos participants
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-8">
                    {/* Basic Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Informations générales</CardTitle>
                            <CardDescription>
                                Définissez les détails de votre rendez-vous
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="title">Titre *</Label>
                                <Input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="Ex: Réunion d'équipe, Consultation client..."
                                    className={errors.title ? 'border-red-500' : ''}
                                />
                                {errors.title && (
                                    <p className="text-sm text-red-600">{errors.title}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description || ''}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Description du rendez-vous, ordre du jour..."
                                    rows={3}
                                    className={errors.description ? 'border-red-500' : ''}
                                />
                                {errors.description && (
                                    <p className="text-sm text-red-600">{errors.description}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="type">Type de rendez-vous *</Label>
                                    <Select
                                        value={data.type}
                                        onValueChange={(value) => setData('type', value as any)}
                                    >
                                        <SelectTrigger className={errors.type ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Sélectionnez le type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {types.map((type) => (
                                                <SelectItem key={type} value={type}>
                                                    <div className="flex items-center space-x-2">
                                                        {getTypeIcon(type)}
                                                        <span>{getTypeLabel(type)}</span>
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.type && (
                                        <p className="text-sm text-red-600">{errors.type}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="visibility">Visibilité *</Label>
                                    <Select
                                        value={data.visibility}
                                        onValueChange={(value) => setData('visibility', value as any)}
                                    >
                                        <SelectTrigger className={errors.visibility ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Sélectionnez la visibilité" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="private">
                                                <div className="flex items-center space-x-2">
                                                    {getVisibilityIcon('private')}
                                                    <span>{getVisibilityLabel('private')}</span>
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="public">
                                                <div className="flex items-center space-x-2">
                                                    {getVisibilityIcon('public')}
                                                    <span>{getVisibilityLabel('public')}</span>
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.visibility && (
                                        <p className="text-sm text-red-600">{errors.visibility}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="location">Lieu</Label>
                                    <Input
                                        id="location"
                                        type="text"
                                        value={data.location || ''}
                                        onChange={(e) => setData('location', e.target.value)}
                                        placeholder="Salle de réunion, adresse..."
                                        className={errors.location ? 'border-red-500' : ''}
                                    />
                                    {errors.location && (
                                        <p className="text-sm text-red-600">{errors.location}</p>
                                    )}
                                </div>
                            </div>

                            <Separator />

                            {/* Meeting Mode Section */}
                            <div className="space-y-4">
                                <div className="flex items-center space-x-2">
                                    <Video className="h-5 w-5 text-gray-500" />
                                    <Label className="text-base font-medium">Mode de réunion</Label>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="meeting_mode">Mode *</Label>
                                        <Select
                                            value={data.meeting_mode}
                                            onValueChange={(value) => {
                                                setData('meeting_mode', value as MeetingMode);
                                                // Clear meeting link and platform if switching to in_person
                                                if (value === 'in_person') {
                                                    setData('meeting_link', '');
                                                    setData('meeting_platform', undefined);
                                                }
                                            }}
                                        >
                                            <SelectTrigger className={errors.meeting_mode ? 'border-red-500' : ''}>
                                                <SelectValue placeholder="Sélectionnez le mode" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="in_person">
                                                    <div className="flex items-center space-x-2">
                                                        <MapPin className="h-4 w-4" />
                                                        <span>En présentiel</span>
                                                    </div>
                                                </SelectItem>
                                                <SelectItem value="online">
                                                    <div className="flex items-center space-x-2">
                                                        <Video className="h-4 w-4" />
                                                        <span>En ligne</span>
                                                    </div>
                                                </SelectItem>
                                                <SelectItem value="hybrid">
                                                    <div className="flex items-center space-x-2">
                                                        <Users className="h-4 w-4" />
                                                        <span>Hybride</span>
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.meeting_mode && (
                                            <p className="text-sm text-red-600">{errors.meeting_mode}</p>
                                        )}
                                    </div>

                                    {(data.meeting_mode === 'online' || data.meeting_mode === 'hybrid') && (
                                        <>
                                            <div className="space-y-2">
                                                <Label htmlFor="meeting_platform">Plateforme *</Label>
                                                <Select
                                                    value={data.meeting_platform || ''}
                                                    onValueChange={(value) => setData('meeting_platform', value as MeetingPlatform)}
                                                >
                                                    <SelectTrigger className={errors.meeting_platform ? 'border-red-500' : ''}>
                                                        <SelectValue placeholder="Sélectionnez la plateforme" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="zoom">
                                                            <span>Zoom</span>
                                                        </SelectItem>
                                                        <SelectItem value="google_meet">
                                                            <span>Google Meet</span>
                                                        </SelectItem>
                                                        <SelectItem value="ms_teams">
                                                            <span>Microsoft Teams</span>
                                                        </SelectItem>
                                                        <SelectItem value="other">
                                                            <span>Autre</span>
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                {errors.meeting_platform && (
                                                    <p className="text-sm text-red-600">{errors.meeting_platform}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="meeting_link">Lien de la réunion *</Label>
                                                <div className="relative">
                                                    <LinkIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                                    <Input
                                                        id="meeting_link"
                                                        type="url"
                                                        value={data.meeting_link || ''}
                                                        onChange={(e) => setData('meeting_link', e.target.value)}
                                                        placeholder="https://zoom.us/j/..."
                                                        className={`pl-10 ${errors.meeting_link ? 'border-red-500' : ''}`}
                                                    />
                                                </div>
                                                {errors.meeting_link && (
                                                    <p className="text-sm text-red-600">{errors.meeting_link}</p>
                                                )}
                                            </div>
                                        </>
                                    )}
                                </div>

                                {(data.meeting_mode === 'online' || data.meeting_mode === 'hybrid') && (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Le lien de la réunion sera inclus dans les invitations envoyées aux participants.
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Date and Time Slot Picker */}
                    <TimeSlotPicker
                        selectedStartDateTime={data.start_datetime}
                        selectedEndDateTime={data.end_datetime}
                        onTimeSlotSelect={(startDateTime, endDateTime) => {
                            setData('start_datetime', format(new Date(startDateTime), "yyyy-MM-dd'T'HH:mm"));
                            setData('end_datetime', format(new Date(endDateTime), "yyyy-MM-dd'T'HH:mm"));
                        }}
                        duration={60}
                        organizerId={auth.user?.id}
                        participants={selectedParticipants}
                        errors={{
                            start_datetime: errors.start_datetime,
                            end_datetime: errors.end_datetime
                        }}
                    />

                    {/* Participants */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Users className="h-5 w-5" />
                                <span>Participants</span>
                            </CardTitle>
                            <CardDescription>
                                Invitez des participants à votre rendez-vous
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Selected Participants */}
                            {selectedParticipants.length > 0 && (
                                <div className="space-y-3">
                                    <Label>Participants sélectionnés ({selectedParticipants.length})</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {selectedParticipants.map((participant) => (
                                            <Badge key={participant.id} variant="secondary" className="flex items-center space-x-2">
                                                <span>{participant.name}</span>
                                                <button
                                                    type="button"
                                                    onClick={() => removeParticipant(participant.id)}
                                                    className="ml-2 hover:text-red-600"
                                                >
                                                    <X className="h-3 w-3" />
                                                </button>
                                            </Badge>
                                        ))}
                                    </div>
                                    <Separator />
                                </div>
                            )}

                            {/* Available Users */}
                            <div className="space-y-3">
                                <Label>Utilisateurs disponibles</Label>

                                {/* Search Input */}
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        type="text"
                                        placeholder="Rechercher par nom ou email..."
                                        value={userSearchQuery}
                                        onChange={(e) => setUserSearchQuery(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>

                                <div className="max-h-64 overflow-y-auto space-y-2 border rounded-md p-3">
                                    {filteredUsers.length === 0 ? (
                                        <div className="text-center py-4 text-gray-500">
                                            {userSearchQuery.trim() ? 'Aucun utilisateur trouvé' : 'Aucun utilisateur disponible'}
                                        </div>
                                    ) : (
                                        filteredUsers.map((user) => (
                                        <div key={user.id} className="flex items-center space-x-3">
                                            <Checkbox
                                                id={`user-${user.id}`}
                                                checked={selectedParticipants.some(p => p.id === user.id)}
                                                onCheckedChange={(checked) => handleParticipantToggle(user, checked as boolean)}
                                            />
                                            <Label htmlFor={`user-${user.id}`} className="flex-1 cursor-pointer">
                                                <div className="flex items-center space-x-2">
                                                    <User className="h-4 w-4 text-gray-400" />
                                                    <div>
                                                        <p className="font-medium">{user.name}</p>
                                                        <p className="text-sm text-gray-500">{user.email}</p>
                                                    </div>
                                                </div>
                                            </Label>
                                        </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Notification Settings */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Bell className="h-5 w-5" />
                                <span>Notifications de rappel</span>
                            </CardTitle>
                            <CardDescription>
                                Choisissez comment les participants recevront les rappels de rendez-vous (24h avant)
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {/* Email */}
                                <div className="flex items-center space-x-3 p-4 border rounded-lg bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                                    <Checkbox
                                        id="notification-email"
                                        checked={(data.notification_channels || []).includes('email')}
                                        onCheckedChange={(checked) => handleNotificationChannelToggle('email', checked as boolean)}
                                        disabled={true}
                                    />
                                    <Label htmlFor="notification-email" className="flex-1 cursor-pointer">
                                        <div className="flex items-center space-x-2">
                                            <Mail className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                            <div>
                                                <p className="font-medium text-blue-900 dark:text-blue-100">Email</p>
                                                <p className="text-sm text-blue-600 dark:text-blue-400">Toujours activé</p>
                                            </div>
                                        </div>
                                    </Label>
                                </div>

                                {/* SMS */}
                                <div className="flex items-center space-x-3 p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <Checkbox
                                        id="notification-sms"
                                        checked={(data.notification_channels || []).includes('sms')}
                                        onCheckedChange={(checked) => handleNotificationChannelToggle('sms', checked as boolean)}
                                    />
                                    <Label htmlFor="notification-sms" className="flex-1 cursor-pointer">
                                        <div className="flex items-center space-x-2">
                                            <Smartphone className="h-5 w-5 text-green-600 dark:text-green-400" />
                                            <div>
                                                <p className="font-medium">SMS</p>
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Rappel par SMS</p>
                                            </div>
                                        </div>
                                    </Label>
                                </div>

                                {/* WhatsApp */}
                                <div className="flex items-center space-x-3 p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <Checkbox
                                        id="notification-whatsapp"
                                        checked={(data.notification_channels || []).includes('whatsapp')}
                                        onCheckedChange={(checked) => handleNotificationChannelToggle('whatsapp', checked as boolean)}
                                    />
                                    <Label htmlFor="notification-whatsapp" className="flex-1 cursor-pointer">
                                        <div className="flex items-center space-x-2">
                                            <MessageSquare className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                            <div>
                                                <p className="font-medium">WhatsApp</p>
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Rappel par WhatsApp</p>
                                            </div>
                                        </div>
                                    </Label>
                                </div>
                            </div>

                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Les rappels SMS et WhatsApp ne seront envoyés qu'aux participants ayant un numéro de téléphone enregistré.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-between">
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('appointments.index')}>
                                Annuler
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <>Création en cours...</>
                            ) : (
                                <>
                                    <Save className="h-4 w-4 mr-2" />
                                    Créer le rendez-vous
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}