import { useState, useEffect, useMemo } from 'react';
import { Button } from '@/Components/ui/button';
import { UserCircle, ChevronDown, ChevronUp, Calendar, Search } from 'lucide-react';
import { TrainingClass, Training } from '../types';
import axios from 'axios';
import { formatNumber } from '@/lib/utils';
import { apiLogger } from '@/utils/logger';

interface Props {
    classes: TrainingClass[];
    trainings: Training[];
}

interface StudentDetail {
    id: number;
    name: string;
    email: string;
    grade: number | null;
    progress: number | null;
    attendance_rate: number;
    training_id: number;
    training_name: string;
}

interface AttendanceHistory {
    class_id: number;
    class_date: string;
    start_time: string;
    end_time: string;
    room: string | null;
    status: 'present' | 'absent' | 'excused' | null;
    reason: string | null;
}

export default function StudentsView({ classes, trainings }: Props) {
    const [selectedTraining, setSelectedTraining] = useState<string>('');
    const [students, setStudents] = useState<StudentDetail[]>([]);
    const [expandedStudent, setExpandedStudent] = useState<number | null>(null);
    const [attendanceHistory, setAttendanceHistory] = useState<Record<number, AttendanceHistory[]>>({});
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    useEffect(() => {
        if (selectedTraining) {
            fetchStudents(selectedTraining);
        }
    }, [selectedTraining]);

    const fetchStudents = async (trainingId: string) => {
        setLoading(true);
        try {
            const response = await axios.get(route('training-classes.training-students', trainingId));
            setStudents(response.data);
        } catch (error) {
            apiLogger.error('Error fetching students:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchAttendanceHistory = async (studentId: number, trainingId: number) => {
        try {
            const response = await axios.get(route('training-classes.student-attendance-history', {
                student: studentId,
                training: trainingId
            }));
            setAttendanceHistory(prev => ({
                ...prev,
                [studentId]: response.data
            }));
        } catch (error) {
            apiLogger.error('Error fetching attendance history:', error);
        }
    };

    const toggleStudentExpand = (studentId: number, trainingId: number) => {
        if (expandedStudent === studentId) {
            setExpandedStudent(null);
        } else {
            setExpandedStudent(studentId);
            if (!attendanceHistory[studentId]) {
                fetchAttendanceHistory(studentId, trainingId);
            }
        }
    };

    const getStatusBadge = (status: 'present' | 'absent' | 'excused' | null) => {
        if (status === 'present') {
            return <span className="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Présent</span>;
        }
        if (status === 'absent') {
            return <span className="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Absent</span>;
        }
        if (status === 'excused') {
            return <span className="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Excusé</span>;
        }
        return <span className="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Non marqué</span>;
    };

    const getGradeColor = (grade: number | null) => {
        if (!grade) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        if (grade >= 85) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        if (grade >= 70) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    };

    const getAttendanceRateColor = (rate: number) => {
        if (rate >= 90) return 'text-green-600 dark:text-green-400 font-semibold';
        if (rate >= 75) return 'text-yellow-600 dark:text-yellow-400 font-semibold';
        return 'text-red-600 dark:text-red-400 font-semibold';
    };

    // Filter trainings based on search term
    const filteredTrainings = useMemo(() => {
        if (!searchTerm) return trainings;

        const search = searchTerm.toLowerCase();
        return trainings.filter(training =>
            training.title.toLowerCase().includes(search)
        );
    }, [trainings, searchTerm]);

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Gestion des Étudiants</h2>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Sélectionnez une formation
                </label>

                {/* Search input */}
                <div className="relative w-full md:w-96">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Rechercher une formation..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                    />
                </div>

                {/* Dropdown select with filtered options */}
                <select
                    value={selectedTraining}
                    onChange={(e) => setSelectedTraining(e.target.value)}
                    className="w-full md:w-96 px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                    size={Math.min(10, filteredTrainings.length + 1)}
                >
                    <option value="">✓ Choisir une formation...</option>
                    {filteredTrainings.map((training) => (
                        <option key={training.id} value={training.id}>
                            {training.title}
                        </option>
                    ))}
                </select>

                {searchTerm && filteredTrainings.length === 0 && (
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Aucune formation trouvée pour "{searchTerm}"
                    </p>
                )}
            </div>

            {loading ? (
                <div className="text-center py-12">
                    <p className="text-gray-600 dark:text-gray-400">Chargement...</p>
                </div>
            ) : students.length > 0 ? (
                <div className="space-y-4">
                    {students.map((student) => (
                        <div key={student.id} className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            {/* Student Header */}
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-4 flex-1">
                                        <div className="w-12 h-12 bg-violet-100 dark:bg-violet-900 rounded-full flex items-center justify-center">
                                            <UserCircle className="w-8 h-8 text-violet-600 dark:text-violet-400" />
                                        </div>
                                        <div className="flex-1">
                                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                                {student.name}
                                            </h3>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {student.email}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-6">
                                        <div className="text-center">
                                            <p className="text-xs text-gray-500 dark:text-gray-400 uppercase">Note</p>
                                            <span className={`px-3 py-1 rounded-full text-sm font-medium ${getGradeColor(student.grade)}`}>
                                                {student.grade ? `${student.grade}/100` : 'N/A'}
                                            </span>
                                        </div>

                                        <div className="text-center">
                                            <p className="text-xs text-gray-500 dark:text-gray-400 uppercase">Présence</p>
                                            <p className={`text-2xl ${getAttendanceRateColor(student.attendance_rate)}`}>
                                                {formatNumber(student.attendance_rate)}%
                                            </p>
                                        </div>

                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => toggleStudentExpand(student.id, student.training_id)}
                                        >
                                            {expandedStudent === student.id ? (
                                                <>
                                                    <ChevronUp className="w-4 h-4 mr-1" />
                                                    Masquer
                                                </>
                                            ) : (
                                                <>
                                                    <ChevronDown className="w-4 h-4 mr-1" />
                                                    Historique
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            {/* Attendance History */}
                            {expandedStudent === student.id && (
                                <div className="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-6">
                                    <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                        <Calendar className="w-4 h-4 mr-2" />
                                        Historique des présences
                                    </h4>

                                    {attendanceHistory[student.id] && attendanceHistory[student.id].length > 0 ? (
                                        <div className="space-y-2">
                                            {attendanceHistory[student.id].map((record, index) => (
                                                <div
                                                    key={index}
                                                    className="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg"
                                                >
                                                    <div className="flex-1">
                                                        <p className="font-medium text-gray-900 dark:text-white">
                                                            {new Date(record.class_date).toLocaleDateString('fr-FR', {
                                                                weekday: 'long',
                                                                year: 'numeric',
                                                                month: 'long',
                                                                day: 'numeric'
                                                            })}
                                                        </p>
                                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                                            {record.start_time} - {record.end_time}
                                                            {record.room && ` • Salle ${record.room}`}
                                                        </p>
                                                        {record.reason && (
                                                            <p className="text-sm text-gray-500 dark:text-gray-400 italic mt-1">
                                                                Raison: {record.reason}
                                                            </p>
                                                        )}
                                                    </div>
                                                    <div>
                                                        {getStatusBadge(record.status)}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500 dark:text-gray-400 text-center py-4">
                                            Aucun enregistrement de présence pour le moment
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            ) : selectedTraining ? (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <p className="text-gray-600 dark:text-gray-400">Aucun étudiant inscrit à cette formation</p>
                </div>
            ) : null}
        </div>
    );
}
