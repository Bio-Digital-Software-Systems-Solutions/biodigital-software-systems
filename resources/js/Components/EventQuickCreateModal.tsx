import React, { useState, useEffect } from 'react';
import Modal from '@/Components/Modal';
import { useForm } from '@inertiajs/react';
import { XMarkIcon, MapPinIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { registerLocale } from 'react-datepicker';
import UserSelector from '@/Components/UserSelector';
import DateTimePicker from '@/Components/DateTimePicker';

registerLocale('fr', fr);

type EventType = 'event' | 'task' | 'appointment';

interface User {
    id: number;
    name: string;
    first_name?: string;
    last_name?: string;
    email: string;
}

interface EventQuickCreateModalProps {
    show: boolean;
    onClose: () => void;
    initialDate?: Date;
    initialHour?: number;
}

export default function EventQuickCreateModal({
    show,
    onClose,
    initialDate,
    initialHour
}: EventQuickCreateModalProps) {
    const [eventType, setEventType] = useState<EventType>('event');
    const [showAddress, setShowAddress] = useState(false);
    const [selectedParticipants, setSelectedParticipants] = useState<User[]>([]);
    const [startDate, setStartDate] = useState<Date | null>(null);
    const [endDate, setEndDate] = useState<Date | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        type: 'event' as EventType,
        title: '',
        description: '',
        start_date: '',
        end_date: '',
        location: '',
        max_participants: '',
        is_public: true,
        participant_ids: [] as number[],
        address: {
            street: '',
            city: '',
            postal_code: '',
            country: '',
        },
    });

    // Initialize dates when modal opens
    useEffect(() => {
        if (show && initialDate) {
            const start = new Date(initialDate);
            if (initialHour !== undefined) {
                start.setHours(initialHour, 0, 0, 0);
            }

            const end = new Date(start);
            end.setHours(start.getHours() + 1); // Default 1 hour duration

            setStartDate(start);
            setEndDate(end);
        }
    }, [show, initialDate, initialHour]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!startDate || !endDate) {
            return;
        }

        // Submit with all data including participants
        post(route('events.store'), {
            data: {
                ...data,
                type: eventType,
                start_date: format(startDate, 'yyyy-MM-dd HH:mm:ss'),
                end_date: format(endDate, 'yyyy-MM-dd HH:mm:ss'),
                participant_ids: selectedParticipants.map(p => p.id),
            },
            onSuccess: () => {
                reset();
                setSelectedParticipants([]);
                setStartDate(null);
                setEndDate(null);
                onClose();
            },
        });
    };

    const handleClose = () => {
        reset();
        setEventType('event');
        setShowAddress(false);
        setSelectedParticipants([]);
        setStartDate(null);
        setEndDate(null);
        onClose();
    };

    return (
        <Modal show={show} onClose={handleClose} maxWidth="2xl">
            <div className="bg-white dark:bg-gray-800">
                {/* Header */}
                <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        onClick={handleClose}
                        className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                    >
                        <XMarkIcon className="h-6 w-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit}>
                    {/* Title */}
                    <div className="px-6 pt-4">
                        <input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="w-full text-2xl font-normal border-0 border-b-2 border-icc-blue focus:ring-0 focus:border-icc-blue bg-transparent dark:text-white placeholder-gray-400"
                            placeholder="Add title"
                            required
                        />
                        {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                    </div>

                    {/* Event Type Tabs */}
                    <div className="px-6 pt-4 flex gap-2">
                        <button
                            type="button"
                            onClick={() => setEventType('event')}
                            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                                eventType === 'event'
                                    ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                            }`}
                        >
                            Event
                        </button>
                        <button
                            type="button"
                            onClick={() => setEventType('task')}
                            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                                eventType === 'task'
                                    ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                            }`}
                        >
                            Task
                        </button>
                        <button
                            type="button"
                            onClick={() => setEventType('appointment')}
                            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors relative ${
                                eventType === 'appointment'
                                    ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                            }`}
                        >
                            Appointment schedule
                            <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                New
                            </span>
                        </button>
                    </div>

                    {/* Date and Time */}
                    <div className="px-6 pt-6 space-y-4">
                        <div className="flex items-center gap-4 text-sm text-gray-700 dark:text-gray-300">
                            <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div className="flex-1 grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        Date et heure de début *
                                    </label>
                                    <DateTimePicker
                                        selected={startDate}
                                        onChange={(date) => setStartDate(date)}
                                        minDate={new Date()}
                                        placeholderText="Sélectionner une date de début"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        Date et heure de fin *
                                    </label>
                                    <DateTimePicker
                                        selected={endDate}
                                        onChange={(date) => setEndDate(date)}
                                        minDate={startDate || new Date()}
                                        placeholderText="Sélectionner une date de fin"
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                        <div className="text-xs text-gray-500 dark:text-gray-400 pl-9">
                            Time zone • Does not repeat
                        </div>

                        {/* Add guests */}
                        <UserSelector
                            selectedUsers={selectedParticipants}
                            onUsersChange={setSelectedParticipants}
                        />

                        {/* Location */}
                        <div className="flex items-start gap-4 text-sm">
                            <MapPinIcon className="h-5 w-5 text-gray-400 mt-0.5" />
                            <div className="flex-1">
                                <input
                                    type="text"
                                    value={data.location}
                                    onChange={(e) => setData('location', e.target.value)}
                                    className="block w-full border-0 border-b border-gray-200 dark:border-gray-700 focus:ring-0 focus:border-icc-blue bg-transparent dark:text-white placeholder-gray-400 text-sm"
                                    placeholder="Add location"
                                />
                            </div>
                        </div>

                        {/* Description */}
                        <div className="flex items-start gap-4 text-sm">
                            <svg className="h-5 w-5 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <div className="flex-1">
                                <textarea
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={2}
                                    className="block w-full border-0 border-b border-gray-200 dark:border-gray-700 focus:ring-0 focus:border-icc-blue bg-transparent dark:text-white placeholder-gray-400 text-sm resize-none"
                                    placeholder="Add description or a Google Drive attachment"
                                />
                            </div>
                        </div>

                        {/* Address Section - Collapsible */}
                        <div>
                            <button
                                type="button"
                                onClick={() => setShowAddress(!showAddress)}
                                className="flex items-center gap-2 text-sm text-icc-blue hover:text-icc-blue/80 dark:text-icc-blue dark:hover:text-icc-blue/80"
                            >
                                <MapPinIcon className="h-4 w-4" />
                                {showAddress ? 'Masquer l\'adresse détaillée' : 'Ajouter une adresse'}
                            </button>

                            {showAddress && (
                                <div className="mt-4 ml-9 grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 dark:border-gray-700 rounded-md">
                                    <div className="md:col-span-2">
                                        <label htmlFor="address.street" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Rue
                                        </label>
                                        <input
                                            type="text"
                                            id="address.street"
                                            value={data.address.street}
                                            onChange={(e) => setData('address', { ...data.address, street: e.target.value })}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-icc-blue focus:ring-icc-blue sm:text-sm"
                                            placeholder="Numéro et nom de rue"
                                        />
                                    </div>

                                    <div>
                                        <label htmlFor="address.city" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Ville
                                        </label>
                                        <input
                                            type="text"
                                            id="address.city"
                                            value={data.address.city}
                                            onChange={(e) => setData('address', { ...data.address, city: e.target.value })}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-icc-blue focus:ring-icc-blue sm:text-sm"
                                            placeholder="Ville"
                                        />
                                    </div>

                                    <div>
                                        <label htmlFor="address.postal_code" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Code postal
                                        </label>
                                        <input
                                            type="text"
                                            id="address.postal_code"
                                            value={data.address.postal_code}
                                            onChange={(e) => setData('address', { ...data.address, postal_code: e.target.value })}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-icc-blue focus:ring-icc-blue sm:text-sm"
                                            placeholder="Code postal"
                                        />
                                    </div>

                                    <div className="md:col-span-2">
                                        <label htmlFor="address.country" className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Pays
                                        </label>
                                        <input
                                            type="text"
                                            id="address.country"
                                            value={data.address.country}
                                            onChange={(e) => setData('address', { ...data.address, country: e.target.value })}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-icc-blue focus:ring-icc-blue sm:text-sm"
                                            placeholder="Pays"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Max participants */}
                        <div className="flex items-center gap-4 text-sm">
                            <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <div className="flex-1">
                                <input
                                    type="number"
                                    value={data.max_participants}
                                    onChange={(e) => setData('max_participants', e.target.value)}
                                    className="block w-full border-0 border-b border-gray-200 dark:border-gray-700 focus:ring-0 focus:border-icc-blue bg-transparent dark:text-white placeholder-gray-400 text-sm"
                                    placeholder="Nombre maximum de participants"
                                    min="1"
                                />
                            </div>
                        </div>

                        {/* Public/Private */}
                        <div className="flex items-center gap-4">
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={data.is_public}
                                    onChange={(e) => setData('is_public', e.target.checked)}
                                    className="rounded border-gray-300 text-icc-blue shadow-sm focus:ring-icc-blue dark:border-gray-600 dark:bg-gray-700"
                                />
                                <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Événement public
                                </span>
                            </label>
                        </div>

                        {/* Info Note */}
                        <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-3">
                            <p className="text-xs text-primary dark:text-blue-300">
                                <strong>Note :</strong> Les événements publics sont visibles par tous les utilisateurs. Les événements privés ne sont visibles que par les participants invités.
                            </p>
                        </div>
                    </div>

                    {/* Footer Actions */}
                    <div className="px-6 py-4 mt-6 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <button
                            type="button"
                            className="text-sm text-icc-blue hover:underline"
                        >
                            More options
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-6 py-2 bg-icc-blue text-white rounded-full hover:bg-icc-blue/90 disabled:bg-icc-blue/50 text-sm font-medium transition-colors"
                        >
                            {processing ? 'Saving...' : 'Save'}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
