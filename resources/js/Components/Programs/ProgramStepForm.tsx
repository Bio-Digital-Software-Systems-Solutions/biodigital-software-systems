import { useState, FormEvent } from 'react';
import { useForm, router } from '@inertiajs/react';
import { XMarkIcon, TrashIcon, UserPlusIcon } from '@heroicons/react/24/outline';

interface ProgramStepFormProps {
    programId: number;
    onClose: () => void;
    step?: any;
    users?: any[];
    participants?: any[];
}

export default function ProgramStepForm({ programId, onClose, step, users = [], participants = [] }: ProgramStepFormProps) {
    const isEditing = !!step;
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

    const { data, setData, post, patch, processing, errors } = useForm({
        name: step?.name || '',
        description: step?.description || '',
        order_index: step?.order_index || 1,
        start_datetime: step?.start_datetime ? new Date(step.start_datetime).toISOString().slice(0, 16) : '',
        end_datetime: step?.end_datetime ? new Date(step.end_datetime).toISOString().slice(0, 16) : '',
        duration_minutes: step?.duration_minutes || 60,
        status: step?.status || 'pending',
    });

    const handleDelete = () => {
        router.delete(route('programs.steps.destroy', { program: programId, step: step.id }), {
            onSuccess: () => onClose(),
        });
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (isEditing) {
            patch(route('programs.steps.update', { program: programId, step: step.id }), {
                onSuccess: () => onClose(),
            });
        } else {
            post(route('programs.steps.store', { program: programId }), {
                onSuccess: () => onClose(),
            });
        }
    };

    // Calculate duration when dates change
    const handleDateChange = (field: 'start_datetime' | 'end_datetime', value: string) => {
        const newData = { ...data, [field]: value };

        if (newData.start_datetime && newData.end_datetime) {
            const start = new Date(newData.start_datetime);
            const end = new Date(newData.end_datetime);
            const durationMs = end.getTime() - start.getTime();
            const durationMinutes = Math.floor(durationMs / (1000 * 60));

            if (durationMinutes > 0) {
                setData({ ...newData, duration_minutes: durationMinutes });
            } else {
                setData(newData);
            }
        } else {
            setData(newData);
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div className="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        {isEditing ? 'Modifier l\'étape' : 'Ajouter une étape'}
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                    >
                        <XMarkIcon className="w-6 h-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-6">
                    {/* Name */}
                    <div>
                        <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nom de l'étape <span className="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary focus:ring-primary"
                            required
                        />
                        {errors.name && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                    </div>

                    {/* Description */}
                    <div>
                        <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Description
                        </label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={3}
                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary focus:ring-primary"
                        />
                        {errors.description && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.description}</p>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Order Index */}
                        <div>
                            <label htmlFor="order_index" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Ordre <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="order_index"
                                type="number"
                                min="1"
                                value={data.order_index}
                                onChange={(e) => setData('order_index', parseInt(e.target.value))}
                                className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary focus:ring-primary"
                                required
                            />
                            {errors.order_index && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.order_index}</p>}
                        </div>

                        {/* Status */}
                        <div>
                            <label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Statut <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="status"
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary focus:ring-primary"
                                required
                            >
                                <option value="pending">En attente</option>
                                <option value="in_progress">En cours</option>
                                <option value="completed">Terminé</option>
                                <option value="cancelled">Annulé</option>
                            </select>
                            {errors.status && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.status}</p>}
                        </div>
                    </div>

                    {/* Dates */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label htmlFor="start_datetime" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Date/Heure de début <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="start_datetime"
                                type="datetime-local"
                                value={data.start_datetime}
                                onChange={(e) => handleDateChange('start_datetime', e.target.value)}
                                className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary focus:ring-primary"
                                required
                            />
                            {errors.start_datetime && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.start_datetime}</p>}
                        </div>

                        <div>
                            <label htmlFor="end_datetime" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Date/Heure de fin <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="end_datetime"
                                type="datetime-local"
                                value={data.end_datetime}
                                onChange={(e) => handleDateChange('end_datetime', e.target.value)}
                                className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary focus:ring-primary"
                                required
                            />
                            {errors.end_datetime && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.end_datetime}</p>}
                        </div>
                    </div>

                    {/* Duration (auto-calculated) */}
                    <div>
                        <label htmlFor="duration_minutes" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Durée (minutes) <span className="text-red-500">*</span>
                        </label>
                        <input
                            id="duration_minutes"
                            type="number"
                            min="1"
                            value={data.duration_minutes}
                            onChange={(e) => setData('duration_minutes', parseInt(e.target.value))}
                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-primary focus:ring-primary"
                            required
                        />
                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Calculé automatiquement à partir des dates, mais peut être modifié manuellement
                        </p>
                        {errors.duration_minutes && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.duration_minutes}</p>}
                    </div>

                    {/* Form Actions */}
                    <div className="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            {isEditing && (
                                <button
                                    type="button"
                                    onClick={() => setShowDeleteConfirm(true)}
                                    className="px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md hover:bg-red-100 dark:hover:bg-red-900/30 flex items-center gap-2"
                                >
                                    <TrashIcon className="w-4 h-4" />
                                    Supprimer
                                </button>
                            )}
                        </div>
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                onClick={onClose}
                                className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Enregistrement...' : isEditing ? 'Modifier' : 'Créer'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {/* Delete Confirmation Modal */}
            {showDeleteConfirm && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[60]">
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Confirmer la suppression
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Êtes-vous sûr de vouloir supprimer cette étape ? Cette action est irréversible et supprimera également toutes les tâches associées.
                        </p>
                        <div className="flex items-center justify-end gap-3">
                            <button
                                type="button"
                                onClick={() => setShowDeleteConfirm(false)}
                                className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                onClick={handleDelete}
                                className="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700"
                            >
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
