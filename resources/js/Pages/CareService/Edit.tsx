import { useState, useMemo } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Calendar } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { CalendarIcon, ClockIcon, ArrowLeftIcon, CheckIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface CareServiceAppointment {
    id: number;
    uuid: string;
    user?: User;
    pastor?: User;
    pastor_id: number;
    appointment_date: string;
    appointment_time: string;
    duration_minutes: number;
    status: 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';
    location_type: 'in_person' | 'zoom' | 'hybrid';
    zoom_link?: string;
    client_name: string;
    client_email: string;
    client_phone?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    appointment: CareServiceAppointment;
    pastors?: User[];
    auth: {
        user: User;
    };
}

interface FormData {
    pastor_id: number;
    appointment_date: string;
    appointment_time: string;
    duration_minutes: number;
    location_type: 'in_person' | 'zoom' | 'hybrid';
    zoom_link: string;
    status: 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';
}

export default function Edit({ appointment, pastors, auth }: Props) {
    const [selectedDate, setSelectedDate] = useState<Date | undefined>(
        appointment.appointment_date ? new Date(appointment.appointment_date) : undefined
    );
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, setData, errors, reset } = useForm<FormData>({
        pastor_id: appointment.pastor?.id || appointment.pastor_id || 0,
        appointment_date: appointment.appointment_date ? format(new Date(appointment.appointment_date), 'yyyy-MM-dd') : '',
        appointment_time: appointment.appointment_time ? format(new Date(appointment.appointment_time), 'HH:mm') : '',
        duration_minutes: appointment.duration_minutes,
        location_type: appointment.location_type,
        zoom_link: appointment.zoom_link || '',
        status: appointment.status
    });

    // Find the selected pastor for display
    const selectedPastor = useMemo(() => {
        return pastors?.find(p => p.id === data.pastor_id);
    }, [pastors, data.pastor_id]);

    const timeSlots = [
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
        '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
        '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30'
    ];

    const durationOptions = [
        { value: 30, label: '30 minutes' },
        { value: 45, label: '45 minutes' },
        { value: 60, label: '1 heure' },
        { value: 90, label: '1h30' },
        { value: 120, label: '2 heures' }
    ];

    const statusOptions = [
        { value: 'pending', label: 'En attente' },
        { value: 'confirmed', label: 'Confirmé' },
        { value: 'completed', label: 'Terminé' },
        { value: 'cancelled', label: 'Annulé' },
        { value: 'no_show', label: 'Absent' }
    ];

    const handleDateSelect = (date: Date | undefined) => {
        setSelectedDate(date);
        if (date) {
            setData('appointment_date', format(date, 'yyyy-MM-dd'));
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.appointment_date) {
            toast.error('Veuillez sélectionner une date');
            return;
        }

        if (!data.appointment_time) {
            toast.error('Veuillez sélectionner une heure');
            return;
        }


        setIsSubmitting(true);

        try {
            // Send the time in H:i format as expected by backend validation
            const submitData = {
                ...data,
                appointment_time: data.appointment_time  // Keep as H:i format (e.g., "15:00")
            };

            await router.put(`/care-service/appointments/${appointment.uuid}`, submitData, {
                onSuccess: () => {
                    toast.success('Rendez-vous mis à jour avec succès');
                    router.visit(`/care-service/appointments/${appointment.uuid}`);
                },
                onError: (errors) => {
                    console.error('Erreurs de validation:', errors);
                    if (Object.keys(errors).length > 0) {
                        toast.error('Veuillez corriger les erreurs dans le formulaire');
                    } else {
                        toast.error('Erreur lors de la mise à jour du rendez-vous');
                    }
                }
            });
        } catch (error) {
            toast.error('Erreur lors de la mise à jour du rendez-vous');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleCancel = () => {
        router.visit(`/care-service/appointments/${appointment.uuid}`);
    };

    return (
        <DashboardLayout
            title="Modifier le rendez-vous"
            actions={
                <Button
                    variant="outline"
                    onClick={handleCancel}
                >
                    <ArrowLeftIcon className="h-4 w-4 mr-2" />
                    Retour
                </Button>
            }
        >
            <Head title={`Modifier rendez-vous - ${appointment.client_name}`} />

            <div className="py-6">
                <div className="mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-8">
                        {/* Appointment Status */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Statut du rendez-vous</CardTitle>
                                <CardDescription>
                                    Modifiez le statut actuel de ce rendez-vous
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div>
                                    <Label htmlFor="status">Statut *</Label>
                                    <Select
                                        value={data.status}
                                        onValueChange={(value: any) => setData('status', value)}
                                    >
                                        <SelectTrigger className="mt-2">
                                            <SelectValue />
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
                                        <p className="text-sm text-red-600 mt-1">{errors.status}</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Pastor Assignment */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Assignation du pasteur</CardTitle>
                                <CardDescription>
                                    Modifiez le pasteur responsable de ce rendez-vous
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div>
                                    <Label htmlFor="pastor_id">Pasteur responsable *</Label>
                                    <Select
                                        value={data.pastor_id.toString()}
                                        onValueChange={(value) => setData('pastor_id', parseInt(value))}
                                    >
                                        <SelectTrigger className="mt-2">
                                            <SelectValue>
                                                {selectedPastor ? `${selectedPastor.first_name} ${selectedPastor.last_name}` : 'Sélectionner un pasteur'}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            {pastors?.map((pastor) => (
                                                <SelectItem key={pastor.id} value={pastor.id.toString()}>
                                                    {pastor.first_name} {pastor.last_name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.pastor_id && (
                                        <p className="text-sm text-red-600 mt-1">{errors.pastor_id}</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Date and Time */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Date et heure</CardTitle>
                                <CardDescription>
                                    Modifiez la date, l'heure et la durée du rendez-vous
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    {/* Date Selection */}
                                    <div>
                                        <Label>Date du rendez-vous *</Label>
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    className="w-full justify-start text-left font-normal mt-2"
                                                >
                                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                                    {selectedDate ? format(selectedDate, 'EEEE d MMMM yyyy', { locale: fr }) : 'Sélectionner une date'}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0">
                                                <Calendar
                                                    mode="single"
                                                    selected={selectedDate}
                                                    onSelect={handleDateSelect}
                                                    disabled={(date) => date < new Date("1900-01-01")}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        {errors.appointment_date && (
                                            <p className="text-sm text-red-600 mt-1">{errors.appointment_date}</p>
                                        )}
                                    </div>

                                    {/* Time Selection */}
                                    <div>
                                        <Label htmlFor="appointment_time">Heure de début *</Label>
                                        <Select
                                            value={data.appointment_time}
                                            onValueChange={(value) => setData('appointment_time', value)}
                                        >
                                            <SelectTrigger className="mt-2">
                                                <SelectValue placeholder="Sélectionner une heure..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {timeSlots.map((time) => (
                                                    <SelectItem key={time} value={time}>
                                                        <div className="flex items-center">
                                                            <ClockIcon className="h-4 w-4 mr-2" />
                                                            {time}
                                                        </div>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.appointment_time && (
                                            <p className="text-sm text-red-600 mt-1">{errors.appointment_time}</p>
                                        )}
                                    </div>

                                    {/* Duration Selection */}
                                    <div>
                                        <Label htmlFor="duration_minutes">Durée *</Label>
                                        <Select
                                            value={data.duration_minutes.toString()}
                                            onValueChange={(value) => setData('duration_minutes', parseInt(value))}
                                        >
                                            <SelectTrigger className="mt-2">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {durationOptions.map((option) => (
                                                    <SelectItem key={option.value} value={option.value.toString()}>
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.duration_minutes && (
                                            <p className="text-sm text-red-600 mt-1">{errors.duration_minutes}</p>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Location Type */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Type de rendez-vous</CardTitle>
                                <CardDescription>
                                    Modifiez le mode de rencontre (présentiel, visioconférence ou hybride)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="location_type">Mode de rencontre *</Label>
                                        <Select
                                            value={data.location_type}
                                            onValueChange={(value) => setData('location_type', value as 'in_person' | 'zoom' | 'hybrid')}
                                        >
                                            <SelectTrigger className="mt-2">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="in_person">En présentiel</SelectItem>
                                                <SelectItem value="zoom">Visioconférence</SelectItem>
                                                <SelectItem value="hybrid">Hybride (présentiel + visio)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.location_type && (
                                            <p className="text-sm text-red-600 mt-1">{errors.location_type}</p>
                                        )}
                                    </div>

                                    {(data.location_type === 'zoom' || data.location_type === 'hybrid') && (
                                        <div>
                                            <Label htmlFor="zoom_link">Lien de visioconférence</Label>
                                            <Input
                                                id="zoom_link"
                                                type="url"
                                                value={data.zoom_link}
                                                onChange={(e) => setData('zoom_link', e.target.value)}
                                                placeholder="https://zoom.us/j/123456789"
                                                className="mt-2"
                                            />
                                            {errors.zoom_link && (
                                                <p className="text-sm text-red-600 mt-1">{errors.zoom_link}</p>
                                            )}
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                Le lien sera envoyé automatiquement au client
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Action Buttons */}
                        <Card>
                            <CardContent className="pt-6">
                                <div className="flex flex-col sm:flex-row gap-4">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleCancel}
                                        disabled={isSubmitting}
                                        className="flex-1"
                                    >
                                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                        Annuler
                                    </Button>

                                    <Button
                                        type="submit"
                                        disabled={isSubmitting}
                                        className="flex-1 bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        <CheckIcon className="h-4 w-4 mr-2" />
                                        {isSubmitting ? 'Mise à jour...' : 'Sauvegarder les modifications'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </form>

                    {/* Informational Note */}
                    <Card className="mt-6 border-blue-200 dark:border-blue-800">
                        <CardContent className="pt-6">
                            <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                <h4 className="font-medium text-blue-800 dark:text-blue-200 mb-2">
                                    💡 Information importante
                                </h4>
                                <ul className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                    <li>• Les modifications seront automatiquement notifiées au client par email</li>
                                    <li>• Les changements de date/heure nécessitent une nouvelle confirmation du client</li>
                                    <li>• Les pasteurs recevront une notification des modifications importantes</li>
                                    <li>• L'historique des modifications est conservé pour traçabilité</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}