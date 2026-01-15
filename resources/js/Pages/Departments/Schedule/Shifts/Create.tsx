import React, { useState, useEffect, useMemo } from 'react';
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
    ArrowLeftIcon,
    CalendarDaysIcon,
    ClockIcon,
    UserIcon,
    BriefcaseIcon,
} from '@heroicons/react/24/outline';
import type { ShiftType, EnumOption } from '@/Types/scheduling';

interface WeeklyScheduleProps {
    id: number;
    uuid: string;
    week_start: string;
    week_end: string;
    status: string;
    notes: string | null;
}

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

interface ShiftTypeOption extends EnumOption<ShiftType> {
    defaultDuration: number;
}

interface Props {
    department: Department;
    schedule: WeeklyScheduleProps;
    users: Assignable[];
    employees: Assignable[];
    stars: Assignable[];
    positions: Position[];
    shiftTypes: ShiftTypeOption[];
}

export default function ShiftCreate({
    department,
    schedule,
    users,
    employees,
    stars,
    positions,
    shiftTypes,
}: Props) {
    const [assigneeType, setAssigneeType] = useState<'all' | 'user' | 'employee' | 'star'>('all');
    const { data, setData, post, processing, errors, reset } = useForm({
        date: '',
        type: 'morning' as ShiftType,
        start_time: '08:00',
        end_time: '16:00',
        break_duration: 30,
        title: '',
        description: '',
        location: '',
        user_ids: [] as number[],
        position_id: '' as string | number,
        min_employees: 1,
        max_employees: 1,
        is_overtime: false,
        requires_approval: false,
        notes: '',
    });

    // Update times when shift type changes
    useEffect(() => {
        const selectedType = shiftTypes.find(t => t.value === data.type);
        if (selectedType) {
            // Update with default hours based on type
            const defaultHours: Record<ShiftType, [string, string]> = {
                morning: ['06:00', '14:00'],
                afternoon: ['14:00', '22:00'],
                evening: ['18:00', '23:00'],
                night: ['22:00', '06:00'],
                full_day: ['08:00', '17:00'],
                split: ['08:00', '12:00'],
                on_call: ['00:00', '23:59'],
                custom: ['09:00', '17:00'],
            };
            const [start, end] = defaultHours[data.type] || ['09:00', '17:00'];
            setData(prev => ({
                ...prev,
                start_time: start,
                end_time: end,
            }));
        }
    }, [data.type]);

    // Generate dates for the week
    const weekDates = React.useMemo(() => {
        const dates: { value: string; label: string }[] = [];
        const weekStart = new Date(schedule.week_start);

        for (let i = 0; i < 7; i++) {
            const date = new Date(weekStart);
            date.setDate(date.getDate() + i);
            const value = date.toISOString().split('T')[0];
            const label = date.toLocaleDateString('fr-FR', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
            });
            dates.push({ value, label });
        }
        return dates;
    }, [schedule.week_start]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/departments/${department.uuid}/schedule/${schedule.uuid}/shifts`, {
            onSuccess: () => {
                reset();
            },
        });
    };

    const getShiftTypeColor = (type: ShiftType) => {
        const colors: Record<ShiftType, string> = {
            morning: 'bg-yellow-100 text-yellow-800',
            afternoon: 'bg-orange-100 text-orange-800',
            evening: 'bg-purple-100 text-purple-800',
            night: 'bg-indigo-100 text-indigo-800',
            full_day: 'bg-blue-100 text-blue-800',
            split: 'bg-pink-100 text-pink-800',
            on_call: 'bg-gray-100 text-gray-800',
            custom: 'bg-slate-100 text-slate-800',
        };
        return colors[type] || 'bg-gray-100 text-gray-800';
    };

    const getAssigneeTypeLabel = (type: 'user' | 'employee' | 'star') => {
        const labels = {
            user: 'Membre',
            employee: 'Employé',
            star: 'Star',
        };
        return labels[type];
    };

    const getAssigneeTypeBadgeColor = (type: 'user' | 'employee' | 'star') => {
        const colors = {
            user: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            employee: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            star: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        };
        return colors[type];
    };

    // Combine and filter assignees based on selected type
    const allAssignees = useMemo(() => {
        const combined: Assignable[] = [];

        if (assigneeType === 'all' || assigneeType === 'user') {
            combined.push(...users);
        }
        if (assigneeType === 'all' || assigneeType === 'employee') {
            combined.push(...employees);
        }
        if (assigneeType === 'all' || assigneeType === 'star') {
            combined.push(...stars);
        }

        // Remove duplicates by user id
        const seen = new Set<number>();
        return combined.filter(a => {
            if (seen.has(a.id)) return false;
            seen.add(a.id);
            return true;
        });
    }, [users, employees, stars, assigneeType]);

    // Transform assignees to SelectOption format for SearchableMultiSelect
    const assigneeOptions: SelectOption[] = useMemo(() => {
        return allAssignees.map(assignee => ({
            value: assignee.id,
            label: `${assignee.first_name} ${assignee.last_name} (${getAssigneeTypeLabel(assignee.type)})${assignee.position ? ` - ${assignee.position}` : ''}${assignee.title ? ` - ${assignee.title}` : ''}`,
        }));
    }, [allAssignees]);

    return (
        <DashboardLayout>
            <Head title={`Nouveau Shift - ${department.name}`} />

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
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Nouveau Shift
                    </h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        {department.name} - Semaine du{' '}
                        {new Date(schedule.week_start).toLocaleDateString('fr-FR', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                        })}
                    </p>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Informations du Shift</CardTitle>
                            <CardDescription>
                                Remplissez les informations pour créer un nouveau shift
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Date and Type Row */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="date">
                                        <CalendarDaysIcon className="h-4 w-4 inline mr-1" />
                                        Date *
                                    </Label>
                                    <Select
                                        value={data.date}
                                        onValueChange={(value) => setData('date', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner une date" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {weekDates.map((date) => (
                                                <SelectItem key={date.value} value={date.value}>
                                                    {date.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.date && (
                                        <p className="text-sm text-red-600">{errors.date}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="type">Type de Shift *</Label>
                                    <Select
                                        value={data.type}
                                        onValueChange={(value) => setData('type', value as ShiftType)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner un type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {shiftTypes.map((type) => (
                                                <SelectItem key={type.value} value={type.value}>
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getShiftTypeColor(type.value)}`}>
                                                        {type.label}
                                                    </span>
                                                    <span className="ml-2 text-gray-500 text-xs">
                                                        ({type.defaultDuration}h)
                                                    </span>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.type && (
                                        <p className="text-sm text-red-600">{errors.type}</p>
                                    )}
                                </div>
                            </div>

                            {/* Time Row */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="start_time">
                                        <ClockIcon className="h-4 w-4 inline mr-1" />
                                        Heure de début *
                                    </Label>
                                    <Input
                                        id="start_time"
                                        type="time"
                                        value={data.start_time}
                                        onChange={(e) => setData('start_time', e.target.value)}
                                    />
                                    {errors.start_time && (
                                        <p className="text-sm text-red-600">{errors.start_time}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="end_time">Heure de fin *</Label>
                                    <Input
                                        id="end_time"
                                        type="time"
                                        value={data.end_time}
                                        onChange={(e) => setData('end_time', e.target.value)}
                                    />
                                    {errors.end_time && (
                                        <p className="text-sm text-red-600">{errors.end_time}</p>
                                    )}
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
                                    {errors.break_duration && (
                                        <p className="text-sm text-red-600">{errors.break_duration}</p>
                                    )}
                                </div>
                            </div>

                            {/* Title and Location */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="title">Titre (optionnel)</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="Ex: Accueil, Service..."
                                    />
                                    {errors.title && (
                                        <p className="text-sm text-red-600">{errors.title}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="location">Lieu (optionnel)</Label>
                                    <Input
                                        id="location"
                                        value={data.location}
                                        onChange={(e) => setData('location', e.target.value)}
                                        placeholder="Ex: Bureau A, Salle 1..."
                                    />
                                    {errors.location && (
                                        <p className="text-sm text-red-600">{errors.location}</p>
                                    )}
                                </div>
                            </div>

                            {/* Assignment and Position */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                <div className="space-y-2">
                                    <Label htmlFor="user_ids">
                                        <UserIcon className="h-4 w-4 inline mr-1" />
                                        Personnes assignées
                                    </Label>

                                    {/* Filter tabs */}
                                    <div className="flex flex-wrap gap-2 mb-2">
                                        <Button
                                            type="button"
                                            variant={assigneeType === 'all' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setAssigneeType('all')}
                                        >
                                            Tous ({users.length + employees.length + stars.length})
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={assigneeType === 'user' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setAssigneeType('user')}
                                            className={assigneeType === 'user' ? '' : 'border-blue-300 text-blue-700 hover:bg-blue-50'}
                                        >
                                            Membres ({users.length})
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={assigneeType === 'employee' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setAssigneeType('employee')}
                                            className={assigneeType === 'employee' ? '' : 'border-green-300 text-green-700 hover:bg-green-50'}
                                        >
                                            Employés ({employees.length})
                                        </Button>
                                        <Button
                                            type="button"
                                            variant={assigneeType === 'star' ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => setAssigneeType('star')}
                                            className={assigneeType === 'star' ? '' : 'border-yellow-300 text-yellow-700 hover:bg-yellow-50'}
                                        >
                                            Stars ({stars.length})
                                        </Button>
                                    </div>

                                    <SearchableMultiSelect
                                        id="user_ids"
                                        options={assigneeOptions}
                                        value={data.user_ids}
                                        onChange={(values) => setData('user_ids', values as number[])}
                                        placeholder="Sélectionner les personnes..."
                                        noOptionsMessage="Aucune personne trouvée"
                                    />
                                    {errors.user_ids && (
                                        <p className="text-sm text-red-600">{errors.user_ids}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="position_id">
                                        <BriefcaseIcon className="h-4 w-4 inline mr-1" />
                                        Poste
                                    </Label>
                                    <Select
                                        value={data.position_id.toString()}
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
                                    {errors.position_id && (
                                        <p className="text-sm text-red-600">{errors.position_id}</p>
                                    )}
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
                                    {errors.min_employees && (
                                        <p className="text-sm text-red-600">{errors.min_employees}</p>
                                    )}
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
                                    {errors.max_employees && (
                                        <p className="text-sm text-red-600">{errors.max_employees}</p>
                                    )}
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
                                    <Label htmlFor="is_overtime" className="cursor-pointer">
                                        Heures supplémentaires
                                    </Label>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="requires_approval"
                                        checked={data.requires_approval}
                                        onCheckedChange={(checked) => setData('requires_approval', checked === true)}
                                    />
                                    <Label htmlFor="requires_approval" className="cursor-pointer">
                                        Nécessite approbation
                                    </Label>
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
                                {errors.description && (
                                    <p className="text-sm text-red-600">{errors.description}</p>
                                )}
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
                                {errors.notes && (
                                    <p className="text-sm text-red-600">{errors.notes}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="mt-6 flex justify-end space-x-4">
                        <Link
                            href={`/departments/${department.uuid}/schedule?week=${schedule.week_start}`}
                        >
                            <Button type="button" variant="outline">
                                Annuler
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Création...' : 'Créer le Shift'}
                        </Button>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
