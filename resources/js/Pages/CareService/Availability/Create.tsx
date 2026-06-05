import { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Badge } from '@/Components/ui/badge';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import {
    CalendarIcon,
    ClockIcon,
    ArrowLeftIcon,
    InformationCircleIcon,
    ComputerDesktopIcon,
    VideoCameraIcon,
    UserGroupIcon
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

interface Props {
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

const slotDurations = [
    { value: '15', label: '15 minutes' },
    { value: '30', label: '30 minutes' },
    { value: '45', label: '45 minutes' },
    { value: '60', label: '1 heure' },
    { value: '90', label: '1h30' },
    { value: '120', label: '2 heures' },
    { value: '180', label: '3 heures' },
];

const consultationModes = [
    { value: 'in_person', label: 'En présentiel', description: 'Rendez-vous physique dans vos locaux' },
    { value: 'online', label: 'En ligne', description: 'Vidéoconférence via Zoom, Meet, Teams...' },
    { value: 'hybrid', label: 'Hybride', description: 'Au choix du consultant (présentiel ou en ligne)' },
];

export default function Create({ pastor }: Props) {
    const [previewSlots, setPreviewSlots] = useState<string[]>([]);
    const [isLoadingPreview, setIsLoadingPreview] = useState(false);
    const [selectedSlots, setSelectedSlots] = useState<string[]>([]);
    const [isInitialized, setIsInitialized] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        type: 'weekly' as 'weekly' | 'specific_date',
        day_of_week: '',
        specific_date: '',
        start_time: '05:00',
        end_time: '23:00',
        slot_duration: '60',
        is_active: true,
        consultation_mode: 'in_person' as 'in_person' | 'online' | 'hybrid',
        meeting_link: '',
        location: '',
        room: '',
        notes: '',
        selected_slots: [] as string[], // Add selected_slots to form data
    });

    // Ensure proper initialization of time inputs
    useEffect(() => {
        // Force re-initialization of time values after component mount
        setTimeout(() => {
            setData((prev) => ({
                ...prev,
                start_time: '05:00',
                end_time: '23:00'
            }));
            setIsInitialized(true);
        }, 100);
    }, []);

    // Generate preview slots when time settings change
    useEffect(() => {
        // Don't run preview generation until component is properly initialized
        if (!isInitialized) {
            return;
        }

        // Validate time format before making the request
        const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;

        if (data.start_time && data.end_time && data.slot_duration) {
            // Only proceed if times are valid format
            if (timeRegex.test(data.start_time) && timeRegex.test(data.end_time)) {
                generatePreview();
            } else {
                setPreviewSlots([]);
                setSelectedSlots([]);
                setData('selected_slots', []);
            }
        }
    }, [data.start_time, data.end_time, data.slot_duration, isInitialized]);

    const generatePreview = async () => {
        // Validate required fields and time format
        if (!data.start_time || !data.end_time || !data.slot_duration) {
            setPreviewSlots([]);
            setSelectedSlots([]);
            setData('selected_slots', []); // Clear selected slots when parameters are invalid
            return;
        }

        // Validate time format (HH:MM)
        const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
        if (!timeRegex.test(data.start_time) || !timeRegex.test(data.end_time)) {
            toast.error('Format d\'heure invalide. Utilisez le format HH:MM (ex: 09:00)');
            return;
        }

        const requestData = {
            start_time: data.start_time,
            end_time: data.end_time,
            slot_duration: parseInt(data.slot_duration),
        };

        setIsLoadingPreview(true);
        try {
            const response = await window.axios.post(route('care-service-availability.preview-slots'), requestData);

            // Axios automatically parses JSON and throws on error status codes
            const result = response.data;
            setPreviewSlots(result.slots || []);
            setSelectedSlots([]);
            setData('selected_slots', []); // Clear selected slots when new preview is generated
        } catch (error) {
            console.error('Error generating preview:', error);
            setPreviewSlots([]);
            // Show user-friendly error message
            toast.error('Erreur lors de la génération de l\'aperçu des créneaux');
        } finally {
            setIsLoadingPreview(false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate that at least one slot is selected only if slots are available
        if (previewSlots.length > 0 && selectedSlots.length === 0) {
            toast.error('Veuillez sélectionner au moins un créneau horaire');
            return;
        }

        // Ensure data is synchronized before submission
        setData('selected_slots', selectedSlots);

        post(route('care-service-availability.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Créneaux de disponibilité créés avec succès');
                router.visit(route('care-service-availability.index'));
            },
            onError: (errors) => {
                if (errors.conflict) {
                    toast.error(errors.conflict);
                } else {
                    toast.error('Erreur lors de la création des créneaux');
                }
            },
        });
    };

    // Get minimum date (today) for specific date input
    const today = new Date().toISOString().split('T')[0];

    // Handle slot selection
    const toggleSlotSelection = (slot: string) => {
        setSelectedSlots(prev => {
            const newSlots = prev.includes(slot)
                ? prev.filter(s => s !== slot)
                : [...prev, slot];

            // Sync with form data
            setData('selected_slots', newSlots);

            return newSlots;
        });
    };

    // Clear selection when day changes
    const handleDayChange = (value: string) => {
        setData('day_of_week', value);
        setSelectedSlots([]);
        setData('selected_slots', []); // Clear selected slots when day changes
    };

    // Select/deselect all slots
    const selectAllSlots = () => {
        setSelectedSlots([...previewSlots]);
        setData('selected_slots', [...previewSlots]);
    };

    const deselectAllSlots = () => {
        setSelectedSlots([]);
        setData('selected_slots', []);
    };

    return (
        <DashboardLayout
            title="Créer des créneaux de disponibilité"
            description="Définissez vos heures de disponibilité pour les consultations care service"
            actions={
                <Button variant="outline" size="sm" asChild>
                    <a href={route('care-service-availability.index')}>
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour
                    </a>
                </Button>
            }
        >
            <Head title="Créer des créneaux de disponibilité" />

            <div className="p-3 sm:p-6">
                <Card>
                    <CardHeader className="p-4 sm:p-6">
                        <CardTitle className="flex items-center space-x-2 text-base sm:text-lg">
                            <CalendarIcon className="h-5 w-5 flex-shrink-0" />
                            <span>Configuration des créneaux</span>
                        </CardTitle>
                        <CardDescription className="text-xs sm:text-sm">
                            Configurez vos créneaux de disponibilité pour les consultations care service
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-6 pt-0 sm:pt-0">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                                {/* Form Fields */}
                                <div className="space-y-6">
                                    {/* Type Selection */}
                                    <div className="space-y-3">
                                        <Label>Type de disponibilité</Label>
                                        <RadioGroup
                                            value={data.type}
                                            onValueChange={(value: 'weekly' | 'specific_date') => setData('type', value)}
                                        >
                                            <div className="flex items-center space-x-2">
                                                <RadioGroupItem value="weekly" id="weekly" />
                                                <Label htmlFor="weekly">Récurrent (chaque semaine)</Label>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <RadioGroupItem value="specific_date" id="specific_date" />
                                                <Label htmlFor="specific_date">Date spécifique</Label>
                                            </div>
                                        </RadioGroup>
                                        {errors.type && (
                                            <p className="text-red-500 text-sm">{errors.type}</p>
                                        )}
                                    </div>

                                    {/* Day/Date Selection */}
                                    {data.type === 'weekly' ? (
                                        <div className="space-y-2">
                                            <Label htmlFor="day_of_week">Jour de la semaine</Label>
                                            <Select value={data.day_of_week} onValueChange={handleDayChange}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Sélectionner un jour">
                                                        {data.day_of_week ? dayNames[parseInt(data.day_of_week)] : "Sélectionner un jour"}
                                                    </SelectValue>
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {Object.entries(dayNames).map(([value, label]) => (
                                                        <SelectItem key={value} value={value}>
                                                            {label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.day_of_week && (
                                                <p className="text-red-500 text-sm">{errors.day_of_week}</p>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            <Label htmlFor="specific_date">Date spécifique</Label>
                                            <Input
                                                id="specific_date"
                                                type="date"
                                                min={today}
                                                value={data.specific_date}
                                                onChange={(e) => setData('specific_date', e.target.value)}
                                            />
                                            {errors.specific_date && (
                                                <p className="text-red-500 text-sm">{errors.specific_date}</p>
                                            )}
                                        </div>
                                    )}

                                    {/* Time Range */}
                                    <div className="space-y-4">
                                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            <Label>Horaires de disponibilité</Label>
                                            <div className="flex flex-wrap gap-2 text-xs">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-7 sm:h-6 px-3 sm:px-2 text-xs flex-1 sm:flex-none"
                                                    onClick={() => {
                                                        setData('start_time', '08:00');
                                                        setData('end_time', '18:00');
                                                    }}
                                                >
                                                    Jour (8h-18h)
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-7 sm:h-6 px-3 sm:px-2 text-xs flex-1 sm:flex-none"
                                                    onClick={() => {
                                                        setData('start_time', '05:00');
                                                        setData('end_time', '23:00');
                                                    }}
                                                >
                                                    Étendu (5h-23h)
                                                </Button>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="start_time">Heure de début</Label>
                                                <Input
                                                    id="start_time"
                                                    type="time"
                                                    min="05:00"
                                                    max="22:00"
                                                    value={data.start_time}
                                                    onChange={(e) => {
                                                        const value = e.target.value;
                                                        // Only set if it's a valid time format or empty
                                                        if (value === '' || /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(value)) {
                                                            setData('start_time', value);
                                                        }
                                                    }}
                                                />
                                                {errors.start_time && (
                                                    <p className="text-red-500 text-sm">{errors.start_time}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="end_time">Heure de fin</Label>
                                                <Input
                                                    id="end_time"
                                                    type="time"
                                                    min="06:00"
                                                    max="23:00"
                                                    value={data.end_time}
                                                    onChange={(e) => {
                                                        const value = e.target.value;
                                                        // Only set if it's a valid time format or empty
                                                        if (value === '' || /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(value)) {
                                                            setData('end_time', value);
                                                        }
                                                    }}
                                                />
                                                {errors.end_time && (
                                                    <p className="text-red-500 text-sm">{errors.end_time}</p>
                                                )}
                                            </div>
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            💡 Plage autorisée : 05:00 à 23:00 pour couvrir toutes les consultations
                                        </div>
                                    </div>

                                    {/* Slot Duration */}
                                    <div className="space-y-2">
                                        <Label htmlFor="slot_duration">Durée de chaque créneau</Label>
                                        <Select value={data.slot_duration} onValueChange={(value) => setData('slot_duration', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {slotDurations.map((duration) => (
                                                    <SelectItem key={duration.value} value={duration.value}>
                                                        {duration.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.slot_duration && (
                                            <p className="text-red-500 text-sm">{errors.slot_duration}</p>
                                        )}
                                    </div>

                                    {/* Consultation Mode */}
                                    <div className="space-y-4">
                                        <Label>Mode de consultation</Label>
                                        <RadioGroup
                                            value={data.consultation_mode}
                                            onValueChange={(value: 'in_person' | 'online' | 'hybrid') => {
                                                setData('consultation_mode', value);
                                                // Clear meeting link if switching to in_person
                                                if (value === 'in_person') {
                                                    setData('meeting_link', '');
                                                }
                                            }}
                                        >
                                            {consultationModes.map((mode) => (
                                                <div key={mode.value} className="flex items-start space-x-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                    <RadioGroupItem value={mode.value} id={mode.value} className="mt-1" />
                                                    <div className="flex-1">
                                                        <Label htmlFor={mode.value} className="flex items-center space-x-2 cursor-pointer">
                                                            {mode.value === 'in_person' && <UserGroupIcon className="h-4 w-4" />}
                                                            {mode.value === 'online' && <VideoCameraIcon className="h-4 w-4" />}
                                                            {mode.value === 'hybrid' && <ComputerDesktopIcon className="h-4 w-4" />}
                                                            <span className="font-medium">{mode.label}</span>
                                                        </Label>
                                                        <p className="text-sm text-gray-500 mt-1">{mode.description}</p>
                                                    </div>
                                                </div>
                                            ))}
                                        </RadioGroup>
                                        {errors.consultation_mode && (
                                            <p className="text-red-500 text-sm">{errors.consultation_mode}</p>
                                        )}
                                    </div>

                                    {/* Meeting Link (for online and hybrid modes) */}
                                    {(data.consultation_mode === 'online' || data.consultation_mode === 'hybrid') && (
                                        <div className="space-y-2">
                                            <Label htmlFor="meeting_link">
                                                Lien de réunion
                                                {data.consultation_mode === 'online' && <span className="text-red-500 ml-1">*</span>}
                                            </Label>
                                            <Input
                                                id="meeting_link"
                                                type="url"
                                                placeholder="https://zoom.us/j/... ou https://meet.google.com/..."
                                                value={data.meeting_link}
                                                onChange={(e) => setData('meeting_link', e.target.value)}
                                            />
                                            <p className="text-xs text-gray-500">
                                                Lien Zoom, Google Meet, Microsoft Teams ou autre plateforme de visioconférence
                                            </p>
                                            {errors.meeting_link && (
                                                <p className="text-red-500 text-sm">{errors.meeting_link}</p>
                                            )}
                                        </div>
                                    )}

                                    {/* Location and Room - Show for in_person and hybrid */}
                                    {(data.consultation_mode === 'in_person' || data.consultation_mode === 'hybrid') && (
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="location">Lieu (optionnel)</Label>
                                                <Input
                                                    id="location"
                                                    type="text"
                                                    placeholder="Ex: Église ICC Munich, 123 Rue de la Paix..."
                                                    value={data.location}
                                                    onChange={(e) => setData('location', e.target.value)}
                                                />
                                                <p className="text-xs text-gray-500">
                                                    Adresse ou lieu de la consultation en présentiel
                                                </p>
                                                {errors.location && (
                                                    <p className="text-red-500 text-sm">{errors.location}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="room">Salle (optionnel)</Label>
                                                <Input
                                                    id="room"
                                                    type="text"
                                                    placeholder="Ex: Bureau care service, Salle 101, Accueil..."
                                                    value={data.room}
                                                    onChange={(e) => setData('room', e.target.value)}
                                                />
                                                <p className="text-xs text-gray-500">
                                                    Salle ou bureau spécifique pour la consultation
                                                </p>
                                                {errors.room && (
                                                    <p className="text-red-500 text-sm">{errors.room}</p>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {/* Notes */}
                                    <div className="space-y-2">
                                        <Label htmlFor="notes">Notes (optionnel)</Label>
                                        <Textarea
                                            id="notes"
                                            placeholder="Notes ou instructions particulières pour ces créneaux..."
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            rows={3}
                                        />
                                        {errors.notes && (
                                            <p className="text-red-500 text-sm">{errors.notes}</p>
                                        )}
                                    </div>

                                    {/* Conflict Error */}
                                    {(errors as any).conflict && (
                                        <Alert className="border-red-200 bg-red-50">
                                            <InformationCircleIcon className="h-4 w-4 text-red-500" />
                                            <AlertDescription className="text-red-700">
                                                {(errors as any).conflict}
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    {/* Submit Buttons */}
                                    <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4">
                                        <Button type="button" variant="outline" asChild className="w-full sm:w-auto">
                                            <a href={route('care-service-availability.index')}>
                                                Annuler
                                            </a>
                                        </Button>
                                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                            {processing ? 'Création...' : 'Créer les créneaux'}
                                        </Button>
                                    </div>
                                </div>

                                {/* Preview */}
                                <div className="lg:border-l lg:pl-6 pt-4 lg:pt-0 border-t lg:border-t-0 mt-4 lg:mt-0">
                                    <div className="mb-4">
                                        <h3 className="font-medium text-base sm:text-lg flex items-center space-x-2">
                                            <ClockIcon className="h-5 w-5 flex-shrink-0" />
                                            <span>Aperçu des créneaux</span>
                                        </h3>
                                        <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                            Créneaux de consultation qui seront disponibles
                                        </p>
                                    </div>
                                    {isLoadingPreview ? (
                                        <div className="text-center py-4">
                                            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900 mx-auto"></div>
                                            <p className="text-sm text-gray-500 mt-2">Génération de l'aperçu...</p>
                                        </div>
                                    ) : previewSlots.length > 0 ? (
                                        <div>
                                            <div className="mb-3">
                                                <p className="text-sm text-gray-600 mb-1">
                                                    {previewSlots.length} créneaux de {data.slot_duration} minutes seront disponibles :
                                                </p>
                                                <p className="text-xs text-blue-600">
                                                    💡 Cliquez sur les créneaux pour les sélectionner
                                                    {selectedSlots.length > 0 && (
                                                        <span className="ml-2 font-medium">
                                                            ({selectedSlots.length} sélectionné{selectedSlots.length > 1 ? 's' : ''})
                                                        </span>
                                                    )}
                                                </p>
                                            </div>

                                            {/* Selection buttons */}
                                            {previewSlots.length > 0 && (
                                                <div className="flex gap-2 mb-3">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={selectAllSlots}
                                                        className="text-xs"
                                                    >
                                                        Tout sélectionner
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={deselectAllSlots}
                                                        className="text-xs"
                                                        disabled={selectedSlots.length === 0}
                                                    >
                                                        Tout désélectionner
                                                    </Button>
                                                </div>
                                            )}

                                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 sm:max-h-60 overflow-y-auto">
                                                {previewSlots.map((slot, index) => {
                                                    const isSelected = selectedSlots.includes(slot);
                                                    return (
                                                        <Badge
                                                            key={index}
                                                            variant={isSelected ? "default" : "secondary"}
                                                            className={`justify-center cursor-pointer transition-all duration-200 hover:scale-105 py-2 sm:py-1 text-xs sm:text-sm ${
                                                                isSelected
                                                                    ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                                                    : 'hover:bg-gray-300'
                                                            }`}
                                                            onClick={() => toggleSlotSelection(slot)}
                                                        >
                                                            {slot}
                                                        </Badge>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-gray-500 text-center py-4">
                                            Configurez les horaires pour voir l'aperçu des créneaux
                                        </p>
                                    )}
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}