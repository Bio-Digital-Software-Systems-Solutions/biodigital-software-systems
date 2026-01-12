import { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Calendar, Clock, AlertCircle, CheckCircle } from 'lucide-react';
import { format, addMinutes, parse, parseISO, isAfter, startOfDay, isSameDay } from 'date-fns';
import { fr } from 'date-fns/locale';
import { toast } from 'sonner';
import axios from 'axios';

interface TimeSlot {
    start_datetime: string;
    end_datetime: string;
    formatted_time: string;
    available?: boolean;
    reason?: string;
}

interface User {
    id: number;
    uuid?: string;
    name: string;
    email: string;
}

interface TimeSlotPickerProps {
    selectedStartDateTime?: string;
    selectedEndDateTime?: string;
    onTimeSlotSelect: (startDateTime: string, endDateTime: string) => void;
    duration?: number; // Duration in minutes
    organizerId?: number; // For checking specific organizer's availability
    participants?: User[]; // For checking participants' availability
    errors?: {
        start_datetime?: string;
        end_datetime?: string;
    };
}

export default function TimeSlotPicker({
    selectedStartDateTime,
    selectedEndDateTime,
    onTimeSlotSelect,
    duration = 60,
    organizerId,
    participants = [],
    errors = {}
}: TimeSlotPickerProps) {
    const [selectedDate, setSelectedDate] = useState<string>('');
    const [availableSlots, setAvailableSlots] = useState<TimeSlot[]>([]);
    const [loading, setLoading] = useState(false);
    const [participantAppointments, setParticipantAppointments] = useState<any[]>([]);

    // Set initial date from selectedStartDateTime if provided
    useEffect(() => {
        if (selectedStartDateTime) {
            const date = new Date(selectedStartDateTime);
            setSelectedDate(format(date, 'yyyy-MM-dd'));
        } else {
            // Default to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            setSelectedDate(format(tomorrow, 'yyyy-MM-dd'));
        }
    }, [selectedStartDateTime]);

    // Fetch available slots when date changes
    useEffect(() => {
        if (selectedDate) {
            fetchAvailableSlots();
            fetchParticipantAppointments();
        }
    }, [selectedDate, duration, organizerId]);

    // Re-fetch when participants change
    useEffect(() => {
        if (selectedDate && participants.length > 0) {
            fetchParticipantAppointments();
        }
    }, [participants, selectedDate]);

    const fetchAvailableSlots = async () => {
        if (!selectedDate) return;

        setLoading(true);
        try {
            const params = new URLSearchParams({
                date: selectedDate,
                duration: duration.toString(),
            });

            if (organizerId) {
                params.append('organizer_id', organizerId.toString());
            }

            const response = await axios.get(`/api/appointments/available-slots?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.data.success) {
                setAvailableSlots(response.data.data.available_slots || []);
            } else {
                toast.error(response.data.message || 'Erreur lors du chargement des créneaux');
                setAvailableSlots([]);
            }
        } catch (error) {
            console.error('Error fetching available slots:', error);
            toast.error('Erreur lors du chargement des créneaux disponibles');
            setAvailableSlots([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchParticipantAppointments = async () => {
        if (!selectedDate || participants.length === 0) {
            setParticipantAppointments([]);
            return;
        }

        try {
            // Fetch public appointments for each participant using their UUIDs
            const promises = participants.map(async (participant) => {
                if (!participant.uuid) {
                    console.warn(`Participant ${participant.name} has no UUID`);
                    return null;
                }

                const response = await axios.get(`/api/users/${participant.uuid}/available-slots?date=${selectedDate}&duration=${duration}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                return response.data;
            });

            const results = await Promise.all(promises);
            setParticipantAppointments(results.filter(result => result !== null));
        } catch (error) {
            console.error('Error fetching participant appointments:', error);
        }
    };

    const handleDateChange = (date: string) => {
        const selectedDateObj = new Date(date);
        const today = startOfDay(new Date());

        if (selectedDateObj < today) {
            toast.error('Impossible de sélectionner une date passée');
            return;
        }

        setSelectedDate(date);
    };

    const handleSlotSelect = (slot: TimeSlot) => {
        if (!slot.available && slot.available !== undefined) {
            toast.error(slot.reason || 'Ce créneau n\'est pas disponible');
            return;
        }

        onTimeSlotSelect(slot.start_datetime, slot.end_datetime);
        toast.success(`Créneau sélectionné: ${slot.formatted_time}. Cliquez sur "Créer" pour valider.`);
    };

    const isSlotSelected = (slot: TimeSlot) => {
        if (!selectedStartDateTime || !selectedEndDateTime) return false;

        const slotStart = parseISO(slot.start_datetime);
        const slotEnd = parseISO(slot.end_datetime);
        const selectedStart = parseISO(selectedStartDateTime);
        const selectedEnd = parseISO(selectedEndDateTime);

        return slotStart.getTime() === selectedStart.getTime() &&
               slotEnd.getTime() === selectedEnd.getTime();
    };

    const isSlotConflictingWithParticipants = (slot: TimeSlot) => {
        const slotStart = parseISO(slot.start_datetime);
        const slotEnd = parseISO(slot.end_datetime);

        return participantAppointments.some(participantData => {
            if (!participantData.success || !participantData.data || !participantData.data.available_slots) return false;

            return participantData.data.available_slots.some((participantSlot: TimeSlot) => {
                if (participantSlot.available) return false; // No conflict if the slot is available

                const participantStart = parseISO(participantSlot.start_datetime);
                const participantEnd = parseISO(participantSlot.end_datetime);

                // Check for time overlap
                return slotStart < participantEnd && slotEnd > participantStart;
            });
        });
    };

    const getSlotStatus = (slot: TimeSlot) => {
        if (isSlotSelected(slot)) {
            return { status: 'selected', className: 'bg-blue-500 text-white border-blue-600', icon: <CheckCircle className="h-4 w-4" /> };
        }

        if (!slot.available && slot.available !== undefined) {
            return { status: 'unavailable', className: 'bg-red-100 text-red-600 border-red-300 cursor-not-allowed opacity-60', icon: <AlertCircle className="h-4 w-4" /> };
        }

        if (isSlotConflictingWithParticipants(slot)) {
            return { status: 'participant-conflict', className: 'bg-orange-100 text-orange-600 border-orange-300 cursor-not-allowed opacity-60', icon: <AlertCircle className="h-4 w-4" /> };
        }

        return { status: 'available', className: 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100', icon: <Clock className="h-4 w-4" /> };
    };

    const minDate = format(new Date(), 'yyyy-MM-dd');

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                    <Calendar className="h-5 w-5" />
                    <span>Sélection du créneau</span>
                </CardTitle>
                <CardDescription>
                    Choisissez une date puis sélectionnez un créneau disponible
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* Date Selection */}
                <div className="space-y-2">
                    <Label htmlFor="appointment-date">Date du rendez-vous *</Label>
                    <Input
                        id="appointment-date"
                        type="date"
                        value={selectedDate}
                        min={minDate}
                        onChange={(e) => handleDateChange(e.target.value)}
                        className={errors.start_datetime ? 'border-red-500' : ''}
                    />
                    {errors.start_datetime && (
                        <p className="text-sm text-red-600">{errors.start_datetime}</p>
                    )}
                </div>

                {/* Selected Date Display */}
                {selectedDate && (
                    <div className="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-md">
                        <p className="text-sm text-blue-700 dark:text-blue-400">
                            Date sélectionnée: <span className="font-medium">
                                {format(parse(selectedDate, 'yyyy-MM-dd', new Date()), 'EEEE d MMMM yyyy', { locale: fr })}
                            </span>
                        </p>
                    </div>
                )}

                {/* Time Slots */}
                {selectedDate && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <Label>Créneaux disponibles ({duration} minutes)</Label>
                            {loading && (
                                <div className="text-sm text-gray-500">Chargement...</div>
                            )}
                        </div>

                        {/* Legend */}
                        <div className="flex flex-wrap gap-4 text-xs">
                            <div className="flex items-center space-x-1">
                                <div className="w-3 h-3 bg-green-100 border border-green-200 rounded"></div>
                                <span>Disponible</span>
                            </div>
                            <div className="flex items-center space-x-1">
                                <div className="w-3 h-3 bg-red-100 border border-red-300 rounded"></div>
                                <span>Occupé</span>
                            </div>
                            <div className="flex items-center space-x-1">
                                <div className="w-3 h-3 bg-orange-100 border border-orange-300 rounded"></div>
                                <span>Conflit participant</span>
                            </div>
                            <div className="flex items-center space-x-1">
                                <div className="w-3 h-3 bg-blue-500 border border-blue-600 rounded"></div>
                                <span>Sélectionné</span>
                            </div>
                        </div>

                        {/* Slots Grid */}
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 max-h-96 overflow-y-auto">
                            {availableSlots.filter(slot => slot && slot.start_datetime && slot.end_datetime).map((slot, index) => {
                                const slotStatus = getSlotStatus(slot);
                                return (
                                    <Button
                                        key={`${slot.start_datetime}-${slot.end_datetime}`}
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleSlotSelect(slot)}
                                        disabled={slotStatus.status === 'unavailable' || slotStatus.status === 'participant-conflict'}
                                        className={`p-2 h-auto flex flex-col items-center justify-center text-xs ${slotStatus.className}`}
                                    >
                                        {slotStatus.icon}
                                        <span className="mt-1">{slot.formatted_time}</span>
                                        {slotStatus.status === 'unavailable' && slot.reason && (
                                            <span className="text-xs mt-1 opacity-70">{slot.reason}</span>
                                        )}
                                        {slotStatus.status === 'participant-conflict' && (
                                            <span className="text-xs mt-1 opacity-70">Conflit</span>
                                        )}
                                    </Button>
                                );
                            })}
                        </div>

                        {availableSlots.length === 0 && !loading && (
                            <div className="text-center py-8 text-gray-500">
                                <Calendar className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                <p>Aucun créneau disponible pour cette date</p>
                                <p className="text-sm">Essayez une autre date ou une durée différente</p>
                            </div>
                        )}
                    </div>
                )}

                {/* Selected Time Display */}
                {selectedStartDateTime && selectedEndDateTime && (
                    <div className="bg-green-50 dark:bg-green-900/20 p-3 rounded-md">
                        <p className="text-sm text-green-700 dark:text-green-400">
                            <CheckCircle className="h-4 w-4 inline mr-2" />
                            Créneau sélectionné: <span className="font-medium">
                                {format(parseISO(selectedStartDateTime), 'HH:mm')} - {format(parseISO(selectedEndDateTime), 'HH:mm')}
                            </span> le {format(parseISO(selectedStartDateTime), 'd MMMM yyyy', { locale: fr })}
                        </p>
                        <p className="text-xs text-green-600 dark:text-green-500 mt-1">
                            Durée: {Math.round((parseISO(selectedEndDateTime).getTime() - parseISO(selectedStartDateTime).getTime()) / (1000 * 60))} minutes
                        </p>
                    </div>
                )}

                {errors.end_datetime && (
                    <p className="text-sm text-red-600">{errors.end_datetime}</p>
                )}
            </CardContent>
        </Card>
    );
}