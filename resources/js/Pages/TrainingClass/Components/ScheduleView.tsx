import { useState, useEffect } from 'react';
import { Schedule } from '../types';
import axios from 'axios';
import { apiLogger } from '@/utils/logger';
import { Link } from '@inertiajs/react';

export default function ScheduleView() {
    const [schedules, setSchedules] = useState<Schedule[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchSchedules();
    }, []);

    const fetchSchedules = async () => {
        try {
            const response = await axios.get(route('training-classes.schedules'));
            setSchedules(response.data);
        } catch (error) {
            apiLogger.error('Error fetching schedules:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-600 dark:text-gray-400">Chargement...</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Emplois du Temps</h2>

            {schedules.length === 0 ? (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <p className="text-gray-600 dark:text-gray-400">Aucun horaire disponible</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {schedules.map((schedule) => (
                        <div key={schedule.training_id} className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                                {schedule.training_name}
                            </h3>
                            <div className="space-y-3">
                                {schedule.classes.map((cls) => (
                                    <div
                                        key={cls.id}
                                        className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                    >
                                        <div>
                                            <Link
                                                href={route('training-classes.show', cls.uuid)}
                                                className="font-medium text-gray-900 dark:text-white hover:text-primary dark:hover:text-primary-light transition-colors duration-200 cursor-pointer"
                                            >
                                                {cls.name}
                                            </Link>
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                {cls.day}
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {new Date(cls.date).toLocaleDateString()} • {cls.start_time} - {cls.end_time}
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {cls.room} • {cls.teacher}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
