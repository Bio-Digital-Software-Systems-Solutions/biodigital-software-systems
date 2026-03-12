import React, { useState, useMemo } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Checkbox } from '@/Components/ui/checkbox';
import { SearchableMultiSelect, SelectOption } from '@/Components/ui/searchable-select';
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
    ClockIcon,
    UserIcon,
    BriefcaseIcon,
    ArrowPathIcon,
} from '@heroicons/react/24/outline';
import type { Shift, WeeklySchedule, ShiftType, ShiftStatus, EnumOption, ShiftUpdateScope } from '@/Types/scheduling';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Assignable {
    id: number;
    uuid: string | null;
    first_name: string;
    last_name: string;
    email: string;
    type: 'user' | 'employee' | 'star';
    position?: string;
    title?: string;
}

interface Position {
    id: number;
    name: string;
}

interface Props {
    department: Department;
    schedule: WeeklySchedule;
    shift: Shift;
    users: Assignable[];
    employees: Assignable[];
    stars: Assignable[];
    positions: Position[];
    shiftTypes: EnumOption<ShiftType>[];
    shiftStatuses: EnumOption<ShiftStatus>[];
}

const TYPE_COLORS: Record<ShiftType, string> = {
    morning: 'bg-yellow-100 text-yellow-800',
    afternoon: 'bg-orange-100 text-orange-800',
    evening: 'bg-purple-100 text-purple-800',
    night: 'bg-indigo-100 text-indigo-800',
    full_day: 'bg-blue-100 text-blue-800',
    split: 'bg-pink-100 text-pink-800',
    on_call: 'bg-gray-100 text-gray-800',
    custom: 'bg-slate-100 text-slate-800',
};

export default function ShiftEdit({
    department,
    schedule,
    shift,
    users,
    employees,
    stars,
    positions,
    shiftTypes,
    shiftStatuses,
}: Props) {
    const [assigneeType, setAssigneeType] = useState<'all' | 'user' | 'employee' | 'star'>('all');
    const [showScopeDialog, setShowScopeDialog] = useState(false);

    const initialUserIds = useMemo(() => {
        if (shift.users && Array.isArray(shift.users)) {
            return shift.users.map((u: any) => u.id);
        }
        return [];
    }, [shift.users]);

    const { data, setData, put, processing, errors } = useForm({
        update_scope: 'single' as ShiftUpdateScope,
        date: shift.date,
        type: shift.type,
        status: shift.status,
        start_time: shift.start_time?.slice(0, 5) || '09:00',
        end_time: shift.end_time?.slice(0, 5) || '17:00',
        break_duration: shift.break_duration || 30,
        title: shift.title || '',
        description: shift.description || '',
        location: shift.location || '',
        user_ids: initialUserIds,
        position_id: shift.position_id || '',
        min_employees: shift.min_employees || 1,
        max_employees: shift.max_employees || 1,
        is_overtime: shift.is_overtime || false,
        requires_approval: shift.requires_approval || false,
        notes: shift.notes || '',
        color: shift.color || '',
    });

    const handleSubmitClick = (e: React.FormEvent) => {
        e.preventDefault();
        if (shift.series_id) {
            setShowScopeDialog(true);
        } else {
            submitForm('single');
        }
    };

    const submitForm = (scope: ShiftUpdateScope) => {
        setShowScopeDialog(false);
        router.put(
            `/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}`,
            { ...data, update_scope: scope },
        );
    };

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const getAssigneeTypeLabel = (type: 'user' | 'employee' | 'star') => {
        const labels = { user: 'Membre', employee: 'Employé', star: 'Star' };
        return labels[type];
    };

    const allAssignees = useMemo(() => {
        const combined: Assignable[] = [];
        if (assigneeType === 'all' || assigneeType === 'user') combined.push(...users);
        if (assigneeType === 'all' || assigneeType === 'employee') combined.push(...employees);
        if (assigneeType === 'all' || assigneeType === 'star') combined.push(...stars);

        const seen = new Set<number>();
        return combined.filter(a => {
            if (seen.has(a.id)) return false;
            seen.add(a.id);
            return true;
        });
    }, [users, employees, stars, assigneeType]);

    const assigneeOptions: SelectOption[] = useMemo(() => {
        return allAssignees.map(assignee => ({
            value: assignee.id,
            label: `${assignee.first_name} ${assignee.last_name} (${getAssigneeTypeLabel(assignee.type)})${assignee.position ? ` - ${assignee.position}` : ''}${assignee.title ? ` - ${assignee.title}` : ''}`,
        }));
    }, [allAssignees]);

    return (
        <DashboardLayout>
            <Head title={`Modifier Shift - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <Link
                        href={`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour aux détails
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Modifier le Shift</h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400">{formatDate(shift.date)}</p>
                    {shift.series_id && (
                        <div className="mt-1 inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-full px-2 py-1">
                            <ArrowPathIcon className="h-3 w-3" />
                            Fait partie d'une série récurrente
                        </div>
                    )}
                </div>

                <form onSubmit={handleSubmitClick}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Informations du Shift</CardTitle>
                            <CardDescription>Modifiez les informations du shift</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Type and Status Row */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Type de Shift</Label>
                                    <Select
                                        value={data.type}
                                        onValueChange={(value) => setData('type', value as ShiftType)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {shiftTypes.map((type) => (
                                                <SelectItem key={type.value} value={type.value}>
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${TYPE_COLORS[type.value]}`}>
                                                        {type.label}
                                                    </span>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.type && <p className="text-sm text-red-600">{errors.type}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label>Statut</Label>
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) => setData('status', value as ShiftStatus)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {shiftStatuses.map((status) => (
                                                <SelectItem key={status.value} value={status.value}>
                                                    {status.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="text-sm text-red-600">{errors.status}</p>}
                                </div>
                            </div>

                            {/* Time Row */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="start_time">
                                        <ClockIcon className="h-4 w-4 inline mr-1" />
                                        Heure de début
                                    </Label>
                                    <Input
                                        id="start_time"
                                        type="time"
                                        value={data.start_time}
                                        onChange={(e) => setData('start_time', e.target.value)}
                                    />
                                    {errors.start_time && <p className="text-sm text-red-600">{errors.start_time}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="end_time">Heure de fin</Label>
                                    <Input
                                        id="end_time"
                                        type="time"
                                        value={data.end_time}
                                        onChange={(e) => setData('end_time', e.target.value)}
                                    />
                                    {errors.end_time && <p className="text-sm text-red-600">{errors.end_time}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="break_duration">Pause (minutes)</Label>
                                    <Input
                                        id="break_duration"
                                        type="number"
                                        min="0"
                                        step="5"
                                        value={data.break_duration}
                                        onChange={(e) => setData('break_duration', parseInt(e.target.value) || 0)}
                                    />
                                    {errors.break_duration && <p className="text-sm text-red-600">{errors.break_duration}</p>}
                                </div>
                            </div>

                            {/* Title and Location */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">Titre</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="Ex: Accueil, Service..."
                                    />
                                    {errors.title && <p className="text-sm text-red-600">{errors.title}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="location">Lieu</Label>
                                    <Input
                                        id="location"
                                        value={data.location}
                                        onChange={(e) => setData('location', e.target.value)}
                                        placeholder="Ex: Bureau A, Salle 1..."
                                    />
                                    {errors.location && <p className="text-sm text-red-600">{errors.location}</p>}
                                </div>
                            </div>

                            {/* Color */}
                            <div className="space-y-2">
                                <Label htmlFor="color">Couleur personnalisée</Label>
                                <div className="flex items-center gap-2">
                                    <Input
                                        id="color"
                                        type="color"
                                        value={data.color || '#3B82F6'}
                                        onChange={(e) => setData('color', e.target.value)}
                                        className="w-16 h-10 p-1"
                                    />
                                    <Input
                                        value={data.color}
                                        onChange={(e) => setData('color', e.target.value)}
                                        placeholder="#3B82F6"
                                        className="flex-1"
                                    />
                                    {data.color && (
                                        <Button type="button" variant="ghost" size="sm" onClick={() => setData('color', '')}>
                                            Réinitialiser
                                        </Button>
                                    )}
                                </div>
                            </div>

                            {/* Assignment and Position */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                <div className="space-y-2">
                                    <Label>
                                        <UserIcon className="h-4 w-4 inline mr-1" />
                                        Personnes assignées
                                    </Label>
                                    <div className="flex flex-wrap gap-2 mb-2">
                                        {(['all', 'user', 'employee', 'star'] as const).map(type => (
                                            <Button
                                                key={type}
                                                type="button"
                                                variant={assigneeType === type ? 'default' : 'outline'}
                                                size="sm"
                                                onClick={() => setAssigneeType(type)}
                                                className={assigneeType === type ? '' : type === 'user' ? 'border-blue-300 text-blue-700 hover:bg-blue-50' : type === 'employee' ? 'border-green-300 text-green-700 hover:bg-green-50' : type === 'star' ? 'border-yellow-300 text-yellow-700 hover:bg-yellow-50' : ''}
                                            >
                                                {type === 'all' && `Tous (${users.length + employees.length + stars.length})`}
                                                {type === 'user' && `Membres (${users.length})`}
                                                {type === 'employee' && `Employés (${employees.length})`}
                                                {type === 'star' && `Stars (${stars.length})`}
                                            </Button>
                                        ))}
                                    </div>
                                    <SearchableMultiSelect
                                        id="user_ids"
                                        options={assigneeOptions}
                                        value={data.user_ids}
                                        onChange={(values) => setData('user_ids', values as number[])}
                                        placeholder="Sélectionner les personnes..."
                                        noOptionsMessage="Aucune personne trouvée"
                                    />
                                    {errors.user_ids && <p className="text-sm text-red-600">{errors.user_ids}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label>
                                        <BriefcaseIcon className="h-4 w-4 inline mr-1" />
                                        Poste
                                    </Label>
                                    <Select
                                        value={data.position_id?.toString() || ''}
                                        onValueChange={(value) => setData('position_id', value === '' ? '' : parseInt(value))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Aucun poste" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Aucun poste</SelectItem>
                                            {positions.map((position) => (
                                                <SelectItem key={position.id} value={position.id.toString()}>
                                                    {position.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.position_id && <p className="text-sm text-red-600">{errors.position_id}</p>}
                                </div>
                            </div>

                            {/* Capacity */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="min_employees">Min. employés</Label>
                                    <Input
                                        id="min_employees"
                                        type="number"
                                        min="1"
                                        value={data.min_employees}
                                        onChange={(e) => setData('min_employees', parseInt(e.target.value) || 1)}
                                    />
                                    {errors.min_employees && <p className="text-sm text-red-600">{errors.min_employees}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="max_employees">Max. employés</Label>
                                    <Input
                                        id="max_employees"
                                        type="number"
                                        min="1"
                                        value={data.max_employees}
                                        onChange={(e) => setData('max_employees', parseInt(e.target.value) || 1)}
                                    />
                                    {errors.max_employees && <p className="text-sm text-red-600">{errors.max_employees}</p>}
                                </div>
                            </div>

                            {/* Options */}
                            <div className="flex flex-wrap gap-6">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_overtime"
                                        checked={data.is_overtime}
                                        onCheckedChange={(checked) => setData('is_overtime', checked === true)}
                                    />
                                    <Label htmlFor="is_overtime" className="cursor-pointer">Heures supplémentaires</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="requires_approval"
                                        checked={data.requires_approval}
                                        onCheckedChange={(checked) => setData('requires_approval', checked === true)}
                                    />
                                    <Label htmlFor="requires_approval" className="cursor-pointer">Nécessite approbation</Label>
                                </div>
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Instructions, tâches spécifiques..."
                                    rows={3}
                                />
                                {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                            </div>

                            {/* Notes */}
                            <div className="space-y-2">
                                <Label htmlFor="notes">Notes internes</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Notes pour les managers..."
                                    rows={2}
                                />
                                {errors.notes && <p className="text-sm text-red-600">{errors.notes}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="mt-6 flex justify-end space-x-4">
                        <Link href={`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts/${shift.uuid}`}>
                            <Button type="button" variant="outline">Annuler</Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Enregistrement...' : 'Enregistrer les modifications'}
                        </Button>
                    </div>
                </form>
            </div>

            {/* Series scope dialog */}
            <Dialog open={showScopeDialog} onOpenChange={setShowScopeDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ArrowPathIcon className="h-5 w-5 text-blue-500" />
                            Modifier la série
                        </DialogTitle>
                        <DialogDescription>
                            Ce shift fait partie d'une série récurrente. Quels shifts souhaitez-vous modifier ?
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex flex-col gap-3 py-2 px-6">
                        <button
                            type="button"
                            onClick={() => submitForm('single')}
                            className="flex flex-col items-start gap-1 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-primary hover:bg-primary/5 transition-colors text-left"
                        >
                            <span className="font-medium text-gray-900 dark:text-white">Ce shift uniquement</span>
                            <span className="text-sm text-gray-500">Modifier seulement ce shift ({new Date(shift.date).toLocaleDateString('fr-FR')})</span>
                        </button>

                        <button
                            type="button"
                            onClick={() => submitForm('following')}
                            className="flex flex-col items-start gap-1 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-primary hover:bg-primary/5 transition-colors text-left"
                        >
                            <span className="font-medium text-gray-900 dark:text-white">Ce shift et les suivants</span>
                            <span className="text-sm text-gray-500">Modifier ce shift et tous les shifts suivants de la série</span>
                        </button>

                        <button
                            type="button"
                            onClick={() => submitForm('all')}
                            className="flex flex-col items-start gap-1 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-primary hover:bg-primary/5 transition-colors text-left"
                        >
                            <span className="font-medium text-gray-900 dark:text-white">Tous les shifts de la série</span>
                            <span className="text-sm text-gray-500">Modifier tous les shifts de cette série (passés et futurs)</span>
                        </button>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setShowScopeDialog(false)}>
                            Annuler
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </DashboardLayout>
    );
}
