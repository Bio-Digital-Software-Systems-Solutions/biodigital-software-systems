import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ClockIcon, ArrowLeftIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface Props {
    pastors?: User[];
    auth: {
        user: User;
    };
}

interface AppointmentFormData {
    pastor_id: number | null;
    appointment_date: string;
    appointment_time: string;
    duration_minutes: number;
    location_type: 'in_person' | 'zoom' | 'hybrid';
    zoom_link: string;
}

export default function Create({ pastors, auth }: Props) {
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, setData, errors, reset } = useForm<AppointmentFormData>({
        pastor_id: pastors && pastors.length > 0 ? pastors[0].id : null,
        appointment_date: '',
        appointment_time: '',
        duration_minutes: 60,
        location_type: 'in_person',
        zoom_link: '',
    });

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
            await router.post('/pastoral-care/appointments', data as any, {
                onSuccess: () => {
                    toast.success('Rendez-vous créé avec succès');
                    router.visit('/pastoral-care/appointments');
                },
                onError: (errors) => {
                    console.error('Erreurs de validation:', errors);
                    if (Object.keys(errors).length > 0) {
                        toast.error('Veuillez corriger les erreurs dans le formulaire');
                    } else {
                        toast.error('Erreur lors de la création du rendez-vous');
                    }
                }
            });
        } catch (error) {
            toast.error('Erreur lors de la création du rendez-vous');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleCancel = () => {
        router.visit('/pastoral-care/appointments');
    };

    return (
        <DashboardLayout
            title="Nouveau rendez-vous de soin pastoral"
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
            <Head title="Nouveau rendez-vous - Soin Pastoral" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-8">
                        {/* Pastor Selection */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Assignation du pasteur</CardTitle>
                                <CardDescription>
                                    Sélectionnez le pasteur responsable de ce rendez-vous
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="pastor_id">Pasteur responsable *</Label>
                                        <Select
                                            value={data.pastor_id?.toString() || ''}
                                            onValueChange={(value) => setData('pastor_id', parseInt(value))}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Sélectionner un pasteur..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {pastors && pastors.length > 0 ? pastors.map((pastor) => (
                                                    <SelectItem key={pastor.id} value={pastor.id.toString()}>
                                                        {pastor.first_name} {pastor.last_name}
                                                    </SelectItem>
                                                )) : (
                                                    <SelectItem value="">
                                                        Aucun pasteur disponible
                                                    </SelectItem>
                                                )}
                                            </SelectContent>
                                        </Select>
                                        {errors.pastor_id && (
                                            <p className="text-sm text-red-600 mt-1">{errors.pastor_id}</p>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Date and Time */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Date et heure</CardTitle>
                                <CardDescription>
                                    Définissez la date, l'heure et la durée du rendez-vous
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    {/* Date Selection */}
                                    <div>
                                        <Label htmlFor="appointment_date">Date du rendez-vous *</Label>
                                        <Input
                                            id="appointment_date"
                                            type="date"
                                            value={data.appointment_date}
                                            onChange={(e) => setData('appointment_date', e.target.value)}
                                            min={new Date().toISOString().split('T')[0]}
                                            className="mt-2"
                                            required
                                        />
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
                                    Choisissez le mode de rencontre (présentiel, visioconférence ou hybride)
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
                                        {isSubmitting ? 'Création...' : 'Créer le rendez-vous'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}