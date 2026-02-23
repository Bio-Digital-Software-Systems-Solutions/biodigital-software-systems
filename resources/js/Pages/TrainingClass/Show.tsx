import { useState, useEffect, useCallback, useRef } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Edit, Calendar, Clock, MapPin, Users, UserCheck, UserX, ChevronLeft, ChevronRight, UserPlus, Check, X, BookOpen, ClipboardList, FileText, Video, Headphones, Presentation, Download, File } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from '@/Components/ui/accordion';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';
import axios from 'axios';
import { toast } from 'sonner';
import { apiLogger } from '@/utils/logger';

interface Student {
    id: number;
    name: string;
    email: string;
    grade: number | null;
    progress: number | null;
    attendance_rate: number | null;
    attendance_status: 'present' | 'absent' | 'excused' | null;
    attendance_reason: string | null;
}

interface TrainingClass {
    id: number;
    uuid: string;
    training_id: number;
    training_name: string;
    teacher_id: number | null;
    teacher_name: string;
    date: string;
    start_time: string;
    end_time: string;
    room: string | null;
    max_students: number | null;
    notes: string | null;
    students_count: number;
    status: string;
}

interface PendingEnrollment {
    id: number;
    user_name: string;
    user_email: string;
    motivation: string | null;
    created_at: string;
}

interface Material {
    id: number;
    uuid: string;
    title: string;
    type: string;
    file_url: string | null;
    duration: string | null;
    description: string | null;
    is_active: boolean;
    uploaded_by_name: string | null;
}

interface Quiz {
    id: number;
    uuid: string;
    title: string;
    description: string | null;
    duration_minutes: number | null;
    max_score: number | null;
    passing_score: number | null;
    status: string;
    available_from: string | null;
    available_until: string | null;
}

interface Props {
    class: TrainingClass;
    students: Student[];
    pendingEnrollments: PendingEnrollment[];
    materials: Material[];
    quizzes: Quiz[];
}

export default function Show({ class: trainingClass, students: initialStudents, pendingEnrollments: initialPendingEnrollments, materials, quizzes }: Props) {
    const [students, setStudents] = useState<Student[]>(initialStudents);
    const [attendance, setAttendance] = useState<Record<number, { student_id: number; status: 'present' | 'absent' | 'excused'; reason?: string }>>(() => {
        const initial: Record<number, { student_id: number; status: 'present' | 'absent' | 'excused'; reason?: string }> = {};
        initialStudents.forEach(student => {
            if (student.attendance_status) {
                initial[student.id] = {
                    student_id: student.id,
                    status: student.attendance_status,
                    reason: student.attendance_reason || undefined,
                };
            }
        });
        return initial;
    });
    const [saving, setSaving] = useState(false);
    const [weekDays, setWeekDays] = useState<any[]>([]);
    const [selectedDay, setSelectedDay] = useState<string>('');
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [currentWeekStart, setCurrentWeekStart] = useState<Date>(() => {
        const classDate = new Date(trainingClass.date);
        const day = classDate.getDay();
        const diff = classDate.getDate() - day + (day === 0 ? -6 : 1); // Adjust to Monday
        return new Date(classDate.setDate(diff));
    });
    const [loadingDays, setLoadingDays] = useState(false);
    const [scheduleStudents, setScheduleStudents] = useState<any[]>([]);
    const [loadingScheduleStudents, setLoadingScheduleStudents] = useState(false);
    const previousScheduleIdRef = useRef<string | null>(null);
    const [pendingEnrollments, setPendingEnrollments] = useState<PendingEnrollment[]>(initialPendingEnrollments);
    const [processingIds, setProcessingIds] = useState<number[]>([]);
    const [showRejectDialog, setShowRejectDialog] = useState(false);
    const [selectedEnrollmentId, setSelectedEnrollmentId] = useState<number | null>(null);
    const [rejectionReason, setRejectionReason] = useState('');

    // Fetch week days schedule on mount
    useEffect(() => {
        fetchWeekSchedule();
    }, []);

    // Fetch students when selected day changes
    useEffect(() => {
        if (selectedDay && weekDays.length > 0) {
            const selectedDayData = weekDays.find(d => d.day_name === selectedDay);
            const scheduleUuid = selectedDayData?.has_schedule ? selectedDayData.uuid : null;

            // Only fetch if schedule UUID changed
            if (scheduleUuid !== previousScheduleIdRef.current) {
                previousScheduleIdRef.current = scheduleUuid;
                if (scheduleUuid) {
                    fetchScheduleStudents(scheduleUuid);
                } else {
                    setScheduleStudents([]);
                    setAttendance({});
                }
            }
        }
    }, [selectedDay, weekDays]);

    // Generate week dates
    const getWeekDates = (weekStart: Date) => {
        const dates = [];
        for (let i = 0; i < 7; i++) {
            const date = new Date(weekStart);
            date.setDate(weekStart.getDate() + i);
            dates.push(date);
        }
        return dates;
    };

    const navigateWeek = (direction: 'prev' | 'next') => {
        const newWeekStart = new Date(currentWeekStart);
        newWeekStart.setDate(currentWeekStart.getDate() + (direction === 'next' ? 7 : -7));
        setCurrentWeekStart(newWeekStart);
        setSelectedDay('');
        setSelectedDate(null);
    };

    const fetchWeekSchedule = async () => {
        setLoadingDays(true);
        try {
            const response = await axios.get(route('training-classes.week-schedule', trainingClass.uuid));
            const days = response.data;

            setWeekDays(days);

            // Auto-select current day if it exists
            const currentDayName = new Date(trainingClass.date).toLocaleDateString('fr-FR', { weekday: 'long' });
            const dayNameCapitalized = currentDayName.charAt(0).toUpperCase() + currentDayName.slice(1);
            const currentDay = days.find((day: any) => day.day_name.toLowerCase() === dayNameCapitalized.toLowerCase());
            if (currentDay) {
                setSelectedDay(currentDay.day_name);
            } else {
                // Default to first day with schedule, or just first day
                const dayWithSchedule = days.find((day: any) => day.has_schedule);
                setSelectedDay(dayWithSchedule?.day_name || days[0]?.day_name);
            }
        } catch (error) {
            apiLogger.error('Error fetching week schedule:', error);
            // Fallback to default days
            const defaultDays = [
                { day_name: 'Lundi', has_schedule: false },
                { day_name: 'Mardi', has_schedule: false },
                { day_name: 'Mercredi', has_schedule: false },
                { day_name: 'Jeudi', has_schedule: false },
                { day_name: 'Vendredi', has_schedule: false },
                { day_name: 'Samedi', has_schedule: false },
                { day_name: 'Dimanche', has_schedule: false },
            ];
            setWeekDays(defaultDays);
            setSelectedDay(defaultDays[0].day_name);
        } finally {
            setLoadingDays(false);
        }
    };

    const fetchScheduleStudents = useCallback(async (scheduleUuid: string) => {
        setLoadingScheduleStudents(true);
        try {
            const response = await axios.get(route('training-class-schedules.attendance', scheduleUuid));
            setScheduleStudents(response.data);

            // Update attendance state with schedule students
            const newAttendance: any = {};
            response.data.forEach((student: any) => {
                if (student.attendance_status) {
                    newAttendance[student.id] = {
                student_id: student.id,
                status: student.attendance_status,
                reason: student.attendance_reason || undefined,
                    };
                }
            });
            setAttendance(newAttendance);
        } catch (error) {
            apiLogger.error('Error fetching schedule students:', error);
            setScheduleStudents([]);
        } finally {
            setLoadingScheduleStudents(false);
        }
    }, []);

    const handleApproveEnrollment = async (enrollmentId: number) => {
        setProcessingIds(prev => [...prev, enrollmentId]);
        try {
            await axios.post(route('training-enrollments.approve', enrollmentId));
            setPendingEnrollments(prev => prev.filter(e => e.id !== enrollmentId));
            toast.success('Inscription approuvée', {
                description: 'L\'étudiant a été inscrit à la formation.',
            });
            router.reload({ only: ['students'] });
        } catch (error) {
            apiLogger.error('Error approving enrollment:', error);
            toast.error('Erreur lors de l\'approbation');
        } finally {
            setProcessingIds(prev => prev.filter(id => id !== enrollmentId));
        }
    };

    const handleRejectClick = (enrollmentId: number) => {
        setSelectedEnrollmentId(enrollmentId);
        setRejectionReason('');
        setShowRejectDialog(true);
    };

    const handleRejectConfirm = async () => {
        if (!selectedEnrollmentId) return;

        setProcessingIds(prev => [...prev, selectedEnrollmentId]);
        try {
            await axios.post(route('training-enrollments.reject', selectedEnrollmentId), {
                rejection_reason: rejectionReason,
            });
            setPendingEnrollments(prev => prev.filter(e => e.id !== selectedEnrollmentId));
            setShowRejectDialog(false);
            setSelectedEnrollmentId(null);
            toast.success('Inscription refusée', {
                description: 'L\'étudiant a été notifié du refus.',
            });
        } catch (error) {
            apiLogger.error('Error rejecting enrollment:', error);
            toast.error('Erreur lors du refus');
        } finally {
            setProcessingIds(prev => prev.filter(id => id !== selectedEnrollmentId));
        }
    };

    const getStatusColor = (status: string) => {
        return status === 'À venir'
            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    };

    // Calculate real-time attendance rate for a student
    const calculateAttendanceRate = (studentId: number): number => {
        const currentAttendance = attendance[studentId];
        if (!currentAttendance) return 0;

        // For now, we'll use a simple calculation based on current status
        // This could be enhanced to fetch historical data
        return currentAttendance.status === 'present' ? 100 : 0;
    };

    const handleAttendanceChange = (studentId: number, status: 'present' | 'absent' | 'excused') => {
        setAttendance(prev => ({
            ...prev,
            [studentId]: { student_id: studentId, status }
        }));
    };

    const handleReasonChange = (studentId: number, reason: string) => {
        setAttendance(prev => ({
            ...prev,
            [studentId]: { ...prev[studentId], reason }
        }));
    };

    const handleSaveAttendance = async () => {
        setSaving(true);
        try {
            const selectedDayData = weekDays.find(d => d.day_name === selectedDay);
            if (!selectedDayData || !selectedDayData.uuid) {
                toast.error('Erreur', {
                    description: 'Aucun horaire sélectionné pour ce jour.',
                });
                return;
            }

            const attendances = Object.values(attendance);
            await axios.post(route('training-class-schedules.mark-attendance', selectedDayData.uuid), { attendances });
            toast.success('Présences enregistrées avec succès', {
                description: `Les présences ont été mises à jour pour ${selectedDay}.`,
            });

            // Refresh schedule students
            fetchScheduleStudents(selectedDayData.uuid);
        } catch (error) {
            apiLogger.error('Error saving attendance:', error);
            toast.error('Erreur lors de l\'enregistrement', {
                description: 'Une erreur est survenue lors de l\'enregistrement des présences.',
            });
        } finally {
            setSaving(false);
        }
    };

    const getAttendanceStats = () => {
        const currentStudents = scheduleStudents.length > 0 ? scheduleStudents : students;
        const total = currentStudents.length;
        const present = Object.values(attendance).filter((a: any) => a.status === 'present').length;
        const absent = Object.values(attendance).filter((a: any) => a.status === 'absent').length;
        const excused = Object.values(attendance).filter((a: any) => a.status === 'excused').length;

        return { total, present, absent, excused };
    };

    const stats = getAttendanceStats();

    return (
        <DashboardLayout>
            <div className="mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="mb-6">
                    <Link
                href={route('training-classes.index')}
                className="inline-flex items-center text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300 mb-4"
                    >
                <ArrowLeft className="w-4 h-4 mr-2" />
                Retour aux classes
                    </Link>

                    <div className="flex justify-between items-start">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        {trainingClass.training_name}
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400 mt-1">
                        {trainingClass.teacher_name}
                    </p>
                </div>
                <span
                    className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(
                        trainingClass.status
                    )}`}
                >
                    {trainingClass.status}
                </span>
                    </div>
                </div>

                {/* Class Information */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div className="flex items-center">
                    <Calendar className="w-8 h-8 text-violet-600 dark:text-violet-400 mr-3" />
                    <div>
                        <p className="text-sm text-gray-600 dark:text-gray-400">Date</p>
                        <p className="text-lg font-semibold text-gray-900 dark:text-white">
                            {new Date(trainingClass.date).toLocaleDateString('fr-FR')}
                        </p>
                    </div>
                </div>
                    </div>

            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div className="flex items-center">
                    <Clock className="w-8 h-8 text-violet-600 dark:text-violet-400 mr-3" />
                    <div>
                        <p className="text-sm text-gray-600 dark:text-gray-400">Horaire</p>
                        <p className="text-lg font-semibold text-gray-900 dark:text-white">
                            {trainingClass.start_time} - {trainingClass.end_time}
                        </p>
                    </div>
                </div>
                    </div>

            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div className="flex items-center">
                    <MapPin className="w-8 h-8 text-violet-600 dark:text-violet-400 mr-3" />
                    <div>
                        <p className="text-sm text-gray-600 dark:text-gray-400">Salle</p>
                        <p className="text-lg font-semibold text-gray-900 dark:text-white">
                            {trainingClass.room || 'Non spécifiée'}
                        </p>
                    </div>
                </div>
                    </div>

            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div className="flex items-center">
                    <Users className="w-8 h-8 text-violet-600 dark:text-violet-400 mr-3" />
                    <div>
                        <p className="text-sm text-gray-600 dark:text-gray-400">Étudiants</p>
                        <p className="text-lg font-semibold text-gray-900 dark:text-white">
                            {trainingClass.students_count}
                            {trainingClass.max_students && ` / ${trainingClass.max_students}`}
                        </p>
                    </div>
                </div>
                    </div>
                </div>

                {/* Pending Enrollments Accordion */}
                {pendingEnrollments.length > 0 && (
                    <div className="mb-6">
                        <Accordion>
                            <AccordionItem value="pending-enrollments" className="bg-white dark:bg-gray-800 rounded-lg shadow border border-amber-200 dark:border-amber-800">
                                <AccordionTrigger>
                                    <div className="flex items-center gap-3">
                                        <UserPlus className="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                        <span>Inscriptions en attente</span>
                                        <span className="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-amber-500 rounded-full">
                                            {pendingEnrollments.length}
                                        </span>
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent>
                                    <div className="space-y-3">
                                        {pendingEnrollments.map((enrollment) => (
                                            <div
                                                key={enrollment.id}
                                                className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {enrollment.user_name}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                        {enrollment.user_email}
                                                    </p>
                                                    {enrollment.motivation && (
                                                        <p className="text-xs text-gray-600 dark:text-gray-300 mt-1 italic">
                                                            {enrollment.motivation}
                                                        </p>
                                                    )}
                                                    <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                        {new Date(enrollment.created_at).toLocaleDateString('fr-FR')}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2 ml-4">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="text-green-600 border-green-300 hover:bg-green-50 dark:text-green-400 dark:border-green-700 dark:hover:bg-green-900/20"
                                                        onClick={() => handleApproveEnrollment(enrollment.id)}
                                                        disabled={processingIds.includes(enrollment.id)}
                                                        title="Approuver"
                                                    >
                                                        <Check className="w-4 h-4" />
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-700 dark:hover:bg-red-900/20"
                                                        onClick={() => handleRejectClick(enrollment.id)}
                                                        disabled={processingIds.includes(enrollment.id)}
                                                        title="Refuser"
                                                    >
                                                        <X className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </div>
                )}

                {/* Course Materials Accordion */}
                <div className="mb-6">
                    <Accordion>
                        <AccordionItem value="course-materials" className="bg-white dark:bg-gray-800 rounded-lg shadow border border-blue-200 dark:border-blue-800">
                            <AccordionTrigger>
                                <div className="flex items-center gap-3">
                                    <BookOpen className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    <span>Supports de cours</span>
                                    <span className="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-blue-500 rounded-full">
                                        {materials.length}
                                    </span>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent>
                                {materials.length === 0 ? (
                                    <div className="text-center py-6 text-gray-500 dark:text-gray-400">
                                        <BookOpen className="w-8 h-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">Aucun support de cours pour cette classe.</p>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {materials.map((material) => {
                                            const TypeIcon = material.type === 'pdf' ? FileText
                                                : material.type === 'video' ? Video
                                                : material.type === 'audio' ? Headphones
                                                : material.type === 'powerpoint' ? Presentation
                                                : File;

                                            return (
                                                <div
                                                    key={material.id}
                                                    className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                                >
                                                    <div className="flex-shrink-0 mt-0.5">
                                                        <TypeIcon className="w-5 h-5 text-blue-500 dark:text-blue-400" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {material.title}
                                                        </p>
                                                        {material.description && (
                                                            <p className="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                                {material.description}
                                                            </p>
                                                        )}
                                                        <div className="flex items-center gap-3 mt-1">
                                                            <span className="text-xs text-gray-400 dark:text-gray-500 uppercase">
                                                                {material.type}
                                                            </span>
                                                            {material.duration && (
                                                                <span className="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                                                    <Clock className="w-3 h-3" />
                                                                    {material.duration}
                                                                </span>
                                                            )}
                                                            {material.uploaded_by_name && (
                                                                <span className="text-xs text-gray-400 dark:text-gray-500">
                                                                    Par {material.uploaded_by_name}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                    {material.file_url && (
                                                        <a
                                                            href={material.file_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="flex-shrink-0 p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                            title="Télécharger"
                                                        >
                                                            <Download className="w-4 h-4" />
                                                        </a>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </div>

                {/* Quizzes & Evaluations Accordion */}
                <div className="mb-6">
                    <Accordion>
                        <AccordionItem value="quizzes-evaluations" className="bg-white dark:bg-gray-800 rounded-lg shadow border border-purple-200 dark:border-purple-800">
                            <AccordionTrigger>
                                <div className="flex items-center gap-3">
                                    <ClipboardList className="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                    <span>Quiz et évaluations</span>
                                    <span className="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-purple-500 rounded-full">
                                        {quizzes.length}
                                    </span>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent>
                                {quizzes.length === 0 ? (
                                    <div className="text-center py-6 text-gray-500 dark:text-gray-400">
                                        <ClipboardList className="w-8 h-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">Aucun quiz ou évaluation pour cette classe.</p>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {quizzes.map((quiz) => (
                                            <div
                                                key={quiz.id}
                                                className="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                            >
                                                <div className="flex items-start justify-between">
                                                    <div className="min-w-0 flex-1">
                                                        <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {quiz.title}
                                                        </p>
                                                        {quiz.description && (
                                                            <p className="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                                {quiz.description}
                                                            </p>
                                                        )}
                                                        <div className="flex flex-wrap items-center gap-3 mt-2">
                                                            {quiz.duration_minutes && (
                                                                <span className="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                                    <Clock className="w-3 h-3" />
                                                                    {quiz.duration_minutes} min
                                                                </span>
                                                            )}
                                                            {quiz.max_score !== null && (
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                                    Max : {quiz.max_score} pts
                                                                </span>
                                                            )}
                                                            {quiz.passing_score !== null && (
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                                    Seuil : {quiz.passing_score} pts
                                                                </span>
                                                            )}
                                                            {quiz.available_from && (
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                                    Du {new Date(quiz.available_from).toLocaleDateString('fr-FR')}
                                                                </span>
                                                            )}
                                                            {quiz.available_until && (
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                                    au {new Date(quiz.available_until).toLocaleDateString('fr-FR')}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <span className={`flex-shrink-0 ml-3 px-2 py-1 text-xs font-medium rounded-full ${
                                                        quiz.status === 'published'
                                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                            : quiz.status === 'draft'
                                                            ? 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300'
                                                            : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                    }`}>
                                                        {quiz.status === 'published' ? 'Publié'
                                                            : quiz.status === 'draft' ? 'Brouillon'
                                                            : quiz.status}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </div>

                {/* Notes */}
                {trainingClass.notes && (
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Notes
                </h2>
                <p className="text-gray-600 dark:text-gray-400">
                    {trainingClass.notes}
                </p>
                    </div>
                )}

                {/* Schedule Section - Attendance Statistics */}
                {students.length > 0 && (
                    <>
                <div className="mb-6">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Statistiques de Présence
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-gray-600 dark:text-gray-400">Total</p>
                            <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                {stats.total}
                            </p>
                        </div>
                        <Users className="w-8 h-8 text-gray-400" />
                    </div>
                </div>

                <div className="bg-green-50 dark:bg-green-900/20 rounded-lg shadow p-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-green-600 dark:text-green-400">Présents</p>
                            <p className="text-2xl font-bold text-green-700 dark:text-green-300">
                                {stats.present}
                            </p>
                        </div>
                        <UserCheck className="w-8 h-8 text-green-600 dark:text-green-400" />
                    </div>
                </div>

                <div className="bg-red-50 dark:bg-red-900/20 rounded-lg shadow p-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-red-600 dark:text-red-400">Absents</p>
                            <p className="text-2xl font-bold text-red-700 dark:text-red-300">
                                {stats.absent}
                            </p>
                        </div>
                        <UserX className="w-8 h-8 text-red-600 dark:text-red-400" />
                    </div>
                </div>

                <div className="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow p-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-yellow-600 dark:text-yellow-400">Excusés</p>
                            <p className="text-2xl font-bold text-yellow-700 dark:text-yellow-300">
                                {stats.excused}
                            </p>
                        </div>
                        <UserCheck className="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
                    </div>
                </div>
                </div>
                </div>

                {/* Calendrier hebdomadaire */}
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                            Calendrier des présences
                        </h2>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => navigateWeek('prev')}
                                className="flex items-center gap-1"
                            >
                                <ChevronLeft className="h-4 w-4" />
                                Semaine précédente
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => navigateWeek('next')}
                                className="flex items-center gap-1"
                            >
                                Semaine suivante
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    {loadingDays ? (
                        <div className="text-center py-8">
                            <p className="text-gray-600 dark:text-gray-400">Chargement...</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {/* Month/Year header */}
                            <div className="text-center">
                                <p className="text-lg font-medium text-gray-700 dark:text-gray-300">
                                    {currentWeekStart.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}
                                </p>
                            </div>

                            {/* Week view */}
                            <div className="grid grid-cols-7 gap-2">
                                {getWeekDates(currentWeekStart).map((date, index) => {
                                    const dayName = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'][index];
                                    const dayData = weekDays.find(d => d.day_name === dayName);
                                    const isSelected = selectedDate?.toDateString() === date.toDateString();
                                    const isToday = new Date().toDateString() === date.toDateString();

                                    return (
                                        <button
                                            key={index}
                                            onClick={() => {
                                                setSelectedDate(date);
                                                setSelectedDay(dayName);
                                            }}
                                            className={`p-4 rounded-lg border-2 transition-all text-center ${
                                                isSelected
                                                    ? 'border-violet-600 bg-violet-50 dark:bg-violet-900/20 dark:border-violet-400'
                                                    : isToday
                                                    ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400'
                                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600'
                                            }`}
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    {dayName.substring(0, 3)}
                                                </p>
                                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                                    {date.getDate()}
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    Présences
                                                </p>
                                                {dayData?.has_schedule && (
                                                    <span className="px-2 py-1 bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300 text-xs font-medium rounded-full">
                                                        ✓
                                                    </span>
                                                )}
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>

                            {selectedDate && (
                                <div className="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                        Date sélectionnée : {selectedDate.toLocaleDateString('fr-FR', {
                                            weekday: 'long',
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric'
                                        })}
                                    </p>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Display attendance for selected day */}
                    {selectedDay && weekDays.find(d => d.day_name === selectedDay)?.has_schedule && (() => {
                        const selectedDayData = weekDays.find(d => d.day_name === selectedDay);
                        const startTime = selectedDayData?.start_time ? new Date(`2000-01-01T${selectedDayData.start_time}`).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '';
                        const endTime = selectedDayData?.end_time ? new Date(`2000-01-01T${selectedDayData.end_time}`).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '';

                        return (
                            <div className="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                                <h3 className="font-semibold text-gray-900 dark:text-white mb-4">
                                    Présences - {selectedDay}
                                </h3>
                                <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <Clock className="h-4 w-4" />
                                    <span>
                                        Horaire défini pour ce jour : <span className="font-medium text-gray-900 dark:text-white">{startTime} - {endTime}</span>
                                    </span>
                                </div>
                            </div>
                        );
                    })()}
                    {selectedDay && !weekDays.find(d => d.day_name === selectedDay)?.has_schedule && (
                        <div className="mt-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow p-6">
                            <h3 className="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                Aucun horaire défini
                            </h3>
                            <p className="text-sm text-yellow-700 dark:text-yellow-300">
                                Aucun horaire n'a été configuré pour {selectedDay}.
                            </p>
                        </div>
                    )}
                </div>
                    </>
                )}

                {/* Students List */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                    Liste des Étudiants
                    {selectedDay && ` - ${selectedDay}`}
                </h2>
                    </div>

                    {loadingScheduleStudents ? (
                <div className="p-12 text-center">
                    <p className="text-gray-600 dark:text-gray-400">Chargement...</p>
                </div>
                    ) : (scheduleStudents.length > 0 ? scheduleStudents : students).length === 0 ? (
                <div className="p-12 text-center">
                    <p className="text-gray-600 dark:text-gray-400">
                        Aucun étudiant inscrit à cette formation
                    </p>
                </div>
                    ) : (
                <>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Étudiant
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Email
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Présent
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Absent
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Excusé
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Raison
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Taux de présence
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {(scheduleStudents.length > 0 ? scheduleStudents : students).map((student) => (
                                    <tr key={student.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                {student.name}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-600 dark:text-gray-400">
                                                {student.email}
                                            </div>
                                        </td>
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
                                        <td className="px-6 py-4 text-center">
                                            <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                {attendance[student.id]
                                                    ? `${calculateAttendanceRate(student.id).toFixed(0)}%`
                                                    : (student.attendance_rate !== null && student.attendance_rate !== undefined
                                                        ? `${Number(student.attendance_rate).toFixed(0)}%`
                                                        : 'N/A')}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-end">
                        <Button onClick={handleSaveAttendance} disabled={saving}>
                            {saving ? 'Enregistrement...' : 'Enregistrer les présences'}
                        </Button>
                    </div>
                </>
                    )}
                </div>
            </div>

            {/* Reject Enrollment Dialog */}
            <Dialog open={showRejectDialog} onOpenChange={setShowRejectDialog}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Refuser l'inscription</DialogTitle>
                        <DialogDescription>
                            Veuillez indiquer la raison du refus. L'étudiant sera notifié par email.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="px-6 py-2">
                        <Textarea
                            value={rejectionReason}
                            onChange={(e) => setRejectionReason(e.target.value)}
                            placeholder="Raison du refus (minimum 10 caractères)..."
                            rows={3}
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowRejectDialog(false)}
                        >
                            Annuler
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleRejectConfirm}
                            disabled={rejectionReason.length < 10 || processingIds.includes(selectedEnrollmentId ?? 0)}
                        >
                            Refuser
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </DashboardLayout>
    );
}
