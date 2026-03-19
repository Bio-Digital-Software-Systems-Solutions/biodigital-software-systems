import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
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
    email: string;
}

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    department: Department;
    departmentUsers: User[];
    frequencies: EnumOption[];
}

export default function RoutineCreate({ department, departmentUsers, frequencies }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        frequency: 'on_demand',
        responsible_id: '' as string | number,
        estimated_duration_minutes: '' as string | number,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/departments/${department.uuid}/routines`, {
            onSuccess: () => toast.success('Routine créée avec succès'),
            onError: () => toast.error('Erreur lors de la création'),
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Nouvelle routine - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div className="flex items-center gap-4 mb-6">
                    <Link href={`/departments/${department.uuid}/routines`}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeftIcon className="h-4 w-4 mr-1" />
                            Retour
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Nouvelle routine</h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400">{department.name}</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Informations de la routine</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <Label htmlFor="name">Nom *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={e => setData('name', e.target.value)}
                                    placeholder="Nom de la routine"
                                />
                                {errors.name && <p className="text-sm text-red-600 mt-1">{errors.name}</p>}
                            </div>

                            <div>
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    placeholder="Décrivez la routine..."
                                    rows={4}
                                />
                                {errors.description && <p className="text-sm text-red-600 mt-1">{errors.description}</p>}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="frequency">Fréquence *</Label>
                                    <Select
                                        value={data.frequency}
                                        onValueChange={v => setData('frequency', v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Choisir une fréquence" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {frequencies.map(f => (
                                                <SelectItem key={f.value} value={f.value}>{f.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.frequency && <p className="text-sm text-red-600 mt-1">{errors.frequency}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="estimated_duration_minutes">Durée estimée (minutes)</Label>
                                    <Input
                                        id="estimated_duration_minutes"
                                        type="number"
                                        min={1}
                                        value={data.estimated_duration_minutes}
                                        onChange={e => setData('estimated_duration_minutes', e.target.value ? parseInt(e.target.value) : '')}
                                        placeholder="Ex: 60"
                                    />
                                    {errors.estimated_duration_minutes && <p className="text-sm text-red-600 mt-1">{errors.estimated_duration_minutes}</p>}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="responsible_id">Responsable</Label>
                                <Select
                                    value={data.responsible_id ? String(data.responsible_id) : ''}
                                    onValueChange={v => setData('responsible_id', v ? parseInt(v) : '')}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choisir un responsable" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {departmentUsers.map(u => (
                                            <SelectItem key={u.id} value={String(u.id)}>
                                                {u.first_name} {u.last_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.responsible_id && <p className="text-sm text-red-600 mt-1">{errors.responsible_id}</p>}
                            </div>

                            <div className="flex justify-end gap-3">
                                <Link href={`/departments/${department.uuid}/routines`}>
                                    <Button type="button" variant="outline">Annuler</Button>
                                </Link>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Création...' : 'Créer la routine'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
