import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { ConsultationModeDisplay } from '@/Components/ui/consultation-mode-display';
import {
    CalendarIcon,
    ClockIcon,
    ArrowLeftIcon,
    PencilIcon,
    PowerIcon
} from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';

interface PastorAvailability {
    id: number;
    pastor_id: number;
    type: 'weekly' | 'specific_date';
    day_of_week?: number;
    specific_date?: string;
    start_time: string;
    end_time: string;
    slot_duration: number;
    is_active: boolean;
    consultation_mode: 'in_person' | 'online' | 'hybrid';
    meeting_link?: string;
    location?: string;
    room?: string;
    notes?: string;
    selected_slots?: string[];
    day_name?: string;
    time_range: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    availability: PastorAvailability;
    sampleSlots: string[];
}

const dayNames: { [key: number]: string } = {
    1: 'Lundi',
    2: 'Mardi',
    3: 'Mercredi',
    4: 'Jeudi',
    5: 'Vendredi',
    6: 'Samedi',
    7: 'Dimanche',
};

export default function Show({ availability, sampleSlots }: Props) {
    // Calculate actual time range based on selected slots
    const getActualTimeRange = () => {
        if (!availability.selected_slots || availability.selected_slots.length === 0) {
            return availability.time_range; // Fallback to configured range
        }

        const slots = availability.selected_slots.sort();
        const earliestSlot = slots[0];
        const latestSlot = slots[slots.length - 1];

        // Calculate end time by adding slot duration to the latest slot
        const [hours, minutes] = latestSlot.split(':').map(Number);
        const endTime = new Date();
        endTime.setHours(hours, minutes + availability.slot_duration, 0, 0);
        const endTimeString = endTime.toTimeString().slice(0, 5);

        return `${earliestSlot} - ${endTimeString}`;
    };

    const actualTimeRange = getActualTimeRange();

    const handleToggleStatus = () => {
        router.post(
            route('pastoral-availability.toggle-status', availability.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(
                        availability.is_active
                            ? 'Créneaux désactivés avec succès'
                            : 'Créneaux activés avec succès'
                    );
                },
                onError: () => {
                    toast.error('Erreur lors de la modification du statut');
                }
            }
        );
    };

    const getStatusBadge = () => {
        if (availability.is_active) {
            return <Badge className="bg-green-100 text-green-800">Actif</Badge>;
        }
        return <Badge variant="secondary">Inactif</Badge>;
    };

    return (
        <DashboardLayout
            title="Détails des créneaux de disponibilité"
            description="Consultez les détails de vos créneaux de disponibilité pour les consultations pastorales"
            actions={
                <div className="flex items-center space-x-3">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('pastoral-availability.index')}>
                            <ArrowLeftIcon className="h-4 w-4 mr-1" />
                            Retour
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        onClick={handleToggleStatus}
                        className={availability.is_active ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700'}
                    >
                        <PowerIcon className="h-4 w-4 mr-1" />
                        {availability.is_active ? 'Désactiver' : 'Activer'}
                    </Button>
                    <Button asChild>
                        <Link href={route('pastoral-availability.edit', availability.id)}>
                            <PencilIcon className="h-4 w-4 mr-1" />
                            Modifier
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Détails des créneaux de disponibilité" />

            <div className="p-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Left Column - Details */}
                        <div className="space-y-6">
                            {/* Details Card */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center space-x-2">
                                        <CalendarIcon className="h-5 w-5 text-gray-500" />
                                        <CardTitle>
                                            {availability.type === 'weekly'
                                                ? dayNames[availability.day_of_week!] || `Jour ${availability.day_of_week}`
                                                : format(new Date(availability.specific_date!), 'PPP', { locale: fr })
                                            }
                                        </CardTitle>
                                    </div>
                                    {getStatusBadge()}
                                </div>
                                <CardDescription>
                                    {availability.type === 'weekly' ? 'Récurrent chaque semaine' : 'Date spécifique'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Time Information */}
                                <div className="space-y-4">
                                    <div className="flex items-center space-x-2">
                                        <ClockIcon className="h-5 w-5 text-gray-500" />
                                        <span className="font-medium">Horaires</span>
                                    </div>

                                    {availability.selected_slots && availability.selected_slots.length > 0 ? (
                                        // Show actual range based on selected slots
                                        <div className="ml-7 space-y-3">
                                            <div>
                                                <p className="text-sm text-gray-500">Plage des créneaux sélectionnés</p>
                                                <p className="font-medium text-blue-600">{actualTimeRange}</p>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p className="text-sm text-gray-500">Plage configurée</p>
                                                    <p className="text-sm text-gray-400">{availability.time_range}</p>
                                                </div>
                                                <div>
                                                    <p className="text-sm text-gray-500">Durée de chaque créneau</p>
                                                    <p className="text-sm">{availability.slot_duration} minutes</p>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        // Fallback to configured range when no slots selected
                                        <div className="grid grid-cols-2 gap-4 ml-7">
                                            <div>
                                                <p className="text-sm text-gray-500">Début</p>
                                                <p className="font-medium">{availability.start_time}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-gray-500">Fin</p>
                                                <p className="font-medium">{availability.end_time}</p>
                                            </div>
                                            <div className="col-span-2">
                                                <p className="text-sm text-gray-500">Durée de chaque créneau</p>
                                                <p className="font-medium">{availability.slot_duration} minutes</p>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Notes */}
                                {availability.notes && (
                                    <div className="space-y-2">
                                        <h4 className="font-medium">Notes</h4>
                                        <p className="text-gray-600 bg-gray-50 p-3 rounded">
                                            {availability.notes}
                                        </p>
                                    </div>
                                )}

                                {/* Metadata */}
                                <div className="border-t pt-4 space-y-2">
                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p className="text-gray-500">Créé le</p>
                                            <p>{format(new Date(availability.created_at), 'PPPp', { locale: fr })}</p>
                                        </div>
                                        <div>
                                            <p className="text-gray-500">Modifié le</p>
                                            <p>{format(new Date(availability.updated_at), 'PPPp', { locale: fr })}</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        </div>

                        {/* Right Column - Mode & Slots */}
                        <div className="space-y-6">
                            {/* Consultation Mode */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <span>Mode de consultation</span>
                                    </CardTitle>
                                    <CardDescription>
                                        Type de consultation pour ces créneaux
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ConsultationModeDisplay
                                        mode={availability.consultation_mode}
                                        meetingLink={availability.meeting_link}
                                        location={availability.location}
                                        room={availability.room}
                                        size="lg"
                                        showLink
                                        showLocation
                                    />
                                    {availability.meeting_link && (
                                        <div className="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800">
                                            <p className="text-sm text-blue-700 dark:text-blue-300">
                                                <strong>Lien de réunion :</strong> Ce lien sera fourni aux consultants lors de la réservation.
                                            </p>
                                        </div>
                                    )}

                                    {(availability.location || availability.room) && availability.consultation_mode !== 'online' && (
                                        <div className="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                                            <p className="text-sm text-green-700 dark:text-green-300">
                                                <strong>Lieu de consultation :</strong> {[availability.location, availability.room].filter(Boolean).join(' - ')}
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Selected Slots */}
                            <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <ClockIcon className="h-5 w-5" />
                                    <span>Créneaux sélectionnés</span>
                                </CardTitle>
                                <CardDescription>
                                    Créneaux de consultation que vous avez sélectionnés
                                    {availability.type === 'weekly' && ' (chaque semaine)'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {availability.selected_slots && availability.selected_slots.length > 0 ? (
                                    <div>
                                        <p className="text-sm text-gray-600 mb-3">
                                            {availability.selected_slots.length} créneaux de {availability.slot_duration} minutes sélectionnés :
                                        </p>
                                        <div className="grid grid-cols-3 gap-2 max-h-60 overflow-y-auto">
                                            {availability.selected_slots
                                                .slice()
                                                .sort((a, b) => {
                                                    // Convert HH:MM to minutes for comparison
                                                    const timeA = a.split(':').reduce((acc, time, index) => acc + (index === 0 ? parseInt(time) * 60 : parseInt(time)), 0);
                                                    const timeB = b.split(':').reduce((acc, time, index) => acc + (index === 0 ? parseInt(time) * 60 : parseInt(time)), 0);
                                                    return timeA - timeB;
                                                })
                                                .map((slot, index) => (
                                                    <Badge key={index} variant="default" className="justify-center bg-blue-600 text-white">
                                                        {slot}
                                                    </Badge>
                                                ))
                                            }
                                        </div>
                                        <div className="mt-4 p-3 bg-green-50 rounded border border-green-200">
                                            <p className="text-sm text-green-700">
                                                <strong>Info :</strong> Ces créneaux sont activement disponibles pour les consultations.
                                                Les clients peuvent réserver ces créneaux horaires spécifiques.
                                            </p>
                                        </div>
                                    </div>
                                ) : sampleSlots.length > 0 ? (
                                    <div>
                                        <p className="text-sm text-gray-600 mb-3">
                                            {sampleSlots.length} créneaux de {availability.slot_duration} minutes générés automatiquement :
                                        </p>
                                        <div className="grid grid-cols-3 gap-2 max-h-60 overflow-y-auto">
                                            {sampleSlots.map((slot, index) => (
                                                <Badge key={index} variant="secondary" className="justify-center">
                                                    {slot}
                                                </Badge>
                                            ))}
                                        </div>
                                        <div className="mt-4 p-3 bg-yellow-50 rounded border border-yellow-200">
                                            <p className="text-sm text-yellow-700">
                                                <strong>Note :</strong> Aucun créneau spécifique sélectionné. Tous les créneaux dans cette plage horaire sont disponibles.
                                                Les créneaux réellement disponibles dépendent des rendez-vous déjà pris et de l'heure actuelle.
                                            </p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-8 text-gray-500">
                                        <ClockIcon className="h-8 w-8 mx-auto mb-2" />
                                        <p>Aucun créneau disponible</p>
                                        <p className="text-sm">
                                            Vérifiez les horaires de début et de fin
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                        </div>
                    </div>
            </div>
        </DashboardLayout>
    );
}