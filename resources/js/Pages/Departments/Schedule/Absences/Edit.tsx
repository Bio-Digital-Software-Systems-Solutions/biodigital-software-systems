import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Checkbox } from '@/Components/ui/checkbox';
import { Calendar } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/lib/utils';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import {
    ArrowLeftIcon,
    CalendarDaysIcon,
    DocumentArrowUpIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface AbsenceType {
    value: string;
    label: string;
    color: string;
    requiresApproval: boolean;
    deductsFromBalance: boolean;
}

interface LeaveBalance {
    leave_type: string;
    entitled_days: number;
    taken_days: number;
    pending_days: number;
    carried_over: number;
}

interface DepartmentMember {
    id: number;
    full_name: string;
}

interface InterimUser {
    id: number;
    full_name: string;
}

interface Absence {
    id: number;
    uuid: string;
    type: string;
    start_date: string;
    end_date: string;
    reason: string | null;
    is_half_day_start: boolean;
    interim_user_id: number | null;
    interim_notes: string | null;
    interim_user: InterimUser | null;
}

interface Props {
    department: Department;
    absence: Absence;
    balances: Record<string, LeaveBalance>;
    absenceTypes: AbsenceType[];
    departmentMembers: DepartmentMember[];
}

export default function EditAbsence({
    department,
    absence,
    balances,
    absenceTypes,
    departmentMembers,
}: Props) {
    // Format date from ISO to yyyy-MM-dd
    const formatDateForInput = (dateStr: string | null): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return format(date, 'yyyy-MM-dd');
    };

    // Get the type value (handle both string and enum object)
    const getTypeValue = (type: string | { value: string } | null): string => {
        if (!type) return '';
        if (typeof type === 'object' && 'value' in type) return type.value;
        return type;
    };

    const { data, setData, put, processing, errors } = useForm({
        type: getTypeValue(absence.type as unknown as string | { value: string }),
        start_date: formatDateForInput(absence.start_date),
        end_date: formatDateForInput(absence.end_date),
        reason: absence.reason || '',
        is_half_day: absence.is_half_day_start || false,
        half_day_period: '' as 'morning' | 'afternoon' | '',
        interim_user_id: absence.interim_user_id ? String(absence.interim_user_id) : '',
        interim_notes: absence.interim_notes || '',
        attachment: null as File | null,
    });

    const [startDateOpen, setStartDateOpen] = useState(false);
    const [endDateOpen, setEndDateOpen] = useState(false);

    const selectedType = absenceTypes.find(t => t.value === data.type);

    const getRemainingDays = (leaveType: string) => {
        const balance = balances[leaveType];
        if (!balance) return null;
        return balance.entitled_days + balance.carried_over - balance.taken_days - balance.pending_days;
    };

    const calculateDays = () => {
        if (!data.start_date || !data.end_date) return 0;
        if (data.is_half_day) return 0.5;

        const start = new Date(data.start_date);
        const end = new Date(data.end_date);
        const diffTime = Math.abs(end.getTime() - start.getTime());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        return diffDays;
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Debug: log the data being sent
        console.log('Submitting data:', {
            type: data.type,
            start_date: data.start_date,
            end_date: data.end_date,
        });

        put(`/departments/${department.uuid}/absences/${absence.uuid}`, {
            preserveScroll: true,
        });
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('attachment', file);
        }
    };

    const handleStartDateSelect = (date: Date | undefined) => {
        if (date) {
            setData('start_date', format(date, 'yyyy-MM-dd'));
            if (!data.end_date || new Date(data.end_date) < date) {
                setData('end_date', format(date, 'yyyy-MM-dd'));
            }
        }
        setStartDateOpen(false);
    };

    const handleEndDateSelect = (date: Date | undefined) => {
        if (date) {
            setData('end_date', format(date, 'yyyy-MM-dd'));
        }
        setEndDateOpen(false);
    };

    return (
        <DashboardLayout>
            <Head title={`Modifier la demande - ${department.name}`} />

            <div className="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href={`/departments/${department.uuid}/absences/my`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour a mes absences
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Modifier la demande d'absence
                    </h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {department.name}
                    </p>
                </div>

                <form onSubmit={handleSubmit}>
                    {/* Absence Type */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Type d'absence</CardTitle>
                            <CardDescription>
                                Selectionnez le type d'absence
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Select
                                value={data.type}
                                onValueChange={(value) => setData('type', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Selectionner un type">
                                        {selectedType?.label || data.type}
                                    </SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    {absenceTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            <div className="flex items-center gap-2">
                                                <span>{type.label}</span>
                                                {type.requiresApproval && (
                                                    <Badge variant="outline" className="text-xs">
                                                        Approbation requise
                                                    </Badge>
                                                )}
                                            </div>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.type && (
                                <p className="text-sm text-red-600 mt-1">{errors.type}</p>
                            )}

                            {/* Balance Info */}
                            {selectedType && selectedType.deductsFromBalance && (
                                <div className="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <div className="flex items-center gap-2">
                                        <InformationCircleIcon className="h-5 w-5 text-blue-600" />
                                        <span className="text-sm text-blue-800 dark:text-blue-200">
                                            Solde disponible: {getRemainingDays(data.type)?.toFixed(1) || '0'} jour(s)
                                        </span>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Dates */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Periode</CardTitle>
                            <CardDescription>
                                Selectionnez les dates de debut et de fin
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {/* Start Date */}
                                <div className="space-y-2">
                                    <Label>Date de debut *</Label>
                                    <Popover open={startDateOpen} onOpenChange={setStartDateOpen}>
                                        <PopoverTrigger asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className={cn(
                                                    'w-full justify-start text-left font-normal',
                                                    !data.start_date && 'text-muted-foreground'
                                                )}
                                            >
                                                <CalendarDaysIcon className="mr-2 h-4 w-4" />
                                                {data.start_date
                                                    ? format(new Date(data.start_date), 'PPP', { locale: fr })
                                                    : 'Selectionner une date'}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-auto p-0" align="start" side="top">
                                            <Calendar
                                                mode="single"
                                                selected={data.start_date ? new Date(data.start_date) : undefined}
                                                onSelect={handleStartDateSelect}
                                                disabled={(date) => date < new Date()}
                                                initialFocus
                                                locale={fr}
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    {errors.start_date && (
                                        <p className="text-sm text-red-600">{errors.start_date}</p>
                                    )}
                                </div>

                                {/* End Date */}
                                <div className="space-y-2">
                                    <Label>Date de fin *</Label>
                                    <Popover open={endDateOpen} onOpenChange={setEndDateOpen}>
                                        <PopoverTrigger asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className={cn(
                                                    'w-full justify-start text-left font-normal',
                                                    !data.end_date && 'text-muted-foreground'
                                                )}
                                            >
                                                <CalendarDaysIcon className="mr-2 h-4 w-4" />
                                                {data.end_date
                                                    ? format(new Date(data.end_date), 'PPP', { locale: fr })
                                                    : 'Selectionner une date'}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-auto p-0" align="start" side="top">
                                            <Calendar
                                                mode="single"
                                                selected={data.end_date ? new Date(data.end_date) : undefined}
                                                onSelect={handleEndDateSelect}
                                                disabled={(date) =>
                                                    date < new Date() ||
                                                    (data.start_date ? date < new Date(data.start_date) : false)
                                                }
                                                initialFocus
                                                locale={fr}
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    {errors.end_date && (
                                        <p className="text-sm text-red-600">{errors.end_date}</p>
                                    )}
                                </div>
                            </div>

                            {/* Half Day Option */}
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_half_day"
                                    checked={data.is_half_day}
                                    onCheckedChange={(checked) => {
                                        setData('is_half_day', checked === true);
                                        if (!checked) setData('half_day_period', '');
                                    }}
                                />
                                <Label htmlFor="is_half_day" className="cursor-pointer">
                                    Demi-journee
                                </Label>
                            </div>

                            {data.is_half_day && (
                                <div className="space-y-2">
                                    <Label>Periode</Label>
                                    <Select
                                        value={data.half_day_period}
                                        onValueChange={(value) => setData('half_day_period', value as 'morning' | 'afternoon')}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selectionner la periode" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="morning">Matin</SelectItem>
                                            <SelectItem value="afternoon">Apres-midi</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            {/* Days Count */}
                            {data.start_date && data.end_date && (
                                <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <span className="text-sm font-medium">
                                        Duree: {calculateDays()} jour(s)
                                    </span>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Interim User */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Interimaire</CardTitle>
                            <CardDescription>
                                Designez un collegue pour assurer l'interim pendant votre absence
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label>Collegue interimaire (optionnel)</Label>
                                <Select
                                    value={data.interim_user_id}
                                    onValueChange={(value) => setData('interim_user_id', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selectionner un collegue" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {departmentMembers?.map((member) => (
                                            <SelectItem key={member.id} value={String(member.id)}>
                                                {member.full_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.interim_user_id && (
                                    <p className="text-sm text-red-600">{errors.interim_user_id}</p>
                                )}
                            </div>

                            {data.interim_user_id && (
                                <div className="space-y-2">
                                    <Label htmlFor="interim_notes">Instructions pour l'interimaire</Label>
                                    <Textarea
                                        id="interim_notes"
                                        value={data.interim_notes}
                                        onChange={(e) => setData('interim_notes', e.target.value)}
                                        placeholder="Informations utiles pour votre interimaire..."
                                        rows={2}
                                    />
                                    {errors.interim_notes && (
                                        <p className="text-sm text-red-600">{errors.interim_notes}</p>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Reason and Attachment */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="reason">Motif (optionnel)</Label>
                                <Textarea
                                    id="reason"
                                    value={data.reason}
                                    onChange={(e) => setData('reason', e.target.value)}
                                    placeholder="Expliquez brievement le motif de votre absence..."
                                    rows={3}
                                />
                                {errors.reason && (
                                    <p className="text-sm text-red-600">{errors.reason}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="attachment">
                                    <DocumentArrowUpIcon className="h-4 w-4 inline mr-1" />
                                    Justificatif (optionnel)
                                </Label>
                                <Input
                                    id="attachment"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={handleFileChange}
                                />
                                <p className="text-xs text-gray-500">
                                    Formats acceptes: PDF, JPG, PNG (max 5MB)
                                </p>
                                {errors.attachment && (
                                    <p className="text-sm text-red-600">{errors.attachment}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Warning if balance insufficient */}
                    {selectedType && selectedType.deductsFromBalance && calculateDays() > 0 && (
                        <div className="mb-6">
                            {getRemainingDays(data.type) !== null && calculateDays() > (getRemainingDays(data.type) || 0) && (
                                <Card className="border-red-200 dark:border-red-800">
                                    <CardContent className="pt-4">
                                        <div className="flex items-start gap-3">
                                            <ExclamationTriangleIcon className="h-5 w-5 text-red-600 flex-shrink-0" />
                                            <div className="text-sm text-red-800 dark:text-red-200">
                                                <p className="font-medium">Solde insuffisant</p>
                                                <p>
                                                    Vous demandez {calculateDays()} jour(s) mais il vous reste seulement{' '}
                                                    {getRemainingDays(data.type)?.toFixed(1)} jour(s).
                                                </p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    )}

                    {/* Actions */}
                    <div className="flex justify-end space-x-4">
                        <Link href={`/departments/${department.uuid}/absences/my`}>
                            <Button type="button" variant="outline">
                                Annuler
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Enregistrement...' : 'Enregistrer les modifications'}
                        </Button>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
