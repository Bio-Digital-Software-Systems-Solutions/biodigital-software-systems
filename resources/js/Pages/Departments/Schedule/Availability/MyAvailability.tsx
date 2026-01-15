import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import {
    ArrowLeftIcon,
    CalendarDaysIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface TimeSlot {
    start: string;
    end: string;
}

interface DayAvailability {
    available: boolean;
    slots: TimeSlot[];
    notes?: string;
}

interface WeekAvailability {
    [key: string]: DayAvailability;
}

interface Props {
    department: Department;
    currentAvailability: WeekAvailability;
    weekStart: string;
    weekEnd: string;
}

const DAYS_OF_WEEK = [
    { key: 'monday', label: 'Lundi' },
    { key: 'tuesday', label: 'Mardi' },
    { key: 'wednesday', label: 'Mercredi' },
    { key: 'thursday', label: 'Jeudi' },
    { key: 'friday', label: 'Vendredi' },
    { key: 'saturday', label: 'Samedi' },
    { key: 'sunday', label: 'Dimanche' },
];

const DEFAULT_SLOTS: TimeSlot[] = [
    { start: '08:00', end: '12:00' },
    { start: '14:00', end: '18:00' },
];

export default function MyAvailability({
    department,
    currentAvailability,
    weekStart,
    weekEnd,
}: Props) {
    const [availability, setAvailability] = useState<WeekAvailability>(() => {
        const initial: WeekAvailability = {};
        DAYS_OF_WEEK.forEach(day => {
            initial[day.key] = currentAvailability?.[day.key] || {
                available: true,
                slots: [...DEFAULT_SLOTS],
                notes: '',
            };
        });
        return initial;
    });

    const [processing, setProcessing] = useState(false);

    const handleDayToggle = (dayKey: string, checked: boolean) => {
        setAvailability(prev => ({
            ...prev,
            [dayKey]: {
                ...prev[dayKey],
                available: checked,
            },
        }));
    };

    const handleSlotChange = (dayKey: string, slotIndex: number, field: 'start' | 'end', value: string) => {
        setAvailability(prev => ({
            ...prev,
            [dayKey]: {
                ...prev[dayKey],
                slots: prev[dayKey].slots.map((slot, i) =>
                    i === slotIndex ? { ...slot, [field]: value } : slot
                ),
            },
        }));
    };

    const addSlot = (dayKey: string) => {
        setAvailability(prev => ({
            ...prev,
            [dayKey]: {
                ...prev[dayKey],
                slots: [...prev[dayKey].slots, { start: '09:00', end: '17:00' }],
            },
        }));
    };

    const removeSlot = (dayKey: string, slotIndex: number) => {
        setAvailability(prev => ({
            ...prev,
            [dayKey]: {
                ...prev[dayKey],
                slots: prev[dayKey].slots.filter((_, i) => i !== slotIndex),
            },
        }));
    };

    const handleNotesChange = (dayKey: string, notes: string) => {
        setAvailability(prev => ({
            ...prev,
            [dayKey]: {
                ...prev[dayKey],
                notes,
            },
        }));
    };

    const handleSubmit = () => {
        setProcessing(true);
        router.post(
            `/departments/${department.uuid}/availability/my`,
            { availability, week_start: weekStart } as any,
            {
                onSuccess: () => {
                    toast.success('Disponibilités enregistrées avec succès');
                },
                onError: () => {
                    toast.error('Erreur lors de l\'enregistrement');
                },
                onFinish: () => setProcessing(false),
            }
        );
    };

    const formatWeekDisplay = () => {
        const start = new Date(weekStart);
        const end = new Date(weekEnd);
        return `${start.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })} - ${end.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}`;
    };

    return (
        <DashboardLayout>
            <Head title={`Mes Disponibilités - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href={`/departments/${department.uuid}/schedule`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour au planning
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Mes Disponibilités
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                <CalendarDaysIcon className="h-4 w-4 inline mr-1" />
                                Semaine du {formatWeekDisplay()}
                            </p>
                        </div>
                        <Button onClick={handleSubmit} disabled={processing}>
                            {processing ? 'Enregistrement...' : 'Enregistrer'}
                        </Button>
                    </div>
                </div>

                {/* Info Card */}
                <Card className="mb-6 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                    <CardContent className="pt-4">
                        <div className="flex items-start gap-3">
                            <ExclamationTriangleIcon className="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                            <div className="text-sm text-blue-800 dark:text-blue-200">
                                <p className="font-medium">Indiquez vos disponibilités pour la semaine</p>
                                <p className="mt-1">
                                    Cochez les jours où vous êtes disponible et précisez vos créneaux horaires.
                                    Ces informations aideront le manager à établir le planning.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Days Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    {DAYS_OF_WEEK.map((day) => (
                        <Card key={day.key} className={!availability[day.key].available ? 'opacity-60' : ''}>
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-lg">{day.label}</CardTitle>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id={`available-${day.key}`}
                                            checked={availability[day.key].available}
                                            onCheckedChange={(checked) => handleDayToggle(day.key, checked === true)}
                                        />
                                        <Label htmlFor={`available-${day.key}`} className="text-sm cursor-pointer">
                                            Disponible
                                        </Label>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {availability[day.key].available ? (
                                    <>
                                        {/* Time Slots */}
                                        <div className="space-y-2">
                                            <Label className="text-xs text-gray-500 dark:text-gray-400">
                                                <ClockIcon className="h-3 w-3 inline mr-1" />
                                                Créneaux horaires
                                            </Label>
                                            {availability[day.key].slots.map((slot, index) => (
                                                <div key={index} className="flex items-center gap-2">
                                                    <Input
                                                        type="time"
                                                        value={slot.start}
                                                        onChange={(e) => handleSlotChange(day.key, index, 'start', e.target.value)}
                                                        className="w-24 h-8 text-sm"
                                                    />
                                                    <span className="text-gray-400">-</span>
                                                    <Input
                                                        type="time"
                                                        value={slot.end}
                                                        onChange={(e) => handleSlotChange(day.key, index, 'end', e.target.value)}
                                                        className="w-24 h-8 text-sm"
                                                    />
                                                    {availability[day.key].slots.length > 1 && (
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => removeSlot(day.key, index)}
                                                            className="h-8 w-8 p-0 text-red-500 hover:text-red-700"
                                                        >
                                                            <XCircleIcon className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            ))}
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => addSlot(day.key)}
                                                className="w-full text-xs"
                                            >
                                                + Ajouter un créneau
                                            </Button>
                                        </div>

                                        {/* Notes */}
                                        <div>
                                            <Label className="text-xs text-gray-500 dark:text-gray-400">
                                                Notes (optionnel)
                                            </Label>
                                            <Textarea
                                                value={availability[day.key].notes || ''}
                                                onChange={(e) => handleNotesChange(day.key, e.target.value)}
                                                placeholder="Ex: Préférence matin..."
                                                rows={2}
                                                className="mt-1 text-sm"
                                            />
                                        </div>
                                    </>
                                ) : (
                                    <div className="text-center py-4 text-gray-500 dark:text-gray-400">
                                        <XCircleIcon className="h-8 w-8 mx-auto mb-2" />
                                        <p className="text-sm">Non disponible ce jour</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Summary */}
                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle className="text-lg">Résumé</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-2">
                            {DAYS_OF_WEEK.map((day) => (
                                <Badge
                                    key={day.key}
                                    variant={availability[day.key].available ? 'default' : 'secondary'}
                                    className={availability[day.key].available ? 'bg-green-500' : ''}
                                >
                                    {availability[day.key].available ? (
                                        <CheckCircleIcon className="h-3 w-3 mr-1" />
                                    ) : (
                                        <XCircleIcon className="h-3 w-3 mr-1" />
                                    )}
                                    {day.label}
                                </Badge>
                            ))}
                        </div>
                        <p className="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            Vous êtes disponible {DAYS_OF_WEEK.filter(d => availability[d.key].available).length} jour(s) cette semaine.
                        </p>
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="mt-6 flex justify-end space-x-4">
                    <Link href={`/departments/${department.uuid}/schedule`}>
                        <Button variant="outline">Annuler</Button>
                    </Link>
                    <Button onClick={handleSubmit} disabled={processing}>
                        {processing ? 'Enregistrement...' : 'Enregistrer mes disponibilités'}
                    </Button>
                </div>
            </div>
        </DashboardLayout>
    );
}
