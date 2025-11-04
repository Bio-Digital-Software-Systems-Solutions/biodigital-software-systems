import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Separator } from '@/Components/ui/separator';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    ArrowLeft,
    Edit,
    Trash2,
    Calendar,
    Clock,
    MapPin,
    Users,
    User,
    CheckCircle,
    XCircle,
    AlertCircle,
    MoreHorizontal,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { toast } from 'sonner';
import { format, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';

import type { AppointmentShowProps, AppointmentStatus, ParticipantStatus } from '@/Types/appointment';

export default function AppointmentShow() {
    const { appointment, canModify, canCancel } = usePage<AppointmentShowProps>().props;
    const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);

    const handleDelete = () => {
        router.delete(route('appointments.destroy', appointment.uuid), {
            onSuccess: () => {
                toast.success('Rendez-vous supprimé avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la suppression');
            }
        });
    };

    const handleStatusAction = (action: 'confirm' | 'cancel') => {
        const routeName = action === 'confirm' ? 'appointments.confirm' : 'appointments.cancel';
        const message = action === 'confirm' ? 'Rendez-vous confirmé' : 'Rendez-vous annulé';

        router.patch(route(routeName, appointment.uuid), {}, {
            onSuccess: () => {
                toast.success(message);
            },
            onError: () => {
                toast.error('Erreur lors de la mise à jour');
            }
        });
    };

    const handleInvitationResponse = (action: 'accept' | 'decline') => {
        const routeName = action === 'accept' ? 'appointments.accept-invitation' : 'appointments.decline-invitation';
        const message = action === 'accept' ? 'Invitation acceptée' : 'Invitation déclinée';

        router.patch(route(routeName, appointment.uuid), {}, {
            onSuccess: () => {
                toast.success(message);
            },
            onError: () => {
                toast.error('Erreur lors de la réponse');
            }
        });
    };

    const getStatusIcon = (status: AppointmentStatus) => {
        switch (status) {
            case 'confirmed':
                return <CheckCircle className="h-5 w-5 text-green-600" />;
            case 'cancelled':
                return <XCircle className="h-5 w-5 text-red-600" />;
            case 'completed':
                return <CheckCircle className="h-5 w-5 text-blue-600" />;
            default:
                return <AlertCircle className="h-5 w-5 text-yellow-600" />;
        }
    };

    const getStatusVariant = (status: AppointmentStatus): "default" | "secondary" | "destructive" | "outline" => {
        switch (status) {
            case 'confirmed':
                return 'default';
            case 'cancelled':
                return 'destructive';
            case 'completed':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getStatusLabel = (status: AppointmentStatus) => {
        switch (status) {
            case 'pending': return 'En attente';
            case 'confirmed': return 'Confirmé';
            case 'cancelled': return 'Annulé';
            case 'completed': return 'Terminé';
            default: return status;
        }
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'individual': return 'Individuel';
            case 'group': return 'Groupe';
            case 'consultation': return 'Consultation';
            case 'meeting': return 'Réunion';
            default: return type;
        }
    };

    const getParticipantStatusVariant = (status: ParticipantStatus): "default" | "secondary" | "destructive" | "outline" => {
        switch (status) {
            case 'accepted':
                return 'default';
            case 'declined':
                return 'destructive';
            case 'cancelled':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getParticipantStatusLabel = (status: ParticipantStatus) => {
        switch (status) {
            case 'pending': return 'En attente';
            case 'accepted': return 'Accepté';
            case 'declined': return 'Décliné';
            case 'cancelled': return 'Annulé';
            default: return status;
        }
    };

    const getUserInitials = (name: string) => {
        return name
            .split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return (
        <DashboardLayout>
            <Head title={`Rendez-vous - ${appointment.title}`} />

            <div className="mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="mb-8">
                    <Link
                        href={route('appointments.index')}
                        className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white mb-4"
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Retour aux rendez-vous
                    </Link>

                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                {appointment.title}
                            </h1>
                            <div className="flex items-center space-x-4 mt-2">
                                <Badge variant={getStatusVariant(appointment.status)} className="flex items-center space-x-1">
                                    {getStatusIcon(appointment.status)}
                                    <span>{getStatusLabel(appointment.status)}</span>
                                </Badge>
                                <Badge variant="outline">
                                    {getTypeLabel(appointment.type)}
                                </Badge>
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            {appointment.status === 'pending' && canModify && (
                                <Button
                                    variant="outline"
                                    onClick={() => handleStatusAction('confirm')}
                                >
                                    <CheckCircle className="h-4 w-4 mr-2" />
                                    Confirmer
                                </Button>
                            )}

                            {canCancel && (
                                <Button
                                    variant="outline"
                                    onClick={() => handleStatusAction('cancel')}
                                >
                                    <XCircle className="h-4 w-4 mr-2" />
                                    Annuler
                                </Button>
                            )}

                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="icon">
                                        <MoreHorizontal className="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    {canModify && (
                                        <DropdownMenuItem asChild>
                                            <Link href={route('appointments.edit', appointment.uuid)}>
                                                <Edit className="h-4 w-4 mr-2" />
                                                Modifier
                                            </Link>
                                        </DropdownMenuItem>
                                    )}
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        onClick={() => setDeleteConfirmOpen(true)}
                                        className="text-red-600"
                                    >
                                        <Trash2 className="h-4 w-4 mr-2" />
                                        Supprimer
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Détails du rendez-vous</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {appointment.description && (
                                    <div>
                                        <h4 className="font-medium text-gray-900 dark:text-white mb-2">Description</h4>
                                        <p className="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                            {appointment.description}
                                        </p>
                                    </div>
                                )}

                                <Separator />

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="flex items-center space-x-3">
                                        <Calendar className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-500">Date</p>
                                            <p className="font-medium">
                                                {format(parseISO(appointment.start_datetime), 'EEEE d MMMM yyyy', { locale: fr })}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center space-x-3">
                                        <Clock className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-500">Heure</p>
                                            <p className="font-medium">{appointment.formatted_time_range}</p>
                                            <p className="text-sm text-gray-500">
                                                Durée : {appointment.duration_minutes} minutes
                                            </p>
                                        </div>
                                    </div>

                                    {appointment.location && (
                                        <div className="flex items-center space-x-3">
                                            <MapPin className="h-5 w-5 text-gray-400" />
                                            <div>
                                                <p className="text-sm text-gray-500">Lieu</p>
                                                <p className="font-medium">{appointment.location}</p>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex items-center space-x-3">
                                        <User className="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-500">Organisateur</p>
                                            <p className="font-medium">{appointment.organizer?.name}</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Participants */}
                        {appointment.participants && appointment.participants.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Users className="h-5 w-5" />
                                        <span>Participants ({appointment.participants.length})</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {appointment.participants.map((participant) => (
                                            <div key={participant.id} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div className="flex items-center space-x-3">
                                                    <Avatar>
                                                        <AvatarImage src={participant.avatar} alt={participant.name} />
                                                        <AvatarFallback>
                                                            {getUserInitials(participant.name)}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <p className="font-medium">{participant.name}</p>
                                                        <p className="text-sm text-gray-500">{participant.email}</p>
                                                        {participant.pivot.responded_at && (
                                                            <p className="text-xs text-gray-400">
                                                                Répondu le {format(parseISO(participant.pivot.responded_at), 'dd/MM/yyyy à HH:mm')}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Badge variant={getParticipantStatusVariant(participant.pivot.status)}>
                                                        {getParticipantStatusLabel(participant.pivot.status)}
                                                    </Badge>
                                                    {participant.pivot.attended && (
                                                        <Badge variant="secondary">Présent</Badge>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Quick Actions */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Actions rapides</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {canModify && (
                                    <Button asChild className="w-full">
                                        <Link href={route('appointments.edit', appointment.uuid)}>
                                            <Edit className="h-4 w-4 mr-2" />
                                            Modifier
                                        </Link>
                                    </Button>
                                )}

                                {appointment.status === 'pending' && canModify && (
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        onClick={() => handleStatusAction('confirm')}
                                    >
                                        <CheckCircle className="h-4 w-4 mr-2" />
                                        Confirmer
                                    </Button>
                                )}

                                {canCancel && (
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        onClick={() => handleStatusAction('cancel')}
                                    >
                                        <XCircle className="h-4 w-4 mr-2" />
                                        Annuler
                                    </Button>
                                )}

                                {/* Invitation response buttons for participants */}
                                {appointment.participants?.some(p => p.id === usePage().props.auth?.user?.id && p.pivot.status === 'pending') && (
                                    <>
                                        <Separator />
                                        <p className="text-sm text-gray-600 dark:text-gray-400">Répondre à l'invitation :</p>
                                        <div className="grid grid-cols-2 gap-2">
                                            <Button
                                                size="sm"
                                                onClick={() => handleInvitationResponse('accept')}
                                            >
                                                Accepter
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => handleInvitationResponse('decline')}
                                            >
                                                Décliner
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Status Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Informations</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Statut :</span>
                                    <Badge variant={getStatusVariant(appointment.status)}>
                                        {getStatusLabel(appointment.status)}
                                    </Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Type :</span>
                                    <span>{getTypeLabel(appointment.type)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Créé le :</span>
                                    <span>{format(parseISO(appointment.created_at), 'dd/MM/yyyy')}</span>
                                </div>
                                {appointment.is_past && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">État :</span>
                                        <Badge variant="secondary">Passé</Badge>
                                    </div>
                                )}
                                {appointment.is_today && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">État :</span>
                                        <Badge variant="default">Aujourd'hui</Badge>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <DeleteConfirmationDialog
                open={deleteConfirmOpen}
                onOpenChange={setDeleteConfirmOpen}
                onConfirm={handleDelete}
                title="Supprimer le rendez-vous"
                description={`Êtes-vous sûr de vouloir supprimer le rendez-vous "${appointment.title}" ? Cette action est irréversible.`}
            />
        </DashboardLayout>
    );
}