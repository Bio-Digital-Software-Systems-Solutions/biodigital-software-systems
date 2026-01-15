import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import {
    ArrowLeftIcon,
    ArrowsRightLeftIcon,
    CalendarDaysIcon,
    UserIcon,
    CheckIcon,
    ClockIcon,
} from '@heroicons/react/24/outline';
import { toast } from 'sonner';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface User {
    id: number;
    first_name: string;
    last_name: string;
    full_name?: string;
    name?: string;
}

interface Shift {
    id: number;
    uuid: string;
    date: string;
    start_time: string;
    end_time: string;
    type: string;
    title: string | null;
    user?: User;
}

interface Props {
    department: Department;
    availableShifts: Shift[];
    myShifts: Shift[];
}

export default function CreateSwapRequest({ department, availableShifts, myShifts }: Props) {
    const [selectedShift, setSelectedShift] = useState<Shift | null>(null);
    const [offeredShift, setOfferedShift] = useState<Shift | null>(null);
    const [reason, setReason] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        });
    };

    const formatTime = (time: string) => {
        return time.slice(0, 5);
    };

    const getUserName = (user: User | null | undefined) => {
        if (!user) return 'Non assigné';
        if (user.full_name) return user.full_name;
        if (user.first_name && user.last_name) return `${user.first_name} ${user.last_name}`;
        return user.name || 'Utilisateur';
    };

    const getShiftTypeLabel = (type: string) => {
        const labels: Record<string, string> = {
            morning: 'Matin',
            afternoon: 'Après-midi',
            evening: 'Soir',
            night: 'Nuit',
            full_day: 'Journée',
            split: 'Coupure',
            on_call: 'Astreinte',
            custom: 'Personnalisé',
        };
        return labels[type] || type;
    };

    const handleSubmit = () => {
        if (!selectedShift) {
            toast.error('Veuillez sélectionner un shift à demander');
            return;
        }

        setIsSubmitting(true);
        router.post(
            `/departments/${department.uuid}/swap-requests`,
            {
                requested_shift_id: selectedShift.id,
                offered_shift_id: offeredShift?.id || null,
                reason: reason || null,
            },
            {
                onSuccess: () => {
                    toast.success('Demande d\'échange envoyée avec succès');
                },
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat().join(', ');
                    toast.error(errorMessage || 'Erreur lors de l\'envoi de la demande');
                },
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Group shifts by date
    const groupShiftsByDate = (shifts: Shift[]) => {
        return shifts.reduce((groups, shift) => {
            const date = shift.date;
            if (!groups[date]) {
                groups[date] = [];
            }
            groups[date].push(shift);
            return groups;
        }, {} as Record<string, Shift[]>);
    };

    const availableByDate = groupShiftsByDate(availableShifts);
    const myShiftsByDate = groupShiftsByDate(myShifts);

    return (
        <DashboardLayout>
            <Head title={`Nouvelle demande d'échange - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href={`/departments/${department.uuid}/swap-requests/my`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour à mes échanges
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Nouvelle demande d'échange
                    </h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {department.name}
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Step 1: Select shift to request */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <span className="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-sm font-bold">1</span>
                                Shift à demander
                            </CardTitle>
                            <CardDescription>
                                Sélectionnez le shift que vous souhaitez obtenir
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="max-h-96 overflow-y-auto">
                            {availableShifts.length === 0 ? (
                                <div className="text-center py-8 text-gray-500">
                                    <CalendarDaysIcon className="h-12 w-12 mx-auto mb-3 text-gray-300" />
                                    <p>Aucun shift disponible pour l'échange</p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {Object.entries(availableByDate).map(([date, shifts]) => (
                                        <div key={date}>
                                            <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                {formatDate(date)}
                                            </h4>
                                            <div className="space-y-2">
                                                {shifts.map((shift) => (
                                                    <button
                                                        key={shift.id}
                                                        type="button"
                                                        onClick={() => setSelectedShift(shift)}
                                                        className={`w-full p-3 rounded-lg border-2 text-left transition-all ${
                                                            selectedShift?.id === shift.id
                                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                                        }`}
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <div className="flex items-center gap-3">
                                                                <ClockIcon className="h-5 w-5 text-gray-400" />
                                                                <div>
                                                                    <p className="font-medium">
                                                                        {formatTime(shift.start_time)} - {formatTime(shift.end_time)}
                                                                    </p>
                                                                    <p className="text-sm text-gray-500">
                                                                        {getShiftTypeLabel(shift.type)}
                                                                        {shift.title && ` - ${shift.title}`}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div className="flex items-center gap-2">
                                                                <Badge variant="outline" className="text-xs">
                                                                    <UserIcon className="h-3 w-3 mr-1" />
                                                                    {getUserName(shift.user)}
                                                                </Badge>
                                                                {selectedShift?.id === shift.id && (
                                                                    <CheckIcon className="h-5 w-5 text-blue-500" />
                                                                )}
                                                            </div>
                                                        </div>
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Step 2: Offer a shift (optional) */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <span className="flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-600 text-sm font-bold">2</span>
                                Shift à offrir (optionnel)
                            </CardTitle>
                            <CardDescription>
                                Proposez un de vos shifts en échange
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="max-h-96 overflow-y-auto">
                            {myShifts.length === 0 ? (
                                <div className="text-center py-8 text-gray-500">
                                    <CalendarDaysIcon className="h-12 w-12 mx-auto mb-3 text-gray-300" />
                                    <p>Vous n'avez pas de shifts à offrir</p>
                                    <p className="text-sm mt-1">Vous pouvez quand même faire une demande simple</p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    <button
                                        type="button"
                                        onClick={() => setOfferedShift(null)}
                                        className={`w-full p-3 rounded-lg border-2 text-left transition-all ${
                                            offeredShift === null
                                                ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <ArrowsRightLeftIcon className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <p className="font-medium">Échange simple</p>
                                                    <p className="text-sm text-gray-500">Sans offrir de shift en retour</p>
                                                </div>
                                            </div>
                                            {offeredShift === null && (
                                                <CheckIcon className="h-5 w-5 text-green-500" />
                                            )}
                                        </div>
                                    </button>

                                    {Object.entries(myShiftsByDate).map(([date, shifts]) => (
                                        <div key={date}>
                                            <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                {formatDate(date)}
                                            </h4>
                                            <div className="space-y-2">
                                                {shifts.map((shift) => (
                                                    <button
                                                        key={shift.id}
                                                        type="button"
                                                        onClick={() => setOfferedShift(shift)}
                                                        className={`w-full p-3 rounded-lg border-2 text-left transition-all ${
                                                            offeredShift?.id === shift.id
                                                                ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                                        }`}
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <div className="flex items-center gap-3">
                                                                <ClockIcon className="h-5 w-5 text-gray-400" />
                                                                <div>
                                                                    <p className="font-medium">
                                                                        {formatTime(shift.start_time)} - {formatTime(shift.end_time)}
                                                                    </p>
                                                                    <p className="text-sm text-gray-500">
                                                                        {getShiftTypeLabel(shift.type)}
                                                                        {shift.title && ` - ${shift.title}`}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            {offeredShift?.id === shift.id && (
                                                                <CheckIcon className="h-5 w-5 text-green-500" />
                                                            )}
                                                        </div>
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Reason and Submit */}
                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle className="text-lg flex items-center gap-2">
                            <span className="flex items-center justify-center w-6 h-6 rounded-full bg-purple-100 text-purple-600 text-sm font-bold">3</span>
                            Motif de la demande
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="reason">Motif (optionnel)</Label>
                                <Textarea
                                    id="reason"
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    placeholder="Expliquez pourquoi vous souhaitez cet échange..."
                                    className="mt-1"
                                    rows={3}
                                />
                            </div>

                            {/* Summary */}
                            {selectedShift && (
                                <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <h4 className="font-medium mb-3">Résumé de la demande</h4>
                                    <div className="flex items-center gap-4">
                                        <div className="flex-1 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                            <p className="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">
                                                Shift demandé
                                            </p>
                                            <p className="font-medium">{formatDate(selectedShift.date)}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {formatTime(selectedShift.start_time)} - {formatTime(selectedShift.end_time)}
                                            </p>
                                            <p className="text-sm text-gray-500 mt-1">
                                                Assigné à: {getUserName(selectedShift.user)}
                                            </p>
                                        </div>

                                        <ArrowsRightLeftIcon className="h-6 w-6 text-gray-400 flex-shrink-0" />

                                        <div className="flex-1 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                            <p className="text-xs text-green-600 dark:text-green-400 font-medium mb-1">
                                                {offeredShift ? 'Shift offert' : 'Échange simple'}
                                            </p>
                                            {offeredShift ? (
                                                <>
                                                    <p className="font-medium">{formatDate(offeredShift.date)}</p>
                                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                                        {formatTime(offeredShift.start_time)} - {formatTime(offeredShift.end_time)}
                                                    </p>
                                                </>
                                            ) : (
                                                <p className="text-sm text-gray-500">
                                                    Pas de shift offert en échange
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="flex justify-end gap-4">
                                <Link href={`/departments/${department.uuid}/swap-requests/my`}>
                                    <Button variant="outline">Annuler</Button>
                                </Link>
                                <Button
                                    onClick={handleSubmit}
                                    disabled={!selectedShift || isSubmitting}
                                    className="bg-blue-600 hover:bg-blue-700"
                                >
                                    {isSubmitting ? 'Envoi en cours...' : 'Envoyer la demande'}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
