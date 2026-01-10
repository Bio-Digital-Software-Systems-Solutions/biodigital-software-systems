import React, { useState, useEffect, FormEvent } from 'react';
import {
    XMarkIcon,
    MapPinIcon,
    CalendarIcon,
    ClockIcon,
    UserGroupIcon,
    PencilIcon,
    TrashIcon,
    CheckIcon,
    XCircleIcon
} from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
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

interface Participant {
    id: number;
    first_name: string;
    last_name: string;
    status: string;
}

interface Appointment {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    start_datetime: string;
    end_datetime: string;
    location?: string;
    status: string;
    type: string;
    visibility: string;
    max_participants?: number;
    organizer: {
        id: number;
        first_name: string;
        last_name: string;
    };
    participants: Participant[];
    participants_count: number;
    appointmentable_type: string;
    appointmentable?: {
        id: number;
        title?: string;
    };
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    onUpdate: () => void;
    onDelete: () => void;
    appointment: Appointment | null;
    projectId: number;
    projectUuid: string;
    tasks?: Task[];
    users?: User[];
    canEdit?: boolean;
}

type AppointmentType = 'individual' | 'group' | 'consultation' | 'meeting';
type AppointmentStatus = 'pending' | 'confirmed' | 'cancelled' | 'completed';

export default function AppointmentDetailModal({
    isOpen,
    onClose,
    onUpdate,
    onDelete,
    appointment,
    projectId,
    projectUuid,
    tasks = [],
    users = [],
    canEdit = true
}: Props) {
    const [isEditing, setIsEditing] = useState(false);
    const [loading, setLoading] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

    // Form state for editing
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [startDate, setStartDate] = useState('');
    const [startTime, setStartTime] = useState('');
    const [endDate, setEndDate] = useState('');
    const [endTime, setEndTime] = useState('');
    const [location, setLocation] = useState('');
    const [status, setStatus] = useState<AppointmentStatus>('pending');
    const [type, setType] = useState<AppointmentType>('meeting');
    const [isPublic, setIsPublic] = useState(true);
    const [maxParticipants, setMaxParticipants] = useState<number | ''>('');
    const [selectedParticipants, setSelectedParticipants] = useState<number[]>([]);
    const [showUserSearch, setShowUserSearch] = useState(false);
    const [userSearchTerm, setUserSearchTerm] = useState('');

    // Format date to local YYYY-MM-DD string
    const formatLocalDate = (dateStr: string): string => {
        const date = new Date(dateStr);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    // Format time from datetime string
    const formatTime = (dateStr: string): string => {
        const date = new Date(dateStr);
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    };

    // Initialize form when appointment changes or editing mode changes
    useEffect(() => {
        if (appointment && isOpen) {
            setTitle(appointment.title);
            setDescription(appointment.description || '');
            setStartDate(formatLocalDate(appointment.start_datetime));
            setStartTime(formatTime(appointment.start_datetime));
            setEndDate(formatLocalDate(appointment.end_datetime));
            setEndTime(formatTime(appointment.end_datetime));
            setLocation(appointment.location || '');
            setStatus(appointment.status as AppointmentStatus);
            setType(appointment.type as AppointmentType);
            setIsPublic(appointment.visibility === 'public');
            setMaxParticipants(appointment.max_participants || '');
            setSelectedParticipants(appointment.participants?.map(p => p.id) || []);
        }
    }, [appointment, isOpen]);

    // Reset editing state when modal closes
    useEffect(() => {
        if (!isOpen) {
            setIsEditing(false);
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
            if (user) return `${user.first_name} ${user.last_name}`;
            const participant = appointment?.participants?.find(p => p.id === id);
            if (participant) return `${participant.first_name} ${participant.last_name}`;
            return '';
        }).filter(Boolean);
    };

    const handleSave = async (e: FormEvent) => {
        e.preventDefault();

        if (!title.trim()) {
            toast.error('Le titre est requis');
            return;
        }

        if (!appointment) return;

        setLoading(true);

        try {
            const startDatetime = `${startDate}T${startTime}:00`;
            const endDatetime = `${endDate}T${endTime}:00`;

            const payload = {
                title,
                description,
                start_datetime: startDatetime,
                end_datetime: endDatetime,
                location,
                status,
                type,
                visibility: isPublic ? 'public' : 'private',
                max_participants: maxParticipants || null,
                participants: selectedParticipants,
            };

            await axios.patch(`/api/projects/${projectUuid}/appointments/${appointment.uuid}`, payload);

            toast.success('Rendez-vous mis à jour avec succès');
            setIsEditing(false);
            onUpdate();
        } catch (error: any) {
            console.error('Error updating appointment:', error);
            const message = error.response?.data?.message || 'Erreur lors de la mise à jour du rendez-vous';
            toast.error(message);
        } finally {
            setLoading(false);
        }
    };

    const handleDeleteClick = () => {
        setShowDeleteConfirm(true);
    };

    const handleDeleteConfirm = async () => {
        if (!appointment) return;

        setDeleting(true);

        try {
            await axios.delete(`/api/projects/${projectUuid}/appointments/${appointment.uuid}`);

            toast.success('Rendez-vous supprimé avec succès');
            setShowDeleteConfirm(false);
            onDelete();
            onClose();
        } catch (error: any) {
            console.error('Error deleting appointment:', error);
            const message = error.response?.data?.message || 'Erreur lors de la suppression du rendez-vous';
            toast.error(message);
        } finally {
            setDeleting(false);
        }
    };

    const handleStatusChange = async (newStatus: AppointmentStatus) => {
        if (!appointment) return;

        setLoading(true);

        try {
            await axios.patch(`/api/projects/${projectUuid}/appointments/${appointment.uuid}`, {
                status: newStatus
            });

            toast.success('Statut mis à jour');
            onUpdate();
        } catch (error: any) {
            console.error('Error updating status:', error);
            toast.error('Erreur lors de la mise à jour du statut');
        } finally {
            setLoading(false);
        }
    };

    const formatDisplayDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const formatDisplayTime = (dateStr: string) => {
        return new Date(dateStr).toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getStatusColor = (appointmentStatus: string) => {
        switch (appointmentStatus) {
            case 'pending':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
            case 'confirmed':
                return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
            case 'cancelled':
                return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
            case 'completed':
                return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }
    };

    const getStatusLabel = (appointmentStatus: string) => {
        switch (appointmentStatus) {
            case 'pending': return 'En attente';
            case 'confirmed': return 'Confirmé';
            case 'cancelled': return 'Annulé';
            case 'completed': return 'Terminé';
            default: return appointmentStatus;
        }
    };

    const getTypeLabel = (appointmentType: string) => {
        switch (appointmentType) {
            case 'individual': return 'Individuel';
            case 'group': return 'Groupe';
            case 'consultation': return 'Consultation';
            case 'meeting': return 'Réunion';
            default: return appointmentType;
        }
    };

    if (!isOpen || !appointment) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/50"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        {isEditing ? 'Modifier le rendez-vous' : 'Détails du rendez-vous'}
                    </h2>
                    <div className="flex items-center gap-2">
                        {canEdit && !isEditing && (
                            <>
                                <button
                                    type="button"
                                    onClick={() => setIsEditing(true)}
                                    className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
                                    title="Modifier"
                                >
                                    <PencilIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                </button>
                                <button
                                    type="button"
                                    onClick={handleDeleteClick}
                                    disabled={deleting}
                                    className="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-full transition-colors"
                                    title="Supprimer"
                                >
                                    <TrashIcon className="h-5 w-5 text-red-500" />
                                </button>
                            </>
                        )}
                        <button
                            type="button"
                            onClick={onClose}
                            className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
                            title="Fermer"
                        >
                            <XMarkIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        </button>
                    </div>
                </div>

                {isEditing ? (
                    /* Edit Form */
                    <form onSubmit={handleSave} className="p-4 space-y-4">
                        {/* Title */}
                        <div>
                            <Label htmlFor="edit-title" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Titre
                            </Label>
                            <Input
                                id="edit-title"
                                type="text"
                                value={title}
                                onChange={(e) => setTitle(e.target.value)}
                                className="mt-1"
                            />
                        </div>

                        {/* Status */}
                        <div>
                            <Label htmlFor="edit-status" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Statut
                            </Label>
                            <select
                                id="edit-status"
                                value={status}
                                onChange={(e) => setStatus(e.target.value as AppointmentStatus)}
                                className="mt-1 w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm"
                            >
                                <option value="pending">En attente</option>
                                <option value="confirmed">Confirmé</option>
                                <option value="cancelled">Annulé</option>
                                <option value="completed">Terminé</option>
                            </select>
                        </div>

                        {/* Type */}
                        <div>
                            <Label className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">
                                Type
                            </Label>
                            <div className="flex gap-2 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                {(['individual', 'group', 'meeting'] as AppointmentType[]).map((t) => (
                                    <button
                                        key={t}
                                        type="button"
                                        onClick={() => setType(t)}
                                        className={`flex-1 py-2 px-3 text-sm font-medium rounded-md transition-colors ${
                                            type === t
                                                ? 'bg-white dark:bg-gray-600 text-primary shadow-sm'
                                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                        }`}
                                    >
                                        {getTypeLabel(t)}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Date & Time */}
                        <div className="space-y-3">
                            <Label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Date et heure
                            </Label>
                            <div className="grid grid-cols-2 gap-2">
                                <Input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => {
                                        setStartDate(e.target.value);
                                        if (!endDate || e.target.value > endDate) {
                                            setEndDate(e.target.value);
                                        }
                                    }}
                                />
                                <Input
                                    type="time"
                                    value={startTime}
                                    onChange={(e) => setStartTime(e.target.value)}
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <Input
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                    min={startDate}
                                />
                                <Input
                                    type="time"
                                    value={endTime}
                                    onChange={(e) => setEndTime(e.target.value)}
                                />
                            </div>
                        </div>

                        {/* Location */}
                        <div>
                            <Label htmlFor="edit-location" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Emplacement
                            </Label>
                            <Input
                                id="edit-location"
                                type="text"
                                value={location}
                                onChange={(e) => setLocation(e.target.value)}
                                className="mt-1"
                            />
                        </div>

                        {/* Description */}
                        <div>
                            <Label htmlFor="edit-description" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Description
                            </Label>
                            <Textarea
                                id="edit-description"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                rows={3}
                                className="mt-1"
                            />
                        </div>

                        {/* Participants */}
                        <div className="relative">
                            <Label className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">
                                Participants
                            </Label>
                            <button
                                type="button"
                                onClick={() => setShowUserSearch(!showUserSearch)}
                                className="text-sm text-primary hover:text-primary/80 transition-colors"
                            >
                                + Ajouter des participants
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
                                            >
                                                <XMarkIcon className="h-3 w-3" />
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            )}

                            {showUserSearch && (
                                <div className="absolute left-0 right-0 mt-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-10">
                                    <Input
                                        type="text"
                                        placeholder="Rechercher..."
                                        value={userSearchTerm}
                                        onChange={(e) => setUserSearchTerm(e.target.value)}
                                        className="border-0 border-b rounded-t-lg rounded-b-none"
                                        autoFocus
                                    />
                                    <div className="max-h-40 overflow-y-auto">
                                        {filteredUsers.slice(0, 5).map(user => (
                                            <button
                                                key={user.id}
                                                type="button"
                                                onClick={() => handleAddParticipant(user.id)}
                                                className="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600"
                                            >
                                                {user.first_name} {user.last_name}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Visibility & Max Participants */}
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex items-center gap-3">
                                <Label className="text-sm text-gray-600 dark:text-gray-400">
                                    Public
                                </Label>
                                <Switch
                                    checked={isPublic}
                                    onCheckedChange={setIsPublic}
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <Label className="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    Max
                                </Label>
                                <Input
                                    type="number"
                                    min="1"
                                    value={maxParticipants}
                                    onChange={(e) => setMaxParticipants(e.target.value ? Number(e.target.value) : '')}
                                    className="w-20 text-sm"
                                />
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex gap-3 pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsEditing(false)}
                                className="flex-1"
                            >
                                Annuler
                            </Button>
                            <Button
                                type="submit"
                                disabled={loading}
                                className="flex-1 bg-primary hover:bg-primary/90"
                            >
                                {loading ? 'Enregistrement...' : 'Enregistrer'}
                            </Button>
                        </div>
                    </form>
                ) : (
                    /* View Mode */
                    <div className="p-4 space-y-4">
                        {/* Title & Status */}
                        <div className="flex items-start justify-between gap-4">
                            <h3 className="text-xl font-semibold text-gray-900 dark:text-white">
                                {appointment.title}
                            </h3>
                            <span className={`px-3 py-1 text-sm rounded-full ${getStatusColor(appointment.status)}`}>
                                {getStatusLabel(appointment.status)}
                            </span>
                        </div>

                        {/* Type Badge */}
                        <div>
                            <span className="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                {getTypeLabel(appointment.type)}
                            </span>
                            <span className="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                {appointment.visibility === 'public' ? 'Public' : 'Privé'}
                            </span>
                        </div>

                        {/* Date & Time */}
                        <div className="flex items-start gap-3">
                            <CalendarIcon className="h-5 w-5 text-gray-400 mt-0.5" />
                            <div>
                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                    {formatDisplayDate(appointment.start_datetime)}
                                </p>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {formatDisplayTime(appointment.start_datetime)} - {formatDisplayTime(appointment.end_datetime)}
                                </p>
                            </div>
                        </div>

                        {/* Location */}
                        {appointment.location && (
                            <div className="flex items-start gap-3">
                                <MapPinIcon className="h-5 w-5 text-gray-400 mt-0.5" />
                                <p className="text-sm text-gray-700 dark:text-gray-300">
                                    {appointment.location}
                                </p>
                            </div>
                        )}

                        {/* Description */}
                        {appointment.description && (
                            <div className="pt-2 border-t border-gray-200 dark:border-gray-700">
                                <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                    {appointment.description}
                                </p>
                            </div>
                        )}

                        {/* Organizer */}
                        <div className="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">Organisateur</p>
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                                    <span className="text-xs font-medium text-primary">
                                        {appointment.organizer.first_name[0]}{appointment.organizer.last_name[0]}
                                    </span>
                                </div>
                                <span className="text-sm text-gray-900 dark:text-white">
                                    {appointment.organizer.first_name} {appointment.organizer.last_name}
                                </span>
                            </div>
                        </div>

                        {/* Participants */}
                        {appointment.participants && appointment.participants.length > 0 && (
                            <div className="pt-2 border-t border-gray-200 dark:border-gray-700">
                                <div className="flex items-center gap-2 mb-2">
                                    <UserGroupIcon className="h-4 w-4 text-gray-400" />
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {appointment.participants.length} participant{appointment.participants.length > 1 ? 's' : ''}
                                    </p>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {appointment.participants.map(participant => (
                                        <div
                                            key={participant.id}
                                            className="flex items-center gap-2 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full"
                                        >
                                            <div className="w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    {participant.first_name[0]}{participant.last_name[0]}
                                                </span>
                                            </div>
                                            <span className="text-xs text-gray-700 dark:text-gray-300">
                                                {participant.first_name} {participant.last_name}
                                            </span>
                                            <span className={`text-xs px-1.5 py-0.5 rounded ${
                                                participant.status === 'accepted' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' :
                                                participant.status === 'declined' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' :
                                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300'
                                            }`}>
                                                {participant.status === 'accepted' ? '✓' :
                                                 participant.status === 'declined' ? '✗' : '?'}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Quick Status Actions */}
                        {canEdit && appointment.status !== 'completed' && appointment.status !== 'cancelled' && (
                            <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">Actions rapides</p>
                                <div className="flex gap-2">
                                    {appointment.status === 'pending' && (
                                        <Button
                                            size="sm"
                                            onClick={() => handleStatusChange('confirmed')}
                                            disabled={loading}
                                            className="flex items-center gap-1 bg-green-600 hover:bg-green-700"
                                        >
                                            <CheckIcon className="h-4 w-4" />
                                            Confirmer
                                        </Button>
                                    )}
                                    {appointment.status === 'confirmed' && (
                                        <Button
                                            size="sm"
                                            onClick={() => handleStatusChange('completed')}
                                            disabled={loading}
                                            className="flex items-center gap-1 bg-gray-600 hover:bg-gray-700"
                                        >
                                            <CheckIcon className="h-4 w-4" />
                                            Terminer
                                        </Button>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleStatusChange('cancelled')}
                                        disabled={loading}
                                        className="flex items-center gap-1 text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    >
                                        <XCircleIcon className="h-4 w-4" />
                                        Annuler
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Delete Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showDeleteConfirm}
                onOpenChange={setShowDeleteConfirm}
                onConfirm={handleDeleteConfirm}
                title="Supprimer le rendez-vous"
                description={`Êtes-vous sûr de vouloir supprimer le rendez-vous "${appointment?.title}" ? Cette action est irréversible.`}
                isDeleting={deleting}
            />
        </div>
    );
}
