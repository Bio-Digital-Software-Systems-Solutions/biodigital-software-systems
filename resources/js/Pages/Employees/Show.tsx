import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    UserIcon,
    BuildingOfficeIcon,
    PhoneIcon,
    EnvelopeIcon,
    MapPinIcon,
    CalendarDaysIcon,
    BanknotesIcon,
    ClockIcon,
    AcademicCapIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlayIcon,
} from '@heroicons/react/24/outline';

interface Employee {
    id: number;
    uuid: string;
    employee_number: string;
    full_name: string;
    position: string | null;
    job_title: string | null;
    birth_date: string | null;
    age: number | null;
    nationality: string | null;
    social_security_number: string | null;
    tax_id: string | null;
    personal_email: string | null;
    work_phone: string | null;
    personal_phone: string | null;
    address: string | null;
    city: string | null;
    postal_code: string | null;
    country: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
    status: {
        value: string;
        label: string;
        color: string;
    };
    employment_type: {
        value: string;
        label: string;
        color: string;
    };
    hire_date: string | null;
    probation_end_date: string | null;
    contract_end_date: string | null;
    termination_date: string | null;
    termination_reason: string | null;
    is_on_probation: boolean;
    years_of_service: number | null;
    remaining_probation_days: number | null;
    contract_remaining_days: number | null;
    hourly_rate: string | null;
    monthly_salary: string | null;
    payment_method: {
        value: string;
        label: string;
    } | null;
    bank_name: string | null;
    bank_iban: string | null;
    bank_bic: string | null;
    weekly_hours: string | null;
    working_days: string[] | null;
    default_start_time: string | null;
    default_end_time: string | null;
    annual_leave_days: number;
    remaining_leave_days: number;
    sick_days_taken: number;
    skills: string[] | null;
    certifications: string[] | null;
    languages: string[] | null;
    avatar: string | null;
    notes: string | null;
    internal_notes: string | null;
    created_at: string;
    updated_at: string;
    user: {
        id: number;
        uuid: string;
        name: string;
        email: string;
        avatar: string | null;
    } | null;
    department: {
        id: number;
        uuid: string;
        name: string;
    } | null;
    manager: {
        id: number;
        uuid: string;
        name: string;
        position: string | null;
    } | null;
    subordinates: Array<{
        id: number;
        uuid: string;
        name: string;
        position: string | null;
    }>;
}

interface Props {
    employee: Employee;
    canManage: boolean;
}

export default function EmployeeShow({ employee, canManage }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getStatusBadgeClass = (color: string) => {
        const colors: Record<string, string> = {
            green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            gray: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
            yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            red: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        };
        return colors[color] || colors.gray;
    };

    const handleDelete = () => {
        router.delete(`/employees/${employee.uuid}`, {
            onSuccess: () => setShowDeleteDialog(false),
        });
    };

    const handleTerminate = () => {
        router.post(`/employees/${employee.uuid}/terminate`, {
            termination_date: new Date().toISOString().split('T')[0],
            termination_reason: 'À spécifier',
        });
    };

    const handleActivate = () => {
        router.post(`/employees/${employee.uuid}/activate`);
    };

    const handleSetOnLeave = () => {
        router.post(`/employees/${employee.uuid}/on-leave`);
    };

    const formatDate = (date: string | null) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const formatCurrency = (amount: string | null) => {
        if (!amount) return '-';
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR',
        }).format(parseFloat(amount));
    };

    const dayLabels: Record<string, string> = {
        monday: 'Lun',
        tuesday: 'Mar',
        wednesday: 'Mer',
        thursday: 'Jeu',
        friday: 'Ven',
        saturday: 'Sam',
        sunday: 'Dim',
    };

    return (
        <DashboardLayout>
            <Head title={`${employee.full_name} - Employé`} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href="/employees"
                            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                        >
                            <ArrowLeftIcon className="h-4 w-4 mr-1" />
                            Retour
                        </Link>
                    </div>

                    {canManage && (
                        <div className="flex items-center gap-2">
                            {employee.status.value !== 'active' && (
                                <Button variant="outline" onClick={handleActivate}>
                                    <PlayIcon className="h-4 w-4 mr-2" />
                                    Activer
                                </Button>
                            )}
                            {employee.status.value === 'active' && (
                                <Button variant="outline" onClick={handleSetOnLeave}>
                                    <ClockIcon className="h-4 w-4 mr-2" />
                                    Mettre en congé
                                </Button>
                            )}
                            <Button variant="outline" asChild>
                                <Link href={`/employees/${employee.uuid}/edit`}>
                                    <PencilIcon className="h-4 w-4 mr-2" />
                                    Modifier
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                className="text-red-600 hover:bg-red-50"
                                onClick={() => setShowDeleteDialog(true)}
                            >
                                <TrashIcon className="h-4 w-4" />
                            </Button>
                        </div>
                    )}
                </div>

                {/* Profile Header */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-start gap-6">
                            <Avatar className="h-24 w-24">
                                {employee.avatar ? (
                                    <AvatarImage src={employee.avatar} />
                                ) : null}
                                <AvatarFallback className="text-2xl bg-blue-100 text-blue-600">
                                    {getInitials(employee.full_name)}
                                </AvatarFallback>
                            </Avatar>

                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <h1 className="text-2xl font-bold">{employee.full_name}</h1>
                                    <Badge className={getStatusBadgeClass(employee.status.color)}>
                                        {employee.status.label}
                                    </Badge>
                                </div>
                                <p className="text-gray-500 mb-2">
                                    {employee.position || employee.job_title || 'Position non définie'}
                                </p>
                                <div className="flex flex-wrap gap-4 text-sm">
                                    <span className="flex items-center gap-1 text-gray-500">
                                        <UserIcon className="h-4 w-4" />
                                        {employee.employee_number}
                                    </span>
                                    {employee.department && (
                                        <span className="flex items-center gap-1 text-gray-500">
                                            <BuildingOfficeIcon className="h-4 w-4" />
                                            {employee.department.name}
                                        </span>
                                    )}
                                    {employee.user?.email && (
                                        <span className="flex items-center gap-1 text-gray-500">
                                            <EnvelopeIcon className="h-4 w-4" />
                                            {employee.user.email}
                                        </span>
                                    )}
                                    {employee.hire_date && (
                                        <span className="flex items-center gap-1 text-gray-500">
                                            <CalendarDaysIcon className="h-4 w-4" />
                                            Depuis {formatDate(employee.hire_date)}
                                        </span>
                                    )}
                                </div>
                            </div>

                            {/* Quick Stats */}
                            <div className="grid grid-cols-3 gap-4 text-center">
                                <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p className="text-2xl font-bold text-blue-600">
                                        {employee.years_of_service ?? 0}
                                    </p>
                                    <p className="text-xs text-gray-500">Années</p>
                                </div>
                                <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p className="text-2xl font-bold text-green-600">
                                        {employee.remaining_leave_days}
                                    </p>
                                    <p className="text-xs text-gray-500">Congés restants</p>
                                </div>
                                <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p className="text-2xl font-bold text-orange-600">
                                        {employee.sick_days_taken}
                                    </p>
                                    <p className="text-xs text-gray-500">Jours maladie</p>
                                </div>
                            </div>
                        </div>

                        {/* Alerts */}
                        {employee.is_on_probation && (
                            <div className="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg flex items-center gap-2">
                                <ExclamationTriangleIcon className="h-5 w-5 text-yellow-600" />
                                <span className="text-sm text-yellow-800 dark:text-yellow-400">
                                    Période d'essai en cours - {employee.remaining_probation_days} jours restants
                                </span>
                            </div>
                        )}
                        {employee.contract_remaining_days !== null && employee.contract_remaining_days <= 30 && (
                            <div className="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg flex items-center gap-2">
                                <ExclamationTriangleIcon className="h-5 w-5 text-red-600" />
                                <span className="text-sm text-red-800 dark:text-red-400">
                                    Contrat se terminant dans {employee.contract_remaining_days} jours
                                </span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Tabs */}
                <Tabs defaultValue="info">
                    <TabsList>
                        <TabsTrigger value="info">Informations</TabsTrigger>
                        <TabsTrigger value="employment">Emploi</TabsTrigger>
                        <TabsTrigger value="compensation">Rémunération</TabsTrigger>
                        <TabsTrigger value="skills">Compétences</TabsTrigger>
                        <TabsTrigger value="team">Équipe</TabsTrigger>
                    </TabsList>

                    <TabsContent value="info" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Personal Info */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Informations personnelles</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Date de naissance</span>
                                        <span>{formatDate(employee.birth_date)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Âge</span>
                                        <span>{employee.age ? `${employee.age} ans` : '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Nationalité</span>
                                        <span>{employee.nationality || '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">N° Sécurité sociale</span>
                                        <span>{employee.social_security_number || '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">N° Fiscal</span>
                                        <span>{employee.tax_id || '-'}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Contact Info */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Contact</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Email personnel</span>
                                        <span>{employee.personal_email || '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Tél. professionnel</span>
                                        <span>{employee.work_phone || '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Tél. personnel</span>
                                        <span>{employee.personal_phone || '-'}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Address */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Adresse</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {employee.address ? (
                                        <div className="flex items-start gap-2">
                                            <MapPinIcon className="h-5 w-5 text-gray-400 mt-0.5" />
                                            <div>
                                                <p>{employee.address}</p>
                                                <p>{employee.postal_code} {employee.city}</p>
                                                <p>{employee.country}</p>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Non renseignée</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Emergency Contact */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Contact d'urgence</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Nom</span>
                                        <span>{employee.emergency_contact_name || '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Téléphone</span>
                                        <span>{employee.emergency_contact_phone || '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Relation</span>
                                        <span>{employee.emergency_contact_relationship || '-'}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="employment" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Employment Details */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Détails de l'emploi</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Type de contrat</span>
                                        <Badge>{employee.employment_type.label}</Badge>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Date d'embauche</span>
                                        <span>{formatDate(employee.hire_date)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Fin période d'essai</span>
                                        <span>{formatDate(employee.probation_end_date)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Fin de contrat</span>
                                        <span>{formatDate(employee.contract_end_date)}</span>
                                    </div>
                                    {employee.termination_date && (
                                        <>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Date de fin</span>
                                                <span className="text-red-600">{formatDate(employee.termination_date)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Raison</span>
                                                <span>{employee.termination_reason || '-'}</span>
                                            </div>
                                        </>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Work Schedule */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Horaires de travail</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Heures/semaine</span>
                                        <span>{employee.weekly_hours}h</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Horaires par défaut</span>
                                        <span>
                                            {employee.default_start_time && employee.default_end_time
                                                ? `${employee.default_start_time} - ${employee.default_end_time}`
                                                : '-'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-gray-500 block mb-2">Jours travaillés</span>
                                        <div className="flex gap-2">
                                            {['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'].map(
                                                (day) => (
                                                    <span
                                                        key={day}
                                                        className={`px-2 py-1 text-xs rounded ${
                                                            employee.working_days?.includes(day)
                                                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                                                                : 'bg-gray-100 text-gray-400'
                                                        }`}
                                                    >
                                                        {dayLabels[day]}
                                                    </span>
                                                )
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Leave Balance */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Solde de congés</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Congés annuels</span>
                                        <span>{employee.annual_leave_days} jours</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Restants</span>
                                        <span className="text-green-600 font-medium">
                                            {employee.remaining_leave_days} jours
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Jours maladie pris</span>
                                        <span>{employee.sick_days_taken} jours</span>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                        <div
                                            className="bg-blue-600 h-2.5 rounded-full"
                                            style={{
                                                width: `${
                                                    (employee.remaining_leave_days / employee.annual_leave_days) * 100
                                                }%`,
                                            }}
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="compensation" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Salary */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Rémunération</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Salaire mensuel</span>
                                        <span className="font-medium">{formatCurrency(employee.monthly_salary)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Taux horaire</span>
                                        <span>{formatCurrency(employee.hourly_rate)}/h</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Mode de paiement</span>
                                        <span>{employee.payment_method?.label || '-'}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Bank Details */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Coordonnées bancaires</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Banque</span>
                                        <span>{employee.bank_name || '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">IBAN</span>
                                        <span className="font-mono text-sm">{employee.bank_iban || '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">BIC</span>
                                        <span className="font-mono">{employee.bank_bic || '-'}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="skills" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Skills */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Compétences</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {employee.skills && employee.skills.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {employee.skills.map((skill, index) => (
                                                <Badge key={index} variant="secondary">
                                                    {skill}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Aucune compétence enregistrée</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Languages */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Langues</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {employee.languages && employee.languages.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {employee.languages.map((lang, index) => (
                                                <Badge key={index} variant="outline">
                                                    {lang}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Aucune langue enregistrée</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Certifications */}
                            <Card className="md:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-lg">Certifications</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {employee.certifications && employee.certifications.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {employee.certifications.map((cert, index) => (
                                                <Badge key={index} className="bg-green-100 text-green-800">
                                                    <AcademicCapIcon className="h-3 w-3 mr-1" />
                                                    {cert}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Aucune certification enregistrée</p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="team" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Manager */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Responsable hiérarchique</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {employee.manager ? (
                                        <Link
                                            href={`/employees/${employee.manager.uuid}`}
                                            className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            <Avatar>
                                                <AvatarFallback>
                                                    {getInitials(employee.manager.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p className="font-medium">{employee.manager.name}</p>
                                                <p className="text-sm text-gray-500">{employee.manager.position}</p>
                                            </div>
                                        </Link>
                                    ) : (
                                        <p className="text-gray-500">Aucun responsable assigné</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Subordinates */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">
                                        Équipe directe ({employee.subordinates.length})
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {employee.subordinates.length > 0 ? (
                                        <div className="space-y-2">
                                            {employee.subordinates.map((sub) => (
                                                <Link
                                                    key={sub.uuid}
                                                    href={`/employees/${sub.uuid}`}
                                                    className="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800"
                                                >
                                                    <Avatar className="h-8 w-8">
                                                        <AvatarFallback className="text-xs">
                                                            {getInitials(sub.name)}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <p className="text-sm font-medium">{sub.name}</p>
                                                        <p className="text-xs text-gray-500">{sub.position}</p>
                                                    </div>
                                                </Link>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Aucun membre dans l'équipe</p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>
                </Tabs>

                {/* Notes */}
                {(employee.notes || employee.internal_notes) && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {employee.notes && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Notes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap">{employee.notes}</p>
                                </CardContent>
                            </Card>
                        )}
                        {employee.internal_notes && canManage && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Notes internes</CardTitle>
                                    <CardDescription>Visible uniquement par les gestionnaires</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap">{employee.internal_notes}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}
            </div>

            <DeleteConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
                onConfirm={handleDelete}
                title="Supprimer cet employé ?"
                description="Cette action est irréversible. Toutes les données de l'employé seront supprimées."
            />
        </DashboardLayout>
    );
}
