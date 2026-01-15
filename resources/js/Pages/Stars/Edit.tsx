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
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { ArrowLeftIcon, PlusIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';

interface Star {
    id: number;
    uuid: string;
    user_id: number;
    department_id: number | null;
    nominated_by: number | null;
    star_number: string;
    title: string | null;
    description: string | null;
    status: string;
    type: string;
    category: string | null;
    points: number;
    level: number;
    recognition_date: string | null;
    expiry_date: string | null;
    achievements: string[] | null;
    badges: string[] | null;
    skills: string[] | null;
    areas_of_service: string[] | null;
    available_days: string[] | null;
    available_from: string | null;
    available_to: string | null;
    hours_per_week: number | null;
    total_hours_served: number;
    is_contactable: boolean;
    preferred_contact_method: string | null;
    receive_notifications: boolean;
    bio: string | null;
    is_public_profile: boolean;
    is_featured: boolean;
    testimonial: string | null;
    favorite_verse: string | null;
    notes: string | null;
    internal_notes: string | null;
    avatar: string | null;
    cover_image: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Nominator {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
}

interface SelectOption {
    value: string;
    label: string;
}

interface Props {
    star: Star;
    departments: Department[];
    nominators: Nominator[];
    statuses: SelectOption[];
    types: SelectOption[];
    categories: SelectOption[];
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

export default function StarEdit({
    star,
    departments,
    nominators,
    statuses,
    types,
    categories,
}: Props) {
    const [newSkill, setNewSkill] = useState('');
    const [newArea, setNewArea] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        department_id: star.department_id?.toString() || '',
        nominated_by: star.nominated_by?.toString() || '',
        title: star.title || '',
        description: star.description || '',
        status: star.status,
        type: star.type,
        category: star.category || '',
        points: star.points.toString(),
        level: star.level.toString(),
        recognition_date: star.recognition_date || '',
        expiry_date: star.expiry_date || '',
        achievements: star.achievements || [],
        badges: star.badges || [],
        skills: star.skills || [],
        areas_of_service: star.areas_of_service || [],
        available_days: star.available_days || [],
        available_from: star.available_from || '',
        available_to: star.available_to || '',
        hours_per_week: star.hours_per_week?.toString() || '',
        total_hours_served: star.total_hours_served.toString(),
        is_contactable: star.is_contactable,
        preferred_contact_method: star.preferred_contact_method || 'email',
        receive_notifications: star.receive_notifications,
        bio: star.bio || '',
        is_public_profile: star.is_public_profile,
        is_featured: star.is_featured,
        testimonial: star.testimonial || '',
        favorite_verse: star.favorite_verse || '',
        notes: star.notes || '',
        internal_notes: star.internal_notes || '',
        avatar: null as File | null,
        cover_image: null as File | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/stars/${star.uuid}`, {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Star mis à jour avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la mise à jour');
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

    const addArea = () => {
        if (newArea.trim() && !data.areas_of_service.includes(newArea.trim())) {
            setData('areas_of_service', [...data.areas_of_service, newArea.trim()]);
            setNewArea('');
        }
    };

    const removeArea = (area: string) => {
        setData('areas_of_service', data.areas_of_service.filter((a) => a !== area));
    };

    const toggleAvailableDay = (day: string) => {
        if (data.available_days.includes(day)) {
            setData('available_days', data.available_days.filter((d) => d !== day));
        } else {
            setData('available_days', [...data.available_days, day]);
        }
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return (
        <DashboardLayout>
            <Head title={`Modifier ${star.user?.name || 'Star'}`} />

            <div className="p-6 max-w-5xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Link
                        href={`/stars/${star.uuid}`}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-1" />
                        Retour au profil
                    </Link>
                    <div className="flex items-center gap-4">
                        <Avatar className="h-12 w-12">
                            {star.avatar ? (
                                <AvatarImage src={star.avatar} />
                            ) : null}
                            <AvatarFallback className="bg-yellow-100 text-yellow-600">
                                {star.user ? getInitials(star.user.name) : 'ST'}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Modifier {star.user?.name || 'Star'}
                            </h1>
                            <p className="text-sm text-gray-500">
                                {star.star_number}
                            </p>
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Title & Department */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Titre et Département</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="title">Titre</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="Ex: Bénévole du mois, Star de l'accueil..."
                                        className={errors.title ? 'border-red-500' : ''}
                                    />
                                    {errors.title && (
                                        <p className="text-red-500 text-sm mt-1">{errors.title}</p>
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
                                    <Label htmlFor="nominated_by">Nominé par</Label>
                                    <Select
                                        value={data.nominated_by}
                                        onValueChange={(value) => setData('nominated_by', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner un nominateur" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Aucun</SelectItem>
                                            {nominators.map((nom) => (
                                                <SelectItem key={nom.id} value={nom.id.toString()}>
                                                    {nom.first_name} {nom.last_name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="md:col-span-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={3}
                                        placeholder="Description du rôle et des responsabilités..."
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Status & Type */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Statut et Type</CardTitle>
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
                                    <Label htmlFor="type">Type *</Label>
                                    <Select
                                        value={data.type}
                                        onValueChange={(value) => setData('type', value)}
                                    >
                                        <SelectTrigger className={errors.type ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Sélectionner un type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {types.map((type) => (
                                                <SelectItem key={type.value} value={type.value}>
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.type && (
                                        <p className="text-red-500 text-sm mt-1">{errors.type}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="category">Catégorie</Label>
                                    <Select
                                        value={data.category}
                                        onValueChange={(value) => setData('category', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner une catégorie" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Aucune</SelectItem>
                                            {categories.map((cat) => (
                                                <SelectItem key={cat.value} value={cat.value}>
                                                    {cat.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Points & Dates */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Points et Dates</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                                <div>
                                    <Label htmlFor="points">Points</Label>
                                    <Input
                                        id="points"
                                        type="number"
                                        min="0"
                                        value={data.points}
                                        onChange={(e) => setData('points', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="level">Niveau (1-5)</Label>
                                    <Input
                                        id="level"
                                        type="number"
                                        min="1"
                                        max="5"
                                        value={data.level}
                                        onChange={(e) => setData('level', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="total_hours_served">Total heures servies</Label>
                                    <Input
                                        id="total_hours_served"
                                        type="number"
                                        min="0"
                                        value={data.total_hours_served}
                                        onChange={(e) => setData('total_hours_served', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="recognition_date">Date de reconnaissance</Label>
                                    <Input
                                        id="recognition_date"
                                        type="date"
                                        value={data.recognition_date}
                                        onChange={(e) => setData('recognition_date', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="expiry_date">Date d'expiration</Label>
                                    <Input
                                        id="expiry_date"
                                        type="date"
                                        value={data.expiry_date}
                                        onChange={(e) => setData('expiry_date', e.target.value)}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Availability */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Disponibilité</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <Label htmlFor="hours_per_week">Heures/semaine</Label>
                                        <Input
                                            id="hours_per_week"
                                            type="number"
                                            min="0"
                                            value={data.hours_per_week}
                                            onChange={(e) => setData('hours_per_week', e.target.value)}
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="available_from">Disponible de</Label>
                                        <Input
                                            id="available_from"
                                            type="time"
                                            value={data.available_from}
                                            onChange={(e) => setData('available_from', e.target.value)}
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="available_to">Disponible à</Label>
                                        <Input
                                            id="available_to"
                                            type="time"
                                            value={data.available_to}
                                            onChange={(e) => setData('available_to', e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div>
                                    <Label className="mb-2 block">Jours disponibles</Label>
                                    <div className="flex flex-wrap gap-4">
                                        {DAYS_OF_WEEK.map((day) => (
                                            <div key={day.value} className="flex items-center space-x-2">
                                                <Checkbox
                                                    id={day.value}
                                                    checked={data.available_days.includes(day.value)}
                                                    onCheckedChange={() => toggleAvailableDay(day.value)}
                                                />
                                                <Label htmlFor={day.value} className="cursor-pointer">
                                                    {day.label}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Skills & Areas of Service */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Compétences et Domaines</CardTitle>
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

                                {/* Areas of Service */}
                                <div>
                                    <Label className="mb-2 block">Domaines de service</Label>
                                    <div className="flex gap-2 mb-2">
                                        <Input
                                            value={newArea}
                                            onChange={(e) => setNewArea(e.target.value)}
                                            placeholder="Ajouter un domaine"
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    addArea();
                                                }
                                            }}
                                        />
                                        <Button type="button" variant="outline" onClick={addArea}>
                                            <PlusIcon className="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {data.areas_of_service.map((area, index) => (
                                            <span
                                                key={index}
                                                className="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full text-sm"
                                            >
                                                {area}
                                                <button
                                                    type="button"
                                                    onClick={() => removeArea(area)}
                                                    className="ml-1 hover:text-yellow-600"
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

                    {/* Contact Preferences */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Préférences de Contact</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_contactable"
                                        checked={data.is_contactable}
                                        onCheckedChange={(checked) => setData('is_contactable', checked as boolean)}
                                    />
                                    <Label htmlFor="is_contactable" className="cursor-pointer">
                                        Joignable
                                    </Label>
                                </div>

                                <div>
                                    <Label htmlFor="preferred_contact_method">Méthode préférée</Label>
                                    <Select
                                        value={data.preferred_contact_method}
                                        onValueChange={(value) => setData('preferred_contact_method', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="email">Email</SelectItem>
                                            <SelectItem value="phone">Téléphone</SelectItem>
                                            <SelectItem value="sms">SMS</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="receive_notifications"
                                        checked={data.receive_notifications}
                                        onCheckedChange={(checked) => setData('receive_notifications', checked as boolean)}
                                    />
                                    <Label htmlFor="receive_notifications" className="cursor-pointer">
                                        Recevoir les notifications
                                    </Label>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Profile Settings */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Paramètres du Profil</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex gap-6">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="is_public_profile"
                                            checked={data.is_public_profile}
                                            onCheckedChange={(checked) => setData('is_public_profile', checked as boolean)}
                                        />
                                        <Label htmlFor="is_public_profile" className="cursor-pointer">
                                            Profil public
                                        </Label>
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="is_featured"
                                            checked={data.is_featured}
                                            onCheckedChange={(checked) => setData('is_featured', checked as boolean)}
                                        />
                                        <Label htmlFor="is_featured" className="cursor-pointer">
                                            En vedette
                                        </Label>
                                    </div>
                                </div>

                                <div>
                                    <Label htmlFor="bio">Biographie</Label>
                                    <Textarea
                                        id="bio"
                                        value={data.bio}
                                        onChange={(e) => setData('bio', e.target.value)}
                                        rows={3}
                                        placeholder="Courte biographie..."
                                    />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="testimonial">Témoignage</Label>
                                        <Textarea
                                            id="testimonial"
                                            value={data.testimonial}
                                            onChange={(e) => setData('testimonial', e.target.value)}
                                            rows={2}
                                            placeholder="Témoignage personnel..."
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="favorite_verse">Verset préféré</Label>
                                        <Input
                                            id="favorite_verse"
                                            value={data.favorite_verse}
                                            onChange={(e) => setData('favorite_verse', e.target.value)}
                                            placeholder="Ex: Philippiens 4:13"
                                        />
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

                    {/* Images Upload */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Images</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="avatar">Photo de profil</Label>
                                    {star.avatar && (
                                        <div className="mb-2">
                                            <img
                                                src={star.avatar}
                                                alt="Current avatar"
                                                className="h-20 w-20 rounded-full object-cover"
                                            />
                                        </div>
                                    )}
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

                                <div>
                                    <Label htmlFor="cover_image">Image de couverture</Label>
                                    {star.cover_image && (
                                        <div className="mb-2">
                                            <img
                                                src={star.cover_image}
                                                alt="Current cover"
                                                className="h-20 w-full rounded object-cover"
                                            />
                                        </div>
                                    )}
                                    <Input
                                        id="cover_image"
                                        type="file"
                                        accept="image/*"
                                        onChange={(e) => {
                                            if (e.target.files?.[0]) {
                                                setData('cover_image', e.target.files[0]);
                                            }
                                        }}
                                        className="mt-1"
                                    />
                                    {errors.cover_image && (
                                        <p className="text-red-500 text-sm mt-1">{errors.cover_image}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Submit */}
                    <div className="flex items-center justify-end gap-4">
                        <Button variant="outline" asChild>
                            <Link href={`/stars/${star.uuid}`}>Annuler</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Mise à jour...' : 'Enregistrer les modifications'}
                        </Button>
                    </div>
                </form>
            </div>
        </DashboardLayout>
    );
}
