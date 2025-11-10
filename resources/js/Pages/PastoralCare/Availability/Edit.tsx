import { useState, useEffect } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
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
import { format } from 'date-fns';

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
    created_at: string;
    updated_at: string;
}

interface Props {
    availability: PastorAvailability;
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

export default function Edit({ availability }: Props) {
    const [previewSlots, setPreviewSlots] = useState<string[]>([]);
    const [isLoadingPreview, setIsLoadingPreview] = useState(false);
    const [selectedSlots, setSelectedSlots] = useState<string[]>(availability.selected_slots || []);
    const [isInitialized, setIsInitialized] = useState(false);

    // Format the availability data for the form
    const formatTimeForInput = (timeString: string) => {
        console.log('formatTimeForInput called with:', timeString);

        // If it's empty or undefined, return a default
        if (!timeString) {
            console.log('Empty timeString, returning default');
            return '09:00';
        }

        // If timeString is already in HH:MM format, use it directly
        if (timeString.match(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/)) {
            console.log('Time already in HH:MM format:', timeString);
            return timeString;
        }

        // If timeString is in HH:MM:SS format, extract HH:MM
        if (timeString.match(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/)) {
            const timeHHMM = timeString.slice(0, 5);
            console.log('Time in HH:MM:SS format, extracted HH:MM:', timeHHMM);
            return timeHHMM;
        }

        // If it's a datetime string, try to extract the time part
        try {
            const date = new Date(timeString);

            // Check if the date is valid
            if (isNaN(date.getTime())) {
                console.warn('Invalid date from timeString, using default:', timeString);
                return '09:00'; // Default fallback
            }

            const timeStr = date.toTimeString().slice(0, 5);
            console.log('Formatted time from date:', timeStr);

            // Validate the result
            if (timeStr && timeStr.match(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/)) {
                return timeStr;
            } else {
                console.warn('Invalid time result from date formatting, using default:', timeStr);
                return '09:00'; // Default fallback
            }
        } catch (error) {
            console.error('Error formatting time, using default:', error, timeString);
            return '09:00'; // Default fallback
        }
    };

    // Format date for HTML date input (YYYY-MM-DD format)
    const formatDateForInput = (dateString?: string) => {
        if (!dateString) return '';

        // If already in YYYY-MM-DD format, use directly
        if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
            return dateString;
        }

        // Try to parse and format the date
        try {
            const date = new Date(dateString);
            // Check if the date is valid
            if (isNaN(date.getTime())) {
                return '';
            }
            return date.toISOString().split('T')[0];
        } catch (error) {
            console.error('Error formatting date:', error);
            return '';
        }
    };

    // Store original values for comparison
    const originalValues = {
        type: availability.type,
        day_of_week: availability.day_of_week ? availability.day_of_week.toString() : '',
        specific_date: formatDateForInput(availability.specific_date),
        start_time: formatTimeForInput(availability.start_time),
        end_time: formatTimeForInput(availability.end_time),
        slot_duration: availability.slot_duration.toString(),
        is_active: availability.is_active,
        consultation_mode: availability.consultation_mode || 'in_person',
        meeting_link: availability.meeting_link || '',
        location: availability.location || '',
        room: availability.room || '',
        notes: availability.notes || '',
        selected_slots: availability.selected_slots || [],
    };

    const { data, setData, put, processing, errors } = useForm({
        type: availability.type,
        day_of_week: availability.day_of_week ? availability.day_of_week.toString() : '',
        specific_date: formatDateForInput(availability.specific_date),
        start_time: formatTimeForInput(availability.start_time),
        end_time: formatTimeForInput(availability.end_time),
        slot_duration: availability.slot_duration.toString(),
        is_active: availability.is_active,
        consultation_mode: availability.consultation_mode || 'in_person',
        meeting_link: availability.meeting_link || '',
        location: availability.location || '',
        room: availability.room || '',
        notes: availability.notes || '',
    });

    // Ensure proper initialization of time inputs
    useEffect(() => {
        console.log('Edit component initialization effect running...');
        // Force re-initialization of time values after component mount
        setTimeout(() => {
            console.log('Re-initializing time values in Edit component...');
            setData((prev) => ({
                ...prev,
                start_time: formatTimeForInput(availability.start_time),
                end_time: formatTimeForInput(availability.end_time)
            }));
            setIsInitialized(true);
        }, 100);
    }, []);

    // Generate preview slots when time settings change
    useEffect(() => {
        // Don't run preview generation until component is properly initialized
        if (!isInitialized) {
            console.log('Preview effect skipped in Edit - component not yet initialized');
            return;
        }

        console.log('Preview effect triggered in Edit with:', {
            start_time: data.start_time,
            end_time: data.end_time,
            slot_duration: data.slot_duration,
            isInitialized,
            types: {
                start_time: typeof data.start_time,
                end_time: typeof data.end_time,
                slot_duration: typeof data.slot_duration
            }
        });

        // Validate time format before making the request
        const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;

        if (data.start_time && data.end_time && data.slot_duration) {
            // Only proceed if times are valid format
            if (timeRegex.test(data.start_time) && timeRegex.test(data.end_time)) {
                console.log('Valid time format detected in Edit, generating preview...');
                generatePreview();
            } else {
                console.log('Invalid time format detected in Edit, skipping preview generation:', {
                    start_time: data.start_time,
                    end_time: data.end_time,
                    start_valid: timeRegex.test(data.start_time),
                    end_valid: timeRegex.test(data.end_time)
                });
                setPreviewSlots([]);
                setSelectedSlots([]);
            }
        }
    }, [data.start_time, data.end_time, data.slot_duration, isInitialized]);

    // Note: Initial preview generation is now handled by the main useEffect with isInitialized check

    // Update selected slots when preview slots change to keep only valid selections
    useEffect(() => {
        if (previewSlots.length > 0) {
            setSelectedSlots(prev => prev.filter(slot => previewSlots.includes(slot)));
        }
    }, [previewSlots]);

    const generatePreview = async () => {
        // Validate required fields and time format
        if (!data.start_time || !data.end_time || !data.slot_duration) {
            console.log('Missing required fields in Edit:', { start_time: data.start_time, end_time: data.end_time, slot_duration: data.slot_duration });
            setPreviewSlots([]);
            setSelectedSlots([]);
            return;
        }

        // Validate time format (HH:MM)
        const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
        if (!timeRegex.test(data.start_time) || !timeRegex.test(data.end_time)) {
            console.error('Invalid time format in Edit:', { start_time: data.start_time, end_time: data.end_time });
            toast.error('Format d\'heure invalide. Utilisez le format HH:MM (ex: 09:00)');
            return;
        }

        const requestData = {
            start_time: data.start_time,
            end_time: data.end_time,
            slot_duration: parseInt(data.slot_duration),
        };

        console.log('Sending preview request from Edit with data:', requestData);

        setIsLoadingPreview(true);
        try {
            const response = await window.axios.post(route('pastoral-availability.preview-slots'), requestData);

            // Axios automatically parses JSON and throws on error status codes
            const result = response.data;
            setPreviewSlots(result.slots || []);
        } catch (error) {
            console.error('Error generating preview in Edit:', error);
            setPreviewSlots([]);
            // Show user-friendly error message
            toast.error('Erreur lors de la génération de l\'aperçu des créneaux');
        } finally {
            setIsLoadingPreview(false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate required fields based on type
        if (data.type === 'weekly' && !data.day_of_week) {
            toast.error('Veuillez sélectionner un jour de la semaine');
            return;
        }

        if (data.type === 'specific_date' && !data.specific_date) {
            toast.error('Veuillez sélectionner une date spécifique');
            return;
        }

        // Validate that at least one slot is selected (only if slots exist)
        if (previewSlots.length > 0 && selectedSlots.length === 0) {
            toast.error('Veuillez sélectionner au moins un créneau horaire');
            return;
        }

        // Helper function to compare arrays
        const arraysEqual = (a: any[], b: any[]) => {
            if (a.length !== b.length) return false;
            return a.sort().join(',') === b.sort().join(',');
        };

        // Build object with only changed fields
        const submitData: any = {};
        let hasChanges = false;

        // Compare each field and only include if changed
        if (data.type !== originalValues.type) {
            submitData.type = data.type;
            hasChanges = true;

            // When type changes, we need to include the appropriate field for the new type
            // and potentially clear the old field
            if (data.type === 'weekly') {
                if (data.day_of_week) {
                    submitData.day_of_week = parseInt(data.day_of_week);
                }
                // Clear specific_date when switching to weekly
                submitData.specific_date = null;
            } else if (data.type === 'specific_date') {
                if (data.specific_date) {
                    submitData.specific_date = data.specific_date;
                }
                // Clear day_of_week when switching to specific_date
                submitData.day_of_week = null;
            }
        } else {
            // Type hasn't changed, check individual fields
            if (data.day_of_week !== originalValues.day_of_week) {
                if (data.type === 'weekly') {
                    submitData.day_of_week = parseInt(data.day_of_week);
                    hasChanges = true;
                }
            }

            if (data.specific_date !== originalValues.specific_date) {
                if (data.type === 'specific_date') {
                    submitData.specific_date = data.specific_date;
                    hasChanges = true;
                }
            }
        }

        if (data.start_time !== originalValues.start_time) {
            submitData.start_time = data.start_time;
            hasChanges = true;
        }

        if (data.end_time !== originalValues.end_time) {
            submitData.end_time = data.end_time;
            hasChanges = true;
        }

        if (data.slot_duration !== originalValues.slot_duration) {
            submitData.slot_duration = parseInt(data.slot_duration);
            hasChanges = true;
        }

        if (data.is_active !== originalValues.is_active) {
            submitData.is_active = data.is_active;
            hasChanges = true;
        }

        if (data.consultation_mode !== originalValues.consultation_mode) {
            submitData.consultation_mode = data.consultation_mode;
            hasChanges = true;
        }

        if (data.meeting_link !== originalValues.meeting_link) {
            submitData.meeting_link = data.meeting_link;
            hasChanges = true;
        }

        if (data.location !== originalValues.location) {
            submitData.location = data.location;
            hasChanges = true;
        }

        if (data.room !== originalValues.room) {
            submitData.room = data.room;
            hasChanges = true;
        }

        if (data.notes !== originalValues.notes) {
            submitData.notes = data.notes;
            hasChanges = true;
        }

        if (!arraysEqual(selectedSlots, originalValues.selected_slots)) {
            submitData.selected_slots = selectedSlots;
            hasChanges = true;
        }

        // If no changes detected, show info message and return
        if (!hasChanges) {
            toast.info('Aucune modification détectée');
            return;
        }

        router.put(route('pastoral-availability.update', availability.id), submitData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Créneaux de disponibilité mis à jour avec succès');
                router.visit(route('pastoral-availability.index'));
            },
            onError: (errors) => {
                if (errors.conflict) {
                    toast.error(errors.conflict);
                } else {
                    toast.error('Erreur lors de la mise à jour des créneaux');
                }
            },
        });
    };

    // Handle slot selection
    const toggleSlotSelection = (slot: string) => {
        setSelectedSlots(prev => {
            if (prev.includes(slot)) {
                return prev.filter(s => s !== slot);
            } else {
                return [...prev, slot];
            }
        });
    };

    // Clear selection when day changes
    const handleDayChange = (value: string) => {
        setData('day_of_week', value);
        setSelectedSlots([]); // Clear selected slots when day changes
    };

    // Clear selection when date changes
    const handleDateChange = (value: string) => {
        setData('specific_date', value);
        setSelectedSlots([]); // Clear selected slots when date changes
    };

    // Select/deselect all slots
    const selectAllSlots = () => {
        setSelectedSlots([...previewSlots]);
    };

    const deselectAllSlots = () => {
        setSelectedSlots([]);
    };

    // Helper function to check if a field has been modified
    const isFieldModified = (fieldName: string, currentValue: any, originalValue: any) => {
        if (fieldName === 'selected_slots') {
            const arraysEqual = (a: any[], b: any[]) => {
                if (a.length !== b.length) return false;
                return a.sort().join(',') === b.sort().join(',');
            };
            return !arraysEqual(currentValue, originalValue);
        }
        return currentValue !== originalValue;
    };

    // Get count of modified fields
    const getModifiedFieldsCount = () => {
        let count = 0;
        if (data.type !== originalValues.type) count++;
        if (data.day_of_week !== originalValues.day_of_week) count++;
        if (data.specific_date !== originalValues.specific_date) count++;
        if (data.start_time !== originalValues.start_time) count++;
        if (data.end_time !== originalValues.end_time) count++;
        if (data.slot_duration !== originalValues.slot_duration) count++;
        if (data.is_active !== originalValues.is_active) count++;
        if (data.consultation_mode !== originalValues.consultation_mode) count++;
        if (data.meeting_link !== originalValues.meeting_link) count++;
        if (data.notes !== originalValues.notes) count++;
        if (isFieldModified('selected_slots', selectedSlots, originalValues.selected_slots)) count++;
        return count;
    };

    const modifiedFieldsCount = getModifiedFieldsCount();

    // Get minimum date (today) for specific date input
    const today = new Date().toISOString().split('T')[0];

    return (
        <DashboardLayout
            title="Modifier les créneaux de disponibilité"
            description="Modifiez vos heures de disponibilité pour les consultations pastorales"
            actions={
                <Button variant="outline" size="sm" asChild>
                    <Link href={route('pastoral-availability.index')}>
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour
                    </Link>
                </Button>
            }
        >
            <Head title="Modifier les créneaux de disponibilité" />

            <div className="p-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <CalendarIcon className="h-5 w-5" />
                            <span>Configuration des créneaux</span>
                        </CardTitle>
                        <CardDescription>
                            Modifiez vos créneaux de disponibilité pour les consultations pastorales
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Form Fields */}
                                <div className="space-y-6">
                                    {/* Type Selection */}
                                    <div className="space-y-3">
                                        <Label>Type de disponibilité</Label>
                                        <RadioGroup
                                            value={data.type}
                                            onValueChange={(value: 'weekly' | 'specific_date') => {
                                                setData('type', value);
                                                // Clear related fields when switching type
                                                if (value === 'weekly') {
                                                    setData('specific_date', '');
                                                } else {
                                                    setData('day_of_week', '');
                                                }
                                                setSelectedSlots([]); // Clear selected slots when type changes
                                            }}
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
                                        {data.type !== originalValues.type && (
                                            <Alert>
                                                <InformationCircleIcon className="h-4 w-4" />
                                                <AlertDescription>
                                                    Vous avez changé le type de disponibilité.
                                                    {data.type === 'weekly' ?
                                                        ' Veuillez sélectionner un jour de la semaine.' :
                                                        ' Veuillez sélectionner une date spécifique.'
                                                    }
                                                </AlertDescription>
                                            </Alert>
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
                                                onChange={(e) => handleDateChange(e.target.value)}
                                            />
                                            {errors.specific_date && (
                                                <p className="text-red-500 text-sm">{errors.specific_date}</p>
                                            )}
                                        </div>
                                    )}

                                    {/* Time Range */}
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <Label>Horaires de disponibilité</Label>
                                            <div className="flex gap-2 text-xs">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-6 px-2 text-xs"
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
                                                    className="h-6 px-2 text-xs"
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
                                                        console.log('Start time input changed in Edit:', e.target.value);
                                                        const value = e.target.value;
                                                        // Only set if it's a valid time format or empty
                                                        if (value === '' || /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(value)) {
                                                            setData('start_time', value);
                                                        } else {
                                                            console.warn('Invalid time format rejected in Edit:', value);
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
                                                        console.log('End time input changed in Edit:', e.target.value);
                                                        const value = e.target.value;
                                                        // Only set if it's a valid time format or empty
                                                        if (value === '' || /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(value)) {
                                                            setData('end_time', value);
                                                        } else {
                                                            console.warn('Invalid time format rejected in Edit:', value);
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

                                    {/* Active Status */}
                                    <div className="flex items-center space-x-2">
                                        <Switch
                                            id="is_active"
                                            checked={data.is_active}
                                            onCheckedChange={(checked) => setData('is_active', checked)}
                                        />
                                        <Label htmlFor="is_active">Créneaux actifs</Label>
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
                                                    placeholder="Ex: Bureau pastoral, Salle 101, Accueil..."
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

                                    {/* Modification indicator */}
                                    {modifiedFieldsCount > 0 && (
                                        <Alert className="border-blue-200 bg-blue-50">
                                            <InformationCircleIcon className="h-4 w-4 text-blue-500" />
                                            <AlertDescription className="text-blue-700">
                                                {modifiedFieldsCount} champ{modifiedFieldsCount > 1 ? 's' : ''} modifié{modifiedFieldsCount > 1 ? 's' : ''}.
                                                Seules les modifications seront persistées.
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    {/* Submit Buttons */}
                                    <div className="flex justify-end space-x-3 pt-4">
                                        <Button type="button" variant="outline" asChild>
                                            <Link href={route('pastoral-availability.index')}>
                                                Annuler
                                            </Link>
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing || modifiedFieldsCount === 0}
                                            className={modifiedFieldsCount === 0 ? 'opacity-50 cursor-not-allowed' : ''}
                                        >
                                            {processing ? 'Mise à jour...' :
                                             modifiedFieldsCount === 0 ? 'Aucune modification' :
                                             `Mettre à jour (${modifiedFieldsCount} champ${modifiedFieldsCount > 1 ? 's' : ''})`}
                                        </Button>
                                    </div>
                                </div>

                                {/* Slot Selection */}
                                <div>
                                    <div className="mb-4">
                                        <h3 className="font-medium text-lg flex items-center space-x-2">
                                            <ClockIcon className="h-5 w-5" />
                                            <span>Sélection des créneaux</span>
                                        </h3>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            Choisissez les créneaux de consultation disponibles
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
                                                    {previewSlots.length} créneaux de {data.slot_duration} minutes disponibles
                                                </p>
                                                <div className="flex items-center justify-between text-xs">
                                                    <p className="text-blue-600">
                                                        💡 Cliquez sur les créneaux pour les sélectionner/désélectionner
                                                    </p>
                                                    {selectedSlots.length > 0 && (
                                                        <span className="font-medium text-blue-800 bg-blue-100 px-2 py-1 rounded">
                                                            {selectedSlots.length} sélectionné{selectedSlots.length > 1 ? 's' : ''}
                                                        </span>
                                                    )}
                                                </div>
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

                                            <div className="grid grid-cols-3 gap-2 max-h-60 overflow-y-auto">
                                                {previewSlots.map((slot, index) => {
                                                    const isSelected = selectedSlots.includes(slot);
                                                    return (
                                                        <Badge
                                                            key={index}
                                                            variant={isSelected ? "default" : "secondary"}
                                                            className={`justify-center cursor-pointer transition-all duration-200 hover:scale-105 ${
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

                                            {/* Show existing selection info */}
                                            {selectedSlots.length > 0 && (
                                                <div className="mt-4 p-3 bg-green-50 rounded border border-green-200">
                                                    <p className="text-sm text-green-800 font-medium mb-2">
                                                        ✅ Créneaux actuellement sélectionnés chargés depuis la base de données
                                                    </p>
                                                    {selectedSlots.some(slot => !previewSlots.includes(slot)) && (
                                                        <div>
                                                            <p className="text-sm text-yellow-800 font-medium mb-2">⚠️ Certains créneaux ne correspondent plus aux paramètres actuels :</p>
                                                            <div className="grid grid-cols-3 gap-2 mb-2">
                                                                {selectedSlots
                                                                    .filter(slot => !previewSlots.includes(slot))
                                                                    .sort((a, b) => {
                                                                        // Convert HH:MM to minutes for comparison
                                                                        const timeA = a.split(':').reduce((acc, time, index) => acc + (index === 0 ? parseInt(time) * 60 : parseInt(time)), 0);
                                                                        const timeB = b.split(':').reduce((acc, time, index) => acc + (index === 0 ? parseInt(time) * 60 : parseInt(time)), 0);
                                                                        return timeA - timeB;
                                                                    })
                                                                    .map((slot, index) => (
                                                                    <Badge
                                                                        key={index}
                                                                        variant="outline"
                                                                        className="justify-center bg-yellow-100 text-yellow-800 border-yellow-300"
                                                                    >
                                                                        {slot}
                                                                    </Badge>
                                                                ))}
                                                            </div>
                                                            <p className="text-xs text-yellow-700">
                                                                Modifiez les paramètres de temps pour inclure ces créneaux ou ils seront supprimés.
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="text-center py-4">
                                            <p className="text-gray-500 mb-2">
                                                Configurez les horaires pour voir les créneaux disponibles
                                            </p>
                                            {selectedSlots.length > 0 && (
                                                <div className="mt-4 p-3 bg-blue-50 rounded border border-blue-200">
                                                    <p className="text-sm text-blue-800 font-medium mb-2">Créneaux actuellement sélectionnés :</p>
                                                    <div className="grid grid-cols-3 gap-2">
                                                        {selectedSlots
                                                            .slice()
                                                            .sort((a, b) => {
                                                                // Convert HH:MM to minutes for comparison
                                                                const timeA = a.split(':').reduce((acc, time, index) => acc + (index === 0 ? parseInt(time) * 60 : parseInt(time)), 0);
                                                                const timeB = b.split(':').reduce((acc, time, index) => acc + (index === 0 ? parseInt(time) * 60 : parseInt(time)), 0);
                                                                return timeA - timeB;
                                                            })
                                                            .map((slot, index) => (
                                                            <Badge
                                                                key={index}
                                                                variant="default"
                                                                className="justify-center bg-blue-600 text-white"
                                                            >
                                                                {slot}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
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