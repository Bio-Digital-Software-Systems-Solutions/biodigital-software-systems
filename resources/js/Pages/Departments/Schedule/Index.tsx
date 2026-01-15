import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import DatePicker, { registerLocale } from 'react-datepicker';
import { fr } from 'date-fns/locale';
import { format, parse, startOfWeek, getDay } from 'date-fns';
import 'react-datepicker/dist/react-datepicker.css';
import {
    ArrowLeftIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    PlusIcon,
    CalendarDaysIcon,
    UserGroupIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    LockClosedIcon,
    PaperAirplaneIcon,
    DocumentDuplicateIcon,
    CogIcon,
    GlobeAltIcon,
} from '@heroicons/react/24/outline';
import WeeklyCalendar from '@/Components/Scheduling/WeeklyCalendar';
import ShiftCard from '@/Components/Scheduling/ShiftCard';
import type {
    ScheduleIndexProps,
    Shift,
    ScheduleStatus,
} from '@/Types/scheduling';

// Register French locale for DatePicker
registerLocale('fr', fr);

// Filter to only allow Mondays in the DatePicker
const isMonday = (date: Date) => {
    return getDay(date) === 1;
};

export default function ScheduleIndex({
    department,
    schedule,
    stats,
    globalStats,
    settings,
    weeks,
    currentWeek,
    prevWeek,
    nextWeek,
}: ScheduleIndexProps) {
    const [isPublishing, setIsPublishing] = useState(false);
    const [isAutoAssigning, setIsAutoAssigning] = useState(false);
    const [showGlobalStats, setShowGlobalStats] = useState(false);

    // Confirmation dialogs state
    const [publishDialogOpen, setPublishDialogOpen] = useState(false);
    const [lockDialogOpen, setLockDialogOpen] = useState(false);
    const [autoAssignDialogOpen, setAutoAssignDialogOpen] = useState(false);
    const [copyDialogOpen, setCopyDialogOpen] = useState(false);
    const [targetWeek, setTargetWeek] = useState('');

    // Use either weekly or global stats based on toggle (with fallback to stats if globalStats is undefined)
    const displayStats = showGlobalStats && globalStats ? globalStats : stats;

    const handleWeekChange = (direction: 'prev' | 'next') => {
        const targetWeek = direction === 'prev' ? prevWeek : nextWeek;
        router.get(`/departments/${department.uuid}/schedule`, { week: targetWeek }, {
            preserveState: true,
        });
    };

    const handleSelectWeek = (weekDate: string) => {
        router.get(`/departments/${department.uuid}/schedule`, { week: weekDate }, {
            preserveState: true,
        });
    };

    const handlePublish = () => {
        setIsPublishing(true);
        router.post(`/departments/${department.uuid}/schedule/${schedule.uuid}/publish`, {}, {
            onFinish: () => {
                setIsPublishing(false);
                setPublishDialogOpen(false);
            },
        });
    };

    const handleLock = () => {
        router.post(`/departments/${department.uuid}/schedule/${schedule.uuid}/lock`, {}, {
            onFinish: () => setLockDialogOpen(false),
        });
    };

    const handleAutoAssign = () => {
        setIsAutoAssigning(true);
        router.post(`/departments/${department.uuid}/schedule/${schedule.uuid}/auto-assign`, {}, {
            onFinish: () => {
                setIsAutoAssigning(false);
                setAutoAssignDialogOpen(false);
            },
        });
    };

    const handleCopySchedule = () => {
        if (!targetWeek) return;
        router.post(`/departments/${department.uuid}/schedule/${schedule.uuid}/copy`, {
            target_week: targetWeek,
            copy_assignments: false,
        }, {
            onFinish: () => {
                setCopyDialogOpen(false);
                setTargetWeek('');
            },
        });
    };

    const formatWeekDisplay = (date: string) => {
        const d = new Date(date);
        const endDate = new Date(d);
        endDate.setDate(endDate.getDate() + 6);

        const formatDate = (date: Date) => {
            return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
        };

        return `${formatDate(d)} - ${formatDate(endDate)}`;
    };

    const getStatusBadge = (status: ScheduleStatus) => {
        switch (status) {
            case 'draft':
                return <Badge variant="secondary">Brouillon</Badge>;
            case 'published':
                return <Badge className="bg-green-500">Publié</Badge>;
            case 'locked':
                return <Badge className="bg-blue-500"><LockClosedIcon className="h-3 w-3 mr-1" />Verrouillé</Badge>;
        }
    };

    // Group shifts by day
    const shiftsByDay = useMemo(() => {
        const grouped: Record<string, Shift[]> = {};
        const shifts = schedule.shifts || [];

        shifts.forEach(shift => {
            // Normalize date to YYYY-MM-DD format (handle ISO dates)
            const day = shift.date ? shift.date.split('T')[0] : '';
            if (!day) return;

            if (!grouped[day]) {
                grouped[day] = [];
            }
            grouped[day].push(shift);
        });

        // Sort shifts by start time within each day
        Object.keys(grouped).forEach(day => {
            grouped[day].sort((a, b) => a.start_time.localeCompare(b.start_time));
        });

        return grouped;
    }, [schedule.shifts]);

    return (
        <DashboardLayout>
            <Head title={`Planning - ${department.name}`} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/departments/${department.uuid}`}>
                                <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                Retour
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Planning - {department.name}
                            </h1>
                            <div className="flex items-center gap-2 mt-1">
                                {getStatusBadge(schedule.status)}
                                {schedule.week_label && (
                                    <span className="text-sm text-gray-500">{schedule.week_label}</span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {schedule.status === 'draft' && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => setAutoAssignDialogOpen(true)}
                                    disabled={isAutoAssigning || stats.unassigned_shifts === 0}
                                >
                                    <ArrowPathIcon className={`h-4 w-4 mr-2 ${isAutoAssigning ? 'animate-spin' : ''}`} />
                                    Auto-assigner
                                </Button>
                                <Button onClick={() => setPublishDialogOpen(true)} disabled={isPublishing}>
                                    <PaperAirplaneIcon className="h-4 w-4 mr-2" />
                                    Publier
                                </Button>
                            </>
                        )}
                        {schedule.status === 'published' && (
                            <Button variant="outline" onClick={() => setLockDialogOpen(true)}>
                                <LockClosedIcon className="h-4 w-4 mr-2" />
                                Verrouiller
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/create`}>
                                <PlusIcon className="h-4 w-4 mr-2" />
                                Nouveau Shift
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Week Navigation */}
                <Card>
                    <CardContent className="py-4">
                        <div className="flex items-center justify-between">
                            <Button variant="outline" size="sm" onClick={() => handleWeekChange('prev')}>
                                <ChevronLeftIcon className="h-4 w-4 mr-1" />
                                Semaine précédente
                            </Button>

                            <div className="flex items-center gap-4">
                                <Select
                                    value={currentWeek}
                                    onValueChange={handleSelectWeek}
                                >
                                    <SelectTrigger className="w-64">
                                        <SelectValue>
                                            <div className="flex items-center gap-2">
                                                <CalendarDaysIcon className="h-4 w-4" />
                                                {formatWeekDisplay(currentWeek)}
                                            </div>
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        {weeks.map((week) => (
                                            <SelectItem key={week.uuid} value={week.week_start}>
                                                <div className="flex items-center justify-between w-full gap-4">
                                                    <span>{formatWeekDisplay(week.week_start)}</span>
                                                    {week.status === 'published' && (
                                                        <Badge variant="secondary" className="text-xs">Publié</Badge>
                                                    )}
                                                    {week.status === 'locked' && (
                                                        <Badge variant="secondary" className="text-xs">Verrouillé</Badge>
                                                    )}
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => {
                                        const today = new Date();
                                        const day = today.getDay();
                                        const diff = today.getDate() - day + (day === 0 ? -6 : 1);
                                        const monday = new Date(today.setDate(diff));
                                        handleSelectWeek(monday.toISOString().split('T')[0]);
                                    }}
                                >
                                    Aujourd'hui
                                </Button>
                            </div>

                            <Button variant="outline" size="sm" onClick={() => handleWeekChange('next')}>
                                Semaine suivante
                                <ChevronRightIcon className="h-4 w-4 ml-1" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Stats Header with Toggle */}
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        {showGlobalStats && globalStats ? 'Statistiques globales' : 'Statistiques de la semaine'}
                    </h2>
                    {globalStats && (
                        <div className="flex items-center gap-2">
                            <Button
                                variant={showGlobalStats ? 'outline' : 'default'}
                                size="sm"
                                onClick={() => setShowGlobalStats(false)}
                            >
                                <CalendarDaysIcon className="h-4 w-4 mr-1" />
                                Semaine
                            </Button>
                            <Button
                                variant={showGlobalStats ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setShowGlobalStats(true)}
                            >
                                <GlobeAltIcon className="h-4 w-4 mr-1" />
                                Global
                            </Button>
                        </div>
                    )}
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">Total Shifts</p>
                                    <p className="text-2xl font-bold">{displayStats.total_shifts}</p>
                                    {showGlobalStats && globalStats && displayStats.total_schedules !== undefined && (
                                        <p className="text-xs text-gray-400">{displayStats.total_schedules} semaines</p>
                                    )}
                                </div>
                                <CalendarDaysIcon className="h-8 w-8 text-gray-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">Assignés</p>
                                    <p className="text-2xl font-bold text-green-600">{displayStats.assigned_shifts}</p>
                                    <p className="text-xs text-gray-400">{displayStats.assignment_rate}%</p>
                                </div>
                                <CheckCircleIcon className="h-8 w-8 text-green-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">Non assignés</p>
                                    <p className="text-2xl font-bold text-orange-600">{displayStats.unassigned_shifts}</p>
                                </div>
                                <ExclamationTriangleIcon className="h-8 w-8 text-orange-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">Heures totales</p>
                                    <p className="text-2xl font-bold">{displayStats.total_hours}h</p>
                                    <p className="text-xs text-gray-400">{displayStats.assigned_hours}h assignées</p>
                                </div>
                                <ClockIcon className="h-8 w-8 text-gray-400" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Weekly Calendar */}
                <WeeklyCalendar
                    weekStart={currentWeek}
                    shiftsByDay={shiftsByDay}
                    departmentUuid={department.uuid}
                    scheduleUuid={schedule.uuid}
                    isEditable={schedule.status === 'draft'}
                />

                {/* Quick Actions */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Actions rapides</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-3">
                            <Button variant="outline" asChild>
                                <Link href={`/departments/${department.uuid}/availability`}>
                                    <UserGroupIcon className="h-4 w-4 mr-2" />
                                    Voir les disponibilités
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={`/departments/${department.uuid}/absences`}>
                                    <CalendarDaysIcon className="h-4 w-4 mr-2" />
                                    Demandes d'absence
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={`/departments/${department.uuid}/swap-requests`}>
                                    <ArrowPathIcon className="h-4 w-4 mr-2" />
                                    Échanges de shifts
                                </Link>
                            </Button>
                            {schedule.status === 'draft' && (
                                <Button
                                    variant="outline"
                                    onClick={() => setCopyDialogOpen(true)}
                                >
                                    <DocumentDuplicateIcon className="h-4 w-4 mr-2" />
                                    Copier vers une autre semaine
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Publish Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={publishDialogOpen}
                onOpenChange={setPublishDialogOpen}
                title="Publier le planning"
                description="Voulez-vous publier ce planning ? Les employés seront notifiés de leurs shifts."
                confirmText={isPublishing ? 'Publication...' : 'Publier'}
                cancelText="Annuler"
                onConfirm={handlePublish}
                variant="default"
            />

            {/* Lock Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={lockDialogOpen}
                onOpenChange={setLockDialogOpen}
                title="Verrouiller le planning"
                description="Voulez-vous verrouiller ce planning ? Il ne pourra plus être modifié après cette action."
                confirmText="Verrouiller"
                cancelText="Annuler"
                onConfirm={handleLock}
                variant="default"
            />

            {/* Auto-assign Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={autoAssignDialogOpen}
                onOpenChange={setAutoAssignDialogOpen}
                title="Auto-assigner les shifts"
                description="Voulez-vous auto-assigner les shifts non assignés ? Le système attribuera automatiquement les employés disponibles."
                confirmText={isAutoAssigning ? 'Attribution...' : 'Auto-assigner'}
                cancelText="Annuler"
                onConfirm={handleAutoAssign}
                variant="default"
            />

            {/* Copy Schedule Dialog */}
            <Dialog open={copyDialogOpen} onOpenChange={setCopyDialogOpen}>
                <DialogContent className="sm:max-w-md overflow-visible">
                    <DialogHeader>
                        <DialogTitle>Copier vers une autre semaine</DialogTitle>
                        <DialogDescription>
                            Entrez la date de début de la semaine cible pour copier ce planning.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="py-4 px-4">
                        <Label htmlFor="targetWeek">Date de début (lundi)</Label>
                        <div className="mt-2 relative">
                            <DatePicker
                                selected={targetWeek ? parse(targetWeek, 'yyyy-MM-dd', new Date()) : null}
                                onChange={(date: Date | null) => {
                                    setTargetWeek(date ? format(date, 'yyyy-MM-dd') : '');
                                }}
                                locale="fr"
                                dateFormat="dd/MM/yyyy"
                                minDate={new Date()}
                                filterDate={isMonday}
                                placeholderText="Sélectionner un lundi"
                                className="w-full px-3 py-2 rounded-md border text-sm bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                                calendarClassName="shadow-lg !z-[9999]"
                                popperClassName="!z-[9999]"
                                popperPlacement="bottom-start"
                                showPopperArrow={false}
                                isClearable
                            />
                        </div>
                    </div>
                    <DialogFooter className="px-4">
                        <Button variant="outline" onClick={() => setCopyDialogOpen(false)}>
                            Annuler
                        </Button>
                        <Button onClick={handleCopySchedule} disabled={!targetWeek}>
                            Copier
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </DashboardLayout>
    );
}
