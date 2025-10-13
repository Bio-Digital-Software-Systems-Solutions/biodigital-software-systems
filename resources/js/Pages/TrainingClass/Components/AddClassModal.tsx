import { useState, FormEvent } from 'react';
import { Button } from '@/Components/ui/button';
import { X } from 'lucide-react';
import { Training, Teacher, TrainingClass } from '../types';
import axios from 'axios';
import { toast } from 'sonner';

interface Props {
    trainings: Training[];
    teachers: Teacher[];
    onClose: () => void;
    onClassAdded: (newClass: TrainingClass) => void;
}

interface DaySchedule {
    day: string;
    start_time: string;
    end_time: string;
}

export default function AddClassModal({ trainings, teachers, onClose, onClassAdded }: Props) {
    const [formData, setFormData] = useState({
        training_id: '',
        teacher_id: '',
        name: '',
        date: '',
        room: '',
        max_students: '',
        notes: '',
    });
    const [daySchedules, setDaySchedules] = useState<DaySchedule[]>([]);
    const [useGlobalTime, setUseGlobalTime] = useState(true);
    const [globalTime, setGlobalTime] = useState({ start_time: '', end_time: '' });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const daysOfWeek = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    const toggleDay = (day: string) => {
        setDaySchedules(prev => {
            const exists = prev.find(s => s.day === day);
            if (exists) {
                return prev.filter(s => s.day !== day);
            } else {
                // Use global time if enabled, otherwise empty strings
                const time = useGlobalTime
                    ? { start_time: globalTime.start_time, end_time: globalTime.end_time }
                    : { start_time: '', end_time: '' };
                return [...prev, { day, ...time }];
            }
        });
    };

    const updateDaySchedule = (day: string, field: 'start_time' | 'end_time', value: string) => {
        setDaySchedules(prev => prev.map(schedule =>
            schedule.day === day
                ? { ...schedule, [field]: value }
                : schedule
        ));
    };

    const updateGlobalTime = (field: 'start_time' | 'end_time', value: string) => {
        setGlobalTime(prev => ({ ...prev, [field]: value }));

        // If global time mode is enabled, update all selected days
        if (useGlobalTime) {
            setDaySchedules(prev => prev.map(schedule => ({
                ...schedule,
                [field]: value
            })));
        }
    };

    const toggleTimeMode = () => {
        const newMode = !useGlobalTime;
        setUseGlobalTime(newMode);

        // If switching to global mode, apply global time to all days
        if (newMode && globalTime.start_time && globalTime.end_time) {
            setDaySchedules(prev => prev.map(schedule => ({
                ...schedule,
                start_time: globalTime.start_time,
                end_time: globalTime.end_time
            })));
        }
    };

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        // Validate that all selected days have times
        const invalidDays = daySchedules.filter(d => !d.start_time || !d.end_time);
        if (invalidDays.length > 0) {
            setError(`Veuillez définir les horaires pour: ${invalidDays.map(d => d.day).join(', ')}`);
            setLoading(false);
            return;
        }

        try {
            const response = await axios.post(route('training-classes.store'), {
                training_id: formData.training_id,
                teacher_id: formData.teacher_id || null,
                name: formData.name,
                date: formData.date,
                room: formData.room || null,
                max_students: formData.max_students ? parseInt(formData.max_students) : null,
                notes: formData.notes || null,
                schedules: daySchedules.map(schedule => ({
                    day_of_week: schedule.day,
                    start_time: schedule.start_time,
                    end_time: schedule.end_time,
                    room: formData.room,
                })),
            });

            onClassAdded(response.data.class);
            onClose();
            toast.success('Classe créée avec succès', {
                description: 'La nouvelle classe et ses horaires ont été ajoutés.',
            });
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Une erreur est survenue');
            toast.error('Erreur lors de la création', {
                description: err instanceof Error ? err.message : 'Une erreur est survenue lors de la création de la classe.',
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white dark:bg-gray-800 rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div className="sticky top-0 bg-white dark:bg-gray-800 border-b dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Ajouter une nouvelle classe
                    </h3>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <X size={24} />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    {error && (
                        <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded">
                            {error}
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Formation *
                            </label>
                            <select
                                value={formData.training_id}
                                onChange={(e) => setFormData({ ...formData, training_id: e.target.value })}
                                className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                                required
                            >
                                <option value="">Sélectionnez une formation</option>
                                {trainings.map((training) => (
                                    <option key={training.id} value={training.id}>
                                        {training.title}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Enseignant
                            </label>
                            <select
                                value={formData.teacher_id}
                                onChange={(e) => setFormData({ ...formData, teacher_id: e.target.value })}
                                className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                            >
                                <option value="">Aucun enseignant assigné</option>
                                {teachers.map((teacher) => (
                                    <option key={teacher.id} value={teacher.id}>
                                        {teacher.first_name} {teacher.last_name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nom de la classe *
                            </label>
                            <input
                                type="text"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                placeholder="Ex: Classe Avancée - Groupe A"
                                className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                                required
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date *
                            </label>
                            <input
                                type="date"
                                value={formData.date}
                                onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                                className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                                required
                            />
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Salle
                            </label>
                            <input
                                type="text"
                                value={formData.room}
                                onChange={(e) => setFormData({ ...formData, room: e.target.value })}
                                placeholder="Ex: Salle A1"
                                className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre maximum d'étudiants
                            </label>
                            <input
                                type="number"
                                value={formData.max_students}
                                onChange={(e) => setFormData({ ...formData, max_students: e.target.value })}
                                min="1"
                                placeholder="Ex: 30"
                                className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                            />
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes
                            </label>
                            <textarea
                                value={formData.notes}
                                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                rows={3}
                                placeholder="Notes additionnelles..."
                                className="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                            />
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Jours de la semaine *
                            </label>
                            <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                                {daysOfWeek.map((day) => (
                                    <label
                                        key={day}
                                        className={`flex items-center gap-2 p-3 border-2 rounded-lg cursor-pointer transition-all ${
                                            daySchedules.some(s => s.day === day)
                                                ? 'border-violet-600 bg-violet-50 dark:bg-violet-900/20'
                                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                        }`}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={daySchedules.some(s => s.day === day)}
                                            onChange={() => toggleDay(day)}
                                            className="w-4 h-4 text-violet-600 focus:ring-violet-500 rounded"
                                        />
                                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {day}
                                        </span>
                                    </label>
                                ))}
                            </div>
                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Sélectionnez les jours où ce cours a lieu régulièrement
                            </p>
                        </div>

                        {daySchedules.length > 0 && (
                            <div className="md:col-span-2 space-y-4">
                                {/* Toggle between global and individual times */}
                                <div className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div>
                                        <label className="text-sm font-medium text-gray-900 dark:text-white">
                                            Horaire global pour tous les jours
                                        </label>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Utilisez le même horaire pour tous les jours sélectionnés
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={toggleTimeMode}
                                        className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                            useGlobalTime ? 'bg-violet-600' : 'bg-gray-300 dark:bg-gray-600'
                                        }`}
                                    >
                                        <span
                                            className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                                useGlobalTime ? 'translate-x-6' : 'translate-x-1'
                                            }`}
                                        />
                                    </button>
                                </div>

                                {/* Global time inputs */}
                                {useGlobalTime && (
                                    <div className="p-4 bg-violet-50 dark:bg-violet-900/20 rounded-lg border-2 border-violet-600">
                                        <label className="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                                            Horaire global *
                                        </label>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                    Heure de début
                                                </label>
                                                <input
                                                    type="time"
                                                    value={globalTime.start_time}
                                                    onChange={(e) => updateGlobalTime('start_time', e.target.value)}
                                                    className="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                    Heure de fin
                                                </label>
                                                <input
                                                    type="time"
                                                    value={globalTime.end_time}
                                                    onChange={(e) => updateGlobalTime('end_time', e.target.value)}
                                                    className="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            Cet horaire sera appliqué à tous les jours sélectionnés
                                        </p>
                                    </div>
                                )}

                                {/* Individual time inputs */}
                                {!useGlobalTime && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            Horaires pour chaque jour *
                                        </label>
                                        <div className="space-y-3">
                                            {daySchedules.map((schedule) => (
                                                <div key={schedule.day} className="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                                    <div className="flex items-center gap-4">
                                                        <div className="min-w-[100px]">
                                                            <span className="font-medium text-gray-900 dark:text-white">
                                                                {schedule.day}
                                                            </span>
                                                        </div>
                                                        <div className="flex-1 grid grid-cols-2 gap-3">
                                                            <div>
                                                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                                    Début
                                                                </label>
                                                                <input
                                                                    type="time"
                                                                    value={schedule.start_time}
                                                                    onChange={(e) => updateDaySchedule(schedule.day, 'start_time', e.target.value)}
                                                                    className="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                                                                    required
                                                                />
                                                            </div>
                                                            <div>
                                                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                                    Fin
                                                                </label>
                                                                <input
                                                                    type="time"
                                                                    value={schedule.end_time}
                                                                    onChange={(e) => updateDaySchedule(schedule.day, 'end_time', e.target.value)}
                                                                    className="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500"
                                                                    required
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            Définissez les horaires de début et de fin pour chaque jour sélectionné
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="flex gap-2 pt-4">
                        <Button type="submit" disabled={loading} className="flex-1">
                            {loading ? 'Création...' : 'Créer la classe'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                            className="flex-1"
                        >
                            Annuler
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
