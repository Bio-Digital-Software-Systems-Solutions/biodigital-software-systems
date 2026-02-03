import React, { useState, useMemo } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeftIcon, CalendarIcon } from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { SearchableSelect, SearchableMultiSelect } from '@/Components/ui/searchable-select';
import { Calendar } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

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

interface Props {
    users: Assignable[];
    employees: Assignable[];
    stars: Assignable[];
}

export default function CreateProject({ users = [], employees = [], stars = [] }: Props) {
    const [managerType, setManagerType] = useState<'all' | 'user' | 'employee' | 'star'>('all');
    const [participantType, setParticipantType] = useState<'all' | 'user' | 'employee' | 'star'>('all');
    const [startDateOpen, setStartDateOpen] = useState(false);
    const [endDateOpen, setEndDateOpen] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        status: 'planning',
        priority: 'medium',
        color: '#3B82F6',
        start_date: '',
        end_date: '',
        budget: '',
        project_manager_id: '' as string | number,
        participants: [] as number[],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('projects.store'));
    };

    // Combine and filter assignees based on selected type for manager
    const filteredManagers = useMemo(() => {
        const combined: Assignable[] = [];

        if (managerType === 'all' || managerType === 'user') {
            combined.push(...users);
        }
        if (managerType === 'all' || managerType === 'employee') {
            combined.push(...employees);
        }
        if (managerType === 'all' || managerType === 'star') {
            combined.push(...stars);
        }

        // Remove duplicates by user id
        const seen = new Set<number>();
        return combined.filter(a => {
            if (seen.has(a.id)) return false;
            seen.add(a.id);
            return true;
        });
    }, [users, employees, stars, managerType]);

    // Combine and filter assignees based on selected type for participants
    const filteredParticipants = useMemo(() => {
        const combined: Assignable[] = [];

        if (participantType === 'all' || participantType === 'user') {
            combined.push(...users);
        }
        if (participantType === 'all' || participantType === 'employee') {
            combined.push(...employees);
        }
        if (participantType === 'all' || participantType === 'star') {
            combined.push(...stars);
        }

        // Remove duplicates by user id
        const seen = new Set<number>();
        return combined.filter(a => {
            if (seen.has(a.id)) return false;
            seen.add(a.id);
            return true;
        });
    }, [users, employees, stars, participantType]);

    // Convert to options for SearchableSelect (manager)
    const managerOptions = useMemo(() => {
        return filteredManagers.map(a => {
            const typeLabel = a.type === 'user' ? 'Utilisateur' : a.type === 'employee' ? 'Employé' : 'Star';
            const extra = a.position || a.title || '';
            return {
                value: a.id,
                label: `${a.first_name} ${a.last_name} - ${typeLabel}${extra ? ` (${extra})` : ''}`,
            };
        });
    }, [filteredManagers]);

    // Convert to options for SearchableMultiSelect (participants)
    const participantOptions = useMemo(() => {
        return filteredParticipants.map(a => {
            const typeLabel = a.type === 'user' ? 'Utilisateur' : a.type === 'employee' ? 'Employé' : 'Star';
            const extra = a.position || a.title || '';
            return {
                value: a.id,
                label: `${a.first_name} ${a.last_name} - ${typeLabel}${extra ? ` (${extra})` : ''}`,
            };
        });
    }, [filteredParticipants]);

    // Status options
    const statusOptions = [
        { value: 'planning', label: 'Planification' },
        { value: 'active', label: 'Actif' },
        { value: 'on_hold', label: 'En pause' },
        { value: 'completed', label: 'Terminé' },
        { value: 'cancelled', label: 'Annulé' },
    ];

    // Priority options
    const priorityOptions = [
        { value: 'lowest', label: 'Très basse' },
        { value: 'low', label: 'Basse' },
        { value: 'medium', label: 'Moyenne' },
        { value: 'high', label: 'Haute' },
        { value: 'highest', label: 'Très haute' },
    ];

    // Handle start date selection
    const handleStartDateSelect = (date: Date | undefined) => {
        if (date) {
            setData('start_date', format(date, 'yyyy-MM-dd'));
        } else {
            setData('start_date', '');
        }
        setStartDateOpen(false);
    };

    // Handle end date selection
    const handleEndDateSelect = (date: Date | undefined) => {
        if (date) {
            setData('end_date', format(date, 'yyyy-MM-dd'));
        } else {
            setData('end_date', '');
        }
        setEndDateOpen(false);
    };

    // Filter button component
    const FilterButtons = ({
        type,
        setType,
    }: {
        type: 'all' | 'user' | 'employee' | 'star';
        setType: (t: 'all' | 'user' | 'employee' | 'star') => void;
    }) => (
        <div className="flex gap-1">
            <Button
                type="button"
                variant={type === 'all' ? 'default' : 'outline'}
                size="sm"
                className="h-6 px-2 text-xs"
                onClick={() => setType('all')}
            >
                Tous
            </Button>
            <Button
                type="button"
                variant={type === 'employee' ? 'default' : 'outline'}
                size="sm"
                className={`h-6 px-2 text-xs ${type === 'employee' ? '' : 'border-green-300 text-green-700 hover:bg-green-50 dark:border-green-700 dark:text-green-400 dark:hover:bg-green-900/20'}`}
                onClick={() => setType('employee')}
            >
                Employés
            </Button>
            <Button
                type="button"
                variant={type === 'star' ? 'default' : 'outline'}
                size="sm"
                className={`h-6 px-2 text-xs ${type === 'star' ? '' : 'border-yellow-300 text-yellow-700 hover:bg-yellow-50 dark:border-yellow-700 dark:text-yellow-400 dark:hover:bg-yellow-900/20'}`}
                onClick={() => setType('star')}
            >
                Stars
            </Button>
        </div>
    );

    return (
        <DashboardLayout>
            <Head title="Créer un projet" />

            <div className="p-6">
                <Card>
                    <CardHeader>
                        <div className="flex items-center">
                            <Link
                                href={route('projects.index')}
                                className="flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 mr-4"
                            >
                                <ArrowLeftIcon className="w-4 h-4 mr-1" />
                                Retour
                            </Link>
                            <CardTitle>Créer un nouveau projet</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Name */}
                            <div className="space-y-2">
                                <Label htmlFor="name">Nom du projet *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Nom du projet"
                                    required
                                />
                                {errors.name && (
                                    <p className="text-sm text-red-600">{errors.name}</p>
                                )}
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <Label htmlFor="description">Description *</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={4}
                                    placeholder="Description du projet (minimum 10 caractères)..."
                                    required
                                    minLength={10}
                                />
                                {errors.description && (
                                    <p className="text-sm text-red-600">{errors.description}</p>
                                )}
                            </div>

                            {/* Status, Priority, Color */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="space-y-2">
                                    <Label>Statut *</Label>
                                    <SearchableSelect
                                        options={statusOptions}
                                        value={data.status}
                                        onChange={(val) => setData('status', (val as string) ?? 'planning')}
                                        placeholder="Sélectionner un statut"
                                        isClearable={false}
                                    />
                                    {errors.status && (
                                        <p className="text-sm text-red-600">{errors.status}</p>
                                    )}
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
                                    {errors.priority && (
                                        <p className="text-sm text-red-600">{errors.priority}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="color">Couleur</Label>
                                    <Input
                                        id="color"
                                        type="color"
                                        value={data.color}
                                        onChange={(e) => setData('color', e.target.value)}
                                        className="h-[38px]"
                                    />
                                    {errors.color && (
                                        <p className="text-sm text-red-600">{errors.color}</p>
                                    )}
                                </div>
                            </div>

                            {/* Dates */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label>Date de début</Label>
                                    <Popover open={startDateOpen} onOpenChange={setStartDateOpen}>
                                        <PopoverTrigger asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className={`w-full justify-start text-left font-normal ${!data.start_date && 'text-muted-foreground'}`}
                                            >
                                                <CalendarIcon className="mr-2 h-4 w-4" />
                                                {data.start_date
                                                    ? format(new Date(data.start_date), 'PPP', { locale: fr })
                                                    : 'Sélectionner une date'}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-auto p-0" align="start" side="top">
                                            <Calendar
                                                mode="single"
                                                selected={data.start_date ? new Date(data.start_date) : undefined}
                                                onSelect={handleStartDateSelect}
                                                locale={fr}
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    {errors.start_date && (
                                        <p className="text-sm text-red-600">{errors.start_date}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label>Date de fin</Label>
                                    <Popover open={endDateOpen} onOpenChange={setEndDateOpen}>
                                        <PopoverTrigger asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className={`w-full justify-start text-left font-normal ${!data.end_date && 'text-muted-foreground'}`}
                                            >
                                                <CalendarIcon className="mr-2 h-4 w-4" />
                                                {data.end_date
                                                    ? format(new Date(data.end_date), 'PPP', { locale: fr })
                                                    : 'Sélectionner une date'}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-auto p-0" align="start" side="top">
                                            <Calendar
                                                mode="single"
                                                selected={data.end_date ? new Date(data.end_date) : undefined}
                                                onSelect={handleEndDateSelect}
                                                disabled={(date) =>
                                                    data.start_date ? date < new Date(data.start_date) : false
                                                }
                                                locale={fr}
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    {errors.end_date && (
                                        <p className="text-sm text-red-600">{errors.end_date}</p>
                                    )}
                                </div>
                            </div>

                            {/* Budget */}
                            <div className="space-y-2">
                                <Label htmlFor="budget">Budget (€)</Label>
                                <Input
                                    id="budget"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.budget}
                                    onChange={(e) => setData('budget', e.target.value)}
                                    placeholder="Ex: 10000.00"
                                />
                                {errors.budget && (
                                    <p className="text-sm text-red-600">{errors.budget}</p>
                                )}
                            </div>

                            {/* Project Manager */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label>Chef de projet</Label>
                                    <FilterButtons type={managerType} setType={setManagerType} />
                                </div>
                                <SearchableSelect
                                    options={managerOptions}
                                    value={data.project_manager_id}
                                    onChange={(val) => setData('project_manager_id', val ?? '')}
                                    placeholder="Sélectionner un chef de projet"
                                    noOptionsMessage="Aucun utilisateur trouvé"
                                    isClearable
                                />
                                {errors.project_manager_id && (
                                    <p className="text-sm text-red-600">{errors.project_manager_id}</p>
                                )}
                            </div>

                            {/* Participants */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label>Participants</Label>
                                    <FilterButtons type={participantType} setType={setParticipantType} />
                                </div>
                                <SearchableMultiSelect
                                    options={participantOptions}
                                    value={data.participants}
                                    onChange={(val) => setData('participants', val as number[])}
                                    placeholder="Sélectionner des participants"
                                    noOptionsMessage="Aucun utilisateur trouvé"
                                    isClearable
                                />
                                {errors.participants && (
                                    <p className="text-sm text-red-600">{errors.participants}</p>
                                )}
                            </div>

                            {/* Actions */}
                            <div className="flex justify-end gap-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    asChild
                                >
                                    <Link href={route('projects.index')}>
                                        Annuler
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Création...' : 'Créer le projet'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
