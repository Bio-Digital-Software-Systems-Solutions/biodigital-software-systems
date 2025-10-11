import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    EnvelopeIcon,
    PhoneIcon,
    ClockIcon,
    EyeIcon,
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

interface PaginatedContacts {
    data: Contact[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    contacts: PaginatedContacts;
}

const statusConfig = {
    new: { label: 'Nouveau', color: 'bg-primary' },
    in_progress: { label: 'En cours', color: 'bg-yellow-500' },
    resolved: { label: 'Résolu', color: 'bg-green-500' },
    closed: { label: 'Fermé', color: 'bg-gray-500' },
};

export default function Index({ contacts }: Props) {
    return (
        <DashboardLayout>
            <Head title="Contacts" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Messages de Contact</h1>
                        <p className="text-muted-foreground">
                            Gérez les messages reçus via le formulaire de contact
                        </p>
                    </div>
                </div>

                <div className="grid gap-4">
                    {contacts.data.map((contact) => (
                        <Card
                            key={contact.id}
                            className={`transition-all hover:shadow-lg ${
                                !contact.read_at ? 'border-icc-blue' : ''
                            }`}
                        >
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <div className="space-y-2 flex-1">
                                        <div className="flex items-center gap-2">
                                            <CardTitle className="text-xl">{contact.subject}</CardTitle>
                                            {!contact.read_at && (
                                                <Badge variant="destructive" className="text-xs">
                                                    Non lu
                                                </Badge>
                                            )}
                                            <Badge className={statusConfig[contact.status].color}>
                                                {statusConfig[contact.status].label}
                                            </Badge>
                                        </div>
                                        <CardDescription className="flex flex-col gap-1">
                                            <span className="font-semibold text-foreground">{contact.name}</span>
                                            <div className="flex flex-wrap gap-4 text-sm">
                                                <span className="flex items-center gap-1">
                                                    <EnvelopeIcon className="h-4 w-4" />
                                                    {contact.email}
                                                </span>
                                                {contact.phone && (
                                                    <span className="flex items-center gap-1">
                                                        <PhoneIcon className="h-4 w-4" />
                                                        {contact.phone}
                                                    </span>
                                                )}
                                                <span className="flex items-center gap-1">
                                                    <ClockIcon className="h-4 w-4" />
                                                    {new Date(contact.created_at).toLocaleDateString('fr-FR', {
                                                        day: 'numeric',
                                                        month: 'long',
                                                        year: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    })}
                                                </span>
                                            </div>
                                        </CardDescription>
                                    </div>
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={route('contacts.show', contact.id)}>
                                            <EyeIcon className="h-4 w-4 mr-2" />
                                            Voir
                                        </Link>
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground line-clamp-2">
                                    {contact.message}
                                </p>
                                {contact.assigned_to && (
                                    <p className="text-sm mt-2">
                                        <span className="font-medium">Assigné à:</span> {contact.assigned_to.name}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {contacts.data.length === 0 && (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <EnvelopeIcon className="h-16 w-16 text-muted-foreground mb-4" />
                            <h3 className="text-xl font-semibold mb-2">Aucun message</h3>
                            <p className="text-muted-foreground text-center">
                                Aucun message de contact n'a été reçu pour le moment.
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Pagination */}
                {contacts.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {Array.from({ length: contacts.last_page }, (_, i) => i + 1).map((page) => (
                            <Button
                                key={page}
                                variant={page === contacts.current_page ? 'default' : 'outline'}
                                size="sm"
                                asChild
                            >
                                <Link href={route('contacts.index', { page })}>
                                    {page}
                                </Link>
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
