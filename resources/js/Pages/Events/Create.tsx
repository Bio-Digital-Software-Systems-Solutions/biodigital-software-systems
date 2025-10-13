import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/Types';
import { ArrowLeftIcon, MapPinIcon } from '@heroicons/react/24/outline';
import DateTimePicker from '@/Components/DateTimePicker';
import UserMultiSelect from '@/Components/UserMultiSelect';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { registerLocale } from 'react-datepicker';

registerLocale('fr', fr);

export default function Create() {
    const [showAddress, setShowAddress] = useState(false);
    const [startDate, setStartDate] = useState<Date | null>(null);
    const [endDate, setEndDate] = useState<Date | null>(null);

    const { data, setData, post, processing, errors, transform } = useForm({
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

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!startDate || !endDate) {
            return;
        }

        // Transform data before sending to include formatted dates
        transform((data) => ({
            ...data,
            start_date: format(startDate, 'yyyy-MM-dd HH:mm:ss'),
            end_date: format(endDate, 'yyyy-MM-dd HH:mm:ss'),
        }));

        // Submit the form
        post(route('events.store'));
    };

    return (
        <DashboardLayout>
            <Head title="Créer un Événement" />

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
                                Créer un nouvel événement
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
                                    <div className="mt-1">
                                        <DateTimePicker
                                            selected={startDate}
                                            onChange={(date) => setStartDate(date)}
                                            minDate={new Date()}
                                            placeholderText="Sélectionner une date de début"
                                            required
                                        />
                                    </div>
                                    {errors.start_date && <p className="mt-1 text-sm text-red-600">{errors.start_date}</p>}
                                </div>

                                <div>
                                    <label htmlFor="end_date" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Date et heure de fin *
                                    </label>
                                    <div className="mt-1">
                                        <DateTimePicker
                                            selected={endDate}
                                            onChange={(date) => setEndDate(date)}
                                            minDate={startDate || new Date()}
                                            placeholderText="Sélectionner une date de fin"
                                            required
                                        />
                                    </div>
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
                                            <strong>Note :</strong> Les événements publics sont visibles par tous les utilisateurs. Les événements privés ne sont visibles que par les participants invités.
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
                                    {processing ? 'Création...' : 'Créer l\'événement'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}