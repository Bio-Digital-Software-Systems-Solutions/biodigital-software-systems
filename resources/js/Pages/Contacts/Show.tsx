import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import {
    EnvelopeIcon,
    PhoneIcon,
    ClockIcon,
    ArrowLeftIcon,
    TrashIcon,
    UserIcon,
} from '@heroicons/react/24/outline';

interface Contact {
    id: number;
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
}

const statusConfig = {
    new: { label: 'Nouveau', color: 'bg-primary' },
    in_progress: { label: 'En cours', color: 'bg-yellow-500' },
    resolved: { label: 'Résolu', color: 'bg-green-500' },
    closed: { label: 'Fermé', color: 'bg-gray-500' },
};

export default function Show({ contact }: Props) {
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
            setDeleting(true);
            router.delete(route('contacts.destroy', contact.id), {
                onFinish: () => setDeleting(false),
            });
        }
    };

    return (
        <DashboardLayout>
            <Head title={`Contact - ${contact.subject}`} />

            <div className="p-4">
                <div className="space-y-6 ">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('contacts.index')}>
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour
                        </Link>
                    </Button>
                    <div className="flex-1">
                        <h1 className="text-3xl font-bold">{contact.subject}</h1>
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        onClick={handleDelete}
                        disabled={deleting}
                    >
                        <TrashIcon className="h-4 w-4 mr-2" />
                        {deleting ? 'Suppression...' : 'Supprimer'}
                    </Button>
                </div>

                <div className="grid gap-6">
                    {/* Contact Info Card */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>Informations de Contact</CardTitle>
                                <div className="flex gap-2">
                                    <Badge className={statusConfig[contact.status].color}>
                                        {statusConfig[contact.status].label}
                                    </Badge>
                                    {!contact.read_at && (
                                        <Badge variant="destructive">Non lu</Badge>
                                    )}
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label className="text-muted-foreground">Nom</Label>
                                    <div className="flex items-center gap-2">
                                        <UserIcon className="h-5 w-5 text-muted-foreground" />
                                        <span className="font-medium">{contact.name}</span>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label className="text-muted-foreground">Email</Label>
                                    <div className="flex items-center gap-2">
                                        <EnvelopeIcon className="h-5 w-5 text-muted-foreground" />
                                        <a
                                            href={`mailto:${contact.email}`}
                                            className="text-icc-blue hover:underline"
                                        >
                                            {contact.email}
                                        </a>
                                    </div>
                                </div>

                                {contact.phone && (
                                    <div className="space-y-2">
                                        <Label className="text-muted-foreground">Téléphone</Label>
                                        <div className="flex items-center gap-2">
                                            <PhoneIcon className="h-5 w-5 text-muted-foreground" />
                                            <a
                                                href={`tel:${contact.phone}`}
                                                className="text-icc-blue hover:underline"
                                            >
                                                {contact.phone}
                                            </a>
                                        </div>
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label className="text-muted-foreground">Date de réception</Label>
                                    <div className="flex items-center gap-2">
                                        <ClockIcon className="h-5 w-5 text-muted-foreground" />
                                        <span>
                                            {new Date(contact.created_at).toLocaleDateString('fr-FR', {
                                                day: 'numeric',
                                                month: 'long',
                                                year: 'numeric',
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })}
                                        </span>
                                    </div>
                                </div>

                                {contact.assigned_to && (
                                    <div className="space-y-2 md:col-span-2">
                                        <Label className="text-muted-foreground">Assigné à</Label>
                                        <div className="flex items-center gap-2">
                                            <UserIcon className="h-5 w-5 text-muted-foreground" />
                                            <span className="font-medium">{contact.assigned_to.name}</span>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Message Card */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Message</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="prose prose-sm dark:prose-invert">
                                <p className="whitespace-pre-wrap">{contact.message}</p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions Card */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Actions</CardTitle>
                            <CardDescription>
                                Modifier le statut ou assigner ce message à un membre de l'équipe
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button variant="outline" asChild>
                                <Link href={route('contacts.edit', contact.id)}>
                                    Modifier le statut
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
