import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps, User } from '@/Types';
import { ArrowLeftIcon, MapPinIcon, PhotoIcon, VideoCameraIcon } from '@heroicons/react/24/outline';
import UserMultiSelect from '@/Components/UserMultiSelect';
import { EventMediaUploader, EventMediaGallery, EventBanner } from '@/Components/Events';
import { EventMedia } from '@/Types/event.d';

interface Event {
    id: number;
    uuid: string;
    title: string;
    description?: string;
    start_date: string;
    end_date: string;
    location?: string;
    max_participants?: number;
    is_public: boolean;
    status: string;
    participants?: User[];
    media?: EventMedia[];
    address?: {
        street?: string;
        city?: string;
        postal_code?: string;
        country?: string;
    };
}

interface Props extends PageProps {
    event: Event;
    banners?: EventMedia[];
    galleryImages?: EventMedia[];
    galleryVideos?: EventMedia[];
}

export default function Edit({ event, banners = [], galleryImages = [], galleryVideos = [] }: Props) {
    const [showAddress, setShowAddress] = useState(!!event.address && Object.values(event.address).some(value => value));
    const [showMediaSection, setShowMediaSection] = useState(false);
    const [currentMedia, setCurrentMedia] = useState<EventMedia[]>(event.media || []);
    const [currentBanners, setCurrentBanners] = useState<EventMedia[]>(banners);

    const handleMediaUploadComplete = (uploadedMedia: EventMedia[]) => {
        setCurrentMedia((prev) => [...prev, ...uploadedMedia]);
        // Refresh the page to get updated data
        router.reload({ only: ['event', 'banners', 'galleryImages', 'galleryVideos'] });
    };

    const handleMediaDeleted = (mediaId: number) => {
        setCurrentMedia((prev) => prev.filter((m) => m.id !== mediaId));
        setCurrentBanners((prev) => prev.filter((m) => m.id !== mediaId));
    };

    const handleMediaUpdated = (updatedMedia: EventMedia) => {
        setCurrentMedia((prev) =>
            prev.map((m) => (m.id === updatedMedia.id ? updatedMedia : m))
        );
        if (updatedMedia.collection === 'banner') {
            setCurrentBanners((prev) => {
                const filtered = prev.filter((m) => m.id !== updatedMedia.id);
                return [...filtered, updatedMedia];
            });
        } else {
            setCurrentBanners((prev) => prev.filter((m) => m.id !== updatedMedia.id));
        }
    };

    const { data, setData, put, processing, errors } = useForm({
        title: event.title || '',
        description: event.description || '',
        start_date: event.start_date || '',
        end_date: event.end_date || '',
        location: event.location || '',
        max_participants: event.max_participants?.toString() || '',
        is_public: event.is_public ?? true,
        status: event.status || 'planned',
        participant_ids: event.participants?.map(p => p.id) || [] as number[],
        address: {
            street: event.address?.street || '',
            city: event.address?.city || '',
            postal_code: event.address?.postal_code || '',
            country: event.address?.country || '',
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('events.update', event.uuid));
    };

    const formatDatetimeLocal = (date: string) => {
        if (!date) return '';
        return new Date(date).toISOString().slice(0, 16);
    };

    const handleDateTimeChange = (field: 'start_date' | 'end_date', value: string) => {
        setData(field, value);
    };

    return (
        <DashboardLayout>
            <Head title={`Modifier l'Événement: ${event.title}`} />

            <div className="p-4">
                <div className="mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('events.index')}
                            className="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Retour aux événements
                        </Link>
                    </div>

                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h1 className="text-lg font-medium text-gray-900 dark:text-white">
                                Modifier l'événement : {event.title}
                            </h1>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Basic Information */}
                            <div>
                                <label htmlFor="title" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Titre *
                                </label>
                                <input
                                    type="text"
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    placeholder="Titre de l'événement"
                                    required
                                />
                                {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                            </div>

                            <div>
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    rows={4}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    placeholder="Description de l'événement..."
                                />
                                {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                            </div>

                            {/* Date and Time */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="start_date" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Date et heure de début *
                                    </label>
                                    <input
                                        type="datetime-local"
                                        id="start_date"
                                        value={formatDatetimeLocal(data.start_date)}
                                        onChange={(e) => handleDateTimeChange('start_date', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        required
                                    />
                                    {errors.start_date && <p className="mt-1 text-sm text-red-600">{errors.start_date}</p>}
                                </div>

                                <div>
                                    <label htmlFor="end_date" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Date et heure de fin *
                                    </label>
                                    <input
                                        type="datetime-local"
                                        id="end_date"
                                        value={formatDatetimeLocal(data.end_date)}
                                        onChange={(e) => handleDateTimeChange('end_date', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        required
                                    />
                                    {errors.end_date && <p className="mt-1 text-sm text-red-600">{errors.end_date}</p>}
                                </div>
                            </div>

                            {/* Location */}
                            <div>
                                <label htmlFor="location" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Lieu
                                </label>
                                <input
                                    type="text"
                                    id="location"
                                    value={data.location}
                                    onChange={(e) => setData('location', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    placeholder="Nom du lieu ou description courte"
                                />
                                {errors.location && <p className="mt-1 text-sm text-red-600">{errors.location}</p>}
                            </div>

                            {/* Address Section */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Adresse détaillée
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => setShowAddress(!showAddress)}
                                        className="inline-flex items-center text-sm text-primary dark:text-blue-400 hover:text-primary dark:hover:text-blue-300"
                                    >
                                        <MapPinIcon className="h-4 w-4 mr-1" />
                                        {showAddress ? 'Masquer l\'adresse' : 'Ajouter une adresse'}
                                    </button>
                                </div>

                                {showAddress && (
                                    <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border border-gray-200 dark:border-gray-700 rounded-md">
                                        <div className="md:col-span-2">
                                            <label htmlFor="address.street" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Rue
                                            </label>
                                            <input
                                                type="text"
                                                id="address.street"
                                                value={data.address.street}
                                                onChange={(e) => setData('address', { ...data.address, street: e.target.value })}
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                                placeholder="Numéro et nom de rue"
                                            />
                                        </div>

                                        <div>
                                            <label htmlFor="address.city" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Ville
                                            </label>
                                            <input
                                                type="text"
                                                id="address.city"
                                                value={data.address.city}
                                                onChange={(e) => setData('address', { ...data.address, city: e.target.value })}
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                                placeholder="Ville"
                                            />
                                        </div>

                                        <div>
                                            <label htmlFor="address.postal_code" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Code postal
                                            </label>
                                            <input
                                                type="text"
                                                id="address.postal_code"
                                                value={data.address.postal_code}
                                                onChange={(e) => setData('address', { ...data.address, postal_code: e.target.value })}
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                                placeholder="Code postal"
                                            />
                                        </div>

                                        <div className="md:col-span-2">
                                            <label htmlFor="address.country" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Pays
                                            </label>
                                            <input
                                                type="text"
                                                id="address.country"
                                                value={data.address.country}
                                                onChange={(e) => setData('address', { ...data.address, country: e.target.value })}
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                                placeholder="Pays"
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Media Section */}
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        <PhotoIcon className="h-5 w-5" />
                                        Images & Vidéos
                                    </h3>
                                    <button
                                        type="button"
                                        onClick={() => setShowMediaSection(!showMediaSection)}
                                        className="text-sm text-primary dark:text-blue-400 hover:text-primary/80"
                                    >
                                        {showMediaSection ? 'Masquer' : 'Afficher'}
                                    </button>
                                </div>

                                {showMediaSection && (
                                    <div className="space-y-6">
                                        {/* Banner Section */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Banner / Flyer
                                            </label>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <EventBanner
                                                        banner={currentBanners[0]}
                                                        eventTitle={event.title}
                                                    />
                                                </div>
                                                <div>
                                                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                                        Uploader un nouveau banner
                                                    </p>
                                                    <EventMediaUploader
                                                        eventUuid={event.uuid}
                                                        collection="banner"
                                                        acceptedTypes="images"
                                                        onUploadComplete={handleMediaUploadComplete}
                                                        maxFiles={1}
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        {/* Gallery Upload Section */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Ajouter des photos et vidéos à la galerie
                                            </label>
                                            <EventMediaUploader
                                                eventUuid={event.uuid}
                                                collection="gallery"
                                                onUploadComplete={handleMediaUploadComplete}
                                                maxFiles={20}
                                            />
                                        </div>

                                        {/* Gallery */}
                                        {currentMedia.length > 0 && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Galerie ({currentMedia.length} médias)
                                                </label>
                                                <EventMediaGallery
                                                    media={currentMedia}
                                                    eventUuid={event.uuid}
                                                    canEdit={true}
                                                    onMediaDeleted={handleMediaDeleted}
                                                    onMediaUpdated={handleMediaUpdated}
                                                />
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Participants Selection */}
                            <div>
                                <UserMultiSelect
                                    selectedUserIds={data.participant_ids}
                                    onChange={(userIds) => setData('participant_ids', userIds)}
                                    error={errors.participant_ids}
                                    label="Participants"
                                    placeholder="Rechercher des participants..."
                                />
                            </div>

                            {/* Event Settings */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label htmlFor="max_participants" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Nombre maximum de participants
                                    </label>
                                    <input
                                        type="number"
                                        id="max_participants"
                                        value={data.max_participants}
                                        onChange={(e) => setData('max_participants', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        placeholder="Illimité si vide"
                                        min="1"
                                    />
                                    {errors.max_participants && <p className="mt-1 text-sm text-red-600">{errors.max_participants}</p>}
                                </div>

                                <div>
                                    <label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Statut
                                    </label>
                                    <select
                                        id="status"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                        required
                                    >
                                        <option value="planned">Planifié</option>
                                        <option value="ongoing">En cours</option>
                                        <option value="completed">Terminé</option>
                                        <option value="cancelled">Annulé</option>
                                    </select>
                                    {errors.status && <p className="mt-1 text-sm text-red-600">{errors.status}</p>}
                                </div>
                            </div>

                            <div className="flex items-center justify-center">
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={data.is_public}
                                        onChange={(e) => setData('is_public', e.target.checked)}
                                        className="rounded border-gray-300 text-primary shadow-sm focus:ring-primary dark:border-gray-600 dark:bg-gray-700"
                                    />
                                    <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Événement public
                                    </span>
                                </label>
                            </div>

                            {/* Info Box */}
                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm text-primary dark:text-blue-300">
                                            <strong>Note :</strong> Modifier le statut de l'événement peut affecter les notifications envoyées aux participants.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <Link
                                    href={route('events.index')}
                                    className="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    Annuler
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-primary hover:bg-primary disabled:bg-blue-300 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                >
                                    {processing ? 'Mise à jour...' : 'Mettre à jour l\'événement'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}