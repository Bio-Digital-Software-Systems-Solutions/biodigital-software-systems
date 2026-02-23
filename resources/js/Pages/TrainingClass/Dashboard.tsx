import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Button } from '@/Components/ui/button';
import { apiLogger } from '@/utils/logger';
import {
    BookOpen,
    Calendar,
    Users,
    BarChart3,
    UserCheck,
    Plus,
    UserPlus
} from 'lucide-react';
import { TrainingClass, Training, Teacher, Statistics } from './types';
import ClassesView from './Components/ClassesView';
import StudentsView from './Components/StudentsView';
import ScheduleView from './Components/ScheduleView';
import AttendanceView from './Components/AttendanceView';
import StatsView from './Components/StatsView';
import EnrollmentsView from './Components/EnrollmentsView';
import AddClassModal from './Components/AddClassModal';
import axios from 'axios';

interface PaginatedData<T> {
    data: T[];
    links: any[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
}

interface Props {
    classes: PaginatedData<TrainingClass>;
    trainings: Training[];
    teachers: Teacher[];
    filters: Record<string, any>;
}

export default function Dashboard({ classes: initialClasses, trainings, teachers, filters }: Props) {
    const [activeTab, setActiveTab] = useState<'classes' | 'students' | 'schedule' | 'attendance' | 'enrollments' | 'stats'>('classes');
    const [classes, setClasses] = useState<TrainingClass[]>(initialClasses.data);
    const [showAddClassModal, setShowAddClassModal] = useState(false);
    const [statistics, setStatistics] = useState<Statistics | null>(null);

    // Fetch statistics on mount
    useEffect(() => {
        fetchStatistics();
    }, []);

    const fetchStatistics = async () => {
        try {
            const response = await axios.get(route('training-classes.statistics'));
            setStatistics(response.data);
        } catch (error) {
            apiLogger.error('Error fetching statistics:', error);
        }
    };

    const handleClassAdded = (newClass: TrainingClass) => {
        setClasses([...classes, newClass]);
        setShowAddClassModal(false);
        fetchStatistics(); // Refresh statistics
    };

    const handleClassUpdated = (updatedClass: TrainingClass) => {
        setClasses(classes.map(c => c.id === updatedClass.id ? updatedClass : c));
        fetchStatistics(); // Refresh statistics
    };

    const handleClassDeleted = (classUuid: string) => {
        setClasses(classes.filter(c => c.uuid !== classUuid));
        fetchStatistics(); // Refresh statistics
    };

    const tabs = [
        { id: 'classes' as const, label: 'Classes', icon: BookOpen },
        { id: 'students' as const, label: 'Étudiants', icon: Users },
        { id: 'schedule' as const, label: 'Emploi du temps', icon: Calendar },
        { id: 'attendance' as const, label: 'Présences', icon: UserCheck },
        { id: 'enrollments' as const, label: 'Inscriptions', icon: UserPlus },
        { id: 'stats' as const, label: 'Statistiques', icon: BarChart3 }
    ];

    return (
        <DashboardLayout
            title="Gestion des Classes"
            description="Gérez vos classes de formation"
            actions={
                activeTab === 'classes' ? (
                    <Button onClick={() => setShowAddClassModal(true)}>
                        <Plus className="h-5 w-5 mr-2" />
                        Nouvelle Classe
                    </Button>
                ) : undefined
            }
        >
            <Head title="Gestion des Classes - TrainingClass Dashboard" />

            {/* Navigation Tabs */}
            <nav className="bg-white dark:bg-gray-800 shadow-sm -mx-4 -mt-6">
                <div className="px-4">
                    <div className="flex space-x-8">
                        {tabs.map(({ id, label, icon: Icon }) => (
                            <button
                                key={id}
                                onClick={() => setActiveTab(id)}
                                className={`flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
                                    activeTab === id
                                        ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                }`}
                            >
                                <Icon size={18} />
                                {label}
                            </button>
                        ))}
                    </div>
                </div>
            </nav>

            {/* Main Content */}
            <main className="py-8">
                {activeTab === 'classes' && (
                    <div className="space-y-6">
                        <ClassesView
                            classes={classes}
                            trainings={trainings}
                            teachers={teachers}
                            onClassUpdated={handleClassUpdated}
                            onClassDeleted={handleClassDeleted}
                            onClassAdded={handleClassAdded}
                        />

                        {/* Pagination */}
                        {initialClasses.data.length > 0 && initialClasses.meta?.last_page > 1 && (
                            <div className="flex justify-center">
                                <nav className="flex space-x-2">
                                    {initialClasses.links.map((link, index) => {
                                        if (!link.url) {
                                            return (
                                                <span
                                                    key={index}
                                                    className="px-3 py-2 text-sm font-medium rounded-lg cursor-not-allowed opacity-50 text-gray-500 dark:text-gray-400"
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            );
                                        }

                                        return (
                                            <Link
                                                key={index}
                                                href={link.url}
                                                preserveState
                                                className={`px-3 py-2 text-sm font-medium rounded-lg ${
                                                    link.active
                                                        ? 'bg-primary text-white'
                                                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        );
                                    })}
                                </nav>
                            </div>
                        )}
                    </div>
                )}
                {activeTab === 'students' && (
                    <StudentsView classes={classes} trainings={trainings} />
                )}
                {activeTab === 'schedule' && (
                    <ScheduleView />
                )}
                {activeTab === 'attendance' && (
                    <AttendanceView classes={classes} />
                )}
                {activeTab === 'enrollments' && (
                    <EnrollmentsView trainings={trainings} />
                )}
                {activeTab === 'stats' && statistics && (
                    <StatsView statistics={statistics} />
                )}
            </main>

            {/* Modals */}
            {showAddClassModal && (
                <AddClassModal
                    trainings={trainings}
                    teachers={teachers}
                    onClose={() => setShowAddClassModal(false)}
                    onClassAdded={handleClassAdded}
                />
            )}
        </DashboardLayout>
    );
}
