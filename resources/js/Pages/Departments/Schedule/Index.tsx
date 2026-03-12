import React, { useState, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Toaster } from '@/Components/ui/toaster';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
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
    ClipboardDocumentListIcon,
    MinusIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    ListBulletIcon,
    TableCellsIcon,
    Squares2X2Icon,
} from '@heroicons/react/24/outline';
import WeeklyCalendar from '@/Components/Scheduling/WeeklyCalendar';
import ShiftCard from '@/Components/Scheduling/ShiftCard';
import TodoCreateModal from '@/Components/Scheduling/TodoCreateModal';
import TodoEditModal from '@/Components/Scheduling/TodoEditModal';
import TodoList from '@/Components/Scheduling/TodoList';
import type { TodoViewMode } from '@/Components/Scheduling/TodoList';
import { cn } from '@/lib/utils';
import { filterTodos } from '@/utils/todoFilters';
import type {
    ScheduleIndexProps,
    Shift,
    ScheduleStatus,
    DepartmentTodo,
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
    todos = [],
    todoStats,
    members = [],
    todoStatuses = [],
    todoPriorities = [],
}: ScheduleIndexProps) {
    const { url } = usePage();
    const showTasksOnLoad = new URLSearchParams(url.split('?')[1] ?? '').get('showTasks') === '1';

    const [isPublishing, setIsPublishing] = useState(false);
    const [isAutoAssigning, setIsAutoAssigning] = useState(false);
    const [showGlobalStats, setShowGlobalStats] = useState(false);
    const [todoModalOpen, setTodoModalOpen] = useState(false);
    const [showTodoPanel, setShowTodoPanel] = useState(showTasksOnLoad);
    const [todoAccordionOpen, setTodoAccordionOpen] = useState(showTasksOnLoad);
    const [showAllTodos, setShowAllTodos] = useState(false);
    const [todoSearchQuery, setTodoSearchQuery] = useState('');
    const [todoViewMode, setTodoViewMode] = useState<TodoViewMode>('table');
    const [editingTodo, setEditingTodo] = useState<DepartmentTodo | null>(null);
    const [editModalOpen, setEditModalOpen] = useState(false);

    // Confirmation dialogs state
    const [publishDialogOpen, setPublishDialogOpen] = useState(false);
    const [lockDialogOpen, setLockDialogOpen] = useState(false);
    const [autoAssignDialogOpen, setAutoAssignDialogOpen] = useState(false);
    const [copyDialogOpen, setCopyDialogOpen] = useState(false);
    const [targetWeek, setTargetWeek] = useState('');

    // Use either weekly or global stats based on toggle (with fallback to stats if globalStats is undefined)
    const displayStats = showGlobalStats && globalStats ? globalStats : stats;

    // Handle editing a todo
    const handleEditTodo = (todo: DepartmentTodo) => {
        setEditingTodo(todo);
        setEditModalOpen(true);
    };

    // Filter todos based on showAllTodos toggle and search query
    const filteredTodos = useMemo(() => {
        return filterTodos(todos, {
            showAll: showAllTodos,
            searchQuery: todoSearchQuery,
        });
    }, [todos, showAllTodos, todoSearchQuery]);

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

            <div className="p-3 sm:p-6 space-y-4 sm:space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                        <Button variant="outline" size="sm" asChild className="w-fit">
                            <Link href={`/departments/${department.uuid}`}>
                                <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                Retour
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                                Planning - {department.name}
                            </h1>
                            <div className="flex flex-wrap items-center gap-2 mt-1">
                                {getStatusBadge(schedule.status)}
                                {schedule.week_label && (
                                    <span className="text-xs sm:text-sm text-gray-500">{schedule.week_label}</span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {schedule.status === 'draft' && (
                            <>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setAutoAssignDialogOpen(true)}
                                    disabled={isAutoAssigning || stats.unassigned_shifts === 0}
                                >
                                    <ArrowPathIcon className={`h-4 w-4 sm:mr-2 ${isAutoAssigning ? 'animate-spin' : ''}`} />
                                    <span className="hidden sm:inline">Auto-assigner</span>
                                </Button>
                                <Button size="sm" onClick={() => setPublishDialogOpen(true)} disabled={isPublishing}>
                                    <PaperAirplaneIcon className="h-4 w-4 sm:mr-2" />
                                    <span className="hidden sm:inline">Publier</span>
                                </Button>
                            </>
                        )}
                        {schedule.status === 'published' && (
                            <Button variant="outline" size="sm" onClick={() => setLockDialogOpen(true)}>
                                <LockClosedIcon className="h-4 w-4 sm:mr-2" />
                                <span className="hidden sm:inline">Verrouiller</span>
                            </Button>
                        )}
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/create`}>
                                <PlusIcon className="h-4 w-4 sm:mr-2" />
                                <span className="hidden sm:inline">Nouveau Shift</span>
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Week Navigation */}
                <Card>
                    <CardContent className="py-3 sm:py-4">
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <Button variant="outline" size="sm" onClick={() => handleWeekChange('prev')} className="w-full sm:w-auto">
                                <ChevronLeftIcon className="h-4 w-4 mr-1" />
                                <span className="hidden sm:inline">Semaine précédente</span>
                                <span className="sm:hidden">Précédent</span>
                            </Button>

                            <div className="flex items-center gap-2 sm:gap-4 w-full sm:w-auto">
                                <Select
                                    value={currentWeek}
                                    onValueChange={handleSelectWeek}
                                >
                                    <SelectTrigger className="w-full sm:w-64">
                                        <SelectValue>
                                            <div className="flex items-center gap-2">
                                                <CalendarDaysIcon className="h-4 w-4" />
                                                <span className="text-sm">{formatWeekDisplay(currentWeek)}</span>
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
                                    className="hidden sm:inline-flex"
                                >
                                    Aujourd'hui
                                </Button>
                            </div>

                            <Button variant="outline" size="sm" onClick={() => handleWeekChange('next')} className="w-full sm:w-auto">
                                <span className="hidden sm:inline">Semaine suivante</span>
                                <span className="sm:hidden">Suivant</span>
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
                    <div className="flex items-center gap-2">
                        {/* TODO Button */}
                        <Button
                            variant={showTodoPanel ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => {
                                const newValue = !showTodoPanel;
                                setShowTodoPanel(newValue);
                                if (newValue) {
                                    setTodoAccordionOpen(true);
                                }
                            }}
                            className="relative"
                        >
                            <ClipboardDocumentListIcon className="h-4 w-4 mr-1" />
                            Taches
                            {todoStats && todoStats.pending > 0 && (
                                <Badge className="ml-1 h-5 min-w-5 px-1.5 text-xs bg-blue-500">
                                    {todoStats.pending}
                                </Badge>
                            )}
                        </Button>
                        {globalStats && (
                            <>
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
                            </>
                        )}
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                    <Card>
                        <CardContent className="p-3 sm:pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs sm:text-sm text-gray-500">Total Shifts</p>
                                    <p className="text-lg sm:text-2xl font-bold">{displayStats.total_shifts}</p>
                                    {showGlobalStats && globalStats && displayStats.total_schedules !== undefined && (
                                        <p className="text-xs text-gray-400 hidden sm:block">{displayStats.total_schedules} semaines</p>
                                    )}
                                </div>
                                <CalendarDaysIcon className="h-6 w-6 sm:h-8 sm:w-8 text-gray-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-3 sm:pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs sm:text-sm text-gray-500">Assignés</p>
                                    <p className="text-lg sm:text-2xl font-bold text-green-600">{displayStats.assigned_shifts}</p>
                                    <p className="text-xs text-gray-400">{displayStats.assignment_rate}%</p>
                                </div>
                                <CheckCircleIcon className="h-6 w-6 sm:h-8 sm:w-8 text-green-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-3 sm:pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs sm:text-sm text-gray-500">Non assignés</p>
                                    <p className="text-lg sm:text-2xl font-bold text-orange-600">{displayStats.unassigned_shifts}</p>
                                </div>
                                <ExclamationTriangleIcon className="h-6 w-6 sm:h-8 sm:w-8 text-orange-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-3 sm:pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs sm:text-sm text-gray-500">Heures</p>
                                    <p className="text-lg sm:text-2xl font-bold">{displayStats.total_hours}h</p>
                                    <p className="text-xs text-gray-400 hidden sm:block">{displayStats.assigned_hours}h assignées</p>
                                </div>
                                <ClockIcon className="h-6 w-6 sm:h-8 sm:w-8 text-gray-400" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* TODO Panel - Accordion */}
                {showTodoPanel && (
                    <Card>
                        <div
                            className="flex items-center justify-between px-6 py-4 cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            onClick={() => setTodoAccordionOpen(!todoAccordionOpen)}
                        >
                            <div className="flex items-center gap-3">
                                <button
                                    type="button"
                                    className="flex items-center justify-center w-6 h-6 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        setTodoAccordionOpen(!todoAccordionOpen);
                                    }}
                                >
                                    {todoAccordionOpen ? (
                                        <MinusIcon className="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                    ) : (
                                        <PlusIcon className="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                    )}
                                </button>
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                        Taches du departement
                                    </h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        {todoStats && (
                                            <span>
                                                {todoStats.pending} en cours
                                                {todoStats.overdue > 0 && (
                                                    <span className="text-red-600 dark:text-red-400 ml-2">
                                                        ({todoStats.overdue} en retard)
                                                    </span>
                                                )}
                                                {showAllTodos && todoStats.completed > 0 && (
                                                    <span className="text-green-600 dark:text-green-400 ml-2">
                                                        ({todoStats.completed} terminee(s))
                                                    </span>
                                                )}
                                            </span>
                                        )}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2" onClick={(e) => e.stopPropagation()}>
                                {/* Search input */}
                                <div className="relative">
                                    <MagnifyingGlassIcon className="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        type="text"
                                        placeholder="Rechercher..."
                                        value={todoSearchQuery}
                                        onChange={(e) => setTodoSearchQuery(e.target.value)}
                                        className="pl-8 pr-8 h-8 w-48 text-sm"
                                    />
                                    {todoSearchQuery && (
                                        <button
                                            type="button"
                                            aria-label="Effacer la recherche"
                                            onClick={() => setTodoSearchQuery('')}
                                            className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        >
                                            <XMarkIcon className="h-4 w-4" />
                                        </button>
                                    )}
                                </div>
                                <Button
                                    variant={showAllTodos ? 'secondary' : 'outline'}
                                    size="sm"
                                    onClick={() => setShowAllTodos(!showAllTodos)}
                                >
                                    {showAllTodos ? 'Toutes' : 'Actives'}
                                </Button>
                                {/* View mode toggle */}
                                <div className="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 p-0.5">
                                    <button
                                        onClick={() => setTodoViewMode('list')}
                                        className={cn(
                                            'px-2 py-1.5 rounded-md transition-colors',
                                            todoViewMode === 'list'
                                                ? 'bg-icc-blue text-white'
                                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        )}
                                        title="Vue liste"
                                    >
                                        <ListBulletIcon className="h-4 w-4" />
                                    </button>
                                    <button
                                        onClick={() => setTodoViewMode('table')}
                                        className={cn(
                                            'px-2 py-1.5 rounded-md transition-colors',
                                            todoViewMode === 'table'
                                                ? 'bg-icc-blue text-white'
                                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        )}
                                        title="Vue tableau"
                                    >
                                        <TableCellsIcon className="h-4 w-4" />
                                    </button>
                                    <button
                                        onClick={() => setTodoViewMode('grid')}
                                        className={cn(
                                            'px-2 py-1.5 rounded-md transition-colors',
                                            todoViewMode === 'grid'
                                                ? 'bg-icc-blue text-white'
                                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        )}
                                        title="Vue grille"
                                    >
                                        <Squares2X2Icon className="h-4 w-4" />
                                    </button>
                                </div>
                                <Button size="sm" onClick={() => setTodoModalOpen(true)}>
                                    <PlusIcon className="h-4 w-4 mr-1" />
                                    Nouvelle tache
                                </Button>
                            </div>
                        </div>
                        {todoAccordionOpen && (
                            <CardContent className="pt-0 border-t border-gray-200 dark:border-gray-700">
                                <TodoList
                                    todos={filteredTodos}
                                    departmentUuid={department.uuid}
                                    members={members}
                                    onEdit={handleEditTodo}
                                    viewMode={todoViewMode}
                                />
                            </CardContent>
                        )}
                    </Card>
                )}

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

            {/* TODO Create Modal */}
            <TodoCreateModal
                open={todoModalOpen}
                onOpenChange={setTodoModalOpen}
                departmentUuid={department.uuid}
                members={members}
                priorities={todoPriorities}
                shifts={schedule.shifts || []}
            />

            {/* TODO Edit Modal */}
            <TodoEditModal
                open={editModalOpen}
                onOpenChange={setEditModalOpen}
                todo={editingTodo}
                departmentUuid={department.uuid}
                members={members}
                priorities={todoPriorities}
                shifts={schedule.shifts || []}
            />

            {/* Toast notifications */}
            <Toaster position="top-right" richColors closeButton />
        </DashboardLayout>
    );
}
