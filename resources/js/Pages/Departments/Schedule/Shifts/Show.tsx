import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Toaster } from '@/Components/ui/toaster';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    ClockIcon,
    UserIcon,
    MapPinIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    BriefcaseIcon,
    PlayIcon,
    StopIcon,
    XCircleIcon,
    PlusIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/react/24/outline';
import type { Shift, WeeklySchedule, ShiftType, ShiftStatus, DepartmentTodo, DepartmentMember, EnumOption, TodoPriority } from '@/Types/scheduling';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import TodoCreateModal from '@/Components/Scheduling/TodoCreateModal';
import TodoEditModal from '@/Components/Scheduling/TodoEditModal';
import TodoList from '@/Components/Scheduling/TodoList';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Props {
    department: Department;
    schedule: WeeklySchedule;
    shift: Shift;
    conflicts: {
        has_blocking_conflicts: boolean;
        has_warnings: boolean;
        conflicts: Array<{ type: string; message: string }>;
        warnings: Array<{ type: string; message: string }>;
    } | null;
    shiftTodos?: DepartmentTodo[];
    members?: DepartmentMember[];
    todoPriorities?: EnumOption<TodoPriority>[];
}

const TYPE_LABELS: Record<ShiftType, string> = {
    morning: 'Matin',
    afternoon: 'Après-midi',
    evening: 'Soir',
    night: 'Nuit',
    full_day: 'Journée complète',
    split: 'Coupure',
    on_call: 'Astreinte',
    custom: 'Personnalisé',
};

const STATUS_INFO: Record<ShiftStatus, { color: string; label: string; bgColor: string }> = {
    draft: { color: 'text-gray-600', label: 'Brouillon', bgColor: 'bg-gray-100' },
    published: { color: 'text-blue-600', label: 'Publié', bgColor: 'bg-blue-100' },
    confirmed: { color: 'text-green-600', label: 'Confirmé', bgColor: 'bg-green-100' },
    in_progress: { color: 'text-yellow-600', label: 'En cours', bgColor: 'bg-yellow-100' },
    completed: { color: 'text-green-700', label: 'Terminé', bgColor: 'bg-green-200' },
    cancelled: { color: 'text-red-600', label: 'Annulé', bgColor: 'bg-red-100' },
    no_show: { color: 'text-red-700', label: 'Absent', bgColor: 'bg-red-200' },
};

export default function ShiftShow({ department, schedule, shift, conflicts, shiftTodos = [], members = [], todoPriorities = [] }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [showCancelDialog, setShowCancelDialog] = useState(false);
    const [todoModalOpen, setTodoModalOpen] = useState(false);
    const [editingTodo, setEditingTodo] = useState<DepartmentTodo | null>(null);
    const [editModalOpen, setEditModalOpen] = useState(false);

    const cancelForm = useForm({
        cancellation_reason: '',
    });

    const statusInfo = STATUS_INFO[shift.status] || STATUS_INFO.draft;

    // A shift can be cancelled if it's not already in a final state
    const canCancel = !['completed', 'cancelled', 'no_show'].includes(shift.status);

    const formatTime = (time: string) => time?.slice(0, 5) || '';

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const handleDelete = () => {
        router.delete(
            `/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}`,
            {
                onSuccess: () => setShowDeleteDialog(false),
            }
        );
    };

    const handleCheckIn = () => {
        router.post(
            `/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}/check-in`
        );
    };

    const handleCheckOut = () => {
        router.post(
            `/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}/check-out`
        );
    };

    const handleCancel = () => {
        cancelForm.post(
            `/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}/cancel`,
            {
                onSuccess: () => {
                    setShowCancelDialog(false);
                    cancelForm.reset();
                },
            }
        );
    };

    const canCheckIn = shift.can_check_in;
    const canCheckOut = shift.can_check_out;
    const isEditable = schedule.status !== 'locked';

    const handleEditTodo = (todo: DepartmentTodo) => {
        setEditingTodo(todo);
        setEditModalOpen(true);
    };

    return (
        <DashboardLayout>
            <Head title={`Shift - ${shift.title || formatTime(shift.start_time)}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href={`/departments/${department.uuid}/schedule?week=${schedule.week_start}`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour au planning
                    </Link>

                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                {shift.title || `Shift ${TYPE_LABELS[shift.type]}`}
                            </h1>
                            <p className="text-sm text-gray-500 mt-1">
                                {formatDate(shift.date)}
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            {canCheckIn && (
                                <Button onClick={handleCheckIn} className="bg-green-600 hover:bg-green-700">
                                    <PlayIcon className="h-4 w-4 mr-2" />
                                    Pointer l'arrivée
                                </Button>
                            )}
                            {canCheckOut && (
                                <Button onClick={handleCheckOut} variant="outline">
                                    <StopIcon className="h-4 w-4 mr-2" />
                                    Pointer la sortie
                                </Button>
                            )}
                            {isEditable && (
                                <>
                                    <Button variant="outline" asChild>
                                        <Link href={`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}/edit`}>
                                            <PencilIcon className="h-4 w-4 mr-2" />
                                            Modifier
                                        </Link>
                                    </Button>
                                    {canCancel && (
                                        <Button
                                            variant="outline"
                                            className="text-orange-600 hover:text-orange-700 hover:bg-orange-50 dark:hover:bg-orange-900/20"
                                            onClick={() => setShowCancelDialog(true)}
                                        >
                                            <XCircleIcon className="h-4 w-4 mr-2" />
                                            Annuler
                                        </Button>
                                    )}
                                    <Button
                                        variant="outline"
                                        className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                        onClick={() => setShowDeleteDialog(true)}
                                    >
                                        <TrashIcon className="h-4 w-4" />
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Info */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Informations du Shift</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-4">
                                    <Badge className={`${statusInfo.bgColor} ${statusInfo.color}`}>
                                        {statusInfo.label}
                                    </Badge>
                                    <Badge variant="outline">
                                        {TYPE_LABELS[shift.type]}
                                    </Badge>
                                    {shift.is_overtime && (
                                        <Badge variant="destructive">Heures supplémentaires</Badge>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4 pt-4">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                            <CalendarDaysIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Date</p>
                                            <p className="font-medium">{formatDate(shift.date)}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                            <ClockIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Horaires</p>
                                            <p className="font-medium">
                                                {formatTime(shift.start_time)} - {formatTime(shift.end_time)}
                                                <span className="text-gray-400 ml-2">({shift.duration_hours}h)</span>
                                            </p>
                                        </div>
                                    </div>

                                    {shift.location && (
                                        <div className="flex items-center gap-3">
                                            <div className="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                                <MapPinIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                            </div>
                                            <div>
                                                <p className="text-sm text-gray-500">Lieu</p>
                                                <p className="font-medium">{shift.location}</p>
                                            </div>
                                        </div>
                                    )}

                                    {shift.break_duration > 0 && (
                                        <div className="flex items-center gap-3">
                                            <div className="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                                <ClockIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                            </div>
                                            <div>
                                                <p className="text-sm text-gray-500">Pause</p>
                                                <p className="font-medium">{shift.break_duration} minutes</p>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Description */}
                                <div className="pt-4 border-t">
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</p>
                                    {shift.description ? (
                                        <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{shift.description}</p>
                                    ) : (
                                        <p className="text-gray-400 dark:text-gray-500 italic">Aucune description</p>
                                    )}
                                </div>

                                {/* Notes internes */}
                                <div className="pt-4 border-t">
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Notes internes</p>
                                    {shift.notes ? (
                                        <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{shift.notes}</p>
                                    ) : (
                                        <p className="text-gray-400 dark:text-gray-500 italic">Aucune note</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Conflicts/Warnings */}
                        {conflicts && (conflicts.has_blocking_conflicts || conflicts.has_warnings) && (
                            <Card className={conflicts.has_blocking_conflicts ? 'border-red-300' : 'border-yellow-300'}>
                                <CardHeader>
                                    <CardTitle className={conflicts.has_blocking_conflicts ? 'text-red-600' : 'text-yellow-600'}>
                                        <ExclamationTriangleIcon className="h-5 w-5 inline mr-2" />
                                        {conflicts.has_blocking_conflicts ? 'Conflits détectés' : 'Avertissements'}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-2">
                                        {conflicts.conflicts.map((c, i) => (
                                            <li key={i} className="text-red-600 text-sm">
                                                {c.message}
                                            </li>
                                        ))}
                                        {conflicts.warnings.map((w, i) => (
                                            <li key={i} className="text-yellow-600 text-sm">
                                                {w.message}
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        )}

                        {/* Tasks */}
                        {shift.tasks && shift.tasks.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Tâches</CardTitle>
                                    <CardDescription>
                                        {shift.tasks.filter(t => t.status === 'completed').length} / {shift.tasks.length} complétées
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-2">
                                        {shift.tasks.map((task) => (
                                            <li
                                                key={task.uuid}
                                                className={`flex items-center gap-3 p-2 rounded ${
                                                    task.status === 'completed' ? 'bg-green-50 dark:bg-green-900/20' : ''
                                                }`}
                                            >
                                                {task.status === 'completed' ? (
                                                    <CheckCircleIcon className="h-5 w-5 text-green-600" />
                                                ) : (
                                                    <div className="h-5 w-5 border-2 border-gray-300 rounded-full" />
                                                )}
                                                <span className={task.status === 'completed' ? 'line-through text-gray-500' : ''}>
                                                    {task.title}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        )}

                        {/* Shift TODOs */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <div>
                                    <CardTitle className="text-base flex items-center gap-2">
                                        <ClipboardDocumentListIcon className="h-5 w-5" />
                                        TODOs du shift
                                        {shiftTodos.length > 0 && (
                                            <Badge variant="secondary" className="ml-2">
                                                {shiftTodos.filter(t => t.status !== 'completed' && t.status !== 'cancelled').length} en cours
                                            </Badge>
                                        )}
                                    </CardTitle>
                                    <CardDescription>
                                        Taches associees a ce shift
                                    </CardDescription>
                                </div>
                                {isEditable && (
                                    <Button size="sm" onClick={() => setTodoModalOpen(true)}>
                                        <PlusIcon className="h-4 w-4 mr-1" />
                                        Ajouter
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent>
                                <TodoList
                                    todos={shiftTodos}
                                    departmentUuid={department.uuid}
                                    members={members}
                                    compact={true}
                                    showShiftInfo={false}
                                    onEdit={isEditable ? handleEditTodo : undefined}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Employee Assignment */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    <UserIcon className="h-4 w-4 inline mr-2" />
                                    Personnes assignées
                                    {shift.users && shift.users.length > 0 && (
                                        <Badge variant="secondary" className="ml-2">
                                            {shift.users.length}
                                        </Badge>
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {shift.users && shift.users.length > 0 ? (
                                    <div className="space-y-3">
                                        {shift.users.map((user) => (
                                            <div key={user.id} className="flex items-center gap-3">
                                                <Avatar className="h-10 w-10">
                                                    <AvatarFallback className="bg-blue-100 text-blue-600">
                                                        {getInitials(`${user.first_name || ''} ${user.last_name || ''}`.trim() || user.name || 'NN')}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="font-medium">
                                                        {user.first_name && user.last_name
                                                            ? `${user.first_name} ${user.last_name}`
                                                            : user.name || 'Utilisateur'}
                                                    </p>
                                                    <p className="text-sm text-gray-500">{user.email}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : shift.user ? (
                                    <div className="flex items-center gap-3">
                                        <Avatar className="h-10 w-10">
                                            <AvatarFallback className="bg-blue-100 text-blue-600">
                                                {getInitials(shift.user.name || 'NN')}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <p className="font-medium">{shift.user.name}</p>
                                            <p className="text-sm text-gray-500">{shift.user.email}</p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-4">
                                        <ExclamationTriangleIcon className="h-8 w-8 text-orange-400 mx-auto mb-2" />
                                        <p className="text-orange-600 font-medium">Non assigné</p>
                                        {isEditable && (
                                            <Button variant="outline" size="sm" className="mt-2" asChild>
                                                <Link href={`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}/edit`}>
                                                    Assigner
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Position */}
                        {shift.position && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        <BriefcaseIcon className="h-4 w-4 inline mr-2" />
                                        Poste
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="font-medium">{shift.position.name}</p>
                                    {shift.position.description && (
                                        <p className="text-sm text-gray-500 mt-1">{shift.position.description}</p>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Check-in/out Status */}
                        {(shift.checked_in_at || shift.checked_out_at) && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Pointage</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {shift.checked_in_at && (
                                        <div className="flex items-center gap-2 text-green-600">
                                            <CheckCircleIcon className="h-5 w-5" />
                                            <div>
                                                <p className="text-sm font-medium">Arrivée</p>
                                                <p className="text-xs text-gray-500">
                                                    {new Date(shift.checked_in_at).toLocaleString('fr-FR')}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                    {shift.checked_out_at && (
                                        <div className="flex items-center gap-2 text-blue-600">
                                            <CheckCircleIcon className="h-5 w-5" />
                                            <div>
                                                <p className="text-sm font-medium">Départ</p>
                                                <p className="text-xs text-gray-500">
                                                    {new Date(shift.checked_out_at).toLocaleString('fr-FR')}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            <DeleteConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
                onConfirm={handleDelete}
                title="Supprimer ce shift ?"
                description="Cette action est irréversible. Le shift sera définitivement supprimé."
            />

            {/* Cancel Shift Dialog */}
            <Dialog open={showCancelDialog} onOpenChange={setShowCancelDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <XCircleIcon className="h-5 w-5 text-orange-500" />
                            Annuler ce shift ?
                        </DialogTitle>
                        <DialogDescription>
                            Le shift passera au statut "Annulé". Cette action peut être inversée en modifiant le statut du shift.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4 px-3">
                        <div className="space-y-2">
                            <Label htmlFor="cancellation_reason">Raison de l'annulation (optionnelle)</Label>
                            <Textarea
                                id="cancellation_reason"
                                value={cancelForm.data.cancellation_reason}
                                onChange={(e) => cancelForm.setData('cancellation_reason', e.target.value)}
                                placeholder="Ex: Employé malade, manque de personnel..."
                                rows={3}
                            />
                            {cancelForm.errors.cancellation_reason && (
                                <p className="text-sm text-red-600">{cancelForm.errors.cancellation_reason}</p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setShowCancelDialog(false)}
                        >
                            Retour
                        </Button>
                        <Button
                            type="button"
                            className="bg-orange-600 hover:bg-orange-700"
                            onClick={handleCancel}
                            disabled={cancelForm.processing}
                        >
                            {cancelForm.processing ? 'Annulation...' : 'Confirmer l\'annulation'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* TODO Create Modal for this shift */}
            <TodoCreateModal
                open={todoModalOpen}
                onOpenChange={setTodoModalOpen}
                departmentUuid={department.uuid}
                members={members}
                priorities={todoPriorities}
                shifts={[shift]}
                defaultShiftUuid={shift.uuid}
            />

            {/* TODO Edit Modal */}
            <TodoEditModal
                open={editModalOpen}
                onOpenChange={setEditModalOpen}
                todo={editingTodo}
                departmentUuid={department.uuid}
                members={members}
                priorities={todoPriorities}
                shifts={[shift]}
            />

            {/* Toast notifications */}
            <Toaster position="top-right" richColors closeButton />
        </DashboardLayout>
    );
}
