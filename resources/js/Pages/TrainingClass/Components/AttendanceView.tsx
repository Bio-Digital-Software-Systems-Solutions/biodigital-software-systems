import { useState, useEffect, useMemo } from 'react';
import { Button } from '@/Components/ui/button';
import { UserCheck, UserX, Search } from 'lucide-react';
import { TrainingClass, Student, AttendanceRecord } from '../types';
import axios from 'axios';
import { toast } from 'sonner';
import { apiLogger } from '@/utils/logger';

interface Props {
    classes: TrainingClass[];
}

export default function AttendanceView({ classes }: Props) {
    const [selectedClass, setSelectedClass] = useState<string>('');
    const [students, setStudents] = useState<Student[]>([]);
    const [attendance, setAttendance] = useState<Record<number, AttendanceRecord>>({});
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    useEffect(() => {
        if (selectedClass) {
            fetchStudents(selectedClass);
        }
    }, [selectedClass]);

    const fetchStudents = async (classId: string) => {
        setLoading(true);
        try {
            const response = await axios.get(route('training-classes.students', classId));
            const data: Student[] = response.data;
            setStudents(data);

            // Initialize attendance from existing data
            const initialAttendance: Record<number, AttendanceRecord> = {};
            data.forEach(student => {
                initialAttendance[student.id] = {
                    student_id: student.id,
                    status: (student.attendance_status as 'present' | 'absent' | 'excused') || 'present',
                    reason: student.attendance_reason || '',
                };
            });
            setAttendance(initialAttendance);
        } catch (error) {
            apiLogger.error('Error fetching students:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleAttendanceChange = (studentId: number, status: 'present' | 'absent' | 'excused') => {
        setAttendance(prev => ({
            ...prev,
            [studentId]: { ...prev[studentId], student_id: studentId, status }
        }));
    };

    const handleReasonChange = (studentId: number, reason: string) => {
        setAttendance(prev => ({
            ...prev,
            [studentId]: { ...prev[studentId], reason }
        }));
    };

    const handleSave = async () => {
        if (!selectedClass) return;

        setSaving(true);
        try {
            const attendances = Object.values(attendance);
            await axios.post(route('training-classes.attendance', selectedClass), { attendances });
            toast.success('Présences enregistrées avec succès', {
                description: 'Les présences ont été mises à jour pour cette classe.',
            });
        } catch (error) {
            apiLogger.error('Error saving attendance:', error);
            toast.error('Erreur lors de l\'enregistrement', {
                description: 'Une erreur est survenue lors de l\'enregistrement des présences.',
            });
        } finally {
            setSaving(false);
        }
    };

    // Filter classes based on search term
    const filteredClasses = useMemo(() => {
        if (!searchTerm) return classes;

        const search = searchTerm.toLowerCase();
        return classes.filter(cls =>
            cls.training_name.toLowerCase().includes(search) ||
            new Date(cls.date).toLocaleDateString('fr-FR').includes(search)
        );
    }, [classes, searchTerm]);

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Contrôle des Présences</h2>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Sélectionnez une classe
                </label>

                {/* Search input */}
                <div className="relative w-full md:w-96">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Rechercher une classe..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                    />
                </div>

                {/* Dropdown select with filtered options */}
                <select
                    value={selectedClass}
                    onChange={(e) => setSelectedClass(e.target.value)}
                    className="w-full md:w-96 px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                    size={Math.min(10, filteredClasses.length + 1)}
                >
                    <option value="">✓ Choisir une classe...</option>
                    {filteredClasses.map((cls) => (
                        <option key={cls.id} value={cls.uuid}>
                            {cls.training_name} - {new Date(cls.date).toLocaleDateString('fr-FR')}
                        </option>
                    ))}
                </select>

                {searchTerm && filteredClasses.length === 0 && (
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Aucune classe trouvée pour "{searchTerm}"
                    </p>
                )}
            </div>

            {loading ? (
                <div className="text-center py-12">
                    <p className="text-gray-600 dark:text-gray-400">Chargement...</p>
                </div>
            ) : students.length > 0 ? (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Étudiant</th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Présent</th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Absent</th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Excusé</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Raison</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {students.map((student) => (
                                    <tr key={student.id}>
                                        <td className="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">{student.name}</td>
                                        <td className="px-6 py-4 text-center">
                                            <input
                                                type="radio"
                                                name={`attendance-${student.id}`}
                                                checked={attendance[student.id]?.status === 'present'}
                                                onChange={() => handleAttendanceChange(student.id, 'present')}
                                                className="w-4 h-4"
                                            />
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <input
                                                type="radio"
                                                name={`attendance-${student.id}`}
                                                checked={attendance[student.id]?.status === 'absent'}
                                                onChange={() => handleAttendanceChange(student.id, 'absent')}
                                                className="w-4 h-4"
                                            />
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <input
                                                type="radio"
                                                name={`attendance-${student.id}`}
                                                checked={attendance[student.id]?.status === 'excused'}
                                                onChange={() => handleAttendanceChange(student.id, 'excused')}
                                                className="w-4 h-4"
                                            />
                                        </td>
                                        <td className="px-6 py-4">
                                            <input
                                                type="text"
                                                value={attendance[student.id]?.reason || ''}
                                                onChange={(e) => handleReasonChange(student.id, e.target.value)}
                                                placeholder="Raison (si absent)"
                                                className="w-full px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                                disabled={attendance[student.id]?.status === 'present'}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-6 py-4 bg-gray-50 dark:bg-gray-900">
                        <Button onClick={handleSave} disabled={saving}>
                            {saving ? 'Enregistrement...' : 'Enregistrer les présences'}
                        </Button>
                    </div>
                </div>
            ) : selectedClass ? (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <p className="text-gray-600 dark:text-gray-400">Aucun étudiant trouvé</p>
                </div>
            ) : null}
        </div>
    );
}
