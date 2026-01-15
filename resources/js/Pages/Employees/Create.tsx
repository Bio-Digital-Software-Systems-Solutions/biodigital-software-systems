import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Checkbox } from '@/Components/ui/checkbox';
import { ArrowLeftIcon, PlusIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';

interface User {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
    email: string;
}

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Manager {
    id: number;
    uuid: string;
    name: string;
    position: string | null;
}

interface SelectOption {
    value: string;
    label: string;
}

interface Props {
    users: User[];
    departments: Department[];
    managers: Manager[];
    statuses: SelectOption[];
    employmentTypes: SelectOption[];
    paymentMethods: SelectOption[];
}

const DAYS_OF_WEEK = [
    { value: 'monday', label: 'Lundi' },
    { value: 'tuesday', label: 'Mardi' },
    { value: 'wednesday', label: 'Mercredi' },
    { value: 'thursday', label: 'Jeudi' },
    { value: 'friday', label: 'Vendredi' },
    { value: 'saturday', label: 'Samedi' },
    { value: 'sunday', label: 'Dimanche' },
];

export default function EmployeeCreate({
    users,
    departments,
    managers,
    statuses,
    employmentTypes,
    paymentMethods,
}: Props) {
    const [newSkill, setNewSkill] = useState('');
    const [newLanguage, setNewLanguage] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        department_id: '',
        manager_id: '',
        position: '',
        job_title: '',
        birth_date: '',
        nationality: '',
        social_security_number: '',
        tax_id: '',
        personal_email: '',
        work_phone: '',
        personal_phone: '',
        address: '',
        city: '',
        postal_code: '',
        country: 'Germany',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        emergency_contact_relationship: '',
        status: 'active',
        employment_type: 'full_time',
        hire_date: new Date().toISOString().split('T')[0],
        probation_end_date: '',
        contract_end_date: '',
        hourly_rate: '',
        monthly_salary: '',
        payment_method: 'bank_transfer',
        bank_name: '',
        bank_iban: '',
        bank_bic: '',
        weekly_hours: '40',
        working_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as string[],
        default_start_time: '09:00',
        default_end_time: '17:00',
        annual_leave_days: '30',
        skills: [] as string[],
        languages: ['German', 'English'] as string[],
        notes: '',
        internal_notes: '',
        avatar: null as File | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/employees', {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Employé créé avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la création');
            },
        });
    };

    const addSkill = () => {
        if (newSkill.trim() && !data.skills.includes(newSkill.trim())) {
            setData('skills', [...data.skills, newSkill.trim()]);
            setNewSkill('');
        }
    };

    const removeSkill = (skill: string) => {
        setData('skills', data.skills.filter((s) => s !== skill));
    };

    const addLanguage = () => {
        if (newLanguage.trim() && !data.languages.includes(newLanguage.trim())) {
            setData('languages', [...data.languages, newLanguage.trim()]);
            setNewLanguage('');
        }
    };

    const removeLanguage = (lang: string) => {
        setData('languages', data.languages.filter((l) => l !== lang));
    };

    const toggleWorkingDay = (day: string) => {
        if (data.working_days.includes(day)) {
            setData('working_days', data.working_days.filter((d) => d !== day));
        } else {
            setData('working_days', [...data.working_days, day]);
        }
    };

    return (
        <DashboardLayout>
            <Head title="Nouvel Employé" />

            <div className="p-6 max-w-5xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href="/employees"
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour à la liste
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Nouvel Employé
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Créez un nouveau dossier employé
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* User Selection */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Utilisateur</CardTitle>
                            <CardDescription>
                                Sélectionnez l'utilisateur à associer à ce dossier employé
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="user_id">Utilisateur *</Label>
                                    <Select
                                        value={data.user_id}
                                        onValueChange={(value) => setData('user_id', value)}
                                    >
                                        <SelectTrigger className={errors.user_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Sélectionner un utilisateur" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {users.map((user) => (
                                                <SelectItem key={user.id} value={user.id.toString()}>
                                                    {user.first_name} {user.last_name} ({user.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.user_id && (
                                        <p className="text-red-500 text-sm mt-1">{errors.user_id}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Position & Department */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Poste et Département</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="position">Position</Label>
                                    <Input
                                        id="position"
                                        value={data.position}
                                        onChange={(e) => setData('position', e.target.value)}
                                        placeholder="Ex: Développeur, Manager..."
                                        className={errors.position ? 'border-red-500' : ''}
                                    />
                                    {errors.position && (
                                        <p className="text-red-500 text-sm mt-1">{errors.position}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="job_title">Titre du poste</Label>
                                    <Input
                                        id="job_title"
                                        value={data.job_title}
                                        onChange={(e) => setData('job_title', e.target.value)}
                                        placeholder="Ex: Senior Developer..."
                                        className={errors.job_title ? 'border-red-500' : ''}
                                    />
                                    {errors.job_title && (
                                        <p className="text-red-500 text-sm mt-1">{errors.job_title}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="department_id">Département</Label>
                                    <Select
                                        value={data.department_id}
                                        onValueChange={(value) => setData('department_id', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner un département" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Aucun</SelectItem>
                                            {departments.map((dept) => (
                                                <SelectItem key={dept.id} value={dept.id.toString()}>
                                                    {dept.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="manager_id">Manager</Label>
                                    <Select
                                        value={data.manager_id}
                                        onValueChange={(value) => setData('manager_id', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner un manager" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Aucun</SelectItem>
                                            {managers.map((manager) => (
                                                <SelectItem key={manager.id} value={manager.id.toString()}>
                                                    {manager.name} {manager.position && `(${manager.position})`}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Personal Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Informations Personnelles</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="birth_date">Date de naissance</Label>
                                    <Input
                                        id="birth_date"
                                        type="date"
                                        value={data.birth_date}
                                        onChange={(e) => setData('birth_date', e.target.value)}
                                        className={errors.birth_date ? 'border-red-500' : ''}
                                    />
                                    {errors.birth_date && (
                                        <p className="text-red-500 text-sm mt-1">{errors.birth_date}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="nationality">Nationalité</Label>
                                    <Input
                                        id="nationality"
                                        value={data.nationality}
                                        onChange={(e) => setData('nationality', e.target.value)}
                                        placeholder="Ex: German, French..."
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="social_security_number">N° Sécurité sociale</Label>
                                    <Input
                                        id="social_security_number"
                                        value={data.social_security_number}
                                        onChange={(e) => setData('social_security_number', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="tax_id">N° Fiscal</Label>
                                    <Input
                                        id="tax_id"
                                        value={data.tax_id}
                                        onChange={(e) => setData('tax_id', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="personal_email">Email personnel</Label>
                                    <Input
                                        id="personal_email"
                                        type="email"
                                        value={data.personal_email}
                                        onChange={(e) => setData('personal_email', e.target.value)}
                                        className={errors.personal_email ? 'border-red-500' : ''}
                                    />
                                    {errors.personal_email && (
                                        <p className="text-red-500 text-sm mt-1">{errors.personal_email}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="work_phone">Tél. professionnel</Label>
                                    <Input
                                        id="work_phone"
                                        value={data.work_phone}
                                        onChange={(e) => setData('work_phone', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="personal_phone">Tél. personnel</Label>
                                    <Input
                                        id="personal_phone"
                                        value={data.personal_phone}
                                        onChange={(e) => setData('personal_phone', e.target.value)}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Address */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Adresse</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="md:col-span-2">
                                    <Label htmlFor="address">Adresse</Label>
                                    <Input
                                        id="address"
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        placeholder="Rue et numéro"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="postal_code">Code postal</Label>
                                    <Input
                                        id="postal_code"
                                        value={data.postal_code}
                                        onChange={(e) => setData('postal_code', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="city">Ville</Label>
                                    <Input
                                        id="city"
                                        value={data.city}
                                        onChange={(e) => setData('city', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="country">Pays</Label>
                                    <Input
                                        id="country"
                                        value={data.country}
                                        onChange={(e) => setData('country', e.target.value)}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Emergency Contact */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Contact d'Urgence</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="emergency_contact_name">Nom</Label>
                                    <Input
                                        id="emergency_contact_name"
                                        value={data.emergency_contact_name}
                                        onChange={(e) => setData('emergency_contact_name', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="emergency_contact_phone">Téléphone</Label>
                                    <Input
                                        id="emergency_contact_phone"
                                        value={data.emergency_contact_phone}
                                        onChange={(e) => setData('emergency_contact_phone', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="emergency_contact_relationship">Relation</Label>
                                    <Input
                                        id="emergency_contact_relationship"
                                        value={data.emergency_contact_relationship}
                                        onChange={(e) => setData('emergency_contact_relationship', e.target.value)}
                                        placeholder="Ex: Conjoint, Parent..."
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Employment Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Détails de l'Emploi</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="status">Statut *</Label>
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) => setData('status', value)}
                                    >
                                        <SelectTrigger className={errors.status ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Sélectionner un statut" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statuses.map((status) => (
                                                <SelectItem key={status.value} value={status.value}>
                                                    {status.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.status && (
                                        <p className="text-red-500 text-sm mt-1">{errors.status}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="employment_type">Type de contrat *</Label>
                                    <Select
                                        value={data.employment_type}
                                        onValueChange={(value) => setData('employment_type', value)}
                                    >
                                        <SelectTrigger className={errors.employment_type ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Sélectionner un type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {employmentTypes.map((type) => (
                                                <SelectItem key={type.value} value={type.value}>
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.employment_type && (
                                        <p className="text-red-500 text-sm mt-1">{errors.employment_type}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="hire_date">Date d'embauche</Label>
                                    <Input
                                        id="hire_date"
                                        type="date"
                                        value={data.hire_date}
                                        onChange={(e) => setData('hire_date', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="probation_end_date">Fin période d'essai</Label>
                                    <Input
                                        id="probation_end_date"
                                        type="date"
                                        value={data.probation_end_date}
                                        onChange={(e) => setData('probation_end_date', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="contract_end_date">Fin de contrat</Label>
                                    <Input
                                        id="contract_end_date"
                                        type="date"
                                        value={data.contract_end_date}
                                        onChange={(e) => setData('contract_end_date', e.target.value)}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Work Schedule */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Horaires de Travail</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <Label htmlFor="weekly_hours">Heures/semaine</Label>
                                        <Input
                                            id="weekly_hours"
                                            type="number"
                                            step="0.5"
                                            value={data.weekly_hours}
                                            onChange={(e) => setData('weekly_hours', e.target.value)}
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="default_start_time">Heure de début</Label>
                                        <Input
                                            id="default_start_time"
                                            type="time"
                                            value={data.default_start_time}
                                            onChange={(e) => setData('default_start_time', e.target.value)}
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="default_end_time">Heure de fin</Label>
                                        <Input
                                            id="default_end_time"
                                            type="time"
                                            value={data.default_end_time}
                                            onChange={(e) => setData('default_end_time', e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div>
                                    <Label className="mb-2 block">Jours travaillés</Label>
                                    <div className="flex flex-wrap gap-4">
                                        {DAYS_OF_WEEK.map((day) => (
                                            <div key={day.value} className="flex items-center space-x-2">
                                                <Checkbox
                                                    id={day.value}
                                                    checked={data.working_days.includes(day.value)}
                                                    onCheckedChange={() => toggleWorkingDay(day.value)}
                                                />
                                                <Label htmlFor={day.value} className="cursor-pointer">
                                                    {day.label}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div>
                                    <Label htmlFor="annual_leave_days">Congés annuels (jours)</Label>
                                    <Input
                                        id="annual_leave_days"
                                        type="number"
                                        value={data.annual_leave_days}
                                        onChange={(e) => setData('annual_leave_days', e.target.value)}
                                        className="w-32"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Compensation */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Rémunération</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="monthly_salary">Salaire mensuel (€)</Label>
                                    <Input
                                        id="monthly_salary"
                                        type="number"
                                        step="0.01"
                                        value={data.monthly_salary}
                                        onChange={(e) => setData('monthly_salary', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="hourly_rate">Taux horaire (€)</Label>
                                    <Input
                                        id="hourly_rate"
                                        type="number"
                                        step="0.01"
                                        value={data.hourly_rate}
                                        onChange={(e) => setData('hourly_rate', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="payment_method">Mode de paiement</Label>
                                    <Select
                                        value={data.payment_method}
                                        onValueChange={(value) => setData('payment_method', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {paymentMethods.map((method) => (
                                                <SelectItem key={method.value} value={method.value}>
                                                    {method.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="bank_name">Banque</Label>
                                    <Input
                                        id="bank_name"
                                        value={data.bank_name}
                                        onChange={(e) => setData('bank_name', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="bank_iban">IBAN</Label>
                                    <Input
                                        id="bank_iban"
                                        value={data.bank_iban}
                                        onChange={(e) => setData('bank_iban', e.target.value)}
                                        placeholder="DE..."
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="bank_bic">BIC</Label>
                                    <Input
                                        id="bank_bic"
                                        value={data.bank_bic}
                                        onChange={(e) => setData('bank_bic', e.target.value)}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Skills & Languages */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Compétences et Langues</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Skills */}
                                <div>
                                    <Label className="mb-2 block">Compétences</Label>
                                    <div className="flex gap-2 mb-2">
                                        <Input
                                            value={newSkill}
                                            onChange={(e) => setNewSkill(e.target.value)}
                                            placeholder="Ajouter une compétence"
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    addSkill();
                                                }
                                            }}
                                        />
                                        <Button type="button" variant="outline" onClick={addSkill}>
                                            <PlusIcon className="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {data.skills.map((skill, index) => (
                                            <span
                                                key={index}
                                                className="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-full text-sm"
                                            >
                                                {skill}
                                                <button
                                                    type="button"
                                                    onClick={() => removeSkill(skill)}
                                                    className="ml-1 hover:text-blue-600"
                                                >
                                                    <XMarkIcon className="h-4 w-4" />
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                </div>

                                {/* Languages */}
                                <div>
                                    <Label className="mb-2 block">Langues</Label>
                                    <div className="flex gap-2 mb-2">
                                        <Input
                                            value={newLanguage}
                                            onChange={(e) => setNewLanguage(e.target.value)}
                                            placeholder="Ajouter une langue"
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    addLanguage();
                                                }
                                            }}
                                        />
                                        <Button type="button" variant="outline" onClick={addLanguage}>
                                            <PlusIcon className="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {data.languages.map((lang, index) => (
                                            <span
                                                key={index}
                                                className="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-sm"
                                            >
                                                {lang}
                                                <button
                                                    type="button"
                                                    onClick={() => removeLanguage(lang)}
                                                    className="ml-1 hover:text-green-600"
                                                >
                                                    <XMarkIcon className="h-4 w-4" />
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Notes */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="notes">Notes</Label>
                                    <Textarea
                                        id="notes"
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        rows={4}
                                        placeholder="Notes générales..."
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="internal_notes">Notes internes</Label>
                                    <Textarea
                                        id="internal_notes"
                                        value={data.internal_notes}
                                        onChange={(e) => setData('internal_notes', e.target.value)}
                                        rows={4}
                                        placeholder="Notes confidentielles..."
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Visible uniquement par les gestionnaires
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Avatar Upload */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Photo</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div>
                                <Label htmlFor="avatar">Photo de profil</Label>
                                <Input
                                    id="avatar"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => {
                                        if (e.target.files?.[0]) {
                                            setData('avatar', e.target.files[0]);
                                        }
                                    }}
                                    className="mt-1"
                                />
                                {errors.avatar && (
                                    <p className="text-red-500 text-sm mt-1">{errors.avatar}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Submit */}
                    <div className="flex items-center justify-end gap-4">
                        <Button variant="outline" asChild>
                            <Link href="/employees">Annuler</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Création...' : 'Créer l\'employé'}
                        </Button>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
