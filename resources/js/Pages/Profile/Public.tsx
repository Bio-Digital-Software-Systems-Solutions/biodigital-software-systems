import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowLeftIcon,
    BuildingOfficeIcon,
    UserGroupIcon,
    AcademicCapIcon,
    EnvelopeIcon,
    PhoneIcon,
    MapPinIcon,
    GlobeAltIcon,
    HeartIcon,
    BriefcaseIcon,
    ShieldCheckIcon,
    CalendarDaysIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/react/24/outline';

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Group {
    id: number;
    uuid: string;
    name: string;
}

interface Training {
    id: number;
    uuid: string;
    title: string;
}

interface SpokenLanguage {
    id: number;
    uuid: string;
    name: string;
    code: string;
    native_name: string | null;
    level: 'beginner' | 'intermediate' | 'advanced' | 'native';
}

interface Interest {
    id: number;
    uuid: string;
    name: string;
    icon: string | null;
}

interface Skill {
    id: number;
    uuid: string;
    name: string;
    category: 'soft' | 'hard' | 'technical';
    level: 'beginner' | 'intermediate' | 'advanced' | 'expert' | null;
}

interface User {
    id: number;
    name: string;
    first_name: string | null;
    last_name: string | null;
    email: string;
    phone_number: string | null;
    avatar: string | null;
    bio: string | null;
    position: string | null;
    address: string | null;
    languages: SpokenLanguage[];
    interests: Interest[];
    skills: Skill[];
    is_calendar_public: boolean;
    departments: Department[];
    groups: Group[];
    trainings: Training[];
    roles: string[];
}

interface Props {
    user: User;
}

export default function PublicProfile({ user }: Props) {
    const getInitials = (name: string) => {
        if (!name) return '?';
        const parts = name.split(' ');
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
        }
        return name.charAt(0).toUpperCase();
    };

    const getRoleColor = (role: string) => {
        const colors: Record<string, string> = {
            admin: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'project-manager': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'event-manager': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            writer: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            pastor: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
            member: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
        return colors[role] || colors.member;
    };

    const getLanguageLevelLabel = (level: string) => {
        const labels: Record<string, string> = {
            beginner: 'Débutant',
            intermediate: 'Intermédiaire',
            advanced: 'Avancé',
            native: 'Natif',
        };
        return labels[level] || level;
    };

    const getSkillLevelLabel = (level: string | null) => {
        if (!level) return null;
        const labels: Record<string, string> = {
            beginner: 'Débutant',
            intermediate: 'Intermédiaire',
            advanced: 'Avancé',
            expert: 'Expert',
        };
        return labels[level] || level;
    };

    const getSkillCategoryLabel = (category: string) => {
        const labels: Record<string, string> = {
            soft: 'Soft Skills',
            hard: 'Hard Skills',
            technical: 'Compétences techniques',
        };
        return labels[category] || category;
    };

    const getSkillCategoryColor = (category: string) => {
        const colors: Record<string, string> = {
            soft: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            hard: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
            technical: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        };
        return colors[category] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    };

    // Group skills by category
    const skillsByCategory = user.skills.reduce((acc, skill) => {
        if (!acc[skill.category]) {
            acc[skill.category] = [];
        }
        acc[skill.category].push(skill);
        return acc;
    }, {} as Record<string, Skill[]>);

    return (
        <DashboardLayout>
            <Head title={`${user.name} - Profil`} />

            <div className="p-4 bg-gray-50 dark:bg-gray-900 min-h-screen">
                {/* Breadcrumb */}
                <div className="mb-6">
                    <Link
                        href="#"
                        onClick={(e) => {
                            e.preventDefault();
                            window.history.back();
                        }}
                        className="inline-flex items-center text-sm font-medium text-gray-600 hover:text-icc-blue dark:text-gray-400 dark:hover:text-icc-blue transition-colors"
                    >
                        <ArrowLeftIcon className="w-4 h-4 mr-2" />
                        Retour
                    </Link>
                </div>

                {/* Hero Section */}
                <div className="relative mb-8">
                    <div className="relative h-[200px] md:h-[280px] rounded-2xl overflow-hidden shadow-2xl bg-gradient-to-r from-icc-blue via-icc-purple to-icc-red">
                        <div className="absolute inset-0 bg-gradient-to-b from-transparent to-black/40" />

                        {/* Profile Info Overlay */}
                        <div className="absolute bottom-0 left-0 right-0 p-6 md:p-8">
                            <div className="flex items-end gap-6">
                                {/* Avatar */}
                                {user.avatar ? (
                                    <img
                                        src={`/storage/${user.avatar}`}
                                        alt={user.name}
                                        className="h-24 w-24 md:h-32 md:w-32 rounded-full object-cover border-4 border-white shadow-xl"
                                    />
                                ) : (
                                    <div className="h-24 w-24 md:h-32 md:w-32 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white text-3xl md:text-4xl font-bold border-4 border-white/50 shadow-xl">
                                        {getInitials(user.name)}
                                    </div>
                                )}

                                {/* Name, Position and Roles */}
                                <div className="flex-1 pb-2">
                                    <h1 className="text-3xl md:text-4xl font-bold text-white mb-1 drop-shadow-lg">
                                        {user.name}
                                    </h1>
                                    {user.position && (
                                        <p className="text-white/90 text-lg mb-2 flex items-center gap-2">
                                            <BriefcaseIcon className="h-5 w-5" />
                                            {user.position}
                                        </p>
                                    )}
                                    <div className="flex flex-wrap gap-2">
                                        {user.roles.map((role) => (
                                            <Badge
                                                key={role}
                                                className={`${getRoleColor(role)} border-0`}
                                            >
                                                <ShieldCheckIcon className="h-3 w-3 mr-1" />
                                                {role}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column - Contact Info & Bio */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Bio Section */}
                        <Card className="bg-white dark:bg-gray-800 shadow-lg">
                            <CardHeader>
                                <CardTitle>À propos</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {user.bio ? (
                                    <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                        {user.bio}
                                    </p>
                                ) : (
                                    <p className="text-gray-400 dark:text-gray-500 italic">
                                        Aucune description renseignée
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Contact Information */}
                        <Card className="bg-white dark:bg-gray-800 shadow-lg">
                            <CardHeader>
                                <CardTitle>Coordonnées</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-2">
                                    {/* Email */}
                                    <div className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <div className="p-2 rounded-lg bg-icc-blue/10 dark:bg-icc-blue/20">
                                            <EnvelopeIcon className="h-5 w-5 text-icc-blue" />
                                        </div>
                                        <div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">Email</p>
                                            <a
                                                href={`mailto:${user.email}`}
                                                className="text-gray-900 dark:text-white hover:text-icc-blue dark:hover:text-icc-blue transition-colors"
                                            >
                                                {user.email}
                                            </a>
                                        </div>
                                    </div>

                                    {/* Phone */}
                                    <div className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <div className="p-2 rounded-lg bg-green-100 dark:bg-green-900/30">
                                            <PhoneIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                                        </div>
                                        <div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">Téléphone</p>
                                            {user.phone_number ? (
                                                <a
                                                    href={`tel:${user.phone_number}`}
                                                    className="text-gray-900 dark:text-white hover:text-icc-blue dark:hover:text-icc-blue transition-colors"
                                                >
                                                    {user.phone_number}
                                                </a>
                                            ) : (
                                                <span className="text-gray-400 dark:text-gray-500 italic text-sm">
                                                    Non renseigné
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Address */}
                                    {user.address && (
                                        <div className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                            <div className="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                                                <MapPinIcon className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            </div>
                                            <div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">Adresse</p>
                                                <p className="text-gray-900 dark:text-white">
                                                    {user.address}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Languages & Interests */}
                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Languages */}
                            <Card className="bg-white dark:bg-gray-800 shadow-lg">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center gap-2">
                                        <GlobeAltIcon className="h-5 w-5 text-gray-500" />
                                        <CardTitle className="text-base">Langues parlées</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {user.languages && user.languages.length > 0 ? (
                                        <div className="space-y-2">
                                            {user.languages.map((language) => (
                                                <div
                                                    key={language.id}
                                                    className="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                                >
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-gray-900 dark:text-white font-medium">
                                                            {language.name}
                                                        </span>
                                                        {language.native_name && language.native_name !== language.name && (
                                                            <span className="text-gray-500 dark:text-gray-400 text-sm">
                                                                ({language.native_name})
                                                            </span>
                                                        )}
                                                    </div>
                                                    <Badge
                                                        variant="secondary"
                                                        className="bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 text-xs"
                                                    >
                                                        {getLanguageLevelLabel(language.level)}
                                                    </Badge>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-400 dark:text-gray-500 italic text-sm">
                                            Aucune langue renseignée
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Interests */}
                            <Card className="bg-white dark:bg-gray-800 shadow-lg">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center gap-2">
                                        <HeartIcon className="h-5 w-5 text-gray-500" />
                                        <CardTitle className="text-base">Centres d'intérêt</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {user.interests && user.interests.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {user.interests.map((interest) => (
                                                <Badge
                                                    key={interest.id}
                                                    variant="secondary"
                                                    className="bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300"
                                                >
                                                    {interest.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-400 dark:text-gray-500 italic text-sm">
                                            Aucun centre d'intérêt renseigné
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Skills Section */}
                        <Card className="bg-white dark:bg-gray-800 shadow-lg">
                            <CardHeader className="pb-3">
                                <div className="flex items-center gap-2">
                                    <WrenchScrewdriverIcon className="h-5 w-5 text-gray-500" />
                                    <CardTitle className="text-base">Compétences</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {user.skills && user.skills.length > 0 ? (
                                    <div className="space-y-4">
                                        {(['soft', 'hard', 'technical'] as const).map((category) => {
                                            const categorySkills = skillsByCategory[category];
                                            if (!categorySkills || categorySkills.length === 0) return null;

                                            return (
                                                <div key={category}>
                                                    <h4 className="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">
                                                        {getSkillCategoryLabel(category)}
                                                    </h4>
                                                    <div className="flex flex-wrap gap-2">
                                                        {categorySkills.map((skill) => (
                                                            <Badge
                                                                key={skill.id}
                                                                variant="secondary"
                                                                className={getSkillCategoryColor(skill.category)}
                                                            >
                                                                {skill.name}
                                                                {skill.level && (
                                                                    <span className="ml-1 opacity-70">
                                                                        · {getSkillLevelLabel(skill.level)}
                                                                    </span>
                                                                )}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <p className="text-gray-400 dark:text-gray-500 italic text-sm">
                                        Aucune compétence renseignée
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column - Departments, Groups, Trainings */}
                    <div className="space-y-6">
                        {/* Departments */}
                        <Card className="bg-white dark:bg-gray-800 shadow-lg">
                            <CardHeader className="pb-3">
                                <div className="flex items-center gap-2">
                                    <BuildingOfficeIcon className="h-5 w-5 text-gray-500" />
                                    <CardTitle className="text-base">Départements</CardTitle>
                                </div>
                                {user.departments.length > 0 && (
                                    <CardDescription>
                                        {user.departments.length} département{user.departments.length > 1 ? 's' : ''}
                                    </CardDescription>
                                )}
                            </CardHeader>
                            <CardContent>
                                {user.departments.length > 0 ? (
                                    <div className="space-y-2">
                                        {user.departments.map((dept) => (
                                            <Link
                                                key={dept.id}
                                                href={`/departments/${dept.uuid}`}
                                                className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group"
                                            >
                                                <div className="p-2 rounded-lg bg-icc-blue/10 dark:bg-icc-blue/20 group-hover:bg-icc-blue/20 transition-colors">
                                                    <BuildingOfficeIcon className="h-4 w-4 text-icc-blue" />
                                                </div>
                                                <span className="font-medium text-gray-900 dark:text-white group-hover:text-icc-blue transition-colors text-sm">
                                                    {dept.name}
                                                </span>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-6 text-gray-500 dark:text-gray-400">
                                        <BuildingOfficeIcon className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">Aucun département</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Groups */}
                        <Card className="bg-white dark:bg-gray-800 shadow-lg">
                            <CardHeader className="pb-3">
                                <div className="flex items-center gap-2">
                                    <UserGroupIcon className="h-5 w-5 text-gray-500" />
                                    <CardTitle className="text-base">Groupes</CardTitle>
                                </div>
                                {user.groups.length > 0 && (
                                    <CardDescription>
                                        {user.groups.length} groupe{user.groups.length > 1 ? 's' : ''}
                                    </CardDescription>
                                )}
                            </CardHeader>
                            <CardContent>
                                {user.groups.length > 0 ? (
                                    <div className="space-y-2">
                                        {user.groups.map((group) => (
                                            <Link
                                                key={group.id}
                                                href={`/groups/${group.uuid}`}
                                                className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group"
                                            >
                                                <div className="p-2 rounded-lg bg-green-100 dark:bg-green-900/30 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
                                                    <UserGroupIcon className="h-4 w-4 text-green-600 dark:text-green-400" />
                                                </div>
                                                <span className="font-medium text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors text-sm">
                                                    {group.name}
                                                </span>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-6 text-gray-500 dark:text-gray-400">
                                        <UserGroupIcon className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">Aucun groupe</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Trainings */}
                        <Card className="bg-white dark:bg-gray-800 shadow-lg">
                            <CardHeader className="pb-3">
                                <div className="flex items-center gap-2">
                                    <AcademicCapIcon className="h-5 w-5 text-gray-500" />
                                    <CardTitle className="text-base">Formations</CardTitle>
                                </div>
                                {user.trainings.length > 0 && (
                                    <CardDescription>
                                        {user.trainings.length} formation{user.trainings.length > 1 ? 's' : ''}
                                    </CardDescription>
                                )}
                            </CardHeader>
                            <CardContent>
                                {user.trainings.length > 0 ? (
                                    <div className="space-y-2">
                                        {user.trainings.map((training) => (
                                            <Link
                                                key={training.id}
                                                href={`/trainings/${training.uuid}`}
                                                className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group"
                                            >
                                                <div className="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/30 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                                                    <AcademicCapIcon className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                                </div>
                                                <span className="font-medium text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors text-sm">
                                                    {training.title}
                                                </span>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-6 text-gray-500 dark:text-gray-400">
                                        <AcademicCapIcon className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">Aucune formation</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Public Calendar Link */}
                        {user.is_calendar_public && (
                            <Card className="bg-white dark:bg-gray-800 shadow-lg">
                                <CardContent className="pt-6">
                                    <Link
                                        href={`/calendar/user/${user.id}`}
                                        className="flex items-center gap-3 p-4 bg-gradient-to-r from-icc-blue/10 to-icc-purple/10 dark:from-icc-blue/20 dark:to-icc-purple/20 rounded-xl hover:from-icc-blue/20 hover:to-icc-purple/20 dark:hover:from-icc-blue/30 dark:hover:to-icc-purple/30 transition-colors group"
                                    >
                                        <div className="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-sm">
                                            <CalendarDaysIcon className="h-5 w-5 text-icc-blue" />
                                        </div>
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white group-hover:text-icc-blue transition-colors">
                                                Voir l'agenda
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                Calendrier public
                                            </p>
                                        </div>
                                    </Link>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
