import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowLeftIcon, EnvelopeIcon, UserIcon } from '@heroicons/react/24/outline';

interface AssignableUser {
    id: number;
    name: string;
}

interface Contact {
    id: number;
    uuid: string;
    name: string;
    email: string;
    phone: string | null;
    subject: string;
    message: string;
    status: 'new' | 'in_progress' | 'resolved' | 'closed';
    read_at: string | null;
    created_at: string;
    assigned_to: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    contact: Contact;
    users: AssignableUser[];
}

const statusOptions = [
    { value: 'new', label: 'Nouveau' },
    { value: 'in_progress', label: 'En cours' },
    { value: 'resolved', label: 'Résolu' },
    { value: 'closed', label: 'Fermé' },
];

const statusConfig = {
    new: { label: 'Nouveau', color: 'bg-primary' },
    in_progress: { label: 'En cours', color: 'bg-yellow-500' },
    resolved: { label: 'Résolu', color: 'bg-green-500' },
    closed: { label: 'Fermé', color: 'bg-gray-500' },
};

const UNASSIGNED = 'unassigned';

export default function Edit({ contact, users }: Props) {
    const { data, setData, put, processing, errors, transform } = useForm<{
        status: string;
        assigned_to: string;
    }>({
        status: contact.status,
        assigned_to: contact.assigned_to ? String(contact.assigned_to.id) : UNASSIGNED,
    });

    transform((formData) => ({
        status: formData.status,
        assigned_to: formData.assigned_to === UNASSIGNED ? null : Number(formData.assigned_to),
    }));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('contacts.update', contact.uuid));
    };

    return (
        <DashboardLayout>
            <Head title={`Modifier - ${contact.subject}`} />

            <div className="p-4">
                <div className="space-y-6 max-w-2xl">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('contacts.show', contact.uuid)}>
                                <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                Retour
                            </Link>
                        </Button>
                        <div className="flex-1">
                            <h1 className="text-3xl font-bold">Modifier le message</h1>
                        </div>
                        <Badge className={statusConfig[contact.status].color}>
                            {statusConfig[contact.status].label}
                        </Badge>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>{contact.subject}</CardTitle>
                            <CardDescription className="flex flex-wrap gap-4">
                                <span className="flex items-center gap-1">
                                    <UserIcon className="h-4 w-4" />
                                    {contact.name}
                                </span>
                                <span className="flex items-center gap-1">
                                    <EnvelopeIcon className="h-4 w-4" />
                                    {contact.email}
                                </span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="status">Statut</Label>
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) => setData('status', value)}
                                    >
                                        <SelectTrigger id="status">
                                            <SelectValue placeholder="Sélectionner un statut" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statusOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.status && (
                                        <p className="text-sm text-red-600">{errors.status}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="assigned_to">Assigné à</Label>
                                    <Select
                                        value={data.assigned_to}
                                        onValueChange={(value) => setData('assigned_to', value)}
                                    >
                                        <SelectTrigger id="assigned_to">
                                            <SelectValue placeholder="Sélectionner un membre" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={UNASSIGNED}>Non assigné</SelectItem>
                                            {users.map((user) => (
                                                <SelectItem key={user.id} value={String(user.id)}>
                                                    {user.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.assigned_to && (
                                        <p className="text-sm text-red-600">{errors.assigned_to}</p>
                                    )}
                                </div>

                                <div className="flex items-center justify-end gap-3">
                                    <Button variant="outline" type="button" asChild>
                                        <Link href={route('contacts.show', contact.uuid)}>Annuler</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Enregistrement...' : 'Enregistrer'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}
