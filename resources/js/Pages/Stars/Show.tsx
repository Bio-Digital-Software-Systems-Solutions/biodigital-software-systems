import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { Progress } from '@/Components/ui/progress';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    UserIcon,
    BuildingOfficeIcon,
    EnvelopeIcon,
    CalendarDaysIcon,
    ClockIcon,
    SparklesIcon,
    TrophyIcon,
    StarIcon as StarIconOutline,
    ExclamationTriangleIcon,
    PlayIcon,
    PauseIcon,
    AcademicCapIcon,
    NoSymbolIcon,
    ArrowPathIcon,
} from '@heroicons/react/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/react/24/solid';

interface Star {
    id: number;
    uuid: string;
    star_number: string;
    full_name: string;
    title: string | null;
    description: string | null;
    status: {
        value: string;
        label: string;
        color: string;
    };
    type: {
        value: string;
        label: string;
        color: string;
    };
    category: {
        value: string;
        label: string;
        color: string;
    } | null;
    points: number;
    level: number;
    level_title: string;
    next_level_points: number;
    progress_to_next_level: number;
    recognition_date: string | null;
    expiry_date: string | null;
    is_expired: boolean;
    days_until_expiry: number | null;
    service_duration: number | null;
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
    avatar: string | null;
    cover_image: string | null;
    is_public_profile: boolean;
    is_featured: boolean;
    display_order: number;
    testimonial: string | null;
    favorite_verse: string | null;
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
    nominator: {
        id: number;
        uuid: string;
        name: string;
    } | null;
}

interface Props {
    star: Star;
    canManage: boolean;
}

export default function StarShow({ star, canManage }: Props) {
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
            blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        };
        return colors[color] || colors.gray;
    };

    const getLevelColor = (level: number) => {
        const colors: Record<number, string> = {
            1: 'text-amber-600', // Bronze
            2: 'text-gray-400', // Argent
            3: 'text-yellow-500', // Or
            4: 'text-cyan-400', // Platine
            5: 'text-purple-400', // Diamant
        };
        return colors[level] || colors[1];
    };

    const handleDelete = () => {
        router.delete(`/stars/${star.uuid}`, {
            onSuccess: () => setShowDeleteDialog(false),
        });
    };

    const handleActivate = () => {
        router.post(`/stars/${star.uuid}/activate`);
    };

    const handleDeactivate = () => {
        router.post(`/stars/${star.uuid}/deactivate`);
    };

    const handleSetOnBreak = () => {
        router.post(`/stars/${star.uuid}/on-break`);
    };

    const handleGraduate = () => {
        router.post(`/stars/${star.uuid}/graduate`);
    };

    const handleSuspend = () => {
        router.post(`/stars/${star.uuid}/suspend`);
    };

    const handleToggleFeatured = () => {
        router.post(`/stars/${star.uuid}/toggle-featured`);
    };

    const formatDate = (date: string | null) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
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
            <Head title={`${star.full_name} - Star`} />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href="/stars"
                            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                        >
                            <ArrowLeftIcon className="h-4 w-4 mr-1" />
                            Retour
                        </Link>
                    </div>

                    {canManage && (
                        <div className="flex items-center gap-2">
                            {star.status.value !== 'active' && (
                                <Button variant="outline" onClick={handleActivate}>
                                    <PlayIcon className="h-4 w-4 mr-2" />
                                    Activer
                                </Button>
                            )}
                            {star.status.value === 'active' && (
                                <>
                                    <Button variant="outline" onClick={handleSetOnBreak}>
                                        <PauseIcon className="h-4 w-4 mr-2" />
                                        Pause
                                    </Button>
                                    <Button variant="outline" onClick={handleGraduate}>
                                        <AcademicCapIcon className="h-4 w-4 mr-2" />
                                        Diplômer
                                    </Button>
                                </>
                            )}
                            <Button
                                variant="outline"
                                onClick={handleToggleFeatured}
                                className={star.is_featured ? 'text-yellow-600' : ''}
                            >
                                {star.is_featured ? (
                                    <StarIconSolid className="h-4 w-4 mr-2" />
                                ) : (
                                    <StarIconOutline className="h-4 w-4 mr-2" />
                                )}
                                {star.is_featured ? 'Retirer vedette' : 'Vedette'}
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={`/stars/${star.uuid}/edit`}>
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

                {/* Cover Image */}
                {star.cover_image && (
                    <div className="h-48 rounded-lg overflow-hidden">
                        <img
                            src={star.cover_image}
                            alt="Cover"
                            className="w-full h-full object-cover"
                        />
                    </div>
                )}

                {/* Profile Header */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-start gap-6">
                            <div className="relative">
                                <Avatar className="h-24 w-24">
                                    {star.avatar ? (
                                        <AvatarImage src={star.avatar} />
                                    ) : null}
                                    <AvatarFallback className="text-2xl bg-yellow-100 text-yellow-600">
                                        {getInitials(star.full_name)}
                                    </AvatarFallback>
                                </Avatar>
                                {star.is_featured && (
                                    <StarIconSolid className="absolute -top-2 -right-2 h-8 w-8 text-yellow-400" />
                                )}
                            </div>

                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <h1 className="text-2xl font-bold">{star.full_name}</h1>
                                    <Badge className={getStatusBadgeClass(star.status.color)}>
                                        {star.status.label}
                                    </Badge>
                                    <Badge className={getStatusBadgeClass(star.type.color)}>
                                        {star.type.label}
                                    </Badge>
                                </div>
                                <p className="text-gray-500 mb-2">
                                    {star.title || 'Bénévole'}
                                </p>
                                <div className="flex flex-wrap gap-4 text-sm">
                                    <span className="flex items-center gap-1 text-gray-500">
                                        <UserIcon className="h-4 w-4" />
                                        {star.star_number}
                                    </span>
                                    {star.department && (
                                        <span className="flex items-center gap-1 text-gray-500">
                                            <BuildingOfficeIcon className="h-4 w-4" />
                                            {star.department.name}
                                        </span>
                                    )}
                                    {star.user?.email && (
                                        <span className="flex items-center gap-1 text-gray-500">
                                            <EnvelopeIcon className="h-4 w-4" />
                                            {star.user.email}
                                        </span>
                                    )}
                                    {star.recognition_date && (
                                        <span className="flex items-center gap-1 text-gray-500">
                                            <CalendarDaysIcon className="h-4 w-4" />
                                            Depuis {formatDate(star.recognition_date)}
                                        </span>
                                    )}
                                </div>
                            </div>

                            {/* Quick Stats */}
                            <div className="grid grid-cols-3 gap-4 text-center">
                                <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div className="flex items-center justify-center gap-1">
                                        <StarIconSolid className={`h-5 w-5 ${getLevelColor(star.level)}`} />
                                        <p className="text-xl font-bold">{star.level_title}</p>
                                    </div>
                                    <p className="text-xs text-gray-500">Niveau</p>
                                </div>
                                <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p className="text-2xl font-bold text-yellow-600">
                                        {star.points}
                                    </p>
                                    <p className="text-xs text-gray-500">Points</p>
                                </div>
                                <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p className="text-2xl font-bold text-blue-600">
                                        {star.total_hours_served}
                                    </p>
                                    <p className="text-xs text-gray-500">Heures servies</p>
                                </div>
                            </div>
                        </div>

                        {/* Progress to next level */}
                        <div className="mt-6">
                            <div className="flex justify-between text-sm mb-2">
                                <span className="text-gray-500">Progression vers le niveau suivant</span>
                                <span className="font-medium">{star.points} / {star.next_level_points} pts</span>
                            </div>
                            <Progress value={star.progress_to_next_level} className="h-2" />
                        </div>

                        {/* Alerts */}
                        {star.is_expired && (
                            <div className="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg flex items-center gap-2">
                                <ExclamationTriangleIcon className="h-5 w-5 text-red-600" />
                                <span className="text-sm text-red-800 dark:text-red-400">
                                    Profil expiré
                                </span>
                            </div>
                        )}
                        {star.days_until_expiry !== null && star.days_until_expiry <= 30 && !star.is_expired && (
                            <div className="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg flex items-center gap-2">
                                <ExclamationTriangleIcon className="h-5 w-5 text-yellow-600" />
                                <span className="text-sm text-yellow-800 dark:text-yellow-400">
                                    Profil expire dans {star.days_until_expiry} jours
                                </span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Tabs */}
                <Tabs defaultValue="info">
                    <TabsList>
                        <TabsTrigger value="info">Informations</TabsTrigger>
                        <TabsTrigger value="achievements">Récompenses</TabsTrigger>
                        <TabsTrigger value="availability">Disponibilité</TabsTrigger>
                        <TabsTrigger value="profile">Profil public</TabsTrigger>
                    </TabsList>

                    <TabsContent value="info" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Service Info */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Informations de service</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Date de reconnaissance</span>
                                        <span>{formatDate(star.recognition_date)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Durée de service</span>
                                        <span>{star.service_duration ? `${star.service_duration} mois` : '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Date d'expiration</span>
                                        <span>{formatDate(star.expiry_date)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Type</span>
                                        <Badge className={getStatusBadgeClass(star.type.color)}>
                                            {star.type.label}
                                        </Badge>
                                    </div>
                                    {star.category && (
                                        <div className="flex justify-between">
                                            <span className="text-gray-500">Catégorie</span>
                                            <Badge variant="outline">{star.category.label}</Badge>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Skills */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Compétences</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {star.skills && star.skills.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {star.skills.map((skill, index) => (
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

                            {/* Areas of Service */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Domaines de service</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {star.areas_of_service && star.areas_of_service.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {star.areas_of_service.map((area, index) => (
                                                <Badge key={index} variant="outline">
                                                    {area}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Aucun domaine enregistré</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Nominator */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Nominé par</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {star.nominator ? (
                                        <div className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <Avatar>
                                                <AvatarFallback>
                                                    {getInitials(star.nominator.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p className="font-medium">{star.nominator.name}</p>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Aucune nomination</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Description */}
                            {star.description && (
                                <Card className="md:col-span-2">
                                    <CardHeader>
                                        <CardTitle className="text-lg">Description</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="whitespace-pre-wrap">{star.description}</p>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    </TabsContent>

                    <TabsContent value="achievements" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Achievements */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg flex items-center gap-2">
                                        <TrophyIcon className="h-5 w-5 text-yellow-500" />
                                        Réalisations
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {star.achievements && star.achievements.length > 0 ? (
                                        <div className="space-y-2">
                                            {star.achievements.map((achievement, index) => (
                                                <div key={index} className="flex items-center gap-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                                    <TrophyIcon className="h-4 w-4 text-yellow-600" />
                                                    <span>{achievement}</span>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Aucune réalisation</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Badges */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg flex items-center gap-2">
                                        <SparklesIcon className="h-5 w-5 text-purple-500" />
                                        Badges
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {star.badges && star.badges.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {star.badges.map((badge, index) => (
                                                <Badge key={index} className="bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                    <SparklesIcon className="h-3 w-3 mr-1" />
                                                    {badge}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Aucun badge</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Level Progress */}
                            <Card className="md:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-lg">Progression des niveaux</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center justify-between gap-4">
                                        {[1, 2, 3, 4, 5].map((level) => (
                                            <div
                                                key={level}
                                                className={`flex-1 text-center p-4 rounded-lg ${
                                                    level <= star.level
                                                        ? 'bg-yellow-100 dark:bg-yellow-900/30'
                                                        : 'bg-gray-100 dark:bg-gray-800'
                                                }`}
                                            >
                                                <StarIconSolid
                                                    className={`h-8 w-8 mx-auto ${
                                                        level <= star.level
                                                            ? getLevelColor(level)
                                                            : 'text-gray-300'
                                                    }`}
                                                />
                                                <p className="text-sm font-medium mt-2">
                                                    {level === 1 && 'Bronze'}
                                                    {level === 2 && 'Argent'}
                                                    {level === 3 && 'Or'}
                                                    {level === 4 && 'Platine'}
                                                    {level === 5 && 'Diamant'}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    {level === 1 && '0 pts'}
                                                    {level === 2 && '100 pts'}
                                                    {level === 3 && '250 pts'}
                                                    {level === 4 && '500 pts'}
                                                    {level === 5 && '1000 pts'}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="availability" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Schedule */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Horaires</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Heures/semaine</span>
                                        <span>{star.hours_per_week ? `${star.hours_per_week}h` : '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Disponible de</span>
                                        <span>
                                            {star.available_from && star.available_to
                                                ? `${star.available_from} - ${star.available_to}`
                                                : '-'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-gray-500 block mb-2">Jours disponibles</span>
                                        <div className="flex gap-2">
                                            {['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'].map(
                                                (day) => (
                                                    <span
                                                        key={day}
                                                        className={`px-2 py-1 text-xs rounded ${
                                                            star.available_days?.includes(day)
                                                                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
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

                            {/* Contact Preferences */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Préférences de contact</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Joignable</span>
                                        <Badge variant={star.is_contactable ? 'default' : 'secondary'}>
                                            {star.is_contactable ? 'Oui' : 'Non'}
                                        </Badge>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Méthode préférée</span>
                                        <span className="capitalize">{star.preferred_contact_method || '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Notifications</span>
                                        <Badge variant={star.receive_notifications ? 'default' : 'secondary'}>
                                            {star.receive_notifications ? 'Activées' : 'Désactivées'}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="profile" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Bio */}
                            <Card className="md:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-lg">Biographie</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {star.bio ? (
                                        <p className="whitespace-pre-wrap">{star.bio}</p>
                                    ) : (
                                        <p className="text-gray-500">Aucune biographie</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Testimonial */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Témoignage</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {star.testimonial ? (
                                        <blockquote className="italic text-gray-600 dark:text-gray-400 border-l-4 border-yellow-400 pl-4">
                                            "{star.testimonial}"
                                        </blockquote>
                                    ) : (
                                        <p className="text-gray-500">Aucun témoignage</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Favorite Verse */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Verset préféré</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {star.favorite_verse ? (
                                        <p className="text-gray-600 dark:text-gray-400">{star.favorite_verse}</p>
                                    ) : (
                                        <p className="text-gray-500">Aucun verset</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Visibility Settings */}
                            <Card className="md:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-lg">Paramètres de visibilité</CardTitle>
                                </CardHeader>
                                <CardContent className="flex gap-4">
                                    <Badge variant={star.is_public_profile ? 'default' : 'secondary'}>
                                        {star.is_public_profile ? 'Profil public' : 'Profil privé'}
                                    </Badge>
                                    {star.is_featured && (
                                        <Badge className="bg-yellow-100 text-yellow-800">
                                            <StarIconSolid className="h-3 w-3 mr-1" />
                                            En vedette
                                        </Badge>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>
                </Tabs>

                {/* Notes */}
                {(star.notes || star.internal_notes) && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {star.notes && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Notes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap">{star.notes}</p>
                                </CardContent>
                            </Card>
                        )}
                        {star.internal_notes && canManage && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Notes internes</CardTitle>
                                    <CardDescription>Visible uniquement par les gestionnaires</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap">{star.internal_notes}</p>
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
                title="Supprimer cette star ?"
                description="Cette action est irréversible. Toutes les données de la star seront supprimées."
            />
        </DashboardLayout>
    );
}
