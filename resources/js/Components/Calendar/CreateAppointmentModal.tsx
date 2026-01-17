import React, { useState, useEffect, FormEvent } from 'react';
import { XMarkIcon, MapPinIcon, UserPlusIcon, CalendarIcon, VideoCameraIcon, LinkIcon } from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import axios from 'axios';
import { toast } from 'sonner';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email?: string;
}

interface Task {
    id: number;
    uuid: string;
    title: string;
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void;
    projectId: number;
    projectUuid: string;
    tasks?: Task[];
    users?: User[];
    initialDate?: Date;
}

type AppointmentType = 'individual' | 'group' | 'consultation' | 'meeting';
type MeetingMode = 'in_person' | 'online' | 'hybrid';
type MeetingPlatform = 'zoom' | 'google_meet' | 'ms_teams' | 'other';

export default function CreateAppointmentModal({
    isOpen,
    onClose,
    onSuccess,
    projectId,
    projectUuid,
    tasks = [],
    users = [],
    initialDate
}: Props) {
    const [loading, setLoading] = useState(false);
    const [activeTab, setActiveTab] = useState<AppointmentType>('individual');

    // Form state
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [startDate, setStartDate] = useState('');
    const [startTime, setStartTime] = useState('09:00');
    const [endDate, setEndDate] = useState('');
    const [endTime, setEndTime] = useState('10:00');
    const [location, setLocation] = useState('');
    const [meetingMode, setMeetingMode] = useState<MeetingMode>('in_person');
    const [meetingPlatform, setMeetingPlatform] = useState<MeetingPlatform | ''>('');
    const [meetingLink, setMeetingLink] = useState('');
    const [isPublic, setIsPublic] = useState(true);
    const [maxParticipants, setMaxParticipants] = useState<number | ''>('');
    const [selectedParticipants, setSelectedParticipants] = useState<number[]>([]);
    const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
    const [showUserSearch, setShowUserSearch] = useState(false);
    const [userSearchTerm, setUserSearchTerm] = useState('');

    // Format date to local YYYY-MM-DD string (avoids timezone issues with toISOString)
    const formatLocalDate = (date: Date): string => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    // Initialize dates when modal opens
    useEffect(() => {
        if (isOpen) {
            const date = initialDate || new Date();
            const dateStr = formatLocalDate(date);
            setStartDate(dateStr);
            setEndDate(dateStr);
        }
    }, [isOpen, initialDate]);

    // Reset form when modal closes
    useEffect(() => {
        if (!isOpen) {
            setTitle('');
            setDescription('');
            setStartTime('09:00');
            setEndTime('10:00');
            setLocation('');
            setMeetingMode('in_person');
            setMeetingPlatform('');
            setMeetingLink('');
            setIsPublic(true);
            setMaxParticipants('');
            setSelectedParticipants([]);
            setSelectedTaskId(null);
            setActiveTab('meeting');
        }
    }, [isOpen]);

    const filteredUsers = users.filter(user => {
        const fullName = `${user.first_name} ${user.last_name}`.toLowerCase();
        return fullName.includes(userSearchTerm.toLowerCase()) &&
            !selectedParticipants.includes(user.id);
    });

    const handleAddParticipant = (userId: number) => {
        setSelectedParticipants([...selectedParticipants, userId]);
        setUserSearchTerm('');
        setShowUserSearch(false);
    };

    const handleRemoveParticipant = (userId: number) => {
        setSelectedParticipants(selectedParticipants.filter(id => id !== userId));
    };

    const getSelectedParticipantNames = () => {
        return selectedParticipants.map(id => {
            const user = users.find(u => u.id === id);
            return user ? `${user.first_name} ${user.last_name}` : '';
        }).filter(Boolean);
    };

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();

        if (!title.trim()) {
            toast.error('Le titre est requis');
            return;
        }

        if (!startDate || !startTime) {
            toast.error('La date et l\'heure de début sont requises');
            return;
        }

        setLoading(true);

        try {
            const startDatetime = `${startDate}T${startTime}:00`;
            const endDatetime = `${endDate}T${endTime}:00`;

            // Determine appointmentable type based on selection
            let appointmentableType = 'App\\Models\\Project';
            let appointmentableId = projectId;

            if (selectedTaskId) {
                appointmentableType = 'App\\Models\\Task';
                appointmentableId = selectedTaskId;
            }

            const payload = {
                title,
                description,
                start_datetime: startDatetime,
                end_datetime: endDatetime,
                location,
                meeting_mode: meetingMode,
                meeting_platform: meetingPlatform || null,
                meeting_link: meetingLink || null,
                type: activeTab,
                visibility: isPublic ? 'public' : 'private',
                max_participants: maxParticipants || null,
                participants: selectedParticipants,
                appointmentable_type: appointmentableType,
                appointmentable_id: appointmentableId,
            };

            await axios.post(`/api/projects/${projectUuid}/appointments`, payload);

            toast.success('Rendez-vous créé avec succès');
            onSuccess();
            onClose();
        } catch (error: any) {
            console.error('Error creating appointment:', error);
            const message = error.response?.data?.message || 'Erreur lors de la création du rendez-vous';
            toast.error(message);
        } finally {
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/50"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Ajouter un rendez-vous
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
                        title="Fermer"
                        aria-label="Fermer le modal"
                    >
                        <XMarkIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-4 space-y-4">
                    {/* Title */}
                    <div>
                        <Input
                            type="text"
                            placeholder="Ajouter un titre"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            className="text-lg font-medium border-0 border-b border-gray-200 dark:border-gray-700 rounded-none px-0 focus:ring-0 focus:border-primary"
                        />
                    </div>

                    {/* Type Tabs */}
                    <div className="flex gap-2 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <button
                            type="button"
                            onClick={() => setActiveTab('individual')}
                            className={`flex-1 py-2 px-3 text-sm font-medium rounded-md transition-colors ${
                                activeTab === 'individual'
                                    ? 'bg-white dark:bg-gray-600 text-primary shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                            }`}
                        >
                            Individuel
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('group')}
                            className={`flex-1 py-2 px-3 text-sm font-medium rounded-md transition-colors ${
                                activeTab === 'group'
                                    ? 'bg-white dark:bg-gray-600 text-primary shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                            }`}
                        >
                            Groupe
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('meeting')}
                            className={`flex-1 py-2 px-3 text-sm font-medium rounded-md transition-colors ${
                                activeTab === 'meeting'
                                    ? 'bg-white dark:bg-gray-600 text-primary shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                            }`}
                        >
                            Réunion
                        </button>
                    </div>

                    {/* Date & Time */}
                    <div className="space-y-3">
                        <div className="flex items-center gap-3">
                            <CalendarIcon className="h-5 w-5 text-gray-400" />
                            <div className="flex-1 grid grid-cols-2 gap-2">
                                <Input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => {
                                        setStartDate(e.target.value);
                                        if (!endDate || e.target.value > endDate) {
                                            setEndDate(e.target.value);
                                        }
                                    }}
                                    className="text-sm"
                                />
                                <Input
                                    type="time"
                                    value={startTime}
                                    onChange={(e) => setStartTime(e.target.value)}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <div className="w-5" /> {/* Spacer */}
                            <div className="flex-1 grid grid-cols-2 gap-2">
                                <Input
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                    min={startDate}
                                    className="text-sm"
                                />
                                <Input
                                    type="time"
                                    value={endTime}
                                    onChange={(e) => setEndTime(e.target.value)}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Add Guests */}
                    <div className="relative">
                        <div className="flex items-center gap-3">
                            <UserPlusIcon className="h-5 w-5 text-gray-400" />
                            <div className="flex-1">
                                <button
                                    type="button"
                                    onClick={() => setShowUserSearch(!showUserSearch)}
                                    className="text-sm text-primary hover:text-primary/80 transition-colors"
                                >
                                    Ajouter des invités
                                </button>
                                {selectedParticipants.length > 0 && (
                                    <div className="flex flex-wrap gap-1 mt-2">
                                        {getSelectedParticipantNames().map((name, index) => (
                                            <span
                                                key={selectedParticipants[index]}
                                                className="inline-flex items-center gap-1 px-2 py-1 bg-primary/10 text-primary text-xs rounded-full"
                                            >
                                                {name}
                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveParticipant(selectedParticipants[index])}
                                                    className="hover:text-red-500"
                                                    title={`Supprimer ${name}`}
                                                    aria-label={`Supprimer ${name}`}
                                                >
                                                    <XMarkIcon className="h-3 w-3" />
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {showUserSearch && (
                            <div className="absolute left-8 right-0 mt-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-10">
                                <Input
                                    type="text"
                                    placeholder="Rechercher un utilisateur..."
                                    value={userSearchTerm}
                                    onChange={(e) => setUserSearchTerm(e.target.value)}
                                    className="border-0 border-b border-gray-200 dark:border-gray-600 rounded-t-lg rounded-b-none"
                                    autoFocus
                                />
                                <div className="max-h-40 overflow-y-auto">
                                    {filteredUsers.length > 0 ? (
                                        filteredUsers.slice(0, 5).map(user => (
                                            <button
                                                key={user.id}
                                                type="button"
                                                onClick={() => handleAddParticipant(user.id)}
                                                className="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                            >
                                                {user.first_name} {user.last_name}
                                            </button>
                                        ))
                                    ) : (
                                        <div className="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            Aucun utilisateur trouvé
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Location */}
                    <div className="flex items-center gap-3">
                        <MapPinIcon className="h-5 w-5 text-gray-400" />
                        <Input
                            type="text"
                            placeholder="Ajouter un emplacement"
                            value={location}
                            onChange={(e) => setLocation(e.target.value)}
                            className="flex-1 border-0 bg-gray-50 dark:bg-gray-700"
                        />
                    </div>

                    {/* Meeting Mode */}
                    <div className="space-y-3">
                        <div className="flex items-center gap-3">
                            <VideoCameraIcon className="h-5 w-5 text-gray-400" />
                            <Select
                                value={meetingMode}
                                onValueChange={(value) => {
                                    setMeetingMode(value as MeetingMode);
                                    if (value === 'in_person') {
                                        setMeetingPlatform('');
                                        setMeetingLink('');
                                    }
                                }}
                            >
                                <SelectTrigger className="flex-1 border-0 bg-gray-50 dark:bg-gray-700">
                                    <SelectValue placeholder="Mode de réunion" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="in_person">En présentiel</SelectItem>
                                    <SelectItem value="online">En ligne</SelectItem>
                                    <SelectItem value="hybrid">Hybride</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Platform & Link (only show for online/hybrid) */}
                        {(meetingMode === 'online' || meetingMode === 'hybrid') && (
                            <div className="ml-8 space-y-3">
                                <Select
                                    value={meetingPlatform}
                                    onValueChange={(value) => setMeetingPlatform(value as MeetingPlatform)}
                                >
                                    <SelectTrigger className="w-full border-0 bg-gray-50 dark:bg-gray-700">
                                        <SelectValue placeholder="Sélectionner la plateforme" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="zoom">Zoom</SelectItem>
                                        <SelectItem value="google_meet">Google Meet</SelectItem>
                                        <SelectItem value="ms_teams">Microsoft Teams</SelectItem>
                                        <SelectItem value="other">Autre</SelectItem>
                                    </SelectContent>
                                </Select>

                                <div className="relative">
                                    <LinkIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        type="url"
                                        placeholder="https://zoom.us/j/... ou lien de la réunion"
                                        value={meetingLink}
                                        onChange={(e) => setMeetingLink(e.target.value)}
                                        className="pl-10 border-0 bg-gray-50 dark:bg-gray-700"
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Description */}
                    <div>
                        <Textarea
                            placeholder="Ajouter une description"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            rows={3}
                            className="border-0 bg-gray-50 dark:bg-gray-700 resize-none"
                        />
                    </div>

                    {/* Task Association (optional) */}
                    {tasks.length > 0 && (
                        <div>
                            <Label htmlFor="task-select" className="text-sm text-gray-600 dark:text-gray-400 mb-2 block">
                                Associer à une tâche (optionnel)
                            </Label>
                            <select
                                id="task-select"
                                value={selectedTaskId || ''}
                                onChange={(e) => setSelectedTaskId(e.target.value ? Number(e.target.value) : null)}
                                className="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm"
                                title="Associer à une tâche"
                            >
                                <option value="">Projet uniquement</option>
                                {tasks.map(task => (
                                    <option key={task.id} value={task.id}>
                                        {task.title}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    {/* Visibility & Max Participants */}
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <Label htmlFor="visibility" className="text-sm text-gray-600 dark:text-gray-400">
                                Public
                            </Label>
                            <Switch
                                id="visibility"
                                checked={isPublic}
                                onCheckedChange={setIsPublic}
                            />
                        </div>

                        <div className="flex items-center gap-2">
                            <Label htmlFor="maxParticipants" className="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                Max participants
                            </Label>
                            <Input
                                id="maxParticipants"
                                type="number"
                                min="1"
                                value={maxParticipants}
                                onChange={(e) => setMaxParticipants(e.target.value ? Number(e.target.value) : '')}
                                className="w-20 text-sm"
                                placeholder="-"
                            />
                        </div>
                    </div>

                    {/* Submit Button */}
                    <Button
                        type="submit"
                        disabled={loading}
                        className="w-full py-3 bg-primary hover:bg-primary/90 text-white rounded-full font-medium"
                    >
                        {loading ? 'Enregistrement...' : 'Enregistrer'}
                    </Button>
                </form>
            </div>
        </div>
    );
}
