import React, { useState, useMemo } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeftIcon, CalendarIcon } from '@heroicons/react/24/outline';
import { Status, PageProps } from '@/Types';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Calendar } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

interface Project {
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

interface Props extends PageProps {
    projects: Project[];
    statuses: Status[];
    users: Assignable[];
    employees: Assignable[];
    stars: Assignable[];
    projectId?: string;
}

export default function Create({ projects, statuses, users, employees = [], stars = [], projectId }: Props) {
    const [assigneeType, setAssigneeType] = useState<'all' | 'user' | 'employee' | 'star'>('all');
    const [dueDateOpen, setDueDateOpen] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        due_date: '',
        priority: 'medium',
        estimated_hours: '',
        notes: '',
        status_id: '' as string | number,
        project_id: projectId || '' as string | number,
        assigned_to: '' as string | number,
        from_project: projectId || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('tasks.store'));
    };

    // Combine and filter assignees based on selected type
    const filteredAssignees = useMemo(() => {
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

    // Convert to options for SearchableSelect
    const assigneeOptions = useMemo(() => {
        return filteredAssignees.map(a => {
            const typeLabel = a.type === 'user' ? 'Utilisateur' : a.type === 'employee' ? 'Employé' : 'Star';
            const extra = a.position || a.title || '';
            return {
                value: a.id,
                label: `${a.first_name} ${a.last_name} - ${typeLabel}${extra ? ` (${extra})` : ''}`,
            };
        });
    }, [filteredAssignees]);

    // Project options
    const projectOptions = useMemo(() => {
        return projects.map(p => ({
            value: p.id,
            label: p.name,
        }));
    }, [projects]);

    // Status options
    const statusOptions = useMemo(() => {
        return statuses.map(s => ({
            value: s.id,
            label: s.name,
        }));
    }, [statuses]);

    // Priority options
    const priorityOptions = [
        { value: 'low', label: 'Basse' },
        { value: 'medium', label: 'Moyenne' },
        { value: 'high', label: 'Haute' },
    ];

    // Handle date selection
    const handleDateSelect = (date: Date | undefined) => {
        if (date) {
            setData('due_date', format(date, 'yyyy-MM-dd'));
        } else {
            setData('due_date', '');
        }
        setDueDateOpen(false);
    };

    return (
        <DashboardLayout>
            <Head title="Créer une Tâche" />

            <div className="p-4">
                <div className="mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <div className="flex items-center mb-6">
                                <Link
                                    href={
                                        projectId
                                            ? route('projects.show', projectId)
                                            : route('tasks.index')
                                    }
                                    className="flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mr-4"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-1" />
                                    {projectId ? 'Retour au Projet' : 'Retour aux Tâches'}
                                </Link>
                                <h1 className="text-2xl font-semibold">Créer une Tâche</h1>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Title and Project */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="title">Titre *</Label>
                                        <Input
                                            id="title"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            placeholder="Titre de la tâche"
                                            required
                                        />
                                        {errors.title && <p className="text-sm text-red-600">{errors.title}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label>Projet</Label>
                                        <SearchableSelect
                                            options={projectOptions}
                                            value={data.project_id}
                                            onChange={(val) => setData('project_id', val ?? '')}
                                            placeholder="Sélectionner un projet (optionnel)"
                                            noOptionsMessage="Aucun projet trouvé"
                                            isClearable
                                        />
                                        {errors.project_id && <p className="text-sm text-red-600">{errors.project_id}</p>}
                                    </div>
                                </div>

                                {/* Description */}
                                <div className="space-y-2">
                                    <Label htmlFor="description">Description *</Label>
                                    <Textarea
                                        id="description"
                                        rows={4}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Description de la tâche (minimum 10 caractères)..."
                                        required
                                        minLength={10}
                                    />
                                    {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                                </div>

                                {/* Status, Priority, Assigned To */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                                    <div className="space-y-2">
                                        <Label>Statut *</Label>
                                        <SearchableSelect
                                            options={statusOptions}
                                            value={data.status_id}
                                            onChange={(val) => setData('status_id', val ?? '')}
                                            placeholder="Sélectionner un statut"
                                            noOptionsMessage="Aucun statut trouvé"
                                        />
                                        {errors.status_id && <p className="text-sm text-red-600">{errors.status_id}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label>Priorité *</Label>
                                        <SearchableSelect
                                            options={priorityOptions}
                                            value={data.priority}
                                            onChange={(val) => setData('priority', (val as string) ?? 'medium')}
                                            placeholder="Sélectionner une priorité"
                                            isClearable={false}
                                        />
                                        {errors.priority && <p className="text-sm text-red-600">{errors.priority}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label>Assigné à</Label>
                                            {/* Filter tabs */}
                                            <div className="flex gap-1">
                                                <Button
                                                    type="button"
                                                    variant={assigneeType === 'all' ? 'default' : 'outline'}
                                                    size="sm"
                                                    className="h-6 px-2 text-xs"
                                                    onClick={() => setAssigneeType('all')}
                                                >
                                                    Tous
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant={assigneeType === 'employee' ? 'default' : 'outline'}
                                                    size="sm"
                                                    className={`h-6 px-2 text-xs ${assigneeType === 'employee' ? '' : 'border-green-300 text-green-700 hover:bg-green-50 dark:border-green-700 dark:text-green-400 dark:hover:bg-green-900/20'}`}
                                                    onClick={() => setAssigneeType('employee')}
                                                >
                                                    Employés
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant={assigneeType === 'star' ? 'default' : 'outline'}
                                                    size="sm"
                                                    className={`h-6 px-2 text-xs ${assigneeType === 'star' ? '' : 'border-yellow-300 text-yellow-700 hover:bg-yellow-50 dark:border-yellow-700 dark:text-yellow-400 dark:hover:bg-yellow-900/20'}`}
                                                    onClick={() => setAssigneeType('star')}
                                                >
                                                    Stars
                                                </Button>
                                            </div>
                                        </div>
                                        <SearchableSelect
                                            options={assigneeOptions}
                                            value={data.assigned_to}
                                            onChange={(val) => setData('assigned_to', val ?? '')}
                                            placeholder="Non assigné"
                                            noOptionsMessage="Aucun utilisateur trouvé"
                                            isClearable
                                        />
                                        {errors.assigned_to && <p className="text-sm text-red-600">{errors.assigned_to}</p>}
                                    </div>
                                </div>

                                {/* Due Date and Estimated Hours */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label>Date d'échéance</Label>
                                        <Popover open={dueDateOpen} onOpenChange={setDueDateOpen}>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className={`w-full justify-start text-left font-normal ${!data.due_date && 'text-muted-foreground'}`}
                                                >
                                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                                    {data.due_date
                                                        ? format(new Date(data.due_date), 'PPP', { locale: fr })
                                                        : 'Sélectionner une date'}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0" align="start" side="top">
                                                <Calendar
                                                    mode="single"
                                                    selected={data.due_date ? new Date(data.due_date) : undefined}
                                                    onSelect={handleDateSelect}
                                                    disabled={(date) => date < new Date(new Date().setHours(0, 0, 0, 0))}
                                                    locale={fr}
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        {errors.due_date && <p className="text-sm text-red-600">{errors.due_date}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="estimated_hours">Heures estimées</Label>
                                        <Input
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            id="estimated_hours"
                                            value={data.estimated_hours}
                                            onChange={(e) => setData('estimated_hours', e.target.value)}
                                            placeholder="Ex: 8"
                                        />
                                        {errors.estimated_hours && <p className="text-sm text-red-600">{errors.estimated_hours}</p>}
                                    </div>
                                </div>

                                {/* Notes */}
                                <div className="space-y-2">
                                    <Label htmlFor="notes">Notes</Label>
                                    <Textarea
                                        id="notes"
                                        rows={3}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        placeholder="Notes additionnelles..."
                                    />
                                    {errors.notes && <p className="text-sm text-red-600">{errors.notes}</p>}
                                </div>

                                {/* Actions */}
                                <div className="flex justify-end space-x-3">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        asChild
                                    >
                                        <Link
                                            href={
                                                projectId
                                                    ? route('projects.show', projectId)
                                                    : route('tasks.index')
                                            }
                                        >
                                            Annuler
                                        </Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Création...' : 'Créer la Tâche'}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
