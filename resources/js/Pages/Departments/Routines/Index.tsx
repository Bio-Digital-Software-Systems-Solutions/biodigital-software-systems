import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import {
    ArrowLeftIcon,
    PlusIcon,
    ArrowPathIcon,
    ClockIcon,
    UserIcon,
} from '@heroicons/react/24/outline';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { toast } from 'sonner';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface User {
    id: number;
    first_name: string;
    last_name: string;
}

interface RoutineItem {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    status: string;
    frequency: string;
    responsible: User | null;
    creator: User;
    is_active: boolean;
    all_steps_count: number;
    all_sops_count: number;
    estimated_duration_minutes: number | null;
    created_at: string;
}

interface EnumOption {
    value: string;
    label: string;
    color?: string;
}

interface Filters {
    status?: string;
    frequency?: string;
    search?: string;
}

interface PaginatedRoutines {
    data: RoutineItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    department: Department;
    routines: PaginatedRoutines;
    statuses: EnumOption[];
    frequencies: EnumOption[];
    filters: Filters;
}

const statusColorMap: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    pending_approval: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    archived: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
};

export default function RoutinesIndex({ department, routines, statuses, frequencies, filters }: Props) {
    const [localFilters, setLocalFilters] = useState<Filters>(filters);
    const [deletingRoutine, setDeletingRoutine] = useState<RoutineItem | null>(null);

    const applyFilters = () => {
        router.get(`/departments/${department.uuid}/routines`, localFilters as Record<string, string>, {
            preserveState: true,
        });
    };

    const clearFilters = () => {
        setLocalFilters({});
        router.get(`/departments/${department.uuid}/routines`);
    };

    const handleDelete = () => {
        if (!deletingRoutine) return;
        router.delete(`/departments/${department.uuid}/routines/${deletingRoutine.uuid}`, {
            onSuccess: () => {
                toast.success('Routine supprimée');
                setDeletingRoutine(null);
            },
            onError: () => toast.error('Erreur lors de la suppression'),
        });
    };

    const getStatusLabel = (value: string) => statuses.find(s => s.value === value)?.label ?? value;
    const getFrequencyLabel = (value: string) => frequencies.find(f => f.value === value)?.label ?? value;

    return (
        <DashboardLayout>
            <Head title={`Routines - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-4">
                        <Link href={`/departments/${department.uuid}`}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeftIcon className="h-4 w-4 mr-1" />
                                Retour
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Routines</h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">{department.name}</p>
                        </div>
                    </div>
                    <Link href={`/departments/${department.uuid}/routines/create`}>
                        <Button>
                            <PlusIcon className="h-4 w-4 mr-1" />
                            Nouvelle routine
                        </Button>
                    </Link>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="flex-1 min-w-[200px]">
                                <Input
                                    placeholder="Rechercher..."
                                    value={localFilters.search ?? ''}
                                    onChange={e => setLocalFilters({ ...localFilters, search: e.target.value })}
                                    onKeyDown={e => e.key === 'Enter' && applyFilters()}
                                />
                            </div>
                            <Select
                                value={localFilters.status ?? ''}
                                onValueChange={v => setLocalFilters({ ...localFilters, status: v || undefined })}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Statut" />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map(s => (
                                        <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={localFilters.frequency ?? ''}
                                onValueChange={v => setLocalFilters({ ...localFilters, frequency: v || undefined })}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Fréquence" />
                                </SelectTrigger>
                                <SelectContent>
                                    {frequencies.map(f => (
                                        <SelectItem key={f.value} value={f.value}>{f.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button onClick={applyFilters} size="sm">Filtrer</Button>
                            <Button onClick={clearFilters} variant="ghost" size="sm">Réinitialiser</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Routines list */}
                {routines.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-12 text-center">
                            <ArrowPathIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-1">Aucune routine</h3>
                            <p className="text-sm text-gray-500 dark:text-gray-400">Créez votre première routine pour ce département.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {routines.data.map(routine => (
                            <Card key={routine.uuid} className="hover:shadow-md transition-shadow">
                                <CardContent className="py-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-1">
                                                <Link
                                                    href={`/departments/${department.uuid}/routines/${routine.uuid}`}
                                                    className="text-lg font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400"
                                                >
                                                    {routine.name}
                                                </Link>
                                                <Badge className={statusColorMap[routine.status] ?? ''}>
                                                    {getStatusLabel(routine.status)}
                                                </Badge>
                                            </div>
                                            {routine.description && (
                                                <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-1 mb-2">
                                                    {routine.description}
                                                </p>
                                            )}
                                            <div className="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                <span className="flex items-center gap-1">
                                                    <ArrowPathIcon className="h-3.5 w-3.5" />
                                                    {getFrequencyLabel(routine.frequency)}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <ClockIcon className="h-3.5 w-3.5" />
                                                    {routine.all_steps_count} étape{routine.all_steps_count !== 1 ? 's' : ''}
                                                </span>
                                                {routine.responsible && (
                                                    <span className="flex items-center gap-1">
                                                        <UserIcon className="h-3.5 w-3.5" />
                                                        {routine.responsible.first_name} {routine.responsible.last_name}
                                                    </span>
                                                )}
                                                {routine.estimated_duration_minutes && (
                                                    <span>~{routine.estimated_duration_minutes} min</span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Link href={`/departments/${department.uuid}/routines/${routine.uuid}`}>
                                                <Button variant="outline" size="sm">Voir</Button>
                                            </Link>
                                            {routine.status === 'draft' && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-red-600 hover:text-red-700"
                                                    onClick={() => setDeletingRoutine(routine)}
                                                >
                                                    Supprimer
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}

                        {/* Pagination */}
                        {routines.last_page > 1 && (
                            <div className="flex justify-center gap-2 mt-6">
                                {routines.links.map((link, i) => (
                                    <Button
                                        key={i}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>

            <DeleteConfirmationDialog
                open={!!deletingRoutine}
                onOpenChange={() => setDeletingRoutine(null)}
                onConfirm={handleDelete}
                title="Supprimer la routine"
                description={`Êtes-vous sûr de vouloir supprimer la routine "${deletingRoutine?.name}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
}
