import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { TimeSlotsMini } from '@/Components/ui/time-slots-mini';
import { ConsultationModeDisplay } from '@/Components/ui/consultation-mode-display';
import {
    CalendarIcon,
    ClockIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    PowerIcon,
    InformationCircleIcon,
    Squares2X2Icon,
    ListBulletIcon,
    TableCellsIcon
} from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

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
    availabilities: PastorAvailability[];
    pastor: User;
}

const dayNames: { [key: number]: string } = {
    0: 'Dimanche',
    1: 'Lundi',
    2: 'Mardi',
    3: 'Mercredi',
    4: 'Jeudi',
    5: 'Vendredi',
    6: 'Samedi',
};

type ViewType = 'cards' | 'list' | 'table';

export default function Index({ availabilities, pastor }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<PastorAvailability | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [currentView, setCurrentView] = useState<ViewType>('cards');

    const weeklyAvailabilities = availabilities.filter(a => a.type === 'weekly');
    const specificDateAvailabilities = availabilities.filter(a => a.type === 'specific_date');

    const handleToggleStatus = (availability: PastorAvailability) => {
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

    const handleDelete = async () => {
        if (!deleteTarget) return;

        setIsDeleting(true);
        router.delete(
            route('pastoral-availability.destroy', deleteTarget.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Créneaux de disponibilité supprimés avec succès');
                    setDeleteTarget(null);
                },
                onError: () => {
                    toast.error('Erreur lors de la suppression');
                },
                onFinish: () => {
                    setIsDeleting(false);
                }
            }
        );
    };

    const getStatusBadge = (availability: PastorAvailability) => {
        if (availability.is_active) {
            return <Badge className="bg-green-100 text-green-800">Actif</Badge>;
        }
        return <Badge variant="secondary">Inactif</Badge>;
    };

    // List View Component
    const renderAvailabilityList = (availability: PastorAvailability) => (
        <div key={availability.id} className="border rounded-lg p-4 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow">
            {/* Mobile Layout */}
            <div className="block sm:hidden">
                <div className="space-y-3">
                    {/* Header */}
                    <div className="flex items-start justify-between">
                        <div className="flex items-center space-x-2">
                            <CalendarIcon className="h-5 w-5 text-gray-500 flex-shrink-0" />
                            <div>
                                <div className="font-medium text-base">
                                    {availability.type === 'weekly'
                                        ? dayNames[availability.day_of_week!] || `Jour ${availability.day_of_week}`
                                        : format(new Date(availability.specific_date!), 'dd/MM/yyyy', { locale: fr })
                                    }
                                </div>
                                <div className="text-xs text-gray-500">
                                    {availability.type === 'weekly' ? 'Récurrent' : 'Date spécifique'}
                                </div>
                            </div>
                        </div>
                        {getStatusBadge(availability)}
                    </div>

                    {/* Time Info */}
                    <div className="space-y-2">
                        <div className="flex items-center space-x-2 text-sm text-gray-600">
                            <ClockIcon className="h-4 w-4 flex-shrink-0" />
                            <span>{availability.time_range}</span>
                            <span className="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                {availability.slot_duration} min
                            </span>
                        </div>

                        {/* Selected Slots Mini Component */}
                        {availability.selected_slots && availability.selected_slots.length > 0 && (
                            <TimeSlotsMini
                                selectedSlots={availability.selected_slots}
                                slotDuration={availability.slot_duration}
                                timeRange={availability.time_range}
                                maxDisplay={3}
                                size="xs"
                            />
                        )}

                        {/* Consultation Mode */}
                        <ConsultationModeDisplay
                            mode={availability.consultation_mode}
                            meetingLink={availability.meeting_link}
                            location={availability.location}
                            room={availability.room}
                            size="sm"
                            showLocation
                        />
                    </div>

                    {/* Notes */}
                    {availability.notes && (
                        <div className="text-sm text-gray-600">
                            <span className="font-medium">Notes: </span>
                            <span className="break-words">{availability.notes}</span>
                        </div>
                    )}

                    {/* Actions */}
                    <div className="flex flex-wrap gap-2 pt-2 border-t">
                        <Button variant="outline" size="sm" asChild className="flex-1">
                            <Link href={route('pastoral-availability.show', availability.id)}>
                                <EyeIcon className="h-4 w-4 mr-1" />
                                Voir
                            </Link>
                        </Button>

                        <Button variant="outline" size="sm" asChild className="flex-1">
                            <Link href={route('pastoral-availability.edit', availability.id)}>
                                <PencilIcon className="h-4 w-4 mr-1" />
                                Modifier
                            </Link>
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleToggleStatus(availability)}
                            className={`flex-1 ${availability.is_active ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700'}`}
                        >
                            <PowerIcon className="h-4 w-4 mr-1" />
                            {availability.is_active ? 'Désactiver' : 'Activer'}
                        </Button>

                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => setDeleteTarget(availability)}
                            className="flex-1"
                        >
                            <TrashIcon className="h-4 w-4 mr-1" />
                            Supprimer
                        </Button>
                    </div>
                </div>
            </div>

            {/* Desktop Layout */}
            <div className="hidden sm:block">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <div className="flex items-center space-x-2">
                            <CalendarIcon className="h-5 w-5 text-gray-500" />
                            <div>
                                <div className="font-medium text-lg">
                                    {availability.type === 'weekly'
                                        ? dayNames[availability.day_of_week!] || `Jour ${availability.day_of_week}`
                                        : format(new Date(availability.specific_date!), 'PPP', { locale: fr })
                                    }
                                </div>
                                <div className="text-sm text-gray-500">
                                    {availability.type === 'weekly' ? 'Récurrent chaque semaine' : 'Date spécifique'}
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col space-y-1">
                            <div className="flex items-center space-x-2 text-sm text-gray-600">
                                <ClockIcon className="h-4 w-4" />
                                <span>{availability.time_range}</span>
                                <span className="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                    {availability.slot_duration} min
                                </span>
                            </div>

                            {/* Selected Slots Mini Component */}
                            {availability.selected_slots && availability.selected_slots.length > 0 && (
                                <TimeSlotsMini
                                    selectedSlots={availability.selected_slots}
                                    slotDuration={availability.slot_duration}
                                    timeRange={availability.time_range}
                                    maxDisplay={4}
                                    size="xs"
                                />
                            )}
                        </div>

                        {availability.notes && (
                            <div className="text-sm text-gray-600 max-w-md truncate">
                                <span className="font-medium">Notes: </span>
                                {availability.notes}
                            </div>
                        )}

                        <ConsultationModeDisplay
                            mode={availability.consultation_mode}
                            meetingLink={availability.meeting_link}
                            location={availability.location}
                            room={availability.room}
                            size="sm"
                            showLocation
                        />

                        {getStatusBadge(availability)}
                    </div>

                    <div className="flex items-center space-x-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('pastoral-availability.show', availability.id)}>
                                <EyeIcon className="h-4 w-4 mr-1" />
                                <span className="hidden lg:inline">Voir</span>
                            </Link>
                        </Button>

                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('pastoral-availability.edit', availability.id)}>
                                <PencilIcon className="h-4 w-4 mr-1" />
                                <span className="hidden lg:inline">Modifier</span>
                            </Link>
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleToggleStatus(availability)}
                            className={availability.is_active ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700'}
                        >
                            <PowerIcon className="h-4 w-4 mr-1" />
                            <span className="hidden lg:inline">
                                {availability.is_active ? 'Désactiver' : 'Activer'}
                            </span>
                        </Button>

                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => setDeleteTarget(availability)}
                        >
                            <TrashIcon className="h-4 w-4 mr-1" />
                            <span className="hidden lg:inline">Supprimer</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );

    // Table View Component
    const renderAvailabilityTable = () => (
        <div className="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Jour/Date
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Type
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Horaires
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Durée
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Statut
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Mode
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Notes
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        {availabilities.length > 0 ? (
                            availabilities.map((availability) => (
                                <tr key={availability.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="flex items-center">
                                            <CalendarIcon className="h-5 w-5 text-gray-500 mr-2" />
                                            <div>
                                                <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {availability.type === 'weekly'
                                                        ? dayNames[availability.day_of_week!] || `Jour ${availability.day_of_week}`
                                                        : format(new Date(availability.specific_date!), 'dd/MM/yyyy', { locale: fr })
                                                    }
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {availability.type === 'weekly' ? 'Hebdomadaire' : 'Date spécifique'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="flex items-center">
                                            <ClockIcon className="h-4 w-4 text-gray-500 mr-1" />
                                            <span className="text-sm text-gray-900 dark:text-gray-100">
                                                {availability.time_range}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="space-y-1">
                                            <div className="text-sm text-gray-500 dark:text-gray-400">
                                                {availability.slot_duration} min
                                            </div>
                                            {availability.selected_slots && availability.selected_slots.length > 0 && (
                                                <TimeSlotsMini
                                                    selectedSlots={availability.selected_slots}
                                                    slotDuration={availability.slot_duration}
                                                    timeRange={availability.time_range}
                                                    maxDisplay={2}
                                                    size="xs"
                                                />
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {getStatusBadge(availability)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <ConsultationModeDisplay
                                            mode={availability.consultation_mode}
                                            meetingLink={availability.meeting_link}
                                            location={availability.location}
                                            room={availability.room}
                                            size="sm"
                                            showLink
                                            showLocation
                                        />
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                        {availability.notes || '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div className="flex items-center justify-end space-x-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('pastoral-availability.show', availability.id)}>
                                                    <EyeIcon className="h-4 w-4" />
                                                </Link>
                                            </Button>

                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('pastoral-availability.edit', availability.id)}>
                                                    <PencilIcon className="h-4 w-4" />
                                                </Link>
                                            </Button>

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleToggleStatus(availability)}
                                                className={availability.is_active ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700'}
                                            >
                                                <PowerIcon className="h-4 w-4" />
                                            </Button>

                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => setDeleteTarget(availability)}
                                            >
                                                <TrashIcon className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={8} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div className="flex flex-col items-center">
                                        <CalendarIcon className="h-8 w-8 mb-2 text-gray-400" />
                                        <p>Aucun créneau de disponibilité trouvé</p>
                                    </div>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );

    const renderAvailabilityCard = (availability: PastorAvailability) => (
        <Card key={availability.id} className="h-full">
            <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                        <CalendarIcon className="h-5 w-5 text-gray-500" />
                        <CardTitle className="text-lg">
                            {availability.type === 'weekly'
                                ? dayNames[availability.day_of_week!] || `Jour ${availability.day_of_week}`
                                : format(new Date(availability.specific_date!), 'PPP', { locale: fr })
                            }
                        </CardTitle>
                    </div>
                    {getStatusBadge(availability)}
                </div>
                <CardDescription>
                    {availability.type === 'weekly' ? 'Récurrent chaque semaine' : 'Date spécifique'}
                </CardDescription>
            </CardHeader>

            <CardContent>
                <div className="space-y-3">
                    <div className="flex items-center space-x-2 text-sm text-gray-600">
                        <ClockIcon className="h-4 w-4" />
                        <span>{availability.time_range}</span>
                        <span className="text-xs bg-gray-100 px-2 py-1 rounded">
                            {availability.slot_duration} min
                        </span>
                    </div>

                    {/* Selected Slots Mini Component */}
                    {availability.selected_slots && availability.selected_slots.length > 0 && (
                        <TimeSlotsMini
                            selectedSlots={availability.selected_slots}
                            slotDuration={availability.slot_duration}
                            timeRange={availability.time_range}
                            maxDisplay={5}
                            size="sm"
                        />
                    )}

                    {/* Consultation Mode */}
                    <ConsultationModeDisplay
                        mode={availability.consultation_mode}
                        meetingLink={availability.meeting_link}
                        location={availability.location}
                        room={availability.room}
                        size="md"
                        showLink
                        showLocation
                    />

                    {availability.notes && (
                        <div className="text-sm text-gray-600">
                            <span className="font-medium">Notes: </span>
                            {availability.notes}
                        </div>
                    )}

                    <div className="pt-3 border-t space-y-3">
                        {/* Primary Actions Row */}
                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                className="flex-1 min-w-0"
                            >
                                <Link href={route('pastoral-availability.show', availability.id)}>
                                    <EyeIcon className="h-4 w-4 mr-1" />
                                    <span className="hidden sm:inline">Voir</span>
                                </Link>
                            </Button>

                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                className="flex-1 min-w-0"
                            >
                                <Link href={route('pastoral-availability.edit', availability.id)}>
                                    <PencilIcon className="h-4 w-4 mr-1" />
                                    <span className="hidden sm:inline">Modifier</span>
                                </Link>
                            </Button>
                        </div>

                        {/* Secondary Actions Row */}
                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleToggleStatus(availability)}
                                className={`flex-1 min-w-0 ${availability.is_active ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700'}`}
                            >
                                <PowerIcon className="h-4 w-4 mr-1" />
                                <span className="hidden sm:inline">
                                    {availability.is_active ? 'Désactiver' : 'Activer'}
                                </span>
                            </Button>

                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={() => setDeleteTarget(availability)}
                                className="flex-1 min-w-0"
                            >
                                <TrashIcon className="h-4 w-4 mr-1" />
                                <span className="hidden sm:inline">Supprimer</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );

    return (
        <DashboardLayout
            title="Mes créneaux de disponibilité"
            description="Gérez vos heures de disponibilité pour les consultations pastorales"
            actions={
                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    {/* View Toggle Buttons */}
                    <div className="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <Button
                            variant={currentView === 'cards' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => setCurrentView('cards')}
                            className="rounded-r-none flex-1 sm:flex-none"
                            title="Vue en cartes"
                        >
                            <Squares2X2Icon className="h-4 w-4" />
                            <span className="ml-1 sm:hidden">Cartes</span>
                        </Button>
                        <Button
                            variant={currentView === 'list' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => setCurrentView('list')}
                            className="rounded-none border-x flex-1 sm:flex-none"
                            title="Vue en liste"
                        >
                            <ListBulletIcon className="h-4 w-4" />
                            <span className="ml-1 sm:hidden">Liste</span>
                        </Button>
                        <Button
                            variant={currentView === 'table' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => setCurrentView('table')}
                            className="rounded-l-none flex-1 sm:flex-none"
                            title="Vue en tableau"
                        >
                            <TableCellsIcon className="h-4 w-4" />
                            <span className="ml-1 sm:hidden">Tableau</span>
                        </Button>
                    </div>

                    {/* Add Button */}
                    <Button asChild className="w-full sm:w-auto">
                        <Link href={route('pastoral-availability.create')}>
                            <PlusIcon className="h-4 w-4 mr-2" />
                            <span className="hidden sm:inline">Ajouter des créneaux</span>
                            <span className="sm:hidden">Ajouter</span>
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Mes créneaux de disponibilité" />

            <div className="py-12">
                <div className="mx-auto sm:px-6 lg:px-8">
                    <div className="space-y-6">
                        {availabilities.length === 0 ? (
                            <Alert>
                                <InformationCircleIcon className="h-4 w-4" />
                                <AlertDescription>
                                    Vous n'avez pas encore défini vos créneaux de disponibilité.
                                    Les consultants ne pourront pas réserver de rendez-vous avec vous.
                                    <br />
                                    <Link
                                        href={route('pastoral-availability.create')}
                                        className="font-medium text-blue-600 hover:underline mt-2 inline-block"
                                    >
                                        Créer vos premiers créneaux de disponibilité
                                    </Link>
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <>
                                {/* Render based on current view */}
                                {currentView === 'table' ? (
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                            Tous les créneaux de disponibilité
                                        </h3>
                                        {renderAvailabilityTable()}
                                    </div>
                                ) : currentView === 'list' ? (
                                    <>
                                        {/* Weekly Availabilities - List View */}
                                        {weeklyAvailabilities.length > 0 && (
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                                    Disponibilité hebdomadaire
                                                </h3>
                                                <div className="space-y-4">
                                                    {weeklyAvailabilities.map(renderAvailabilityList)}
                                                </div>
                                            </div>
                                        )}

                                        {/* Specific Date Availabilities - List View */}
                                        {specificDateAvailabilities.length > 0 && (
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                                    Disponibilité pour des dates spécifiques
                                                </h3>
                                                <div className="space-y-4">
                                                    {specificDateAvailabilities.map(renderAvailabilityList)}
                                                </div>
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <>
                                        {/* Weekly Availabilities - Cards View */}
                                        {weeklyAvailabilities.length > 0 && (
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                                    Disponibilité hebdomadaire
                                                </h3>
                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                                    {weeklyAvailabilities.map(renderAvailabilityCard)}
                                                </div>
                                            </div>
                                        )}

                                        {/* Specific Date Availabilities - Cards View */}
                                        {specificDateAvailabilities.length > 0 && (
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                                    Disponibilité pour des dates spécifiques
                                                </h3>
                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                                    {specificDateAvailabilities.map(renderAvailabilityCard)}
                                                </div>
                                            </div>
                                        )}
                                    </>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>

            <DeleteConfirmationDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                onConfirm={handleDelete}
                title="Supprimer les créneaux de disponibilité"
                description={
                    deleteTarget
                        ? `Êtes-vous sûr de vouloir supprimer ces créneaux de disponibilité ${
                              deleteTarget.type === 'weekly'
                                  ? `pour ${dayNames[deleteTarget.day_of_week!]}`
                                  : `du ${format(new Date(deleteTarget.specific_date!), 'PPP', { locale: fr })}`
                          } ? Cette action est irréversible.`
                        : ''
                }
                isDeleting={isDeleting}
            />
        </DashboardLayout>
    );
}