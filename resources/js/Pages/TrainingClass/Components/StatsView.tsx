import { BookOpen, Users, BarChart3, Clock } from 'lucide-react';
import { Statistics } from '../types';
import { formatNumber } from '@/lib/utils';

interface Props {
    statistics: Statistics;
}

export default function StatsView({ statistics }: Props) {
    const stats = [
        {
            label: 'Classes actives',
            value: statistics.total_classes,
            icon: BookOpen,
            color: 'blue',
        },
        {
            label: 'Total étudiants',
            value: statistics.total_students,
            icon: Users,
            color: 'green',
        },
        {
            label: 'Moyenne générale',
            value: statistics.average_grade,
            icon: BarChart3,
            color: 'yellow',
        },
        {
            label: 'Taux de présence',
            value: `${statistics.attendance_rate}%`,
            icon: Clock,
            color: 'purple',
        },
    ];

    const colorClasses = {
        blue: 'bg-blue-100 dark:bg-blue-900/30 text-primary dark:text-blue-400',
        green: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
        yellow: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
        purple: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    };

    return (
        <div className="space-y-6">
            <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Statistiques</h2>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {stats.map((stat, index) => (
                    <div key={index} className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div className="flex items-center gap-3">
                            <div className={`p-3 rounded-lg ${colorClasses[stat.color as keyof typeof colorClasses]}`}>
                                <stat.icon size={24} />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">{stat.label}</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">{stat.value}</p>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Grade Distribution */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                        Répartition des notes par formation
                    </h3>
                    <div className="space-y-4">
                        {statistics.grade_distribution.map((dist) => (
                            <div key={dist.training_id}>
                                <div className="flex justify-between text-sm mb-1">
                                    <span className="text-gray-900 dark:text-white">{dist.training_name}</span>
                                    <span className="text-gray-600 dark:text-gray-400">
                                        {formatNumber(dist.average_grade)}/100
                                    </span>
                                </div>
                                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div
                                        className="bg-violet-600 h-2 rounded-full"
                                        style={{ width: `${dist.average_grade}%` }}
                                    ></div>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {dist.students_count} étudiant{dist.students_count > 1 ? 's' : ''}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Top Students */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Top étudiants</h3>
                    <div className="space-y-3">
                        {statistics.top_students.map((student, index) => (
                            <div key={student.id} className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center text-sm font-medium text-gray-900 dark:text-white">
                                        {index + 1}
                                    </div>
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">{student.name}</p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">{student.email}</p>
                                    </div>
                                </div>
                                <span className="font-semibold text-gray-900 dark:text-white">
                                    {formatNumber(student.average_grade)}/100
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
